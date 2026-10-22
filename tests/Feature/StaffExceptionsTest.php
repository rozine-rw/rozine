<?php

declare(strict_types=1);

use App\Http\Resources\StaffExceptionsResource;
use App\Http\Resources\StaffNavigationResource;
use App\Models\BusinessProfile;
use App\Models\InvestorAccountRestriction;
use App\Models\Party;
use App\Models\User;
use App\Models\WalletProviderEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\StaffFixture;

/*
 * Exceptions (MVP-ADMIN-SCR-06) from live records: every open reconciliation break as a variance
 * and every Investor account case in force as a halt, oldest first, aged and never hidden.
 */

/** @param list<string> $roles */
function exceptionsStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, $roles, 'Exceptions assignment.');

    return $user;
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->travelTo(now('Africa/Kigali')->setTime(10, 0));
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.exceptions.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/exceptions');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
    $this->open = function (): array {
        InvestorWalletFixture::policy(maximum: null);
        $investor = InvestorWalletFixture::ready();
        $investor['user']->forceFill(['name' => 'Aline Uwase'])->save();
        $hold = InvestorAccountRestriction::factory()->create(['party_id' => $investor['party']->id, 'effective_at' => now()->subDays(3)]);
        $order = InvestorAccountRestriction::factory()->externalOrder(['deposits'])
            ->create(['effective_at' => now()->subDay(), 'expires_at' => now()->addDays(10)]);
        InvestorAccountRestriction::factory()->create(['effective_at' => now()->subDays(5), 'expires_at' => now()->subDay()]);
        InvestorAccountRestriction::factory()->create(['effective_at' => now()->addDay()]);
        InvestorWalletFixture::settle(InvestorWalletFixture::deposit($investor, '20000')['data']['intent_id'], eventId: 'evt-mismatch', amount: '25000');

        return ['investor' => $investor, 'hold' => $hold, 'order' => $order];
    };
});

it('lists every halt in force and every open variance, oldest first, with what it holds and where it opens', function (): void {
    ['investor' => $investor, 'hold' => $hold, 'order' => $order] = ($this->open)();
    $props = ($this->page)(exceptionsStaff(['compliance']));
    $kigali = fn (InvestorAccountRestriction $case): string => $case->effective_at->setTimezone('Africa/Kigali')->toIso8601String();
    $common = ['note_title' => null, 'amount' => null, 'dpd' => null, 'owner' => null];

    expect($props['contract_version'])->toBe('staff-exceptions-v1')->and($props['allowed_actions'])->toBe([])
        ->and($props['nav']['exceptions'])->toBe(['url' => '/admin/exceptions', 'method' => 'get'])
        ->and($props['nav']['risk'])->toBe(['url' => '/admin/exceptions', 'method' => 'get'])
        ->and($props['counts'])->toBe(['open' => 3, 'unassigned' => 3])
        ->and(array_map(fn (array $chip): array => [$chip['key'], $chip['count'], $chip['link']['url'], $chip['active']], $props['chips']))->toBe([
            ['all', 3, '/admin/exceptions', true], ['arrears', 0, '/admin/exceptions?kind=arrears', false],
            ['halt', 2, '/admin/exceptions?kind=halt', false], ['variance', 1, '/admin/exceptions?kind=variance', false],
            ['audit', 0, '/admin/exceptions?kind=audit', false], ['complaint', 0, '/admin/exceptions?kind=complaint', false],
        ])
        ->and($props['exceptions'][0])->toEqual(['id' => $hold->id, 'kind' => 'halt', 'reference' => 'HLD-'.Str::upper(Str::substr($hold->id, -6)),
            'subject' => ['kind' => 'investor', 'label' => 'Aline Uwase'], ...$common,
            'description' => 'Account on high-risk hold: withdrawals, new purchases, secondary trading paused',
            'opened_at' => $kigali($hold), 'age_days' => 3, 'link' => ['url' => '/admin/investors?investor='.$investor['party']->id, 'method' => 'get']])
        ->and($props['exceptions'][1])->toMatchArray(['id' => $order->id, 'kind' => 'halt', 'subject' => ['kind' => 'investor', 'label' => 'Investor without an account'],
            'description' => 'Account restricted by an external order: deposits paused until '.now('Africa/Kigali')->addDays(10)->format('j M Y'),
            'opened_at' => $kigali($order), 'age_days' => 1])
        ->and($props['exceptions'][2])->toMatchArray(['id' => WalletProviderEvent::query()->where('provider_event_id', 'evt-mismatch')->value('id'),
            'kind' => 'variance', 'reference' => 'evt-mismatch', 'subject' => ['kind' => 'account', 'label' => 'Deposit collections (provider clearing)'],
            'description' => 'Investor deposit reported successful for another amount, currency or environment, so the ledger never posted it',
            'amount' => ['currency' => 'RWF', 'amount' => '25000'], 'age_days' => 0, 'owner' => null,
            'link' => ['url' => '/admin/reconciliation', 'method' => 'get']])
        ->and($props['pagination'])->toBe(['next' => null]);

    expect(array_column(($this->page)(exceptionsStaff(['treasury']))['exceptions'], 'link'))
        ->toBe([null, null, ['url' => '/admin/reconciliation', 'method' => 'get']]);
});

