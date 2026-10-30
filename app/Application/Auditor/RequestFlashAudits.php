<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Lottery;
use Throwable;

/**
 * Draws unannounced Flash audits (BR-48). Each hourly run has a one in ODDS chance of sending one,
 * about one every two days, to a Business picked at random, so neither the day nor the Business can be
 * predicted. The Businesses drawn from are those owed a monthly audit: a note that lived through a whole
 * month and still has instalments due, so a note issued this month joins the draw next month. A drawn
 * Business with an audit still open, or whose last audit was closed, gets none from that draw.
 */
final class RequestFlashAudits
{
    /** One draw in this many sends a Flash audit. */
    public const ODDS = 48;

    public function __construct(private MonthlyAuditBusinesses $businesses, private AuditAssignmentStore $store) {}

    public function handle(DateTimeImmutable $now): int
    {
        $businesses = $this->businesses->due($now);
        if ($businesses === [] || ! Lottery::odds(1, self::ODDS)->choose()) {
            return 0;
        }
        $businessId = Arr::random($businesses);
        try {
            return $this->store->requestFlash($businessId) === null ? 0 : 1;
        } catch (Throwable $exception) {
            Log::error('AUDIT_FLASH_REQUEST_FAILED', ['business_id' => $businessId, 'exception_type' => $exception::class]);

            return 0;
        }
    }
}
