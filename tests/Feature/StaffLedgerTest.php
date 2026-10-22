<?php

declare(strict_types=1);

use App\Http\Resources\StaffLedgerResource;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\User;
use App\Models\WalletDepositIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\StaffFixture;

/*
 * The Admin Ledger from the live wallet ledger: every posting newest first, where the money came
 * from and went to, the reference it moved under, and one entry opened to its balanced lines.
 */

/** @param list<string> $roles */
function ledgerStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, $roles, 'Ledger assignment.');

    return $user;
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(fee: '500', minimum: '1500');
    $this->investor = InvestorWalletFixture::ready();
    $this->investor['user']->forceFill(['name' => 'Aline Uwase'])->save();
    $this->deposit = function (string $amount): WalletDepositIntent {
        $intent = InvestorWalletFixture::deposit($this->investor, $amount)['data']['intent_id'];
        InvestorWalletFixture::settle($intent);

        return WalletDepositIntent::query()->whereKey($intent)->sole();
    };
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.ledger.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/ledger');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('lists each posting with where the money came from and went to, and opens it to its balanced lines', function (): void {
    $intent = ($this->deposit)('50000');
    $entry = (string) LedgerEntry::query()->where('kind', 'deposit_credit')->value('id');
    $staff = ledgerStaff(['treasury']);
    $props = ($this->page)($staff);
    $row = ['id' => $entry, 'at' => now('UTC')->toIso8601String(), 'kind' => 'deposit', 'from' => 'Deposit clearing (provider)', 'to' => 'Aline Uwase · available',
        'reference' => $intent->provider_reference, 'amount' => ['currency' => 'RWF', 'amount' => '50000'],
        'link' => ['url' => '/admin/ledger?entry='.$entry, 'method' => 'get']];

    expect($props['contract_version'])->toBe('staff-ledger-v1')
        ->and($props['nav']['ledger'])->toBe(['url' => '/admin/ledger', 'method' => 'get'])
        ->and($props['entries'])->toBe([$row])->and($props['total'])->toBe(1)->and($props['more'])->toBeNull()->and($props['entry'])->toBeNull()
        ->and($props['older'])->toBeNull()->and($props['newest'])->toBeNull();

    $opened = ($this->page)($staff, ['entry' => $entry])['entry'];
    $account = Str::upper(Str::substr((string) DB::table('ledger_lines')->where('entry_id', $entry)->where('direction', 'credit')
        ->orderByDesc('amount')->value('account_id'), -6));
    $operation = (string) LedgerEntry::query()->whereKey($entry)->value('origin_operation_id');
    $started = CommandOperation::query()->whereKey($operation)->sole();
    expect($started->actor_key)->toBe('party:'.$this->investor['user']->party_id);
    expect($opened)->toMatchArray([...$row, 'operation_id' => $operation, 'contra_of' => null, 'contra_by' => null, 'balanced' => true,
        'posted_by' => ['actor' => 'System · deposit credit', 'at' => $row['at'], 'reason' => null],
        'origin' => ['actor' => 'PARTY-'.Str::upper(Str::substr((string) $this->investor['user']->party_id, -8)), 'at' => $started->created_at?->toIso8601String()],
        'totals' => ['debit' => ['currency' => 'RWF', 'amount' => '50000'], 'credit' => ['currency' => 'RWF', 'amount' => '50000']],
        'links' => ['close' => ['url' => '/admin/ledger', 'method' => 'get'], 'events' => ['url' => '/admin/activity?q='.$operation, 'method' => 'get']]])
        ->and($opened['postings'])->toEqualCanonicalizing([
            ['account' => 'Deposit clearing (provider)', 'account_code' => 'DEP-CLR', 'debit' => ['currency' => 'RWF', 'amount' => '50000'], 'credit' => null],
            ['account' => 'Aline Uwase · available', 'account_code' => 'INV-AVL-'.$account, 'debit' => null, 'credit' => ['currency' => 'RWF', 'amount' => '49500']],
            ['account' => 'Deposit fee revenue', 'account_code' => 'DEP-FEE', 'debit' => null, 'credit' => ['currency' => 'RWF', 'amount' => '500']],
        ]);
});

