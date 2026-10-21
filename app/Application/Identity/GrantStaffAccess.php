<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

/**
 * Staff & Roles: a superadmin adds an operator by email with one role and a reason (ADM-R01/R02).
 * Only a dedicated account whose email is verified and whose two-factor sign-in is confirmed can be
 * added; nobody adds themselves, and an existing operator is suspended or reactivated instead.
 */
final class GrantStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $email, string $role, string $reason, string $requestId): array
    {
        return $this->access->grantStaff($actorId, $email, $role, $reason, $requestId);
    }
}
