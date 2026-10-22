<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Domain\Disbursement\PlatformFee;

/**
 * Posts a disbursement's platform fee (BRS v2.0 SVC-R01, FEE-R02) inside the caller's issue
 * transaction: the fee leaves disbursement settlement, the Audit Partner's share is credited to
 * the payable account under their name and the rest is Rozine's revenue. Posting the same closing
 * again returns without a second entry. A Business with no profile (a synthetic campaign) has no
 * wallet to own the entry, so nothing is posted and the fee stays in disbursement settlement.
 */
interface PlatformFeeLedger
{
    /**
     * @param  array{report_id: string, party_id: string}|null  $auditPartner
     * @return bool whether the fee is posted
     */
    public function post(string $disbursementId, string $businessId, string $closingId, string $operationId, PlatformFee $fee, ?array $auditPartner): bool;
}
