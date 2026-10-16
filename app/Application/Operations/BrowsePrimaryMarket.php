<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use Carbon\CarbonImmutable;

/**
 * The Primary Market Monitor (`primary.view`): every rated raise still filling, newest first, from
 * the same projection Investors browse, with its Kigali days left, and how many published raises
 * ended funded or failed. A raise whose funding lock is recorded has left the market and waits on
 * its disbursement, so it is not listed. The funding trend is the cash committed per Kigali day
 * over the last fourteen days. Nothing here can feature, pause, fill or fail a raise:
 * a raise fills or fails on its own clock (BRS), and no moderation policy is signed.
 *
 * @phpstan-import-type Bar from OperationsDashboardStore
 *
 * @phpstan-type Raise array{
 *     campaign_id: string, name: string, industry: string, district: string, rating: array{band: string, score: string},
 *     raised: array{currency: string, amount: string}, target: array{currency: string, amount: string}, funded_pct: string,
 *     investors: int, lifecycle: string, rate_pct: string, term_months: int, starts_at: string, closes_at: string, days_left: int
 * }
 */
final readonly class BrowsePrimaryMarket
{
    private const string ZONE = 'Africa/Kigali';

    public function __construct(private InvestorDealCatalogue $deals, private OperationsDashboardStore $dashboard, private AuthorizeStaffPermission $staff) {}

    /** The design's funding trend reads the last fourteen Kigali days. */
    private const int TREND_DAYS = 14;

    /** @return array{raises: list<Raise>, ended: array{funded: int, failed: int}, trend: array{from: string, bars: list<Bar>}} */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'primary.view');
        $today = CarbonImmutable::now(self::ZONE)->startOfDay();
        $kigali = fn (mixed $at): CarbonImmutable => CarbonImmutable::parse((string) $at)->setTimezone(self::ZONE);

        $raises = [];
        foreach ($this->deals->deals() as $deal) {
            if ($deal['lifecycle'] === 'funded') {
                continue;
            }
            $closes = $kigali($deal['clock']['expires_at']);
            $raises[] = ['campaign_id' => (string) $deal['campaign_id'], 'name' => (string) $deal['name'], 'industry' => (string) $deal['industry'],
                'district' => (string) $deal['district'], 'rating' => $deal['rating'], 'raised' => $deal['raised'], 'target' => $deal['target'],
                'funded_pct' => (string) $deal['funded_pct'], 'investors' => (int) $deal['investors'], 'lifecycle' => (string) $deal['lifecycle'],
                'rate_pct' => (string) $deal['rate_pct'], 'term_months' => (int) $deal['term_months'],
                'starts_at' => $kigali($deal['clock']['starts_at'])->toIso8601String(), 'closes_at' => $closes->toIso8601String(),
                'days_left' => max(0, (int) $today->diffInDays($closes->startOfDay(), false))];
        }
        $notes = $this->dashboard->figures()['notes'];
        $from = $today->subDays(self::TREND_DAYS - 1);

        return ['raises' => $raises, 'ended' => ['funded' => $notes['funded'] + $notes['repaying'], 'failed' => $notes['failed']],
            'trend' => ['from' => $from->toIso8601String(), 'bars' => $this->dashboard->committed('day', $from->utc()->toIso8601String(), null)]];
    }
}
