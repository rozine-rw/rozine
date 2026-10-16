<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;

/** @phpstan-import-type OperationsCase from AuditAssignmentStore */
final class GetAuditOperationsCase
{
    public function __construct(private AuditAssignmentStore $store, private AuditReportPublicationStore $publications) {}

    /** @return OperationsCase */
    public function handle(int $actorId, string $assignmentId): array
    {
        $case = $this->store->operationsCase($actorId, $assignmentId);
        $case['audit_reviews'] = array_map(function (array $review) use ($actorId, $assignmentId): array {
            $current = $this->publications->staffCase($actorId, $assignmentId, $review['report_id']);

            return [...$review, 'status' => $current['status'], 'due_at' => $current['due_at'],
                'publication_available' => $current['publication_available'], 'unavailable_reason' => $current['unavailable_reason']];
        }, $case['audit_reviews']);

        return $case;
    }
}
