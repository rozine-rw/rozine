<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Review from \App\Domain\Evidence\StatementAuditReview */
final class RecordStatementVerification
{
    public function __construct(private StatementStore $store) {}

    /**
     * @param  Review  $review
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $assignmentId, int $expectedAssignmentRevision, int $expectedEvidenceRevision, int $expectedVerificationRevision, string $transcriptionId, string $transcriptionHash, array $review, string $requestId): array
    {
        return $this->store->verify($userId, $contextRevision, $assignmentId, $expectedAssignmentRevision, $expectedEvidenceRevision, $expectedVerificationRevision, $transcriptionId, $transcriptionHash, $review, $requestId);
    }
}
