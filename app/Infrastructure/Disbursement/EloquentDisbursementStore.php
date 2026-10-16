<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Auditor\Contracts\NoteAuditPartner;
use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\Contracts\PlatformFeeLedger;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\VerifiedDestination;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\FundedPurchaseChanges;
use App\Domain\Disbursement\DisbursementReason;
use App\Domain\Disbursement\DisbursementState;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\IntentDigest;
use App\Domain\Disbursement\IssueSchedule;
use App\Domain\Disbursement\PayoutOutcome;
use App\Domain\Disbursement\PlatformFee;
use App\Domain\Disbursement\Reconciliation;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementProviderEvent;
use App\Models\DisbursementReconciliation;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Disbursement persistence (C3 v2 §2e, BRS v2.0 SVC-R01 and ADM-R02). Every effect runs in one
 * transaction in the lock order confirmed on #96 (5871859618):
 *
 *   FundedCampaigns::lockBusiness → the acting staff user (AuthorizeStaffPermission::handle) →
 *   FundedCampaigns::lockFunded → disbursements row → (inside the port) reservations and
 *   commitments → wallets → ledger.
 *
 * A funded campaign pays automatically: opening its disbursement and every later run record the
 * system's intent as soon as the payout destination is verified and the funding recheck passes,
 * with no staff in the lock chain. A failed recheck closes the campaign and refunds its investors.
 * Hold and release take one authorised staff member and skip the campaign; the worker and the
 * reconciler take no staff lock at all. Nothing here calls the payout provider.
 */
final class EloquentDisbursementStore implements DisbursementStore
{
    public const string POLICY_VERSION = 'brs-2.0-2026-10-16.1';

    /** @var array<string, string> */
    public const array PERMISSIONS = ['hold' => 'disbursements.hold', 'release_hold' => 'disbursements.hold', 'requery' => 'disbursements.requery'];

    private const array STAFF_EVENTS = ['hold' => ['held', 'DISBURSEMENT_HELD'], 'release_hold' => ['hold_released', 'DISBURSEMENT_HOLD_RELEASED']];

    public function __construct(private FundedCampaigns $funding, private PayoutDestinations $destinations,
        private PayoutProvider $provider, private AuthorizeStaffPermission $staff, private OperationJournal $journal, private CanonicalJson $json,
        private EnvironmentIsolation $isolation, private FundedPurchaseChanges $purchases, private PartyNotices $notices,
        private BusinessNoticeAudience $audience, private PlatformFeeLedger $fees, private NoteAuditPartner $auditPartners) {}

