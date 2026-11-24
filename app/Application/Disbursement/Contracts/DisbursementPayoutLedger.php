<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * Posts a disbursement's payout (QA #663) inside the caller's issue transaction: the net the
 * Business was sent leaves disbursement settlement for the provider's clearing account, under the
 * closing that issued the note. Posting the same closing again returns without a second entry. A
 * Business with no profile (a synthetic campaign) has no wallet to own the entry, so nothing is
 * posted, as with its platform fee.
 */
interface DisbursementPayoutLedger
{
    /** @return bool whether the payout is posted */
    public function post(string $businessId, string $closingId, string $operationId, string $net): bool;
}
