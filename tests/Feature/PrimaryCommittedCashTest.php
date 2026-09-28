<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\PrimaryCommittedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->commitment = PrimaryCommitment::factory()->create();
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->wallet = app(WalletPostings::class)->lockForParty($this->root->party_id);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->cash = app(PrimaryCommittedCash::class);
});

it('returns exact original committed evidence without adding or replaying a posting', function (): void {
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $evidence = $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source);
    $again = $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source);
    $hold = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_hold')->sole();
    $commit = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_commit')->sole();
    expect($evidence)->toEqual($again)
        ->and([$evidence->holdEntryId, $evidence->commitEntryId, $evidence->walletId, $evidence->reservationId, $evidence->originOperationId, $evidence->amount])
        ->toBe([$hold->id, $commit->id, $this->wallet->walletId, $this->root->id, $this->source->originOperationId, '5000'])
        ->and($evidence->committedAt)->toEqual($commit->created_at->toDateTimeImmutable())
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before);
});

it('refuses amount origin wallet and source substitutions', function (string $case): void {
    $wallet = match ($case) {
        'missing_wallet' => new LockedWallet(strtolower((string) Str::ulid()), $this->wallet->partyId),
        'wrong_party' => new LockedWallet($this->wallet->walletId, strtolower((string) Str::ulid())),
        'other_wallet' => (function (): LockedWallet {
            $other = InvestorWallet::factory()->create();

            return new LockedWallet($other->id, $other->party_id);
        })(),
        default => $this->wallet,
    };
    $source = new PostingSource($case === 'source_type' ? 'primary_commitment' : 'primary_reservation',
        $case === 'source_id' ? strtolower((string) Str::ulid()) : $this->source->id,
        $case === 'origin' ? strtolower((string) Str::ulid()) : $this->source->originOperationId);
    $reason = match ($case) {
        'source_type' => 'WALLET_POSTING_SOURCE_INVALID', 'source_id' => 'PRIMARY_COMMITTED_CASH_REQUIRED',
        'missing_wallet', 'wrong_party' => 'WALLET_POSTING_WALLET_INVALID', default => 'WALLET_POSTING_CONFLICT',
    };
    expect(fn () => $this->cash->requireCommitted($wallet, WalletMoney::of($case === 'amount' ? '10000' : '5000'), $source))
        ->toThrow(WalletViolation::class, $reason);
})->with(['amount', 'origin', 'other_wallet', 'missing_wallet', 'wrong_party', 'source_type', 'source_id']);

it('does not count a refunded purchase even when another commitment keeps the wallet funded', function (): void {
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create(['party_id' => $this->root->party_id]);
    $otherSource = new PostingSource('primary_reservation', $other->id, $other->origin_operation_id);
    PrimaryReservationFixture::terminalVersion($otherSource, 'confirmed');
    app(WalletPostings::class)->commit($this->wallet, WalletMoney::of('5000'), $otherSource);
    app(WalletPostings::class)->refund($this->wallet, WalletMoney::of('5000'), $this->source);
    expect(fn () => $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
    expect($this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $otherSource)->reservationId)->toBe($other->id);
});

it('refuses held and released cash as funding evidence', function (bool $released): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $wallet = app(WalletPostings::class)->lockForParty($root->party_id);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    if ($released) {
        PrimaryReservationFixture::terminalVersion($source, 'released');
        app(WalletPostings::class)->release($wallet, WalletMoney::of('5000'), $source);
    }
    expect(fn () => $this->cash->requireCommitted($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'PRIMARY_COMMITTED_CASH_REQUIRED');
})->with([false, true]);

it('refuses an incomplete commit inside the caller transaction before deferred constraints run', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $wallet = app(WalletPostings::class)->lockForParty($root->party_id);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => 'primary_commit',
        'source_type' => $source->type, 'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId]);
    expect(fn () => $this->cash->requireCommitted($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});
