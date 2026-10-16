<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\ActivityTrailStore;
use Carbon\CarbonImmutable;

/**
 * Activity & Audit (`investors.verify`): the platform-wide trail of identity changes and governed
 * commands, newest first. The trail names participants and their identity decisions, so it is read
 * under the supervisory permission that already reads Investors' identity history, never a wider
 * one. A preset counts back from now; a date range covers whole Kigali days.
 *
 * @phpstan-import-type Trail from ActivityTrailStore
 */
final class BrowseActivityTrail
{
    public function __construct(private ActivityTrailStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'today'|'7d'|'30d'|null  $preset
     * @param  string|null  $from  a `Y-m-d` Kigali date, ignored under a preset
     * @param  string|null  $to  a `Y-m-d` Kigali date, ignored under a preset
     * @return Trail
     */
    public function handle(int $actorId, string $search, ?string $preset, ?string $from, ?string $to, int $limit): array
    {
        $this->staff->check($actorId, 'investors.verify');
        $now = CarbonImmutable::now(BrowseOperationsDashboard::ZONE);
        [$start, $end] = match ($preset) {
            'today' => [$now->startOfDay(), null],
            '7d' => [$now->subDays(7), null],
            '30d' => [$now->subDays(30), null],
            default => [$from === null ? null : CarbonImmutable::parse($from, BrowseOperationsDashboard::ZONE)->startOfDay(),
                $to === null ? null : CarbonImmutable::parse($to, BrowseOperationsDashboard::ZONE)->addDay()->startOfDay()],
        };

        return $this->store->trail($search, $start?->utc()->toIso8601String(), $end?->utc()->toIso8601String(), $limit);
    }
}
