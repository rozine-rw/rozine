<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

/** @phpstan-import-type Original from \App\Application\Evidence\Contracts\StatementStore */
final class ReadAuditLedger
{
    public function __construct(private AuditReportStore $store) {}

    /** @return Original */
    public function handle(int $userId, int $contextRevision, string $reportId, string $documentId): array
    {
        return $this->store->readLedger($userId, $contextRevision, $reportId, $documentId);
    }
}
