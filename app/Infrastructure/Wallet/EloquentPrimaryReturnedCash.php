<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

final readonly class EloquentPrimaryReturnedCash implements PrimaryReturnedCash
{
    public function __construct(private CanonicalJson $json) {}

    public function requireReturned(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): ReturnedCash
    {
        if (DB::transactionLevel() < 1) {
            throw new WalletViolation('WALLET_POSTING_TRANSACTION_REQUIRED');
        }
        if (DB::scalar("SELECT current_setting('transaction_isolation')") !== 'read committed') {
            throw new WalletViolation('PRIMARY_CASH_ISOLATION_REQUIRED');
        }
        if ($source->type !== 'primary_reservation') {
            throw new WalletViolation('WALLET_POSTING_SOURCE_INVALID');
        }
        $walletQuery = InvestorWallet::query()->whereKey($wallet->walletId)->where('party_id', $wallet->partyId)->where('currency', 'RWF');
        if (! $walletQuery->exists()) {
            throw new WalletViolation('WALLET_POSTING_WALLET_INVALID');
        }
        if (LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->where('wallet_id', '!=', $wallet->walletId)->exists()) {
            throw new WalletViolation('WALLET_POSTING_CONFLICT');
        }
        $walletQuery->lockForUpdate()->firstOrFail();
        $entries = LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->orderBy('kind')->get();
        $kinds = $entries->pluck('kind')->all();
        if ($kinds !== ['primary_hold', 'primary_release'] && $kinds !== ['primary_commit', 'primary_hold', 'primary_refund']) {
            throw new WalletViolation('PRIMARY_RETURNED_CASH_REQUIRED');
        }
        foreach ($entries as $entry) {
            if ($entry->wallet_id !== $wallet->walletId || $entry->origin_operation_id !== $source->originOperationId || $entry->currency !== 'RWF') {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
            [$debit, $credit] = match ($entry->kind) {
                'primary_hold' => ['investor_available', 'investor_held'],
                'primary_commit' => ['investor_held', 'investor_committed'],
                'primary_release' => ['investor_held', 'investor_available'],
                default => ['investor_committed', 'investor_available'],
            };
            $lines = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
                ->where('ledger_lines.entry_id', $entry->id)->orderBy('ledger_lines.direction')
                ->get(['ledger_lines.direction', 'ledger_lines.amount', 'ledger_accounts.kind', 'ledger_accounts.wallet_id', 'ledger_accounts.currency'])
                ->map(fn (object $line): array => [(string) $line->direction, (string) $line->amount, $line->kind, $line->wallet_id, $line->currency])->all();
            if ($lines !== [['credit', $amount->amount(), $credit, $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $debit, $wallet->walletId, 'RWF']]) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
            $expectedLines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction,
                'amount' => $line->amount->amount()], JournalEntry::primary($entry->kind, $amount)->lines);
            $expected = ['entry_id' => $entry->id, 'wallet_id' => $wallet->walletId, 'kind' => $entry->kind,
                'source_type' => $source->type, 'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId,
                'lines' => $expectedLines, 'recorded_at' => $entry->created_at->utc()->toIso8601String()];
            $payload = $this->json->encode($entry->payload);
            if ($entry->cause_type !== null || $entry->cause_id !== null || ! hash_equals($entry->sha256, hash('sha256', $payload))
                || $payload !== $this->json->encode($expected)) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
        }
        $hold = $entries->firstOrFail(fn (LedgerEntry $entry): bool => $entry->kind === 'primary_hold');
        $returned = $entries->last();

        return new ReturnedCash($hold->id, $entries->firstWhere('kind', 'primary_commit')?->id, $returned->id, $returned->kind,
            $wallet->walletId, $source->id, $source->originOperationId, $amount->amount(), $returned->created_at->toDateTimeImmutable());
    }

    /** SELECT-only full held return; never locks or creates a wallet and grants no inventory authority.
     * @return array{hold_entry_id: string, hold_sha256: string, return_entry_id: string, return_sha256: string, wallet_id: string}
     */
    public function inspectHeldReturn(string $partyId, WalletMoney $amount, PostingSource $source): array
    {
        if ($source->type !== 'primary_reservation') {
            throw new WalletViolation('WALLET_POSTING_SOURCE_INVALID');
        }
        $entries = LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->orderBy('kind')->get();
        if ($entries->pluck('kind')->all() !== ['primary_hold', 'primary_release']) {
            throw new WalletViolation('PRIMARY_RETURNED_CASH_REQUIRED');
        }
        $hold = $entries->firstOrFail();
        $return = $entries->last();
        $walletId = $hold->wallet_id;
        if (! InvestorWallet::query()->whereKey($walletId)->where('party_id', $partyId)->where('currency', 'RWF')->exists()) {
            throw new WalletViolation('WALLET_POSTING_CONFLICT');
        }
        foreach ($entries as $entry) {
            [$debit, $credit] = $entry->kind === 'primary_hold'
                ? ['investor_available', 'investor_held'] : ['investor_held', 'investor_available'];
            $lines = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', 'ledger_lines.account_id')
                ->where('ledger_lines.entry_id', $entry->id)->orderBy('ledger_lines.direction')
                ->get(['ledger_lines.direction', 'ledger_lines.amount', 'ledger_accounts.kind', 'ledger_accounts.wallet_id', 'ledger_accounts.currency'])
                ->map(fn (object $line): array => [(string) $line->direction, (string) $line->amount, $line->kind, $line->wallet_id, $line->currency])->all();
            $expected = ['entry_id' => $entry->id, 'wallet_id' => $walletId, 'kind' => $entry->kind,
                'source_type' => 'primary_reservation', 'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId,
                'lines' => array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()],
                    JournalEntry::primary($entry->kind, $amount)->lines), 'recorded_at' => $entry->created_at->utc()->toIso8601String()];
            if ($entry->wallet_id !== $walletId || $entry->origin_operation_id !== $source->originOperationId || $entry->currency !== 'RWF'
                || $entry->cause_type !== null || $entry->cause_id !== null
                || $lines !== [['credit', $amount->amount(), $credit, $walletId, 'RWF'], ['debit', $amount->amount(), $debit, $walletId, 'RWF']]
                || ! hash_equals($entry->sha256, hash('sha256', $this->json->encode($entry->payload)))
                || $this->json->encode($entry->payload) !== $this->json->encode($expected)) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
        }

        return ['hold_entry_id' => $hold->id, 'hold_sha256' => $hold->sha256,
            'return_entry_id' => $return->id, 'return_sha256' => $return->sha256, 'wallet_id' => $walletId];
    }
}
