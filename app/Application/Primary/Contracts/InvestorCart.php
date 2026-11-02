<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use Closure;

/**
 * The acting Investor's cart (BRS v2.0 INV-R02), one line per campaign, read and changed under
 * current Investor authority with the actor and Party locked. A convenience list only: it holds no
 * units, money or terms, and the checkout still admits every purchase under its own locks.
 *
 * Every real change to the lines raises the cart's revision once and records the sha256 of its
 * canonical line snapshot (#383), so a whole-cart quote or checkout can tell the cart it priced from
 * the cart as it is now. A change that leaves the lines as they were changes neither.
 *
 * @phpstan-type CartLine array{campaign_id: string, units: int}
 * @phpstan-type CartSnapshot array{cart_id: string|null, revision: int, sha256: string, lines: list<CartLine>}
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
     * The Party's lines, oldest first, with the cart's id, revision and snapshot hash. A Party whose
     * cart has never changed has no cart id and revision 0, and the hash of an empty snapshot.
     *
     * @return CartSnapshot
     */
    public function snapshot(int $userId, ?int $contextRevision): array;

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

    /**
     * The Party's lines with their ids, in the order they were added, for a whole-cart checkout that
     * already holds the Party's authority (and, when it needs them exact, the cart root).
     *
     * @return list<array{id: string, campaign_id: string, units: int}>
     */
    public function partyLines(string $partyId): array;
}
