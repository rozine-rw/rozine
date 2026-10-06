<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class ConfigureIdentityOperator
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, bool $enabled, string $reason, string $requestId): array
    {
        return $this->access->configureOperator($userId, $enabled, $reason, $requestId);
    }
}
