<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Environment\UatMoneySimulation;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Operations\CommandRejection;
use Illuminate\Support\Facades\DB;

/**
 * Resale on UAT while its money simulation is on: the real market, but only a named synthetic
 * Business's notes trade (`app.uat_money_simulation_businesses`). Listing, reserving, settling and
 * repricing are refused on any other Business's note, including a listing or reservation made
 * before that Business was taken off the list. Withdrawing a listing, staff cancellation and halts
 * pass through, so existing work can always be stopped.
 */
final readonly class SimulatedSecondaryMarket implements SecondaryMarket
{
    public function __construct(private SecondaryMarket $market, private UatMoneySimulation $simulation) {}

    public function list(int $userId, int $contextRevision, string $holdingId, string $units, string $price, string $requestId): array
    {
        $campaignId = DB::table('primary_holdings')->where('id', $holdingId)->value('business_campaign_id')
            ?? DB::table('secondary_transfers')->where('id', $holdingId)->value('business_campaign_id');
        $this->assertCovered($campaignId);

        return $this->market->list($userId, $contextRevision, $holdingId, $units, $price, $requestId);
    }

    public function reserve(int $userId, int $contextRevision, string $listingId, string $units, string $requestId): array
    {
        $this->assertListingCovered($listingId);

        return $this->market->reserve($userId, $contextRevision, $listingId, $units, $requestId);
    }

    public function settle(int $userId, int $contextRevision, string $listingId, string $reservationId, string $requestId): array
    {
        $this->assertListingCovered($listingId);

        return $this->market->settle($userId, $contextRevision, $listingId, $reservationId, $requestId);
    }

    public function amend(int $userId, int $contextRevision, string $listingId, ?string $price, string $requestId): array
    {
        if ($price !== null) {
            $this->assertListingCovered($listingId);
        }

        return $this->market->amend($userId, $contextRevision, $listingId, $price, $requestId);
    }

    public function halt(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return $this->market->halt($staffUserId, $noteId, $reason, $requestId);
    }

    public function lift(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return $this->market->lift($staffUserId, $noteId, $reason, $requestId);
    }

    public function cancelListing(int $staffUserId, string $listingId, string $reason, string $requestId): array
    {
        return $this->market->cancelListing($staffUserId, $listingId, $reason, $requestId);
    }

    /** Refuses a command on an open listing whose Business is not (or no longer) named; an unknown listing is the market's to refuse. */
    private function assertListingCovered(string $listingId): void
    {
        $this->assertCovered(DB::table('secondary_listings')->where('id', $listingId)->value('business_campaign_id'));
    }

    private function assertCovered(mixed $campaignId): void
    {
        $businessId = $campaignId === null ? null : DB::table('business_campaigns')->where('id', $campaignId)->value('business_id');
        if ($businessId !== null && ! $this->simulation->covers((string) $businessId)) {
            throw new CommandRejection('SECONDARY_TRADING_DISABLED', 503);
        }
    }
}
