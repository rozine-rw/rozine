<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Business's current mandate while it is active and in force (BRS v3 KYC-R03: one official
 * representative acts for the Business under it). It grants nothing; it only says which mandate
 * authorizes the Business's standing arrangements, such as where its raise is paid.
 *
 * @phpstan-type MandateInForce array{id: string, version: int, expires_at: string|null}
 */
interface BusinessMandatesInForce
{
    /**
     * The current mandate's id, version and end (UTC, `Y-m-d\TH:i:s\Z`, or null when open-ended), or
     * null when the Business is unknown or its current mandate is missing, revoked, not yet effective
     * or expired.
     *
     * @return MandateInForce|null
     */
    public function current(string $businessId): ?array;
}
