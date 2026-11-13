<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The instalments of every issued Note that fall due in a range of Kigali calendar days, as each
 * Note's schedule was issued (no servicing domain is bound yet, so none is known to be paid).
 */
interface UpcomingInstalments
{
    /**
     * Ordered by due date, then Business.
     *
     * @return list<array{business_id: string, due_on: string}>
     */
    public function between(string $from, string $to): array;
}
