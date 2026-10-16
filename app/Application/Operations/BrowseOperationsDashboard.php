<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use Carbon\CarbonImmutable;

/**
 * The Admin console's Operations Center (`admin.open`, so every enabled staff member): the
 * platform's headline figures, the human queues and capital raised over a chosen window. Each figure
 * comes from an existing read: the platform figures from the dashboard store, verified Investors and
 * submissions awaiting review from the Investor directory, and the applications waiting for release
 * from the staff applications queue, with its first few entries. The queue is read only for a
 * viewer who holds `applications.review`, as the queue itself requires: anyone else gets neither
 * the count nor a single row, so unreleased application details never leave the queue's guard.
 *
 * @phpstan-import-type Figures from OperationsDashboardStore
 * @phpstan-import-type Bar from OperationsDashboardStore
 *
 * @phpstan-type Dashboard array{
 *     figures: Figures, verified_investors: int, kyc_awaiting: int, applications_pending: int|null,
 *     pending: list<array<string, mixed>>,
 *     capital: array{from: string|null, to: string|null, grain: 'year'|'month'|'day'|'hour', bars: list<Bar>}
 * }
 */
final class BrowseOperationsDashboard
{
    /** The capital chart reads the Kigali calendar. */
    public const ZONE = 'Africa/Kigali';

    /** The design lists the first four applications waiting. */
    private const PENDING = 4;

    public function __construct(private OperationsDashboardStore $store, private InvestorDirectoryStore $investors,
        private StaffApplicationQueue $applications, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  string|null  $from  a Kigali local `Y-m-d\TH:i`, or null for no lower bound
     * @param  string|null  $to  a Kigali local `Y-m-d\TH:i`, or null for no upper bound
     * @return Dashboard
     */
    public function handle(int $actorId, ?string $from, ?string $to): array
    {
        $this->staff->check($actorId, 'admin.open');
        $start = $from === null ? null : CarbonImmutable::parse($from, self::ZONE);
        $end = $to === null ? null : CarbonImmutable::parse($to, self::ZONE);
        $grain = self::grain($start, $end);
        $investors = $this->investors->directory('all', 'name', '', 0);
        $queue = $this->staff->currentlyHolds($actorId, 'applications.review')
            ? $this->applications->page('pending', '', null, self::PENDING, null) : null;
        /** @var list<array<string, mixed>> $pending */
        $pending = $queue === null ? [] : $queue['entries'];

        return ['figures' => $this->store->figures(), 'verified_investors' => $investors['counts']['verified'], 'kyc_awaiting' => $investors['awaiting_review'],
            'applications_pending' => $queue === null ? null : (int) $queue['counts']['pending'], 'pending' => $pending,
            'capital' => ['from' => $start?->toIso8601String(), 'to' => $end?->toIso8601String(), 'grain' => $grain,
                'bars' => $this->store->capitalRaised($grain, $start?->utc()->toIso8601String(), $end?->utc()->toIso8601String())]];
    }

    /**
     * All time reads by year; a chosen window reads in the finest bucket that keeps the chart to a
     * few dozen bars: hours within a day, days within a month, months within two years.
     *
     * @return 'year'|'month'|'day'|'hour'
     */
    private static function grain(?CarbonImmutable $start, ?CarbonImmutable $end): string
    {
        if ($start === null) {
            return 'year';
        }
        $hours = $start->diffInHours($end ?? CarbonImmutable::now(self::ZONE), true);

        return match (true) {
            $hours <= 24 => 'hour',
            $hours <= 24 * 31 => 'day',
            $hours <= 24 * 731 => 'month',
            default => 'year',
        };
    }
}
