<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class DecideAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, bool $reject, mixed $reasonCode, mixed $reason, string $requestId): array
    {
        return $this->store->decide($userId, $contextRevision, $reportId, $expectedRevision, $reject, $reasonCode, $reason, $requestId);
    }
}
