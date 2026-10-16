<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityRepository;

final class RegisterIdentity
{
    public function __construct(private IdentityRepository $identities) {}

    public function handle(string $name, string $email, string $password): int
    {
        return $this->identities->register($name, $email, $password);
    }
}
