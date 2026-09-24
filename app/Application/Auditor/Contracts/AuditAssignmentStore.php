<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditEngagementState
 *
 * @phpstan-type View array{id: string, business_id: string, revision: int, kind: string, status: string, offered_at: string|null, accept_by: string|null, complete_by: string|null, visit_by: string|null, allowed_actions: list<string>}
 * @phpstan-type Assignment array{id: string, business_id: string, revision: int, state: State}
 */
interface AuditAssignmentStore
{
    /** @return array<string, mixed> */
    public function request(int $actorId, string $businessId, string $kind, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function respond(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $decision, ?string $conflictKind, string $reason, string $requestId): array;

    /** @return View */
    public function get(int $userId, int $contextRevision, string $assignmentId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;

    /** Trusted scheduler only; no participant identity or supplied eligibility facts. */
    public function advanceDue(int $limit): int;

    /** @return array<string, mixed> */
    public function advance(int $actorId, string $assignmentId, int $expectedRevision, string $requestId): array;
}
