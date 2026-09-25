<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use Closure;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditEngagementState
 *
 * @phpstan-type View array{id: string, business_id: string, revision: int, kind: string, status: string, offered_at: string|null, accept_by: string|null, complete_by: string|null, visit_by: string|null, allowed_actions: list<string>}
 * @phpstan-type ConflictReceipt array{conflict_id: string, kind: string, declared_at: string, note: string, blocking: true, status: 'reassignment_pending'|'reassigned'|'closed'}
 * @phpstan-type OwnConflict array{assignment_id: string, business_id: string, conflict: ConflictReceipt}
 * @phpstan-type ConflictPage array{data: list<OwnConflict>, next_cursor: string|null}
 * @phpstan-type Work array{assignment: View, business: array{name: string, industry: string, district: string}, distance_upper_bound_m: int|null}
 * @phpstan-type WorkIdentifiers array{party_id: string, ids: list<string>, next_cursor: string|null}
 * @phpstan-type OperationsCase array{id: string, business_id: string, revision: int, kind: string, status: string, original_dispatch_at: string, complete_by: string|null, attempt: int, operations_reason: string|null, closed_at: string|null, allowed_actions: list<string>}
 * @phpstan-type Assignment array{id: string, business_id: string, revision: int, state: State}
 * @phpstan-type AcceptedAssignment array{id: string, business_id: string, party_id: string, revision: int, kind: string, business_revision: int, mandate_version: int, mandate_sha256: string, independence: array{id: string, revision: int, checked_at: string, evidence_reference: string, sha256: string}, accreditation: array{profile_revision: int, status: string, licence: string|null, expires_on: string|null, checked_at: string|null}}
 */
interface AuditAssignmentStore
{
    /** @return array<string, mixed> */
    public function request(int $actorId, string $businessId, string $kind, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function respond(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $decision, ?string $conflictKind, string $reason, string $requestId, ?string $reasonCode = null): array;

    /** @return View */
    public function get(int $userId, int $contextRevision, string $assignmentId): array;

    /** @return OwnConflict */
    public function ownConflict(int $userId, int $contextRevision, string $assignmentId): array;

    /** @return ConflictPage */
    public function ownConflicts(int $userId, int $contextRevision, ?string $before, int $limit): array;

    /** @return WorkIdentifiers */
    public function workIdentifiers(int $userId, int $contextRevision, ?string $before, int $limit): array;

    /**
     * Holds current offered/accepted assignment authority through a minimal case-summary read.
     * Original evidence still requires withAccepted.
     *
     * @template TResult
     *
     * @param  Closure(Work): TResult  $operation
     * @return TResult
     */
    public function withCurrent(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed;

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

    /**
     * Holds professional standing through the effect. The caller already holds the Business
     * and the globally ordered required/source-author Party locks; this grants no access alone.
     *
     * @template TResult
     *
     * @param  AcceptedAssignment  $assignment
     * @param  Closure(bool): TResult  $operation
     * @return TResult
     */
    public function withVerificationValidity(array $assignment, Closure $operation): mixed;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;

    /** Trusted scheduler only; no participant identity or supplied eligibility facts. */
    public function advanceDue(int $limit): int;

    /** @return array<string, mixed> */
    public function advance(int $actorId, string $assignmentId, int $expectedRevision, string $requestId): array;

    /** @return OperationsCase */
    public function operationsCase(int $actorId, string $assignmentId): array;

    /** @return array<string, mixed> */
    public function resolve(int $actorId, string $assignmentId, int $expectedRevision, string $decision, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function findResolutionOperation(int $actorId, string $command, string $requestId): array;
}
