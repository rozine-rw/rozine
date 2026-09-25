<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use Closure;

/** @phpstan-import-type AcceptedAssignment from AuditAssignmentStore */
final class WithAcceptedAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /**
     * @template TResult
     *
     * @param  Closure(AcceptedAssignment): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed
    {
        return $this->store->withAccepted($userId, $contextRevision, $assignmentId, $operation);
    }

    /** @param AcceptedAssignment $assignment */
    public function retainsVerification(array $assignment): bool
    {
        return $this->store->retainsVerification($assignment);
    }

    /**
     * @template TResult
     *
     * @param  AcceptedAssignment  $assignment
     * @param  Closure(bool): TResult  $operation
     * @return TResult
     */
    public function withVerificationValidity(array $assignment, Closure $operation): mixed
    {
        return $this->store->withVerificationValidity($assignment, $operation);
    }
}
