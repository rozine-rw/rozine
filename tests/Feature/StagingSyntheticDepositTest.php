<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\BusinessWalletStore;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticSettlement;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\Contracts\SyntheticWalletTesters;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\GetBusinessWallet;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Application\Wallet\RecordDepositIntent;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessDepositDispatch;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessProviderEvent;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StagingMailTester;
use App\Models\SyntheticWalletTesterChange;
use App\Models\User;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    Sleep::fake();
    config(['isolation.staging_mail.recipients' => ['tester@staging.rozine.invalid']]);
});

/** Runs the rest of the test as a fresh UAT (staging) process, with every other isolation rule real. */
function asStaging(): void
{
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    app()->forgetInstance(SyntheticSettlement::class);
}

/**
 * A verified Investor named as a wallet tester, and prepared, by `staging:wallet`.
 *
 * @return array{user: User, method: InvestorFundingMethod, declining: InvestorFundingMethod}
 */
function stagingTester(): array
{
    $user = InvestorWalletFixture::investor('tester@staging.rozine.invalid')['user'];
    expect(Artisan::call('staging:wallet', ['email' => 'Tester@staging.rozine.invalid', '--declining' => true, '--reason' => 'UAT wallet test']))->toBe(0);
    $methods = InvestorFundingMethod::query()->where('party_id', $user->party_id)->orderBy('id')->get();

    return ['user' => $user, 'method' => $methods->firstWhere('label', 'MTN MoMo'), 'declining' => $methods->firstWhere('label', 'MTN MoMo (test: declines)')];
}

/**
 * A configured Business whose mandate holder may deposit from a verified synthetic method, under the synthetic policy.
 *
 * @return array{user: User, business: BusinessProfile, method: BusinessFundingMethod}
 */
