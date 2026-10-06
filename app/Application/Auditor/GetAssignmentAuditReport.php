<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

/** @phpstan-import-type Report from AuditReportStore */
final class GetAssignmentAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @return Report|null */
    public function handle(int $userId, int $contextRevision, string $assignmentId): ?array
    {
        return $this->store->forAssignment($userId, $contextRevision, $assignmentId);
    }
}
