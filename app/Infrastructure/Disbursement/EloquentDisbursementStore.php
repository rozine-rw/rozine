<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\Contracts\StaffConnections;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\VerifiedDestination;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\VerifyAuthenticator;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Disbursement\DisbursementReason;
use App\Domain\Disbursement\DisbursementState;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\IntentDigest;
use App\Domain\Disbursement\IssueSchedule;
use App\Domain\Disbursement\PayoutOutcome;
use App\Domain\Disbursement\Reconciliation;
use App\Domain\Disbursement\StaffIndependence;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIndependenceDeclaration;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementProviderEvent;
use App\Models\DisbursementReconciliation;
use App\Models\DisbursementStepUpMarker;
use App\Models\DisbursementStepUpProof;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Disbursement persistence (C3 v2 §2e, MC-02, MC-08). Every effect runs in one transaction in the
 * lock order confirmed on #96 (5871859618):
 *
 *   FundedCampaigns::lockBusiness → staff users in ascending id (AuthorizeStaffPermission::handle
 *   for the actor, ::currentlyHolds for the recorded maker or checker) → FundedCampaigns::lockFunded
 *   → disbursements row → step-up proof → (inside the port) reservations and commitments → wallets
 *   → ledger.
 *
 * The step-up exchange and hold/reject/release skip the campaign; the worker takes the maker and
 * checker instead of an actor; the reconciler takes no staff lock at all, so a sent, verified
 * payout settles whatever happened to staff since. Nothing here calls the payout provider.
 */
final class EloquentDisbursementStore implements DisbursementStore
{
    public const string POLICY_VERSION = 'engineering-2026-09-23.4';

    /** @var array<string, string> */
    public const array PERMISSIONS = ['authorize' => 'disbursements.authorize', 'approve' => 'disbursements.approve', 'reject' => 'disbursements.approve',
        'hold' => 'disbursements.hold', 'release_hold' => 'disbursements.approve', 'requery' => 'disbursements.requery'];

    private const array STAFF_EVENTS = ['authorize' => ['authorized', 'DISBURSEMENT_AUTHORIZED'], 'reject' => ['rejected', 'DISBURSEMENT_AUTHORIZATION_REJECTED'],
        'hold' => ['held', 'DISBURSEMENT_HELD'], 'release_hold' => ['hold_released', 'DISBURSEMENT_HOLD_RELEASED']];

    public function __construct(private FundedCampaigns $funding, private PayoutDestinations $destinations, private StaffConnections $connections,
        private PayoutProvider $provider, private AuthorizeStaffPermission $staff, private OperationJournal $journal, private CanonicalJson $json,
        private VerifyAuthenticator $authenticator, private Repository $config, private EnvironmentIsolation $isolation,
        private IdentityAccessStore $identities) {}

