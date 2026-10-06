<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\ActiveRolePolicy;

final class ChangeMembership
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $partyId, string $role, string $status, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array
    {
        $result = $this->access->changeMembership($actorId, $partyId, $role, $status, $expectedRevision, $evidenceReference, $reason, $requestId);

        return ['contract_version' => 'identity-management-v1', 'policy_version' => ActiveRolePolicy::POLICY_VERSION, ...$result];
    }
}
