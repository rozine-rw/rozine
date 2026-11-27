<?php

declare(strict_types=1);

use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Environment\EnvironmentIsolation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
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

it('checks staff authority and token ability without revealing the servicing portfolio', function (): void {
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Synthetic analyst.');
    $this->actingAs($analyst)->getJson(route('staff.repayments.data'))->assertForbidden();
    Sanctum::actingAs($this->staff, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.repayments.index'))->assertForbidden();
});
