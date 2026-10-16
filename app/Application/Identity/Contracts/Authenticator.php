<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface Authenticator
{
    public function verify(int $userId, string $code): string;

    public function binding(int $userId): string;
}