function stagingBusiness(): array
{
    $authority = BusinessAuthorityFixture::make('organization', 1, 'COMPANY-'.Str::ulid(), 1);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::query()->exists() || DepositPolicy::factory()->create(SyntheticWalletFixtures::POLICY_TERMS);

    return ['user' => $authority['users'][0], 'business' => $business, 'method' => BusinessFundingMethod::factory()->create(['business_id' => $business->id])];
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

it('refuses a new Business deposit on UAT and still settles one accepted before, through the Business provider path', function (): void {
    $business = stagingBusiness();
    $this->actingAs($business['user'])->postJson(route('business.wallet.deposit', $business['business']->id), stagingDeposit($business['method'], '30000'))->assertOk();
    expect(app(GetBusinessWallet::class)->handle($business['user']->id, 1, $business['business']->id)['allowed_actions'])->toBe(['business.wallet.deposit']);

    asStaging();
    $this->postJson(route('business.wallet.deposit', $business['business']->id), stagingDeposit($business['method'], '40000'))
        ->assertForbidden()->assertJsonPath('code', 'SYNTHETIC_BUSINESS_FUNDING_OFF');
    expect(BusinessDepositIntent::query()->count())->toBe(1)
        ->and(app(GetBusinessWallet::class)->handle($business['user']->id, 1, $business['business']->id)['allowed_actions'])->toBe([]);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 1 synthetic deposits; 0 left for the next run.')
        ->and(BusinessProviderEvent::query()->sole()->only(['state', 'disposition', 'environment', 'amount']))
        ->toBe(['state' => 'succeeded', 'disposition' => 'applied', 'environment' => 'uat', 'amount' => '30000'])
        ->and(BusinessDepositCredit::query()->count())->toBe(1)->and(WalletProviderEvent::query()->count())->toBe(0);
});

it('leaves local and testing deposits to the manual event hook', function (): void {
    $tester = stagingTester();
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method']))->assertOk();

    expect(app(SyntheticSettlement::class)->enabled())->toBeFalse()
        ->and(app(SyntheticSettlement::class)->settlePending())->toBe(['settled' => 0, 'failed' => 0, 'remaining' => 0])
        ->and(WalletProviderEvent::query()->count())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(0);
    Sleep::assertNeverSlept();
});

it('names a staging tester once, with an audited reason, the synthetic policy and an optional declining number', function (): void {
    asStaging();
    $tester = stagingTester();
    $grant = SyntheticWalletTesterChange::query()->sole();
    expect($grant->only(['party_id', 'user_id', 'action', 'reason', 'actor_key']))
        ->toBe(['party_id' => $tester['user']->party_id, 'user_id' => $tester['user']->id, 'action' => 'granted', 'reason' => 'UAT wallet test', 'actor_key' => 'console'])
        ->and(Str::isUuid($grant->request_id))->toBeTrue();
    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--declining' => true, '--reason' => 'Again']))->toBe(0)
        ->and(Artisan::output())->toContain('settles by itself a few seconds')->toContain($tester['method']->id)->toContain($tester['declining']->id)
        ->toContain('already named')->and(SyntheticWalletTesterChange::query()->count())->toBe(1)
        ->and(InvestorFundingMethod::query()->where('party_id', $tester['user']->party_id)->count())->toBe(2)
        ->and(DepositPolicy::query()->sole()->only(['synthetic', 'status', 'fee', 'minimum', 'maximum']))
        ->toBe(['synthetic' => true, 'status' => 'active', 'fee' => '0', 'minimum' => '1000', 'maximum' => '5000000'])
        ->and([$tester['method']->masked, $tester['declining']->masked])->toBe(['+250 788 ···· 456', '+250 788 ···· 000']);

    $named = InvestorWalletFixture::investor('named@elsewhere.invalid')['user'];
    $declining = InvestorFundingMethod::factory()->create(['party_id' => $named->party_id, 'label' => SyntheticWalletFixtures::DECLINING_LABEL]);
    expect(Artisan::call('staging:wallet', ['email' => 'named@elsewhere.invalid', '--reason' => 'Off the mail list']))->toBe(0)
        ->and(Artisan::output())->not->toContain($declining->id)
        ->and(InvestorFundingMethod::query()->where('party_id', $named->party_id)->pluck('label')->sort()->values()->all())
        ->toBe(['MTN MoMo', SyntheticWalletFixtures::DECLINING_LABEL]);
});

it('refuses a change without a reason, an account that is not a verified Investor, and every profile without synthetic money', function (Closure $setUp, string $message, array $options = ['--reason' => 'UAT wallet test']): void {
    $email = $setUp();
    expect(Artisan::call('staging:wallet', ['email' => $email, ...$options]))->toBe(1)->and(Artisan::output())->toContain($message)
        ->and(InvestorFundingMethod::query()->count())->toBe(0)->and(DepositPolicy::query()->count())->toBe(0)
        ->and(SyntheticWalletTesterChange::query()->count())->toBe(0);
})->with([
    'no reason' => [function (): string {
        asStaging();
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_REASON_REQUIRED', []],
    'a blank reason' => [function (): string {
        asStaging();
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_REASON_REQUIRED', ['--reason' => '   ']],
    'a reason over 1,000 characters' => [function (): string {
        asStaging();
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_REASON_REQUIRED', ['--reason' => str_repeat('a', 1001)]],
    'revoking someone never named' => [function (): string {
        asStaging();
        InvestorWalletFixture::investor('tester@staging.rozine.invalid');

        return 'tester@staging.rozine.invalid';
    }, 'WALLET_STAGING_TESTER_NOT_NAMED', ['--revoke' => true, '--reason' => 'Done']],
    'revoking an unknown account' => [function (): string {
        asStaging();

        return 'nobody@staging.rozine.invalid';
    }, 'WALLET_STAGING_TESTER_NOT_NAMED', ['--revoke' => true, '--reason' => 'Done']],
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

it('recovers a settlement lost to a transient failure, on the next deposit and through the worker, crediting each deposit once', function (): void {
    asStaging();
    $tester = stagingTester();
    $failing = new class
    {
        /** @var list<array<string, mixed>> */
        public array $seen = [];

        /** @param array<string, mixed> $message */
        public function handle(array $message): never
        {
            $this->seen[] = $message;

            throw new RuntimeException('The outcome store is unavailable.');
        }
    };
    app()->instance(ApplyProviderOutcome::class, $failing);
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '10000'))->assertOk();

    expect(WalletDepositDispatch::query()->where('phase', 'acknowledged')->count())->toBe(1)
        ->and(WalletProviderEvent::query()->count())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(0);

    app()->offsetUnset(ApplyProviderOutcome::class);
    $this->travel(5)->minutes();
    $this->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '20000'))->assertOk();
    $first = WalletProviderEvent::query()->where('amount', '10000')->sole();
    expect(WalletDepositCredit::query()->count())->toBe(2)
        ->and(app(GetInvestorWallet::class)->handle($tester['user']->id, 1)['wallet']['total']['amount'])->toBe('30000')
        ->and($failing->seen)->toHaveCount(1)->and($first->evidence)->toBe($failing->seen[0]);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 0 synthetic deposits; 0 left for the next run.')
        ->and(WalletProviderEvent::query()->count())->toBe(2)->and(WalletDepositCredit::query()->count())->toBe(2);
});

it('reports a settlement the worker could not finish and leaves it for the next run', function (): void {
    asStaging();
    $tester = stagingTester();
    app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '10000'], $tester['method']->id);
    app()->bind(ApplyProviderOutcome::class, fn () => throw new RuntimeException('The outcome store is unavailable.'));

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(1)->and(Artisan::output())->toContain('Settled 0 synthetic deposits; 1 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(0);
    app()->offsetUnset(ApplyProviderOutcome::class);
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 1 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(1);
});

it('settles Investor and Business deposits whose process stopped after acknowledging them, once, whoever gets there first', function (): void {
    $business = stagingBusiness();
    app(RecordBusinessDeposit::class)->handle($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '25000'], $business['method']->id);
    asStaging();
    $tester = stagingTester();
    app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '15000'], $tester['method']->id);

    expect(WalletDepositDispatch::query()->where('phase', 'acknowledged')->count())->toBe(1)
        ->and(BusinessDepositDispatch::query()->where('phase', 'acknowledged')->count())->toBe(1)
        ->and(WalletProviderEvent::query()->count() + BusinessProviderEvent::query()->count())->toBe(0);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 2 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(1)->and(BusinessDepositCredit::query()->count())->toBe(1);

    app()->terminate();
    expect(WalletProviderEvent::query()->count())->toBe(1)->and(BusinessProviderEvent::query()->count())->toBe(1)
        ->and(WalletDepositCredit::query()->count())->toBe(1)->and(BusinessDepositCredit::query()->count())->toBe(1);
    Sleep::assertNeverSlept();
});

it('sends Investor and Business deposits lost before dispatch, resends an interrupted send, then settles each once', function (): void {
    $business = stagingBusiness();
    app(BusinessWalletStore::class)->deposit($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '12000'], $business['method']->id);
    asStaging();
    $tester = stagingTester();
    app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '11000'], $tester['method']->id);
    app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '13000'], $tester['method']->id);
    expect(app(WalletStore::class)->claimDispatches(null, 1, true, 'uat'))->toHaveCount(1);
    $this->travel(6)->minutes();

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())
        ->toContain('Claimed 2 deposit dispatches; 2 acknowledged.')
        ->toContain('Claimed 1 Business deposit dispatches; 1 acknowledged.')
        ->toContain('Settled 3 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(2)->and(BusinessDepositCredit::query()->count())->toBe(1);
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 0 synthetic deposits')
        ->and(WalletDepositCredit::query()->count())->toBe(2)->and(BusinessDepositCredit::query()->count())->toBe(1);
});

