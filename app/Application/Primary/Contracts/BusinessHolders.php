<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/** Who holds a Business's notes: the Parties with an issued Holding in any of its campaigns. Read-only. */
interface BusinessHolders
{
    /** @return list<string> Party ids, ascending */
    public function parties(string $businessId): array;
}
