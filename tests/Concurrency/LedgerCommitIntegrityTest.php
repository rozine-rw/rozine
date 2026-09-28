<?php

declare(strict_types=1);

use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * These run with real commits: the balance check is deferred to COMMIT (where PDO reports it), and the seal compares the
 * entry's creating transaction with the current one, which a rolled-back test transaction hides.
 */
it('rejects an unbalanced entry at commit and keeps nothing of it', function (): void {
    $wallet = InvestorWallet::factory()->create();
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $wallet->id]);

    expect(fn () => DB::transaction(function () use ($wallet, $clearing, $available): void {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '4000']);
    }))->toThrow(PDOException::class, 'must balance')
        ->and(fn () => DB::transaction(fn () => LedgerEntry::factory()->create(['wallet_id' => $wallet->id])))->toThrow(PDOException::class, 'must balance')
        ->and(LedgerEntry::query()->count())->toBe(0)
        ->and(LedgerLine::query()->count())->toBe(0);
});

it('seals a committed entry so a later transaction cannot append even balanced lines', function (): void {
    $wallet = InvestorWallet::factory()->create();
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $wallet->id]);
    $entry = DB::transaction(function () use ($wallet, $clearing, $available): LedgerEntry {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);

        return $entry;
    });

    expect(fn () => DB::transaction(function () use ($entry, $clearing, $available): void {
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '100']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '100']);
    }))->toThrow(QueryException::class, 'Ledger entry is sealed')
        ->and(LedgerLine::query()->where('entry_id', $entry->id)->sum('amount'))->toBe('10000');
});

it('refuses a line appended after an early balance flush, even a balanced pair, and keeps nothing of the transaction', function (bool $pair): void {
    $wallet = InvestorWallet::factory()->create();
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $wallet->id]);

    expect(fn () => DB::transaction(function () use ($wallet, $clearing, $available, $pair): void {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->id]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);
        DB::statement('SET CONSTRAINTS ledger_entries_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced DEFERRED');
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '100']);
        if ($pair) {
            LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '100']);
        }
    }))->toThrow(QueryException::class, 'its balance was already validated in this transaction')
        ->and(LedgerEntry::query()->count())->toBe(0)->and(LedgerLine::query()->count())->toBe(0);
})->with(['one line' => false, 'balanced pair' => true]);

it('checks every entry a transaction writes at commit, even when another entry was flushed early', function (): void {
    $wallet = InvestorWallet::factory()->create();
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $wallet->id]);

    expect(fn () => DB::transaction(function () use ($wallet, $clearing, $available): void {
        $first = LedgerEntry::factory()->create(['wallet_id' => $wallet->id]);
        LedgerLine::factory()->create(['entry_id' => $first->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '5000']);
        LedgerLine::factory()->create(['entry_id' => $first->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '5000']);
        DB::statement('SET CONSTRAINTS ledger_entries_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced DEFERRED');
        $second = LedgerEntry::factory()->create(['wallet_id' => $wallet->id]);
        LedgerLine::factory()->create(['entry_id' => $second->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => '700']);
        LedgerLine::factory()->create(['entry_id' => $second->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => '600']);
    }))->toThrow(PDOException::class, 'must balance')
        ->and(LedgerEntry::query()->count())->toBe(0);
});
