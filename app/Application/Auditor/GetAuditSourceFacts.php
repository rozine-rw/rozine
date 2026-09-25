<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditSourceFactsStore;

/**
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type SourceFacts from AuditSourceFactsStore
 */
final class GetAuditSourceFacts
{
    public function __construct(private AuditSourceFactsStore $store) {}

    /**
     * Null means unavailable: missing, withdrawn, another procedure or fixtures disabled.
     *
     * @return SourceFacts|null
     */
    public function handle(int $userId, int $contextRevision, string $assignmentId): ?array
    {
        return $this->store->forAssignment($userId, $contextRevision, $assignmentId);
    }

    /**
     * For composition inside WithAcceptedAuditAssignment; grants no access alone.
     *
     * @param  AcceptedAssignment  $assignment
     * @return SourceFacts|null
     */
    public function forAccepted(array $assignment): ?array
    {
        return $this->store->forAccepted($assignment);
    }
}
