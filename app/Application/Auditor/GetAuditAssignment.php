<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type View from AuditAssignmentStore */
final class GetAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return View */
    public function handle(int $userId, int $contextRevision, string $assignmentId): array
    {
        return $this->store->get($userId, $contextRevision, $assignmentId);
    }
}
