<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class ResolveVerifiedOrganization
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $registryReference, string $evidenceReference, string $reason, string $requestId): array
    {
        return ['contract_version' => 'identity-organization-v1', ...$this->access->resolveOrganization($actorId, $registryReference, $evidenceReference, $reason, $requestId)];
    }
}
