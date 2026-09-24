<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type Assignment from AuditAssignmentStore */
final class RespondToAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $decision, ?string $conflictKind, string $reason, string $requestId): array
    {
        return $this->store->respond($userId, $contextRevision, $assignmentId, $expectedRevision, $decision, $conflictKind, $reason, $requestId);
    }
}
