<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class SealAuditReport
{
    public function __construct(private AuditReportStore $store) {}

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, array $fields, string $proof, string $requestId): array
    {
        return $this->store->seal($userId, $contextRevision, $reportId, $expectedRevision, $fields, $proof, $requestId);
    }
}
