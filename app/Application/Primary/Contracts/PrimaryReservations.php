<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Application\Primary\ReservationConfirmation;
use App\Application\Primary\ReservedCheckout;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use Closure;

/** Internal persistence boundary; no HTTP checkout is activated by this port. */
interface PrimaryReservations
{
    /**
     * Requires the caller's transaction, journal operation and authorized canonical Party.
     * Activation requires a verified caller authority/Business lock order; authority evidence
     * stays locked through commit. Successful journal replay precedes this call. This port takes
     * Business → campaign → reservations in stable id order → wallet/ledger locks.
     * All new evidence and cash movements roll back together, even if the caller catches a refusal.
     *
     * The required server-side admission callback checks current eligibility, connected parties,
     * global exposure and fee-tier policy under the retained locks. It returns explicit disclosed
     * terms or refuses; this port supplies no permissive policy fallback. It must not send external
     * effects. The campaign input is retained publication evidence, not current eligibility.
     *
     * Reservation creation conservatively counts every retained allocation, including timed-out
     * holds. Reusing allocations requires verified release/refund integration and a forward
     * migration of the capacity trigger and retained unit claims; application-only release
     * cannot recycle inventory.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     */
    public function reserve(string $campaignId, string $partyId, string $originOperationId, string $units, Closure $admit): ReservedCheckout;

    /**
     * Same transaction, authority, admission and lock requirements as reserve().
     * Changed fees/disclosures append a held revision, requiring a new acknowledgement;
     * they never extend the deadline or move cash. Matching current acknowledgement
     * atomically creates the commitment and moves the original hold to committed.
     * Expired holds refuse here; release and inventory recycling remain separate work.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     */
    public function confirm(string $campaignId, string $reservationId, string $partyId, string $operationId,
        int $expectedRevision, string $disclosureVersion, string $disclosureSha256, Closure $admit): ReservationConfirmation;
}
