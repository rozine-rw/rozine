<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;

final class FindAuditEngagementOperation
{
    public function __construct(private AuditEngagementStore $engagements) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->engagements->findOperation($userId, $contextRevision, $requestId);
    }
}
