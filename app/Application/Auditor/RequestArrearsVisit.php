<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/**
 * Sends the Business's CPA on the late visit when an instalment reaches day 7 of arrears (BRS v3
 * AUD-R08, COL-R04). Servicing calls it once per overdue instalment; a repeated call for the same
 * instalment, or one while an engagement is still open, sends nothing and returns null.
 */
final class RequestArrearsVisit
{
    public function __construct(private AuditAssignmentStore $store) {}

    public function handle(string $businessId, string $instalmentId): ?string
    {
        return $this->store->requestArrears($businessId, $instalmentId);
    }
}
