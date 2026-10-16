<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\ActiveRolePolicy;
use App\Domain\Identity\RoleAccess;

/** @phpstan-import-type AccessSnapshot from ActiveRolePolicy */
final class GetIdentityContext
{
    public function __construct(private IdentityRepository $identities, private RoleAccess $roles, private ActiveRolePolicy $activeRoles) {}

    /** @return array<string, mixed> */
    public function handle(int $userId): array
    {
        return $this->fromSnapshot($this->identities->forUser($userId));
    }

    /**
     * @param  AccessSnapshot  $identity
     * @return array<string, mixed>
     */
    public function fromSnapshot(array $identity): array
    {
        $party = $identity['party'];
        $access = $this->roles->evaluate(
            $identity['email_verified'], $party['kind'] ?? null, $party['verified'] ?? false, $identity['memberships'],
        );
        $activeRole = $this->activeRoles->activeRole($identity);
        $selectable = array_filter($access['available_roles'], fn (string $role): bool => $role !== 'auditor' || $identity['mfa_confirmed']);
        $actions = $selectable === [] ? [] : ['identity.select_role'];
        if ($activeRole !== null) {
            $actions[] = 'identity.view_role';
        }

        return [
            'contract_version' => 'identity-v2',
            'policy_version' => ActiveRolePolicy::POLICY_VERSION,
            'code' => $access['code'],
            'party' => $party === null ? null : [
                'id' => $party['id'], 'kind' => $party['kind'],
                'verification_status' => $party['verified'] ? 'verified' : 'unverified',
            ],
            'available_roles' => $access['available_roles'],
            'active_role' => $activeRole,
            'context_revision' => $identity['context_revision'],
            'allowed_actions' => $actions,
        ];
    }
}
