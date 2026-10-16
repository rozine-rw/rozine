<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyWithdrawalOutcome;
use App\Application\Wallet\Contracts\BusinessWithdrawals;
use App\Application\Wallet\Contracts\PayoutAccounts;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWithdrawalSigner;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Application\Wallet\RecordBusinessWithdrawal;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessWithdrawal;
use App\Models\DepositPolicy;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WalletWithdrawal;
use App\Models\WalletWithdrawalEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The wallet adapters' refusal and edge paths: an empty or unreadable withdrawal quote, an outcome
 * for a reference no withdrawal carries, a sealed withdrawal or internal posting that no longer
 * matches its record, and a payout-account change once the Investor has a wallet.
 */

/** Fires every deferred check as a commit would, then defers them again for the rest of the test. */
function walletStoreCoverageCommits(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

/** Overwrites a sealed row's hash past its protecting triggers, as a damaged row would read. */
function walletStoreCoverageBreakSeal(string $table): void
{
    walletStoreCoverageCommits();
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    DB::table($table)->update(['sha256' => str_repeat('0', 64)]);
    walletStoreCoverageCommits();
    DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');
}

/**
 * A Business holding every permission, with a verified account and a credited synthetic deposit.
 *
 * @return array{business: BusinessProfile, user: User, method: BusinessFundingMethod}
 */
function walletStoreCoverageBusiness(string $balance = '500000'): array
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

it('quotes no Business withdrawal for an empty amount and refuses an unreadable one at zero', function (): void {
    $withdrawals = app(BusinessWithdrawals::class);

    expect($withdrawals->view(null, true, true, null, null)['withdrawal']['quote'])->toBeNull()
        ->and($withdrawals->view(null, true, true, '', null)['withdrawal']['quote'])->toBeNull()
        ->and($withdrawals->view(null, true, true, '12x', null)['withdrawal']['quote'])->toBe(['amount' => ['currency' => 'RWF', 'amount' => '0'],
            'charge' => ['currency' => 'RWF', 'amount' => '0'], 'received' => ['currency' => 'RWF', 'amount' => '0'], 'refusal' => 'VALIDATION_FAILED']);
});

it('refuses to read a Business withdrawal whose sealed facts no longer match its hash', function (): void {
    $fixture = walletStoreCoverageBusiness();
    app(RecordBusinessWithdrawal::class)->handle($fixture['user']->id, 1, $fixture['business']->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => '100000'], $fixture['method']->id);
    $walletId = BusinessWithdrawal::query()->sole()->wallet_id;
    expect(app(BusinessWithdrawals::class)->view($walletId, true, false, null, null)['withdrawals'])->toHaveCount(1);

    walletStoreCoverageBreakSeal('business_withdrawals');

    expect(fn () => app(BusinessWithdrawals::class)->view($walletId, true, false, null, null))
        ->toThrow(RuntimeException::class, 'WALLET_WITHDRAWAL_INTEGRITY_FAILED');
});

it('quotes no Investor withdrawal for an absent amount and refuses an unreadable one at zero', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $page = fn (array $query): array => app(GetInvestorWallet::class)->handle($fixture['user']->id, 1, $query)['funding'];

    $empty = $page(['kind' => 'withdraw']);
    expect($empty['kind'])->toBe('withdraw')->and($empty['quote'])->toBeNull()
        ->and($empty['withdrawal']['version'])->toBe('LED-R04/2026-10')->and($empty['withdrawal']['quote'])->toBeNull()
        ->and($page(['kind' => 'withdraw', 'amount' => ''])['withdrawal']['quote'])->toBeNull()
        ->and($page(['kind' => 'withdraw', 'amount' => '12x'])['withdrawal']['quote'])->toBe(['amount' => ['currency' => 'RWF', 'amount' => '0'],
            'charge' => ['currency' => 'RWF', 'amount' => '0'], 'received' => ['currency' => 'RWF', 'amount' => '0'], 'refusal' => 'VALIDATION_FAILED']);
});

it('refuses an Investor withdrawal outcome for a reference no withdrawal carries, recording nothing', function (): void {
    $message = app(SyntheticWithdrawalSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'synthetic-event-unknown-reference',
        'reference' => 'syw_unknown', 'state' => 'succeeded', 'amount' => '100000', 'currency' => 'RWF', 'environment' => 'testing',
        'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]);

    expect(fn () => app(ApplyWithdrawalOutcome::class)->handle($message))->toThrow(CommandRejection::class, 'WITHDRAWAL_REFERENCE_UNKNOWN')
        ->and(WalletWithdrawalEvent::query()->count())->toBe(0);
});

it('refuses to read an Investor withdrawal whose sealed facts no longer match its hash', function (): void {
    $fixture = InvestorWalletFixture::funded();
    InvestorWalletFixture::withdraw($fixture, '100000');
    expect(app(GetInvestorWallet::class)->handle($fixture['user']->id, 1)['withdrawals'])->toHaveCount(1)
        ->and(WalletWithdrawal::query()->count())->toBe(1);

    walletStoreCoverageBreakSeal('wallet_withdrawals');

    expect(fn () => app(GetInvestorWallet::class)->handle($fixture['user']->id, 1))->toThrow(RuntimeException::class, 'WALLET_WITHDRAWAL_INTEGRITY_FAILED');
});

it('refuses to open an internal posting receipt whose originating reservation is not on record', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $reservation = app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(),
        PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    $hold = LedgerEntry::query()->where('kind', 'primary_hold')->where('source_id', $reservation)->sole();
    expect(app(GetInvestorWallet::class)->handle($investor['user']->id, 1, ['receipt' => $hold->id])['receipt'])->not->toBeNull();

    walletStoreCoverageCommits();
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::table('ledger_entries')->where('id', $hold->id)->update(['origin_operation_id' => strtolower((string) Str::ulid())]);
    walletStoreCoverageCommits();
    DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER USER');

    expect(fn () => app(GetInvestorWallet::class)->handle($investor['user']->id, 1, ['receipt' => $hold->id]))
        ->toThrow(RuntimeException::class, 'WALLET_ENTRY_INTEGRITY_FAILED');
});

it('tells an open wallet page when the Investor links or unlinks a payout account', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::deposit($fixture);
    $walletId = InvestorWallet::query()->where('party_id', $fixture['party']->id)->value('id');
    $changes = fn (): int => DB::table('change_feed')->where('party_id', $fixture['party']->id)->where('topic', 'wallet')->where('subject', $walletId)->count();
    $before = $changes();

    $linked = app(PayoutAccounts::class)->link($fixture['user']->id, (string) Str::uuid(), ['type' => 'mobile', 'network' => 'airtel', 'number' => '0731234567']);
    expect($linked['status'])->toBe('completed')->and($changes())->toBe($before + 1);

    $unlinked = app(PayoutAccounts::class)->unlink($fixture['user']->id, (string) Str::uuid(), (string) $linked['data']['method_id']);
    expect($unlinked['status'])->toBe('completed')->and($changes())->toBe($before + 2);
});
