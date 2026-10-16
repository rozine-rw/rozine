<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Complaints;

/**
 * A Business's complaints with their conversations, newest first, for its Profile's support center.
 * Everyone who may view the Business sees them.
 *
 * @phpstan-import-type Thread from Complaints
 */
final readonly class ListBusinessComplaints
{
    public function __construct(private Complaints $complaints) {}

    /** @return list<Thread> */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        return $this->complaints->forBusiness($userId, $contextRevision, $businessId);
    }
}
