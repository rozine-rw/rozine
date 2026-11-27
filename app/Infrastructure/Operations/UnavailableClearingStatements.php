<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\AuthoritativeClearingStatements;
use DateTimeInterface;

/** No authorized independent bank statement integration is configured. */
final class UnavailableClearingStatements implements AuthoritativeClearingStatements
{
    public function at(DateTimeInterface $asOf): array
    {
        return [];
    }
}
