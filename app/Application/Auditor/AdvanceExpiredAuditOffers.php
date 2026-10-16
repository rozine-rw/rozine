<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

final class AdvanceExpiredAuditOffers
{
    public function __construct(private AuditAssignmentStore $store) {}

    public function handle(int $limit): int
    {
        return $this->store->advanceDue($limit);
    }
}
