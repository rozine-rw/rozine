<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Party;
use App\Models\User;
use App\Models\WalletProviderEvent;
use App\Models\WalletWithdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;

/*
 * Reconciliation (MVP-ADMIN-SCR-04, AC-05) from live records: each provider clearing account
 * beside what the provider's signed outcomes say moved, the last Kigali day's close, and every
 * provider outcome the ledger could not follow, aged and never hidden.
 */

/** @param list<string> $roles */
function reconciliationStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Reconciliation assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** Fires every deferred check as a commit would, then defers them again for the rest of the test. */
function reconciliationCommits(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->travelTo(now('Africa/Kigali')->setTime(10, 0));
    $this->page = function (User $staff): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.reconciliation.index'))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/reconciliation');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('closes yesterday as reconciled when every provider outcome was followed', function (): void {
    $props = ($this->page)(reconciliationStaff(['treasury']));
    $yesterday = now('Africa/Kigali')->subDay()->endOfDay();
    $zero = ['currency' => 'RWF', 'amount' => '0'];

    expect($props['contract_version'])->toBe('staff-reconciliation-v1')
        ->and($props['allowed_actions'])->toBe([])
        ->and($props['nav']['reconciliation'])->toBe(['url' => '/admin/reconciliation', 'method' => 'get'])
        ->and($props['nav']['finance'])->toBe(['url' => '/admin/reconciliation', 'method' => 'get'])
        ->and($props['day_close'])->toBe(['business_date' => $yesterday->format('Y-m-d'), 'state' => 'reconciled', 'reconciled_at' => $yesterday->toIso8601String()])
        ->and($props['accounts'])->toBe([
            ['id' => 'deposits', 'label' => 'Deposit collections (provider clearing)', 'as_of' => $yesterday->toIso8601String(),
                'ledger' => $zero, 'statement' => $zero, 'difference' => $zero, 'feed' => ['state' => 'available']],
            ['id' => 'withdrawals', 'label' => 'Withdrawal payouts (provider clearing)', 'as_of' => $yesterday->toIso8601String(),
                'ledger' => $zero, 'statement' => $zero, 'difference' => $zero, 'feed' => ['state' => 'available']],
        ])
        ->and($props['breaks'])->toBe([]);
});

it('sets the clearing accounts beside the provider and keeps every unfollowed outcome open as a break', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $investor = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($investor, '500000')['data']['intent_id']);
    $mismatched = InvestorWalletFixture::deposit($investor, '20000')['data']['intent_id'];
    InvestorWalletFixture::settle($mismatched, eventId: 'evt-mismatch', amount: '25000');
    $late = InvestorWalletFixture::deposit($investor, '30000')['data']['intent_id'];
    InvestorWalletFixture::settle($late, 'failed');
    InvestorWalletFixture::settle($late, eventId: 'evt-conflict');
    $paid = (string) InvestorWalletFixture::withdraw($investor, '100000')['data']['withdrawal_id'];
    reconciliationCommits();
    InvestorWalletFixture::settleWithdrawal($paid);
    InvestorWalletFixture::settleWithdrawal($paid, 'failed', 'evt-reversed');
    reconciliationCommits();
    $this->travel(2)->days();
    $today = InvestorWalletFixture::deposit($investor, '40000')['data']['intent_id'];
    InvestorWalletFixture::settle($today, eventId: 'evt-today', amount: '1');

    $compliance = ($this->page)(reconciliationStaff(['compliance']));
    $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

    expect($compliance['day_close']['state'])->toBe('open_break')
        ->and(array_map(fn (array $account): array => [$account['ledger'], $account['statement'], $account['difference']], $compliance['accounts']))->toBe([
            [$money('500000'), $money('555000'), $money('-55000')],
            [$money('100000'), $money('0'), $money('100000')],
        ])
        ->and(array_map(fn (array $break): array => [$break['reference'], $break['account_id'], $break['gap']['amount'], $break['age_days']], $compliance['breaks']))->toBe([
            ['evt-mismatch', 'deposits', '25000', 2], ['evt-conflict', 'deposits', '30000', 2], ['evt-reversed', 'withdrawals', '100000', 2], ['evt-today', 'deposits', '1', 0],
        ])
        ->and($compliance['breaks'][0])->toMatchArray(['id' => WalletProviderEvent::query()->where('provider_event_id', 'evt-mismatch')->value('id'),
            'description' => 'Investor deposit reported successful for another amount, currency or environment, so the ledger never posted it',
            'owner' => null, 'link' => ['url' => '/admin/investors?investor='.$investor['party']->id, 'method' => 'get']])
        ->and($compliance['breaks'][1]['description'])->toBe('Investor deposit reported successful after it had failed, so the ledger never posted it')
        ->and($compliance['breaks'][2]['description'])->toBe('Investor withdrawal reported failed after its success was posted to the ledger')
        ->and(WalletWithdrawal::query()->whereKey($paid)->value('provider_charge'))->toBe('0');

    expect(array_unique(array_column(($this->page)(reconciliationStaff(['treasury']))['breaks'], 'link'), SORT_REGULAR))->toBe([null]);
});

it('is for Treasury, Compliance and superadmin only, and over API v1 with its own ability', function (): void {
    foreach (['analyst', 'approver'] as $role) {
        $this->actingAs(reconciliationStaff([$role]))->get(route('staff.reconciliation.index'))->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.reconciliation.index'))->assertForbidden();
    ($this->page)(reconciliationStaff(['superadmin']));

    $treasury = reconciliationStaff(['treasury']);
    Sanctum::actingAs($treasury, ['staff:reconciliation:read']);
    $this->getJson(route('api.v1.staff.reconciliation.index'))->assertOk()->assertJsonPath('data.contract_version', 'staff-reconciliation-v1')
        ->assertJsonPath('data.nav.reconciliation.url', '/api/v1/staff/reconciliation')->assertJsonCount(2, 'data.accounts');
    Sanctum::actingAs($treasury, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.reconciliation.index'))->assertForbidden();
});
