<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class GetStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, bool $required = false): array
    {
        return $this->access->staffAccess($userId, $required);
    }
}
