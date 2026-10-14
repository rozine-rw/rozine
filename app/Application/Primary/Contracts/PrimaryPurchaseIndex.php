<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * Resolves a reservation or commitment named in a purchase URL to the campaign `PrimaryCheckout`
 * addresses. It grants nothing: the checkout still requires the caller's current Investor
 * authority and refuses a record of another Party as not found, the same answer an unknown id
 * gets here, so this reveals no more than a 404 would.
 */
interface PrimaryPurchaseIndex
{
    /** Refuses `RESERVATION_NOT_FOUND` (404) for an unknown reservation. */
    public function campaignOfReservation(string $reservationId): string;

    /**
     * Refuses `NOT_FOUND` (404) for an unknown commitment.
     *
     * @return array{campaign_id: string, reservation_id: string}
     */
    public function commitment(string $commitmentId): array;

    /**
     * What the Party's own wallet history names each of its reservations by: the deal title, the
     * units and the policy version the purchase was quoted under. Another Party's reservation is
     * left out, as an unknown id would be.
     *
     * @param  list<string>  $reservationIds
     * @return array<string, array{campaign_id: string, deal_name: string, units: string, policy_version: string}>
     */
    public function walletLabels(string $partyId, array $reservationIds): array;
}