it('gives each deposit one stable settlement event, so a settlement racing another replays instead of recording a second', function (): void {
    asStaging();
    $tester = stagingTester();
    $intent = app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '10000'], $tester['method']->id);
    $racer = new class(app(ApplyProviderOutcome::class))
    {
        public bool $raced = false;

        public function __construct(private ApplyProviderOutcome $real) {}

        /**
         * Another settlement runs to completion between this one's read and its apply, a few seconds later.
         *
         * @param  array<string, mixed>  $message
         * @return array{disposition: string, state: string, credited: bool, replayed: bool}
         */
        public function handle(array $message): array
        {
            if (! $this->raced) {
                $this->raced = true;
                Carbon::setTestNow(now()->addSeconds(7));
                app(SyntheticSettlement::class)->settlePending();
            }

            return $this->real->handle($message);
        }
    };
    app()->instance(ApplyProviderOutcome::class, $racer);

    expect(app(SyntheticSettlement::class)->settlePending())->toBe(['settled' => 1, 'failed' => 0, 'remaining' => 0]);
    $event = WalletProviderEvent::query()->sole();
    expect($event->provider_event_id)->toBe('synthetic-settlement-'.$intent['data']['intent_id'])
        ->and($event->disposition)->toBe('applied')->and(WalletDepositCredit::query()->count())->toBe(1);
});

