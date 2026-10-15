<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedNoteInstalment;

/**
 * Repayments over Business Home. No servicing domain is bound yet, so nothing is due or payable;
 * what the Business can see is each issued Note's schedule as it was issued: every instalment's
 * Kigali due date and its principal and return, the equal monthly amount the Business pays (BRS
 * glossary, "Instalment"). It carries no paid amounts, late fees or current claim. The Notes are
 * the Business's own funded raises from Home, read under its current authority.
 *
 * @phpstan-type Money array{currency: 'RWF', amount: string}
 * @phpstan-type IssuedInstalment array{index: int, due_on: string, principal: Money, return: Money, total: Money}
 * @phpstan-type IssuedSchedule array{note_id: string, title: string, effective_date: string|null, principal: Money, return: Money, total: Money, instalments: list<IssuedInstalment>}
 */
final readonly class GetBusinessRepayments
{
    public function __construct(private GetBusinessHome $home, private RetainedNoteSchedules $schedules) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $home = $this->home->handle($userId, $contextRevision, $businessId);
        $issued = [];
        foreach ($home['raises']['notes'] as $note) {
            $schedule = $note['status'] === 'funded' ? $this->schedules->find((string) $note['campaign_id']) : null;
            if ($schedule === null || $schedule->businessId !== (string) $home['entry']['business_id']) {
                continue;
            }
            $issued[] = ['note_id' => $schedule->campaignId, 'title' => (string) $note['title'], 'effective_date' => $schedule->closing->effectiveDate,
                'principal' => self::money($schedule->principal), 'return' => self::money($schedule->contractualReturn), 'total' => self::money($schedule->gross),
                'instalments' => array_map(fn (RetainedNoteInstalment $row): array => ['index' => $row->index, 'due_on' => $row->dueDate,
                    'principal' => self::money($row->principal), 'return' => self::money($row->contractualReturn), 'total' => self::money($row->gross)], $schedule->instalments)];
        }

        return [...$home, 'issued' => $issued];
    }

    /** @return array{currency: 'RWF', amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
