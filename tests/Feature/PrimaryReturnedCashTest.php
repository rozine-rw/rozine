<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->commitment = PrimaryCommitment::factory()->create();
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->wallet = app(WalletPostings::class)->lockForParty($this->root->party_id);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    app(WalletPostings::class)->refund($this->wallet, WalletMoney::of('5000'), $this->source);
    $this->cash = app(PrimaryReturnedCash::class);
});

it('returns exact original refund evidence without adding or replaying a posting', function (): void {
    $before = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $evidence = $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source);
    $again = $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source);
    $hold = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_hold')->sole();
    $commit = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_commit')->sole();
    $refund = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_refund')->sole();
    expect($evidence)->toEqual($again)
        ->and([$evidence->holdEntryId, $evidence->commitEntryId, $evidence->returnEntryId, $evidence->returnKind,
            $evidence->walletId, $evidence->reservationId, $evidence->originOperationId, $evidence->amount])
        ->toBe([$hold->id, $commit->id, $refund->id, 'primary_refund', $this->wallet->walletId, $this->root->id, $this->source->originOperationId, '5000'])
        ->and($evidence->returnedAt)->toEqual($refund->created_at->toDateTimeImmutable())
        ->and(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($before);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('proves a release against the exact hold and return time without inventing a commit', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $wallet = app(WalletPostings::class)->lockForParty($root->party_id);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    $this->travel(40)->seconds();
    PrimaryReservationFixture::terminalVersion($source, 'released');
    $release = app(WalletPostings::class)->release($wallet, WalletMoney::of('5000'), $source);
    $hold = LedgerEntry::query()->where('source_id', $root->id)->where('kind', 'primary_hold')->sole();
    $entry = LedgerEntry::query()->whereKey($release->entryId)->sole();
    $evidence = $this->cash->requireReturned($wallet, WalletMoney::of('5000'), $source);
    expect($evidence->holdEntryId)->toBe($hold->id)->and($evidence->commitEntryId)->toBeNull()
        ->and($evidence->returnKind)->toBe('primary_release')->and($evidence->returnEntryId)->toBe($entry->id)
        ->and($evidence->returnedAt)->toEqual($entry->created_at->toDateTimeImmutable())
        ->and($hold->created_at->equalTo($entry->created_at))->toBeFalse();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
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
        'source_type' => 'WALLET_POSTING_SOURCE_INVALID', 'source_id' => 'PRIMARY_RETURNED_CASH_REQUIRED',
        'missing_wallet', 'wrong_party' => 'WALLET_POSTING_WALLET_INVALID', default => 'WALLET_POSTING_CONFLICT',
    };
    expect(fn () => $this->cash->requireReturned($wallet, WalletMoney::of($case === 'amount' ? '10000' : '5000'), $source))
        ->toThrow(WalletViolation::class, $reason);
})->with(['amount', 'origin', 'other_wallet', 'missing_wallet', 'wrong_party', 'source_type', 'source_id']);

it('does not treat another source refund as returning an unrefunded commitment', function (): void {
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create(['party_id' => $this->root->party_id]);
    $otherSource = new PostingSource('primary_reservation', $other->id, $other->origin_operation_id);
    PrimaryReservationFixture::terminalVersion($otherSource, 'confirmed');
    app(WalletPostings::class)->commit($this->wallet, WalletMoney::of('5000'), $otherSource);
    expect(fn () => $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $otherSource))
        ->toThrow(WalletViolation::class, 'PRIMARY_RETURNED_CASH_REQUIRED');
    expect($this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source)->reservationId)->toBe($this->root->id);
});

it('refuses an unreturned hold', function (): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $wallet = app(WalletPostings::class)->lockForParty($root->party_id);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    expect(fn () => $this->cash->requireReturned($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'PRIMARY_RETURNED_CASH_REQUIRED');
});

it('refuses an incomplete return before deferred constraints run', function (string $kind): void {
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $wallet = app(WalletPostings::class)->lockForParty($root->party_id);
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    if ($kind === 'primary_refund') {
        PrimaryReservationFixture::terminalVersion($source, 'confirmed');
        app(WalletPostings::class)->commit($wallet, WalletMoney::of('5000'), $source);
    } else {
        PrimaryReservationFixture::terminalVersion($source, 'released');
    }
    LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $kind,
        'source_type' => $source->type, 'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId]);
    expect(fn () => $this->cash->requireReturned($wallet, WalletMoney::of('5000'), $source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
})->with(['primary_release', 'primary_refund']);

it('an entry whose header wallet matches but whose lines post to another wallet is refused', function (): void {
    $other = PrimaryReservationRecord::factory()->withInitialVersion()->create();
    $otherWallet = app(WalletPostings::class)->lockForParty($other->party_id);
    $otherHeld = DB::table('ledger_accounts')->where('wallet_id', $otherWallet->walletId)->where('kind', 'investor_held')->value('id');
    $refundId = LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_refund')->value('id');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::table('ledger_lines')->where('entry_id', $refundId)->where('direction', 'debit')->update(['account_id' => $otherHeld]);
    expect(fn () => $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});

it('refuses a foreign source before acquiring any supplied wallet lock', function (): void {
    $other = InvestorWallet::factory()->create();
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = $query->sql;
    });
    expect(fn () => $this->cash->requireReturned(new LockedWallet($other->id, $other->party_id), WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
    expect(array_filter($statements, fn (string $sql): bool => str_contains($sql, 'for update')))->toBe([]);
});

it('refuses additional issue mixed release and unknown movements', function (string $kind): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::table('ledger_entries')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $this->wallet->walletId,
        'kind' => $kind, 'source_type' => 'primary_reservation', 'source_id' => $this->source->id,
        'origin_operation_id' => $this->source->originOperationId, 'currency' => 'RWF', 'payload' => '{}',
        'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    expect(fn () => $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'PRIMARY_RETURNED_CASH_REQUIRED');
})->with(['primary_issue', 'primary_release', 'primary_adjustment']);

it('refuses a return with an altered currency header', function (): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_currency');
    DB::table('ledger_entries')->where('source_id', $this->source->id)->where('kind', 'primary_refund')->update(['currency' => 'USD']);
    expect(fn () => $this->cash->requireReturned($this->wallet, WalletMoney::of('5000'), $this->source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
});
