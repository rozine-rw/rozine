<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class FindAuditorOperation
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        return $this->store->findOperation($userId, $contextRevision, $command, $requestId);
    }
}
