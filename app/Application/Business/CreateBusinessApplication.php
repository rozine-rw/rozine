<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class CreateBusinessApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array
    {
        return $this->store->create($userId, $contextRevision, $businessId, $expectedRevision, $requestId);
    }
}
