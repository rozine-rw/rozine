<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Auditor\GetAuditProcedureSources;
use App\Application\Auditor\GetAuditSourceFacts;
use App\Application\Auditor\ReviewAuditPublication;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Business\Contracts\BusinessDirectoryStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Auditor\VerifiedAuditLocation;
use App\Domain\Auditor\Wgs84Distance;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Auditor\EloquentAuditSourceFactsStore;
use App\Models\AuditLocation;
use App\Models\AuditReportPublication;
use App\Models\AuditSourceSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schedule;
use Tests\Support\AuditSealingFixture as SealingFixture;
use Tests\Support\AuditSourceFactsFixture as Fixture;

/*
 * The staging demo's on-site capture: on UAT with the money simulation on, each accepted audit of a
 * simulated Business gets a synthetic check-in at its premises and the required photos, so the
 * Auditor can go straight to the visit. Nothing of it reaches any other Business or environment.
 */

/** UAT with the simulation on, naming these synthetic test Businesses. */
function asStagingAuditDemo(string ...$businesses): void
{
    // The staging box's own database and mail boundary are not this test database's.
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile,assertSafeConfiguration]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat', 'assertSafeConfiguration' => null]);
    app()->instance(EnvironmentIsolation::class, $isolation);
    config(['app.uat_money_simulation' => true, 'app.uat_money_simulation_businesses' => $businesses, 'isolation.uat_seed_enabled' => true]);
}

/** @return array{0: int, 1: string} */
function recordDemoAuditSources(): array
{
    $code = Artisan::call('staging:record-audit-sources');

    return [$code, Artisan::output()];
}

beforeEach(function (): void {
    $this->freezeSecond();
});

