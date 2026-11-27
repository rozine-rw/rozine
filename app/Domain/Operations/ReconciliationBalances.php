<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use Brick\Math\BigInteger;

/** Exact clearing-account coverage; a missing account is never evidence of a balanced close. */
final class ReconciliationBalances
{
    /** @param list<array{id: string, ledger: string, statement: string}> $accounts */
    public static function complete(array $accounts): bool
    {
        $ids = array_column($accounts, 'id');
        sort($ids);

        return $ids === ['deposits', 'withdrawals'];
    }

    /** @param list<array{id: string, ledger: string, statement: string}> $accounts */
    public static function balanced(array $accounts): bool
    {
        return self::complete($accounts) && array_all($accounts,
            fn (array $account): bool => BigInteger::of($account['ledger'])->isEqualTo($account['statement']));
    }
}
