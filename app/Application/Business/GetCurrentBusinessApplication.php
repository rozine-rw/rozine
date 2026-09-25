<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

/** @phpstan-import-type Application from BusinessApplicationStore */
final class GetCurrentBusinessApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return Application|null */
    public function handle(int $userId, int $contextRevision, string $businessId): ?array
    {
        return $this->store->current($userId, $contextRevision, $businessId);
    }
}
