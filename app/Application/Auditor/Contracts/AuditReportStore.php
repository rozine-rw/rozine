<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-type Report array{id: string, assignment_id: string, business_id: string, application_id: string, application_revision: int, revision: int, kind: string, status: string, step: string, period: string|null, amends_id: string|null, amendment_id: string|null, binding_sha256: string, draft: array<string, mixed>, version: array{id: string, sha256: string}}
 *
 * @phpstan-import-type Projection from \App\Application\Auditor\GetAuditProcedureSources
 * @phpstan-import-type Original from \App\Application\Evidence\Contracts\StatementStore
 *
 * @phpstan-type Procedure array{report: Report, sources: Projection, mfa_confirmed: bool}
 */
interface AuditReportStore
{
    /** @return array<string, mixed> */
    public function decide(int $userId, int $contextRevision, string $reportId, int $expectedRevision, bool $reject, mixed $reasonCode, mixed $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function amend(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $requestId): array;

    /** @return array<string, mixed> */
    public function start(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $applicationId, int $applicationRevision, string $requestId): array;

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function saveStep(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $step, array $fields, string $requestId): array;

    /** @return array<string, mixed> */
    public function ingestLedger(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $filename, string $content, ?string $replaces, string $requestId): array;

    /** @return Original */
    public function readLedger(int $userId, int $contextRevision, string $reportId, string $documentId): array;

    /** @return Report */
    public function get(int $userId, int $contextRevision, string $reportId): array;

    /** @return Procedure */
    public function procedure(int $userId, int $contextRevision, string $reportId): array;

    /** @return Report|null */
    public function forAssignment(int $userId, int $contextRevision, string $assignmentId): ?array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;
}
