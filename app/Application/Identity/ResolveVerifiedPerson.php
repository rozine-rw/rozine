<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\ActiveRolePolicy;

final class ResolveVerifiedPerson
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId): array
    {
        $result = $this->access->resolvePerson($actorId, $userId, $identityReference, $evidenceReference, $reason, $requestId);

        return ['contract_version' => 'identity-management-v1', 'policy_version' => ActiveRolePolicy::POLICY_VERSION, ...$result];
    }
}
