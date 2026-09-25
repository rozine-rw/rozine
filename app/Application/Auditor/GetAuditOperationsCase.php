<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type OperationsCase from AuditAssignmentStore */
final class GetAuditOperationsCase
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return OperationsCase */
    public function handle(int $actorId, string $assignmentId): array
    {
        return $this->store->operationsCase($actorId, $assignmentId);
    }
}
