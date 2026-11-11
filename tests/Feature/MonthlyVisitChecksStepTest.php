<?php

declare(strict_types=1);

use App\Models\AuditReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * BRS v3 VIS-R01 steps 10, 12, 13 and 14: after the differences the monthly visit records the staff
 * count from RSSB, the tax and RSSB expiry dates, the use of the loan money and the four yes/no
 * changes, and all of them are sealed with the rest of the draft.
 */

it('seals the staff count, expiry dates, use of the loan money and changes the visit recorded', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $fields = $fixture['report']->refresh()->draft['fields'];

    expect($fields['staff'])->toBe(['staff_count' => '6', 'rssb_checked' => true])
        ->and($fields['expiries'])->toBe(['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => '2027-06-30'])
        ->and($fields['loan_use'])->toBe(['loan_use' => 'as_planned', 'loan_use_note' => ''])
        ->and($fields['changes'])->toBe(['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false, 'legal_claim' => false]])
        ->and(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
});

it('allows a monthly report at the new steps only while the constraint does, and refuses rollback once one stands there', function (string $step): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $move = fn (string $to) => DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => $to, 'revision' => DB::raw('revision + 1')]);
    $move($step);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_11_11_090000_add_monthly_staff_expiries_loan_use_and_changes_steps.php');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Monthly reports at the staff, expiries, loan use or changes step require a forward migration');

    $move('photos');
    $migration->down();
    expect(fn () => DB::transaction(fn () => $move($step)))->toThrow(QueryException::class, 'audit_report_step');
    $migration->up();
    $move($step);
    expect(AuditReport::query()->whereKey($fixture['report']->id)->value('step'))->toBe($step);
})->with(['staff', 'expiries', 'loan_use', 'changes']);