it('refuses to prepare a tester while a policy on other terms is in force, changing nothing', function (): void {
    asStaging();
    InvestorWalletFixture::investor('tester@staging.rozine.invalid');
    DepositPolicy::factory()->create(['version' => 'synthetic-other-terms', 'fee' => '500', 'minimum' => '2500', 'maximum' => '1000000']);

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--declining' => true, '--reason' => 'UAT wallet test']))->toBe(1)
        ->and(Artisan::output())->toContain('WALLET_STAGING_POLICY_MISMATCH')->toContain('synthetic-other-terms')
        ->and(InvestorFundingMethod::query()->count())->toBe(0)->and(SyntheticWalletTesterChange::query()->count())->toBe(0)
        ->and(DepositPolicy::query()->sole()->only(['version', 'fee', 'minimum', 'maximum']))
        ->toBe(['version' => 'synthetic-other-terms', 'fee' => '500', 'minimum' => '2500', 'maximum' => '1000000']);
});

it('prepares a tester under a fresh synthetic policy when the policy in force was withdrawn', function (): void {
    asStaging();
    InvestorWalletFixture::investor('tester@staging.rozine.invalid');
    DepositPolicy::factory()->withdrawn()->create(['version' => 'synthetic-withdrawn']);
    $this->travel(1)->seconds();

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--reason' => 'UAT wallet test']))->toBe(0)
        ->and(app(SyntheticWalletFixtures::class)->currentPolicy())
        ->toMatchArray(['status' => 'active', ...SyntheticWalletFixtures::POLICY_TERMS]);
});

it('never resurrects a deposit whose outcome is already final, for Investor or Business', function (): void {
    $business = stagingBusiness();
    $declined = app(BusinessWalletStore::class)->deposit($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '14000'], $business['method']->id);
    asStaging();
    $tester = stagingTester();
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['declining'], '10000'))->assertOk();
    $intent = app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '12000'], $tester['method']->id);
    foreach ([[ApplyProviderOutcome::class, $intent], [ApplyBusinessProviderOutcome::class, $declined]] as [$applier, $result]) {
        $record = $applier === ApplyProviderOutcome::class ? WalletDepositIntent::query()->whereKey($result['data']['intent_id'])->sole()
            : BusinessDepositIntent::query()->whereKey($result['data']['intent_id'])->sole();
        expect(app($applier)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'provider-failure-'.$record->id,
            'reference' => $record->provider_reference, 'state' => 'failed', 'amount' => $record->amount, 'currency' => 'RWF', 'environment' => 'uat',
            'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]))['state'])->toBe('failed');
    }

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())
        ->toContain('Claimed 1 deposit dispatches; 1 acknowledged.')->toContain('Claimed 1 Business deposit dispatches; 1 acknowledged.')
        ->toContain('Settled 0 synthetic deposits; 0 left for the next run.')
        ->and(WalletProviderEvent::query()->where('state', 'succeeded')->count() + BusinessProviderEvent::query()->where('state', 'succeeded')->count())->toBe(0)
        ->and(WalletDepositCredit::query()->count() + BusinessDepositCredit::query()->count())->toBe(0);
});

it('resends an interrupted Business send once its claim is stale, then settles it once', function (): void {
    $business = stagingBusiness();
    app(BusinessWalletStore::class)->deposit($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '16000'], $business['method']->id);
    asStaging();
    expect(app(BusinessWalletStore::class)->claimDispatches(null, 1, true, 'uat'))->toHaveCount(1);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 0 Business deposit dispatches; 0 acknowledged.')
        ->toContain('Settled 0 synthetic deposits');
    $this->travel(6)->minutes();
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 1 Business deposit dispatches; 1 acknowledged.')
        ->toContain('Settled 1 synthetic deposits; 0 left for the next run.')
        ->and(BusinessDepositCredit::query()->count())->toBe(1);
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(BusinessDepositCredit::query()->count())->toBe(1)
        ->and(BusinessProviderEvent::query()->count())->toBe(1);
});

