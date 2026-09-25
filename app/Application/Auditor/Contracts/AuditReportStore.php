<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-type Report array{id: string, assignment_id: string, business_id: string, application_id: string, application_revision: int, revision: int, kind: string, status: string, step: string, amends_id: string|null, binding_sha256: string, draft: array<string, mixed>, version: array{id: string, sha256: string}}
 */
interface AuditReportStore
{
    /** @return array<string, mixed> */
    public function start(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $applicationId, int $applicationRevision, string $requestId): array;

    /** @return Report */
    public function get(int $userId, int $contextRevision, string $reportId): array;

    /** @return Report|null */
    public function forAssignment(int $userId, int $contextRevision, string $assignmentId): ?array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;
}
