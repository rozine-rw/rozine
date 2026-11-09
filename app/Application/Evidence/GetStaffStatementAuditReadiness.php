<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Readiness from StatementStore */
final class GetStaffStatementAuditReadiness
{
    public function __construct(private StatementStore $store) {}

    /** @return Readiness */
    public function handle(int $actorId, string $businessId): array
    {
        return $this->store->staffReadiness($actorId, $businessId);
    }
}
