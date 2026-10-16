<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class ConfigureStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    public function handle(int $userId, bool $enabled, string $reason, string $requestId, array $roles = []): array
    {
        return $this->access->configureStaff($userId, $enabled, $reason, $requestId, $roles);
    }
}
