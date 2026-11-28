<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use App\Domain\Operations\StatementBalance;
use DateTimeInterface;

/** An independent authoritative statement feed, never a sum derived from ledger/provider outcome rows. */
interface AuthoritativeClearingStatements
{
    /**
     * Missing coverage returns fewer rows, never fabricated zero balances.
     *
     * @return list<StatementBalance>
     */
    public function at(DateTimeInterface $asOf): array;
}
