<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Auditor\GetAuditSourceFacts;
use App\Application\Environment\EnvironmentIsolation;
use App\Console\Commands\RecordStagingAuditSources;
use App\Domain\Auditor\Wgs84Distance;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditLocation;
use App\Models\AuditSourceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
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

it('records a check-in at the premises and both required photos for a simulated Business\'s accepted audit, once', function (): void {
    $fixture = Fixture::accepted();
    asStagingAuditDemo(strtoupper($fixture['audit']['business']));
    $premises = AuditLocation::query()->where('business_id', $fixture['audit']['business'])->sole()->state['point'];
    expect($premises)->not->toBeNull();

    [$code, $output] = recordDemoAuditSources();
    expect($code)->toBe(0)->and($output)->toContain($fixture['assignment']->id.': check-in and photos recorded');

    $at = now('UTC')->subMinutes(5)->format('Y-m-d\TH:i:s\Z');
    $facts = app(GetAuditSourceFacts::class)->handle($fixture['partner']['user']->id, 1, $fixture['assignment']->id);
    $position = [...app(Wgs84Distance::class)->point($premises['latitude'] ?? '', $premises['longitude'] ?? ''), 'accuracy_m' => 12];
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
    $facts = RecordStagingAuditSources::facts((string) Str::ulid(), '2026-11-17T11:55:00Z');

    expect($facts['check_in'])->toBe(['at' => '2026-11-17T11:55:00Z', 'position' => [...RecordStagingAuditSources::KIGALI, 'accuracy_m' => 12], 'review_required' => false]);
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

it('records only for a current accepted audit of a simulated Business, as its Auditor', function (): void {
    $fixture = Fixture::accepted();
    $facts = RecordStagingAuditSources::facts($fixture['audit']['business'], now('UTC')->subMinutes(5)->format('Y-m-d\TH:i:s\Z'));
    $actor = $fixture['partner']['user']->id;
    asStagingAuditDemo('01m6dz3t0wemrqac36j094k19b');
    $store = app(AuditSourceFactsStore::class);
    expect(fn () => $store->recordSimulated($actor, $fixture['assignment']->id, $facts, 'Demo.'))
        ->toThrow(CommandRejection::class, 'UAT_SIMULATION_BUSINESS_REQUIRED');

    asStagingAuditDemo($fixture['audit']['business']);
    expect($store->recordSimulated($actor, (string) Str::ulid(), $facts, 'Demo.'))->toBeNull()
        ->and($store->recordSimulated($actor, $fixture['assignment']->id, $facts, 'Demo.'))->toBeString()
        ->and($store->recordSimulated($actor, $fixture['assignment']->id, $facts, 'Demo.'))->toBeNull();

    // An Auditor with no sign-in account is skipped rather than recorded under someone else.
    $orphan = Fixture::accepted();
    asStagingAuditDemo($orphan['audit']['business']);
    User::query()->whereKey($orphan['partner']['user']->id)->update(['party_id' => null]);
    [$code, $output] = recordDemoAuditSources();
    expect($code)->toBe(0)->and($output)->toBe('');
    $this->assertDatabaseCount('audit_source_snapshots', 1);
});
