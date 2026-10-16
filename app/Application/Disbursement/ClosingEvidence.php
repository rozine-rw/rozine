<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/**
 * A disbursement's retained terminal closing, authenticated with its disbursement and causal
 * ancestry (#96 5925426310). `issued` carries its intent, reconciliation, effective instant and
 * recomputed schedule; a failed closing carries its cause and causes, with the intent and
 * reconciliation that cause requires, or the approve operation for `approve_recheck`.
 */
final readonly class ClosingEvidence
{
    /**
     * @param  'issued'|'failed_closing'  $kind
     * @param  'approve_recheck'|'worker_recheck'|'reconciled_failure'|'reconciled_success'  $cause
     * @param  list<string>  $causes
     * @param  list<string>|null  $dueDates
     */
    public function __construct(
        public string $closingId,
        public string $kind,
        public string $cause,
        public array $causes,
        public string $disbursementId,
        public string $campaignId,
        public string $businessId,
        public string $exposureReservationId,
        public string $amount,
        public string $currency,
        public string $commitmentsDigest,
        public int $commitmentCount,
        public int $termMonths,
        public ?string $intentId,
        public ?string $intentOperationId,
        public ?string $reconciliationId,
        public ?string $operationId,
        public ?string $effectiveAt,
        public ?string $effectiveDate,
        public ?array $dueDates,
        public string $recordedAt,
        public string $closingSha256,
        public string $disbursementSha256,
    ) {}
}
