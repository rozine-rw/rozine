<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Models\AuditReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * BRS v3 AUD-R03 and VIS-R02: a business's first Flash visit, before any report of it is sealed, is
 * its baseline. It adds the verified history, premises and assets, directors and owners,
 * registration and tax, and the trading start, all sealed with the rest of the draft. A monthly
 * visit is never a baseline, and an amendment keeps its parent's procedure.
 */

it('binds the first Flash visit as the baseline and seals the steps it adds', function (): void {
    $fixture = Fixture::ready();
    $report = $fixture['report']->refresh();

    expect($report->binding['baseline'] ?? null)->toBeTrue()
        ->and($report->draft['completed_steps'])->toBe(['review', 'check_in', 'history', 'premises', 'people', 'registration', 'trading', 'photos', 'ledger'])
        ->and($report->draft['fields']['premises'])->toBe(['premises_tenure' => 'rented', 'assets' => [['item' => 'Delivery motorbike', 'value' => '1800000']]])
        ->and($report->draft['fields']['trading'])->toBe(['trading_since' => '2021-06'])
        ->and(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
});

it('never binds a monthly visit as a baseline', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');

    expect($fixture['report']->refresh()->binding)->not->toHaveKey('baseline')
        ->and(Fixture::baseline($fixture['report']))->toBe([]);
});

it('keeps the baseline steps on an amendment of a baseline report', function (): void {
    $fixture = Fixture::ready();
    Fixture::sealLegacy($fixture);
    $receipt = app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->refresh()->revision, (string) Str::uuid());
    $child = AuditReport::query()->whereKey($receipt['data']['audit_id'])->firstOrFail();

    expect($child->binding['baseline'] ?? null)->toBeTrue()->and(array_keys(Fixture::baseline($child)))->toBe(['history', 'premises', 'people', 'registration', 'trading']);
});

it('allows a Flash report at a baseline step only while the constraint does, and refuses rollback once one stands there', function (string $step): void {
    $fixture = Fixture::ready();
    $move = fn (string $to) => DB::table('audit_reports')->where('id', $fixture['report']->id)->update(['step' => $to, 'revision' => DB::raw('revision + 1')]);
    $move($step);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_11_12_080000_add_baseline_visit_steps.php');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Reports at a baseline visit step require a forward migration');

    $move('photos');
    $migration->down();
    expect(fn () => DB::transaction(fn () => $move($step)))->toThrow(QueryException::class, 'audit_report_step');
    $migration->up();
    $move($step);
    expect(AuditReport::query()->whereKey($fixture['report']->id)->value('step'))->toBe($step);
})->with(['history', 'premises', 'people', 'registration', 'trading']);
