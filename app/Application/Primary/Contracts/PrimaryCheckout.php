<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use Closure;

/** Internal caller boundary. HTTP checkout remains inactive until admission and lifecycle integration.
 * @phpstan-import-type PurchaseFacts from PrimaryReservations
 */
interface PrimaryCheckout
{
    /**
     * Current Investor/context/Party authorization precedes private retained-history replay.
     * Starts without previously acquired authority/wallet locks; retains Business → User →
     * Party → shared root/commitment gates through the caller's outer commit. Returns only
     * authenticated historical reservation and confirmation facts, including terminal roots.
     * Separate current cash/funding/admission/Holding sources remain necessary. No writes,
     * policy selection or transport activation occur.
     *
     * @return PurchaseFacts
     */
    public function reservationFacts(int $userId, int $contextRevision, string $campaignId, string $reservationId): array;

    /** Same current authority and historical-only contract as reservationFacts().
     * @return PurchaseFacts
     */
    public function commitmentFacts(int $userId, int $contextRevision, string $campaignId, string $commitmentId): array;

    /**
     * Starts with no previously acquired authority/wallet locks. Retains Business → User → Party
     * authority through journal replay, reservation persistence and the outer commit. The Party
     * comes from the authenticated user's current Investor context, never client input.
     *
     * Admission must check current eligibility, connections, global exposure and fee policy;
     * it runs only for a new command, under the same locks, and must have no external effects.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     * @return array<string, mixed>
     */
    public function reserve(int $userId, int $contextRevision, string $campaignId, string $units, string $requestId, Closure $admit): array;

    /**
     * Current Investor authority is required even for a retained receipt of a closed campaign.
     *
     * @return array<string, mixed>
     */
    public function findReservation(int $userId, int $contextRevision, string $campaignId, string $requestId): array;

    /**
     * Reuses reserve's current authority and lock order. A changed disclosure produces
     * RESERVATION_REQUOTED without committing cash; acknowledge that returned revision
     * in a fresh command. Successful operation replays never re-run admission.
     *
     * @param  Closure(UnitRights, array<string, mixed>): PrimaryTerms  $admit
     * @return array<string, mixed>
     */
    public function confirm(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision,
        string $disclosureVersion, string $disclosureSha256, string $requestId, Closure $admit): array;

    /** @return array<string, mixed> */
    public function findConfirmation(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array;

    /**
     * Returns held cash under current Investor authority, even after publication closes.
     * An elapsed hold records a rejected expiry receipt and releases cash atomically.
     *
     * @return array<string, mixed>
     */
    public function release(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision, string $requestId): array;

    /** @return array<string, mixed> */
    public function findRelease(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array;

    /**
     * Returns confirmed principal without a fee before full funding. Requires current Investor
     * authority even on a retry; no new investment admission or fee policy is applied.
     * Original confirmation revision and terms remain retained, as do occupied unit claims.
     *
     * @return array<string, mixed>
     */
    public function refund(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision, string $requestId): array;

    /** @return array<string, mixed> */
    public function findRefund(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array;
}
