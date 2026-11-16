<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * Staging's stand-in for the funding close (Erastus, 14 Nov 2026): while the UAT money simulation is
 * on, a named synthetic Business's raise that is fully committed is recorded as funded, so the
 * disbursement that follows can open. The live eligibility, policy, connection and destination
 * admission is not built yet; the simulation's own admission takes its place and says so. Outside
 * the simulation it records nothing.
 */
interface SimulatedRaiseFunding
{
    /**
     * Records funding for up to `$limit` fully committed raises of named Businesses, once each.
     *
     * @return array{funded: int, waiting: int}
     */
    public function fundCommitted(int $limit): array;
}
