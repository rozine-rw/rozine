<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Application\Environment\EnvironmentIsolation;
use App\Domain\Auditor\AuditEngagementState;
use App\Models\AuditAssignment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schedule;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditSealingFixture;

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
 * @param  array<string, string|bool>  $arguments
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

it('reports a simulated Business it cannot open an audit for, without failing the deploy migration', function (): void {
    asStagingDemo('01m6dz3t0wemrqac36j094k19b');
    [$code, $output] = openDemoAudit();
    (require database_path('migrations/2026_11_17_120000_open_uat_demo_monthly_audit.php'))->up();

    expect($code)->toBe(1)->and($output)->toContain('01m6dz3t0wemrqac36j094k19b: the monthly audit could not be opened');
    expect(AuditAssignment::query()->count())->toBe(0);
});

it('opens each month once, so the scheduler can retry it until an open audit finishes', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo($fixture['business']);
    openDemoAudit();
    $this->travel(5)->hours();

    [$code, $output] = openDemoAudit();

    expect($code)->toBe(0)->and($output)->toContain('no monthly audit was opened')
        ->and(AuditAssignment::query()->where('business_id', $fixture['business'])->count())->toBe(1);
    $events = collect(Schedule::events())->filter(fn ($event): bool => str_contains((string) $event->command, 'staging:open-monthly-audit'));
    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('*/5 * * * *')
        ->and($events->first()->filtersPass(app()))->toBeTrue();
    config(['app.uat_money_simulation' => false]);
    expect($events->first()->filtersPass(app()))->toBeFalse();
});

it('opens a named Business the simulation does not name only when asked to, still only on UAT', function (): void {
    $fixture = Fixture::make(1);
    asStagingDemo('01m6dz3t0wemrqac36j094k19b');

    [$code, $output] = openDemoAudit(['business' => $fixture['business'], '--unsimulated' => true]);
    expect($code)->toBe(0)->and($output)->toContain('monthly audit for 2026-11 opened')
        ->and(AuditAssignment::query()->where('business_id', $fixture['business'])->sole()->state['period'] ?? null)->toBe('2026-11');

    config(['app.uat_money_simulation' => false]);
    [$code, $output] = openDemoAudit(['business' => $fixture['business'], '--unsimulated' => true]);
    expect($code)->toBe(1)->and($output)->toContain('UAT_SIMULATION_OFF');
});

it('opens a second demo month for the Business whose flash audit published last, once, on UAT only', function (): void {
    $sealed = AuditSealingFixture::ready();
    AuditSealingFixture::seal($sealed);
    AuditSealingFixture::publish($sealed);
    $business = $sealed['audit']['business'];
    $migration = require database_path('migrations/2026_11_17_190000_open_uat_second_demo_monthly_audit.php');

    $migration->up();
    expect(AuditAssignment::query()->where('business_id', $business)->get()->filter(fn (AuditAssignment $record): bool => ($record->state['period'] ?? null) === '2026-11'))->toHaveCount(0);

    asStagingDemo('01m6dz3t0wemrqac36j094k19b');
    $migration->up();
    $migration->up();
    expect(AuditAssignment::query()->where('business_id', $business)->get()->filter(fn (AuditAssignment $record): bool => ($record->state['period'] ?? null) === '2026-11'))->toHaveCount(1);
});
