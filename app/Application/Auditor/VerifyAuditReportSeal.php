<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;

final class VerifyAuditReportSeal
{
    public function __construct(private AuditReportPublicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(string $reportId): array
    {
        return $this->store->verify($reportId);
    }
}