it('filters by kind, searches, and opens the rest on request', function (): void {
    ($this->open)();
    $staff = exceptionsStaff(['superadmin']);

    $halts = ($this->page)($staff, ['kind' => 'halt']);
    expect(array_column($halts['exceptions'], 'kind'))->toBe(['halt', 'halt'])
        ->and(array_column($halts['chips'], 'active'))->toBe([false, false, true, false, false, false])
        ->and($halts['counts']['open'])->toBe(3);

    $search = ($this->page)($staff, ['q' => 'aline', 'kind' => 'variance']);
    expect($search['exceptions'])->toBe([])->and($search['search'])->toBe('aline')
        ->and(array_map(fn (array $chip): array => [$chip['count'], $chip['link']['url']], $search['chips']))->toBe([
            [1, '/admin/exceptions?q=aline'], [0, '/admin/exceptions?q=aline&kind=arrears'],
            [1, '/admin/exceptions?q=aline&kind=halt'], [0, '/admin/exceptions?q=aline&kind=variance'], [0, '/admin/exceptions?q=aline&kind=audit'],
            [0, '/admin/exceptions?q=aline&kind=complaint'],
        ]);

    $first = ($this->page)($staff, ['limit' => 1]);
    expect($first['exceptions'])->toHaveCount(1)->and($first['counts']['open'])->toBe(3)
        ->and($first['pagination'])->toBe(['next' => ['url' => '/admin/exceptions?limit=500', 'method' => 'get']])
        ->and(($this->page)($staff, ['q' => 'evt-', 'limit' => 500])['exceptions'])->toHaveCount(1);
});

it('raises a Flash Audit still unfinished after its 24-hour clock until its report is sealed (BRS AUD-R07)', function (): void {
    $fixture = AuditAssignmentFixture::make();
    $offered = AuditAssignmentFixture::request($fixture);
    $staff = exceptionsStaff(['superadmin']);
    $dueAt = CarbonImmutable::parse($offered->state['complete_by']);
    $business = (string) BusinessProfile::query()->whereKey($fixture['business'])->sole()->profile['name'];

    $this->travelTo($dueAt->subMinute());
    expect(($this->page)($staff)['exceptions'])->toBe([]);

    $this->travelTo($dueAt->addMinute());
    $props = ($this->page)($staff, ['kind' => 'audit']);
    expect($props['exceptions'])->toBe([['id' => $offered->id, 'kind' => 'audit', 'reference' => 'AUD-'.Str::upper(Str::substr($offered->id, -6)),
        'subject' => ['kind' => 'business', 'label' => $business], 'note_title' => null,
        'description' => 'Flash audit unfinished after 24 hours: offered to a partner, not yet accepted', 'amount' => null, 'dpd' => null,
        'opened_at' => $dueAt->setTimezone('Africa/Kigali')->toIso8601String(), 'age_days' => (int) $dueAt->setTimezone('Africa/Kigali')->startOfDay()->diffInDays(now('Africa/Kigali')->startOfDay()),
        'owner' => null, 'link' => ['url' => '/admin/businesses?business='.$fixture['business'], 'method' => 'get']]])
        ->and(array_column($props['chips'], 'count', 'key'))->toMatchArray(['all' => 1, 'audit' => 1]);

    $offered->forceFill(['status' => 'operations', 'party_id' => null, 'state' => [...$offered->state, 'status' => 'operations']])->save();
    expect(($this->page)($staff)['exceptions'][0]['description'])->toBe('Flash audit unfinished after 24 hours: no partner found, with Audit Operations');
});

