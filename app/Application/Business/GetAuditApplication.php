<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

/** @phpstan-import-type AuditApplication from BusinessApplicationStore */
final class GetAuditApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return AuditApplication */
    public function handle(int $userId, int $contextRevision, string $assignmentId, ?string $applicationId = null): array
    {
        return $this->store->audit($userId, $contextRevision, $assignmentId, $applicationId);
    }
}
