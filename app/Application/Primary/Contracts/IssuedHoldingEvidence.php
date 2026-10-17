<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * Complete historical issued Closing/Holdings source for C4. SELECT-only in the caller's open
 * transaction: no locks, current admission, repricing, servicing or financial writes. Authenticates
 * original funding/purchases, closing ancestry, every Holding envelope, original rights/terms,
 * anchored schedule, receipts, exact issue journals and the once-only exposure conversion.
 * Null means no issuance exists. Partial, schema-only or corrupt evidence is refused, never
 * promoted to a certified bundle or a current financial permission.
 */
interface IssuedHoldingEvidence
{
    /** @return array<string, mixed>|null */
    public function find(string $campaignId): ?array;
}
