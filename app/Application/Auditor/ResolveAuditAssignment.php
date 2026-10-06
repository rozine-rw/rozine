<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type OperationsCase from AuditAssignmentStore */
final class ResolveAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $assignmentId, int $expectedRevision, string $decision, string $reason, string $requestId): array
    {
        return $this->store->resolve($actorId, $assignmentId, $expectedRevision, $decision, $reason, $requestId);
    }
}
