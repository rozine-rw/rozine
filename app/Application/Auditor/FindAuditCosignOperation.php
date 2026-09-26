<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;

final class FindAuditCosignOperation
{
    public function __construct(private AuditReportPublicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $requestId, string $command = 'report.cosign'): array
    {
        return $this->store->findOperation($userId, $contextRevision, $requestId, $command);
    }
}
