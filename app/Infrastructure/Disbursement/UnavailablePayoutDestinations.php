<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\VerifiedDestination;

/** No Business-owned verified destination source exists yet (#96 answer 1): nothing is verified. */
final class UnavailablePayoutDestinations implements PayoutDestinations
{
    public function verified(string $businessId, string $environment): ?VerifiedDestination
    {
        return null;
    }
}
