<?php

declare(strict_types=1);

use App\Application\Wallet\GetInvestorWallet;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\User;
use App\Models\WalletDepositCredit;
use App\Models\WalletProviderEvent;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/**
 * @param  array<string, string|bool>  $options
 * @return array{int, string}
 */
function syntheticWallet(array $options): array
{
    $status = Artisan::call('local:wallet', $options);

    return [$status, Artisan::output()];
}

it('seeds a synthetic Investor with a verified method under an explicit synthetic policy, and reruns without duplicating', function (): void {
    [$status, $output] = syntheticWallet(['--seed' => 'wallet-a@s3b.rozine.invalid']);
    $user = User::query()->where('email', 'wallet-a@s3b.rozine.invalid')->sole();

    expect($status)->toBe(0)->and($output)->toContain('wallet-a@s3b.rozine.invalid')->toContain('synthetic-deposit-policy-0')
        ->toContain(SyntheticWalletSeeder::PASSWORD)->toContain('/investor/wallet')
        ->and(Hash::check(SyntheticWalletSeeder::PASSWORD, $user->password))->toBeTrue()
        ->and(app(GetInvestorWallet::class)->handle($user->id, 1)['allowed_actions'])->toBe(['wallet.deposit'])
        ->and(DepositPolicy::query()->sole()->only(['version', 'synthetic', 'status', 'fee', 'minimum', 'maximum']))
        ->toBe(['version' => 'synthetic-deposit-policy-0', 'synthetic' => true, 'status' => 'active', 'fee' => '0', 'minimum' => '1000', 'maximum' => '5000000']);

    expect(syntheticWallet(['--seed' => 'wallet-a@s3b.rozine.invalid'])[0])->toBe(0)
        ->and(InvestorFundingMethod::query()->where('party_id', $user->party_id)->count())->toBe(1)
        ->and(DepositPolicy::query()->count())->toBe(1);
});

it('withdraws the policy and reinstates a new synthetic version on the next seed', function (): void {
    syntheticWallet(['--seed' => 'wallet-b@s3b.rozine.invalid']);
    $user = User::query()->where('email', 'wallet-b@s3b.rozine.invalid')->sole();
    [$status, $output] = syntheticWallet(['--no-policy' => true]);

    expect($status)->toBe(0)->and($output)->toContain('Deposit policy withdrawn: synthetic-deposit-policy-1-withdrawn')
        ->and(app(GetInvestorWallet::class)->handle($user->id, 1)['funding']['policy'])->toBeNull();
    $this->travel(1)->seconds();
    expect(syntheticWallet(['--seed' => 'wallet-b@s3b.rozine.invalid'])[1])->toContain('synthetic-deposit-policy-2')
        ->and(app(GetInvestorWallet::class)->handle($user->id, 1)['funding']['policy']['version'])->toBe('synthetic-deposit-policy-2');
});

it('records a section 11.4 hold that leaves deposits open', function (): void {
    syntheticWallet(['--seed' => 'wallet-c@s3b.rozine.invalid']);
    $user = User::query()->where('email', 'wallet-c@s3b.rozine.invalid')->sole();
    [$status, $output] = syntheticWallet(['--restrict' => 'wallet-c@s3b.rozine.invalid']);
    $case = InvestorAccountRestriction::query()->sole();
    $page = app(GetInvestorWallet::class)->handle($user->id, 1);

    expect($status)->toBe(0)->and($output)->toContain('Section 11.4 hold recorded: '.$case->id)
        ->and([$case->cause, $case->source, $case->expires_at?->diffInHours($case->effective_at, true)])->toBe(['high_risk_hold', 'synthetic', 24.0])
        ->and($page['wallet']['status'])->toBe('restricted')->and($page['allowed_actions'])->toBe(['wallet.deposit']);
});

it('delivers signed synthetic events for a recorded deposit and credits a success once', function (): void {
    syntheticWallet(['--seed' => 'wallet-d@s3b.rozine.invalid']);
    $user = User::query()->where('email', 'wallet-d@s3b.rozine.invalid')->sole();
    $request = (string) Str::uuid();
    InvestorWalletFixture::deposit(['user' => $user, 'method' => InvestorFundingMethod::query()->where('party_id', $user->party_id)->sole()], '50000', $request);

    expect(syntheticWallet(['--event' => $request, '--state' => 'unknown'])[1])->toContain(': applied; deposit unknown; nothing credited')
        ->and(syntheticWallet(['--event' => $request, '--state' => 'succeeded', '--event-id' => 'synthetic-event-hook-1'])[1])
        ->toContain('Event synthetic-event-hook-1: applied; deposit succeeded; credited once')
        ->and(syntheticWallet(['--event' => strtoupper($request), '--state' => 'succeeded', '--event-id' => 'synthetic-event-hook-2'])[1])
        ->toContain('Event synthetic-event-hook-2: duplicate; deposit succeeded; nothing credited')
        ->and(syntheticWallet(['--event' => $request, '--state' => 'failed', '--amount' => '1'])[1])->toContain(': mismatch; deposit succeeded')
        ->and(WalletDepositCredit::query()->count())->toBe(1)->and(WalletProviderEvent::query()->count())->toBe(4)
        ->and(app(GetInvestorWallet::class)->handle($user->id, 1)['wallet']['total']['amount'])->toBe('50000');
});

it('refuses unknown requests, accounts and options', function (array $options, int $status, string $message): void {
    User::factory()->create(['email' => 'staff@s3b.rozine.invalid']);
    [$code, $output] = syntheticWallet($options);
    expect($code)->toBe($status)->and($output)->toContain($message);
})->with([
    'nothing chosen' => [[], 2, 'Choose --seed, --no-policy, --restrict or --event.'],
    'no state' => [['--event' => '5b0f7c2e-1d4a-4c6b-9e21-000000000000'], 2, '--event needs --state'],
    'bad state' => [['--event' => '5b0f7c2e-1d4a-4c6b-9e21-000000000000', '--state' => 'redirected'], 2, '--event needs --state'],
    'unknown request' => [['--event' => '5b0f7c2e-1d4a-4c6b-9e21-000000000000', '--state' => 'succeeded'], 1, 'WALLET_SYNTHETIC_INTENT_NOT_FOUND'],
    'unknown account' => [['--restrict' => 'nobody@s3b.rozine.invalid'], 1, 'WALLET_SYNTHETIC_ACCOUNT_NOT_FOUND'],
    'not an investor' => [['--seed' => 'staff@s3b.rozine.invalid'], 1, 'WALLET_SYNTHETIC_ACCOUNT_NOT_INVESTOR'],
]);

it('refuses to run where synthetic support or a local database is not in place', function (Closure $environment, string $message): void {
    $environment();
    [$code, $output] = syntheticWallet(['--seed' => 'wallet-e@s3b.rozine.invalid']);
    expect($code)->toBe(1)->and($output)->toContain($message)
        ->and(User::query()->where('email', 'wallet-e@s3b.rozine.invalid')->exists())->toBeFalse();
})->with([
    'live money on' => [fn () => config(['isolation.live_money_enabled' => true]), 'WALLET_SYNTHETIC_ONLY'],
    'remote database' => [fn () => config(['database.connections.pgsql.host' => 'db.example.test']), 'WALLET_LOCAL_DATABASE_REQUIRED'],
]);
