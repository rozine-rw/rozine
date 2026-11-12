<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditProcedure;
use App\Domain\Auditor\VisitReadyChecklist;

/*
 * BRS v3 VIS-R03: the visit-ready checklist is generated from the monthly guided-visit steps, so
 * the checklist and the CPA's steps always list the same documents, and it opens at D−14.
 */

it('maps every monthly visit step, and nothing else, in the visit order', function (): void {
    expect(array_keys(VisitReadyChecklist::ITEMS))->toBe((new AuditProcedure)->steps('monthly'));

    $items = (new VisitReadyChecklist)->items();
    expect(array_column($items, 'key'))->toBe(['bank_statements', 'cash_and_stock', 'sales_records', 'cost_receipts', 'owner_payments', 'loan_papers',
        'rssb_total', 'tax_clearance', 'rssb_clearance', 'loan_use_proof', 'change_papers', 'premises_access'])
        ->and(array_column($items, 'step'))->toBe(['statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'staff', 'expiries', 'expiries', 'loan_use', 'changes', 'photos'])
        ->and(array_unique(array_column($items, 'key')))->toHaveCount(count($items));
});

it('opens at D−14 and closes after the due date, with the D−10 to D−3 visit window', function (string $today, bool $open): void {
    $checklist = (new VisitReadyChecklist)->forInstalment('2026-12-18', $today);

    expect($checklist !== null)->toBe($open);
    if ($open) {
        expect($checklist)->toMatchArray(['due_on' => '2026-12-18', 'opens_on' => '2026-12-04', 'visit_from' => '2026-12-08', 'visit_by' => '2026-12-15'])
            ->and($checklist['items'])->toBe((new VisitReadyChecklist)->items());
    }
})->with([
    'D−15' => ['2026-12-03', false],
    'D−14' => ['2026-12-04', true],
    'D−1' => ['2026-12-17', true],
    'due day' => ['2026-12-18', true],
    'day after' => ['2026-12-19', false],
]);

it('leaves out the documents the CPA already holds, and names the day the visit is sealed by', function (): void {
    $checklist = new VisitReadyChecklist;
    $ready = $checklist->forInstalment('2026-12-15', '2026-12-01', ['tax_clearance', 'rssb_clearance']);

    expect(array_column($ready['items'] ?? [], 'key'))->not->toContain('tax_clearance')->not->toContain('rssb_clearance')
        ->and($ready['items'] ?? [])->toHaveCount(count($checklist->items()) - 2)
        ->and(VisitReadyChecklist::visitBy('2026-12-15'))->toBe('2026-12-12');
});