it('names the partner of an accepted Flash Audit left unsealed, and drops it once the report is sealed', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $ready = AuditSealingFixture::ready();
    $assignment = $ready['assignment']->refresh();
    $completeBy = $assignment->state['complete_by'];
    $ready['user']->forceFill(['name' => 'Diane Uwase'])->save();
    $staff = exceptionsStaff(['treasury']);

    $assignment->forceFill(['state' => [...$assignment->state, 'complete_by' => now('UTC')->subSecond()->format('Y-m-d\TH:i:s\Z')]])->save();
    expect(($this->page)($staff)['exceptions'][0])->toMatchArray(['kind' => 'audit',
        'description' => 'Flash audit unfinished after 24 hours: with Diane Uwase, report not sealed', 'age_days' => 0]);

    $assignment->forceFill(['state' => [...$assignment->state, 'complete_by' => $completeBy]])->save();
    AuditSealingFixture::seal($ready);
    $this->travelTo(CarbonImmutable::parse($completeBy)->addHour());
    expect(($this->page)($staff)['exceptions'])->toBe([]);
});

it('names a case it does not know plainly, and shows an empty list before anything is open', function (): void {
    $props = (new StaffExceptionsResource(['variances' => [], 'audits' => [], 'complaints' => [], 'halts' => [['id' => '01k0000000000000000000abcd', 'party_id' => '01k0000000000000000000abce',
        'name' => 'Aline Uwase', 'cause' => 'court_freeze', 'scope' => ['future_scope'], 'effective_at' => '2027-01-22T08:00:00+02:00', 'expires_at' => null,
        'age_days' => 1]], 'search' => '', 'kind' => null, 'limit' => 50, 'roles' => ['treasury'], 'permissions' => ['exceptions.view']]))->resolve(request());

    expect($props['exceptions'][0]['description'])->toBe('Account case Court Freeze: Future Scope paused')
        ->and(($this->page)(exceptionsStaff(['treasury']))['exceptions'])->toBe([])
        ->and((new StaffNavigationResource([]))->resolve(request())['risk'])->toBe(['url' => '/admin/risk', 'method' => 'get']);
});

it('is for Treasury, Compliance and superadmin only, and over API v1 with its own ability', function (): void {
    foreach (['analyst', 'approver'] as $role) {
        $this->actingAs(exceptionsStaff([$role]))->get(route('staff.exceptions.index'))->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.exceptions.index'))->assertForbidden();
    $this->actingAs(exceptionsStaff(['treasury']))->get(route('staff.exceptions.index', ['kind' => 'other']))->assertSessionHasErrors('kind');

    ($this->open)();
    $treasury = exceptionsStaff(['treasury']);
    Sanctum::actingAs($treasury, ['staff:exceptions:read']);
    $this->getJson(route('api.v1.staff.exceptions.index'))->assertOk()->assertJsonPath('data.contract_version', 'staff-exceptions-v1')
        ->assertJsonPath('data.nav.exceptions.url', '/api/v1/staff/exceptions')->assertJsonPath('data.exceptions.2.link.url', '/api/v1/staff/reconciliation')
        ->assertJsonCount(3, 'data.exceptions');
    Sanctum::actingAs($treasury, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.exceptions.index'))->assertForbidden();
});
