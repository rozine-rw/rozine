<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;

final class ReviewAuditPublication
{
    public function __construct(private AuditReportPublicationStore $store) {}

    /** @param array<string, mixed> $input
     * @param  list<array{filename: string, content: string}>  $files
     * @return array<string, mixed>
     */
    public function dispute(int $userId, string $businessId, string $reportId, array $input, array $files): array
    {
        return $this->store->dispute($userId, $businessId, $reportId, $input, $files);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function uphold(int $userId, string $reportId, array $input): array
    {
        return $this->store->uphold($userId, $reportId, $input);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function resolve(int $userId, string $assignmentId, string $reportId, string $command, array $input): array
    {
        return $this->store->staffDecision($userId, $assignmentId, $reportId, $command, $input);
    }

    /** @return array<string, mixed> */
    public function staffCase(int $userId, string $assignmentId, string $reportId): array
    {
        return $this->store->staffCase($userId, $assignmentId, $reportId);
    }

    /** @return array{content: string, filename: string, mime_type: string} */
    public function proof(int $userId, ?int $contextRevision, string $role, string $scopeId, string $reportId, string $proofId): array
    {
        return $this->store->proof($userId, $contextRevision, $role, $scopeId, $reportId, $proofId);
    }

    /** @return array<string, mixed> */
    public function operation(int $userId, ?int $contextRevision, string $role, string $command, string $requestId): array
    {
        return $this->store->findReviewOperation($userId, $contextRevision, $role, $command, $requestId);
    }

    /** @return array{inspected: int, published: int, blocked: array<string, string>, next_cursor: string|null} */
    public function advanceDue(int $limit, ?string $after = null): array
    {
        return $this->store->advanceDue($limit, $after);
    }
}
