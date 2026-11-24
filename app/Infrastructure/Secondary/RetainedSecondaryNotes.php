<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Primary\GetRetainedNoteSchedule;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteInstalment;
use App\Application\Secondary\Contracts\SecondaryNotes;
use Illuminate\Support\Facades\DB;

/**
 * Reads a note through its authenticated issued schedule, so every size, instalment and issued lot
 * the market prices or moves comes from the retained issue and never from a native column alone.
 * Health is not available yet; see `SecondaryNotes`. `posted_through` is the due date of the last
 * instalment, counted in order from the first, whose payouts have posted (`servicing_payouts`), so
 * an instalment paid on to its holders reopens trading and leaves the price cap.
 */
final readonly class RetainedSecondaryNotes implements SecondaryNotes
{
    public function __construct(private GetRetainedNoteSchedule $schedules) {}

    public function find(string $noteId): ?array
    {
        $schedule = $this->schedules->find($noteId);
        if ($schedule === null) {
            return null;
        }

        return ['note_id' => $schedule->campaignId,
            'raise_units' => (string) array_sum(array_map(fn (RetainedHoldingSchedule $holding): int => $holding->units, $schedule->holdings)),
            'health' => 'current', 'posted_through' => self::postedThrough($schedule->campaignId, $schedule->instalments),
            'instalments' => array_map(fn (RetainedNoteInstalment $instalment): array => ['due_on' => $instalment->dueDate,
                'principal' => $instalment->principal, 'return' => $instalment->contractualReturn], $schedule->instalments),
            'issued_lots' => array_map(fn (RetainedHoldingSchedule $holding): array => ['lot_type' => 'primary_holding', 'lot_id' => $holding->holdingId,
                'party_id' => $holding->partyId, 'units' => (string) $holding->units,
                'ordinals' => array_map(fn (array $range): array => ['first' => (string) $range['first'], 'last' => (string) $range['last']], $holding->ordinals)],
                $schedule->holdings)];
    }

    /** @param  list<RetainedNoteInstalment>  $instalments */
    private static function postedThrough(string $campaignId, array $instalments): ?string
    {
        $paid = [];
        foreach (DB::table('servicing_payouts')->where('note_id', strtolower($campaignId))->pluck('instalment_indexes') as $indexes) {
            foreach (json_decode((string) $indexes, true, flags: JSON_THROW_ON_ERROR) as $index) {
                $paid[(int) $index] = true;
            }
        }
        $through = null;
        foreach ($instalments as $instalment) {
            if (! isset($paid[$instalment->index])) {
                break;
            }
            $through = $instalment->dueDate;
        }

        return $through;
    }
}