it('finishes a worker batch through the real console termination without waiting, and waits once when something is left', function (): void {
    $business = stagingBusiness();
    foreach (['14000', '15000'] as $amount) {
        app(BusinessWalletStore::class)->deposit($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $business['method']->id);
    }
    asStaging();
    $tester = stagingTester();
    foreach (['11000', '12000', '13000'] as $amount) {
        app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $tester['method']->id);
    }
    $kernel = app(ConsoleKernel::class);
    $worker = fn (): int => $kernel->handle(new ArrayInput(['command' => 'wallet:dispatch-deposits']), new BufferedOutput);

    $status = $worker();
    $kernel->terminate(new ArrayInput(['command' => 'wallet:dispatch-deposits']), $status);
    expect($status)->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(3)->and(BusinessDepositCredit::query()->count())->toBe(2);
    Sleep::assertNeverSlept();

    foreach (['16000', '17000', '18000', '19000'] as $amount) {
        app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $tester['method']->id);
    }
    app()->bind(ApplyProviderOutcome::class, fn () => throw new RuntimeException('The outcome store is unavailable.'));
    app()->bind(ApplyBusinessProviderOutcome::class, fn () => throw new RuntimeException('The outcome store is unavailable.'));
    $status = $worker();
    $kernel->terminate(new ArrayInput(['command' => 'wallet:dispatch-deposits']), $status);
    expect($status)->toBe(1)->and(WalletDepositCredit::query()->count())->toBe(3)->and(BusinessDepositCredit::query()->count())->toBe(2);
    Sleep::assertSleptTimes(1);

    app()->offsetUnset(ApplyProviderOutcome::class);
    app()->offsetUnset(ApplyBusinessProviderOutcome::class);
    expect($worker())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(7)->and(BusinessDepositCredit::query()->count())->toBe(2)
        ->and(WalletProviderEvent::query()->count() + BusinessProviderEvent::query()->count())->toBe(9);
});

it('keeps the worker non-zero while deposits beyond its limit are unsettled, settling the oldest first across both owners', function (): void {
    $business = stagingBusiness();
    app(RecordBusinessDeposit::class)->handle($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '25000'], $business['method']->id);
    asStaging();
    $tester = stagingTester();
    foreach (['11000', '12000', '13000'] as $amount) {
        app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $tester['method']->id);
    }
    expect(WalletDepositDispatch::query()->where('phase', 'acknowledged')->count())->toBe(3)
        ->and(BusinessDepositDispatch::query()->where('phase', 'acknowledged')->count())->toBe(1)
        ->and(WalletProviderEvent::query()->count() + BusinessProviderEvent::query()->count())->toBe(0);

    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(1)->and(Artisan::output())
        ->toContain('Settled 2 synthetic deposits; 2 left for the next run.')->not->toContain('deposit dispatches left')
        ->and(BusinessDepositCredit::query()->count())->toBe(1)
        ->and(WalletProviderEvent::query()->pluck('amount')->all())->toBe(['11000'])->and(WalletDepositCredit::query()->count())->toBe(1);
    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(0)->and(Artisan::output())
        ->toContain('Settled 2 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(3)->and(BusinessDepositCredit::query()->count())->toBe(1);
    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(0)->and(Artisan::output())->toContain('Settled 0 synthetic deposits; 0 left for the next run.')
        ->and(WalletProviderEvent::query()->count() + BusinessProviderEvent::query()->count())->toBe(4)
        ->and(WalletDepositCredit::query()->count() + BusinessDepositCredit::query()->count())->toBe(4);
});

