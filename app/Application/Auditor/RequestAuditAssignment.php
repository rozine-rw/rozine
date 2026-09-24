<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type Assignment from AuditAssignmentStore */
final class RequestAuditAssignment
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $businessId, string $kind, string $reason, string $requestId): array
    {
        return $this->store->request($actorId, $businessId, $kind, $reason, $requestId);
    }
}
