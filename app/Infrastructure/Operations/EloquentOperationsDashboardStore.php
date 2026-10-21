<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\OperationsDashboardStore;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Figures from OperationsDashboardStore
 * @phpstan-import-type Bar from OperationsDashboardStore
 * @phpstan-import-type Movements from OperationsDashboardStore
 * @phpstan-import-type Payout from OperationsDashboardStore
 * @phpstan-import-type Sector from OperationsDashboardStore
 */
final class EloquentOperationsDashboardStore implements OperationsDashboardStore
{
    /** How each bucket is labelled under the bar. */
    private const LABELS = ['year' => 'YYYY', 'month' => 'YYYY-MM', 'day' => 'MM-DD', 'hour' => 'DD HH24:00'];

    /** The postings that credit a deposit, Investor and Business. */
    private const DEPOSITS = ['deposit_credit', 'business_deposit_credit'];

    /** The postings that pay a withdrawal out, Investor and Business. */
    private const PAYOUTS = ['withdrawal_payout', 'business_withdrawal_payout'];

    /** @return Figures */
    public function figures(): array
    {
        $now = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $notes = DB::table('business_campaigns AS c')
            ->leftJoin('business_campaign_closures AS closure', 'closure.business_campaign_id', '=', 'c.id')
            ->leftJoin('primary_campaign_fundings AS funding', 'funding.business_campaign_id', '=', 'c.id')
            ->leftJoin('disbursements AS d', 'd.business_campaign_id', '=', 'c.id')
            ->leftJoin('disbursement_closings AS closing', 'closing.disbursement_id', '=', 'd.id')
            ->selectRaw("count(*) FILTER (WHERE closure.id IS NULL AND funding.id IS NULL) AS live,
                count(*) FILTER (WHERE closure.id IS NULL AND funding.id IS NOT NULL AND closing.id IS NULL) AS funded,
                count(*) FILTER (WHERE closing.kind = 'issued') AS repaying,
                count(*) FILTER (WHERE closure.id IS NOT NULL OR closing.kind = 'failed_closing') AS failed")->first();
        $businesses = DB::table('business_profiles AS b')
            ->join('business_mandates AS m', fn ($join) => $join->on('m.business_id', '=', 'b.id')->on('m.version', '=', 'b.mandate_version'))
            ->whereRaw("m.terms->>'status' = 'active'")->whereRaw("m.terms->>'effective_at' <= ?", [$now])
            ->whereRaw("(m.terms->>'expires_at' IS NULL OR m.terms->>'expires_at' > ?)", [$now])->count();
        $disbursed = DB::table('disbursements AS d')->join('disbursement_closings AS closing', 'closing.disbursement_id', '=', 'd.id')
            ->where('closing.kind', 'issued')->sum('d.amount');
        $waiting = DB::selectOne("SELECT count(*) AS total FROM disbursements d
            WHERE (SELECT f_state FROM disbursement_fold(d.id)) IN ('ready', 'awaiting_second_approver', 'on_hold')");

        return ['capital_raised' => (string) DB::table('primary_holdings')->sum('principal'), 'active_businesses' => $businesses,
            'disbursements_waiting' => (int) ($waiting->total ?? 0), 'disbursed' => (string) $disbursed,
            'notes' => ['live' => (int) ($notes->live ?? 0), 'funded' => (int) ($notes->funded ?? 0),
                'repaying' => (int) ($notes->repaying ?? 0), 'failed' => (int) ($notes->failed ?? 0)]];
    }

    public function noteStates(): array
    {
        return array_values(array_map(fn (object $row): array => ['campaign_id' => (string) $row->id, 'state' => match ((string) $row->state) {
            'live' => 'live', 'funded' => 'funded', 'repaying' => 'repaying', default => 'failed',
        }, 'live_at' => CarbonImmutable::parse((string) $row->live_at)->toIso8601String()], DB::table('business_campaigns AS c')
            ->leftJoin('business_campaign_closures AS closure', 'closure.business_campaign_id', '=', 'c.id')
            ->leftJoin('primary_campaign_fundings AS funding', 'funding.business_campaign_id', '=', 'c.id')
            ->leftJoin('disbursements AS d', 'd.business_campaign_id', '=', 'c.id')
            ->leftJoin('disbursement_closings AS closing', 'closing.disbursement_id', '=', 'd.id')
            ->selectRaw("c.id, c.live_at, CASE
                WHEN closure.id IS NOT NULL OR closing.kind = 'failed_closing' THEN 'failed'
                WHEN closing.kind = 'issued' THEN 'repaying'
                WHEN funding.id IS NOT NULL AND closing.id IS NULL THEN 'funded'
                WHEN funding.id IS NULL THEN 'live'
                ELSE 'failed' END AS state")
            ->orderByDesc('c.live_at')->orderByDesc('c.id')->get()->all()));
    }

    /** @return list<Bar> */
    public function capitalRaised(string $grain, ?string $from, ?string $to): array
    {
        return $this->bars($grain, DB::table('primary_holdings')
            ->selectRaw("date_trunc(?, issued_at AT TIME ZONE 'Africa/Kigali') AS bucket, sum(principal) AS amount", [$grain])
            ->when($from !== null, fn ($query) => $query->where('issued_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->where('issued_at', '<', $to))
            ->groupBy('bucket'));
    }

    /** @return list<Bar> */
    public function committed(string $grain, ?string $from, ?string $to): array
    {
        return $this->bars($grain, DB::table('ledger_entries AS e')->join('ledger_lines AS l', 'l.entry_id', '=', 'e.id')
            ->selectRaw("date_trunc(?, e.created_at AT TIME ZONE 'Africa/Kigali') AS bucket, sum(l.amount) AS amount", [$grain])
            ->where('e.kind', 'primary_commit')->where('l.direction', 'credit')
            ->when($from !== null, fn ($query) => $query->where('e.created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->where('e.created_at', '<', $to))
            ->groupBy('bucket'));
    }

    /** @return Movements */
    public function movements(string $from): array
    {
        $movement = fn (?object $row): array => ['amount' => (string) ($row->amount ?? '0'), 'count' => (int) ($row->total ?? 0)];
        $entries = fn (array $kinds): array => $movement(DB::table('ledger_entries AS e')->join('ledger_lines AS l', 'l.entry_id', '=', 'e.id')
            ->whereIn('e.kind', $kinds)->where('l.direction', 'debit')->where('e.created_at', '>=', $from)
            ->selectRaw('COALESCE(sum(l.amount), 0)::text AS amount, count(DISTINCT e.id) AS total')->first());
        $disbursed = DB::table('disbursements AS d')->join('disbursement_closings AS closing', 'closing.disbursement_id', '=', 'd.id')
            ->where('closing.kind', 'issued')->where('closing.created_at', '>=', $from)
            ->selectRaw('COALESCE(sum(d.amount), 0)::text AS amount, count(*) AS total')->first();

        return ['deposits' => $entries(self::DEPOSITS), 'withdrawals' => $entries(self::PAYOUTS), 'disbursed' => $movement($disbursed)];
    }

    /** @return list<Bar> */
    public function moved(string $grain, ?string $from, ?string $to): array
    {
        return $this->bars($grain, DB::table('ledger_entries AS e')->join('ledger_lines AS l', 'l.entry_id', '=', 'e.id')
            ->selectRaw("date_trunc(?, e.created_at AT TIME ZONE 'Africa/Kigali') AS bucket, sum(l.amount) AS amount", [$grain])
            ->whereIn('e.kind', [...self::DEPOSITS, ...self::PAYOUTS])->where('l.direction', 'debit')
            ->when($from !== null, fn ($query) => $query->where('e.created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->where('e.created_at', '<', $to))
            ->groupBy('bucket'));
    }

    /** @return list<Payout> */
    public function payoutsInFlight(): array
    {
        $investor = $this->inFlight('wallet_withdrawals', 'wallet_withdrawal_events', 'investor_funding_methods', 'wallet_withdrawal',
            'SELECT users.name FROM users WHERE users.party_id = w.party_id ORDER BY users.id LIMIT 1', 'investor');
        $business = $this->inFlight('business_withdrawals', 'business_withdrawal_events', 'business_funding_methods', 'business_withdrawal',
            "SELECT business_profiles.profile->>'name' FROM business_profiles WHERE business_profiles.id = w.business_id", 'business');

        return array_values(array_map(fn (object $row): array => ['id' => (string) $row->id, 'owner' => $row->owner === 'business' ? 'business' : 'investor',
            'name' => $row->name === null ? null : (string) $row->name, 'amount' => (string) $row->amount, 'received' => (string) $row->received,
            'account' => (string) $row->account, 'state' => $row->state === 'unknown' ? 'unknown' : 'pending',
            'requested_at' => CarbonImmutable::parse((string) $row->created_at)->utc()->toIso8601String()],
            DB::query()->fromSub($investor->unionAll($business), 'payouts')->orderBy('created_at')->orderBy('id')->get()->all()));
    }

    /** @return list<Sector> */
    public function sectorExposure(): array
    {
        return array_values(array_map(fn (object $row): array => ['sector' => (string) $row->sector, 'principal' => (string) $row->principal,
            'notes' => (int) $row->notes, 'campaigns' => explode(',', (string) $row->campaigns)], DB::table('primary_holdings AS h')
                ->join('business_campaigns AS c', 'c.id', '=', 'h.business_campaign_id')
                ->join('business_profiles AS b', 'b.id', '=', 'c.business_id')
                ->selectRaw("b.profile->>'industry' AS sector, sum(h.principal) AS principal, count(DISTINCT c.id) AS notes,
                    string_agg(DISTINCT c.id, ',' ORDER BY c.id) AS campaigns")
                ->groupBy('sector')->orderByDesc('principal')->orderBy('sector')->get()->all()));
    }

    /**
     * Withdrawals of one owner kind with no payout or return entry yet, with the state of their latest applied event.
     *
     * @param  literal-string  $events
     * @param  literal-string  $name
     */
    private function inFlight(string $table, string $events, string $methods, string $source, string $name, string $owner): Builder
    {
        return DB::table($table.' AS w')
            ->join($methods.' AS m', 'm.id', '=', 'w.method_id')
            ->whereNotExists(fn ($query) => $query->from('ledger_entries AS e')->whereColumn('e.source_id', 'w.id')
                ->where('e.source_type', $source)->whereIn('e.kind', $owner === 'investor' ? ['withdrawal_payout', 'withdrawal_return'] : ['business_withdrawal_payout', 'business_withdrawal_return']))
            ->selectRaw("w.id, ? AS owner, ({$name}) AS name, w.amount::text AS amount, w.received::text AS received, m.label || ' ' || m.masked AS account,
                COALESCE((SELECT ev.state FROM {$events} ev WHERE ev.withdrawal_id = w.id AND ev.disposition = 'applied' ORDER BY ev.created_at DESC, ev.id DESC LIMIT 1), 'pending') AS state,
                w.created_at", [$owner]);
    }

    /**
     * Labels each bucket and sizes its bar as a share of the tallest.
     *
     * @return list<Bar>
     */
    private function bars(string $grain, Builder $buckets): array
    {
        $rows = DB::query()->fromSub($buckets, 'buckets')
            ->selectRaw('to_char(bucket, ?) AS label, amount::text AS amount', [self::LABELS[$grain]])->orderBy('bucket')->get()->all();
        $tallest = array_reduce($rows, fn (BigDecimal $max, object $row): BigDecimal => BigDecimal::max($max, BigDecimal::of((string) $row->amount)), BigDecimal::zero());

        return array_values(array_map(fn (object $row): array => ['label' => (string) $row->label, 'amount' => (string) $row->amount,
            'height_pct' => $tallest->isZero() ? 0 : BigDecimal::of((string) $row->amount)->multipliedBy(100)->dividedBy($tallest, 0, RoundingMode::HalfUp)->toInt()], $rows));
    }
}
