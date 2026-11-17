<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Business's servicing track record across every issued note: the notes it has repaid in full,
 * with the exposure each one held, and how many of its instalments it has paid and how many of those
 * on or before their due date. A repaid note's principal is no longer owed, so its exposure no longer
 * counts against the Business's capacity, and a repeat raise grows to at most 1.5 times the last one
 * (BRS v3 REP-R05). Repaid notes are listed oldest first. Read only; it never locks.
 *
 * @phpstan-type RepaidNote array{campaign_id: string, exposure_reservation_id: string, principal: string}
 * @phpstan-type Record array{repaid: list<RepaidNote>, instalments_paid: int, paid_on_time: int}
 */
interface ServicingRecord
{
    /** @return Record */
    public function forBusiness(string $businessId): array;
}
