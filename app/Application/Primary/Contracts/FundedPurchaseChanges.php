<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * Tells each Investor in a funded campaign that their purchase moved while the payout to the
 * Business is under way (approved, sent, or observed by the provider), so their Portfolio,
 * commitment and Holding pages reload. It records a change, never what the purchase now is.
 */
interface FundedPurchaseChanges
{
    /** Records a `purchase` change for every purchase in the campaign's funding, inside the caller's transaction. */
    public function record(string $campaignId): void;
}
