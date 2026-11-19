<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Business\Contracts\MonthlySubmissionStore;
use App\Models\AuditReportSeal;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * QA #674, BRS v3 VIS-R01 steps 5, 7 and 9 against VIS-R04: the costs and owner drawings the CPA
 * verifies sit beside what the Business declared for the month, their differences join the cash
 * difference step 9 explains to the franc, and the seal records both as findings.
 */

it('compares verified costs and drawings with the declaration, explains every difference and seals them', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $report = $fixture['report']->refresh();
    $business = (string) $fixture['assignment']->business_id;
    $this->app->instance(MonthlySubmissionStore::class, new class($business) implements MonthlySubmissionStore
    {
        public function __construct(private string $business) {}

        public function find(string $businessId, string $period): ?array
        {
            $costs = ['stock' => '405000', 'staff' => '250000', 'premises' => '80000', 'transport' => '30000', 'taxes' => '20000', 'other' => '0'];

            return $businessId !== $this->business ? null : ['period' => $period, 'forecast_period' => '2099-01', 'submitted_at' => '2026-11-01T00:00:00Z', 'figures' => [
                'money_in' => '1000000', 'costs' => $costs, 'costs_total' => '785000', 'paid_to_owners' => '2000', 'forecast' => '0',
                'forecast_basis' => null, 'order' => null, 'changes' => ['owners' => false, 'premises' => false, 'borrowing' => false, 'customers' => false]]];
        }

        public function forBusiness(string $businessId): array
        {
            return [];
        }

        public function submit(int $userId, int $contextRevision, string $businessId, string $requestId, array $input, Closure $decide): array
        {
            throw new LogicException('Not used.');
        }
    });
    $user = $fixture['user'];

    $page = app(GetAuditProcedure::class)->handle($user->id, 1, $report->id);
    expect($page['monthly']['difference'])->toBe('7000')
        ->and($page['monthly']['parts'])->toBe(['cash' => '0', 'costs' => '5000', 'drawings' => '2000']);

    $this->actingAs($user)->getJson(route('api.v1.auditor.reports.show', ['report' => $report->id, 'step' => 'costs']))->assertOk()
        ->assertJsonPath('data.stage.declared_total.amount', '785000')->assertJsonPath('data.stage.categories.0.declared.amount', '405000')
        ->assertJsonPath('data.stage.categories.0.amount.amount', '400000');

    $save = fn (string $step, array $fields): array => app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, $report->refresh()->revision, $step, $fields, (string) Str::uuid());
    // The declaration arrived after the CPA passed the costs step, so the draft cannot seal until those steps are checked again.
    expect($page['unavailable'])->toBe('AUDIT_PROCEDURE_SOURCE_CHANGED')
        ->and($save('differences', ['explanations' => []])['code'])->toBe('AUDIT_PROCEDURE_SOURCE_CHANGED');
    expect($save('costs', ['costs' => ['stock' => '400000', 'staff' => '250000', 'premises' => '80000', 'transport' => '30000', 'taxes' => '20000', 'other' => '0']])['code'])->toBe('AUDIT_STEP_SAVED')
        ->and($save('drawings', ['owner_drawings' => '0'])['code'])->toBe('AUDIT_STEP_SAVED')
        ->and($save('loans', ['loans' => []])['code'])->toBe('AUDIT_STEP_SAVED');
    $refused = $save('differences', ['explanations' => []]);
    expect($refused['code'])->toBe('AUDIT_DIFFERENCE_UNEXPLAINED')
        ->and($refused['field_errors'])->toBe(['explanations' => ['Explain the whole difference of RWF 7,000, to the franc.']]);
    $explained = [['cause' => 'personal_spending', 'amount' => '2000'], ['cause' => 'entry_error', 'amount' => '5000']];
    expect($save('differences', ['explanations' => $explained])['code'])->toBe('AUDIT_STEP_SAVED');
    foreach (['staff' => ['staff_count' => '6', 'rssb_checked' => true], 'expiries' => ['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => '2027-06-30'],
        // An optional note left empty arrives as null once the request is trimmed (QA #674).
        'loan_use' => ['loan_use' => 'as_planned', 'loan_use_note' => null], 'changes' => ['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false, 'legal_claim' => false]],
        'photos' => ['titles' => ['extra-1' => 'Stock room']], 'seal' => ['note' => 'Costs and drawings differ from the declaration.']] as $step => $fields) {
        expect($save($step, $fields)['code'])->toBe('AUDIT_STEP_SAVED');
    }

    // The seal step lists what will be signed before the note (QA #674), and a done step opens from the bar (QA #685).
    /** @var list<array{key: string, state: string, link: array{url: string}|null}> $bar */
    $bar = $this->actingAs($user)->getJson(route('api.v1.auditor.reports.show', ['report' => $report->id, 'step' => 'seal']))->assertOk()->json('data.steps');
    expect($bar[0]['link']['url'] ?? null)->toBe(route('api.v1.auditor.reports.show', ['report' => $report->id, 'step' => $bar[0]['key']], false))
        ->and(array_column(array_filter($bar, fn (array $step): bool => $step['state'] !== 'done'), 'link'))->each->toBeNull();
    $this->actingAs($user)->getJson(route('api.v1.auditor.reports.show', ['report' => $report->id, 'step' => 'seal']))->assertOk()
        ->assertJsonFragment(['label' => 'Costs', 'value' => 'Declared RWF 785,000, verified RWF 780,000', 'tone' => 'flag'])
        ->assertJsonFragment(['label' => 'Explained in step 9', 'value' => 'Personal spending from the business RWF 2,000 · Entry error RWF 5,000', 'tone' => 'neutral'])
        ->assertJsonFragment(['label' => 'Owner drawings', 'value' => 'Declared RWF 2,000, verified RWF 0', 'tone' => 'flag'])
        ->assertJsonFragment(['label' => 'Cash balance', 'tone' => 'ok']);

    expect(Fixture::seal(Fixture::prepare([...$fixture, 'report' => $report->refresh()]))['code'])->toBe('AUDIT_SEALED');
    /** @var list<array<string, mixed>> $findings */
    $findings = AuditReportSeal::query()->where('audit_report_id', $report->id)->sole()->payload['report']['findings'];
    $by = array_column($findings, null, 'measure');
    expect($by['costs'])->toMatchArray(['code' => 'COSTS_DIFFERENCE', 'reported' => '785000', 'observed' => '780000', 'difference' => '-5000',
        'explained_by' => [['cause' => 'personal_spending', 'amount' => '2000'], ['cause' => 'entry_error', 'amount' => '5000']]])
        ->and($by['owner_drawings'])->toMatchArray(['code' => 'OWNER_DRAWINGS_DIFFERENCE', 'reported' => '2000', 'observed' => '0', 'difference' => '-2000'])
        ->and($by['owner_drawings'])->not->toHaveKey('explained_by')
        ->and($by['cash']['code'])->toBe('CASH_MATCHED');
});

it('leaves the cash difference alone when the business declared nothing for the month', function (): void {
    $fixture = Fixture::ready(kind: 'monthly', findings: true);
    $page = app(GetAuditProcedure::class)->handle($fixture['user']->id, 1, $fixture['report']->id);

    expect($page['monthly']['difference'])->toBe('1')->and($page['monthly']['parts'])->toBe(['cash' => '1'])
        ->and(array_column($page['seal']['findings'], 'measure'))->toBe(['cash', 'stock_units', 'operational_status']);
});
