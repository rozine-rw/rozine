<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class StartAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $applicationId, int $applicationRevision, string $requestId): array
    {
        return $this->store->start($userId, $contextRevision, $assignmentId, $expectedRevision, $applicationId, $applicationRevision, $requestId);
    }
}
