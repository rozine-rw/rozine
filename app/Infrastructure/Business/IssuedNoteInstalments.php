<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\UpcomingInstalments;
use Illuminate\Support\Facades\DB;

/** Reads the due dates fixed on each issued disbursement closing, one row per Business and day. */
final class IssuedNoteInstalments implements UpcomingInstalments
{
    public function between(string $from, string $to): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT DISTINCT disbursements.business_id, due.due_on
            FROM disbursement_closings closing
            JOIN disbursements ON disbursements.id = closing.disbursement_id
            CROSS JOIN LATERAL jsonb_array_elements_text(closing.due_dates) AS due(due_on)
            WHERE closing.kind = 'issued' AND due.due_on BETWEEN ? AND ?
            ORDER BY due.due_on, disbursements.business_id
            SQL, [$from, $to]);

        return array_map(fn (object $row): array => ['business_id' => (string) $row->business_id, 'due_on' => (string) $row->due_on], $rows);
    }
}
