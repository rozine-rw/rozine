<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use DateTimeImmutable;

/**
 * The Business's next monthly audit, as its open routine assignment states it. A routine audit
 * covers the month before it was dispatched and is sealed by that month's report deadline; the
 * co-sign window closes 24 hours after that. A plain read: the caller has already checked the
 * Business authority, and no Audit Partner's contact, location or licence leaves this port.
 *
 * @phpstan-type AuditWindow array{period: string, seal_by: string, cosign_by: string, days_left: int, first: bool, reassigned: array{from: string, to: string}|null}
 */
interface BusinessAuditWindows
{
    /**
     * The Business's open routine audit while its report is not sealed yet and its deadline has
     * not passed at `$now`, or null when there is none.
     *
     * @return AuditWindow|null
     */
    public function upcoming(string $businessId, DateTimeImmutable $now): ?array;
}
