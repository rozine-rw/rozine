<?php

declare(strict_types=1);

use App\Models\AuditReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * BRS v3 VIS-R01 steps 7 and 8: the monthly visit records owner drawings and other loans after the
 * count, and both are sealed with the rest of the draft.
 */

it('seals the owner drawings and other loans the visit recorded', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $draft = $fixture['report']->refresh()->draft;

    expect($draft['completed_steps'])->toBe(['statements', 'count', 'drawings', 'loans', 'differences', 'photos'])
        ->and($draft['fields']['drawings'])->toBe(['owner_drawings' => '0'])
        ->and($draft['fields']['loans'])->toBe(['loans' => []])
        ->and(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
});

it('allows a monthly report at the drawings and loans steps only while the constraint does, and refuses rollback once one stands there', function (string $step): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $move = fn (string $to) => DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => $to, 'revision' => DB::raw('revision + 1')]);
    $move($step);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_11_10_220000_add_monthly_drawings_and_loans_steps.php');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Monthly reports at the drawings or loans step require a forward migration');

    $move('photos');
    $migration->down();
    expect(fn () => DB::transaction(fn () => $move($step)))->toThrow(QueryException::class, 'audit_report_step');
    $migration->up();
    $move($step);
    expect(AuditReport::query()->whereKey($fixture['report']->id)->value('step'))->toBe($step);
})->with(['drawings', 'loans']);
