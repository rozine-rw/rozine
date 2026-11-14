<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use Carbon\CarbonImmutable;

/**
 * The Business's visit-ready checklist (BRS v3 VIS-R03, AUD-R04): from D−14 before an instalment
 * falls due, the Business sees exactly what the CPA will ask for on the monthly visit. The list is
 * generated from the monthly guided-visit steps themselves, so the checklist and the CPA's steps
 * always name the same documents: every monthly step is mapped here, a step that needs nothing
 * from the Business maps to no item, and the order follows the visit.
 */
final class VisitReadyChecklist
{
    /** The checklist opens this many calendar days before the instalment falls due (D−14). */
    public const int OPENS_DAYS_BEFORE = 14;

    /** The monthly figures are submitted and the CPA's visit window opens at D−10 (VIS-R04, AUD-R04). */
    public const int VISIT_FROM_DAYS_BEFORE = 10;

    /** The CPA seals by D−3 (AUD-R04). */
    public const int VISIT_BY_DAYS_BEFORE = 3;

    /** What the Business gets ready for each monthly step, in the visit's order. */
    public const array ITEMS = [
        'statements' => ['bank_statements'],
        'count' => ['cash_and_stock'],
        'cash_sales' => ['sales_records'],
        'costs' => ['cost_receipts'],
        'drawings' => ['owner_payments'],
        'loans' => ['loan_papers'],
        'differences' => [],
        'staff' => ['rssb_total'],
        'expiries' => ['tax_clearance', 'rssb_clearance'],
        'loan_use' => ['loan_use_proof'],
        'changes' => ['change_papers'],
        'photos' => ['premises_access'],
        'seal' => [],
    ];

    public function __construct(private AuditProcedure $procedure = new AuditProcedure) {}

    /**
     * The checklist for the instalment due on `$dueOn`, or null before D−14 or after the due date.
     * Dates are Kigali calendar dates (`Y-m-d`). Items in `$held` are documents the CPA already
     * holds, and are not asked for again.
     *
     * @param  list<string>  $held
     * @return array{due_on: string, opens_on: string, visit_from: string, visit_by: string, items: list<array{key: string, step: string}>}|null
     */
    public function forInstalment(string $dueOn, string $today, array $held = []): ?array
    {
        $due = CarbonImmutable::parse($dueOn, 'Africa/Kigali')->startOfDay();
        $now = CarbonImmutable::parse($today, 'Africa/Kigali')->startOfDay();
        $opens = $due->subDays(self::OPENS_DAYS_BEFORE);
        if ($now < $opens || $now > $due) {
            return null;
        }

        return ['due_on' => $dueOn, 'opens_on' => $opens->toDateString(),
            'visit_from' => $due->subDays(self::VISIT_FROM_DAYS_BEFORE)->toDateString(),
            'visit_by' => self::visitBy($dueOn), 'items' => $this->items($held)];
    }

    /**
     * The day the CPA must seal the visit for the instalment due on `$dueOn` (D−3, AUD-R04).
     */
    public static function visitBy(string $dueOn): string
    {
        return CarbonImmutable::parse($dueOn, 'Africa/Kigali')->subDays(self::VISIT_BY_DAYS_BEFORE)->toDateString();
    }

    /**
     * Every item, in the order of the monthly steps it serves, less the documents in `$held`.
     *
     * @param  list<string>  $held
     * @return list<array{key: string, step: string}>
     */
    public function items(array $held = []): array
    {
        $items = [];
        foreach ($this->procedure->steps('monthly') as $step) {
            foreach (self::ITEMS[$step] as $key) {
                if (! in_array($key, $held, true)) {
                    $items[] = ['key' => $key, 'step' => $step];
                }
            }
        }

        return $items;
    }
}
