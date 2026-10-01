<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use DateTimeImmutable;

/** Aggregate retained facts only: never an admission, funding decision or proof of spendable cash. */
interface CampaignReservationSummary
{
    /**
     * The caller authorizes access and holds the Business lock when composing this with other evidence.
     * The instant classifies current held rows at their half-open deadline; this is not a historical query.
     * Returned and overdue roots still occupy inventory until recycling is implemented. Confirmed totals
     * exclude complete, source-bound principal refunds; original commitment evidence remains retained.
     * This is a current projection, not funding admission: callers must separately verify original cash.
     *
     * @return array{committed_principal: string, committed_units: string, investors: int,
     *     held_principal: string, held_units: string, expired_hold_principal: string, expired_hold_units: string,
     *     returned_principal: string, returned_units: string, occupied_units: string}
     */
    public function read(string $campaignId, DateTimeImmutable $at): array;
}
