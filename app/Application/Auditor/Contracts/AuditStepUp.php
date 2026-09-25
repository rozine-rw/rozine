<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

interface AuditStepUp
{
    /** @return array{proof: string, expires_at: string} */
    public function issue(int $userId, string $partyId, int $contextRevision, string $reportId, int $reportRevision, string $digest, string $code): array;

    public function consume(int $userId, string $partyId, int $contextRevision, string $reportId, int $reportRevision, string $digest, string $proof): void;
}
