<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;

final class ConfirmAuditStepUp
{
    public function __construct(private AuditReportStore $store) {}

    /** @return array{proof: string, expires_at: string} */
    public function handle(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $digest, string $code): array
    {
        return $this->store->stepUp($userId, $contextRevision, $reportId, $expectedRevision, $digest, $code);
    }
}
