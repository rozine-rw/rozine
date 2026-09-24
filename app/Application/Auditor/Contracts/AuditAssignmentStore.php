<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use Closure;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditEngagementState
 *
 * @phpstan-type View array{id: string, business_id: string, revision: int, kind: string, status: string, offered_at: string|null, accept_by: string|null, complete_by: string|null, visit_by: string|null, allowed_actions: list<string>}
 * @phpstan-type Assignment array{id: string, business_id: string, revision: int, state: State}
 * @phpstan-type AcceptedAssignment array{id: string, business_id: string, party_id: string, revision: int, kind: string, business_revision: int, mandate_version: int, accreditation: array{profile_revision: int, licence: string|null, expires_on: string|null, checked_at: string|null}}
 */
interface AuditAssignmentStore
{
    /** @return array<string, mixed> */
    public function request(int $actorId, string $businessId, string $kind, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function respond(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $decision, ?string $conflictKind, string $reason, string $requestId): array;

    /** @return View */
    public function get(int $userId, int $contextRevision, string $assignmentId): array;

    /**
     * Hold current accepted-assignment authority through the protected operation.
     *
     * @template TResult
     *
     * @param  Closure(AcceptedAssignment): TResult  $operation
     * @return TResult
     */
    public function withAccepted(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed;

    /**
     * Source validity only; does not grant access. The caller holds current Business authority.
     *
     * @param  AcceptedAssignment  $assignment
     */
    public function retainsVerification(array $assignment): bool;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;

    /** Trusted scheduler only; no participant identity or supplied eligibility facts. */
    public function advanceDue(int $limit): int;

    /** @return array<string, mixed> */
    public function advance(int $actorId, string $assignmentId, int $expectedRevision, string $requestId): array;
}
