<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\Authenticator;

final class VerifyAuthenticator
{
    public function __construct(private Authenticator $authenticator) {}

    public function handle(int $userId, string $code): string
    {
        return $this->authenticator->verify($userId, $code);
    }

    public function binding(int $userId): string
    {
        return $this->authenticator->binding($userId);
    }
}
