<?php

declare(strict_types=1);

use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\Expectation;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    $this->staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($this->staff, ['treasury'], 'Synthetic servicing observer.');
});

/** @param list<string> $businesses */
function staffServicingSimulation(array $businesses): void
{
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => $businesses]);
}

it('reports unavailable servicing rather than zero arrears outside simulation', function (): void {
    PrimaryIssuanceFixture::close(now()->toIso8601String());
    Sanctum::actingAs($this->staff, ['staff:payments:read']);
    $this->getJson(route('api.v1.staff.repayments.index'))->assertOk()
        ->assertJsonPath('data.mode', 'unavailable')->assertJsonPath('data.coverage.issued_notes', 1)
        ->assertJsonPath('data.coverage.available_notes', 0)->assertJsonPath('data.par30.state', 'unavailable')
        ->assertJsonPath('data.par30.percent', null)->assertJsonPath('data.par30.principal_outstanding', null)
        ->assertJsonPath('data.notes.0.statement', null)->assertJsonPath('data.allowed_actions', []);
});

it('distinguishes an empty simulated portfolio from a measured zero percent', function (): void {
    staffServicingSimulation([]);
    $this->actingAs($this->staff)->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.mode', 'simulation')->assertJsonPath('data.coverage.issued_notes', 0)
        ->assertJsonPath('data.par30.state', 'no_outstanding_principal')->assertJsonPath('data.par30.percent', null)
        ->assertJsonPath('data.pd.state', 'not_calibrated')->assertJsonPath('data.pd.value', null);
});

it('reads each exact issued note and classifies the day thirty boundary without collecting', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(now()->toIso8601String());
    staffServicingSimulation([$campaign->businessId]);
    $before = [];
    foreach (['business_repayments', 'servicing_payouts', 'ledger_entries', 'command_operations', 'disbursement_dispatches'] as $table) {
        $before[$table] = DB::table($table)->count();
    }
    $due = CarbonImmutable::parse($instruction->dueDates[0])->setTimezone('Africa/Kigali')->startOfDay();
    $this->travelTo($due->addDays(30));
    $this->actingAs($this->staff)->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.notes.0.note_id', $campaign->campaignId)->assertJsonPath('data.notes.0.availability', 'simulation')
        ->assertJsonPath('data.notes.0.statement.servicing.dpd', 30)->assertJsonPath('data.notes.0.policy_stage_due', 'legal_demand')
        ->assertJsonPath('data.par30.percent', '0.000000')->assertJsonPath('data.par30.threshold_exceeded', false);
    $this->travelTo($due->addDays(31));
    $this->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.notes.0.statement.servicing.dpd', 31)->assertJsonPath('data.notes.0.policy_stage_due', 'default')
        ->assertJsonPath('data.par30.percent', '100.000000')->assertJsonPath('data.par30.threshold_exceeded', true)
        ->assertJsonPath('data.unavailable_capabilities', ['late_charges', 'collection_actions', 'automatic_collection', 'supervisor_alert_delivery']);
    foreach ($before as $table => $count) {
        expect(DB::table($table)->count())->toBe($count);
    }
    expect(app(ServicingStatements::class)->forNote('other-business', $campaign->campaignId, now()->toImmutable()))->toBeNull();
});

it('marks partial simulation coverage unavailable for the whole portfolio', function (): void {
    ['campaign' => $first] = PrimaryIssuanceFixture::close(now()->toIso8601String());
    PrimaryIssuanceFixture::close(now()->toIso8601String());
    staffServicingSimulation([$first->businessId]);
    $this->actingAs($this->staff)->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.coverage.issued_notes', 2)->assertJsonPath('data.coverage.available_notes', 1)
        ->assertJsonPath('data.coverage.complete', false)->assertJsonPath('data.par30.percent', null)
        ->assertJsonPath('data.par30.threshold_exceeded', null);
});

