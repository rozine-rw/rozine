<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class WithdrawAuditorAccreditation
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, string $submissionId, string $requestId): array
    {
        return $this->store->withdraw($userId, $contextRevision, $expectedRevision, $submissionId, $requestId);
    }
}
