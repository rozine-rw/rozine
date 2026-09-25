<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;
use App\Domain\Operations\OperationResult;

final class IngestAuditStatement
{
    public function __construct(private StatementStore $store) {}

    public function handle(int $userId, int $contextRevision, string $assignmentId, string $filename, string $content, ?string $replaces): OperationResult
    {
        return $this->store->ingestAudit($userId, $contextRevision, $assignmentId, $filename, $content, $replaces);
    }
}
