<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class IngestAuditLedger
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $filename, string $content, ?string $replaces, string $requestId): array
    {
        return $this->store->ingestLedger($userId, $contextRevision, $reportId, $expectedRevision, $filename, $content, $replaces, $requestId);
    }
}
