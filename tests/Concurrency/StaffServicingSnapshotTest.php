<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\StaffFixture;

it('reads committed exact-note schedules through the root read-only snapshot', function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(now()->toIso8601String());
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => [$campaign->businessId]]);
    $staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($staff, ['treasury'], 'Synthetic servicing snapshot.');
    expect(DB::transactionLevel())->toBe(0);
    $entries = DB::table('ledger_entries')->count();
    $operations = DB::table('command_operations')->count();
    $this->actingAs($staff)->getJson(route('staff.repayments.data'))->assertOk()
        ->assertJsonPath('data.mode', 'simulation')->assertJsonPath('data.coverage.complete', true)
        ->assertJsonPath('data.notes.0.note_id', $campaign->campaignId)->assertJsonPath('data.par30.percent', '0.000000')
        ->assertJsonPath('data.allowed_actions', []);
    expect(DB::transactionLevel())->toBe(0)->and(DB::table('ledger_entries')->count())->toBe($entries)
        ->and(DB::table('command_operations')->count())->toBe($operations);
});
