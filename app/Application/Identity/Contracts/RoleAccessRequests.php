<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * Requests for Business or Auditor access sent from the launcher (#616). A verified person sends at
 * most one per role, journaled under their Party; staff read them in the console and act with the
 * tools that grant the role. The sent request never changes; whether it is still open is read from the
 * Party's membership of that role, so it leaves the open queue as soon as staff create one.
 *
 * @phpstan-type Details array{organization: string, reference: string|null, phone: string|null, note: string|null}
 * @phpstan-type StaffRow array{id: string, role: 'business'|'auditor', name: string, email: string, organization: string,
 *     reference: string|null, phone: string|null, note: string|null, sent_at: string,
 *     outcome: 'pending'|'active'|'suspended'|'revoked'|null}
 */
interface RoleAccessRequests
{
    /**
     * When the person's Party sent each request, by role.
     *
     * @return array<'business'|'auditor', string>
     */
    public function sent(int $userId): array;

    /**
     * @param  'business'|'auditor'  $role
     * @param  Details  $details
     * @return array<string, mixed>
     */
    public function request(int $userId, string $requestId, string $role, array $details): array;

    /**
     * The requests for the roles the staff member may act on: open ones (no membership of that role
     * yet) first, then handled ones with the membership's status as their outcome, each newest first.
     *
     * @param  list<'business'|'auditor'>  $roles
     * @return list<StaffRow>
     */
    public function forStaff(array $roles): array;
}
