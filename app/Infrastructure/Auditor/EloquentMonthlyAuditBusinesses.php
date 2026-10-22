<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class EloquentMonthlyAuditBusinesses implements MonthlyAuditBusinesses
{
    public function __construct(private RetainedNoteSchedules $schedules) {}

    /** @return list<string> */
    public function due(DateTimeImmutable $now): array
    {
        $month = CarbonImmutable::instance($now)->setTimezone('Africa/Kigali')->startOfMonth();
        $due = [];
        DB::table('business_campaigns')->where('live_at', '<', $month->utc())->select('id')->lazyById(100)->each(function (object $campaign) use ($month, &$due): void {
            $schedule = $this->schedules->find((string) $campaign->id);
            $last = $schedule === null || $schedule->instalments === [] ? null : $schedule->instalments[count($schedule->instalments) - 1];
            if ($last !== null && CarbonImmutable::parse($schedule->issuedAt)->lessThan($month) && $last->dueDate >= $month->format('Y-m-d')) {
                $due[$schedule->businessId] = true;
            }
        });
        $businesses = array_keys($due);
        sort($businesses);

        return $businesses;
    }
}
