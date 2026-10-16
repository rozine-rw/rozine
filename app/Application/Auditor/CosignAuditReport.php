<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;

final class CosignAuditReport
{
    public function __construct(private AuditReportPublicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $reportId, int $expectedRevision, int $reportRevision, int $mandateVersion, string $digest, bool $accepted, string $note, string $requestId): array
    {
        return $this->store->cosign($userId, $contextRevision, $businessId, $reportId, $expectedRevision, $reportRevision, $mandateVersion, $digest, $accepted, $note, $requestId);
    }
}
