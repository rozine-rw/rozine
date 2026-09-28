<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Application\Primary\ReservedCheckout;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use Closure;

/** Internal persistence boundary; no HTTP checkout is activated by this port. */
interface PrimaryReservations
{
    /**
     * Requires the caller's transaction, journal operation and authorized canonical Party.
     * Authority locks must already be retained. Successful journal replay precedes this call.
     * Takes Business → campaign → reservations in stable id order → wallet/ledger locks.
     * All new evidence and cash movements roll back together, even if the caller catches a refusal.
     *
     * The required server-side admission callback checks current eligibility, connected parties,
     * global exposure and fee-tier policy under the retained locks. It returns explicit disclosed
     * terms or refuses; this port supplies no permissive policy fallback. It must not send external
     * effects. The campaign input is retained publication evidence, not current eligibility.
     *
     * Reservation creation conservatively counts every retained allocation, including timed-out
     * holds. Reusing allocations requires the future verified release/refund lifecycle integration.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     */
    public function reserve(string $campaignId, string $partyId, string $originOperationId, string $units, Closure $admit): ReservedCheckout;
}
