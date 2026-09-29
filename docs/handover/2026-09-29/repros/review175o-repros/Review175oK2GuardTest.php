<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range 205a8a08..66e284d5 (K2 guard). Copy into tests/Feature/.
 * Every test asserts the SAFE behaviour and should pass on 66e284d5.
 */

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->version = PrimaryReservationVersion::query()->sole();
    $this->wallets = app(WalletPostings::class);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->breakdown = fn (): array => app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'];
    $this->confirm = fn (?string $request = null, int $revision = 1): array => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $revision,
        $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'],
        $request ?? (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->nothingLeft = function (): void {
        expect(PrimaryReservationVersion::query()->count())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe(0)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0)
            ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0)
            ->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0)
            ->and(($this->breakdown)()['held']['amount'])->toBe('15000')->and(($this->breakdown)()['committed']['amount'])->toBe('0');
    };
});

it('K2 (was DEFECT): a same-transaction prior commit makes confirm fail closed and roll back', function (): void {
    expect(fn () => DB::transaction(function (): void {
        $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
        ($this->confirm)();
    }))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
    ($this->nothingLeft)();
    // the hold is still confirmable afterwards, once
    expect(($this->confirm)()['code'])->toBe('RESERVATION_CONFIRMED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('BYPASS: a prior commit of a different amount (partial) is refused, not replayed', function (): void {
    expect(fn () => DB::transaction(function (): void {
        $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of('5000'), $this->source);
        ($this->confirm)();
    }))->toThrow(App\Domain\Wallet\WalletViolation::class, 'WALLET_POSTING_CONFLICT');
    ($this->nothingLeft)();
});

it('BYPASS: a prior commit under another origin operation is refused, not replayed', function (): void {
    expect(fn () => DB::transaction(function (): void {
        $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal),
            new PostingSource('primary_reservation', $this->root->id, (string) Str::ulid()));
        ($this->confirm)();
    }))->toThrow(App\Domain\Wallet\WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID');
    ($this->nothingLeft)();
});

it('BYPASS: a prior release in the same transaction blocks the commit', function (): void {
    expect(fn () => DB::transaction(function (): void {
        $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
        ($this->confirm)();
    }))->toThrow(App\Domain\Wallet\WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
    ($this->nothingLeft)();
});

it('LEGIT: a same-key command replay still returns the original confirmation through the journal', function (): void {
    $key = (string) Str::uuid();
    $first = ($this->confirm)($key);
    $replay = ($this->confirm)($key);
    expect($first['code'])->toBe('RESERVATION_CONFIRMED')->and($replay)->toEqual($first)
        ->and(PrimaryCommitment::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('LEGIT: a new key after confirmation is refused before posting, not an integrity failure', function (int $revision, string $code): void {
    expect(($this->confirm)()['code'])->toBe('RESERVATION_CONFIRMED');
    try {
        $again = ($this->confirm)(revision: $revision);
    } catch (Throwable $exception) {
        $again = ['code' => $exception->getMessage()];
    }
    expect($again['code'])->toBe($code)
        ->and(PrimaryCommitment::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([[1, 'VERSION_CONFLICT'], [2, 'RESERVATION_NOT_HELD']]);
