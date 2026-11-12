<?php

declare(strict_types=1);

use App\Application\Auditor\SaveAuditReportStep;
use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * BRS v3 VIS-R01 step 9: the monthly visit explains every difference with a cause code until the
 * explained amounts equal the total difference, to the franc, and the seal carries them.
 */

it('seals the cause codes on the cash finding and refuses an explanation a franc short', function (): void {
    $fixture = Fixture::ready(kind: 'monthly', findings: true);
    $report = $fixture['report']->refresh();
    $draft = $report->draft;
    expect($draft['fields']['differences'])->toBe(['explanations' => [['cause' => 'entry_error', 'amount' => '1']]]);

    $refused = app(SaveAuditReportStep::class)->handle($fixture['user']->id, 1, $report->id, $report->revision, 'differences',
        ['explanations' => []], (string) Str::uuid());
    expect($refused['code'])->toBe('AUDIT_DIFFERENCE_UNEXPLAINED')
        ->and($refused['field_errors'])->toBe(['explanations' => ['Explain the whole difference of RWF 1, to the franc.']])
        ->and($report->refresh()->step)->toBe('seal');

    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $payload = AuditReportSeal::query()->where('audit_report_id', $report->id)->sole()->payload;
    /** @var list<array<string, mixed>> $findings */
    $findings = $payload['report']['findings'];
    $finding = array_find($findings, fn (array $finding): bool => $finding['measure'] === 'cash');
    expect($finding['explained_by'] ?? null)->toBe([['cause' => 'entry_error', 'amount' => '1']]);
});

it('keeps a monthly report at the difference step only while the step constraint allows it, and refuses rollback once one stands there', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => 'differences', 'revision' => DB::raw('revision + 1')]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_11_10_210000_add_monthly_difference_step.php');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Monthly reports at the differences step require a forward migration');

    DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => 'photos', 'revision' => DB::raw('revision + 1')]);
    $migration->down();
    expect(fn () => DB::transaction(fn () => DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => 'differences', 'revision' => DB::raw('revision + 1')])))
        ->toThrow(QueryException::class, 'audit_report_step');
    $migration->up();
    DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => 'differences', 'revision' => DB::raw('revision + 1')]);
    expect(AuditReport::query()->whereKey($fixture['report']->id)->value('step'))->toBe('differences');
});
