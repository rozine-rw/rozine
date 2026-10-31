<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Application\Identity\Contracts\StaffDirectoryStore;
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
 * Frozen accounts counts frozen operators, the only accounts that can be frozen today.
 *
 * Portfolio health reads every issued note (repaying) by its Business's newest published monthly
 * report, as Investors see it: on watch when that report is, healthy otherwise. A sector is at risk
 * while any of its notes is on watch. Distress (late or defaulted) needs repayment servicing, which
 * the platform does not record yet, so it is null.
 *
 * @phpstan-import-type Figures from OperationsDashboardStore
 * @phpstan-import-type Bar from OperationsDashboardStore
 *
 * @phpstan-type RiskSector array{sector: string, principal: string, notes: int, campaigns: list<string>, at_risk: bool}
 * @phpstan-type Dashboard array{
 *     figures: Figures, verified_investors: int, kyc_awaiting: int, applications_pending: int|null, frozen_accounts: int,
 *     pending: list<array<string, mixed>>, sectors: list<RiskSector>,
 *     health: array{total: int, healthy: int, watch: int, distressed: null},
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
        private StaffApplicationQueue $applications, private AuthorizeStaffPermission $staff, private InvestorDealCatalogue $deals,
        private StaffDirectoryStore $operators) {}

    /**
     * @param  string|null  $from  a Kigali local `Y-m-d\TH:i`, or null for no lower bound
     * @param  string|null  $to  a Kigali local `Y-m-d\TH:i`, or null for no upper bound
     * @return Dashboard
     */
    public function handle(int $actorId, ?string $from, ?string $to): array
    {
        $this->staff->check($actorId, 'admin.open');
        $investors = $this->investors->directory('all', 'name', '', 0);
        $queue = $this->staff->currentlyHolds($actorId, 'applications.review')
            ? $this->applications->page('pending', '', null, self::PENDING, null) : null;
        /** @var list<array<string, mixed>> $pending */
        $pending = $queue === null ? [] : $queue['entries'];

        return ['figures' => $this->store->figures(), 'verified_investors' => $investors['counts']['verified'], 'kyc_awaiting' => $investors['awaiting_review'],
            'applications_pending' => $queue === null ? null : (int) $queue['counts']['pending'], 'pending' => $pending,
            'frozen_accounts' => $this->operators->directory('all', '')['counts']['frozen'],
            ...$this->health(), 'capital' => $this->capitalChart($from, $to)];
    }

    /** @return array{from: string|null, to: string|null, grain: 'year'|'month'|'day'|'hour', bars: list<Bar>} */
    public function capitalRaised(int $actorId, ?string $from, ?string $to): array
    {
        $this->staff->check($actorId, 'admin.open');

        return $this->capitalChart($from, $to);
    }

    /** @return array{from: string|null, to: string|null, grain: 'year'|'month'|'day'|'hour', bars: list<Bar>} */
    private function capitalChart(?string $from, ?string $to): array
    {
        $start = $from === null ? null : CarbonImmutable::parse($from, self::ZONE);
        $end = $to === null ? null : CarbonImmutable::parse($to, self::ZONE);
        $grain = self::grain($start, $end);

        return ['from' => $start?->toIso8601String(), 'to' => $end?->toIso8601String(), 'grain' => $grain,
            'bars' => $this->store->capitalRaised($grain, $start?->utc()->toIso8601String(), $end?->utc()->toIso8601String())];
    }

    /**
     * Issued notes by their newest monthly report, and each sector marked at risk while one of its
     * notes is on watch.
     *
     * @return array{sectors: list<RiskSector>, health: array{total: int, healthy: int, watch: int, distressed: null}}
     */
    private function health(): array
    {
        $issued = array_column(array_filter($this->store->noteStates(), fn (array $note): bool => $note['state'] === 'repaying'), 'business_id', 'campaign_id');
        // Reports belong to the Business, so each Business's newest report is read once, whatever number of notes it issued.
        $watched = array_filter(array_flip($issued), fn (string $campaign): bool => ($this->deals->updates($campaign)[0]['status'] ?? null) === 'watch');
        $watch = array_keys(array_filter($issued, fn (string $business): bool => array_key_exists($business, $watched)));

        return ['sectors' => array_map(fn (array $sector): array => [...$sector, 'at_risk' => array_intersect($sector['campaigns'], $watch) !== []],
            $this->store->sectorExposure()),
            'health' => ['total' => count($issued), 'healthy' => count($issued) - count($watch), 'watch' => count($watch), 'distressed' => null]];
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
