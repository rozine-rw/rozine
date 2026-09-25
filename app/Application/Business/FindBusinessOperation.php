<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class FindBusinessOperation
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        return $this->store->findOperation($userId, $contextRevision, $command, $requestId);
    }
}
