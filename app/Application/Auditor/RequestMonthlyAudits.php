<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Domain\Auditor\AuditReportWindow;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispatches each funded Business's monthly audit once per Kigali month, for the month just ended. Nothing is
 * dispatched once that month's report window has shut, because a report sealed after it could never publish.
 */
final class RequestMonthlyAudits
{
    public function __construct(private MonthlyAuditBusinesses $businesses, private AuditAssignmentStore $store, private AuditReportWindow $window) {}

    public function handle(DateTimeImmutable $now): int
    {
        $period = $now->setTimezone(new DateTimeZone('Africa/Kigali'))->modify('first day of last month')->format('Y-m');
        if (! $this->window->mayPublish($period, $now)) {
            return 0;
        }
        $count = 0;
        foreach ($this->businesses->due($now) as $businessId) {
            try {
                $count += $this->store->requestMonthly($businessId) === null ? 0 : 1;
            } catch (Throwable $exception) {
                Log::error('AUDIT_MONTHLY_REQUEST_FAILED', ['business_id' => $businessId, 'exception_type' => $exception::class]);
            }
        }

        return $count;
    }
}