    public function page(int $userId, ?string $disbursementId, ?string $before, int $limit): array
    {
        $this->staff->check($userId, 'disbursements.view');
        $permissions = $this->staff->permissions($userId);
        $found = Disbursement::query()->when($before !== null, fn ($query) => $query->where('id', '<', $before))->orderByDesc('id')->limit($limit + 1)->get();
        $rows = $found->take($limit)->values();
        $selected = $disbursementId === null ? null : Disbursement::query()->find($disbursementId);

        return ['permissions' => $permissions, 'rows' => $rows->map(fn (Disbursement $disbursement): array => $this->row($disbursement))->all(),
            'next_cursor' => $found->count() > $limit ? $rows->last()?->id : null,
            'awaiting_second_approver' => (int) DB::selectOne("SELECT count(*) AS total FROM disbursements d
                WHERE (SELECT f_state FROM disbursement_fold(d.id)) = 'awaiting_second_approver'")?->total,
            'disbursement' => $selected === null ? null : $this->detail($selected, $userId, $permissions),
            'refusal' => $disbursementId !== null && $selected === null ? ['code' => 'DISBURSEMENT_NOT_FOUND', 'status' => 404] : null];
    }

    public function command(int $userId, string $disbursementId, string $command, int $expectedRevision, string $reason, string $requestId, ?string $stepUpProof, bool $independenceDeclared = false): array
    {
        $permission = self::PERMISSIONS[$command] ?? throw new DisbursementViolation('DISBURSEMENT_COMMAND_INVALID');
        $this->staff->check($userId, $permission);
        $disbursement = $this->disbursement($disbursementId);
        $before = $this->state($disbursement);
        $others = in_array($command, ['authorize', 'approve'], true) && $before->makerUserId !== null ? $this->makerAuthority($disbursement, $before) : [];
        $declared = $independenceDeclared && in_array($command, ['authorize', 'approve'], true);
        $input = [...$this->input($expectedRevision, $reason), ...($declared ? ['independence' => StaffIndependence::statementSha256()] : [])];

        return $this->serialized(function () use ($userId, $disbursement, $command, $permission, $others, $before, $input, $expectedRevision, $reason, $requestId, $stepUpProof, $declared): array {
            $this->funding->lockBusiness($disbursement->business_id);

            return $this->withStaff($userId, $permission, $others, function (array $current) use ($userId, $disbursement, $command, $before, $input, $expectedRevision, $reason, $requestId, $stepUpProof, $declared): array {
                $makerCurrent = $before->makerUserId === null || ($current[$before->makerUserId] ?? false);

                return $this->journal->execute('staff:'.$userId, $userId, 'disbursement.'.$command, $requestId, 'disbursement', $disbursement->id, $input,
                    function (): void {},
                    fn (string $operationId): OperationResult => $this->perform($command, $userId, $disbursement, $before, $makerCurrent,
                        $expectedRevision, $reason, $requestId, $operationId, $stepUpProof, $declared));
            });
        });
    }

    public function stepUp(int $userId, string $disbursementId, int $expectedRevision, string $intentDigest, string $code): array
    {
        $this->staff->check($userId, 'disbursements.approve');
        $disbursement = $this->disbursement($disbursementId);
        $before = $this->state($disbursement);
        $others = $before->makerUserId === null ? [] : $this->makerAuthority($disbursement, $before);

        return $this->serialized(function () use ($userId, $disbursement, $before, $others, $expectedRevision, $intentDigest, $code): array {
            $this->funding->lockBusiness($disbursement->business_id);

            return $this->withStaff($userId, 'disbursements.approve', $others, function (array $current) use ($userId, $disbursement, $before, $expectedRevision, $intentDigest, $code): array {
                $this->lock($disbursement);
                $state = $this->state($disbursement);
                if ($state->makerUserId !== $before->makerUserId) {
                    throw new CommandRejection('VERSION_CONFLICT', revision: $state->revision);
                }
                $state->assertAllows('approve', $userId, $state->makerUserId === null || ($current[$state->makerUserId] ?? false));
                if ($state->revision !== $expectedRevision) {
                    throw new CommandRejection('VERSION_CONFLICT', revision: $state->revision);
                }
                $authorized = $this->authorization($disbursement);
                if (! hash_equals($this->intentDigest($disbursement, $state->revision, (string) $authorized->destination_sha256), $intentDigest)) {
                    throw new CommandRejection('DIGEST_STALE', revision: $state->revision);
                }
                $binding = $this->authenticator->handle($userId, $code);
                $this->markCode($userId, $binding, $code);
                $proof = Str::random(64);
                $expires = now('UTC')->addMinutes(5)->startOfSecond();
                (new DisbursementStepUpProof)->forceFill(['purpose' => 'disbursement.approve', 'actor_user_id' => $userId, 'disbursement_id' => $disbursement->id,
                    'revision' => $state->revision, 'amount' => $disbursement->amount, 'destination_sha256' => $authorized->destination_sha256,
                    'intent_digest' => $intentDigest, 'credential_binding' => $binding, 'proof_sha256' => hash('sha256', $proof),
                    'expires_at' => $expires, 'created_at' => now('UTC')])->save();

                return ['proof' => $proof, 'expires_at' => $expires->toIso8601String()];
            });
        });
    }

    public function recordedRequery(int $userId, string $disbursementId, string $requestId): ?array
    {
        $this->staff->check($userId, 'disbursements.requery');
        try {
            return $this->journal->find('staff:'.$userId, 'disbursement.requery', $requestId, function (string $type, string $id) use ($disbursementId): void {
                if ($type !== 'disbursement' || $id !== $disbursementId) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
            });
        } catch (CommandRejection $rejection) {
            return $rejection->reason === 'OPERATION_NOT_FOUND' ? null : throw $rejection;
        }
    }

    public function requeryInstruction(int $userId, string $disbursementId): PayoutInstruction
    {
        $this->staff->check($userId, 'disbursements.requery');
        $disbursement = $this->disbursement($disbursementId);
        $state = $this->state($disbursement);
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();
        if (! $state->allows('requery', $userId) || $intent === null) {
            throw new CommandRejection('DISBURSEMENT_STATE_INVALID', revision: $state->revision);
        }

        return $this->instruction($intent);
    }

    public function recordRequery(int $userId, string $disbursementId, int $expectedRevision, string $reason, string $requestId, ?VerifiedPayoutEvent $observed): array
    {
        $this->staff->check($userId, 'disbursements.requery');
        $disbursement = $this->disbursement($disbursementId);
        $input = $this->input($expectedRevision, $reason);

        return $this->serialized(function () use ($userId, $disbursement, $input, $expectedRevision, $reason, $requestId, $observed): array {
            $this->funding->lockBusiness($disbursement->business_id);

            return $this->staff->handle($userId, 'disbursements.requery', fn (): array => $this->journal->execute('staff:'.$userId, $userId, 'disbursement.requery',
                $requestId, 'disbursement', $disbursement->id, $input, function (): void {},
                function (string $operationId) use ($userId, $disbursement, $expectedRevision, $reason, $requestId, $observed): OperationResult {
                    $this->lock($disbursement);
                    $state = $this->state($disbursement);
                    $state->assertAllows('requery', $userId);
                    $this->assertRevision($state, $expectedRevision);
                    $recordedReason = $this->reason($reason, $state);
                    // A dispatched state has no closing yet: closing moves it on, so requery refuses first.
                    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
                    $call = new DisbursementProviderCall;
                    $call->forceFill(['intent_id' => $intent->id, 'kind' => 'query', 'source' => 'requery', 'operation_id' => $operationId, 'created_at' => now('UTC')])->save();
                    $disposition = $observed === null ? null : $this->recordObservation($intent, $observed, 'requery');
                    $recordedAt = now('UTC')->toIso8601String();

                    return new OperationResult('PROVIDER_QUERY_RECORDED', ['disbursement_id' => $disbursement->id,
                        'receipt' => $this->receipt($call->id, $operationId, $requestId, 'PROVIDER_QUERY_RECORDED', $recordedAt, $disbursement, $state->revision),
                        'observation' => $observed === null ? null : ['state' => $observed->state, 'disposition' => $disposition], 'reason' => $recordedReason], $state->revision);
                }));
        });
    }

    public function find(int $userId, string $command, string $requestId): array
    {
        $name = str_starts_with($command, 'disbursement.') ? substr($command, 13) : '';
        $permission = self::PERMISSIONS[$name] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        $this->staff->check($userId, $permission);

        // The journal only finds this staff member's own disbursement commands, which are never removed.
        return $this->journal->find('staff:'.$userId, $command, $requestId, function (): void {});
    }

    public function transactionOpen(): bool
    {
        return app('db.transactions')->callbackApplicableTransactions()->isNotEmpty();
    }

    public function openFunded(int $limit): array
    {
        $opened = 0;
        $known = 0;
        foreach ($this->funding->funded(null, $limit) as $reference) {
            $new = $this->serialized(function () use ($reference): int {
                $this->funding->lockBusiness($reference->businessId);
                $campaign = $this->funding->lockFunded($reference->campaignId);
                if (Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->exists()) {
                    return 0;
                }
                $payload = ['campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId, 'business_name' => $campaign->businessName,
                    'title' => $campaign->title, 'exposure_reservation_id' => $campaign->exposureReservationId, 'principal' => $campaign->principal,
                    'funded_at' => $campaign->fundedAt, 'term_months' => $campaign->termMonths, 'commitments_digest' => $campaign->commitmentsDigest(),
                    'commitment_ids' => array_map(fn ($commitment): string => $commitment->id, $campaign->commitments)];
                (new Disbursement)->forceFill(['business_campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId,
                    'exposure_reservation_id' => $campaign->exposureReservationId, 'amount' => $campaign->principal, 'currency' => 'RWF',
                    'commitments_digest' => $campaign->commitmentsDigest(), 'commitment_count' => count($campaign->commitments),
                    'term_months' => $campaign->termMonths, 'funded_at' => $campaign->fundedAt, 'environment' => $this->environment(),
                    'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => now('UTC')])->save();

                return 1;
            });
            $opened += $new;
            $known += 1 - $new;
        }

        return ['opened' => $opened, 'known' => $known];
    }

    public function claim(?string $intentId, int $limit, bool $idempotentSends): array
    {
        $settled = DisbursementDispatch::query()->whereIn('phase', ['claimed', 'recheck_failed'])->select('intent_id');
        $queued = DisbursementDispatch::query()->where('phase', 'queued')->whereNotIn('intent_id', $settled)
            ->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))->orderBy('id')->limit($limit)->pluck('intent_id')->all();
        $claims = [];
        foreach ($queued as $queuedIntent) {
            $instruction = $this->claimOne((string) $queuedIntent, false);
            if ($instruction !== null) {
                $claims[] = ['instruction' => $instruction, 'source' => 'dispatch'];
            }
        }
        $outcomes = DisbursementDispatch::query()->whereIn('phase', ['sent', 'unsent'])->select('intent_id');
        $interrupted = DisbursementDispatch::query()->where('phase', 'claimed')->whereNotIn('intent_id', $outcomes)
            ->where('created_at', '<', now('UTC')->subMinutes(5))->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))
            ->orderBy('id')->limit($limit)->pluck('intent_id')->all();
        foreach ($interrupted as $claimedIntent) {
            $recoverable = $idempotentSends && DisbursementIntent::query()->whereKey($claimedIntent)->where('idempotent_sends', true)->exists();
            $instruction = $recoverable ? $this->claimOne((string) $claimedIntent, true) : null;
            if ($instruction === null) {
                $this->recordDispatch((string) $claimedIntent, false);

                continue;
            }
            $claims[] = ['instruction' => $instruction, 'source' => 'recovery'];
        }

