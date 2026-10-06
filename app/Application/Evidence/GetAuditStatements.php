<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type AuditFile from StatementStore */
final class GetAuditStatements
{
    public function __construct(private StatementStore $store) {}

    /** @return AuditFile */
    public function handle(int $userId, int $contextRevision, string $assignmentId): array
    {
        return $this->store->audit($userId, $contextRevision, $assignmentId);
    }
}
