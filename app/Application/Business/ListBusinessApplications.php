<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class ListBusinessApplications
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, ?string $before = null, int $limit = 20): array
    {
        return $this->store->index($userId, $contextRevision, $before, $limit);
    }
}