        return $claims;
    }

    public function recordDispatch(string $intentId, bool $sent): void
    {
        $intent = DisbursementIntent::query()->findOrFail($intentId);
        $disbursement = Disbursement::query()->findOrFail($intent->disbursement_id);
        $this->serialized(function () use ($intent, $disbursement, $sent): void {
            $this->lock($disbursement);
            DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();
            if (DisbursementDispatch::query()->where('intent_id', $intent->id)->whereIn('phase', ['sent', 'unsent', 'recheck_failed'])->exists()) {
                return;
            }
            (new DisbursementDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => $sent ? 'sent' : 'unsent', 'created_at' => now('UTC')])->save();
            $state = $this->state($disbursement);
            if ($state->state === 'queued') {
                $this->appendEvent($disbursement, $state->revision + 1, 'dispatched', null, null, null, ['sent' => $sent, 'intent_id' => $intent->id]);
            }
        });
    }

    public function queryDue(?string $intentId, int $limit): array
    {
        $outcomes = DisbursementDispatch::query()->whereIn('phase', ['sent', 'unsent'])->select('intent_id');
        $closed = DisbursementClosing::query()->whereNotNull('intent_id')->select('intent_id');
        $due = DisbursementIntent::query()->whereIn('id', $outcomes)->whereNotIn('id', $closed)
            ->when($intentId !== null, fn ($query) => $query->whereKey($intentId))->orderBy('id')->limit($limit)->get();

        return array_values($due->map(function (DisbursementIntent $intent): PayoutInstruction {
            $this->serialized(function () use ($intent): void {
                DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();
                (new DisbursementProviderCall)->forceFill(['intent_id' => $intent->id, 'kind' => 'query', 'source' => 'reconcile', 'created_at' => now('UTC')])->save();
            });

            return $this->instruction($intent);
        })->all());
    }

    public function observe(VerifiedPayoutEvent $event, string $source, ?string $intentId = null): array
    {
        $intent = $intentId !== null ? DisbursementIntent::query()->findOrFail($intentId)
            : DisbursementIntent::query()->where('provider_reference_sha256', hash('sha256', (string) $event->providerReference))->first();
        if ($intent === null) {
            throw new CommandRejection('PROVIDER_EVENT_UNMATCHED', 422);
        }
        $disbursement = Disbursement::query()->findOrFail($intent->disbursement_id);

        return $this->serialized(function () use ($intent, $disbursement, $event, $source): array {
            $this->lock($disbursement);

            return ['intent_id' => $intent->id, 'disposition' => $this->recordObservation($intent, $event, $source)];
        });
    }

    public function reconcile(string $intentId): string
    {
        $intent = DisbursementIntent::query()->findOrFail($intentId);
        $disbursement = Disbursement::query()->findOrFail($intent->disbursement_id);
        try {
            return $this->serialized(function () use ($intent, $disbursement): string {
                $this->funding->lockBusiness($disbursement->business_id);
                $campaign = $this->funding->lockFunded($disbursement->business_campaign_id);
                $this->lock($disbursement);
                DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();

                return $this->settle($disbursement, $intent, $campaign);
            });
        } catch (CommandRejection $rejection) {
            return $rejection->reason === 'FUNDING_SOURCE_UNAVAILABLE' ? 'funding_unavailable' : throw $rejection;
        }
    }

    /** The reconciliation decision for one locked intent, and its terminal effects when it has one. */
    private function settle(Disbursement $disbursement, DisbursementIntent $intent, FundedCampaign $campaign): string
    {
        $observations = DisbursementProviderEvent::query()->where('intent_id', $intent->id)->orderBy('id')->get();
        $decision = Reconciliation::decide(array_values($observations->map(fn (DisbursementProviderEvent $event): array => ['state' => $event->state,
            'disposition' => $event->disposition])->all()));
        $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->first();
        $comparison = ['intent' => $this->intentFacts($intent, false), 'observations' => $observations->count()];
        $causes = $decision->causes;
        if ($decision->terminal() && $closing === null && ! $this->fundingMatches($disbursement, $campaign)) {
            $causes = ['funding_changed'];
        }
        if ($causes !== []) {
            $latest = DisbursementReconciliation::query()->where('intent_id', $intent->id)->where('decision', 'exception')->orderByDesc('id')->first();
            if ($latest === null || $latest->causes !== $causes || $latest->comparison['observations'] !== $observations->count()) {
                (new DisbursementReconciliation)->forceFill(['intent_id' => $intent->id, 'decision' => 'exception', 'causes' => $causes,
                    'comparison' => $comparison, 'created_at' => now('UTC')])->save();
            }

            return $closing === null ? 'exception' : 'closed';
        }
        if ($closing !== null) {
            return 'closed';
        }
        if (! $decision->terminal()) {
            return 'open';
        }
        // An authenticated outcome can arrive while the send is still being recorded; it closes
        // once the dispatch is recorded, on the next reconciliation.
        if ($this->state($disbursement)->state !== 'dispatched') {
            return 'awaiting_dispatch';
        }
        $final = $observations->first(fn (DisbursementProviderEvent $event): bool => $event->disposition === 'applied'
            && PayoutOutcome::isFinal($event->state)) ?? throw new DisbursementViolation('RECONCILIATION_FINAL_MISSING');
        $reconciliation = new DisbursementReconciliation;
        $reconciliation->forceFill(['intent_id' => $intent->id, 'provider_event_id' => $final->id, 'decision' => $decision->decision, 'causes' => [],
            'comparison' => [...$comparison, 'observed' => $this->observedFacts($final)], 'created_at' => now('UTC')])->save();
        $state = $this->state($disbursement);
        if ($decision->decision === 'matched_success') {
            $effectiveAt = $final->effective_at?->toIso8601String() ?? throw new DisbursementViolation('RECONCILIATION_EFFECTIVE_AT_MISSING');
            $schedule = IssueSchedule::dates($effectiveAt, $disbursement->term_months);
            // The issue instant is the closing's one retained instant, so a replayed issue sees the same instant.
            $recordedAt = $this->instant();
            $closingId = $this->recordClosing($disbursement, 'issued', 'reconciled_success', [], $intent->id, $reconciliation, $recordedAt,
                effectiveAt: $final->effective_at, effectiveDate: $schedule->effectiveDate, dueDates: $schedule->dueDates);
            $this->funding->issue($campaign, new IssueInstruction($closingId, $disbursement->id, $intent->operation_id, $effectiveAt,
                $schedule->effectiveDate, $schedule->dueDates, $recordedAt->toIso8601String()));
            $this->appendEvent($disbursement, $state->revision + 1, 'succeeded', null, null, null, ['closing_id' => $closingId]);

            return 'matched_success';
        }
        $closingId = $this->recordClosing($disbursement, 'failed_closing', 'reconciled_failure', [], $intent->id, $reconciliation, $this->instant());
        $this->funding->failClose($campaign, new FailedClosing($closingId, $disbursement->id, 'reconciled_failure', []));
        $this->appendEvent($disbursement, $state->revision + 1, 'failed_closing', null, null, null, ['closing_id' => $closingId]);

        return 'matched_failure';
    }

    /** One command's effect inside its journal operation, after every lock it needs. */
    private function perform(string $command, int $userId, Disbursement $disbursement, DisbursementState $before, bool $makerCurrent,
        int $expectedRevision, string $reason, string $requestId, string $operationId, ?string $stepUpProof, bool $declared = false): OperationResult
    {
        $campaign = in_array($command, ['authorize', 'approve'], true) ? $this->funding->lockFunded($disbursement->business_campaign_id) : null;
        if ($declared) {
            // The actor's own statement, retained with this command and the staff-person revision it
            // was signed under; a refused command rolls it back.
            (new DisbursementIndependenceDeclaration)->forceFill(['disbursement_id' => $disbursement->id, 'staff_user_id' => $userId,
                'staff_person_identity_id' => $this->identities->staffPerson($userId)['resolution_id'] ?? null, 'command' => $command, 'operation_id' => $operationId, 'statement_version' => StaffIndependence::VERSION,
                'statement_sha256' => StaffIndependence::statementSha256(), 'declared_at' => now('UTC')])->save();
        }
        // Connection and destination sources are read before the disbursement lock, never under it.
        $facts = $campaign === null ? null : $this->sourceFacts($disbursement, $campaign, $command === 'approve' && $before->makerUserId !== null
            ? [$before->makerUserId => (string) $this->authorization($disbursement)->operation_id, $userId => $operationId] : [$userId => $operationId]);
        $this->lock($disbursement);
        $state = $this->state($disbursement);
        if ($state->makerUserId !== $before->makerUserId) {
            throw new CommandRejection('VERSION_CONFLICT', revision: $state->revision);
        }
        $state->assertAllows($command, $userId, $makerCurrent);
        $this->assertRevision($state, $expectedRevision);
        $recordedReason = $this->reason($reason, $state);
        if ($campaign !== null && $facts !== null && $command === 'approve') {
            return $this->approve($userId, $disbursement, $campaign, $facts, $state, $recordedReason, $requestId, $operationId, $stepUpProof);
        }
        [$kind, $code] = self::STAFF_EVENTS[$command];
        $binding = null;
        $destination = null;
        $payload = ['reason' => $recordedReason];
        if ($campaign !== null && $facts !== null) {
            $this->assertConnections($facts['connections'], $state);
            $this->assertFunding($disbursement, $campaign, $state);
            $verified = $this->currentDestination($facts['destination'], $state);
            $recheck = $this->funding->recheck($campaign);
            if ($recheck->outcome === 'unavailable') {
                throw new CommandRejection('POLICY_INPUT_REQUIRED', revision: $state->revision, data: ['causes' => $recheck->causes]);
            }
            if ($recheck->outcome === 'failed') {
                throw new CommandRejection('DISBURSEMENT_PRECHECK_FAILED', revision: $state->revision, data: ['causes' => $recheck->causes, 'policy_version' => $recheck->policyVersion]);
            }
            $destination = $verified->digest();
            $binding = $this->binding($disbursement, $destination);
            $payload += ['precheck' => ['state' => 'passed', 'checked_at' => now('UTC')->toIso8601String(), 'policy_version' => $recheck->policyVersion, 'causes' => []],
                'destination' => ['id' => $verified->id, 'revision' => $verified->revision, 'masked' => $verified->masked]];
        }
        if ($kind === 'held' || $kind === 'hold_released') {
            $payload['from'] = $state->state;
        }
        $event = $this->appendEvent($disbursement, $state->revision + 1, $kind, $userId, $operationId, $requestId, $payload, $binding, $destination);

        return new OperationResult($code, ['disbursement_id' => $disbursement->id,
            'receipt' => $this->receipt($event->id, $operationId, $requestId, $code, $event->created_at->toIso8601String(), $disbursement, $state->revision + 1)], $state->revision + 1);
    }

    /** @param array{connections: array<int, string>, destination: VerifiedDestination|null} $facts */
    private function approve(int $userId, Disbursement $disbursement, FundedCampaign $campaign, array $facts, DisbursementState $state, string $reason,
        string $requestId, string $operationId, ?string $stepUpProof): OperationResult
    {
        if ($stepUpProof === null || $stepUpProof === '') {
            throw new CommandRejection('STEP_UP_REQUIRED', 403, $state->revision);
        }
        $this->assertConnections($facts['connections'], $state);
        $this->assertFunding($disbursement, $campaign, $state);
        $authorized = $this->authorization($disbursement);
        $destination = $this->currentDestination($facts['destination'], $state);
        if (! hash_equals((string) $authorized->destination_sha256, $destination->digest())) {
            throw new CommandRejection('DIGEST_STALE', revision: $state->revision);
        }
        $digest = $this->intentDigest($disbursement, $state->revision, (string) $authorized->destination_sha256);
        $this->consumeProof($userId, $disbursement, $state->revision, $digest, (string) $authorized->destination_sha256, $stepUpProof, $operationId);
        $recheck = $this->funding->recheck($campaign);
        if ($recheck->outcome === 'unavailable') {
            throw new CommandRejection('POLICY_INPUT_REQUIRED', revision: $state->revision, data: ['causes' => $recheck->causes]);
        }
        $recorded = $this->instant();
        $recordedAt = $recorded->toIso8601String();
        if ($recheck->outcome === 'failed') {
            $closingId = $this->recordClosing($disbursement, 'failed_closing', 'approve_recheck', $recheck->causes, null, null, $recorded,
                ['operation_id' => $operationId, 'actor_user_id' => $userId, 'request_id' => strtolower($requestId)]);
            $this->funding->failClose($campaign, new FailedClosing($closingId, $disbursement->id, 'approve_recheck', $recheck->causes));
            $this->appendEvent($disbursement, $state->revision + 1, 'failed_closing', $userId, $operationId, $requestId, ['reason' => $reason,
                'closing_id' => $closingId, 'causes' => $recheck->causes, 'policy_version' => $recheck->policyVersion]);

            return new OperationResult('CAMPAIGN_FAILED_CLOSING', ['disbursement_id' => $disbursement->id, 'causes' => $recheck->causes,
                'receipt' => $this->receipt($closingId, $operationId, $requestId, 'CAMPAIGN_FAILED_CLOSING', $recordedAt, $disbursement, $state->revision + 1)], $state->revision + 1);
        }
        $intent = new DisbursementIntent;
        $intent->id = strtolower((string) Str::ulid());
        $reference = 'rzd_'.$intent->id;
        $payload = ['intent_id' => $intent->id, 'disbursement_id' => $disbursement->id, 'operation_id' => $operationId, 'request_id' => $requestId,
            'revision' => $state->revision, 'amount' => $disbursement->amount, 'destination' => $authorized->payload['destination'],
            'intent_digest' => $digest, 'provider' => $this->provider->name(), 'environment' => $disbursement->environment,
            'maker_user_id' => $state->makerUserId, 'checker_user_id' => $userId, 'recorded_at' => $recordedAt];
        $intent->forceFill(['disbursement_id' => $disbursement->id, 'operation_id' => $operationId, 'request_id' => $requestId, 'revision' => $state->revision,
            'amount' => $disbursement->amount, 'currency' => 'RWF', 'destination_id' => $destination->id, 'destination_revision' => $destination->revision,
            'destination_sha256' => $authorized->destination_sha256, 'commitments_digest' => $disbursement->commitments_digest,
            'binding_sha256' => $authorized->binding_sha256, 'intent_digest' => $digest, 'provider' => $this->provider->name(),
            'provider_reference' => $reference, 'provider_reference_sha256' => hash('sha256', $reference), 'environment' => $disbursement->environment,
            'idempotent_sends' => $this->provider->idempotentSends(), 'maker_user_id' => $state->makerUserId, 'checker_user_id' => $userId,
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => now('UTC')])->save();
        (new DisbursementDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'queued', 'created_at' => now('UTC')])->save();
        $event = $this->appendEvent($disbursement, $state->revision + 1, 'intent_recorded', $userId, $operationId, $requestId,
            ['reason' => $reason, 'intent_id' => $intent->id, 'recheck' => ['state' => 'passed', 'policy_version' => $recheck->policyVersion]]);
        $intentId = $intent->id;
        DB::afterCommit(function () use ($intentId): void {
            try {
                app(DispatchDisbursements::class)->handle($intentId);
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        return new OperationResult('DISBURSEMENT_INTENT_RECORDED', ['disbursement_id' => $disbursement->id, 'intent_id' => $intent->id,
            'receipt' => $this->receipt($event->id, $operationId, $requestId, 'DISBURSEMENT_INTENT_RECORDED', $recordedAt, $disbursement, $state->revision + 1)], $state->revision + 1);
    }

    /** A claim (or an idempotent recovery resend) after a fresh recheck under the full lock chain. */
    private function claimOne(string $intentId, bool $recovery): ?PayoutInstruction
    {
        $intent = DisbursementIntent::query()->findOrFail($intentId);
        $disbursement = Disbursement::query()->findOrFail($intent->disbursement_id);
        try {
            return $this->serialized(function () use ($intent, $disbursement, $recovery): ?PayoutInstruction {
                $this->funding->lockBusiness($disbursement->business_id);

                $authorizedAt = $this->authorization($disbursement)->created_at->format('Y-m-d\TH:i:s.uP');

                return $this->withStaff(null, null, [$intent->maker_user_id => ['disbursements.authorize', $authorizedAt],
                    $intent->checker_user_id => ['disbursements.approve', $intent->created_at->format('Y-m-d\TH:i:s.uP')]],
                    function (array $current) use ($intent, $disbursement, $recovery): ?PayoutInstruction {
                        $campaign = $this->funding->lockFunded($disbursement->business_campaign_id);
                        $facts = $this->sourceFacts($disbursement, $campaign, [$intent->maker_user_id => (string) $this->authorization($disbursement)->operation_id,
                            $intent->checker_user_id => $intent->operation_id]);
                        $this->lock($disbursement);
                        DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();
                        $state = $this->state($disbursement);
                        $phases = DisbursementDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all();
                        $eligible = $recovery ? $state->state === 'queued' && in_array('claimed', $phases, true) && array_intersect(['sent', 'unsent'], $phases) === []
                                && ! DisbursementProviderEvent::query()->where('intent_id', $intent->id)->exists()
                            : $state->state === 'queued' && array_intersect(['claimed', 'recheck_failed'], $phases) === [];
                        if (! $eligible || ! $current[$intent->maker_user_id] || ! $current[$intent->checker_user_id]
                            || ! $this->presendFactsCurrent($disbursement, $intent, $campaign, $facts)) {
                            return null;
                        }
                        $recheck = $this->funding->recheck($campaign);
                        if ($recheck->outcome === 'unavailable' || ($recovery && $recheck->outcome === 'failed')) {
                            return null;
                        }
                        if ($recheck->outcome === 'failed') {
                            (new DisbursementDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'recheck_failed', 'created_at' => now('UTC')])->save();
                            $closingId = $this->recordClosing($disbursement, 'failed_closing', 'worker_recheck', $recheck->causes, $intent->id, null,
                                $this->instant());
                            $this->funding->failClose($campaign, new FailedClosing($closingId, $disbursement->id, 'worker_recheck', $recheck->causes));
                            $this->appendEvent($disbursement, $state->revision + 1, 'failed_closing', null, null, null,
                                ['closing_id' => $closingId, 'causes' => $recheck->causes, 'policy_version' => $recheck->policyVersion]);

                            return null;
                        }
                        if (! $recovery) {
                            (new DisbursementDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'claimed', 'created_at' => now('UTC')])->save();
                        }
                        (new DisbursementProviderCall)->forceFill(['intent_id' => $intent->id, 'kind' => 'send', 'source' => $recovery ? 'recovery' : 'dispatch',
                            'created_at' => now('UTC')])->save();

                        return $this->instruction($intent);
                    });
            });
        } catch (CommandRejection $rejection) {
            return $rejection->reason === 'FUNDING_SOURCE_UNAVAILABLE' ? null : throw $rejection;
        }
    }

    /**
     * The worker's own pre-send facts: current connections, the same funding and the same verified destination.
     *
     * @param  array{connections: array<int, string>, destination: VerifiedDestination|null}  $facts
     */
    private function presendFactsCurrent(Disbursement $disbursement, DisbursementIntent $intent, FundedCampaign $campaign, array $facts): bool
    {
        $destination = $facts['destination'];

        return array_diff(array_values($facts['connections']), ['unconnected']) === [] && $this->fundingMatches($disbursement, $campaign)
            && $destination !== null && hash_equals($intent->destination_sha256, $destination->digest());
    }

    /**
     * The staff connection and payout destination facts for one locked campaign, read after the
     * Business and campaign locks and before the disbursement lock (#96 5874488658), so no source
     * that may lock Business, User or Party rows is ever called under a disbursement or intent lock.
     *
     * Each staff member is checked against the declaration of their own operation that the
     * disbursement relies on: the maker's authorize and the checker's approve.
     *
     * @param  array<int, string>  $operations  staff user id => their authorize or approve operation id
     * @return array{connections: array<int, string>, destination: VerifiedDestination|null}
     */
    private function sourceFacts(Disbursement $disbursement, FundedCampaign $campaign, array $operations): array
    {
        $connections = [];
        foreach ($operations as $staffUserId => $operationId) {
            $connections[$staffUserId] = $this->connections->connection($staffUserId, $disbursement->id, $operationId, $disbursement->business_id, $campaign->partyIds());
        }

        return ['connections' => $connections, 'destination' => $this->verifiedDestination($disbursement)];
    }

    /** Records an authenticated observation against its locked intent; an exact replay adds nothing. */
    /**
     * Records an authenticated observation against its locked intent; an exact replay adds nothing.
     * Every observation path (callback, scheduled query and staff requery) comes through here.
     */
    private function recordObservation(DisbursementIntent $intent, VerifiedPayoutEvent $event, string $source): string
    {
        DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();
        $this->lockProviderEventIdentity($event->provider, $event->eventId);

        return $this->classifyAndRecord($intent, $event, $source);
    }

    /**
     * Serializes one provider event identity across intents (#96 5876182472, 5876362074). The
     * identity is global while the intent lock is not, so two observations of the same
     * `(provider, provider_event_id)` for different intents would otherwise both read it absent.
     *
     * Lock order: this transaction-scoped advisory lock is the LEAF lock of every observation
     * path. It is taken after the disbursement and intent rows (and, for requery, after the
     * Business and staff locks), and nothing is locked after it: the holder only reads events and
     * inserts one. A waiter therefore never holds anything the holder needs, so it adds no cycle.
     */
    private function lockProviderEventIdentity(string $provider, string $eventId): void
    {
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [self::providerEventLockKey($provider, $eventId)]);
    }

    public static function providerEventLockKey(string $provider, string $eventId): string
    {
        return 'disbursement-provider-event:'.hash('sha256', $provider."\0".$eventId);
    }

    private function classifyAndRecord(DisbursementIntent $intent, VerifiedPayoutEvent $event, string $source): string
    {
        // An exact replay of anything already recorded FOR THIS INTENT, a conflict included, adds
        // nothing. The same content recorded against another intent never suppresses this one.
        if (DisbursementProviderEvent::query()->where('intent_id', $intent->id)->where('provider', $event->provider)
            ->where('provider_event_id', $event->eventId)->where('content_sha256', $event->contentSha256)->exists()) {
            return 'duplicate';
        }
        // The observation that claims this identity: the first that matched its own intent.
        $recorded = DisbursementProviderEvent::query()->where('provider', $event->provider)->where('provider_event_id', $event->eventId)
            ->whereNotIn('disposition', ['key_conflict', 'unverifiable'])->first();
        $mismatches = Reconciliation::mismatches($this->intentFacts($intent, true), $this->eventFacts($event));
        $outcome = PayoutOutcome::observe($this->providerState($intent), $event->state, $recorded?->content_sha256, $event->contentSha256, $mismatches);
        $record = (new DisbursementProviderEvent)->forceFill(['intent_id' => $intent->id, 'provider' => $event->provider, 'provider_event_id' => $event->eventId,
            'content_sha256' => $event->contentSha256, 'source' => $source, 'state' => $event->state, 'amount' => $this->digits($event->amount),
            'currency' => $event->currency !== null && preg_match('/^[A-Z]{3}$/D', $event->currency) === 1 ? $event->currency : null,
            'environment' => $event->environment === null ? null : substr($event->environment, 0, 20),
            'observed_operation_id' => $event->operationId === null ? null : substr($event->operationId, 0, 64),
            'provider_reference_sha256' => $event->providerReference === null ? null : hash('sha256', $event->providerReference),
            'destination_sha256' => $event->destinationSha256 !== null && preg_match('/^[0-9a-f]{64}$/D', $event->destinationSha256) === 1 ? $event->destinationSha256 : null,
            'observed_at' => $event->observedAt, 'effective_at' => $event->effectiveAt, 'disposition' => $outcome->disposition,
            'mismatches' => $outcome->disposition === 'unverifiable' ? $mismatches : [], 'evidence' => $event->evidence, 'created_at' => now('UTC')]);
        $record->save();

        return $outcome->disposition;
    }

    /** The provider outcome currently applied to an intent: the last applied observation, or what the dispatch left. */
    private function providerState(DisbursementIntent $intent): string
    {
        $applied = DisbursementProviderEvent::query()->where('intent_id', $intent->id)->where('disposition', 'applied')->orderByDesc('id')->value('state');
        if (is_string($applied)) {
            return $applied;
        }

        return DisbursementDispatch::query()->where('intent_id', $intent->id)->where('phase', 'unsent')->exists() ? 'unknown' : 'pending';
    }

    private function consumeProof(int $userId, Disbursement $disbursement, int $revision, string $digest, string $destination, string $proof, string $operationId): void
    {
        $record = DisbursementStepUpProof::query()->where('proof_sha256', hash('sha256', $proof))->lockForUpdate()->first();
        if ($record === null || $record->purpose !== 'disbursement.approve' || $record->consumed_at !== null || $record->actor_user_id !== $userId
            || $record->disbursement_id !== $disbursement->id || $record->revision !== $revision || $record->amount !== $disbursement->amount
            || ! hash_equals($record->destination_sha256, $destination) || ! hash_equals($record->intent_digest, $digest)
            || ! hash_equals($record->credential_binding, $this->authenticator->binding($userId))) {
            throw new CommandRejection('STEP_UP_INVALID', 403, $revision);
        }
        if ($record->expires_at->lessThanOrEqualTo(now('UTC'))) {
            throw new CommandRejection('STEP_UP_EXPIRED', 403, $revision);
        }
        $record->forceFill(['consumed_at' => now('UTC'), 'consumed_operation_id' => $operationId])->save();
    }

    /**
     * One accepted authenticator code mints one proof (#96 answer 11). The marker is an HMAC keyed
     * by a server secret over the credential binding, the code and a five-minute bucket; the current
     * and previous buckets are checked, which covers the code's whole accepted window. Neither the
     * code nor an unkeyed digest of it is stored.
     */
    private function markCode(int $userId, string $binding, string $code): void
    {
        $key = hash_hmac('sha256', 'rozine-disbursement-step-up-marker-v1', $this->config->string('app.key'));
        $bucket = intdiv(now('UTC')->getTimestamp(), 300);
        $marker = fn (int $at): string => hash_hmac('sha256', $userId.'|'.$binding.'|'.$code.'|'.$at, $key);
        if (DisbursementStepUpMarker::query()->whereIn('marker', [$marker($bucket), $marker($bucket - 1)])->exists()) {
            throw new CommandRejection('STEP_UP_CODE_INVALID', 422, fieldErrors: ['code' => ['Enter a new authenticator code.']]);
        }
        (new DisbursementStepUpMarker)->forceFill(['actor_user_id' => $userId, 'marker' => $marker($bucket), 'created_at' => now('UTC')])->save();
    }

    /**
     * Locks the actor (checking its permission) and the other recorded staff in ascending user id,
     * then runs the rest with whether each other staff member still holds their permission.
     *
     * @template TResult
     *
     * @param  array<int, array{0: string, 1: string}>  $others  user id => [permission, the instant it must have been held since]
     * @param  Closure(array<int, bool>): TResult  $then
     * @return TResult
     */
    private function withStaff(?int $actorId, ?string $permission, array $others, Closure $then): mixed
    {
        $ids = array_keys($others);
        if ($actorId !== null) {
            $ids[] = $actorId;
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        $current = [];
        $run = function (int $index) use (&$run, &$current, $ids, $actorId, $permission, $others, $then): mixed {
            if ($index === count($ids)) {
                return $then($current);
            }
            $id = $ids[$index];
            if ($id === $actorId && $permission !== null) {
                return $this->staff->handle($id, $permission, function () use (&$run, &$current, $id, $index, $others): mixed {
                    if (isset($others[$id])) {
                        $current[$id] = $this->staff->continuouslyHeldSince($id, $others[$id][0], $others[$id][1]);
                    }

                    return $run($index + 1);
                });
            }
            $current[$id] = $this->staff->continuouslyHeldSince($id, $others[$id][0], $others[$id][1]);

            return $run($index + 1);
        };

        return $run(0);
    }

    /**
     * The recorded maker, and the instant their authorization was recorded: it counts only while
     * they have held authorize authority without a gap since then (#96 answer 4).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function makerAuthority(Disbursement $disbursement, DisbursementState $state): array
    {
        return [(int) $state->makerUserId => ['disbursements.authorize', $this->authorization($disbursement)->created_at->format('Y-m-d\TH:i:s.uP')]];
    }

    /** @param array<int, string> $connections staff user id => connection */
    private function assertConnections(array $connections, DisbursementState $state): void
    {
        foreach ($connections as $connection) {
            if ($connection === 'connected') {
                throw new CommandRejection('ACTION_FORBIDDEN', 403, $state->revision, data: ['causes' => ['connected_staff']]);
            }
            if ($connection !== 'unconnected') {
                throw new CommandRejection('POLICY_INPUT_REQUIRED', revision: $state->revision, data: ['causes' => ['staff_connection_source']]);
            }
        }
    }

    private function assertFunding(Disbursement $disbursement, FundedCampaign $campaign, DisbursementState $state): void
    {
        if (! $this->fundingMatches($disbursement, $campaign)) {
            throw new CommandRejection('DIGEST_STALE', revision: $state->revision);
        }
    }

    private function fundingMatches(Disbursement $disbursement, FundedCampaign $campaign): bool
    {
        return $campaign->campaignId === $disbursement->business_campaign_id && $campaign->businessId === $disbursement->business_id
            && $campaign->exposureReservationId === $disbursement->exposure_reservation_id && $campaign->principal === $disbursement->amount
            && $campaign->termMonths === $disbursement->term_months && hash_equals($disbursement->commitments_digest, $campaign->commitmentsDigest());
    }

    private function currentDestination(?VerifiedDestination $destination, DisbursementState $state): VerifiedDestination
    {
        return $destination
            ?? throw new CommandRejection('POLICY_INPUT_REQUIRED', revision: $state->revision, data: ['causes' => ['verified_payout_destination']]);
    }

    private function verifiedDestination(Disbursement $disbursement): ?VerifiedDestination
    {
        $destination = $this->destinations->verified($disbursement->business_id, $disbursement->environment);

        return $destination !== null && $destination->businessId === $disbursement->business_id && $destination->environment === $disbursement->environment
            && $destination->expiresAt > now('UTC')->toIso8601String() ? $destination : null;
    }

    private function binding(Disbursement $disbursement, string $destinationSha256): string
    {
        return hash('sha256', $this->json->encode(['campaign_id' => $disbursement->business_campaign_id, 'exposure_reservation_id' => $disbursement->exposure_reservation_id,
            'amount' => $disbursement->amount, 'currency' => 'RWF', 'destination_sha256' => $destinationSha256, 'commitments_digest' => $disbursement->commitments_digest]));
    }

    private function intentDigest(Disbursement $disbursement, int $revision, string $destinationSha256): string
    {
        return IntentDigest::intent(['disbursement_id' => $disbursement->id, 'revision' => $revision, 'campaign_id' => $disbursement->business_campaign_id,
            'exposure_reservation_id' => $disbursement->exposure_reservation_id, 'amount' => $disbursement->amount, 'currency' => 'RWF',
            'destination_sha256' => $destinationSha256, 'commitments_digest' => $disbursement->commitments_digest, 'provider' => $this->provider->name(),
            'environment' => $disbursement->environment]);
    }

    /**
     * Records the single terminal closing. One instant is its payload `recorded_at`, its native
     * `created_at` and, for an issue, the instruction's `issuedAt`. An approve-time closing retains
     * its command authority (operation, actor and request), which the deferred
     * `disbursement_closings_authority` trigger binds to the recorded approve at the outer commit; a
     * reconciled closing retains the provider observation its reconciliation selected.
     *
     * @param  list<string>  $causes
     * @param  array{operation_id: string, actor_user_id: int, request_id: string}|null  $authority
     * @param  list<string>|null  $dueDates
     */
    private function recordClosing(Disbursement $disbursement, string $kind, string $cause, array $causes, ?string $intentId,
        ?DisbursementReconciliation $reconciliation, CarbonImmutable $recordedAt, ?array $authority = null, ?CarbonImmutable $effectiveAt = null,
        ?string $effectiveDate = null, ?array $dueDates = null): string
    {
        $closing = new DisbursementClosing;
        $closing->id = strtolower((string) Str::ulid());
        // Every column has its twin in the digested payload, the effective instant included (#96 5925426310).
        $payload = ['closing_id' => $closing->id, 'disbursement_id' => $disbursement->id, 'kind' => $kind, 'cause' => $cause, 'causes' => $causes,
            'intent_id' => $intentId, 'reconciliation_id' => $reconciliation?->id, 'provider_event_id' => $reconciliation?->provider_event_id,
            'operation_id' => $authority['operation_id'] ?? null, 'command' => $authority === null ? null : 'disbursement.approve',
            'actor_user_id' => $authority['actor_user_id'] ?? null, 'request_id' => $authority['request_id'] ?? null,
            'effective_at' => $effectiveAt?->toIso8601String(), 'effective_date' => $effectiveDate, 'due_dates' => $dueDates,
            'refund' => $kind === 'failed_closing' ? ['commitments' => $disbursement->commitment_count, 'amount' => $disbursement->amount, 'fee' => '0'] : null,
            'recorded_at' => $recordedAt->toIso8601String()];
        $closing->forceFill(['disbursement_id' => $disbursement->id, 'intent_id' => $intentId, 'reconciliation_id' => $reconciliation?->id, 'kind' => $kind,
            'cause' => $cause, 'causes' => $causes, 'operation_id' => $payload['operation_id'], 'actor_user_id' => $payload['actor_user_id'],
            'request_id' => $payload['request_id'], 'effective_at' => $effectiveAt, 'effective_date' => $effectiveDate, 'due_dates' => $dueDates,
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();

        return $closing->id;
    }

    /** A whole-second UTC instant, exact in a `timestampTz(0)` column and in its ISO-8601 form. */
    private function instant(): CarbonImmutable
    {
        return now('UTC')->startOfSecond()->toImmutable();
    }

    /** @param array<string, mixed> $payload */
    private function appendEvent(Disbursement $disbursement, int $revision, string $kind, ?int $actorUserId, ?string $operationId, ?string $requestId,
        array $payload, ?string $binding = null, ?string $destination = null): DisbursementEvent
    {
        $event = new DisbursementEvent;
        $event->id = strtolower((string) Str::ulid());
        $recorded = now('UTC');
        $payload = [...$payload, 'event_id' => $event->id, 'disbursement_id' => $disbursement->id, 'revision' => $revision, 'kind' => $kind,
            'actor_user_id' => $actorUserId, 'operation_id' => $operationId, 'request_id' => $requestId, 'recorded_at' => $recorded->toIso8601String()];
        $event->forceFill(['disbursement_id' => $disbursement->id, 'revision' => $revision, 'kind' => $kind, 'actor_user_id' => $actorUserId,
            'operation_id' => $operationId, 'request_id' => $requestId, 'binding_sha256' => $binding, 'destination_sha256' => $destination,
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recorded])->save();

        return $event;
    }

    /** @return array<string, mixed> */
    private function receipt(string $receiptId, string $operationId, string $requestId, string $code, string $recordedAt, Disbursement $disbursement, int $revision): array
    {
        return ['receipt_id' => $receiptId, 'operation_id' => $operationId, 'request_id' => $requestId, 'code' => $code, 'recorded_at' => $recordedAt,
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount], 'units' => null, 'reference' => $this->reference($disbursement),
            'revision' => $revision, 'policy_version' => self::POLICY_VERSION, 'disclosure_version' => null];
    }

    /** @return array<string, mixed> */
    private function row(Disbursement $disbursement): array
    {
        $state = $this->state($disbursement);
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();

        return ['id' => $disbursement->id, 'reference' => $this->reference($disbursement), 'business' => (string) $disbursement->payload['business_name'],
            'note_title' => (string) $disbursement->payload['title'], 'destination' => $this->maskedDestination($disbursement),
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount], 'state' => $state->state,
            'provider_state' => $intent !== null && $this->dispatched($intent) ? $this->providerState($intent) : null];
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function detail(Disbursement $disbursement, int $userId, array $permissions): array
    {
        $events = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->orderBy('revision')->get();
        $state = DisbursementState::fold(array_values($events->map(fn (DisbursementEvent $event): array => ['kind' => $event->kind, 'actor_user_id' => $event->actor_user_id])->all()));
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();
        $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->first();
        $names = DB::table('users')->whereIn('id', $events->pluck('actor_user_id')->filter()->unique()->values()->all())->pluck('name', 'id');
        $attribution = fn (?DisbursementEvent $event): ?array => $event === null ? null
            : ['actor' => (string) ($names[$event->actor_user_id] ?? 'System'), 'at' => $event->created_at->toIso8601String(), 'reason' => $event->payload['reason'] ?? null];
        $last = fn (string $kind): ?DisbursementEvent => $events->last(fn (DisbursementEvent $event): bool => $event->kind === $kind);
        $authorized = $state->makerUserId === null ? null : $last('authorized');
        $makerCurrent = $state->makerUserId === null || ($authorized !== null && $this->staff->continuouslyHeldSince($state->makerUserId, 'disbursements.authorize',
            $authorized->created_at->format('Y-m-d\TH:i:s.uP'), false));
        $dispatched = $intent !== null && $this->dispatched($intent);
        $allowed = [];
        foreach (DisbursementState::COMMANDS as $command) {
            $may = in_array(self::PERMISSIONS[$command], $permissions, true) && $state->allows($command, $userId, $makerCurrent)
                && ($command !== 'requery' || ($dispatched && $closing === null)) && ($command !== 'approve' || $authorized !== null);
            if ($may) {
                $allowed[] = 'disbursement.'.$command;
            }
        }
        $binding = $state->state === 'awaiting_second_approver' && $authorized !== null ? ['revision' => $state->revision,
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount], 'destination' => (string) $authorized->payload['destination']['masked'],
            'intent_digest' => $this->intentDigest($disbursement, $state->revision, (string) $authorized->destination_sha256)] : null;
        $recorded = $last('intent_recorded');
        $held = $state->state === 'on_hold' ? $last('held') : null;
        $precheck = $closing !== null && in_array($closing->cause, ['approve_recheck', 'worker_recheck'], true)
            ? ['state' => 'failed', 'checked_at' => $closing->created_at->toIso8601String(), 'policy_version' => null, 'causes' => $closing->causes]
            : ($authorized !== null ? $authorized->payload['precheck'] : ['state' => 'not_run', 'checked_at' => null, 'policy_version' => null, 'causes' => []]);

        return [...$this->row($disbursement), 'campaign_id' => $disbursement->business_campaign_id, 'revision' => $state->revision, 'due_on' => null,
            'precheck' => $precheck, 'maker' => $attribution($authorized), 'checker' => $attribution($recorded ?? ($closing?->cause === 'approve_recheck' ? $last('failed_closing') : null)),
            'viewer_is_maker' => $state->makerUserId === $userId, 'approval_binding' => $binding,
            'step_up_allowed' => in_array('disbursement.approve', $allowed, true),
            'independence' => ['version' => StaffIndependence::VERSION, 'statement' => StaffIndependence::STATEMENT],
            'intent' => $intent === null || $recorded === null ? null : ['operation_id' => $intent->operation_id, 'recorded_at' => $recorded->created_at->toIso8601String(),
                'receipt' => $this->receipt($recorded->id, $intent->operation_id, $intent->request_id, 'DISBURSEMENT_INTENT_RECORDED',
                    $recorded->created_at->toIso8601String(), $disbursement, $recorded->revision)],
            'dispatch' => $intent === null ? null : $this->dispatchFacts($intent),
            'provider' => $intent === null || ! $dispatched ? null : $this->providerOutcome($intent),
            'hold' => $held === null ? null : ['placed_by' => $attribution($held), 'reason' => (string) ($held->payload['reason'] ?? '')],
            'viewer_placed_hold' => $state->holdPlacerUserId === $userId,
            'issue' => $closing?->kind === 'issued' ? ['holdings' => $disbursement->commitment_count, 'issued_at' => $closing->created_at->toIso8601String(),
                'effective_date' => $closing->effective_date?->format('Y-m-d')] : null,
            'refund' => $closing?->kind === 'failed_closing' ? ['commitments' => $disbursement->commitment_count, 'total' => ['currency' => 'RWF', 'amount' => $disbursement->amount],
                'receipt' => $this->receipt($closing->id, (string) ($closing->operation_id ?? $intent?->operation_id), (string) ($closing->operation_id === null ? $intent?->request_id : $last('failed_closing')?->request_id),
                    'COMMITMENTS_REFUNDED', $closing->created_at->toIso8601String(), $disbursement, $state->revision)] : null,
            'trail' => $events->reverse()->values()->map(fn (DisbursementEvent $event): array => ['id' => $event->id, 'at' => $event->created_at->toIso8601String(),
                'actor' => (string) ($event->actor_user_id === null ? 'System' : ($names[$event->actor_user_id] ?? 'Staff')),
                'action' => ['code' => $event->kind, 'label' => $event->kind, 'tone' => $this->tone($event->kind)], 'reason' => $event->payload['reason'] ?? null])->all(),
            'allowed_actions' => $allowed];
    }

    /** @return array<string, mixed>|null */
    private function dispatchFacts(DisbursementIntent $intent): ?array
    {
        $phases = DisbursementDispatch::query()->where('intent_id', $intent->id)->get()->keyBy('phase');
        $claimed = $phases->get('claimed');
        $outcome = $phases->get('sent') ?? $phases->get('unsent');
        if ($claimed === null || $outcome === null) {
            return null;
        }

        return ['sent_at' => $outcome->created_at->toIso8601String(), 'recheck' => ['state' => 'passed', 'checked_at' => $claimed->created_at->toIso8601String(), 'causes' => []]];
    }

    /** @return array<string, mixed> */
    private function providerOutcome(DisbursementIntent $intent): array
    {
        $events = DisbursementProviderEvent::query()->where('intent_id', $intent->id)->orderBy('id')->get();
        $latest = $events->last(fn (DisbursementProviderEvent $event): bool => $event->disposition === 'applied');
        $reconciliations = DisbursementReconciliation::query()->where('intent_id', $intent->id)->orderBy('id')->get();
        $terminal = $reconciliations->first(fn (DisbursementReconciliation $reconciliation): bool => in_array($reconciliation->decision, ['matched_success', 'matched_failure'], true));
        $exception = $reconciliations->contains(fn (DisbursementReconciliation $reconciliation): bool => $reconciliation->decision === 'exception')
            || $events->contains(fn (DisbursementProviderEvent $event): bool => in_array($event->disposition, ['after_final', 'conflict', 'key_conflict', 'unverifiable'], true));
        $state = $this->providerState($intent);

        return ['state' => $state, 'operation_id' => $intent->operation_id, 'provider_reference' => $intent->provider_reference,
            'observed_at' => $latest?->observed_at->toIso8601String(), 'effective_at' => $latest?->effective_at?->toIso8601String(),
            'error_code' => $state === 'failed' ? 'PAYOUT_FAILED_FINAL' : null,
            'reconciliation' => $exception ? 'exception' : ($terminal !== null ? 'matched' : 'unreconciled'),
            'reconciled_at' => $exception ? null : $terminal?->created_at->toIso8601String()];
    }

    private function dispatched(DisbursementIntent $intent): bool
    {
        return DisbursementDispatch::query()->where('intent_id', $intent->id)->whereIn('phase', ['sent', 'unsent'])->exists();
    }

    private function maskedDestination(Disbursement $disbursement): string
    {
        $authorized = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->where('kind', 'authorized')->orderByDesc('revision')->first();
        if ($authorized !== null) {
            return (string) $authorized->payload['destination']['masked'];
        }

        $destination = $this->verifiedDestination($disbursement);

        return $destination === null ? 'Unverified' : $destination->masked;
    }

    private function tone(string $kind): string
    {
        return match ($kind) {
            'authorized', 'intent_recorded' => 'blue',
            'held' => 'amber',
            'succeeded' => 'green',
            'failed_closing', 'rejected' => 'red',
            default => 'grey',
        };
    }

    private function reference(Disbursement $disbursement): string
    {
        return 'DSB-'.strtoupper(substr($disbursement->id, -8));
    }

    private function instruction(DisbursementIntent $intent): PayoutInstruction
    {
        return new PayoutInstruction($intent->id, $intent->operation_id, $intent->provider_reference, $intent->amount, $intent->currency,
            $intent->destination_id, $intent->destination_sha256, $intent->environment);
    }

    /** @return array<string, string> */
    private function intentFacts(DisbursementIntent $intent, bool $withReference): array
    {
        return ['operation_id' => $intent->operation_id, 'provider' => $intent->provider,
            'provider_reference' => $withReference ? $intent->provider_reference : $intent->provider_reference_sha256, 'environment' => $intent->environment,
            'currency' => $intent->currency, 'amount' => $intent->amount, 'destination_sha256' => $intent->destination_sha256];
    }

    /** @return array<string, string|null> */
    private function eventFacts(VerifiedPayoutEvent $event): array
    {
        return ['operation_id' => $event->operationId, 'provider' => $event->provider, 'provider_reference' => $event->providerReference,
            'environment' => $event->environment, 'currency' => $event->currency, 'amount' => $event->amount, 'destination_sha256' => $event->destinationSha256,
            'state' => $event->state, 'effective_at' => $event->effectiveAt];
    }

    /** @return array<string, string|null> */
    private function observedFacts(DisbursementProviderEvent $event): array
    {
        return ['event_id' => $event->provider_event_id, 'state' => $event->state, 'amount' => $event->amount, 'currency' => $event->currency,
            'environment' => $event->environment, 'operation_id' => $event->observed_operation_id, 'provider_reference_sha256' => $event->provider_reference_sha256,
            'destination_sha256' => $event->destination_sha256, 'effective_at' => $event->effective_at?->toIso8601String()];
    }

    private function digits(?string $amount): ?string
    {
        return $amount !== null && preg_match('/^(0|[1-9][0-9]{0,11})$/D', $amount) === 1 ? $amount : null;
    }

    private function authorization(Disbursement $disbursement): DisbursementEvent
    {
        return DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->where('kind', 'authorized')->orderByDesc('revision')->first()
            ?? throw new DisbursementViolation('DISBURSEMENT_AUTHORIZATION_MISSING');
    }

    private function state(Disbursement $disbursement): DisbursementState
    {
        return DisbursementState::fold(array_values(DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->orderBy('revision')
            ->get(['kind', 'actor_user_id'])->map(fn (DisbursementEvent $event): array => ['kind' => $event->kind, 'actor_user_id' => $event->actor_user_id])->all()));
    }

    private function lock(Disbursement $disbursement): void
    {
        Disbursement::query()->whereKey($disbursement->id)->lockForUpdate()->sole();
    }

    private function disbursement(string $id): Disbursement
    {
        return Disbursement::query()->find($id) ?? throw new CommandRejection('DISBURSEMENT_NOT_FOUND', 404);
    }

    private function assertRevision(DisbursementState $state, int $expectedRevision): void
    {
        if ($state->revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', revision: $state->revision);
        }
    }

    private function reason(string $reason, DisbursementState $state): string
    {
        return DisbursementReason::normalize($reason) ?? throw new CommandRejection('VALIDATION_FAILED', 422, $state->revision,
            ['reason' => ['Give a reason of at most 1,000 characters without control characters.']]);
    }

    /** @return array<string, mixed> */
    private function input(int $expectedRevision, string $reason): array
    {
        $input = ['expected_revision' => $expectedRevision, 'reason' => $reason];
        try {
            $this->json->encode($input);
        } catch (CommandRejection) {
            $input = ['invalid_input_sha256' => hash('sha256', serialize($input))];
        }

        return $input;
    }

    private function environment(): string
    {
        return $this->isolation->profile();
    }

    /**
     * Runs one transaction with deadlock and serialization retries; exhausting them is a 503
     * `RETRYABLE_CONTENTION`, resolved by looking the operation up before any same-key retry.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    private function serialized(Closure $operation): mixed
    {
        try {
            return DB::transaction($operation, 3);
        } catch (DeadlockException) {
            throw new CommandRejection('RETRYABLE_CONTENTION', 503);
        } catch (QueryException $exception) {
            throw in_array((string) $exception->getCode(), ['40001', '40P01'], true) ? new CommandRejection('RETRYABLE_CONTENTION', 503) : $exception;
        }
    }
}
