<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * @phpstan-type AccessSnapshot array{email_verified: bool, mfa_confirmed: bool, party: array{id: string, kind: string, verified: bool}|null, memberships: list<array{id: string, role: string, status: string, revision: int}>, active_membership_id: string|null, active_membership_revision: int|null, context_revision: int}
 */
final class ActiveRolePolicy
{
    public const string POLICY_VERSION = 'engineering-2026-09-23.4';

    public function __construct(private RoleAccess $roles) {}

    /**
     * @param  AccessSnapshot  $identity
     * @return array{id: string, role: string, status: string, revision: int}
     */
    public function selectable(array $identity, string $role): array
    {
        $party = $identity['party'];
        $access = $this->roles->evaluate(
            $identity['email_verified'], $party['kind'] ?? null, $party['verified'] ?? false,
            $identity['memberships'],
        );

        if ($access['code'] !== 'IDENTITY_READY') {
            throw new IdentityViolation($access['code']);
        }

        if (! in_array($role, $access['available_roles'], true)) {
            throw new IdentityViolation('ROLE_NOT_AVAILABLE');
        }

        if ($role === 'auditor' && ! $identity['mfa_confirmed']) {
            throw new IdentityViolation('MFA_REQUIRED');
        }

        return array_values(array_filter($identity['memberships'], fn (array $membership): bool => $membership['role'] === $role))[0];
    }

    /** @param AccessSnapshot $identity */
    public function activeRole(array $identity): ?string
    {
        foreach ($identity['memberships'] as $membership) {
            if ($membership['id'] === $identity['active_membership_id']
                && $membership['revision'] === $identity['active_membership_revision']) {
                try {
                    return $this->selectable($identity, $membership['role'])['role'];
                } catch (IdentityViolation) {
                    return null;
                }
            }
        }

        return null;
    }

    /** @param AccessSnapshot $identity */
    public function authorize(array $identity, string $role, ?string $recordPartyId, ?int $expectedContext): void
    {
        if ($recordPartyId !== null && $recordPartyId !== ($identity['party']['id'] ?? null)) {
            throw new IdentityViolation('IDENTITY_RECORD_NOT_FOUND', 404);
        }

        if ($expectedContext !== null && $identity['context_revision'] !== $expectedContext) {
            throw new IdentityViolation('ACTIVE_ROLE_REVISION_CONFLICT', 409);
        }

        $this->selectable($identity, $role);

        if ($this->activeRole($identity) !== $role) {
            throw new IdentityViolation('ACTIVE_ROLE_REQUIRED');
        }
    }
}
