<?php

declare(strict_types=1);

namespace App\Application\Secondary;

use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Operations\CommandRejection;

/**
 * Compliance's resale commands from the Secondary Market page (BRS v3.0 SEC-R08, SEC-R09): halt
 * the whole market (`$noteId` null) or one note, lift a halt in force, and cancel one open listing,
 * each with a written reason. A refusal raised before the market records anything (trading
 * switched off, a listing that does not exist) answers as a rejected result like any other.
 *
 * @phpstan-type OversightResult array{status: string, code: string}
 */
final class OverseeSecondaryMarket
{
    public function __construct(private SecondaryMarket $market) {}

    /** @return OversightResult */
    public function halt(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return self::answer(fn (): array => $this->market->halt($staffUserId, $noteId, $reason, $requestId));
    }

    /** @return OversightResult */
    public function lift(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return self::answer(fn (): array => $this->market->lift($staffUserId, $noteId, $reason, $requestId));
    }

    /** @return OversightResult */
    public function cancel(int $staffUserId, string $listingId, string $reason, string $requestId): array
    {
        return self::answer(fn (): array => $this->market->cancelListing($staffUserId, $listingId, $reason, $requestId));
    }

    /**
     * @param  callable(): array{status: string, code: string}  $command
     * @return OversightResult
     */
    private static function answer(callable $command): array
    {
        try {
            $result = $command();
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason];
        }

        return ['status' => $result['status'], 'code' => $result['code']];
    }
}
