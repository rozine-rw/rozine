<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/** @phpstan-import-type Assignment from AuditAssignmentStore */
final class FindAuditAssignmentOperation
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        return $this->store->findOperation($userId, $contextRevision, $command, $requestId);
    }
}
