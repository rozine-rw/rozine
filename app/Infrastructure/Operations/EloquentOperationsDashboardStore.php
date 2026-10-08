<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\OperationsDashboardStore;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Figures from OperationsDashboardStore
 * @phpstan-import-type Bar from OperationsDashboardStore
 */
final class EloquentOperationsDashboardStore implements OperationsDashboardStore
{
    /** How each bucket is labelled under the bar. */
    private const LABELS = ['year' => 'YYYY', 'month' => 'YYYY-MM', 'day' => 'MM-DD', 'hour' => 'DD HH24:00'];

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
        $awaiting = DB::selectOne("SELECT count(*) AS total FROM disbursements d
            WHERE (SELECT f_state FROM disbursement_fold(d.id)) = 'awaiting_second_approver'");

        return ['capital_raised' => (string) DB::table('primary_holdings')->sum('principal'), 'active_businesses' => $businesses,
            'awaiting_second_approver' => (int) ($awaiting->total ?? 0), 'disbursed' => (string) $disbursed,
            'notes' => ['live' => (int) ($notes->live ?? 0), 'funded' => (int) ($notes->funded ?? 0),
                'repaying' => (int) ($notes->repaying ?? 0), 'failed' => (int) ($notes->failed ?? 0)]];
    }

    /** @return list<Bar> */
    public function capitalRaised(string $grain, ?string $from, ?string $to): array
    {
        $buckets = DB::table('primary_holdings')
            ->selectRaw("date_trunc(?, issued_at AT TIME ZONE 'Africa/Kigali') AS bucket, sum(principal) AS amount", [$grain])
            ->when($from !== null, fn ($query) => $query->where('issued_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->where('issued_at', '<', $to))
            ->groupBy('bucket');
        $rows = DB::query()->fromSub($buckets, 'buckets')
            ->selectRaw('to_char(bucket, ?) AS label, amount::text AS amount', [self::LABELS[$grain]])->orderBy('bucket')->get()->all();
        $tallest = array_reduce($rows, fn (BigDecimal $max, object $row): BigDecimal => BigDecimal::max($max, BigDecimal::of((string) $row->amount)), BigDecimal::zero());

        return array_values(array_map(fn (object $row): array => ['label' => (string) $row->label, 'amount' => (string) $row->amount,
            'height_pct' => $tallest->isZero() ? 0 : BigDecimal::of((string) $row->amount)->multipliedBy(100)->dividedBy($tallest, 0, RoundingMode::HalfUp)->toInt()], $rows));
    }
}
