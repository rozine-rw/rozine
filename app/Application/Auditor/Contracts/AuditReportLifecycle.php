<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

interface AuditReportLifecycle
{
    /** The assignment adapter holds Business, actor and assignment locks through this effect. */
    public function withdrawForConflict(string $assignmentId, string $partyId, int $actorId, string $conflictId): void;

    /** Audit Operations withdrew the assignment (#489); the adapter holds the same locks. */
    public function withdrawForOperations(string $assignmentId, string $partyId, int $actorId): void;
}
