<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingReceipt;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\PrimaryPosting;
use App\Domain\Wallet\WalletBalance;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Primary purchase postings in the caller's transaction. See `WalletPostings` for the lock order,
 * source binding and retry rules; PostgreSQL enforces the same lifecycle, the per-kind source
 * uniqueness, the balance and the non-negative buckets again at the boundary.
 */
final class EloquentWalletPostings implements WalletPostings
{
    public function __construct(private CanonicalJson $json) {}

    public function lockForParty(string $partyId): LockedWallet
    {
        $this->assertTransaction();
        DB::insert("INSERT INTO investor_wallets (id, party_id, currency, created_at) VALUES (?, ?, 'RWF', now()) ON CONFLICT (party_id) DO NOTHING",
            [strtolower((string) Str::ulid()), $partyId]);
        $wallet = InvestorWallet::query()->where('party_id', $partyId)->lockForUpdate()->sole();

        return new LockedWallet($wallet->id, $wallet->party_id);
    }

    public function hold(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->post('primary_hold', $wallet, $amount, $source);
    }

    public function commit(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->post('primary_commit', $wallet, $amount, $source);
    }

    public function release(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->post('primary_release', $wallet, $amount, $source);
    }

    public function refund(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->post('primary_refund', $wallet, $amount, $source);
    }

    public function issue(LockedWallet $wallet, WalletMoney $amount, PostingSource $source, PostingCause $cause): PostingReceipt
    {
        if ($source->type !== 'primary_commitment') {
            throw new WalletViolation('WALLET_POSTING_SOURCE_INVALID');
        }

        return $this->post('primary_issue', $wallet, $amount, $source, $cause);
    }

    private function post(string $kind, LockedWallet $wallet, WalletMoney $amount, PostingSource $source, ?PostingCause $cause = null): PostingReceipt
    {
        $this->assertTransaction();
        InvestorWallet::query()->whereKey($wallet->walletId)->where('party_id', $wallet->partyId)->lockForUpdate()->first()
            ?? throw new WalletViolation('WALLET_POSTING_WALLET_INVALID');
        $recorded = LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->get()->keyBy('kind');
        $previous = $recorded->get($kind);
        if ($previous !== null) {
            if (! $this->matches($previous, $wallet, $amount, $source) || $previous->cause_id !== $cause?->id) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }

            return $this->receipt($previous, true);
        }
        PrimaryPosting::assertAllowed($kind, array_values($recorded->keys()->map(fn ($recordedKind): string => (string) $recordedKind)->all()));
        $follows = PrimaryPosting::follows($kind);
        if ($follows !== null && ! $this->matches($recorded->get($follows) ?? throw new WalletViolation('WALLET_POSTING_STATE_INVALID'), $wallet, $amount, $source)) {
            throw new WalletViolation('WALLET_POSTING_CONFLICT');
        }
        $journal = JournalEntry::primary($kind, $amount);
        if ($this->bucket($wallet->walletId, PrimaryPosting::MOVEMENTS[$kind][0])->compareTo($amount) < 0) {
            throw $kind === 'primary_hold' ? new CommandRejection('INSUFFICIENT_AVAILABLE_FUNDS', 422) : new WalletViolation('WALLET_BUCKET_NEGATIVE');
        }

        return $this->receipt($this->record($journal, $wallet, $source, $cause), false);
    }

    private function record(JournalEntry $journal, LockedWallet $wallet, PostingSource $source, ?PostingCause $cause): LedgerEntry
    {
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $lines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()], $journal->lines);
        $payload = ['entry_id' => $entry->id, 'wallet_id' => $wallet->walletId, 'kind' => $journal->kind, 'source_type' => $source->type, 'source_id' => $source->id,
            'origin_operation_id' => $source->originOperationId, 'lines' => $lines, 'recorded_at' => $recordedAt->toIso8601String()];
        if ($cause !== null) {
            $payload['cause'] = ['type' => $cause->type, 'id' => $cause->id];
        }
        $entry->forceFill(['wallet_id' => $wallet->walletId, 'kind' => $journal->kind, 'source_type' => $source->type, 'source_id' => $source->id,
            'origin_operation_id' => $source->originOperationId, 'cause_type' => $cause?->type, 'cause_id' => $cause?->id, 'currency' => 'RWF', 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        foreach ($journal->lines as $line) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($line->account, $wallet->walletId),
                'direction' => $line->direction, 'amount' => $line->amount->amount(), 'created_at' => $recordedAt])->save();
        }
        // Surface an unbalanced entry here rather than at commit; the check seals the entry, and
        // any other entry this transaction writes is still checked at commit.
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced DEFERRED');

        return $entry;
    }

    private function matches(LedgerEntry $entry, LockedWallet $wallet, WalletMoney $amount, PostingSource $source): bool
    {
        return $entry->wallet_id === $wallet->walletId && $entry->origin_operation_id === $source->originOperationId && $this->amountOf($entry) === $amount->amount();
    }

    private function receipt(LedgerEntry $entry, bool $replayed): PostingReceipt
    {
        return new PostingReceipt($entry->id, $entry->kind, $entry->wallet_id, $entry->source_type, $entry->source_id, (string) $entry->origin_operation_id,
            $this->amountOf($entry), (string) $entry->payload['recorded_at'], $replayed, $entry->cause_type, $entry->cause_id);
    }

    private function amountOf(LedgerEntry $entry): string
    {
        return (string) LedgerLine::query()->where('entry_id', $entry->id)->where('direction', 'debit')->value('amount');
    }

    /** An Investor bucket's balance from its postings. */
    private function bucket(string $walletId, string $kind): WalletMoney
    {
        $sums = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.wallet_id', $walletId)->where('ledger_accounts.kind', $kind)
            ->selectRaw("coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)::text AS credits, coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0)::text AS debits")
            ->first();

        return WalletBalance::bucket(WalletMoney::of((string) $sums?->credits), WalletMoney::of((string) $sums?->debits));
    }

    private function account(string $kind, string $walletId): string
    {
        if ($kind === 'disbursement_settlement') {
            DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, NULL, ?, 'RWF', now())
                ON CONFLICT (kind) WHERE wallet_id IS NULL DO NOTHING", [strtolower((string) Str::ulid()), $kind]);

            return (string) DB::table('ledger_accounts')->whereNull('wallet_id')->where('kind', $kind)->value('id');
        }
        DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, ?, ?, 'RWF', now())
            ON CONFLICT (wallet_id, kind) WHERE wallet_id IS NOT NULL DO NOTHING", [strtolower((string) Str::ulid()), $walletId, $kind]);

        return (string) DB::table('ledger_accounts')->where('wallet_id', $walletId)->where('kind', $kind)->value('id');
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new WalletViolation('WALLET_POSTING_TRANSACTION_REQUIRED');
        }
    }
}
