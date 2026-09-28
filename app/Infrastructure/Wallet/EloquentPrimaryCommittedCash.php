<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\CommittedCash;
use App\Application\Wallet\Contracts\PrimaryCommittedCash;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

final readonly class EloquentPrimaryCommittedCash implements PrimaryCommittedCash
{
    public function requireCommitted(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): CommittedCash
    {
        if (DB::transactionLevel() < 1) {
            throw new WalletViolation('WALLET_POSTING_TRANSACTION_REQUIRED');
        }
        if ($source->type !== 'primary_reservation') {
            throw new WalletViolation('WALLET_POSTING_SOURCE_INVALID');
        }
        InvestorWallet::query()->whereKey($wallet->walletId)->where('party_id', $wallet->partyId)->where('currency', 'RWF')->lockForUpdate()->first()
            ?? throw new WalletViolation('WALLET_POSTING_WALLET_INVALID');
        $entries = LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->orderBy('kind')->get();
        if ($entries->pluck('kind')->all() !== ['primary_commit', 'primary_hold']) {
            throw new WalletViolation('PRIMARY_COMMITTED_CASH_REQUIRED');
        }
        foreach ($entries as $entry) {
            if ($entry->wallet_id !== $wallet->walletId || $entry->origin_operation_id !== $source->originOperationId || $entry->currency !== 'RWF') {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
            $debit = $entry->kind === 'primary_hold' ? 'investor_available' : 'investor_held';
            $credit = $entry->kind === 'primary_hold' ? 'investor_held' : 'investor_committed';
            $lines = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
                ->where('ledger_lines.entry_id', $entry->id)->orderBy('ledger_lines.direction')
                ->get(['ledger_lines.direction', 'ledger_lines.amount', 'ledger_accounts.kind', 'ledger_accounts.wallet_id', 'ledger_accounts.currency'])
                ->map(fn (object $line): array => [(string) $line->direction, (string) $line->amount, $line->kind, $line->wallet_id, $line->currency])->all();
            if ($lines !== [['credit', $amount->amount(), $credit, $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $debit, $wallet->walletId, 'RWF']]) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }
        }
        $commit = $entries->firstOrFail();
        $hold = $entries->last();

        return new CommittedCash($hold->id, $commit->id, $wallet->walletId, $source->id, $source->originOperationId,
            $amount->amount(), $commit->created_at->toDateTimeImmutable());
    }
}
