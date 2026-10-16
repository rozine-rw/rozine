<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Operations\CommandRejection;

/**
 * Secondary trading is switched off until Phase 3 acceptance: every command is refused with
 * `SECONDARY_TRADING_DISABLED` before anything is read or written.
 */
final class UnavailableSecondaryMarket implements SecondaryMarket
{
    public function list(int $userId, int $contextRevision, string $holdingId, string $units, string $price, string $requestId): array
    {
        throw self::disabled();
    }

    public function reserve(int $userId, int $contextRevision, string $listingId, string $units, string $requestId): array
    {
        throw self::disabled();
    }

    public function settle(int $userId, int $contextRevision, string $listingId, string $reservationId, string $requestId): array
    {
        throw self::disabled();
    }

    public function amend(int $userId, int $contextRevision, string $listingId, ?string $price, string $requestId): array
    {
        throw self::disabled();
    }

    public function halt(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        throw self::disabled();
    }

    private static function disabled(): CommandRejection
    {
        return new CommandRejection('SECONDARY_TRADING_DISABLED', 503);
    }
}
