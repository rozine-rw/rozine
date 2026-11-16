<?php

declare(strict_types=1);

namespace App\Application\Secondary;

use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Secondary\SecondaryTerms;

/**
 * The Investor's resale commands from the Market page (BRS v3.0 §12): list units of a lot at a
 * price per unit, reserve units of someone else's listing, settle that reservation, and withdraw
 * one's own listing. Each answers the market's recorded result under the resale terms version.
 *
 * @phpstan-import-type MarketResult from SecondaryMarket
 *
 * @phpstan-type TradeResult array{status: 'completed'|'rejected', code: string, operation_id: string|null, request_id: string, revision: int|null,
 *     data: array<string, mixed>, http_status: int, policy_version: string}
 */
final class TradeOnSecondaryMarket
{
    public function __construct(private SecondaryMarket $market) {}

    /** @return TradeResult */
    public function list(int $userId, int $contextRevision, string $lotId, string $units, string $price, string $requestId): array
    {
        return self::terms($this->market->list($userId, $contextRevision, $lotId, $units, $price, $requestId));
    }

    /** @return TradeResult */
    public function reserve(int $userId, int $contextRevision, string $listingId, string $units, string $requestId): array
    {
        return self::terms($this->market->reserve($userId, $contextRevision, $listingId, $units, $requestId));
    }

    /** @return TradeResult */
    public function settle(int $userId, int $contextRevision, string $listingId, string $reservationId, string $requestId): array
    {
        return self::terms($this->market->settle($userId, $contextRevision, $listingId, $reservationId, $requestId));
    }

    /** @return TradeResult */
    public function withdraw(int $userId, int $contextRevision, string $listingId, string $requestId): array
    {
        return self::terms($this->market->amend($userId, $contextRevision, $listingId, null, $requestId));
    }

    /**
     * @param  MarketResult  $result
     * @return TradeResult
     */
    private static function terms(array $result): array
    {
        return [...$result, 'policy_version' => SecondaryTerms::VERSION];
    }
}
