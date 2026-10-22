<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use DateTimeImmutable;

interface MonthlyAuditBusinesses
{
    /**
     * Businesses owed a monthly audit for the Kigali month before `$now`: an issued note that
     * existed during that month and still has an instalment due from the current month on.
     *
     * @return list<string>
     */
    public function due(DateTimeImmutable $now): array;
}
