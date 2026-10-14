<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * The Investor's coarse view of a funded campaign's payout to its business (C3 H15): whether the
 * payout is on its way, and if so whether the provider has taken it (`pending`) or its send is not
 * yet confirmed (`unknown`). It never names the provider, the destination or any evidence, and is
 * never authority to refund, issue or pay.
 */
interface CampaignDisbursementProgress
{
    /** @return 'pending'|'unknown'|null null while the payout awaits its approval, or has closed */
    public function inFlight(string $campaignId): ?string;
}