it('searches by owner, reference, kind or entry, and opens every match on request', function (): void {
    $first = ($this->deposit)('20000');
    ($this->deposit)('30000');
    ($this->deposit)('40000');
    $staff = ledgerStaff(['compliance']);

    expect(array_column(($this->page)($staff, ['q' => 'aline'])['entries'], 'amount'))->toBe([
        ['currency' => 'RWF', 'amount' => '40000'], ['currency' => 'RWF', 'amount' => '30000'], ['currency' => 'RWF', 'amount' => '20000']])
        ->and(array_column(($this->page)($staff, ['q' => $first->provider_reference])['entries'], 'reference'))->toBe([$first->provider_reference])
        ->and(($this->page)($staff, ['q' => 'deposit_credit'])['total'])->toBe(3)
        ->and(($this->page)($staff, ['q' => 'nobody'])['entries'])->toBe([]);

    $page = ($this->page)($staff, ['q' => 'aline', 'limit' => 2]);
    expect($page['entries'])->toHaveCount(2)->and($page['total'])->toBe(3)
        ->and($page['more'])->toBe(['url' => '/admin/ledger?q=aline&limit=500', 'method' => 'get'])
        ->and($page['entries'][0]['link']['url'])->toBe('/admin/ledger?q=aline&limit=2&entry='.$page['entries'][0]['id'])
        ->and(($this->page)($staff, ['q' => 'aline', 'limit' => 2, 'entry' => $page['entries'][0]['id']])['entry']['links']['close'])
        ->toBe(['url' => '/admin/ledger?q=aline&limit=2', 'method' => 'get'])
        ->and(($this->page)($staff, ['limit' => 500])['more'])->toBeNull();
});

it('pages back from the last row shown, keeping the search, and returns to the newest page', function (): void {
    ($this->deposit)('20000');
    ($this->deposit)('30000');
    ($this->deposit)('40000');
    $staff = ledgerStaff(['treasury']);

    $newest = ($this->page)($staff, ['q' => 'aline', 'limit' => 2]);
    $last = $newest['entries'][1]['id'];
    expect(array_column(array_column($newest['entries'], 'amount'), 'amount'))->toBe(['40000', '30000'])
        ->and($newest['older'])->toBe(['url' => '/admin/ledger?q=aline&limit=2&before='.$last, 'method' => 'get'])
        ->and($newest['newest'])->toBeNull();

    $older = ($this->page)($staff, ['q' => 'aline', 'limit' => 2, 'before' => $last]);
    $oldest = $older['entries'][0]['id'];
    expect(array_column(array_column($older['entries'], 'amount'), 'amount'))->toBe(['20000'])
        ->and($older['total'])->toBe(3)->and($older['older'])->toBeNull()->and($older['more'])->toBeNull()
        ->and($older['newest'])->toBe(['url' => '/admin/ledger?q=aline&limit=2', 'method' => 'get'])
        ->and($older['entries'][0]['link']['url'])->toBe('/admin/ledger?q=aline&limit=2&before='.$last.'&entry='.$oldest)
        ->and(($this->page)($staff, ['q' => 'aline', 'limit' => 2, 'before' => $last, 'entry' => $oldest])['entry']['links']['close'])
        ->toBe(['url' => '/admin/ledger?q=aline&limit=2&before='.$last, 'method' => 'get']);

    $this->actingAs($staff)->get(route('staff.ledger.index', ['before' => 'nope']))->assertSessionHasErrors('before');
});

