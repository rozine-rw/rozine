<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type ConflictPage from AuditAssignmentStore */
final class ListOwnAuditConflicts
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return ConflictPage */
    public function handle(int $userId, int $contextRevision, ?string $before = null, int $limit = 25): array
    {
        return $this->store->ownConflicts($userId, $contextRevision, $before, $limit);
    }
}
