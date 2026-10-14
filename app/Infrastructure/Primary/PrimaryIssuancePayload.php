<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Disbursement\ClosingEvidence;
use RuntimeException;

/** Deterministic retained envelope assembly, shared only by the writer and its SELECT-only verifier. */
final class PrimaryIssuancePayload
{
    /**
     * @param  array<string, mixed>  $facts
     * @param  array<string, mixed>  $purchase
     * @return array<string, mixed>
     */
    public static function holding(array $facts, array $purchase, ClosingEvidence $closing, string $fundingId,
        string $holdingId, string $receiptId, string $issueEntryId): array
    {
        $instalments = $facts['rights']['instalments'] ?? [];
        if (! is_array($instalments) || ! array_is_list($instalments) || count($instalments) !== $closing->termMonths
            || count($closing->dueDates ?? []) !== $closing->termMonths) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }
        $schedule = [];
        foreach ($instalments as $offset => $instalment) {
            if (($instalment['index'] ?? null) !== $offset + 1) {
                throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
            }
            $schedule[] = [...$instalment, 'due_date' => $closing->dueDates[$offset]];
        }

        return ['scope' => 'primary-issued-holding-v1', 'holding_id' => $holdingId, ...$facts,
            'funding_id' => $fundingId, 'disbursement_closing_id' => $closing->closingId, 'closing_sha256' => $closing->closingSha256,
            'schedule' => $schedule, 'issued_at' => $closing->recordedAt, 'disbursement_effective_at' => $closing->effectiveAt,
            'effective_date' => $closing->effectiveDate,
            'receipt' => ['receipt_id' => $receiptId, 'code' => 'HOLDING_ISSUED', 'operation_id' => $closing->intentOperationId,
                'closing_id' => $closing->closingId, 'issued_at' => $closing->recordedAt],
            'cash' => [...$purchase['cash'], 'issue_entry_id' => $issueEntryId]];
    }

    /** @param list<array{id: string, commitment_id: string, sha256: string}> $holdings
     * @return array<string, mixed>
     */
    public static function campaign(ClosingEvidence $closing, string $fundingId, array $holdings): array
    {
        return ['scope' => 'primary-campaign-issuance-v1', 'campaign_id' => $closing->campaignId,
            'business_id' => $closing->businessId, 'funding_id' => $fundingId, 'closing' => get_object_vars($closing),
            'exposure' => ['reservation_id' => $closing->exposureReservationId, 'principal' => $closing->amount, 'state' => 'outstanding'],
            'holdings' => $holdings, 'issued_at' => $closing->recordedAt];
    }
}
