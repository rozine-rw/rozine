<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\RoleAccess;

final class GetIdentityContext
{
    public function __construct(private IdentityRepository $identities, private RoleAccess $roles) {}

    /**
     * @return array{contract_version: string, policy_version: string, code: string, party: array{id: string, kind: string, verification_status: string}|null, available_roles: list<string>, allowed_actions: list<string>}
     */
    public function handle(int $userId): array
    {
        $identity = $this->identities->forUser($userId);
        $party = $identity['party'];
        $access = $this->roles->evaluate(
            $identity['email_verified'],
            $party['kind'] ?? null,
            $party['verified'] ?? false,
            $identity['memberships'],
        );

        return [
            'contract_version' => 'identity-v1',
            'policy_version' => 'engineering-2026-09-23.4',
            'code' => $access['code'],
            'party' => $party === null ? null : [
                'id' => $party['id'],
                'kind' => $party['kind'],
                'verification_status' => $party['verified'] ? 'verified' : 'unverified',
            ],
            'available_roles' => $access['available_roles'],
            'allowed_actions' => [],
        ];
    }
}
