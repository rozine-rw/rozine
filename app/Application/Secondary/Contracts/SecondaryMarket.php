<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * The secondary-ready trading port (BRS v2.0 §12), fixed in Phase 1 so Holdings, payouts and the
 * ledger are built to carry it; executable trading stays off until Phase 3 acceptance. Every
 * command runs under the caller's current authority through the command journal: a request id
 * names one effect, and repeating it replays the recorded result. The rules each command
 * enforces are the Domain's (`SecondaryListing`, `TradingWindow`, `PriceCap`, `HoldingLot`,
 * `SettlementQuote`); settlement moves the buyer's cash, the seller's net proceeds, the 2% seller
 * fee and the lot's lowest ordinals in one ledger transaction or not at all.
 *
 * @phpstan-type MarketResult array{status: 'completed'|'rejected', code: string, operation_id: string|null, request_id: string, revision: int|null, data: array<string, mixed>}
 */
interface SecondaryMarket
{
    /**
     * Lists whole units of one of the seller's Holdings at a whole-franc price per unit, within
     * the price cap, for seven days.
     *
     * @return MarketResult
     */
    public function list(int $userId, int $contextRevision, string $holdingId, string $units, string $price, string $requestId): array;

    /**
     * Reserves whole units of an open listing for the buyer for five minutes against cash already
     * settled in their wallet, within their half-of-the-raise limit.
     *
     * @return MarketResult
     */
    public function reserve(int $userId, int $contextRevision, string $listingId, string $units, string $requestId): array;

    /**
     * Settles the buyer's open reservation exactly once.
     *
     * @return MarketResult
     */
    public function settle(int $userId, int $contextRevision, string $listingId, string $reservationId, string $requestId): array;

    /**
     * Reprices or cancels the seller's listing while no reservation is open.
     *
     * @param  string|null  $price  null cancels the listing
     * @return MarketResult
     */
    public function amend(int $userId, int $contextRevision, string $listingId, ?string $price, string $requestId): array;

    /**
     * Compliance's halt of the whole market (`$noteId` null) or of one note, with a recorded
     * reason: open reservations are released and listings cancelled for good.
     *
     * @return MarketResult
     */
    public function halt(int $staffUserId, ?string $noteId, string $reason, string $requestId): array;
}
