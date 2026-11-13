<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\RequestArrearsVisit;
use App\Application\Auditor\SaveAuditReportStep;
use App\Domain\Auditor\AuditEngagementState;
use App\Models\AuditAssignment;
use App\Models\AuditReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * BRS v3 AUD-R08 and VIS-R02: day 7 of arrears sends the business's CPA on a late visit. It is a
 * Flash visit with its own short procedure: check in, the money available now with its proof, the
 * reason from a fixed list, seal. Nothing is negotiated, and one instalment gets one visit.
 */

it('binds a visit sent at day 7 of arrears as a late visit and seals its steps', function (): void {
    $fixture = Fixture::ready(kind: 'late');
    $report = $fixture['report']->refresh();

    expect(AuditEngagementState::purpose($fixture['assignment']->refresh()->state))->toBe(AuditEngagementState::PURPOSE_ARREARS)
        ->and($fixture['assignment']->state['arrears_instalment'])->toBe('synthetic-instalment-1')
        ->and($report->kind)->toBe('flash')
        ->and($report->binding['late'] ?? null)->toBeTrue()
        ->and($report->binding)->not->toHaveKey('baseline')
        ->and($report->draft['completed_steps'])->toBe(['review', 'check_in', 'available', 'reason'])
        ->and($report->draft['fields']['available'])->toBe(['cash_available' => '450000', 'financial_proofs' => ['bank', 'momo']])
        ->and($report->draft['fields']['reason'])->toBe(['late_reason' => 'customers_owe', 'late_reason_note' => ''])
        ->and(app(GetAuditProcedure::class)->handle($fixture['user']->id, 1, $report->id)['late'])->toBe(['late_reasons' => [
            'sales_fell', 'customers_owe', 'unexpected_cost', 'stock_bought', 'owner_away', 'trading_stopped', 'other']])
        ->and(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
});

it('shows the money available and reason screens, and opens a completed late step again', function (): void {
    $fixture = Fixture::ready(kind: 'late');
    $this->withoutVite()->actingAs($fixture['user']);
    $props = fn (?string $step) => $this->get(route('auditor.reports.show', ['report' => $fixture['report']->id, ...($step === null ? [] : ['step' => $step])]))
        ->assertOk()->viewData('page')['props'];

    $available = $props('available');
    expect($available['stage'])->toMatchArray(['step' => 'available', 'cash_available' => ['amount' => '450000', 'currency' => 'RWF']])
        ->and(array_column($available['stage']['financial_proofs'], 'seen', 'key'))->toBe(['bank' => true, 'momo' => true])
        ->and(array_column($available['steps'], 'key'))->toBe(['review', 'check_in', 'available', 'reason', 'seal'])
        ->and($props('reason')['stage'])->toBe(['step' => 'reason', 'reasons' => ['sales_fell', 'customers_owe', 'unexpected_cost', 'stock_bought', 'owner_away', 'trading_stopped', 'other'],
            'late_reason' => 'customers_owe', 'late_reason_note' => ''])
        ->and($props(null)['stage']['step'])->toBe('seal');
});

it('sends one late visit per overdue instalment and none while an engagement is open', function (): void {
    $fixture = Fixture::ready(kind: 'late');
    $business = $fixture['audit']['business'];
    $count = AuditAssignment::query()->where('business_id', $business)->count();
    $request = app(RequestArrearsVisit::class);

    expect($request->handle($business, 'synthetic-instalment-2'))->toBeNull()
        ->and(AuditAssignment::query()->where('business_id', $business)->count())->toBe($count);

    Fixture::seal($fixture);
    expect($request->handle($business, 'synthetic-instalment-1'))->toBeNull()
        ->and(AuditAssignment::query()->where('business_id', $business)->count())->toBe($count);
});

it('refuses an Other reason without a note and a reason off the list', function (): void {
    $fixture = Fixture::ready(kind: 'late');
    $save = fn (array $fields) => app(SaveAuditReportStep::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
        $fixture['report']->refresh()->revision, 'reason', $fields, (string) Str::uuid());

    expect($save(['late_reason' => 'other', 'late_reason_note' => '  ']))->toMatchArray(['code' => 'AUDIT_STEP_INPUT_INVALID', 'field_errors' => ['late_reason_note' => ['Use plain text of at most 200 characters.']]])
        ->and($save(['late_reason' => 'payment_plan', 'late_reason_note' => '']))->toMatchArray(['code' => 'AUDIT_STEP_INPUT_INVALID', 'field_errors' => ['late_reason' => ['Choose why the repayment is late.']]])
        ->and($save(['late_reason' => 'other', 'late_reason_note' => ' Flooded shop. '])['code'])->toBe('AUDIT_STEP_SAVED')
        ->and($fixture['report']->refresh()->draft['fields']['reason'])->toBe(['late_reason' => 'other', 'late_reason_note' => 'Flooded shop.']);
});

it('allows a Flash report at a late visit step only while the constraint does, and refuses rollback once one stands there', function (string $step): void {
    $fixture = Fixture::ready(kind: 'late');
    $move = fn (string $to) => DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => $to, 'revision' => DB::raw('revision + 1')]);
    $move($step);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_11_12_090000_add_late_visit_steps.php');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Reports at a late visit step require a forward migration');

    $move('seal');
    $migration->down();
    expect(fn () => DB::transaction(fn () => $move($step)))->toThrow(QueryException::class, 'audit_report_step');
    $migration->up();
    $move($step);
    expect(AuditReport::query()->whereKey($fixture['report']->id)->value('step'))->toBe($step);
})->with(['available', 'reason']);
