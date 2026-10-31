<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;

final class GetStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, bool $required = false): array
    {
        return $this->access->staffAccess($userId, $required);
    }

    /** @return array<string, mixed> */
    public function requiring(int $userId, string $permission): array
    {
        $staff = $this->handle($userId, true);
        if (! in_array($permission, $staff['allowed_actions'], true)) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }

        return $staff;
    }
}
