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

    /**
     * The caller holds current Business view authority. Return only the latest unamended
     * sealed report's navigation metadata; the destination rechecks publication authority.
     *
     * @return array{id: string, kind: string, status: string}|null
     */
    public function latestForBusiness(string $businessId): ?array;

    /** @return array<string, mixed> */
    public function get(int $userId, int $contextRevision, string $businessId, string $reportId): array;

    /** @return array<string, mixed> */
    public function cosign(int $userId, int $contextRevision, string $businessId, string $reportId, int $expectedRevision, int $reportRevision, int $mandateVersion, string $digest, bool $accepted, string $note, string $requestId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId, string $command = 'report.cosign'): array;

    /** @return array<string, mixed> */
    public function findReviewOperation(int $userId, ?int $contextRevision, string $role, string $command, string $requestId): array;

    /** @return array<string, mixed> */
    public function verify(string $reportId): array;

    /** The caller holds current report authority and the Business/report locks. */
    public function requireAmendable(string $reportId): void;

    /** @param array<string, mixed> $input
     * @param  list<array{filename: string, content: string}>  $files
     * @return array<string, mixed>
     */
    public function dispute(int $userId, string $businessId, string $reportId, array $input, array $files): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function uphold(int $userId, string $reportId, array $input): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function staffDecision(int $userId, string $assignmentId, string $reportId, string $command, array $input): array;

    /** @return array<string, mixed> */
    public function staffCase(int $userId, string $assignmentId, string $reportId): array;

    /** @return array{content: string, filename: string, mime_type: string} */
    public function proof(int $userId, ?int $contextRevision, string $role, string $scopeId, string $reportId, string $proofId): array;

    /** @return array{inspected: int, published: int, blocked: array<string, string>, next_cursor: string|null} */
    public function advanceDue(int $limit = 100, ?string $after = null): array;
}
