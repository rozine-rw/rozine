<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;
use Closure;

/**
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type AuditBinding from BusinessApplicationStore
 */
final class WithAuditApplicationBinding
{
    public function __construct(private BusinessApplicationStore $store) {}

    /**
     * @template TResult
     *
     * @param  Closure(AcceptedAssignment, AuditBinding): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, int $contextRevision, string $assignmentId, string $applicationId, Closure $operation): mixed
    {
        return $this->store->withAuditBinding($userId, $contextRevision, $assignmentId, $applicationId, $operation);
    }
}
