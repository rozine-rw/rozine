<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Businesses that have entered the platform (BRS v3.0 RDY-R03): each has at least one
 * published audit report, so it passed KYB and its entry statement audit.
 */
interface EnteredBusinesses
{
    /** @return list<string> */
    public function all(): array;
}
