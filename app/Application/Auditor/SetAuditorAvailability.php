<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class SetAuditorAvailability
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, bool $accepting, string $requestId): array
    {
        return $this->store->availability($userId, $contextRevision, $expectedRevision, $accepting, $requestId);
    }
}
