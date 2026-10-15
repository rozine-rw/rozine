<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;

/**
 * The Investor's own complaints with their conversations, newest first, for the Help center.
 *
 * @phpstan-import-type Thread from Complaints
 */
final readonly class ListInvestorComplaints
{
    public function __construct(private Complaints $complaints) {}

    /** @return list<Thread> */
    public function handle(int $userId): array
    {
        return $this->complaints->forInvestor($userId);
    }
}
