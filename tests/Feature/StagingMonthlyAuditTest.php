<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Application\Environment\EnvironmentIsolation;
use App\Domain\Auditor\AuditEngagementState;
use App\Models\AuditAssignment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Tests\Support\AuditAssignmentFixture as Fixture;

/*
 * The staging demo's monthly audit: on UAT with the money simulation on, a simulated Business's
 * monthly audit for the current month opens now, so its figures, visit and seal can be shown the same
 * day. Nothing of it reaches any other Business or environment.
 */

/** UAT with the simulation on, naming these synthetic test Businesses. */
function asStagingDemo(string ...$businesses): void
{
    // The staging box's own database and mail boundary are not this test database's.
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile,assertSafeConfiguration]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat', 'assertSafeConfiguration' => null]);
    app()->instance(EnvironmentIsolation::class, $isolation);
    // The fixture's partners accepted synthetic engagement terms, which hold where staging may be seeded.
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => $businesses, 'isolation.uat_seed_enabled' => true]);
}

/**
 * Runs the staging command, returning its exit code and output.
 *
 * @param  array<string, string>  $arguments
 * @return array{0: int, 1: string}
 */
function openDemoAudit(array $arguments = []): array
{
    $code = Artisan::call('staging:open-monthly-audit', $arguments);

    return [$code, Artisan::output()];
}

beforeEach(function (): void {
    Date::setTestNow('2026-11-17T12:00:00Z');
});

it('opens a simulated Business\'s monthly audit for this month now, offered like the scheduler\'s', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo(strtoupper($fixture['business']));

    [$code, $output] = openDemoAudit();
    expect($code)->toBe(0)->and($output)->toContain('monthly audit for 2026-11 opened');

    $assignment = AuditAssignment::query()->where('business_id', $fixture['business'])->sole();
    expect($assignment->status)->toBe('offered')
        ->and($assignment->state)->toMatchArray(['kind' => 'routine', 'purpose' => 'monthly', 'period' => '2026-11'])
        ->and(AuditEngagementState::period($assignment->state))->toBe('2026-11')
        ->and(app(BusinessAuditWindows::class)->upcoming($fixture['business'], now()->toDateTimeImmutable()))
        ->toMatchArray(['period' => '2026-11-01', 'seal_by' => '2026-12-07T21:59:59Z', 'days_left' => 20]);

    // Nothing opens over the engagement that is still open.
    [$code, $output] = openDemoAudit(['business' => $fixture['business']]);
    expect($code)->toBe(0)->and($output)->toContain('still open');
    expect(AuditAssignment::query()->where('business_id', $fixture['business'])->count())->toBe(1);
});

it('opens the demo month beside one the scheduler already dispatched this month', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo($fixture['business']);
    $monthly = AuditAssignment::query()->findOrFail(app(AuditAssignmentStore::class)->requestMonthly($fixture['business']));
    expect(AuditEngagementState::period($monthly->state))->toBe('2026-10');
    $monthly->forceFill(['status' => 'completed', 'completed_at' => now(), 'state' => [...$monthly->state, 'status' => 'completed']])->save();

    expect(openDemoAudit()[0])->toBe(0);

    expect(AuditAssignment::query()->where('business_id', $fixture['business'])->latest('id')->firstOrFail()->state['period'] ?? null)->toBe('2026-11');
});

it('refuses for a Business the simulation does not name, and wherever it is off', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo('01m6dz3t0wemrqac36j094k19b');

    [$code, $output] = openDemoAudit(['business' => $fixture['business']]);
    expect($code)->toBe(1)->and($output)->toContain('UAT_SIMULATION_BUSINESS_REQUIRED');

    config(['app.uat_money_simulation' => false]);
    [$code, $output] = openDemoAudit();
    expect($code)->toBe(1)->and($output)->toContain('UAT_SIMULATION_OFF');
    (require database_path('migrations/2026_11_17_120000_open_uat_demo_monthly_audit.php'))->up();
    $this->assertDatabaseCount('audit_assignments', 0);
});

it('opens the demo month as the deploy migrates on staging', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo($fixture['business']);

    (require database_path('migrations/2026_11_17_120000_open_uat_demo_monthly_audit.php'))->up();

    expect(AuditAssignment::query()->where('business_id', $fixture['business'])->sole()->state['period'] ?? null)->toBe('2026-11');
});
