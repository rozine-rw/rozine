<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Notes register: every published raise as a note, by the same rule the dashboard counts its
 * notes, so the two never contradict each other.
 */

/** @param list<string> $roles */
function notesStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Notes assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** @return array<string, mixed> */
function notesCard(string $id, string $name, string $funded = '50.0'): array
{
    return ['campaign_id' => $id, 'name' => $name, 'industry' => 'Agriculture', 'rate_pct' => '15', 'term_months' => 12, 'funded_pct' => $funded,
        'raised' => ['currency' => 'RWF', 'amount' => '3000000'], 'target' => ['currency' => 'RWF', 'amount' => '6000000'], 'investors' => 3];
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.notes.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/notes');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('lists a live publication as a live note, agreeing with the dashboard', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $evidence = $campaign->payload['public_evidence'];
    $staff = notesStaff(['analyst']);
    $props = ($this->page)($staff);

    expect($props['contract_version'])->toBe('staff-notes-v1')->and($props['allowed_actions'])->toBe([])
        ->and($props['nav']['notes'])->toBe(['url' => '/admin/notes', 'method' => 'get'])
        ->and(array_column($props['stats'], 'value', 'key'))->toBe(['total' => ['kind' => 'count', 'value' => 1],
            'live' => ['kind' => 'count', 'value' => 1], 'repaying' => ['kind' => 'count', 'value' => 0],
            'raised' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '0']]])
        ->and($props['notes'])->toHaveCount(1)
        ->and($props['notes'][0])->toMatchArray(['id' => $campaign->id, 'reference' => 'NTE-'.Str::upper(Str::substr($campaign->id, -6)),
            'business' => $evidence['business']['name'], 'state' => 'live', 'rate_pct' => $campaign->payload['quote']['rate_pct'],
            'term_months' => $campaign->payload['quote']['term_months'], 'target' => ['currency' => 'RWF', 'amount' => $campaign->payload['principal']]]);
    $this->get(route('staff.dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('kpis.0.value.value.amount', '0'));
});

it('counts every tab, filters by state and search, and pages', function (): void {
    app()->instance(InvestorDealCatalogue::class, new class implements InvestorDealCatalogue
    {
        public function deals(): array
        {
            return [];
        }

        public function deal(string $campaignId): ?array
        {
            return null;
        }

        public function card(string $campaignId): array
        {
            return notesCard($campaignId, match ($campaignId) {
                '01K0000000000000000000AAAA' => 'Ubuki Crafts', '01K0000000000000000000BBBB' => 'GreenLeaf Agro', default => 'Kivu Fisheries',
            }, match ($campaignId) {
                '01K0000000000000000000AAAA' => '25.0', '01K0000000000000000000BBBB' => '100.0', default => '50.0',
            });
        }

        public function updates(string $campaignId): array
        {
            return [];
        }
    });
    app()->instance(OperationsDashboardStore::class, Mockery::mock(OperationsDashboardStore::class, [
        'noteStates' => [['campaign_id' => '01K0000000000000000000AAAA', 'state' => 'live', 'live_at' => '2026-10-15T08:00:00+00:00'],
            ['campaign_id' => '01K0000000000000000000BBBB', 'state' => 'repaying', 'live_at' => '2026-10-01T08:00:00+00:00'],
            ['campaign_id' => '01K0000000000000000000CCCC', 'state' => 'failed', 'live_at' => '2026-09-01T08:00:00+00:00']],
        'figures' => ['capital_raised' => '6000000', 'active_businesses' => 0, 'disbursements_waiting' => 0, 'disbursed' => '0',
            'notes' => ['live' => 1, 'funded' => 0, 'repaying' => 1, 'failed' => 1]]]));
    $staff = notesStaff(['treasury']);
    $props = ($this->page)($staff);

    expect(array_column($props['tabs'], 'count', 'key'))->toBe(['all' => 3, 'live' => 1, 'repaying' => 1, 'matured' => 0, 'failed' => 1])
        ->and($props['tabs'][2]['link'])->toBe(['url' => '/admin/notes?tab=repaying', 'method' => 'get'])
        ->and(array_column($props['stats'], 'value', 'key')['raised'])->toBe(['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '6000000']])
        ->and(array_column($props['notes'], 'state'))->toBe(['live', 'repaying', 'failed']);

    $repaying = ($this->page)($staff, ['tab' => 'repaying']);
    expect(array_column($repaying['notes'], 'business'))->toBe(['GreenLeaf Agro'])
        ->and(array_values(array_filter($repaying['tabs'], fn (array $tab): bool => $tab['active']))[0]['key'])->toBe('repaying')
        ->and(array_column(($this->page)($staff, ['q' => 'kivu'])['notes'], 'business'))->toBe(['Kivu Fisheries'])
        ->and(($this->page)($staff, ['limit' => 1])['pagination']['next'])->toBe(['url' => '/admin/notes?limit=500', 'method' => 'get'])
        ->and($props['sort'])->toBe('newest');

    // Largest first; ties keep the newest-first order, and the sort rides on every tab link.
    $funded = ($this->page)($staff, ['sort' => 'funded']);
    expect(array_column($funded['notes'], 'business'))->toBe(['GreenLeaf Agro', 'Kivu Fisheries', 'Ubuki Crafts'])
        ->and($funded['sort'])->toBe('funded')
        ->and($funded['tabs'][1]['link'])->toBe(['url' => '/admin/notes?sort=funded&tab=live', 'method' => 'get'])
        ->and(array_column(($this->page)($staff, ['sort' => 'term'])['notes'], 'business'))->toBe(['Ubuki Crafts', 'GreenLeaf Agro', 'Kivu Fisheries']);
    foreach (['raised', 'yield', 'investors'] as $sort) {
        expect(array_column(($this->page)($staff, ['sort' => $sort])['notes'], 'business'))->toBe(['Ubuki Crafts', 'GreenLeaf Agro', 'Kivu Fisheries']);
    }
    $this->actingAs($staff)->get(route('staff.notes.index', ['sort' => 'oldest']))->assertSessionHasErrors('sort');
});

it('is for staff who may view the primary market', function (): void {
    $this->actingAs(User::factory()->withTwoFactor()->create())->get(route('staff.notes.index'))->assertForbidden();
    $this->actingAs(notesStaff(['analyst']))->get(route('staff.notes.index', ['tab' => 'everything']))->assertSessionHasErrors('tab');
});
