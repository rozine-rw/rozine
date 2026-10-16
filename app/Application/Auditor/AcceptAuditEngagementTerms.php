<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;

final class AcceptAuditEngagementTerms
{
    public function __construct(private AuditEngagementStore $engagements) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $releaseId, int $expectedRevision, string $sha256, bool $accepted, string $requestId): array
    {
        return $this->engagements->accept($userId, $contextRevision, $releaseId, $expectedRevision, $sha256, $accepted, $requestId);
    }
}
