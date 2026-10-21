<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use Carbon\CarbonImmutable;

/**
 * The Payment Center (`payments.view`): the money that moved across the platform over the last
 * fourteen Kigali days (credited deposits, paid-out withdrawals and closed disbursements), the same
 * deposits and payouts per day over those days or a chosen window of dates (per month over a window
 * of 62 days or more), and every withdrawal whose cash is held while its payout is in
 * flight. Withdrawals are paid out on the provider's verified outcome; no hold-for-review policy is
 * approved (OD-17), so nothing here can release, hold or reverse a payment.
 *
 * @phpstan-import-type Bar from OperationsDashboardStore
 * @phpstan-import-type Movements from OperationsDashboardStore
 * @phpstan-import-type Payout from OperationsDashboardStore
 */
final readonly class BrowsePayments
{
    private const string ZONE = 'Africa/Kigali';

    /** The design's Payment Center reads the last fourteen Kigali days. */
    private const int DAYS = 14;

    /** A chosen window this long or longer is drawn per month. */
    private const int DAILY_DAYS = 62;

    public function __construct(private OperationsDashboardStore $dashboard, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  string|null  $first  the window's first Kigali date (Y-m-d), or null for fourteen days before `$last`
     * @param  string|null  $last  the window's last Kigali date (Y-m-d), or null for today
     * @return array{from: string, movements: Movements, trend: array{from: string, to: string|null, grain: 'day'|'month'}, bars: list<Bar>, payouts: list<Payout>}
     */
    public function handle(int $actorId, ?string $first = null, ?string $last = null): array
    {
        $this->staff->check($actorId, 'payments.view');
        $today = CarbonImmutable::now(self::ZONE)->startOfDay();
        $from = $today->subDays(self::DAYS - 1);
        $utc = $from->utc()->toIso8601String();
        $end = $last === null ? null : CarbonImmutable::parse($last, self::ZONE)->startOfDay();
        $start = $first === null ? ($end ?? $today)->subDays(self::DAYS - 1) : CarbonImmutable::parse($first, self::ZONE)->startOfDay();
        $grain = $start->diffInDays($end ?? $today, true) >= self::DAILY_DAYS ? 'month' : 'day';

        return ['from' => $from->toIso8601String(), 'movements' => $this->dashboard->movements($utc),
            'trend' => ['from' => $start->toIso8601String(), 'to' => $end?->toIso8601String(), 'grain' => $grain],
            'bars' => $this->dashboard->moved($grain, $start->utc()->toIso8601String(), $end?->addDay()->utc()->toIso8601String()),
            'payouts' => $this->dashboard->payoutsInFlight()];
    }
}
