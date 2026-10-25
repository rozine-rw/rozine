<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use Closure;

/**
 * The acting Investor's cart (BRS v2.0 INV-R02), one line per campaign, read and changed under
 * current Investor authority with the actor and Party locked. A convenience list only: it holds no
 * units, money or terms, and the checkout still admits every purchase under its own locks.
 *
 * @phpstan-type CartLine array{campaign_id: string, units: int}
 */
interface InvestorCart
{
    /** The most lines one cart holds, the length of the Deals deck. */
    public const int MAX_LINES = 50;

    /**
     * How many lines the user's Party holds, for the Cart tab's badge: a plain count, taken without
     * the authority lock, and zero for a user with no Party.
     */
    public function count(int $userId): int;

    /**
     * The Party's lines, oldest first.
     *
     * @return list<CartLine>
     */
    public function lines(int $userId, ?int $contextRevision): array;

    /**
     * Puts the campaign in the cart at `$units`, or changes the units of the line already there.
     * `$admit` runs inside the authority lock, is told whether the line exists and how many lines
     * the cart holds, and throws a `CommandRejection` to refuse.
     *
     * @param  Closure(bool $exists, int $count): void  $admit
     * @return array{campaign_id: string, units: int, count: int}
     */
    public function put(int $userId, int $contextRevision, string $campaignId, int $units, Closure $admit): array;

    /**
     * Takes the campaign out of the cart; a campaign that is not there leaves it unchanged.
     *
     * @return array{campaign_id: string, removed: bool, count: int}
     */
    public function remove(int $userId, int $contextRevision, string $campaignId): array;

    /**
     * Takes a campaign the Party has just bought out of its cart, inside the confirming purchase's
     * transaction, so the cart holds only what is left to buy. The purchase already holds the
     * Investor's authority, so no lock is taken here.
     */
    public function bought(string $partyId, string $campaignId): void;
}
