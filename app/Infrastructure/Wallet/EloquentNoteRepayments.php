<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\NoteRepayments;
use App\Models\BusinessRepayment;
use Illuminate\Support\Facades\DB;

/**
 * Whether an issued note has been repaid in full: every instalment on its disbursement closing's
 * due dates has been named by a repayment, the rule `SimulatedServicingRecord` uses. A note not
 * issued yet is not repaid.
 */
final class EloquentNoteRepayments implements NoteRepayments
{
    public function repaid(string $campaignId): bool
    {
        $dueDates = DB::table('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
            ->where('disbursements.business_campaign_id', $campaignId)->whereNotNull('disbursement_closings.due_dates')->value('disbursement_closings.due_dates');
        /** @var list<string> $instalments */
        $instalments = $dueDates === null ? [] : json_decode((string) $dueDates, true);
        $paid = [];
        foreach (BusinessRepayment::query()->where('note_id', strtolower($campaignId))->get() as $repayment) {
            /** @var list<int> $indexes */
            $indexes = $repayment->payload['instalment_indexes'] ?? [];
            foreach ($indexes as $index) {
                $paid[(int) $index] = true;
            }
        }

        return $instalments !== [] && array_all(array_keys($instalments), fn (int $offset): bool => isset($paid[$offset + 1]));
    }
}