    public function page(int $userId, ?string $disbursementId, ?string $before, int $limit): array
    {
        $this->staff->check($userId, 'disbursements.view');
        $permissions = $this->staff->permissions($userId);
        $found = Disbursement::query()->when($before !== null, fn ($query) => $query->where('id', '<', $before))->orderByDesc('id')->limit($limit + 1)->get();
        $rows = $found->take($limit)->values();
        $selected = $disbursementId === null ? null : Disbursement::query()->find($disbursementId);

        return ['permissions' => $permissions, 'rows' => $rows->map(fn (Disbursement $disbursement): array => $this->row($disbursement))->all(),
            'next_cursor' => $found->count() > $limit ? $rows->last()?->id : null,
            'needs_attention' => (int) DB::selectOne("SELECT count(*) AS total FROM disbursements d
                WHERE (SELECT f_state FROM disbursement_fold(d.id)) IN ('ready', 'awaiting_second_approver', 'on_hold')")?->total,
            'disbursement' => $selected === null ? null : $this->detail($selected, $userId, $permissions),
            'refusal' => $disbursementId !== null && $selected === null ? ['code' => 'DISBURSEMENT_NOT_FOUND', 'status' => 404] : null];
    }

    public function command(int $userId, string $disbursementId, string $command, int $expectedRevision, string $reason, string $requestId): array
    {
        $permission = self::PERMISSIONS[$command] ?? throw new DisbursementViolation('DISBURSEMENT_COMMAND_INVALID');
        if ($command === 'requery') {
            throw new DisbursementViolation('DISBURSEMENT_COMMAND_INVALID');
        }
        $this->staff->check($userId, $permission);
        $disbursement = $this->disbursement($disbursementId);
        $input = $this->input($expectedRevision, $reason);

        return $this->serialized(function () use ($userId, $disbursement, $command, $permission, $input, $expectedRevision, $reason, $requestId): array {
            $this->funding->lockBusiness($disbursement->business_id);

            return $this->staff->handle($userId, $permission, fn (): array => $this->journal->execute('staff:'.$userId, $userId, 'disbursement.'.$command,
                $requestId, 'disbursement', $disbursement->id, $input, function (): void {},
                fn (string $operationId): OperationResult => $this->perform($command, $userId, $disbursement, $expectedRevision, $reason, $requestId, $operationId)));
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
        $counts = ['opened' => 0, 'known' => 0, 'queued' => 0, 'failed' => 0];
        foreach ($this->funding->funded(null, $limit) as $reference) {
            [$opened, $outcome] = $this->serialized(function () use ($reference): array {
                $this->funding->lockBusiness($reference->businessId);
                $campaign = $this->funding->lockFunded($reference->campaignId);
                $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->first();

                return [$disbursement === null, $this->advance($disbursement ?? $this->open($campaign), $campaign)];
            });
            $counts[$opened ? 'opened' : 'known']++;
            if ($outcome !== 'known' && isset($counts[$outcome])) {
                $counts[$outcome]++;
            }
        }

        return $counts;
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
            $this->purchases->record($disbursement->business_campaign_id);
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
            // The platform fee is earned once the note is issued (BRS v2.0 SVC-R01); a disbursement
            // opened before the fee rule carries none.
            $disbursement->fee_version === null || $this->fees->post($disbursement->id, $disbursement->business_id, $closingId, $intent->operation_id,
                PlatformFee::of($disbursement->amount), $this->auditPartners->forCampaign($disbursement->business_campaign_id));
            $this->appendEvent($disbursement, $state->revision + 1, 'succeeded', null, null, null, ['closing_id' => $closingId]);
            // The Business is told the funds reached its verified account (BRS v2.0 NTF-R01); a
            // synthetic payout's business has no profile and tells nobody.
            $party = $this->audience->findEntityParty($disbursement->business_id);
            $party === null || $this->notices->record($party, PartyNoticeKind::BusinessDisbursementSent, [
                'title' => (string) $disbursement->payload['title'], 'amount' => $this->payout($disbursement), 'principal' => $disbursement->amount,
                'platform_fee' => $disbursement->platform_fee, 'currency' => 'RWF',
                'account' => $this->maskedDestination($disbursement), 'reference' => $closingId, 'business_id' => $disbursement->business_id,
                'campaign_id' => $disbursement->business_campaign_id], 'business-disbursement-sent:'.$disbursement->id);

            return 'matched_success';
        }
        $closingId = $this->recordClosing($disbursement, 'failed_closing', 'reconciled_failure', [], $intent->id, $reconciliation, $this->instant());
        $this->funding->failClose($campaign, new FailedClosing($closingId, $disbursement->id, 'reconciled_failure', []));
        $this->appendEvent($disbursement, $state->revision + 1, 'failed_closing', null, null, null, ['closing_id' => $closingId]);

        return 'matched_failure';
    }

    /** One hold or release inside its journal operation, by one authorised staff member with a reason. */
    private function perform(string $command, int $userId, Disbursement $disbursement, int $expectedRevision, string $reason, string $requestId,
        string $operationId): OperationResult
    {
        $this->lock($disbursement);
        $state = $this->state($disbursement);
        $state->assertAllows($command, $userId);
        $this->assertRevision($state, $expectedRevision);
        [$kind, $code] = self::STAFF_EVENTS[$command];
        $event = $this->appendEvent($disbursement, $state->revision + 1, $kind, $userId, $operationId, $requestId,
            ['reason' => $this->reason($reason, $state), 'from' => $state->state]);

        return new OperationResult($code, ['disbursement_id' => $disbursement->id,
            'receipt' => $this->receipt($event->id, $operationId, $requestId, $code, $event->created_at->toIso8601String(), $disbursement, $state->revision + 1)], $state->revision + 1);
    }

    /** Opens the disbursement of a locked funded campaign, under the platform fee rule in force. */
    private function open(FundedCampaign $campaign): Disbursement
    {
        $fee = PlatformFee::of($campaign->principal);
        $payload = ['campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId, 'business_name' => $campaign->businessName,
            'title' => $campaign->title, 'exposure_reservation_id' => $campaign->exposureReservationId, 'principal' => $campaign->principal,
            'funded_at' => $campaign->fundedAt, 'term_months' => $campaign->termMonths, 'commitments_digest' => $campaign->commitmentsDigest(),
            'commitment_ids' => array_map(fn ($commitment): string => $commitment->id, $campaign->commitments), 'platform_fee' => $fee->toArray()];
        $disbursement = new Disbursement;
        $disbursement->forceFill(['business_campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId,
            'exposure_reservation_id' => $campaign->exposureReservationId, 'amount' => $campaign->principal, 'currency' => 'RWF',
            'platform_fee' => (string) $fee->fee, 'audit_partner_fee' => (string) $fee->auditPartnerShare, 'fee_version' => PlatformFee::VERSION,
            'commitments_digest' => $campaign->commitmentsDigest(), 'commitment_count' => count($campaign->commitments),
            'term_months' => $campaign->termMonths, 'funded_at' => $campaign->fundedAt, 'environment' => $this->environment(),
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => now('UTC')])->save();

        return $disbursement;
    }

    /**
     * Pays a funded campaign automatically (BRS v2.0 SVC-R01): with a verified destination, the same
     * funding and a passing recheck, the system records the intent for the principal less the fee
     * and queues it; a failed recheck closes the campaign and refunds its investors. Anything else
     * (held, in flight, no verified destination, changed funding, recheck inputs unavailable) leaves
     * it for the next run. The destination is read before the disbursement lock, never under it.
     *
     * @return 'queued'|'failed'|'waiting'|'known'
     */
    private function advance(Disbursement $disbursement, FundedCampaign $campaign): string
    {
        $destination = $this->verifiedDestination($disbursement);
        $this->lock($disbursement);
        $state = $this->state($disbursement);
        if (! $state->awaitsPayment()) {
            return 'known';
        }
        if ($destination === null || ! $this->fundingMatches($disbursement, $campaign)) {
            return 'waiting';
        }
        $recheck = $this->funding->recheck($campaign);
        if ($recheck->outcome === 'unavailable') {
            return 'waiting';
        }
        $recorded = $this->instant();
        if ($recheck->outcome === 'failed') {
            $closingId = $this->recordClosing($disbursement, 'failed_closing', 'auto_recheck', $recheck->causes, null, null, $recorded);
            $this->funding->failClose($campaign, new FailedClosing($closingId, $disbursement->id, 'auto_recheck', $recheck->causes));
            $this->appendEvent($disbursement, $state->revision + 1, 'failed_closing', null, null, null,
                ['closing_id' => $closingId, 'causes' => $recheck->causes, 'policy_version' => $recheck->policyVersion]);

            return 'failed';
        }
        $digest = $destination->digest();
        $binding = $this->binding($disbursement, $digest);
        $intentDigest = $this->intentDigest($disbursement, $state->revision, $digest);
        $facts = ['id' => $destination->id, 'revision' => $destination->revision, 'masked' => $destination->masked];
        $intent = new DisbursementIntent;
        $intent->id = strtolower((string) Str::ulid());
        $operationId = strtolower((string) Str::ulid());
        $requestId = (string) Str::uuid();
        $reference = 'rzd_'.$intent->id;
        $payload = ['intent_id' => $intent->id, 'disbursement_id' => $disbursement->id, 'operation_id' => $operationId, 'request_id' => $requestId,
            'revision' => $state->revision, 'amount' => $this->payout($disbursement), 'destination' => $facts,
            'intent_digest' => $intentDigest, 'provider' => $this->provider->name(), 'environment' => $disbursement->environment,
            'maker_user_id' => null, 'checker_user_id' => null, 'rule_version' => DisbursementState::AUTO_RULE_VERSION, 'recorded_at' => $recorded->toIso8601String()];
        $intent->forceFill(['disbursement_id' => $disbursement->id, 'operation_id' => $operationId, 'request_id' => $requestId, 'revision' => $state->revision,
            'amount' => $this->payout($disbursement), 'currency' => 'RWF', 'destination_id' => $destination->id, 'destination_revision' => $destination->revision,
            'destination_sha256' => $digest, 'commitments_digest' => $disbursement->commitments_digest,
            'binding_sha256' => $binding, 'intent_digest' => $intentDigest, 'provider' => $this->provider->name(),
            'provider_reference' => $reference, 'provider_reference_sha256' => hash('sha256', $reference), 'environment' => $disbursement->environment,
            'idempotent_sends' => $this->provider->idempotentSends(), 'maker_user_id' => null, 'checker_user_id' => null,
            'rule_version' => DisbursementState::AUTO_RULE_VERSION,
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => now('UTC')])->save();
        (new DisbursementDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'queued', 'created_at' => now('UTC')])->save();
        $this->appendEvent($disbursement, $state->revision + 1, 'auto_intent_recorded', null, null, null, ['rule_version' => DisbursementState::AUTO_RULE_VERSION,
            'intent_id' => $intent->id, 'destination' => $facts,
            'precheck' => ['state' => 'passed', 'checked_at' => $recorded->toIso8601String(), 'policy_version' => $recheck->policyVersion, 'causes' => []]],
            $binding, $digest);
        $this->purchases->record($disbursement->business_campaign_id);
        $intentId = $intent->id;
        DB::afterCommit(function () use ($intentId): void {
            try {
                app(DispatchDisbursements::class)->handle($intentId);
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        return 'queued';
    }

    /** A claim (or an idempotent recovery resend) after a fresh recheck under the full lock chain. */
    private function claimOne(string $intentId, bool $recovery): ?PayoutInstruction
    {
        $intent = DisbursementIntent::query()->findOrFail($intentId);
        $disbursement = Disbursement::query()->findOrFail($intent->disbursement_id);
        try {
            return $this->serialized(function () use ($intent, $disbursement, $recovery): ?PayoutInstruction {
                $this->funding->lockBusiness($disbursement->business_id);
                $campaign = $this->funding->lockFunded($disbursement->business_campaign_id);
                $destination = $this->verifiedDestination($disbursement);
                $this->lock($disbursement);
                DisbursementIntent::query()->whereKey($intent->id)->lockForUpdate()->sole();
                $state = $this->state($disbursement);
                $phases = DisbursementDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all();
                $eligible = $recovery ? $state->state === 'queued' && in_array('claimed', $phases, true) && array_intersect(['sent', 'unsent'], $phases) === []
                        && ! DisbursementProviderEvent::query()->where('intent_id', $intent->id)->exists()
                    : $state->state === 'queued' && array_intersect(['claimed', 'recheck_failed'], $phases) === [];
                if (! $eligible || ! $this->fundingMatches($disbursement, $campaign) || $destination === null
                    || ! hash_equals($intent->destination_sha256, $destination->digest())) {
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
        } catch (CommandRejection $rejection) {
            return $rejection->reason === 'FUNDING_SOURCE_UNAVAILABLE' ? null : throw $rejection;
        }
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
     * Business and staff locks), and nothing is locked after it but the change feed's per-subject
     * leaf: the holder only reads events, inserts one and, for an applied one, records the funded
     * purchases' changes. A waiter therefore never holds anything the holder needs, so it adds no cycle.
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
        if ($outcome->disposition === 'applied') {
            $this->purchases->record(Disbursement::query()->findOrFail($intent->disbursement_id)->business_campaign_id);
        }

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

    private function fundingMatches(Disbursement $disbursement, FundedCampaign $campaign): bool
    {
        return $campaign->campaignId === $disbursement->business_campaign_id && $campaign->businessId === $disbursement->business_id
            && $campaign->exposureReservationId === $disbursement->exposure_reservation_id && $campaign->principal === $disbursement->amount
            && $campaign->termMonths === $disbursement->term_months && hash_equals($disbursement->commitments_digest, $campaign->commitmentsDigest());
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
     * `created_at` and, for an issue, the instruction's `issuedAt`. No closing carries staff
     * authority any more: an automatic one is the system's, and a reconciled one retains the provider
     * observation its reconciliation selected.
     *
     * @param  list<string>  $causes
     * @param  list<string>|null  $dueDates
     */
    private function recordClosing(Disbursement $disbursement, string $kind, string $cause, array $causes, ?string $intentId,
        ?DisbursementReconciliation $reconciliation, CarbonImmutable $recordedAt, ?CarbonImmutable $effectiveAt = null,
        ?string $effectiveDate = null, ?array $dueDates = null): string
    {
        $closing = new DisbursementClosing;
        $closing->id = strtolower((string) Str::ulid());
        // Every column has its twin in the digested payload, the effective instant included (#96 5925426310).
        $payload = ['closing_id' => $closing->id, 'disbursement_id' => $disbursement->id, 'kind' => $kind, 'cause' => $cause, 'causes' => $causes,
            'intent_id' => $intentId, 'reconciliation_id' => $reconciliation?->id, 'provider_event_id' => $reconciliation?->provider_event_id,
            'operation_id' => null, 'command' => null, 'actor_user_id' => null, 'request_id' => null,
            'effective_at' => $effectiveAt?->toIso8601String(), 'effective_date' => $effectiveDate, 'due_dates' => $dueDates,
            'refund' => $kind === 'failed_closing' ? ['commitments' => $disbursement->commitment_count, 'amount' => $disbursement->amount, 'fee' => '0'] : null,
            'recorded_at' => $recordedAt->toIso8601String()];
        $closing->forceFill(['disbursement_id' => $disbursement->id, 'intent_id' => $intentId, 'reconciliation_id' => $reconciliation?->id, 'kind' => $kind,
            'cause' => $cause, 'causes' => $causes, 'operation_id' => null, 'actor_user_id' => null,
            'request_id' => null, 'effective_at' => $effectiveAt, 'effective_date' => $effectiveDate, 'due_dates' => $dueDates,
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

    /** What reaches the Business: the principal less the platform fee it was opened under. */
    private function payout(Disbursement $disbursement): string
    {
        return (string) BigInteger::of($disbursement->amount)->minus($disbursement->platform_fee);
    }

    /** @return array{fee: array{currency: string, amount: string}, audit_partner_share: array{currency: string, amount: string}, rozine_share: array{currency: string, amount: string}, payout: array{currency: string, amount: string}, version: string}|null */
    private function feeFacts(Disbursement $disbursement): ?array
    {
        if ($disbursement->fee_version === null) {
            return null;
        }
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $rozine = (string) BigInteger::of($disbursement->platform_fee)->minus($disbursement->audit_partner_fee);

        return ['fee' => $money($disbursement->platform_fee), 'audit_partner_share' => $money($disbursement->audit_partner_fee), 'rozine_share' => $money($rozine),
            'payout' => $money($this->payout($disbursement)), 'version' => $disbursement->fee_version];
    }

    /** @return array<string, mixed> */
    private function row(Disbursement $disbursement): array
    {
        $state = $this->state($disbursement);
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->first();

        return ['id' => $disbursement->id, 'reference' => $this->reference($disbursement), 'business' => (string) $disbursement->payload['business_name'],
            'note_title' => (string) $disbursement->payload['title'], 'destination' => $this->maskedDestination($disbursement),
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount], 'platform_fee' => $this->feeFacts($disbursement), 'state' => $state->state,
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
        $dispatched = $intent !== null && $this->dispatched($intent);
        $allowed = [];
        foreach (DisbursementState::COMMANDS as $command) {
            if (in_array(self::PERMISSIONS[$command], $permissions, true) && $state->allows($command, $userId)
                && ($command !== 'requery' || ($dispatched && $closing === null))) {
                $allowed[] = 'disbursement.'.$command;
            }
        }
        $queuedBy = $last('auto_intent_recorded') ?? $last('authorized');
        $recorded = $last('auto_intent_recorded') ?? $last('intent_recorded');
        $held = $state->state === 'on_hold' ? $last('held') : null;
        $precheck = $closing !== null && in_array($closing->cause, ['approve_recheck', 'worker_recheck', 'auto_recheck'], true)
            ? ['state' => 'failed', 'checked_at' => $closing->created_at->toIso8601String(), 'policy_version' => null, 'causes' => $closing->causes]
            : ($queuedBy !== null ? $queuedBy->payload['precheck'] : ['state' => 'not_run', 'checked_at' => null, 'policy_version' => null, 'causes' => []]);

        return [...$this->row($disbursement), 'campaign_id' => $disbursement->business_campaign_id, 'revision' => $state->revision, 'due_on' => null,
            'precheck' => $precheck, 'automation' => ['rule_version' => DisbursementState::AUTO_RULE_VERSION,
                'payout' => ['currency' => 'RWF', 'amount' => $this->payout($disbursement)],
                'waiting_for' => $state->state !== 'ready' ? null : ($this->verifiedDestination($disbursement) === null ? 'verified_payout_destination' : 'next_run')],
            'intent' => $intent === null || $recorded === null ? null : ['operation_id' => $intent->operation_id, 'recorded_at' => $recorded->created_at->toIso8601String(),
                'receipt' => $this->receipt($recorded->id, $intent->operation_id, $intent->request_id, 'DISBURSEMENT_INTENT_RECORDED',
                    $recorded->created_at->toIso8601String(), $disbursement, $recorded->revision)],
            'dispatch' => $intent === null ? null : $this->dispatchFacts($intent),
            'provider' => $intent === null || ! $dispatched ? null : $this->providerOutcome($intent),
            'hold' => $held === null ? null : ['placed_by' => $attribution($held), 'reason' => (string) ($held->payload['reason'] ?? '')],
            'issue' => $closing?->kind === 'issued' ? ['holdings' => $disbursement->commitment_count, 'issued_at' => $closing->created_at->toIso8601String(),
                'effective_date' => $closing->effective_date?->format('Y-m-d')] : null,
            'refund' => $closing?->kind === 'failed_closing' ? ['commitments' => $disbursement->commitment_count, 'total' => ['currency' => 'RWF', 'amount' => $disbursement->amount],
                'receipt' => $this->receipt($closing->id, (string) ($closing->operation_id ?? $intent->operation_id ?? $closing->id),
                    (string) ($closing->operation_id === null ? ($intent->request_id ?? $closing->id) : $last('failed_closing')?->request_id),
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
        $bound = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->whereIn('kind', ['authorized', 'auto_intent_recorded'])
            ->orderByDesc('revision')->first();
        if ($bound !== null) {
            return (string) $bound->payload['destination']['masked'];
        }

        $destination = $this->verifiedDestination($disbursement);

        return $destination === null ? 'Unverified' : $destination->masked;
    }

    private function tone(string $kind): string
    {
        return match ($kind) {
            'authorized', 'intent_recorded', 'auto_intent_recorded' => 'blue',
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
