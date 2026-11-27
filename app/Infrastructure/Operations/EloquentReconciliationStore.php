<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\AuthoritativeClearingStatements;
use App\Application\Operations\Contracts\ReconciliationStore;
use Brick\Math\BigInteger;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reads clearing ledger balances against an independent authoritative statement at the same cutoff.
 * Provider outcome exceptions remain a separate source of breaks, never a substitute statement.
 * Ledger amounts are summed as whole francs in one database snapshot, without rounding.
 *
 * @phpstan-import-type Account from ReconciliationStore
 * @phpstan-import-type BreakRecord from ReconciliationStore
 */
final readonly class EloquentReconciliationStore implements ReconciliationStore
{
    public function __construct(private AuthoritativeClearingStatements $statements) {}

    public function accounts(DateTimeInterface $asOf): array
    {
        // Preserve the offset and microseconds: Laravel's default DateTime binding drops both.
        $cutoff = $asOf->format('Y-m-d H:i:s.uP');
        $statements = [];
        foreach ($this->statements->at($asOf) as $statement) {
            if ($statement->asOf->format('U.u') !== $asOf->format('U.u') || isset($statements[$statement->accountId])) {
                return [];
            }
            $statements[$statement->accountId] = $statement;
        }
        if ($statements === []) {
            return [];
        }
        // Both ledger accounts share one MVCC snapshot and the statement's exact cutoff.
        $ledger = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->whereIn('ledger_accounts.kind', ['deposit_clearing', 'withdrawal_clearing'])->where('ledger_lines.created_at', '<=', $cutoff)
            ->groupBy('ledger_accounts.kind')->selectRaw("ledger_accounts.kind, sum(CASE WHEN
                (ledger_accounts.kind = 'deposit_clearing' AND ledger_lines.direction = 'debit') OR
                (ledger_accounts.kind = 'withdrawal_clearing' AND ledger_lines.direction = 'credit')
                THEN ledger_lines.amount ELSE -ledger_lines.amount END) AS total");
        $rows = DB::query()->fromRaw("(VALUES ('deposits', 'deposit_clearing'), ('withdrawals', 'withdrawal_clearing')) AS clearing(id, kind)")
            ->leftJoinSub($ledger, 'ledger', 'ledger.kind', '=', 'clearing.kind')
            ->orderBy('clearing.id')->get(['clearing.id', DB::raw('coalesce(ledger.total, 0)::text AS ledger')]);
        $accounts = [];
        foreach ($rows as $row) {
            $id = $row->id === 'deposits' ? 'deposits' : 'withdrawals';
            if (isset($statements[$id])) {
                $accounts[] = ['id' => $id, 'ledger' => (string) $row->ledger, 'statement' => $statements[$id]->amount, 'statement_reference' => $statements[$id]->reference];
            }
        }

        return $accounts;
    }

    public function breaks(DateTimeInterface $asOf): array
    {
        $rows = DB::query()->fromSub($this->outcomes($asOf), 'outcome')
            ->where(fn (Builder $query) => $query->where(fn (Builder $failed) => $failed->where('state', 'failed')->where('disposition', 'after_final'))
                ->orWhere(fn (Builder $succeeded) => $succeeded->where('state', 'succeeded')->whereIn('disposition', ['conflict', 'mismatch', 'key_conflict'])))
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'account_id', 'flow', 'disposition', 'provider_event_id', 'expected', 'reported', 'created_at', 'party_id', 'business_id']);
        $breaks = [];
        foreach ($rows as $row) {
            $reported = in_array($row->disposition, ['mismatch', 'key_conflict'], true);
            $breaks[] = [
                'id' => (string) $row->id,
                'account_id' => $row->account_id === 'deposits' ? 'deposits' : 'withdrawals',
                'flow' => $row->flow === 'investor' ? 'investor' : 'business',
                'kind' => match ($row->disposition) {
                    'after_final' => 'reversed', 'conflict' => 'conflict', 'mismatch' => 'mismatch', default => 'key_conflict',
                },
                'reference' => (string) $row->provider_event_id,
                'amount' => (string) ($reported ? $row->reported : $row->expected),
                'opened_at' => (string) $row->created_at,
                'party_id' => $row->party_id === null ? null : (string) $row->party_id,
                'business_id' => $row->business_id === null ? null : (string) $row->business_id,
            ];
        }

        foreach (DB::table('reconciliation_variances')->where('created_at', '<=', $asOf->format('Y-m-d H:i:s.uP'))->orderBy('created_at')->orderBy('id')->get() as $case) {
            $breaks[] = ['id' => (string) $case->id, 'account_id' => $case->account_id === 'deposits' ? 'deposits' : 'withdrawals',
                'flow' => 'clearing', 'kind' => 'balance_variance', 'reference' => 'REC-'.$case->business_date.'-'.$case->account_id,
                'amount' => (string) BigInteger::of((string) $case->difference)->abs(), 'opened_at' => (string) $case->created_at,
                'cutoff_at' => (string) $case->cutoff_at, 'statement_reference' => (string) $case->statement_reference, 'party_id' => null, 'business_id' => null];
        }
        usort($breaks, fn (array $left, array $right): int => [$left['opened_at'], $left['id']] <=> [$right['opened_at'], $right['id']]);

        return $breaks;
    }

    /**
     * Every provider outcome recorded by the instant, in one shape: which account and flow it
     * belongs to, what the record expected to move and what the provider reported.
     */
    private function outcomes(DateTimeInterface $asOf): Builder
    {
        $cutoff = $asOf->format('Y-m-d H:i:s.uP');
        $investorDeposits = DB::table('wallet_provider_events AS event')->join('wallet_deposit_intents AS record', 'record.id', '=', 'event.intent_id')
            ->where('event.created_at', '<=', $cutoff)->selectRaw("event.id, 'deposits' AS account_id, 'investor' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, event.amount AS expected, event.amount AS reported, event.created_at, record.party_id, NULL AS business_id');
        $businessDeposits = DB::table('business_provider_events AS event')->join('business_deposit_intents AS record', 'record.id', '=', 'event.intent_id')
            ->where('event.created_at', '<=', $cutoff)->selectRaw("event.id, 'deposits' AS account_id, 'business' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, event.amount AS expected, event.amount AS reported, event.created_at, NULL AS party_id, record.business_id');
        $investorWithdrawals = DB::table('wallet_withdrawal_events AS event')->join('wallet_withdrawals AS record', 'record.id', '=', 'event.withdrawal_id')
            ->where('event.created_at', '<=', $cutoff)->selectRaw("event.id, 'withdrawals' AS account_id, 'investor' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, record.amount AS expected, event.amount + record.provider_charge AS reported, event.created_at, record.party_id, NULL AS business_id');
        $businessWithdrawals = DB::table('business_withdrawal_events AS event')->join('business_withdrawals AS record', 'record.id', '=', 'event.withdrawal_id')
            ->where('event.created_at', '<=', $cutoff)->selectRaw("event.id, 'withdrawals' AS account_id, 'business' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, record.amount AS expected, event.amount + record.provider_charge AS reported, event.created_at, NULL AS party_id, record.business_id');

        return $investorDeposits->unionAll($businessDeposits)->unionAll($investorWithdrawals)->unionAll($businessWithdrawals);
    }
}
