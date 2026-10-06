<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;

final class GetBusinessAuditReport
{
    public function __construct(private AuditReportPublicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $reportId): array
    {
        return $this->store->get($userId, $contextRevision, $businessId, $reportId);
    }
}
