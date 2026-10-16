<?php

declare(strict_types=1);

namespace App\Notifications\Business;

/** How underwriting decided a Business application (FR-404). */
enum ApplicationOutcome: string
{
    case Approved = 'approved';
    case Returned = 'returned';
    case Declined = 'declined';
}
