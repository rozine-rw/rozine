<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\ActiveRolePolicy;

/** An identity operator links a staff account to its verified person, or revokes that link (null reference). */
final class RecordStaffPerson
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, int $staffUserId, ?string $identityReference, string $evidenceReference, string $reason, string $requestId): array
    {
        $result = $this->access->recordStaffPerson($actorId, $staffUserId, $identityReference, $evidenceReference, $reason, $requestId);

        return ['contract_version' => 'identity-management-v1', 'policy_version' => ActiveRolePolicy::POLICY_VERSION, ...$result];
    }
}
