<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type OperationsCase from AuditAssignmentStore */
final class FindAuditResolutionOperation
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $command, string $requestId): array
    {
        return $this->store->findResolutionOperation($actorId, $command, $requestId);
    }
}
