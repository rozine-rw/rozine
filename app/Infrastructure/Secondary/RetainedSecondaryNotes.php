<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Primary\GetRetainedNoteSchedule;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteInstalment;
use App\Application\Secondary\Contracts\SecondaryNotes;

/**
 * Reads a note through its authenticated issued schedule, so every size, instalment and issued lot
 * the market prices or moves comes from the retained issue and never from a native column alone.
 * Servicing facts are not available yet; see `SecondaryNotes`.
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
            'health' => 'current', 'posted_through' => null,
            'instalments' => array_map(fn (RetainedNoteInstalment $instalment): array => ['due_on' => $instalment->dueDate,
                'principal' => $instalment->principal, 'return' => $instalment->contractualReturn], $schedule->instalments),
            'issued_lots' => array_map(fn (RetainedHoldingSchedule $holding): array => ['lot_type' => 'primary_holding', 'lot_id' => $holding->holdingId,
                'party_id' => $holding->partyId, 'units' => (string) $holding->units,
                'ordinals' => array_map(fn (array $range): array => ['first' => (string) $range['first'], 'last' => (string) $range['last']], $holding->ordinals)],
                $schedule->holdings)];
    }
}
