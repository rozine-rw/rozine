<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use DateTimeImmutable;

/**
 * Retained publication input and an explicit known-connection refusal. Neither
 * provides investor authority, complete current eligibility or remaining inventory.
 * The Primary command must check those before moving cash.
 *
 * @phpstan-type CampaignInput array{id: string, business_id: string, application_id: string, exposure_reservation_id: string, publication_sha256: string, principal: string, units: string, rate_pct: string, term_months: int, policy_version: string, payments: list<string>, live_at: DateTimeImmutable, expires_at: DateTimeImmutable}
 */
interface PrimaryCampaignSource
{
    /**
     * Acquires only the owning Business lock before caller User/Party authority.
     * Requires an outer transaction and must precede any actor, campaign or wallet lock.
     * Publication availability is deliberately checked by lock(), after journal replay.
     */
    public function lockBusiness(string $campaignId): void;

    /**
     * Requires the outer transaction and the caller's already-retained Business lock.
     * Rejects the canonical Business Party and people in its current declared mandate;
     * missing or inactive mandate evidence refuses. No User/Party/wallet locks are added.
     * Passing this check is not an independence attestation: broader connections and
     * current investor eligibility still require the authoritative admission checks.
     *
     * @param  list<string>  $partyIds
     */
    public function rejectKnownConnections(string $businessId, array $partyIds): void;

    /**
     * Requires the caller's transaction. Retains Business then campaign locks
     * until the outer commit or rollback, provided the acquiring savepoint is
     * released successfully. Rolling it back releases its locks; callers must
     * reacquire and revalidate before continuing. Primary and wallet locks follow.
     * Refuses a closed publication; never recalculates its accepted pricing.
     *
     * @return CampaignInput
     */
    public function lock(string $campaignId): array;

    /**
     * Same locks and immutable evidence checks as lock(), including validated exposure history,
     * but permits a closed or elapsed publication. This is recovery input for returning held
     * cash, never permission to reserve or confirm another purchase.
     *
     * @return CampaignInput
     */
    public function lockRetained(string $campaignId): array;
}
