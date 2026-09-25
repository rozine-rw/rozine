<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type Assignment from AuditAssignmentStore */
final class AdvanceAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $assignmentId, int $expectedRevision, string $requestId): array
    {
        return $this->store->advance($actorId, $assignmentId, $expectedRevision, $requestId);
    }
}
