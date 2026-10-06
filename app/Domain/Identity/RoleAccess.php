<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class RoleAccess
{
    /**
     * Role availability is not permission to transact or act for an entity.
     * Staff provisioning and entity mandates have separate authority paths.
     *
     * @param  list<array{role: string, status: string}>  $memberships
     * @return array{code: string, available_roles: list<string>}
     */
    public function evaluate(bool $emailVerified, ?string $partyKind, bool $partyVerified, array $memberships): array
    {
        if (! $emailVerified) {
            return ['code' => 'EMAIL_VERIFICATION_REQUIRED', 'available_roles' => []];
        }

        if ($partyKind === null) {
            return ['code' => 'IDENTITY_NOT_LINKED', 'available_roles' => []];
        }

        if (! $partyVerified) {
            return ['code' => 'IDENTITY_VERIFICATION_REQUIRED', 'available_roles' => []];
        }

        if ($partyKind !== 'person') {
            return ['code' => 'PARTY_AUTHORITY_REQUIRED', 'available_roles' => []];
        }

        $knownRoles = ['investor', 'business', 'auditor'];
        $retainedRoles = [];
        $activeRoles = [];

        foreach ($memberships as $membership) {
            if (! in_array($membership['role'], $knownRoles, true)
                || ! in_array($membership['status'], ['pending', 'active', 'suspended', 'revoked'], true)) {
                return ['code' => 'ROLE_MEMBERSHIP_INVALID', 'available_roles' => []];
            }

            if ($membership['status'] !== 'revoked') {
                $retainedRoles[] = $membership['role'];
            }

            if ($membership['status'] === 'active') {
                $activeRoles[] = $membership['role'];
            }
        }

        if (in_array('auditor', $retainedRoles, true) && count(array_unique($retainedRoles)) > 1) {
            return ['code' => 'ROLE_MEMBERSHIP_CONFLICT', 'available_roles' => []];
        }

        $availableRoles = array_values(array_intersect($knownRoles, $activeRoles));

        return [
            'code' => $availableRoles === [] ? 'ROLE_MEMBERSHIP_REQUIRED' : 'IDENTITY_READY',
            'available_roles' => $availableRoles,
        ];
    }
}
