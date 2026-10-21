<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

/** Staff & Roles: a superadmin suspends or reactivates another operator, with a reason (ADM-R02). */
final class SetStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, int $userId, bool $enabled, string $reason, string $requestId): array
    {
        return $this->access->setStaffEnabled($actorId, $userId, $enabled, $reason, $requestId);
    }
}
