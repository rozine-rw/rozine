<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use DateTimeInterface;

/**
 * Every Investor account case in force, for the Admin Exceptions halts (MVP-ADMIN-SCR-06). Reads
 * only and never locks; the caller checks the staff permission.
 *
 * A case is in force once it took effect and until it expires, if it ever does. Each one names
 * the Investor it holds (their account name, or null when the Party has no account), why it was
 * recorded and the actions it covers.
 *
 * @phpstan-type Hold array{
 *     id: string, party_id: string, name: string|null, cause: string, scope: list<string>, effective_at: string, expires_at: string|null
 * }
 */
interface AccountHolds
{
    /**
     * The cases in force at the given instant, oldest first.
     *
     * @return list<Hold>
     */
    public function inForce(DateTimeInterface $at): array;
}
