<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class GetBusinessApplicationQuote
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return array<string, mixed>|null */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId): ?array
    {
        return $this->store->quote($userId, $contextRevision, $businessId, $applicationId);
    }
}
