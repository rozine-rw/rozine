<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Primary Market Monitor from live publications: every rated raise still filling, with its
 * fill, investors and Kigali days left, and no moderation command.
 */

/** @param list<string> $roles */
function primaryMarketStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Primary market assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** @return array<string, mixed> */
function primaryMarketDeal(string $id, string $name, string $raised, string $target, string $lifecycle = 'live', string $expiresAt = '2026-10-20T08:00:00Z'): array
{
    return ['campaign_id' => $id, 'name' => $name, 'industry' => 'Agriculture', 'district' => 'Gasabo', 'rating' => ['band' => 'strong', 'score' => '4.2'],
        'raised' => ['currency' => 'RWF', 'amount' => $raised], 'target' => ['currency' => 'RWF', 'amount' => $target], 'funded_pct' => '50.0',
        'investors' => 3, 'lifecycle' => $lifecycle, 'rate_pct' => '15', 'term_months' => 12,
        'clock' => ['starts_at' => '2026-10-01T08:00:00Z', 'expires_at' => $expiresAt]];
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.primary-market.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/primary-market');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('lists a live publication with its fill, terms and days left, and offers no moderation', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $evidence = $campaign->payload['public_evidence'];
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $version = PrimaryReservationVersion::query()->sole()->payload;
    $checkout->confirm($investor['user']->id, 1, $campaign->id, PrimaryReservationRecord::query()->sole()->id, 1,
        $version['terms']['disclosure_version'], $version['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $props = ($this->page)(primaryMarketStaff(['analyst']));
    $today = now('Africa/Kigali')->startOfDay();
    $closes = CarbonImmutable::parse($campaign->payload['expires_at'])->setTimezone('Africa/Kigali');

    expect($props['contract_version'])->toBe('staff-primary-market-v1')->and($props['allowed_actions'])->toBe([])
        ->and($props['nav']['primary_market'])->toBe(['url' => '/admin/primary-market', 'method' => 'get'])
        ->and($props['stats'])->toBe([['key' => 'active', 'value' => ['kind' => 'count', 'value' => 1]],
            ['key' => 'in_flight', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '15000']]],
            ['key' => 'fill', 'value' => ['kind' => 'percent', 'value' => $props['stats'][2]['value']['value']]], ['key' => 'completion', 'value' => ['kind' => 'percent', 'value' => '0']]])
        ->and($props['trend'])->toBe(['from' => $today->subDays(13)->toIso8601String(),
            'bars' => [['label' => $today->format('m-d'), 'amount' => ['currency' => 'RWF', 'amount' => '15000'], 'height_pct' => 100]]])
        ->and($props['raises'])->toBe([['id' => $campaign->id, 'reference' => 'RSE-'.Str::upper(Str::substr($campaign->id, -6)),
            'business' => $evidence['business']['name'], 'sector' => $evidence['business']['industry'], 'district' => $evidence['business']['district'],
            'rating' => ['band' => $evidence['rating']['band'], 'score' => $evidence['rating']['score']],
            'rate_pct' => $campaign->payload['quote']['rate_pct'], 'term_months' => $campaign->payload['quote']['term_months'], 'funded_pct' => $props['raises'][0]['funded_pct'],
            'raised' => ['currency' => 'RWF', 'amount' => '15000'], 'target' => ['currency' => 'RWF', 'amount' => $campaign->payload['principal']],
            'investors' => 1, 'lifecycle' => 'live',
            'opened_at' => CarbonImmutable::parse($campaign->payload['recorded_at'])->setTimezone('Africa/Kigali')->toIso8601String(),
            'closes_at' => $closes->toIso8601String(),
            'days_left' => max(0, (int) now('Africa/Kigali')->startOfDay()->diffInDays($closes->startOfDay(), false))]])
        ->and($props['pagination'])->toBe(['next' => null]);

    expect(($this->page)(primaryMarketStaff(['analyst']), ['q' => 'no such raise'])['raises'])->toBe([]);
});

it('measures fill and completion across raises, leaves funded raises out, searches and pages', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00', 'Africa/Kigali'));
    app()->instance(InvestorDealCatalogue::class, Mockery::mock(InvestorDealCatalogue::class, ['deals' => [
        primaryMarketDeal('01K0000000000000000000AAAA', 'Ubuki Crafts', '3000000', '4000000'),
        primaryMarketDeal('01K0000000000000000000BBBB', 'GreenLeaf Agro', '1000000', '6000000', 'closing_pending_settlement', '2026-10-14T08:00:00Z'),
        primaryMarketDeal('01K0000000000000000000CCCC', 'Funded Foods', '5000000', '5000000', 'funded'),
    ]]));
    app()->instance(OperationsDashboardStore::class, Mockery::mock(OperationsDashboardStore::class, ['figures' => [
        'capital_raised' => '0', 'active_businesses' => 0, 'awaiting_second_approver' => 0, 'disbursed' => '0',
        'notes' => ['live' => 2, 'funded' => 1, 'repaying' => 2, 'failed' => 1]],
        'committed' => [['label' => '10-15', 'amount' => '250000', 'height_pct' => 100]]]));
    $staff = primaryMarketStaff(['treasury']);
    $props = ($this->page)($staff);

    expect(array_column($props['stats'], 'value', 'key'))->toBe(['active' => ['kind' => 'count', 'value' => 2],
        'in_flight' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '4000000']],
        'fill' => ['kind' => 'percent', 'value' => '40'], 'completion' => ['kind' => 'percent', 'value' => '75']])
        ->and($props['trend'])->toBe(['from' => '2026-10-02T00:00:00+02:00',
            'bars' => [['label' => '10-15', 'amount' => ['currency' => 'RWF', 'amount' => '250000'], 'height_pct' => 100]]])
        ->and(array_column($props['raises'], 'reference'))->toBe(['RSE-00AAAA', 'RSE-00BBBB'])
        ->and(array_column($props['raises'], 'days_left'))->toBe([5, 0])
        ->and(array_column($props['raises'], 'closes_at'))->toBe(['2026-10-20T10:00:00+02:00', '2026-10-14T10:00:00+02:00']);

    expect(array_column(($this->page)($staff, ['q' => 'greenleaf'])['raises'], 'business'))->toBe(['GreenLeaf Agro'])
        ->and(($this->page)($staff, ['limit' => 1])['pagination']['next'])->toBe(['url' => '/admin/primary-market?limit=500', 'method' => 'get']);
});

it('is for staff who may view the primary market, and over the API for a primary read token', function (): void {
    $this->actingAs(User::factory()->withTwoFactor()->create())->get(route('staff.primary-market.index'))->assertForbidden();
    $this->actingAs(primaryMarketStaff(['analyst']))->get(route('staff.primary-market.index', ['limit' => 0]))->assertSessionHasErrors('limit');

    $staff = primaryMarketStaff(['compliance']);
    Sanctum::actingAs($staff, ['staff:exceptions:read']);
    $this->getJson(route('api.v1.staff.primary-market.index'))->assertForbidden();
    Sanctum::actingAs($staff, ['staff:primary:read']);
    $this->getJson(route('api.v1.staff.primary-market.index'))->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-primary-market-v1')
        ->assertJsonPath('data.nav.primary_market.url', '/api/v1/staff/primary-market');
});
