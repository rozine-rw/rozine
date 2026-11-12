<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Secondary\Contracts\SecondaryLedger;
use App\Application\Wallet\LockedWallet;
use App\Domain\Operations\CommandRejection;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The resale postings (LED-R02, SEC-R06), each one balanced immutable entry in the caller's open
 * transaction on a wallet the caller has locked with `WalletPostings::lockForParty`. PostgreSQL binds every one to its reservation,
 * its amounts and its order again at commit; see the secondary market migration.
 *
 * - hold: the buyer's price leaves `investor_available` for `investor_held`, the same hold a
 *   commitment posts; a short available balance refuses `INSUFFICIENT_AVAILABLE_FUNDS` (422).
 * - release: the held price goes back to `investor_available`.
 * - purchase: the held price moves to the platform `secondary_settlement` account.
 * - sale: `secondary_settlement` pays the seller's `investor_available` less the seller fee, which
 *   goes to `secondary_fee_revenue`.
 */
final readonly class WalletSecondaryLedger implements SecondaryLedger
{
    public function __construct(private CanonicalJson $json) {}

    public function hold(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string
    {
        $available = $this->bucket($buyer->walletId, 'investor_available');
        if ($available->isLessThan($gross)) {
            throw new CommandRejection('INSUFFICIENT_AVAILABLE_FUNDS', 422, data: ['available' => ['currency' => 'RWF', 'amount' => (string) $available]]);
        }

        return $this->post('secondary_hold', $buyer, $reservationId, $operationId,
            [['investor_available', 'debit', $gross], ['investor_held', 'credit', $gross]]);
    }

    public function release(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string
    {
        return $this->post('secondary_release', $buyer, $reservationId, $operationId,
            [['investor_held', 'debit', $gross], ['investor_available', 'credit', $gross]]);
    }

    public function purchase(LockedWallet $buyer, BigInteger $gross, string $reservationId, string $operationId): string
    {
        return $this->post('secondary_purchase', $buyer, $reservationId, $operationId,
            [['investor_held', 'debit', $gross], ['secondary_settlement', 'credit', $gross]]);
    }

    public function sale(LockedWallet $seller, BigInteger $gross, BigInteger $receives, BigInteger $fee, string $reservationId, string $operationId): string
    {
        $lines = [['secondary_settlement', 'debit', $gross], ['investor_available', 'credit', $receives]];
        if ($fee->isPositive()) {
            $lines[] = ['secondary_fee_revenue', 'credit', $fee];
        }

        return $this->post('secondary_sale', $seller, $reservationId, $operationId, $lines);
    }

    /** @param list<array{0: string, 1: 'debit'|'credit', 2: BigInteger}> $lines */
    private function post(string $kind, LockedWallet $wallet, string $reservationId, string $operationId, array $lines): string
    {
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $payload = ['entry_id' => $entry->id, 'wallet_id' => $wallet->walletId, 'kind' => $kind, 'source_type' => 'secondary_reservation',
            'source_id' => $reservationId, 'origin_operation_id' => $operationId,
            'lines' => array_map(fn (array $line): array => ['account' => $line[0], 'direction' => $line[1], 'amount' => (string) $line[2]], $lines),
            'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $wallet->walletId, 'kind' => $kind, 'source_type' => 'secondary_reservation', 'source_id' => $reservationId,
            'origin_operation_id' => $operationId, 'cause_type' => null, 'cause_id' => null, 'currency' => 'RWF', 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        foreach ($lines as [$account, $direction, $amount]) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($account, $wallet->walletId),
                'direction' => $direction, 'amount' => (string) $amount, 'created_at' => $recordedAt])->save();
        }
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced DEFERRED');

        return $entry->id;
    }

    private function bucket(string $walletId, string $kind): BigInteger
    {
        $sums = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.wallet_id', $walletId)->where('ledger_accounts.kind', $kind)
            ->selectRaw("coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)::text AS credits, coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0)::text AS debits")
            ->first();

        return BigInteger::of((string) $sums?->credits)->minus((string) $sums?->debits);
    }

    private function account(string $kind, string $walletId): string
    {
        if (in_array($kind, ['secondary_settlement', 'secondary_fee_revenue'], true)) {
            DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, NULL, ?, 'RWF', now())
                ON CONFLICT (kind) WHERE wallet_id IS NULL DO NOTHING", [strtolower((string) Str::ulid()), $kind]);

            return (string) DB::table('ledger_accounts')->whereNull('wallet_id')->where('kind', $kind)->value('id');
        }
        DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, ?, ?, 'RWF', now())
            ON CONFLICT (wallet_id, kind) WHERE wallet_id IS NOT NULL DO NOTHING", [strtolower((string) Str::ulid()), $walletId, $kind]);

        return (string) DB::table('ledger_accounts')->where('wallet_id', $walletId)->where('kind', $kind)->value('id');
    }
}
