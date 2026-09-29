<?php

declare(strict_types=1);

/* Review 175u cash-port probes (1cffe3b4). Copy into tests/Feature. */

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
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->freezeSecond();
    PrimaryCommitment::factory()->create();
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->wallet = app(WalletPostings::class)->lockForParty($this->root->party_id);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->cash = app(PrimaryCommittedCash::class);
});

it('F1 wrong-Party and foreign-wallet tokens are refused before any FOR UPDATE', function (string $case, string $reason): void {
    $other = InvestorWallet::factory()->create();
    $token = match ($case) {
        'right wallet, wrong party' => new LockedWallet($this->wallet->walletId, strtolower((string) Str::ulid())),
        'right wallet, other real party' => new LockedWallet($this->wallet->walletId, $other->party_id),
        'foreign wallet, right party' => new LockedWallet($other->id, $this->wallet->partyId),
        'foreign wallet, its own party' => new LockedWallet($other->id, $other->party_id),
    };
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = $query->sql;
    });
    expect(fn () => $this->cash->requireCommitted($token, WalletMoney::of('5000'), $this->source))->toThrow(WalletViolation::class, $reason)
        ->and(array_values(array_filter($statements, fn (string $sql): bool => str_contains($sql, 'for update'))))->toBe([]);
})->with([
    ['right wallet, wrong party', 'WALLET_POSTING_WALLET_INVALID'],
    ['right wallet, other real party', 'WALLET_POSTING_WALLET_INVALID'],
    ['foreign wallet, right party', 'WALLET_POSTING_WALLET_INVALID'],
    ['foreign wallet, its own party', 'WALLET_POSTING_CONFLICT'],
]);

it('F2 the ownership pre-check reads only immutable columns', function (string $column): void {
    $entry = LedgerEntry::query()->where('source_id', $this->root->id)->firstOrFail();
    $other = InvestorWallet::factory()->create();
    $update = match ($column) {
        'ledger_entries.wallet_id' => fn () => DB::table('ledger_entries')->where('id', $entry->id)->update(['wallet_id' => $other->id]),
        'ledger_entries.source_id' => fn () => DB::table('ledger_entries')->where('id', $entry->id)->update(['source_id' => strtolower((string) Str::ulid())]),
        'investor_wallets.party_id' => fn () => DB::table('investor_wallets')->where('id', $this->wallet->walletId)->update(['party_id' => $other->party_id]),
        'investor_wallets.currency' => fn () => DB::table('investor_wallets')->where('id', $this->wallet->walletId)->update(['currency' => 'USD']),
    };
    expect(fn () => DB::transaction($update))->toThrow(QueryException::class, 'immutable');
})->with(['ledger_entries.wallet_id', 'ledger_entries.source_id', 'investor_wallets.party_id', 'investor_wallets.currency']);

it('F3 the statement order is isolation, wallet exists, foreign-entry exists, then the single wallet FOR UPDATE', function (): void {
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = $query->sql;
    });
    $this->cash->requireCommitted($this->wallet, WalletMoney::of('5000'), $this->source);
    $forUpdate = array_keys(array_filter($statements, fn (string $sql): bool => str_contains($sql, 'for update')));
    expect($statements[0])->toContain('transaction_isolation')->and($forUpdate)->toBe([3])
        ->and($statements[2])->toContain('"wallet_id" != ?');
});
