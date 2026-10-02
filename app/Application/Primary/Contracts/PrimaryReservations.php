<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Application\Primary\PrimaryCampaignReturns;
use App\Application\Primary\PrimaryFundingCandidate;
use App\Application\Primary\ReservationConfirmation;
use App\Application\Primary\ReservationRefund;
use App\Application\Primary\ReservationRelease;
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
     * Expired holds refuse here; the caller records the refusal, then expires the hold in
     * the same outer transaction. Inventory recycling remains separate work.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     */
    public function confirm(string $campaignId, string $reservationId, string $partyId, string $operationId,
        int $expectedRevision, string $disclosureVersion, string $disclosureSha256, Closure $admit): ReservationConfirmation;

    /**
     * Requires current caller authority, its outer transaction and journal operation.
     * A release returns the original held principal without new admission. A confirmed
     * commitment refuses; a released hold is idempotent. Expiry refuses for the caller
     * to retain its rejected receipt before invoking expire() in the outer transaction.
     */
    public function release(string $campaignId, string $reservationId, string $partyId, string $operationId, int $expectedRevision): ReservationRelease;

    /**
     * Requires the caller transaction and Business-first authority order. Locks the unclosed
     * campaign, all reservation roots then commitments by id, and all wallets by Party id.
     * The publication deadline may have elapsed; replayed confirmation history must still
     * prove each purchase occurred within its original half-open hold/publication window.
     * Verifies complete retained rights and original committed cash without posting or
     * declaring funding. Returned/issued cash refuses. The caller must keep these locks
     * while checking current eligibility/policy/destination and recording any funding.
     */
    public function lockFundingCandidate(string $campaignId): PrimaryFundingCandidate;

    /**
     * Requires the authorized closing caller's outer READ COMMITTED transaction. Verifies
     * every retained root, confirmation and exact original cash return under Business →
     * campaign → roots → commitments → Party-sorted wallet locks. Live holds, unreturned
     * commitments and funded campaigns refuse. Publication expiry does not remove evidence.
     * All roots must be settled; this port moves no cash, creates no closing cause, releases
     * no exposure and recycles no ordinals. The caller must retain the locks while recording
     * a separately guarded closure and its exact return bindings in the same transaction.
     */
    public function lockReturnedCampaign(string $campaignId): PrimaryCampaignReturns;

    /**
     * Requires the caller transaction and a new system-expiry closure ID. Returns exact
     * original cash for a partially funded expired campaign, preserving immutable purchase
     * history. Fully committed campaigns defer to funding settlement; retained funding refuses.
     * Business → campaign → roots/commitments → Party-sorted wallets. A durable system cause
     * requires the matching guarded expiry closure at outer commit, so this cannot commit alone.
     */
    public function settleExpiredCampaign(string $campaignId, string $closureId): void;

    /**
     * Requires the caller transaction. The caller authorizes and retains its actor journal
     * command or bound system expiry cause; this primitive does not authenticate that cause.
     * Before retained full funding, returns exact confirmed principal fee-free under Business → campaign →
     * root → commitment → wallet gates. Confirmation history remains immutable; cash replay
     * is idempotent. Publication/hold expiry does not remove the right to return unissued cash.
     * This primitive neither closes the campaign nor releases exposure or recycles ordinals.
     */
    public function refund(string $campaignId, string $reservationId, string $partyId, int $expectedRevision): ReservationRefund;

    /**
     * Examines at most limit overdue, nonterminal candidates. Unfailed candidates go
     * first in deadline/id order, then failed ones in oldest-attempt/deadline/id order.
     * Invoke without an outer transaction so each candidate commits independently.
     * Failures roll back that candidate, retain retry metadata and log its identity;
     * remaining candidates still run, then the first error is rethrown. Successful
     * expiry evidence stays immutable and the failure metadata never authorizes cash.
     * Returns the number newly expired; concurrent terminal transitions are harmless skips.
     */
    public function expireDue(int $limit): int;

    /**
     * Requires an outer transaction. Returns null for not-yet-due or terminal holds.
     * The system expiry path supplies no actor operation. Actor-driven expiry supplies
     * only its retained rejected RESERVATION_EXPIRED receipt. Neither recycles ordinals
     * nor refunds a confirmed commitment. Business → campaign → root → wallet → ledger.
     */
    public function expire(string $campaignId, string $reservationId, ?string $operationId = null): ?ReservationRelease;
}
