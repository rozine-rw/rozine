<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type OwnConflict from AuditAssignmentStore */
final class GetOwnAuditConflict
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return OwnConflict */
    public function handle(int $userId, int $contextRevision, string $assignmentId): array
    {
        return $this->store->ownConflict($userId, $contextRevision, $assignmentId);
    }
}
