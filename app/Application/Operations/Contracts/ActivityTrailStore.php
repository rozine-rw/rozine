<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * The Admin console's Activity & Audit trail, read from the platform's two append-only journals:
 * the identity audit log (access, verification, membership and role changes, each with its reason
 * and before/after values) and the command journal (every governed command a participant or staff
 * member ran, with its recorded outcome). Navigation bookmarks are left out: they record where a
 * person was, not an action. Reads only and never locks; the caller checks the staff permission.
 *
 * `actor_kind` is how the action arrived: `console` for the server console, `staff` for a staff
 * account, `participant` for everyone else. `target_name` is the person a user or membership target
 * names, when there is one.
 *
 * @phpstan-type TrailEntry array{
 *     id: string, at: string, journal: 'identity'|'operations', actor: string|null, actor_kind: 'console'|'staff'|'participant',
 *     code: string, target_type: string, target_id: string, target_name: string|null, reason: string|null,
 *     before: array<string, mixed>|null, after: array<string, mixed>|null, outcome: array{status: string, code: string}|null
 * }
 * @phpstan-type Trail array{entries: list<TrailEntry>, total: int}
 */
interface ActivityTrailStore
{
    /**
     * The newest entries first, matching a search over the actor's name and email, the action, and
     * the target, recorded from `$from` (inclusive) to `$to` (exclusive) when given.
     *
     * @return Trail
     */
    public function trail(string $search, ?string $from, ?string $to, int $limit): array;
}
