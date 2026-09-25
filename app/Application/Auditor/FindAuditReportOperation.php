<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class FindAuditReportOperation
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        return $this->store->findOperation($userId, $contextRevision, $command, $requestId);
    }
}
