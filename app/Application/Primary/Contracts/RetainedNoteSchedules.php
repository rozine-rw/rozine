<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Application\Primary\RetainedNoteSchedule;

/**
 * A Note's retained schedule as it was issued, read in a transaction the adapter owns, for a reader
 * that holds none. Null means the Note has not been issued. Integrity and source failures propagate.
 */
interface RetainedNoteSchedules
{
    public function find(string $campaignId): ?RetainedNoteSchedule;
}
