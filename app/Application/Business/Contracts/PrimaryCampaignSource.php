<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use DateTimeImmutable;

/**
 * Retained publication input only, not investor authority, current eligibility or
 * remaining inventory. The Primary command must check those before moving cash.
 *
 * @phpstan-type CampaignInput array{id: string, business_id: string, application_id: string, exposure_reservation_id: string, publication_sha256: string, principal: string, units: string, rate_pct: string, term_months: int, policy_version: string, payments: list<string>, live_at: DateTimeImmutable, expires_at: DateTimeImmutable}
 */
interface PrimaryCampaignSource
{
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
}
