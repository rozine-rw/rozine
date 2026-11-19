<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Disbursement\Contracts\DisbursementPayoutLedger;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessWallet;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The disbursement payout posting, owned by the Business wallet it was paid for, on the closing that
 * issued the note. Its lines touch only platform accounts, so no Business balance moves; the
 * database binds it to its closing's intent amount and operation.
 */
final class EloquentDisbursementPayoutLedger implements DisbursementPayoutLedger
{
    public function __construct(private CanonicalJson $json) {}

    public function post(string $businessId, string $closingId, string $operationId, string $net): bool
    {
        if (DB::transactionLevel() < 1) {
            throw new WalletViolation('WALLET_POSTING_TRANSACTION_REQUIRED');
        }
        if (! DB::table('business_profiles')->where('id', $businessId)->exists()) {
            return false;
        }
        DB::insert("INSERT INTO business_wallets (id, business_id, currency, created_at) VALUES (?, ?, 'RWF', now()) ON CONFLICT (business_id) DO NOTHING",
            [strtolower((string) Str::ulid()), $businessId]);
        $wallet = BusinessWallet::query()->where('business_id', $businessId)->lockForUpdate()->sole();
        $previous = LedgerEntry::query()->where('kind', 'disbursement_payout')->where('source_type', 'disbursement_closing')->where('source_id', $closingId)->first();
        if ($previous !== null) {
            if ($previous->wallet_id !== $wallet->id || $previous->origin_operation_id !== $operationId) {
                throw new WalletViolation('WALLET_POSTING_CONFLICT');
            }

            return true;
        }
        $journal = JournalEntry::disbursementPayout(WalletMoney::of($net));
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $lines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()], $journal->lines);
        $payload = ['entry_id' => $entry->id, 'wallet_id' => $wallet->id, 'kind' => $journal->kind, 'source_type' => 'disbursement_closing', 'source_id' => $closingId,
            'origin_operation_id' => $operationId, 'lines' => $lines, 'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $wallet->id, 'kind' => $journal->kind, 'source_type' => 'disbursement_closing', 'source_id' => $closingId,
            'origin_operation_id' => $operationId, 'cause_type' => null, 'cause_id' => null, 'currency' => 'RWF', 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        foreach ($journal->lines as $line) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($line->account), 'direction' => $line->direction,
                'amount' => $line->amount->amount(), 'created_at' => $recordedAt])->save();
        }
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced DEFERRED');

        return true;
    }

    private function account(string $kind): string
    {
        DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, NULL, ?, 'RWF', now())
            ON CONFLICT (kind) WHERE wallet_id IS NULL DO NOTHING", [strtolower((string) Str::ulid()), $kind]);

        return (string) DB::table('ledger_accounts')->whereNull('wallet_id')->where('kind', $kind)->value('id');
    }
}
