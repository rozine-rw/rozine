<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Business's ticks on its visit-ready checklist (BRS v3 VIS-R03). A tick is the Business's own
 * readiness note, saved under its `business.view` authority once per request id; it changes no
 * financial or audit fact. The D−11 reminder lists only the items not ticked.
 */
interface VisitReadyTicks
{
    /**
     * The items currently ticked for the instalment due on `$dueOn`, in no particular order.
     *
     * @return list<string>
     */
    public function ticked(string $businessId, string $dueOn): array;

    /** @return array<string, mixed> */
    public function tick(int $userId, int $contextRevision, string $businessId, string $requestId, string $dueOn, string $item, bool $ticked): array;
}
