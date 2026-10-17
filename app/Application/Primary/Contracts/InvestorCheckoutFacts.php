<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * Read-only Investor facts that price and bound a checkout. Never an admission: reserve and
 * confirm still decide under `PrimaryCheckout`'s locks, so these may only lag behind them.
 */
interface InvestorCheckoutFacts
{
    /**
     * Principal the Party already has working in notes (INV-R04 active capital before this
     * checkout): cash committed to confirmed purchases not yet issued plus issued Holding principal.
     */
    public function activeCapital(string $partyId): string;

    /**
     * The acting user's Party, its active capital, the units it holds or has committed in one
     * campaign, which count towards the 50% limit (INV-R01), and its newest unexpired held
     * reservation there, if any.
     *
     * @return array{party_id: string, active_capital: string, campaign_units: string, held_reservation_id: string|null}
     */
    public function forInvestor(int $userId, string $campaignId): array;
}