it('names an owner, account or kind it does not know plainly', function (): void {
    $request = request();
    $line = fn (string $direction, string $amount, string $kind, ?string $owner, ?string $name): array => ['direction' => $direction, 'amount' => $amount,
        'account_id' => '01k0000000000000000000abcd', 'account_kind' => $kind, 'owner' => $owner, 'owner_name' => $name];
    $entry = ['id' => '01k00000000000000000000001', 'kind' => 'future_kind', 'at' => '2027-01-22T08:00:00+00:00', 'reference' => null, 'operation_id' => null,
        'lines' => [$line('debit', '700', 'business_available', 'business', null), $line('debit', '300', 'future_account', null, null)]];
    $props = (new StaffLedgerResource(['entries' => [$entry], 'total' => 1, 'next_before' => null, 'entry' => [...$entry, 'origin' => null], 'search' => '',
        'limit' => 50, 'before' => null, 'roles' => ['treasury'], 'permissions' => ['ledger.view']]))->resolve($request);

    expect($props['entries'][0])->toMatchArray(['kind' => 'investment', 'from' => 'Business · available', 'to' => '—', 'reference' => $entry['id'],
        'amount' => ['currency' => 'RWF', 'amount' => '1000']])
        ->and($props['entry']['balanced'])->toBeFalse()->and($props['entry']['origin'])->toBeNull()
        ->and(array_column($props['entry']['postings'], 'account'))->toBe(['Business · available', 'Future account'])
        ->and(array_column($props['entry']['postings'], 'account_code'))->toBe(['BIZ-AVL-00ABCD', 'FUTURE_ACCOUNT']);
});

it('shows the newest postings as the Operations Center activity, for ledger viewers only', function (): void {
    ($this->deposit)('20000');
    $entry = (string) LedgerEntry::query()->where('kind', 'deposit_credit')->value('id');

    $this->actingAs(ledgerStaff(['treasury']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('activity', [['id' => $entry, 'kind' => 'deposit',
            'summary' => 'Deposit clearing (provider) → Aline Uwase · available', 'amount' => ['currency' => 'RWF', 'amount' => '20000'],
            'at' => now('UTC')->toIso8601String(), 'link' => ['url' => '/admin/ledger?entry='.$entry, 'method' => 'get']]])
            ->where('nav.ledger.url', '/admin/ledger'));
    $this->actingAs(ledgerStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('activity', null)->where('nav.ledger', null));
});

it('is for Treasury, Compliance and superadmin only, and over API v1 with its own ability', function (): void {
    foreach (['analyst', 'approver'] as $role) {
        $this->actingAs(ledgerStaff([$role]))->get(route('staff.ledger.index'))->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.ledger.index'))->assertForbidden();
    ($this->page)(ledgerStaff(['superadmin']));
    $this->actingAs(ledgerStaff(['treasury']))->get(route('staff.ledger.index', ['entry' => strtolower((string) Str::ulid())]))->assertNotFound();
    $this->actingAs(ledgerStaff(['treasury']))->get(route('staff.ledger.index', ['entry' => 'nope']))->assertSessionHasErrors('entry');

    ($this->deposit)('20000');
    $treasury = ledgerStaff(['treasury']);
    Sanctum::actingAs($treasury, ['staff:ledger:read']);
    $this->getJson(route('api.v1.staff.ledger.index'))->assertOk()->assertJsonPath('data.contract_version', 'staff-ledger-v1')
        ->assertJsonPath('data.nav.ledger.url', '/api/v1/staff/ledger')->assertJsonCount(1, 'data.entries');
    Sanctum::actingAs($treasury, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.ledger.index'))->assertForbidden();
});

it('grants the ledger view only to Treasury, Compliance and superadmin, per the #99 P2 signoff', function (): void {
    ($this->deposit)('20000');

    foreach (['treasury', 'compliance', 'superadmin'] as $role) {
        $this->actingAs(ledgerStaff([$role]))->get(route('staff.ledger.index'))->assertOk();
    }
    foreach (['analyst', 'approver'] as $role) {
        $this->actingAs(ledgerStaff([$role]))->get(route('staff.ledger.index'))->assertForbidden();
    }
});
