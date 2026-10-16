<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\ReconciliationStore;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reads the provider clearing accounts from `ledger_lines` and the provider's signed outcomes from
 * the four outcome tables (`wallet_provider_events`, `business_provider_events`,
 * `wallet_withdrawal_events`, `business_withdrawal_events`). Amounts are summed in the database
 * as whole francs, so nothing is rounded.
 *
 * @phpstan-import-type Account from ReconciliationStore
 * @phpstan-import-type BreakRecord from ReconciliationStore
 */
final readonly class EloquentReconciliationStore implements ReconciliationStore
{
    /**
     * How much of each provider outcome the provider says moved, by disposition and state. A
     * withdrawal counts its quoted charge with what was received, as its payout posting does.
     */
    private const string MOVED = <<<'SQL'
        CASE
            WHEN state = 'succeeded' AND disposition IN ('applied', 'conflict') THEN expected
            WHEN state = 'succeeded' AND disposition IN ('mismatch', 'key_conflict') THEN reported
            WHEN state = 'failed' AND disposition = 'after_final' THEN -expected
            ELSE 0
        END
        SQL;

    public function accounts(DateTimeInterface $asOf): array
    {
        return [
            ['id' => 'deposits', 'ledger' => $this->ledger('deposit_clearing', 'debit', $asOf), 'statement' => $this->statement('deposits', $asOf)],
            ['id' => 'withdrawals', 'ledger' => $this->ledger('withdrawal_clearing', 'credit', $asOf), 'statement' => $this->statement('withdrawals', $asOf)],
        ];
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

        return $breaks;
    }

    /** The account's balance on its natural side, from every line posted by the instant. */
    private function ledger(string $kind, string $side, DateTimeInterface $asOf): string
    {
        $sum = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.kind', $kind)->where('ledger_lines.created_at', '<=', $asOf)
            ->selectRaw('coalesce(sum(CASE WHEN ledger_lines.direction = ? THEN ledger_lines.amount ELSE -ledger_lines.amount END), 0) AS total', [$side])
            ->value('total');

        return (string) $sum;
    }

    private function statement(string $account, DateTimeInterface $asOf): string
    {
        $sum = DB::query()->fromSub($this->outcomes($asOf), 'outcome')->where('account_id', $account)
            ->selectRaw('coalesce(sum('.self::MOVED.'), 0) AS total')->value('total');

        return (string) $sum;
    }

    /**
     * Every provider outcome recorded by the instant, in one shape: which account and flow it
     * belongs to, what the record expected to move and what the provider reported.
     */
    private function outcomes(DateTimeInterface $asOf): Builder
    {
        $investorDeposits = DB::table('wallet_provider_events AS event')->join('wallet_deposit_intents AS record', 'record.id', '=', 'event.intent_id')
            ->where('event.created_at', '<=', $asOf)->selectRaw("event.id, 'deposits' AS account_id, 'investor' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, event.amount AS expected, event.amount AS reported, event.created_at, record.party_id, NULL AS business_id');
        $businessDeposits = DB::table('business_provider_events AS event')->join('business_deposit_intents AS record', 'record.id', '=', 'event.intent_id')
            ->where('event.created_at', '<=', $asOf)->selectRaw("event.id, 'deposits' AS account_id, 'business' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, event.amount AS expected, event.amount AS reported, event.created_at, NULL AS party_id, record.business_id');
        $investorWithdrawals = DB::table('wallet_withdrawal_events AS event')->join('wallet_withdrawals AS record', 'record.id', '=', 'event.withdrawal_id')
            ->where('event.created_at', '<=', $asOf)->selectRaw("event.id, 'withdrawals' AS account_id, 'investor' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, record.amount AS expected, event.amount + record.provider_charge AS reported, event.created_at, record.party_id, NULL AS business_id');
        $businessWithdrawals = DB::table('business_withdrawal_events AS event')->join('business_withdrawals AS record', 'record.id', '=', 'event.withdrawal_id')
            ->where('event.created_at', '<=', $asOf)->selectRaw("event.id, 'withdrawals' AS account_id, 'business' AS flow, event.state, event.disposition, "
                .'event.provider_event_id, record.amount AS expected, event.amount + record.provider_charge AS reported, event.created_at, NULL AS party_id, record.business_id');

        return $investorDeposits->unionAll($businessDeposits)->unionAll($investorWithdrawals)->unionAll($businessWithdrawals);
    }
}
