<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 */
interface IdentityRepository
{
    public function register(string $name, string $email, string $password): int;

    /** @return AccessSnapshot */
    public function forUser(int $userId): array;
}
