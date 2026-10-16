<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Verification from StatementStore */
final class GetAuditStatementVerification
{
    public function __construct(private StatementStore $store) {}

    /** @return Verification|null */
    public function handle(int $userId, int $contextRevision, string $assignmentId, ?string $verificationId = null): ?array
    {
        return $this->store->auditVerification($userId, $contextRevision, $assignmentId, $verificationId);
    }
}
