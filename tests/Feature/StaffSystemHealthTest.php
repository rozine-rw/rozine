<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\ActivityTrailStore;
use App\Application\Operations\Contracts\ReconciliationStore;
use App\Http\Controllers\StaffSectionController;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

/*
 * System Health from the records that exist: reconciliation, live raises, the underwriting queue
 * and the governed-action journal, graded against the design's tolerance. Signals the platform
 * does not record yet are shown as not tracked, never estimated.
 */

/** @param list<string> $roles */
function healthStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'System health assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/**
 * @param  list<array{id: 'deposits'|'withdrawals', ledger: string, statement: string}>  $accounts
 * @param  list<string>  $raised
 */
function healthReadings(array $accounts, int $breaks, array $raised, int $pending, int $lastHour, int $total): void
{
    app()->instance(ReconciliationStore::class, new class($accounts, $breaks) implements ReconciliationStore
    {
        /** @param list<array{id: 'deposits'|'withdrawals', ledger: string, statement: string}> $accounts */
        public function __construct(private array $accounts, private int $breaks) {}

        public function accounts(DateTimeInterface $asOf): array
        {
            return $this->accounts;
        }

        public function breaks(DateTimeInterface $asOf): array
        {
            return array_fill(0, $this->breaks, ['id' => 'brk', 'account_id' => 'deposits', 'flow' => 'investor', 'kind' => 'conflict',
                'reference' => 'ref', 'amount' => '1', 'opened_at' => '2026-10-15T00:00:00Z', 'party_id' => null, 'business_id' => null]);
        }
    });
    app()->instance(InvestorDealCatalogue::class, new class($raised) implements InvestorDealCatalogue
    {
        /** @param list<string> $raised */
        public function __construct(private array $raised) {}

        public function deals(): array
        {
            return array_map(fn (string $amount): array => ['raised' => ['currency' => 'RWF', 'amount' => $amount]], $this->raised);
        }

        public function deal(string $campaignId): ?array
        {
            return null;
        }

        public function card(string $campaignId): ?array
        {
            return null;
        }

        public function updates(string $campaignId): array
        {
            return [];
        }
    });
    app()->instance(StaffApplicationQueue::class, new class($pending) implements StaffApplicationQueue
    {
        public function __construct(private int $pending) {}

        public function page(string $tab, string $search, ?string $before, int $limit, ?string $applicationId): array
        {
            return ['entries' => [], 'counts' => [$tab => $this->pending]];
        }
    });
    app()->instance(ActivityTrailStore::class, new class($lastHour, $total) implements ActivityTrailStore
    {
        public function __construct(private int $lastHour, private int $total) {}

        public function trail(string $search, ?string $from, ?string $to, int $limit): array
        {
            return ['entries' => [], 'total' => $from === null ? $this->total : $this->lastHour];
        }
    });
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.system-health.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/system-health');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('reads every signal healthy when the books match, the queue is short and actions flow', function (): void {
    healthReadings([['id' => 'deposits', 'ledger' => '500', 'statement' => '500']], 0, ['300000', '200000'], 2, 7, 90);

    $props = ($this->page)(healthStaff(['superadmin']), ['q' => 'ledger']);

    expect($props['contract_version'])->toBe('staff-system-health-v1')->and($props['search'])->toBe('ledger')
        ->and($props['overall'])->toBe(['critical' => 0, 'watch' => 0])
        ->and(array_column($props['signals'], 'key'))->toBe(['ledger', 'capital', 'queue', 'events', 'frozen', 'default_rate', 'arrears', 'outstanding', 'treasury'])
        ->and(array_column($props['signals'], 'status'))->toBe(['healthy', 'healthy', 'healthy', 'healthy', 'healthy', 'untracked', 'untracked', 'untracked', 'untracked'])
        ->and($props['signals'][4]['facts'])->toBe(['accounts' => 0])
        ->and($props['signals'][0]['facts'])->toBe(['accounts' => 1, 'unmatched' => 0, 'breaks' => 0])
        ->and($props['signals'][1]['facts'])->toBe(['amount' => '500000', 'raises' => 2])
        ->and($props['signals'][3]['facts'])->toBe(['last_hour' => 7, 'total' => 90])
        ->and(array_filter(array_column($props['signals'], 'action')))->toBe([])
        ->and($props['nav']['system_health']['url'])->toBe('/admin/system-health');
});

it('puts what needs a look first and links it to the page that resolves it', function (): void {
    healthReadings([['id' => 'deposits', 'ledger' => '500', 'statement' => '400'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']], 1, [], 6, 0, 3);

    $props = ($this->page)(healthStaff(['superadmin']));

    expect($props['overall'])->toBe(['critical' => 1, 'watch' => 2])
        ->and(array_slice(array_column($props['signals'], 'key'), 0, 4))->toBe(['ledger', 'queue', 'events', 'capital'])
        ->and($props['signals'][0]['status'])->toBe('critical')->and($props['signals'][0]['facts']['unmatched'])->toBe(1)
        ->and($props['signals'][0]['action']['url'])->toBe('/admin/reconciliation')
        ->and($props['signals'][1]['action']['url'])->toBe('/admin/applications')
        ->and($props['signals'][2]['action'])->toBeNull();

    healthReadings([['id' => 'deposits', 'ledger' => '500', 'statement' => '500']], 2, [], 0, 1, 1);
    $treasury = ($this->page)(healthStaff(['treasury']));

    expect($treasury['signals'][0])->toMatchArray(['key' => 'ledger', 'status' => 'watch', 'action' => ['url' => '/admin/reconciliation', 'method' => 'get']]);

    // A frozen operator puts frozen accounts on watch; only staff who manage staff get the link.
    $frozen = healthStaff(['analyst']);
    app(ConfigureStaffAccess::class)->handle($frozen->id, false, 'Left the team.', (string) Str::uuid(), ['analyst']);
    $watch = fn (array $props): array => array_values(array_filter($props['signals'], fn (array $signal): bool => $signal['key'] === 'frozen'))[0];
    expect($watch(($this->page)(healthStaff(['superadmin']))))->toBe(['key' => 'frozen', 'status' => 'watch', 'facts' => ['accounts' => 1],
        'action' => ['url' => '/admin/staff', 'method' => 'get']])
        ->and($watch(($this->page)(healthStaff(['treasury'])))['action'])->toBeNull();
});

it('is for operations staff, replaces the pending section, and serves API v1 with its own ability', function (): void {
    healthReadings([], 0, [], 6, 1, 1);

    expect(StaffSectionController::SECTIONS)->not->toHaveKey('system-health');
    $this->actingAs(healthStaff(['analyst']))->get(route('staff.system-health.index'))->assertForbidden();
    $this->actingAs(healthStaff(['approver']))->get('/admin/system-health')->assertForbidden();
    $this->actingAs(healthStaff(['compliance']))->get(route('staff.system-health.index', ['q' => str_repeat('a', 121)]))->assertSessionHasErrors('q');

    $staff = healthStaff(['compliance']);
    Sanctum::actingAs($staff, ['staff:health:read']);
    $this->getJson('/api/v1/staff/system-health')->assertOk()->assertJsonPath('data.contract_version', 'staff-system-health-v1')
        ->assertJsonPath('data.nav.system_health.url', '/api/v1/staff/system-health')
        ->assertJsonPath('data.signals.0.key', 'queue')->assertJsonPath('data.signals.0.action', null);
    Sanctum::actingAs($staff, ['staff:coverage:read']);
    $this->getJson('/api/v1/staff/system-health')->assertForbidden();
});