it('records a check-in and both required photos for a simulated Business\'s accepted audit, once', function (): void {
    $fixture = Fixture::accepted();
    asStagingAuditDemo(strtoupper($fixture['audit']['business']));
    // Premises are no longer verified, so the synthetic check-in uses central Kigali.
    $premises = EloquentAuditSourceFactsStore::KIGALI;

    [$code, $output] = recordDemoAuditSources();
    expect($code)->toBe(0)->and($output)->toContain($fixture['assignment']->id.': check-in and photos recorded');

    $at = now('UTC')->subMinutes(5)->format('Y-m-d\TH:i:s\Z');
    $facts = app(GetAuditSourceFacts::class)->handle($fixture['partner']['user']->id, 1, $fixture['assignment']->id);
    $position = [...app(Wgs84Distance::class)->point($premises['latitude'], $premises['longitude']), 'accuracy_m' => 12];
    expect($facts['check_in'] ?? null)->toEqual(['at' => $at, 'position' => $position, 'review_required' => false])
        ->and(array_column($facts['photos']['required'] ?? [], 'id'))->toBe(['storefront', 'inventory'])
        ->and(array_column($facts['photos']['required'] ?? [], 'captured_at'))->toBe([$at, $at])
        ->and($facts['source']['reference'] ?? null)->toBe('synthetic:uat-money-simulation')
        ->and(AuditSourceSnapshot::query()->sole()->party_id)->toBe($fixture['partner']['party']->id);

    [$code, $output] = recordDemoAuditSources();
    expect($code)->toBe(0)->and($output)->toBe('');
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('falls back to a Kigali point where the Business has no verified premises', function (): void {
    $facts = EloquentAuditSourceFactsStore::simulatedFacts(null, '2026-11-17T11:55:00Z');

    expect($facts['check_in'])->toBe(['at' => '2026-11-17T11:55:00Z', 'position' => [...EloquentAuditSourceFactsStore::KIGALI, 'accuracy_m' => 12], 'review_required' => false]);
});

it('records nothing for another Business, where the simulation is off, or where fixtures are not allowed', function (): void {
    $fixture = Fixture::accepted();
    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    recordDemoAuditSources();
    config(['app.uat_money_simulation_businesses' => [$fixture['audit']['business']], 'app.uat_money_simulation' => false]);
    recordDemoAuditSources();
    config(['app.uat_money_simulation' => true, 'isolation.uat_seed_enabled' => false]);
    [$code] = recordDemoAuditSources();

    expect($code)->toBe(0);
    $this->assertDatabaseCount('audit_source_snapshots', 0);
});

it('runs every minute on the scheduler and once from its migration', function (): void {
    $events = collect(Schedule::events())->filter(fn ($event): bool => str_contains((string) $event->command, 'staging:record-audit-sources'));
    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('* * * * *');

    $fixture = Fixture::accepted();
    asStagingAuditDemo($fixture['audit']['business']);
    (require database_path('migrations/2026_11_17_140000_record_uat_demo_audit_sources.php'))->up();

    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('skips an accepted audit whose Auditor has no sign-in account, and returns nothing while the simulation is off', function (): void {
    $fixture = Fixture::accepted();
    asStagingAuditDemo($fixture['audit']['business']);
    User::query()->whereKey($fixture['partner']['user']->id)->update(['party_id' => null]);

    [$code, $output] = recordDemoAuditSources();
    expect($code)->toBe(0)->and($output)->toBe('');
    config(['app.uat_money_simulation' => false]);
    expect(app(AuditSourceFactsStore::class)->recordSimulated())->toBe([]);
    $this->assertDatabaseCount('audit_source_snapshots', 0);
});

it('reads the latest verified month for a simulated Business\'s monthly audit of the month still running', function (): void {
    $fixture = SealingFixture::ready(kind: 'monthly');
    $user = $fixture['user'];
    $assignment = app(WithAcceptedAuditAssignment::class)->handle($user->id, 1, $fixture['assignment']->id, fn (array $accepted): array => $accepted);
    $sources = fn (string $period): array => app(GetAuditProcedureSources::class)->handle($user->id, 1, $assignment, 'monthly', $period);
    $verified = $sources(AuditEngagementState::period($fixture['assignment']->refresh()->state));
    $running = CarbonImmutable::parse($verified['monthly']['period'] ?? '')->addMonth()->format('Y-m');

    expect($verified['monthly'])->not->toBeNull()
        ->and($sources($running))->toMatchArray(['verification' => null, 'monthly' => null]);

    asStagingAuditDemo($fixture['audit']['business']);
    $simulated = $sources($running);
    expect($simulated['verification'])->toBe($verified['verification'])
        ->and($simulated['monthly'])->toBe([...$verified['monthly'] ?? [], 'period' => $running]);

    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    expect($sources($running)['monthly'])->toBeNull();
});

it('reads the latest verified month for a monthly audit staging opened for any Business, on UAT only', function (): void {
    $fixture = SealingFixture::ready(kind: 'monthly');
    $user = $fixture['user'];
    $assignment = app(WithAcceptedAuditAssignment::class)->handle($user->id, 1, $fixture['assignment']->id, fn (array $accepted): array => $accepted);
    $verified = app(GetAuditProcedureSources::class)->handle($user->id, 1, $assignment, 'monthly', $assignment['period'] ?? null);
    // staging:open-monthly-audit opens the month the audit is dispatched in; the scheduler never does.
    $running = CarbonImmutable::parse($assignment['original_dispatch_at'] ?? '')->setTimezone('Africa/Kigali')->format('Y-m');
    $opened = [...$assignment, 'period' => $running];
    $sources = fn (): array => app(GetAuditProcedureSources::class)->handle($user->id, 1, $opened, 'monthly', $running);

    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    expect($sources()['monthly'])->toBe([...$verified['monthly'] ?? [], 'period' => $running])
        ->and(app(GetAuditProcedureSources::class)->handle($user->id, 1, $assignment, 'monthly', $running)['monthly'])->toBeNull();

    config(['app.uat_money_simulation' => false]);
    expect($sources()['monthly'])->toBeNull();
});

it('records the capture for a monthly audit staging opened for a Business the simulation does not name', function (): void {
    $fixture = Fixture::accepted(kind: 'routine');
    $unopened = Fixture::accepted(kind: 'routine');
    $state = $fixture['assignment']->state;
    $running = CarbonImmutable::parse($state['original_dispatch_at'])->setTimezone('Africa/Kigali')->format('Y-m');
    $fixture['assignment']->forceFill(['state' => [...$state, 'period' => $running]])->saveQuietly();
    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');

    [$code, $output] = recordDemoAuditSources();

    expect($code)->toBe(0)->and($output)->toContain($fixture['assignment']->id)->not->toContain($unopened['assignment']->id);
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('treats only a routine audit for its own dispatch month as one staging opened, and nothing it cannot read', function (): void {
    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    $simulation = app(UatMoneySimulation::class);
    $opened = ['kind' => 'routine', 'original_dispatch_at' => '2026-11-17T10:00:00Z', 'period' => '2026-11'];

    expect($simulation->coversAudit('01m7unnamed000000000000000', $opened))->toBeTrue()
        ->and($simulation->coversAudit('01m7unnamed000000000000000', [...$opened, 'period' => '2026-10']))->toBeFalse()
        ->and($simulation->coversAudit('01m7unnamed000000000000000', [...$opened, 'period' => '2026-12']))->toBeFalse()
        ->and($simulation->coversAudit('01m7unnamed000000000000000', [...$opened, 'kind' => 'flash']))->toBeFalse()
        ->and($simulation->coversAudit('01m7unnamed000000000000000', [...$opened, 'original_dispatch_at' => 'not-a-time']))->toBeFalse()
        ->and($simulation->coversAudit('01m7unnamed000000000000000', ['kind' => 'routine', 'original_dispatch_at' => '2026-11-17T10:00:00Z']))->toBeFalse()
        ->and($simulation->coversAudit('01m6dz3t0wemrqac36j094k19b', []))->toBeTrue();

    config(['app.uat_money_simulation' => false]);
    expect($simulation->coversAudit('01m7unnamed000000000000000', $opened))->toBeFalse();
});

it('reports an assignment that cannot take the capture and carries on with the others', function (): void {
    $broken = Fixture::accepted();
    $fixture = Fixture::accepted();
    asStagingAuditDemo($broken['audit']['business'], $fixture['audit']['business']);
    // A legacy premises point retained from before premises verification was removed, now unreadable.
    AuditLocation::factory()->create(['office_party_id' => null, 'business_id' => $broken['audit']['business'],
        'state' => [...app(VerifiedAuditLocation::class)->empty(), 'point' => ['latitude' => 'not-a-latitude', 'longitude' => '30.0619']]]);
    Exceptions::fake();

    [$code, $output] = recordDemoAuditSources();

    expect($code)->toBe(0)->and($output)->toContain($fixture['assignment']->id)->not->toContain($broken['assignment']->id);
    Exceptions::assertReported(CommandRejection::class);
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});

it('ends a simulated Business\'s review window at once so its sealed report publishes', function (): void {
    $fixture = SealingFixture::ready();
    SealingFixture::seal($fixture);
    $publication = AuditReportPublication::query()->where('audit_report_id', $fixture['report']->id)->sole();
    expect($publication->status)->toBe('pending')->and($publication->due_at->isFuture())->toBeTrue();

    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    expect(app(ReviewAuditPublication::class)->advanceDue(100)['published'])->toBe(0)
        ->and($publication->refresh()->status)->toBe('pending');

    asStagingAuditDemo($fixture['audit']['business']);
    expect(app(ReviewAuditPublication::class)->advanceDue(100)['published'])->toBe(1)
        ->and($publication->refresh()->status)->toBe('published')
        ->and($publication->published_reason)->toBe('auto_approved');
});

it('dates a simulated review\'s re-verification when its report was sealed, not at the window end it records (QA #673)', function (): void {
    $fixture = SealingFixture::ready();
    SealingFixture::seal($fixture);
    $sealedAt = now('UTC')->toIso8601String();
    // QA's flash report was sealed at 15:35 and published at 16:20: Activity names the seal, as its label says.
    $this->travel(45)->minutes();
    asStagingAuditDemo($fixture['audit']['business']);
    app(ReviewAuditPublication::class)->advanceDue(100);
    $publication = AuditReportPublication::query()->where('audit_report_id', $fixture['report']->id)->sole();
    expect($publication->published_at->isFuture())->toBeTrue();

    $this->travel(1)->hours();
    $history = app(BusinessDirectoryStore::class)->business($fixture['audit']['business'])['history'] ?? [];
    $entry = array_values(array_filter($history, fn (array $entry): bool => $entry['command'] === 'report.publish'));

    expect(array_column($entry, 'at'))->toBe([$sealedAt]);
});
