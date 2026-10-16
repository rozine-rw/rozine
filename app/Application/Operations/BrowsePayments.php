<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use Carbon\CarbonImmutable;

/**
 * The Payment Center (`payments.view`): the money that moved across the platform over the last
 * fourteen Kigali days (credited deposits, paid-out withdrawals and closed disbursements), the same
 * deposits and payouts per day, and every withdrawal whose cash is held while its payout is in
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

    public function __construct(private OperationsDashboardStore $dashboard, private AuthorizeStaffPermission $staff) {}

    /** @return array{from: string, movements: Movements, bars: list<Bar>, payouts: list<Payout>} */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'payments.view');
        $from = CarbonImmutable::now(self::ZONE)->startOfDay()->subDays(self::DAYS - 1);
        $utc = $from->utc()->toIso8601String();

        return ['from' => $from->toIso8601String(), 'movements' => $this->dashboard->movements($utc),
            'bars' => $this->dashboard->moved('day', $utc, null), 'payouts' => $this->dashboard->payoutsInFlight()];
    }
}
