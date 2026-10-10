<?php

declare(strict_types=1);

use App\Application\Environment\Contracts\StagingMailTesterStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\GetInvestorWallet;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessProviderEvent;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\WalletDepositCredit;
use App\Models\WalletProviderEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    Sleep::fake();
    config(['isolation.staging_mail.recipients' => ['tester@staging.rozine.invalid']]);
});

/** Runs the rest of the test as the UAT (staging) profile, with every other isolation rule real. */
function asStaging(): void
{
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
}

/**
 * A verified Investor, owner-approved as a staging tester, prepared by `staging:wallet`.
 *
 * @return array{user: User, method: InvestorFundingMethod, declining: InvestorFundingMethod}
 */
function stagingTester(): array
{
    $user = InvestorWalletFixture::investor('tester@staging.rozine.invalid')['user'];
    expect(Artisan::call('staging:wallet', ['email' => 'Tester@staging.rozine.invalid', '--declining' => true]))->toBe(0);
    $methods = InvestorFundingMethod::query()->where('party_id', $user->party_id)->orderBy('id')->get();

    return ['user' => $user, 'method' => $methods->firstWhere('label', 'MTN MoMo'), 'declining' => $methods->firstWhere('label', 'MTN MoMo (test: declines)')];
}

/** @return array<string, mixed> */
function stagingDeposit(InvestorFundingMethod|BusinessFundingMethod $method, string $amount = '50000'): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'amount' => ['currency' => 'RWF', 'amount' => $amount], 'method_id' => $method->id];
}

it('settles a staging deposit a few seconds after the response, through the signed provider event, crediting it once', function (): void {
    asStaging();
    $tester = stagingTester();
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method']))->assertOk()
        ->assertJsonPath('code', 'DEPOSIT_INTENT_RECORDED');

    $event = WalletProviderEvent::query()->sole();
    expect([$event->state, $event->disposition, $event->environment, $event->amount])->toBe(['succeeded', 'applied', 'uat', '50000'])
        ->and($event->evidence['signature'] ?? null)->toBeString()
        ->and(WalletDepositCredit::query()->count())->toBe(1)
        ->and(app(GetInvestorWallet::class)->handle($tester['user']->id, 1)['wallet']['total']['amount'])->toBe('50000');
    Sleep::assertSlept(fn ($duration): bool => (float) $duration->totalSeconds === 3.0, 1);

    $this->getJson(route('investor.wallet.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'wallet.deposit', 'identity_context_revision' => 1]));
    expect(WalletProviderEvent::query()->count())->toBe(1)->and(WalletDepositCredit::query()->count())->toBe(1);
});

it('declines a staging deposit paid from the declining test number and credits nothing', function (): void {
    asStaging();
    $tester = stagingTester();
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['declining'], '20000'))->assertOk();

    expect(WalletProviderEvent::query()->sole()->only(['state', 'disposition']))->toBe(['state' => 'failed', 'disposition' => 'applied'])
        ->and(WalletDepositCredit::query()->count())->toBe(0)
        ->and(app(GetInvestorWallet::class)->handle($tester['user']->id, 1)['wallet']['total']['amount'])->toBe('0');
});

it('settles a staging Business deposit through the Business provider path', function (): void {
    asStaging();
    $authority = BusinessAuthorityFixture::make('organization', 1, 'COMPANY-'.Str::ulid(), 1);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business->id]);
    $this->actingAs($authority['users'][0])->postJson(route('business.wallet.deposit', $business->id), stagingDeposit($method, '30000'))->assertOk();

    expect(BusinessProviderEvent::query()->sole()->only(['state', 'disposition', 'environment', 'amount']))
        ->toBe(['state' => 'succeeded', 'disposition' => 'applied', 'environment' => 'uat', 'amount' => '30000'])
        ->and(BusinessDepositCredit::query()->count())->toBe(1)->and(WalletProviderEvent::query()->count())->toBe(0);
});

it('leaves local and testing deposits to the manual event hook', function (): void {
    $tester = stagingTester();
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method']))->assertOk();

    expect(WalletProviderEvent::query()->count())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(0);
    Sleep::assertNeverSlept();
});

it('prepares an approved staging tester once, with the synthetic policy and an optional declining number', function (): void {
    asStaging();
    $tester = stagingTester();
    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--declining' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('settles by itself a few seconds')->toContain($tester['method']->id)->toContain($tester['declining']->id)
        ->and(InvestorFundingMethod::query()->where('party_id', $tester['user']->party_id)->count())->toBe(2)
        ->and(DepositPolicy::query()->sole()->only(['synthetic', 'status', 'fee', 'minimum', 'maximum']))
        ->toBe(['synthetic' => true, 'status' => 'active', 'fee' => '0', 'minimum' => '1000', 'maximum' => '5000000'])
        ->and([$tester['method']->masked, $tester['declining']->masked])->toBe(['+250 788 ···· 456', '+250 788 ···· 000']);

    $named = InvestorWalletFixture::investor('named@elsewhere.invalid')['user'];
    $declining = InvestorFundingMethod::factory()->create(['party_id' => $named->party_id, 'label' => SyntheticWalletFixtures::DECLINING_LABEL]);
    app()->instance(StagingMailTesterStore::class, Mockery::mock(StagingMailTesterStore::class, ['includes' => true]));
    expect(Artisan::call('staging:wallet', ['email' => 'named@elsewhere.invalid']))->toBe(0)
        ->and(Artisan::output())->not->toContain($declining->id)
        ->and(InvestorFundingMethod::query()->where('party_id', $named->party_id)->pluck('label')->sort()->values()->all())
        ->toBe(['MTN MoMo', SyntheticWalletFixtures::DECLINING_LABEL]);
});

it('refuses a staging account that is not an approved tester or not a verified Investor, and every profile without synthetic money', function (Closure $setUp, string $message): void {
    $email = $setUp();
    expect(Artisan::call('staging:wallet', ['email' => $email]))->toBe(1)->and(Artisan::output())->toContain($message)
        ->and(InvestorFundingMethod::query()->count())->toBe(0)->and(DepositPolicy::query()->count())->toBe(0);
})->with([
    'not an approved tester' => [function (): string {
        asStaging();
        InvestorWalletFixture::investor('someone@staging-elsewhere.invalid');

        return 'someone@staging-elsewhere.invalid';
    }, 'WALLET_STAGING_TESTER_REQUIRED'],
    'no such account' => [function (): string {
        asStaging();

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_INVESTOR_REQUIRED'],
    'no Party' => [function (): string {
        asStaging();
        User::factory()->create(['email' => 'tester@staging.rozine.invalid']);

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_INVESTOR_REQUIRED'],
    'identity not verified yet' => [function (): string {
        asStaging();
        $party = Party::factory()->create();
        User::factory()->for($party)->create(['email' => 'tester@staging.rozine.invalid']);
        RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_INVESTOR_REQUIRED'],
    'demo' => [function (): string {
        app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'demo']));
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_SYNTHETIC_ONLY'],
    'production' => [function (): string {
        app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'production']));
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_SYNTHETIC_ONLY'],
    'live money on' => [function (): string {
        asStaging();
        config(['isolation.live_money_enabled' => true]);
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_SYNTHETIC_ONLY'],
]);

it('keeps the local account seeder off staging, so no account with a known password is made there', function (): void {
    asStaging();
    expect(Artisan::call('local:wallet', ['--seed' => 'tester@staging.rozine.invalid']))->toBe(1)
        ->and(User::query()->where('email', 'tester@staging.rozine.invalid')->exists())->toBeFalse();
});
