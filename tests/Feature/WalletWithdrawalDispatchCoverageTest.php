<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\WithdrawalProvider;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Application\Wallet\RecordBusinessWithdrawal;
use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Wallet\SyntheticWithdrawalProvider;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessWithdrawal;
use App\Models\BusinessWithdrawalDispatch;
use App\Models\DepositPolicy;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WalletWithdrawal;
use App\Models\WalletWithdrawalDispatch;
use Database\Factories\Scenarios\BusinessAuthorityFixture;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/** Fires every deferred check as a commit would, then defers them again for the rest of the test. */
function dispatchCoverageCommits(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

/**
 * Binds a synthetic-named withdrawal provider whose every send throws, counting the attempts.
 *
 * @return object{sends: int}
 */
function dispatchCoverageThrowingProvider(): object
{
    $provider = new class implements WithdrawalProvider
    {
        public int $sends = 0;

        public function name(): string
        {
            return 'synthetic';
        }

        public function charge(): string
        {
            return '0';
        }

        public function idempotentSends(): bool
        {
            return false;
        }

        public function send(WithdrawalInstruction $instruction): bool
        {
            $this->sends++;

            throw new RuntimeException('provider timeout');
        }

        public function verify(array $message): VerifiedWithdrawalEvent
        {
            throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
        }
    };
    app()->instance(WithdrawalProvider::class, $provider);

    return $provider;
}

/**
 * A Business with a single all-permission mandate holder and a credited synthetic deposit of `$balance`.
 *
 * @return array{business: BusinessProfile, user: User, method: BusinessFundingMethod}
 */
function dispatchCoverageBusiness(string $balance = '500000'): array
{
    $authority = BusinessAuthorityFixture::make('organization', 1, 'COMPANY-'.Str::ulid(), 1);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::query()->exists() || DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $fixture = ['business' => $business, 'user' => $authority['users'][0], 'method' => BusinessFundingMethod::factory()->create(['business_id' => $business->id])];
    $deposit = app(RecordBusinessDeposit::class)->handle($fixture['user']->id, 1, $business->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => $balance], $fixture['method']->id);
    $intent = BusinessDepositIntent::query()->whereKey((string) $deposit['data']['intent_id'])->sole();
    app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
        'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
        'amount' => $balance, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));

    return $fixture;
}

/**
 * @param  array<string, string|bool>  $options
 * @return array{int, string}
 */
function dispatchCoverageCommand(array $options): array
{
    $status = Artisan::call('local:wallet', $options);

    return [$status, Artisan::output()];
}

/**
 * A well-formed synthetic withdrawal outcome, before signing.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function dispatchCoverageFields(array $overrides = []): array
{
    return [...['provider' => 'synthetic', 'event_id' => 'synthetic-event-coverage', 'reference' => 'syw_coverage', 'state' => 'succeeded',
        'amount' => '1000', 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)], ...$overrides];
}

it('records an Investor withdrawal whose send throws as unacknowledged and keeps its cash held', function (): void {
    $provider = dispatchCoverageThrowingProvider();
    $fixture = InvestorWalletFixture::funded();
    $result = InvestorWalletFixture::withdraw($fixture, '100000');
    dispatchCoverageCommits();
    $withdrawal = WalletWithdrawal::query()->whereKey((string) $result['data']['withdrawal_id'])->sole();

    expect($result['status'])->toBe('completed')->and($provider->sends)->toBe(1)
        ->and(WalletWithdrawalDispatch::query()->where('withdrawal_id', $withdrawal->id)->orderBy('id')->pluck('phase')->all())
        ->toBe(['queued', 'claimed', 'unacknowledged'])
        ->and(LedgerEntry::query()->where('source_type', 'wallet_withdrawal')->pluck('kind')->all())->toBe(['withdrawal_hold']);
});

it('records a Business withdrawal whose send throws as unacknowledged and keeps its cash held', function (): void {
    $provider = dispatchCoverageThrowingProvider();
    $fixture = dispatchCoverageBusiness();
    $result = app(RecordBusinessWithdrawal::class)->handle($fixture['user']->id, 1, $fixture['business']->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => '100000'], $fixture['method']->id);
    dispatchCoverageCommits();
    $withdrawal = BusinessWithdrawal::query()->sole();

    expect($result['status'])->toBe('completed')->and($provider->sends)->toBe(1)
        ->and(BusinessWithdrawalDispatch::query()->where('withdrawal_id', $withdrawal->id)->orderBy('id')->pluck('phase')->all())
        ->toBe(['queued', 'claimed', 'unacknowledged'])
        ->and(LedgerEntry::query()->where('source_type', 'business_withdrawal')->pluck('kind')->all())->toBe(['business_withdrawal_hold']);
});

it('delivers signed synthetic events for a recorded withdrawal through the local hook and pays a success out once', function (): void {
    $fixture = InvestorWalletFixture::funded();
    $request = (string) Str::uuid();
    InvestorWalletFixture::withdraw($fixture, '100000', $request);
    dispatchCoverageCommits();

    [$pending, $pendingOutput] = dispatchCoverageCommand(['--event' => $request, '--withdrawal' => true, '--state' => 'unknown']);
    [$paid, $paidOutput] = dispatchCoverageCommand(['--event' => strtoupper($request), '--withdrawal' => true, '--state' => 'succeeded',
        '--event-id' => 'synthetic-withdrawal-hook-1']);

    expect([$pending, $paid])->toBe([0, 0])
        ->and($pendingOutput)->toContain(': applied; withdrawal unknown; nothing settled')
        ->and($paidOutput)->toContain('Event synthetic-withdrawal-hook-1: applied; withdrawal succeeded; settled once')
        ->and(LedgerEntry::query()->where('kind', 'withdrawal_payout')->count())->toBe(1);
});

it('refuses a withdrawal event for a request no single withdrawal was recorded under', function (): void {
    [$status, $output] = dispatchCoverageCommand(['--event' => '5b0f7c2e-1d4a-4c6b-9e21-000000000000', '--withdrawal' => true, '--state' => 'succeeded']);

    expect($status)->toBe(1)->and($output)->toContain('WALLET_SYNTHETIC_WITHDRAWAL_NOT_FOUND')
        ->and(LedgerEntry::query()->whereIn('kind', ['withdrawal_payout', 'withdrawal_return'])->count())->toBe(0);
});

it('never sends a synthetic payout from inside an open transaction', function (): void {
    $instruction = new WithdrawalInstruction((string) Str::ulid(), (string) Str::ulid(), 'syw_coverage', (string) Str::ulid(), '1000', 'RWF', 'testing');

    expect(fn () => DB::transaction(fn () => app(SyntheticWithdrawalProvider::class)->send($instruction)))
        ->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});

it('verifies a well-formed synthetic withdrawal outcome and refuses a signed but malformed one', function (array $overrides): void {
    $provider = app(SyntheticWithdrawalProvider::class);

    expect($provider->verify($provider->sign(dispatchCoverageFields()))->state)->toBe('succeeded')
        ->and(fn () => $provider->verify($provider->sign(dispatchCoverageFields($overrides))))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_MALFORMED');
})->with([
    'an unknown state' => [['state' => 'redirected']],
    'an unparseable observation time' => [['observed_at' => 'not-a-timestamp']],
]);
