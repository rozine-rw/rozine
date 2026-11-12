<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedNoteInstalment;
use App\Domain\Auditor\VisitReadyChecklist;
use Carbon\CarbonImmutable;

/**
 * Repayments over Business Home. No servicing domain is bound yet, so nothing is due or payable;
 * what the Business can see is each issued Note's schedule as it was issued: every instalment's
 * Kigali due date and its principal and return, the equal monthly amount the Business pays (BRS
 * glossary, "Instalment"). It carries no paid amounts, late fees or current claim. The Notes are
 * the Business's own funded raises from Home, read under its current authority.
 *
 * From D−14 before the next instalment falls due, the reminder carries the visit-ready checklist
 * (BRS v3 VIS-R03): what the CPA will ask for on the monthly visit, generated from its steps.
 *
 * @phpstan-type Money array{currency: 'RWF', amount: string}
 * @phpstan-type IssuedInstalment array{index: int, due_on: string, principal: Money, return: Money, total: Money}
 * @phpstan-type IssuedSchedule array{note_id: string, title: string, effective_date: string|null, principal: Money, return: Money, total: Money, instalments: list<IssuedInstalment>}
 */
final readonly class GetBusinessRepayments
{
    public function __construct(private GetBusinessHome $home, private RetainedNoteSchedules $schedules, private VisitReadyChecklist $checklist) {}

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

        $today = CarbonImmutable::now('Africa/Kigali')->toDateString();
        $upcoming = array_filter(array_merge([], ...array_map(fn (array $schedule): array => array_column($schedule['instalments'], 'due_on'), $issued)),
            fn (string $dueOn): bool => $dueOn >= $today);
        $visitReady = $upcoming === [] ? null : $this->checklist->forInstalment(min($upcoming), $today);

        return [...$home, 'issued' => $issued, 'visit_ready' => $visitReady];
    }

    /** @return array{currency: 'RWF', amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
