<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\ServicingRecord;
use App\Application\Environment\UatMoneySimulation;
use App\Models\BusinessRepayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The servicing record `SimulatedNoteServicing` keeps on UAT while its money simulation is on: each
 * issued note's instalments fall due on its disbursement closing's due dates, an instalment is paid
 * once a repayment names it, on that repayment's Kigali date, and a note is repaid once every
 * instalment is paid, at its last payment. Outside a covered Business nothing has
 * been repaid.
 */
final readonly class SimulatedServicingRecord implements ServicingRecord
{
    public function __construct(private UatMoneySimulation $simulation) {}

    public function forBusiness(string $businessId): array
    {
        $record = ['repaid' => [], 'instalments_paid' => 0, 'paid_on_time' => 0];
        $repaidAt = [];
        if (! $this->simulation->active() || ! $this->simulation->covers($businessId)) {
            return $record;
        }
        $issued = DB::table('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
            ->where('disbursements.business_id', $businessId)->whereNotNull('disbursement_closings.due_dates')->orderBy('disbursements.business_campaign_id')
            ->get(['disbursements.business_campaign_id', 'disbursements.exposure_reservation_id', 'disbursements.amount', 'disbursement_closings.due_dates']);
        foreach ($issued as $note) {
            /** @var list<string> $dueDates */
            $dueDates = json_decode((string) $note->due_dates, true);
            $paidOn = [];
            $paidAt = [];
            foreach (BusinessRepayment::query()->where('note_id', strtolower((string) $note->business_campaign_id))->orderBy('id')->get() as $repayment) {
                $on = CarbonImmutable::parse((string) $repayment->payload['recorded_at'])->setTimezone('Africa/Kigali')->toDateString();
                /** @var list<int> $indexes */
                $indexes = $repayment->payload['instalment_indexes'];
                foreach ($indexes as $index) {
                    $paidOn[$index] ??= $on;
                    $paidAt[$index] ??= CarbonImmutable::parse((string) $repayment->payload['recorded_at'])->utc()->format('Y-m-d\TH:i:s.u\Z');
                }
            }
            $complete = $dueDates !== [];
            $repaidOn = '';
            foreach ($dueDates as $offset => $dueDate) {
                $on = $paidOn[$offset + 1] ?? null;
                $complete = $complete && $on !== null;
                $repaidOn = max($repaidOn, $paidAt[$offset + 1] ?? '');
                $record['instalments_paid'] += $on === null ? 0 : 1;
                $record['paid_on_time'] += $on !== null && $on <= substr($dueDate, 0, 10) ? 1 : 0;
            }
            if ($complete) {
                $repaidAt[] = $repaidOn;
                $record['repaid'][] = ['campaign_id' => (string) $note->business_campaign_id, 'exposure_reservation_id' => (string) $note->exposure_reservation_id,
                    'principal' => (string) $note->amount];
            }
        }
        // In the order they were repaid in full, the last one repaid last; notes are already in creation order.
        array_multisort($repaidAt, SORT_ASC, SORT_STRING, array_keys($record['repaid']), SORT_ASC, $record['repaid']);

        return $record;
    }
}
