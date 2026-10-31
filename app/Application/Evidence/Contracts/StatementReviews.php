<?php

declare(strict_types=1);

namespace App\Application\Evidence\Contracts;

/**
 * Lets the engagement store ask whether a statement review's work is done without reading statement
 * evidence records itself.
 *
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 *
 * @phpstan-type LatestVerification array{assignment_id: string, actor_party_id: string, assignment: AcceptedAssignment, current: bool}
 */
interface StatementReviews
{
    /**
     * The Business's newest statement verification, with whether it still matches the current evidence
     * revision and the current review policy, procedure and classification versions.
     *
     * @return LatestVerification|null
     */
    public function latestVerification(string $businessId): ?array;
}
