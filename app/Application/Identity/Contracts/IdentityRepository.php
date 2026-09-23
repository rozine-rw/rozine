<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * @phpstan-type IdentitySnapshot array{email_verified: bool, party: array{id: string, kind: string, verified: bool}|null, memberships: list<array{role: string, status: string}>}
 */
interface IdentityRepository
{
    public function register(string $name, string $email, string $password): int;

    /** @return IdentitySnapshot */
    public function forUser(int $userId): array;
}
