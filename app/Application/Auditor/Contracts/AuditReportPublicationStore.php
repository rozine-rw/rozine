<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

interface AuditReportPublicationStore
{
    /** @param array<string, mixed> $payload */
    public function open(string $reportId, string $digest, array $payload): void;

    /** The caller already holds this report's current Auditor authority.
     * @return array<string, mixed>
     */
    public function forAuditor(string $reportId): array;

    /** @return array<string, mixed> */
    public function get(int $userId, int $contextRevision, string $businessId, string $reportId): array;

    /** @return array<string, mixed> */
    public function cosign(int $userId, int $contextRevision, string $businessId, string $reportId, int $expectedRevision, int $reportRevision, int $mandateVersion, string $digest, bool $accepted, string $note, string $requestId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array;

    /** @return array<string, mixed> */
    public function verify(string $reportId): array;
}
