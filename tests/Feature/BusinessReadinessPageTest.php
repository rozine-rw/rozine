<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;

/*
 * The Business Readiness tab (BRS v3.0 Section 20): the stage settled by the verified revenue of
 * the business's published monthly reports, read under current Business authority. Readiness is
 * Business-only.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->readiness = function (User $user, string $business): array {
        return $this->actingAs($user)->get(route('business.readiness', ['business' => $business]))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('business/readiness'))->viewData('page')['props'];
    };
    $this->business = function (): array {
        $authority = AuthorityFixture::make('organization', 1, 'COMPANY-001', 1);

        return [$authority['users'][0], AuthorityFixture::configure($authority)['data']['business']['id']];
    };
    /** Serves these published months for any business, newest first as the port answers. */
    $this->published = function (array $months): void {
        app()->instance(PublishedMonthlyReports::class, new class($months) implements PublishedMonthlyReports
        {
            /** @param array<string, string> $months */
            public function __construct(private array $months) {}

            public function forBusiness(string $businessId): array
            {
                $reports = [];
                foreach ($this->months as $period => $inflow) {
                    $reports[] = ['report_id' => strtolower((string) Str::ulid()), 'period' => substr($period, 0, 7), 'inflow' => $inflow, 'outflow' => '0', 'net' => $inflow,
                        'operational_status' => 'active', 'differences' => false, 'auditor' => ['name' => 'Auditor', 'licence' => 'ICPAR-1', 'sealed_on' => '2026-10-01'],
                        'auditor_note' => '', 'business_note' => ''];
                }
                usort($reports, fn (array $left, array $right): int => strcmp($right['period'], $left['period']));

                return $reports;
            }
        });
    };
    $this->money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
});

it('names no stage before 3 published reports, counting a real published monthly report', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    AuditSealingFixture::seal($fixture);
    $business = $fixture['audit']['business'];
    $user = $fixture['audit']['authority']['users'][0];

    // Sealed but still in its review window: not final, so not counted, and not yet entered, so no check reads as completed.
    $before = ($this->readiness)($user, $business);
    expect($before['readiness'])->toMatchArray(['stage' => null, 'vmr' => null, 'reports' => 0, 'reports_needed' => 3, 'months' => []])
        ->and(array_slice($before['requirements'], 0, 2))->toBe([
            ['code' => 'C1', 'status' => 'not_triggered', 'due_on' => null, 'days_left' => null, 'met_on' => null, 'applies_from' => ['kind' => 'entry', 'value' => 0]],
            ['code' => 'F1', 'status' => 'not_triggered', 'due_on' => null, 'days_left' => null, 'met_on' => null, 'applies_from' => ['kind' => 'entry', 'value' => 0]]]);

    AuditSealingFixture::cosign($fixture);
    [$published] = app(PublishedMonthlyReports::class)->forBusiness($business);
    $props = ($this->readiness)($user, $business);
    expect(array_column(array_slice($props['requirements'], 0, 2), 'status'))->toBe(['met', 'met'])
        ->and($props['requirements'][0]['applies_from'])->toBeNull();

    expect($props['readiness'])->toBe(['stage' => null, 'status' => 'building', 'vmr' => null, 'reports' => 1, 'reports_needed' => 2, 'window' => 3,
        'pending' => null, 'next' => null,
        'thresholds' => [['stage' => 1, 'from' => ($this->money)('2500000')], ['stage' => 2, 'from' => ($this->money)('10000000')],
            ['stage' => 3, 'from' => ($this->money)('30000000')], ['stage' => 4, 'from' => ($this->money)('80000000')]],
        'months' => [['period' => $published['period'], 'revenue' => ($this->money)($published['inflow'])]]])
        ->and($props['links'])->toBe(['home' => ['url' => "/business/{$business}", 'method' => 'get'],
            'reports' => ['url' => "/business/{$business}/reports", 'method' => 'get'], 'readiness' => ['url' => "/business/{$business}/readiness", 'method' => 'get'],
            'market' => ['url' => "/business/{$business}/market", 'method' => 'get'], 'profile' => ['url' => "/business/{$business}/profile", 'method' => 'get'],
            'launcher' => ['url' => '/dashboard', 'method' => 'get']]);
});

it('settles the stage from the 3-month average and says which way the newest readings point', function (): void {
    [$user, $business] = ($this->business)();
    ($this->published)(['2026-04' => '12000000', '2026-05' => '12000000', '2026-06' => '12000000', '2026-07' => '40000000', '2026-08' => '41000000']);

    // Readings: 12M and 21.33M (Growth), then 31M (Scale): one reading points up, so the stage stays Growth.
    expect(($this->readiness)($user, $business)['readiness'])->toMatchArray(['stage' => 2, 'status' => 'building', 'vmr' => ($this->money)('31000000'),
        'reports' => 5, 'reports_needed' => 0, 'pending' => ['direction' => 'up', 'stage' => 3, 'months' => 1],
        'next' => ['stage' => 3, 'threshold' => ($this->money)('30000000'), 'gap' => ($this->money)('0')],
        'months' => [['period' => '2026-08', 'revenue' => ($this->money)('41000000')], ['period' => '2026-07', 'revenue' => ($this->money)('40000000')],
            ['period' => '2026-06', 'revenue' => ($this->money)('12000000')]]]);

    ($this->published)(['2026-04' => '40000000', '2026-05' => '40000000', '2026-06' => '40000000', '2026-07' => '20000000']);
    expect(($this->readiness)($user, $business)['readiness'])->toMatchArray(['stage' => 3, 'vmr' => ($this->money)('33333333'),
        'pending' => null, 'next' => ['stage' => 4, 'threshold' => ($this->money)('80000000'), 'gap' => ($this->money)('46666667')]]);

    ($this->published)(['2026-04' => '40000000', '2026-05' => '40000000', '2026-06' => '40000000', '2026-07' => '5000000']);
    expect(($this->readiness)($user, $business)['readiness'])->toMatchArray(['stage' => 3, 'pending' => ['direction' => 'down', 'stage' => 2, 'months' => 1]]);
});

it('names the top stage with nothing above it', function (): void {
    [$user, $business] = ($this->business)();
    ($this->published)(['2026-04' => '90000000', '2026-05' => '90000000', '2026-06' => '90000000']);

    expect(($this->readiness)($user, $business)['readiness'])->toMatchArray(['stage' => 4, 'vmr' => ($this->money)('90000000'), 'pending' => null, 'next' => null]);
});
