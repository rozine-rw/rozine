<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

/** @phpstan-import-type Report from AuditReportStore */
final class GetAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @return Report */
    public function handle(int $userId, int $contextRevision, string $reportId): array
    {
        return $this->store->get($userId, $contextRevision, $reportId);
    }
}
