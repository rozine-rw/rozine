<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use DateTimeInterface;

/**
 * Persistent, append-only cutoff evidence. Recording and notification are effects, never GET work.
 *
 * @phpstan-import-type Account from ReconciliationStore
 *
 * @phpstan-type Variance array{id: string, account_id: 'deposits'|'withdrawals', business_date: string,
 *     cutoff_at: string, ledger: string, statement: string, difference: string, created_at: string}
 */
interface ReconciliationVariances
{
    /** @param list<Account> $accounts
     * @return int Number of newly recorded cases; a replay records none.
     */
    public function record(string $businessDate, DateTimeInterface $cutoff, array $accounts): int;
}
