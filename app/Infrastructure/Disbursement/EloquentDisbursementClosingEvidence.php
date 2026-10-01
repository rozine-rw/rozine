<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Disbursement\DisbursementViolation;
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
 * Authenticates a retained closing from its own records (#96 5925426310): the closing digest and
 * every column against its payload, the disbursement digest and funding facts, then the causal
 * ancestry its cause requires. Plain SELECTs only, inside the caller's transaction and its locks.
 *
 * Reconciliation rows carry no digest of their own, so a reconciliation is authenticated by its
 * bindings instead: its intent, its terminal decision and its applied final provider observation,
 * whose recorded facts must agree with what the reconciliation compared.
 *
 * The approve operation of an `approve_recheck` closing is authenticated by the step-up proof it
 * consumed. The journal records the operation itself only after the closing's effect returns, so
 * inside that same operation (as `failClose` sees it) the command operation does not exist yet;
 * the deferred `disbursement_closing_operation` key makes it exist at commit. Once it exists it
 * must be this disbursement's approve.
 */
final class EloquentDisbursementClosingEvidence implements DisbursementClosingEvidence
{
    private const string UNAVAILABLE = 'DISBURSEMENT_CLOSING_UNAVAILABLE';

    private const string INTEGRITY_FAILED = 'DISBURSEMENT_CLOSING_INTEGRITY_FAILED';

    private const string INSTANT = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/D';

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
            $this->assertReconciliation($closing->reconciliation_id, $closing, $intent);
        }
        if ($closing->cause === 'approve_recheck') {
            $this->assertApproveOperation((string) $closing->operation_id, $disbursement);
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
            $closing->due_dates, (string) $payload['recorded_at'], $closing->sha256, $disbursement->sha256);
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

    /**
     * Every column equals its payload twin, and the columns take one of the closing shapes of the
     * `disbursement_closing_facts` constraint.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertClosing(DisbursementClosing $closing, array $payload, Disbursement $disbursement): void
    {
        $failed = $closing->kind === 'failed_closing';
        $expected = ['closing_id' => $closing->id, 'disbursement_id' => $disbursement->id, 'kind' => $closing->kind, 'cause' => $closing->cause,
            'causes' => $closing->causes, 'intent_id' => $closing->intent_id, 'reconciliation_id' => $closing->reconciliation_id,
            'operation_id' => $closing->operation_id, 'effective_at' => $closing->effective_at?->toIso8601String(),
            'effective_date' => $closing->effective_date?->format('Y-m-d'), 'due_dates' => $closing->due_dates,
            'refund' => $failed ? ['commitments' => $disbursement->commitment_count, 'amount' => $disbursement->amount, 'fee' => '0'] : null,
            'recorded_at' => $payload['recorded_at'] ?? null];
        ksort($expected);
        ksort($payload);
        $intent = $closing->intent_id !== null;
        $reconciled = $closing->reconciliation_id !== null;
        $operation = $closing->operation_id !== null;
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
        if ($payload !== $expected || ! $causes || ! $shape
            || ! in_array($closing->kind, ['issued', 'failed_closing'], true)
            || ! is_string($expected['recorded_at']) || preg_match(self::INSTANT, $expected['recorded_at']) !== 1) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /** The closing's intent: this disbursement's, authenticated, with its amount and commitments. */
    private function intent(string $intentId, Disbursement $disbursement): DisbursementIntent
    {
        $intent = DisbursementIntent::query()->find($intentId) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $payload = $this->authenticated($intent);
        if ($intent->disbursement_id !== $disbursement->id || ($payload['intent_id'] ?? null) !== $intent->id
            || ($payload['disbursement_id'] ?? null) !== $disbursement->id || ($payload['operation_id'] ?? null) !== $intent->operation_id
            || ($payload['revision'] ?? null) !== $intent->revision || ($payload['amount'] ?? null) !== $intent->amount
            || $intent->amount !== $disbursement->amount || $intent->currency !== $disbursement->currency
            || $intent->commitments_digest !== $disbursement->commitments_digest || ($payload['intent_digest'] ?? null) !== $intent->intent_digest
            || ($payload['provider'] ?? null) !== $intent->provider || ($payload['environment'] ?? null) !== $intent->environment
            || $intent->environment !== $disbursement->environment || ($payload['maker_user_id'] ?? null) !== $intent->maker_user_id
            || ($payload['checker_user_id'] ?? null) !== $intent->checker_user_id) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }

        return $intent;
    }

    /**
     * The reconciliation that closed the intent: its terminal decision agrees with the closing, and
     * the applied final observation it names agrees with what it compared and, for an issue, with
     * the closing's effective instant.
     */
    private function assertReconciliation(string $reconciliationId, DisbursementClosing $closing, DisbursementIntent $intent): void
    {
        $issued = $closing->kind === 'issued';
        $reconciliation = DisbursementReconciliation::query()->find($reconciliationId) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $event = DisbursementProviderEvent::query()->find((string) $reconciliation->provider_event_id) ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $compared = $reconciliation->comparison['intent'] ?? null;
        $observed = $reconciliation->comparison['observed'] ?? null;
        $effectiveAt = $event->effective_at?->toIso8601String();
        if ($reconciliation->intent_id !== $intent->id || $reconciliation->decision !== ($issued ? 'matched_success' : 'matched_failure')
            || $reconciliation->causes !== [] || $event->intent_id !== $intent->id || $event->disposition !== 'applied'
            || $event->state !== ($issued ? 'succeeded' : 'failed') || ! is_array($compared) || ! is_array($observed)
            || ($compared['operation_id'] ?? null) !== $intent->operation_id || ($compared['amount'] ?? null) !== $intent->amount
            || ($compared['provider_reference'] ?? null) !== $intent->provider_reference_sha256
            || ($observed['event_id'] ?? null) !== $event->provider_event_id || ($observed['state'] ?? null) !== $event->state
            || ($observed['effective_at'] ?? null) !== $effectiveAt
            || ($issued && ($effectiveAt === null || $closing->effective_at?->toIso8601String() !== $effectiveAt))) {
            throw new DisbursementViolation(self::INTEGRITY_FAILED);
        }
    }

    /** The approve that closed before any intent: its consumed step-up proof, and its recorded operation once it exists. */
    private function assertApproveOperation(string $operationId, Disbursement $disbursement): void
    {
        $proof = DisbursementStepUpProof::query()->where('consumed_operation_id', $operationId)->first() ?? throw new DisbursementViolation(self::UNAVAILABLE);
        $operation = DB::table('command_operations')->where('id', $operationId)->first(['command', 'actor_user_id', 'target_type', 'target_id']);
        if ($proof->purpose !== 'disbursement.approve' || $proof->disbursement_id !== $disbursement->id || $proof->amount !== $disbursement->amount
            || $proof->consumed_at === null || ($operation !== null && [$operation->command, (int) $operation->actor_user_id, $operation->target_type,
                $operation->target_id] !== ['disbursement.approve', $proof->actor_user_id, 'disbursement', $disbursement->id])) {
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