it('sends at most the limit across both outboxes, interrupted resends included, and stays non-zero until nothing is left to send', function (): void {
    $business = stagingBusiness();
    foreach (['21000', '22000', '23000'] as $amount) {
        app(BusinessWalletStore::class)->deposit($business['user']->id, 1, $business['business']->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $business['method']->id);
    }
    asStaging();
    $tester = stagingTester();
    foreach (['11000', '12000', '13000'] as $amount) {
        app(WalletStore::class)->deposit($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => $amount], $tester['method']->id);
    }
    expect(app(WalletStore::class)->claimDispatches(null, 1, true, 'uat'))->toHaveCount(1)
        ->and(app(BusinessWalletStore::class)->claimDispatches(null, 1, true, 'uat'))->toHaveCount(1);
    $this->travel(6)->minutes();
    expect(app(WalletStore::class)->claimableDispatches(true))->toBe(3)->and(app(WalletStore::class)->claimableDispatches(false))->toBe(2)
        ->and(app(BusinessWalletStore::class)->claimableDispatches(true))->toBe(3)->and(app(BusinessWalletStore::class)->claimableDispatches(false))->toBe(2);

    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(1)->and(Artisan::output())
        ->toContain('Claimed 2 deposit dispatches; 2 acknowledged.')->toContain('Claimed 0 Business deposit dispatches; 0 acknowledged.')
        ->toContain('4 deposit dispatches left for the next run.')->toContain('Settled 2 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(2)->and(BusinessDepositCredit::query()->count())->toBe(0);
    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(1)->and(Artisan::output())
        ->toContain('Claimed 1 deposit dispatches; 1 acknowledged.')->toContain('Claimed 1 Business deposit dispatches; 1 acknowledged.')
        ->toContain('2 deposit dispatches left for the next run.')->toContain('Settled 2 synthetic deposits; 0 left for the next run.');
    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '2']))->toBe(0)->and(Artisan::output())
        ->toContain('Claimed 0 deposit dispatches; 0 acknowledged.')->toContain('Claimed 2 Business deposit dispatches; 2 acknowledged.')
        ->not->toContain('deposit dispatches left')->toContain('Settled 2 synthetic deposits; 0 left for the next run.')
        ->and(WalletDepositCredit::query()->count())->toBe(3)->and(BusinessDepositCredit::query()->count())->toBe(3)
        ->and(WalletProviderEvent::query()->count() + BusinessProviderEvent::query()->count())->toBe(6);
});

it('refuses UAT deposits from an Investor who is not a named wallet tester, even one the staging mail list approves', function (): void {
    $onServerList = InvestorWalletFixture::investor('tester@staging.rozine.invalid')['user'];
    $namedForMail = InvestorWalletFixture::investor('mail@staging-elsewhere.invalid')['user'];
    StagingMailTester::factory()->create(['email' => 'mail@staging-elsewhere.invalid']);
    $methods = [];
    foreach ([$onServerList, $namedForMail] as $user) {
        $methods[$user->id] = app(SyntheticWalletFixtures::class)->prepareInvestor((string) $user->party_id)['method_id'];
        expect(app(GetInvestorWallet::class)->handle($user->id, 1)['allowed_actions'])->toBe(['wallet.deposit']);
    }

    asStaging();
    foreach ([$onServerList, $namedForMail] as $user) {
        $this->actingAs($user)->postJson(route('investor.wallet.deposit'), stagingDeposit(InvestorFundingMethod::query()->findOrFail($methods[$user->id])))
            ->assertForbidden()->assertJsonPath('code', 'SYNTHETIC_TESTER_REQUIRED');
        expect(app(GetInvestorWallet::class)->handle($user->id, 1)['allowed_actions'])->toBe([]);
    }
    expect(WalletDepositIntent::query()->count())->toBe(0)->and(SyntheticWalletTesterChange::query()->count())->toBe(0);
});

it('stops a revoked tester\'s new deposits, still settles the deposits already accepted, and keeps every change on record', function (): void {
    asStaging();
    $tester = stagingTester();
    app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '10000'], $tester['method']->id);

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--revoke' => true, '--reason' => 'Testing finished']))->toBe(0)
        ->and(Artisan::output())->toContain('Wallet tester revoked');
    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '20000'))
        ->assertForbidden()->assertJsonPath('code', 'SYNTHETIC_TESTER_REQUIRED');
    expect(WalletDepositIntent::query()->count())->toBe(1)
        ->and(app(GetInvestorWallet::class)->handle($tester['user']->id, 1)['allowed_actions'])->toBe([])
        ->and(WalletDepositCredit::query()->count())->toBe(1)
        ->and(app(GetInvestorWallet::class)->handle($tester['user']->id, 1)['wallet']['total']['amount'])->toBe('10000');
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Settled 0 synthetic deposits; 0 left for the next run.');

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--revoke' => true, '--reason' => 'Again']))->toBe(1)
        ->and(Artisan::output())->toContain('WALLET_STAGING_TESTER_NOT_NAMED');
    $changes = SyntheticWalletTesterChange::query()->orderBy('id')->get();
    expect($changes->map(fn (SyntheticWalletTesterChange $change): array => $change->only(['party_id', 'action', 'reason', 'actor_key']))->all())->toBe([
        ['party_id' => $tester['user']->party_id, 'action' => 'granted', 'reason' => 'UAT wallet test', 'actor_key' => 'console'],
        ['party_id' => $tester['user']->party_id, 'action' => 'revoked', 'reason' => 'Testing finished', 'actor_key' => 'console'],
    ]);
    expect(fn () => DB::transaction(fn () => $changes->first()->forceFill(['action' => 'revoked'])->save()))->toThrow(QueryException::class, 'append-only')
        ->and(fn () => DB::transaction(fn () => $changes->last()->delete()))->toThrow(QueryException::class, 'append-only');

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--reason' => 'Retest']))->toBe(0);
    $this->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '20000'))->assertOk();
    expect(SyntheticWalletTesterChange::query()->orderBy('revision')->get()->map(fn (SyntheticWalletTesterChange $change): array => [$change->revision, $change->action])->all())
        ->toBe([[1, 'granted'], [2, 'revoked'], [3, 'granted']])
        ->and(WalletDepositCredit::query()->count())->toBe(2);
});