it('keeps two supplied issued notes for one business distinct through both staff transports', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(now()->toIso8601String());
    staffServicingSimulation([$campaign->businessId]);
    $statement = app(ServicingStatements::class)->forNote($campaign->businessId, $campaign->campaignId, now()->toImmutable())
        ?? throw new LogicException('Synthetic issued statement was unavailable.');
    $firstId = strtolower($campaign->campaignId);
    $secondId = strtolower((string) Str::ulid());
    $second = $statement;
    $second['note']['id'] = $secondId;
    $second['note']['title'] = 'Synthetic later note';
    $second['servicing']['note_id'] = $secondId;
    $zero = ['currency' => 'RWF', 'amount' => '0'];
    $statement['servicing']['state'] = 'repaid';
    $statement['servicing']['next'] = null;
    $statement['servicing']['due_now'] = null;
    $statement['servicing']['progress'] = [...$statement['servicing']['progress'],
        'repaid' => $statement['servicing']['progress']['total'], 'remaining' => $zero, 'repaid_pct' => '100.0',
        'payments_made' => count($statement['schedule']), 'remaining_instalments' => 0];
    foreach ($statement['schedule'] as &$instalment) {
        $instalment['paid'] = $instalment['scheduled'];
        $instalment['outstanding'] = ['principal' => $zero, 'return' => $zero, 'late_fees' => $zero, 'total' => $zero];
        $instalment['status'] = 'paid';
        $instalment['paid_on'] = now()->toDateString();
    }
    unset($instalment);
    $statement['options'] = [];
    // Substitute only the owned issued-note/servicing boundaries; do not create a second live loan.
    app()->instance(OperationsDashboardStore::class, Mockery::mock(OperationsDashboardStore::class, ['noteStates' => [
        ['campaign_id' => $firstId, 'business_id' => $campaign->businessId, 'state' => 'repaying', 'live_at' => now()->toIso8601String()],
        ['campaign_id' => $secondId, 'business_id' => $campaign->businessId, 'state' => 'repaying', 'live_at' => now()->toIso8601String()],
    ]]));
    $statements = Mockery::mock(ServicingStatements::class);
    $statements->shouldNotReceive('current');
    $statements->shouldReceive('forNote');
    $firstLookup = $statements->mockery_findExpectation('forNote', []);
    if (! $firstLookup instanceof Expectation) {
        throw new LogicException('Synthetic servicing lookup expectations were unavailable.');
    }
    $firstLookup->with($campaign->businessId, $firstId, Mockery::type(DateTimeImmutable::class))->twice()->andReturn($statement);
    $statements->shouldReceive('forNote');
    $secondLookup = $statements->mockery_findExpectation('forNote', []);
    if (! $secondLookup instanceof Expectation) {
        throw new LogicException('The second synthetic servicing lookup expectation was unavailable.');
    }
    $secondLookup->with($campaign->businessId, $secondId, Mockery::type(DateTimeImmutable::class))->twice()->andReturn($second);
    app()->instance(ServicingStatements::class, $statements);
    $before = DB::table('ledger_entries')->count();
    $operations = DB::table('command_operations')->count();
    $this->actingAs($this->staff);
    foreach (['staff.repayments.data', 'api.v1.staff.repayments.index'] as $route) {
        if (str_starts_with($route, 'api.')) {
            Sanctum::actingAs($this->staff, ['staff:payments:read']);
        }
        $this->getJson(route($route))->assertOk()->assertJsonPath('data.contract_version', 'staff-servicing-read-v1')
            ->assertJsonPath('data.mode', 'simulation')->assertJsonPath('data.coverage.issued_notes', 2)
            ->assertJsonPath('data.coverage.available_notes', 2)->assertJsonCount(2, 'data.notes')
            ->assertJsonPath('data.notes.0.business_id', $campaign->businessId)->assertJsonPath('data.notes.1.business_id', $campaign->businessId)
            ->assertJsonPath('data.notes.0.statement.note.id', $firstId)->assertJsonPath('data.notes.1.statement.note.id', $secondId)
            ->assertJsonPath('data.notes.0.statement.servicing.state', 'repaid')->assertJsonPath('data.notes.0.principal_outstanding.amount', '0')
            ->assertJsonPath('data.notes.1.statement.note.title', 'Synthetic later note')
            ->assertJsonPath('data.par30.principal_outstanding.amount', '10800000')->assertJsonPath('data.allowed_actions', []);
    }
    expect(DB::table('ledger_entries')->count())->toBe($before)->and(DB::table('command_operations')->count())->toBe($operations);
});

it('retains both same-business note identities when servicing is unavailable', function (): void {
    $businessId = strtolower((string) Str::ulid());
    $ids = [strtolower((string) Str::ulid()), strtolower((string) Str::ulid())];
    app()->instance(OperationsDashboardStore::class, Mockery::mock(OperationsDashboardStore::class, ['noteStates' => array_map(
        fn (string $id): array => ['campaign_id' => $id, 'business_id' => $businessId, 'state' => 'repaying', 'live_at' => now()->toIso8601String()], $ids),
    ]));
    $this->actingAs($this->staff)->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.mode', 'unavailable')->assertJsonPath('data.coverage.issued_notes', 2)
        ->assertJsonPath('data.coverage.available_notes', 0)->assertJsonPath('data.notes.0.note_id', $ids[0])
        ->assertJsonPath('data.notes.1.note_id', $ids[1])->assertJsonPath('data.notes.0.statement', null)->assertJsonPath('data.notes.1.statement', null)
        ->assertJsonPath('data.notes.0.availability', 'unavailable')->assertJsonPath('data.notes.1.availability', 'unavailable')
        ->assertJsonPath('data.par30.percent', null)->assertJsonPath('data.pd.state', 'not_calibrated')->assertJsonPath('data.allowed_actions', []);
});

it('checks staff authority and token ability without revealing the servicing portfolio', function (): void {
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Synthetic analyst.');
    $this->actingAs($analyst)->getJson(route('staff.repayments.data'))->assertForbidden();
    Sanctum::actingAs($this->staff, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.repayments.index'))->assertForbidden();
});
