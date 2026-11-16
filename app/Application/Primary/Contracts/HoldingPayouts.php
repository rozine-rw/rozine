<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * What servicing has paid on to each issued Holding so far: one row per payout recorded against the
 * Holding's lot, oldest first, each naming the instalments it paid, the gross principal and return
 * it settled, and the net that reached the Investor's wallet after the fee on earnings.
 *
 * @phpstan-type HoldingPayout array{recorded_at: string, instalments: list<int>, principal: string, return: string, net: string}
 */
interface HoldingPayouts
{
    /**
     * @param  list<string>  $holdingIds
     * @return array<string, list<HoldingPayout>> Keyed by Holding id; a Holding nothing has been paid on is absent.
     */
    public function forHoldings(array $holdingIds): array;
}
