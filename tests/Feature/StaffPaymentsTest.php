<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Disbursement;
use App\Models\User;
use App\Models\WalletWithdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * The Payment Center from live money movement: credited deposits, paid-out withdrawals and closed
 * disbursements over the last fourteen Kigali days, and the withdrawals still in flight, with no
 * release, hold or reverse command.
 */

/** @param list<string> $roles */
function paymentsStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Payment center assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** Fires the deferred ledger checks, as a commit would. */
function paymentsCommit(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

beforeEach(function (): void {
    $this->freezeSecond();
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.payments.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/payments');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('sums the last fourteen days of deposits and payouts, charts them per day and lists the payouts in flight', function (): void {
    InvestorWalletFixture::policy(fee: '500', minimum: '1500');
    $investor = InvestorWalletFixture::funded('300000');
    $investor['user']->forceFill(['name' => 'Aline Uwase'])->save();
    $paid = (string) InvestorWalletFixture::withdraw($investor, '100000')['data']['withdrawal_id'];
    paymentsCommit();
    InvestorWalletFixture::settleWithdrawal($paid);
    $held = (string) InvestorWalletFixture::withdraw($investor, '20000')['data']['withdrawal_id'];
    $unknown = (string) InvestorWalletFixture::withdraw($investor, '5000')['data']['withdrawal_id'];
    paymentsCommit();
    InvestorWalletFixture::settleWithdrawal($unknown, 'unknown');

    $props = ($this->page)(paymentsStaff(['treasury']));
    $method = $investor['method']->label.' '.$investor['method']->masked;
    $row = fn (string $id, string $amount, string $state): array => ['id' => $id, 'reference' => 'WDR-'.Str::upper(Str::substr($id, -6)),
        'owner' => 'investor', 'name' => 'Aline Uwase', 'amount' => ['currency' => 'RWF', 'amount' => $amount],
        'received' => ['currency' => 'RWF', 'amount' => $amount], 'account' => $method, 'state' => $state,
        'requested_at' => WalletWithdrawal::query()->whereKey($id)->sole()->created_at->utc()->toIso8601String()];

    expect($props)->toMatchArray(['contract_version' => 'staff-payments-v1', 'allowed_actions' => [], 'in_flight' => 2,
        'from' => now('Africa/Kigali')->startOfDay()->subDays(13)->toIso8601String(), 'pagination' => ['next' => null]])
        ->and($props['stats'])->toBe([
            ['key' => 'deposits', 'value' => ['currency' => 'RWF', 'amount' => '300500'], 'count' => 1],
            ['key' => 'withdrawals', 'value' => ['currency' => 'RWF', 'amount' => '100000'], 'count' => 1],
            ['key' => 'disbursed', 'value' => ['currency' => 'RWF', 'amount' => '0'], 'count' => 0]])
        ->and($props['bars'])->toBe([['label' => now('Africa/Kigali')->format('m-d'), 'amount' => ['currency' => 'RWF', 'amount' => '400500'], 'height_pct' => 100]])
        ->and($props['payouts'])->toBe([$row($held, '20000', 'pending'), $row($unknown, '5000', 'unknown')])
        ->and($props['nav']['payments'])->toBe(['url' => '/admin/payments', 'method' => 'get']);

    expect(($this->page)(paymentsStaff(['compliance']), ['q' => Str::substr($unknown, -6)])['payouts'])->toHaveCount(1)
        ->and(($this->page)(paymentsStaff(['superadmin']), ['q' => 'aline', 'limit' => 1]))
        ->toMatchArray(['search' => 'aline', 'pagination' => ['next' => ['url' => '/admin/payments?q=aline&limit=500', 'method' => 'get']]])
        ->and(($this->page)(paymentsStaff(['treasury']), ['q' => 'nobody'])['payouts'])->toBe([]);
});

it('counts a disbursement that closed with issued notes, and leaves out movement older than fourteen days', function (): void {
    PrimaryIssuanceFixture::close();
    $disbursement = Disbursement::query()->sole();

    expect(($this->page)(paymentsStaff(['superadmin']))['stats'][2])
        ->toBe(['key' => 'disbursed', 'value' => ['currency' => 'RWF', 'amount' => (string) $disbursement->amount], 'count' => 1]);

    $this->travel(15)->days();
    $props = ($this->page)(paymentsStaff(['treasury']));
    expect(array_column(array_column($props['stats'], 'value'), 'amount'))->toBe(['0', '0', '0'])
        ->and($props['bars'])->toBe([])->and($props['payouts'])->toBe([]);
});

it('is for Treasury, Compliance and superadmin only, and over API v1 with its own ability', function (): void {
    foreach (['analyst', 'approver'] as $role) {
        $staff = paymentsStaff([$role]);
        $this->actingAs($staff)->get(route('staff.payments.index'))->assertForbidden();
        expect($this->actingAs($staff)->get(route('staff.dashboard'))->viewData('page')['props']['nav']['payments'])
            ->toBe($role === 'approver' ? ['url' => '/admin/disbursements', 'method' => 'get'] : null);
    }
    $this->actingAs(paymentsStaff(['treasury']))->get(route('staff.payments.index', ['limit' => 0]))->assertSessionHasErrors('limit');

    Sanctum::actingAs(paymentsStaff(['treasury']), ['staff:payments:read']);
    $this->getJson('/api/v1/staff/payments')->assertOk()->assertJsonPath('data.contract_version', 'staff-payments-v1')
        ->assertJsonPath('data.nav.payments.url', '/api/v1/staff/payments');
    Sanctum::actingAs(paymentsStaff(['treasury']), ['staff:ledger:read']);
    $this->getJson('/api/v1/staff/payments')->assertForbidden();
});
