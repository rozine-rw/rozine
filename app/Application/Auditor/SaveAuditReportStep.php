<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class SaveAuditReportStep
{
    public function __construct(private AuditReportStore $store) {}

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $step, array $fields, string $requestId): array
    {
        return $this->store->saveStep($userId, $contextRevision, $reportId, $expectedRevision, $step, $fields, $requestId);
    }
}
