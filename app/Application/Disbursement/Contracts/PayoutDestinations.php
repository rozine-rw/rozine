<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\VerifiedDestination;

/**
 * The Business-owned verified payout destination source (#96 answer 1). A destination is usable
 * only while its verification evidence is current for this Business and environment; the source
 * returns null for a missing, expired, revoked or mismatched one, and disbursement fails closed.
 * No Business or Identity persistence exists for it yet: outside the synthetic guard the
 * `UnavailablePayoutDestinations` binding always returns null.
 */
interface PayoutDestinations
{
    public function verified(string $businessId, string $environment): ?VerifiedDestination;
}
