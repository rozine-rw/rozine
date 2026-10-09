<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * The Operations Center's platform-wide figures, read from the records that already exist. Reads
 * only and never locks; the caller checks the staff permission. Nothing is estimated: a figure the
 * platform does not record yet (treasury position, default rate, secondary volume, reconciliation
 * breaks, note health, collections) has no read here at all.
 *
 * - `capital_raised`: the principal of every issued Holding, so a note counts once it has issued.
 * - `active_businesses`: Business profiles whose current mandate is active and in force now.
 * - `notes`: every published campaign by where it sits now: `live` while it raises (no funding lock
 *   and no closure), `funded` once its funding lock is recorded and before its disbursement closes,
 *   `repaying` once its disbursement closed with issued notes, and `failed` when it closed unfunded
 *   (cancelled or expired) or its disbursement failed to close.
 * - `awaiting_second_approver`: disbursements whose folded state waits for the checker.
 * - `disbursed`: the amount of every disbursement that closed with issued notes.
 *
 * @phpstan-type Figures array{
 *     capital_raised: string, active_businesses: int, awaiting_second_approver: int, disbursed: string,
 *     notes: array{live: int, funded: int, repaying: int, failed: int}
 * }
 * @phpstan-type Bar array{label: string, amount: string, height_pct: int}
 */
interface OperationsDashboardStore
{
    /** @return Figures */
    public function figures(): array;

    /**
     * Issued Holding principal per Kigali calendar bucket, oldest first, from `$from` (inclusive) to
     * `$to` (exclusive) when given. Only buckets with issued principal are returned; each bar's
     * height is its share of the tallest, rounded to a whole percent.
     *
     * @param  'year'|'month'|'day'|'hour'  $grain
     * @return list<Bar>
     */
    public function capitalRaised(string $grain, ?string $from, ?string $to): array;
}
