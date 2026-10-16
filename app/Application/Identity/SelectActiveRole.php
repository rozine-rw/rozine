<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class SelectActiveRole
{
    public function __construct(private IdentityAccessStore $access, private GetIdentityContext $identity) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, string $role, int $expectedRevision, string $requestId): array
    {
        $this->access->selectRole($userId, $role, $expectedRevision, $requestId);

        return $this->identity->handle($userId);
    }
}
