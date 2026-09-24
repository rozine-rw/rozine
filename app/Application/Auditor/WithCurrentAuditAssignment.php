<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use Closure;

/** @phpstan-import-type Work from AuditAssignmentStore */
final class WithCurrentAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /**
     * @template TResult
     *
     * @param  Closure(Work): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed
    {
        return $this->store->withCurrent($userId, $contextRevision, $assignmentId, $operation);
    }
}
