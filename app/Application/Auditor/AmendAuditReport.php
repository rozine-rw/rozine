<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class AmendAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $requestId): array
    {
        return $this->store->amend($userId, $contextRevision, $reportId, $expectedRevision, $requestId);
    }
}
