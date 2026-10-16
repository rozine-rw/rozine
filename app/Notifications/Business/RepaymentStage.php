<?php

declare(strict_types=1);

namespace App\Notifications\Business;

/** Where a repayment stands against its due date when the Business is warned (FR-208). */
enum RepaymentStage: string
{
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';
}
