<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * Proposed policy changes and the founders' decisions on them. Proposing needs `policies.change`;
 * deciding needs `policies.approve`, once per founder per change. Both are journalled commands with a
 * written reason, and a refusal comes back as a rejected result.
 *
 * @phpstan-type Decision array{by: string, decision: 'approve'|'reject', reason: string, at: string}
 * @phpstan-type Change array{id: string, key: string, from_value: int, to_value: int, status: string, reason: string,
 *     proposed_by: string, proposed_at: string, decided_at: string|null, decisions: list<Decision>, viewer_decided: bool}
 * @phpstan-type Setting array{key: string, unit: string, value: int, min: int, max: int, revision: int, updated_at: string, pending: Change|null}
 * @phpstan-type Board array{settings: list<Setting>, history: list<Change>, approvals_needed: int, can_propose: bool, can_approve: bool}
 */
interface PolicyChangeStore
{
    /** @return Board */
    public function board(int $actorId): array;

    /** @return array<string, mixed> */
    public function propose(int $actorId, string $key, string $value, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function decide(int $actorId, string $changeId, string $decision, string $reason, string $requestId): array;
}