it('decides by revision, not by id, so a revocation whose id sorts before the grant still stops new deposits', function (): void {
    asStaging();
    $tester = stagingTester();
    app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '10000'], $tester['method']->id);
    $grant = SyntheticWalletTesterChange::query()->sole();
    // Another operator process's revocation, appended next under the account lock, with an id from the same millisecond that sorts lower.
    (new SyntheticWalletTesterChange)->forceFill(['id' => substr($grant->id, 0, 10).str_repeat('0', 16), 'party_id' => $grant->party_id,
        'user_id' => $grant->user_id, 'revision' => 2, 'action' => 'revoked', 'reason' => 'Testing finished', 'actor_key' => 'console',
        'request_id' => (string) Str::uuid(), 'created_at' => $grant->created_at])->save();
    expect(SyntheticWalletTesterChange::query()->orderByDesc('id')->value('action'))->toBe('granted');

    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '20000'))
        ->assertForbidden()->assertJsonPath('code', 'SYNTHETIC_TESTER_REQUIRED');
    expect(app(SyntheticWalletTesters::class)->authorized((string) $tester['user']->party_id))->toBeFalse()
        ->and(WalletDepositIntent::query()->count())->toBe(1)->and(WalletDepositCredit::query()->count())->toBe(1);

    expect(Artisan::call('staging:wallet', ['email' => 'tester@staging.rozine.invalid', '--reason' => 'Retest']))->toBe(0)
        ->and(SyntheticWalletTesterChange::query()->orderByDesc('revision')->first()?->only(['revision', 'action']))->toBe(['revision' => 3, 'action' => 'granted']);
    $this->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '30000'))->assertOk();
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(2);
});

it('refuses a second change at a revision the Party already has', function (): void {
    asStaging();
    $tester = stagingTester();
    $grant = SyntheticWalletTesterChange::query()->sole();

    expect(fn () => DB::transaction(fn () => (new SyntheticWalletTesterChange)->forceFill(['party_id' => $grant->party_id, 'user_id' => $grant->user_id,
        'revision' => 1, 'action' => 'revoked', 'reason' => 'Racing operator', 'actor_key' => 'console', 'request_id' => (string) Str::uuid(),
        'created_at' => now()])->save()))->toThrow(QueryException::class, 'synthetic_wallet_tester_changes_party_id_revision_unique');
    expect(app(SyntheticWalletTesters::class)->authorized((string) $tester['user']->party_id))->toBeTrue();
});

it('refuses a named tester\'s UAT deposit once their Investor role or verification no longer holds, and still settles the accepted one', function (Closure $lapse): void {
    asStaging();
    $tester = stagingTester();
    app(RecordDepositIntent::class)->handle($tester['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '10000'], $tester['method']->id);
    $lapse(Party::query()->findOrFail($tester['user']->party_id));

    $this->actingAs($tester['user'])->postJson(route('investor.wallet.deposit'), stagingDeposit($tester['method'], '20000'))->assertForbidden();
    expect(WalletDepositIntent::query()->count())->toBe(1)->and(app(SyntheticWalletTesters::class)->authorized((string) $tester['user']->party_id))->toBeTrue();
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(1);
})->with([
    'Investor role revoked' => [fn (Party $party) => RoleMembership::query()->where('party_id', $party->id)->where('role', 'investor')->update(['status' => 'revoked'])],
    'identity no longer verified' => [fn (Party $party) => $party->forceFill(['verified_at' => null])->save()],
]);
