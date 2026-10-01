<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\IntentDigest;
use App\Domain\Disbursement\IssueSchedule;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderEvent;
use App\Models\DisbursementReconciliation;
use App\Models\DisbursementStepUpProof;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Authenticates a retained closing from its own records (#96 5925426310, 5925545895): the closing
 * digest and every column against its payload (the one recorded instant included), the
 * disbursement digest and funding facts, then the causal ancestry its cause requires. Plain
 * SELECTs only, inside the caller's transaction and its locks.
 *
 * Intent: its digest, its payload twins and its recomputed intent digest, which binds its
 * destination, provider and environment to this disbursement's funding facts.
 *
 * Reconciliation: its rows carry no digest of their own, so what exists is authenticated instead.
 * The closing's digested payload names the provider observation its reconciliation selected; the
 * reconciliation belongs to the intent, takes the matching terminal decision with no causes, and
 * its retained `comparison.intent` and `comparison.observed` equal the intent's facts and that
 * observation's facts exactly. The observation is an applied, terminal observation of the same
 * intent whose provider, operation, reference digest, environment, RWF amount and destination
 * are the intent's. Later observations and current provider state are never consulted, so a
 * retried read sees the same historical evidence.
 *
 * Approve command authority: the journal records a command only after its effect returns, so
 * inside the approve that closes (as `failClose` sees it) the command row does not exist yet. The
 * closing retains the approve's operation, actor and request in its digested payload and native
 * columns; the deferred `disbursement_closings_authority` trigger refuses the outer commit unless
 * the recorded `disbursement.approve` of this disbursement by that actor and request completed with
 * a `CAMPAIGN_FAILED_CLOSING` receipt naming this closing. `find` checks the same binding against
 * the command row whenever it is visible, and always against the step-up proof the approve consumed.
 */
final class EloquentDisbursementClosingEvidence implements DisbursementClosingEvidence
{
    private const string UNAVAILABLE = 'DISBURSEMENT_CLOSING_UNAVAILABLE';

    private const string INTEGRITY_FAILED = 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED';

    private const string APPROVE = 'disbursement.approve';

    public function __construct(private CanonicalJson $json) {}

    public function find(string $closingId): ClosingEvidence
    {
        // A transaction the caller opened, not merely an enclosing test transaction.
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('DISBURSEMENT_CLOSING_TRANSACTION_REQUIRED');
        }
        $closing = DisbursementClosing::query()->find($closingId) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $payload = $this->authenticated($closing);
        $disbursement = Disbursement::query()->find($closing->disbursement_id) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $this->assertDisbursement($disbursement);
        $this->assertClosing($closing, $payload, $disbursement);
        $intent = $closing->intent_id === null ? null : $this->intent($closing->intent_id, $disbursement);
        if ($closing->reconciliation_id !== null && $intent !== null) {
            $this->assertReconciliation($closing->reconciliation_id, $closing, $payload, $intent);
        }
        if ($closing->cause === 'approve_recheck') {
            $this->assertApproveAuthority($closing, $disbursement);
        }
        if ($closing->kind === 'issued') {
            $this->assertSchedule($closing, $disbursement);
        }

        // assertClosing admitted only the four closing shapes.
        $kind = $closing->kind === 'issued' ? 'issued' : 'failed_closing';
        $cause = match ($closing->cause) {
            'approve_recheck' => 'approve_recheck',
            'worker_recheck' => 'worker_recheck',
            'reconciled_failure' => 'reconciled_failure',
            default => 'reconciled_success',
        };

        return new ClosingEvidence($closing->id, $kind, $cause, $closing->causes, $disbursement->id, $disbursement->business_campaign_id,
            $disbursement->business_id, $disbursement->exposure_reservation_id, $disbursement->amount, $disbursement->currency,
            $disbursement->commitments_digest, $disbursement->commitment_count, $disbursement->term_months, $intent?->id, $intent?->operation_id,
            $closing->reconciliation_id, $closing->operation_id, $closing->effective_at?->toIso8601String(), $closing->effective_date?->format('Y-m-d'),
            $closing->due_dates, $this->recordedAt($closing), $closing->sha256, $disbursement->sha256);
    }

    /**
     * The record's payload, once its stored digest is the canonical digest of that payload.
     *
     * @return array<string, mixed>
     */
    private function authenticated(Model $record): array
    {
        try {
            $payload = $record->getAttribute('payload');
        } catch (DecryptException) {
            throw new DisbursementViolation(self::UNAVAILABLE);
        }
        if (! is_array($payload) || ! hash_equals((string) $record->getAttribute('sha256'), hash('sha256', $this->json->encode($payload)))) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }

        return $payload;
    }

    private function assertDisbursement(Disbursement $disbursement): void
    {
        $payload = $this->authenticated($disbursement);
        $commitments = $payload['commitment_ids'] ?? null;
        if (($payload['campaign_id'] ?? null) !== $disbursement->business_campaign_id || ($payload['business_id'] ?? null) !== $disbursement->business_id
            || ($payload['exposure_reservation_id'] ?? null) !== $disbursement->exposure_reservation_id
            || ($payload['principal'] ?? null) !== $disbursement->amount || $disbursement->currency !== 'RWF'
            || ($payload['commitments_digest'] ?? null) !== $disbursement->commitments_digest
            || ! is_array($commitments) || count($commitments) !== $disbursement->commitment_count
            || ($payload['term_months'] ?? null) !== $disbursement->term_months) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /** The one recorded instant: the native whole-second `created_at`, in the payload's ISO-8601 form. */
    private function recordedAt(DisbursementClosing $closing): string
    {
        return $closing->created_at->utc()->toIso8601String();
    }

    /**
     * Every column equals its payload twin, and the columns take one of the closing shapes of the
     * `disbursement_closing_facts` and `disbursement_closing_authority` constraints.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertClosing(DisbursementClosing $closing, array $payload, Disbursement $disbursement): void
    {
        $failed = $closing->kind === 'failed_closing';
        $approve = $closing->cause === 'approve_recheck';
        $expected = ['closing_id' => $closing->id, 'disbursement_id' => $disbursement->id, 'kind' => $closing->kind, 'cause' => $closing->cause,
            'causes' => $closing->causes, 'intent_id' => $closing->intent_id, 'reconciliation_id' => $closing->reconciliation_id,
            'provider_event_id' => $closing->reconciliation_id === null ? null : ($payload['provider_event_id'] ?? null),
            'operation_id' => $closing->operation_id, 'command' => $approve ? self::APPROVE : null, 'actor_user_id' => $closing->actor_user_id,
            'request_id' => $closing->request_id, 'effective_at' => $closing->effective_at?->toIso8601String(),
            'effective_date' => $closing->effective_date?->format('Y-m-d'), 'due_dates' => $closing->due_dates,
            'refund' => $failed ? ['commitments' => $disbursement->commitment_count, 'amount' => $disbursement->amount, 'fee' => '0'] : null,
            'recorded_at' => $this->recordedAt($closing)];
        ksort($expected);
        ksort($payload);
        $intent = $closing->intent_id !== null;
        $reconciled = $closing->reconciliation_id !== null;
        $operation = $closing->operation_id !== null;
        $authority = $closing->actor_user_id !== null && $closing->request_id !== null;
        $causes = array_filter($closing->causes, is_string(...)) === $closing->causes;
        $effective = $closing->effective_at !== null || $closing->effective_date !== null || $closing->due_dates !== null;
        $shape = match ($closing->cause) {
            'reconciled_success' => ! $failed && $intent && $reconciled && ! $operation && $closing->effective_at !== null
                && $closing->effective_date !== null && $closing->due_dates !== null && $closing->due_dates !== [],
            'reconciled_failure' => $failed && $intent && $reconciled && ! $operation && ! $effective,
            'worker_recheck' => $failed && $intent && ! $reconciled && ! $operation && ! $effective && $closing->causes !== [],
            'approve_recheck' => $failed && ! $intent && ! $reconciled && $operation && ! $effective && $closing->causes !== [],
            default => false,
        };
        if ($payload !== $expected || ! $causes || ! $shape || $authority !== $approve || (! $approve && $closing->request_id !== null)
            || ! in_array($closing->kind, ['issued', 'failed_closing'], true)) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /** The closing's intent: this disbursement's, authenticated, with its amount, commitments and recomputed intent digest. */
    private function intent(string $intentId, Disbursement $disbursement): DisbursementIntent
    {
        $intent = DisbursementIntent::query()->find($intentId) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $payload = $this->authenticated($intent);
        $digest = IntentDigest::intent(['disbursement_id' => $disbursement->id, 'revision' => $intent->revision, 'campaign_id' => $disbursement->business_campaign_id,
            'exposure_reservation_id' => $disbursement->exposure_reservation_id, 'amount' => $disbursement->amount, 'currency' => $disbursement->currency,
            'destination_sha256' => $intent->destination_sha256, 'commitments_digest' => $disbursement->commitments_digest, 'provider' => $intent->provider,
            'environment' => $disbursement->environment]);
        if ($intent->disbursement_id !== $disbursement->id || ($payload['intent_id'] ?? null) !== $intent->id
            || ($payload['disbursement_id'] ?? null) !== $disbursement->id || ($payload['operation_id'] ?? null) !== $intent->operation_id
            || ($payload['revision'] ?? null) !== $intent->revision || ($payload['amount'] ?? null) !== $intent->amount
            || $intent->amount !== $disbursement->amount || $intent->currency !== $disbursement->currency
            || $intent->commitments_digest !== $disbursement->commitments_digest || ($payload['intent_digest'] ?? null) !== $intent->intent_digest
            || ! hash_equals($digest, $intent->intent_digest)
            || ($payload['provider'] ?? null) !== $intent->provider || ($payload['environment'] ?? null) !== $intent->environment
            || $intent->environment !== $disbursement->environment || ($payload['maker_user_id'] ?? null) !== $intent->maker_user_id
            || ($payload['checker_user_id'] ?? null) !== $intent->checker_user_id) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }

        return $intent;
    }

    /**
     * The reconciliation that closed the intent and the observation the closing retains as its
     * source: see the class description.
     *
     * @param  array<string, mixed>  $payload  the closing's authenticated payload
     */
    private function assertReconciliation(string $reconciliationId, DisbursementClosing $closing, array $payload, DisbursementIntent $intent): void
    {
        $issued = $closing->kind === 'issued';
        $reconciliation = DisbursementReconciliation::query()->find($reconciliationId) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $event = DisbursementProviderEvent::query()->find((string) $reconciliation->provider_event_id) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $effectiveAt = $event->effective_at?->toIso8601String();
        $compared = $reconciliation->comparison['intent'] ?? null;
        $observed = $reconciliation->comparison['observed'] ?? null;
        $observations = $reconciliation->comparison['observations'] ?? null;
        $intentFacts = ['operation_id' => $intent->operation_id, 'provider' => $intent->provider, 'provider_reference' => $intent->provider_reference_sha256,
            'environment' => $intent->environment, 'currency' => $intent->currency, 'amount' => $intent->amount, 'destination_sha256' => $intent->destination_sha256];
        $observedFacts = ['event_id' => $event->provider_event_id, 'state' => $event->state, 'amount' => $event->amount, 'currency' => $event->currency,
            'environment' => $event->environment, 'operation_id' => $event->observed_operation_id, 'provider_reference_sha256' => $event->provider_reference_sha256,
            'destination_sha256' => $event->destination_sha256, 'effective_at' => $effectiveAt];
        if (is_array($compared)) {
            ksort($compared);
        }
        if (is_array($observed)) {
            ksort($observed);
        }
        ksort($intentFacts);
        ksort($observedFacts);
        if (($payload['provider_event_id'] ?? null) !== $event->id || $reconciliation->intent_id !== $intent->id
            || $reconciliation->decision !== ($issued ? 'matched_success' : 'matched_failure') || $reconciliation->causes !== []
            || ! is_int($observations) || $observations < 1 || $compared !== $intentFacts || $observed !== $observedFacts
            || $event->intent_id !== $intent->id || $event->disposition !== 'applied' || $event->state !== ($issued ? 'succeeded' : 'failed')
            || $event->provider !== $intent->provider || $event->observed_operation_id !== $intent->operation_id
            || $event->provider_reference_sha256 !== $intent->provider_reference_sha256 || $event->environment !== $intent->environment
            || $event->amount !== $intent->amount || $event->currency !== 'RWF' || $event->destination_sha256 !== $intent->destination_sha256
            || ($issued && ($effectiveAt === null || $closing->effective_at?->toIso8601String() !== $effectiveAt))) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /**
     * The approve that closed before any intent: its retained command authority, against the step-up
     * proof it consumed and, once the journal has recorded it, the command itself.
     */
    private function assertApproveAuthority(DisbursementClosing $closing, Disbursement $disbursement): void
    {
        $proof = DisbursementStepUpProof::query()->where('consumed_operation_id', $closing->operation_id)->first() ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $operation = DB::table('command_operations')->where('id', $closing->operation_id)
            ->first(['command', 'actor_user_id', 'request_id', 'target_type', 'target_id', 'result']);
        $result = $operation === null ? null : json_decode((string) $operation->result, true);
        $recorded = is_array($result) ? [$result['status'] ?? null, $result['code'] ?? null, $result['data']['receipt']['receipt_id'] ?? null] : null;
        if ($proof->purpose !== self::APPROVE || $proof->disbursement_id !== $disbursement->id || $proof->amount !== $disbursement->amount
            || $proof->consumed_at === null || $proof->actor_user_id !== $closing->actor_user_id
            || ($operation !== null && ([$operation->command, (int) $operation->actor_user_id, (string) $operation->request_id, $operation->target_type,
                $operation->target_id, $recorded] !== [self::APPROVE, $closing->actor_user_id, $closing->request_id, 'disbursement', $disbursement->id,
                    ['completed', 'CAMPAIGN_FAILED_CLOSING', $closing->id]]))) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /** The Kigali effective date and due dates, recomputed from the effective instant rather than trusted. */
    private function assertSchedule(DisbursementClosing $closing, Disbursement $disbursement): void
    {
        try {
            $schedule = IssueSchedule::dates((string) $closing->effective_at?->toIso8601String(), $disbursement->term_months);
        } catch (DisbursementViolation) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
        if ($schedule->effectiveDate !== $closing->effective_date?->format('Y-m-d') || $schedule->dueDates !== $closing->due_dates) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }
}
