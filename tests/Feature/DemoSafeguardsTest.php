<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\ResetDemoFixtures;
use App\Models\PulseSignup;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 14));
    $this->originalConnection = config('database.default');
    $this->fixtureDirectory = sys_get_temp_dir().'/rozine-demo-test-'.Str::uuid();
});

afterEach(function () {
    app()->detectEnvironment(fn (): string => 'testing');
    config(['database.default' => $this->originalConnection]);
    DB::purge('demo_test');
    File::deleteDirectory($this->fixtureDirectory);
});

function prepareDemoFixtureDatabase(string $directory): void
{
    $migrations = database_path('migrations');
    File::ensureDirectoryExists($directory.'/database/isolated');
    File::ensureDirectoryExists($directory.'/public');
    File::ensureDirectoryExists($directory.'/storage');
    app()->useDatabasePath($directory.'/database');
    app()->useStoragePath($directory.'/storage');
    app()->usePublicPath($directory.'/public');
    $path = database_path('isolated/rozine_demo.sqlite');
    File::put($path, '');
    config(['database.connections.demo_test' => [
        'driver' => 'sqlite', 'database' => $path, 'foreign_key_constraints' => true,
    ]]);
    Artisan::call('migrate', ['--database' => 'demo_test', '--path' => $migrations, '--realpath' => true, '--force' => true]);
    config([
        'database.default' => 'demo_test', 'app.debug' => false,
        'app.url' => 'https://demo.example.test',
        'isolation.demo_enabled' => true, 'isolation.demo_reset_enabled' => true,
        'services' => [], 'mail.mailers' => ['array' => ['transport' => 'array']],
        'filesystems.disks' => [],
    ]);
    app()->detectEnvironment(fn (): string => 'demo');
}

test('non-live labels are server-owned and included even in partial responses', function (string $environment, ?string $label) {
    app()->detectEnvironment(fn (): string => $environment);
    $this->get('/?nonLiveEnvironment=production&demo=false')
        ->assertOk();
    $this->get('/?nonLiveEnvironment=production&demo=false', [
        'X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'home',
        'X-Inertia-Partial-Data' => 'locale',
        'X-Inertia-Version' => Inertia::getVersion(),
    ])->assertOk()->assertJsonPath('props.nonLiveEnvironment', $label);
})->with([
    ['demo', 'demo'], ['uat', 'uat'], ['staging', 'uat'],
    ['production', null], ['local', null], ['testing', null],
]);

test('demo reset requires explicit fixture-version confirmation before touching data', function (?string $confirmation) {
    $sentinel = User::factory()->create();
    expect(Artisan::call('demo:reset', $confirmation === null ? [] : ['--confirm' => $confirmation]))->toBe(1)
        ->and(Artisan::output())->toContain('DEMO_FIXTURE_CONFIRMATION_REQUIRED');
    $this->assertModelExists($sentinel);
})->with([null, 'yes', 'production']);

test('demo reset refuses every other environment even when called directly', function (string $environment) {
    $sentinel = User::factory()->create();
    app()->detectEnvironment(fn (): string => $environment);
    expect(fn () => app(ResetDemoFixtures::class)->handle())->toThrow(LogicException::class);
    expect(Artisan::call('demo:reset', ['--confirm' => ResetDemoFixtures::VERSION]))->toBe(1);
    $this->assertModelExists($sentinel);
})->with(['production', 'uat', 'staging', 'local', 'testing', 'prod']);

test('enabled temporary demo switches expire without breaking disabled production', function () {
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    expect(config('isolation.demo_flags.owner'))->not->toBeEmpty()
        ->and(config('isolation.demo_flags.reason'))->not->toBeEmpty();
    $this->travelTo(now()->setDate(2026, 10, 14));
    expect(fn () => app(EnvironmentIsolation::class)->canReset())
        ->toThrow(LogicException::class, 'ISOLATION_DEMO_FLAGS_EXPIRED');
    config(['isolation.demo_enabled' => false, 'isolation.demo_reset_enabled' => false]);
    expect(app(EnvironmentIsolation::class)->canReset())->toBeFalse();
    app()->detectEnvironment(fn (): string => 'production');
    config(['database.default' => $this->originalConnection]);
    expect(app(EnvironmentIsolation::class)->canReset())->toBeFalse();
});

test('dedicated demo reset still requires both switches', function (bool $enabled, bool $reset) {
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    config(['isolation.demo_enabled' => $enabled, 'isolation.demo_reset_enabled' => $reset]);
    expect(fn () => app(ResetDemoFixtures::class)->handle())->toThrow(LogicException::class);
    expect(DB::connection('demo_test')->table('pulse_signups')->count())->toBe(0);
})->with([[false, false], [true, false], [false, true]]);

test('fixture reset is deterministic and preserves unrelated records and environment sentinels', function () {
    $sentinel = User::factory()->create();
    $sourceSignup = PulseSignup::factory()->create();
    $originalDatabase = DB::connection($this->originalConnection);
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    $unrelated = PulseSignup::factory()->create();
    $unrelatedBefore = $unrelated->fresh()->getRawOriginal();
    $reset = app(ResetDemoFixtures::class);
    expect($reset->handle())->toBe(2);
    $before = DB::table('pulse_signups')->orderBy('id')->get()->toJson();
    $this->travel(7)->days();
    expect(Artisan::call('demo:reset', ['--confirm' => ResetDemoFixtures::VERSION]))->toBe(0)
        ->and(Artisan::output())->toContain('Restored 2 synthetic fixtures');
    expect(DB::table('pulse_signups')->orderBy('id')->get()->toJson())->toBe($before);
    DB::table('pulse_signups')->where('contact', 'investor@rozine-demo.invalid')->update(['name' => 'Changed', 'listed' => true]);
    expect($reset->handle())->toBe(2)
        ->and(DB::table('pulse_signups')->orderBy('id')->get()->toJson())->toBe($before)
        ->and($unrelated->fresh()->getRawOriginal())->toBe($unrelatedBefore)
        ->and($originalDatabase->table('users')->where('id', $sentinel->id)->exists())->toBeTrue()
        ->and($originalDatabase->table('pulse_signups')->where('id', $sourceSignup->id)->exists())->toBeTrue();
    expect(DB::table('pulse_signups')->where('user_agent', ResetDemoFixtures::PROVENANCE)->whereNotNull('loan_number')->count())->toBe(0)
        ->and(DB::table('users')->count())->toBe(0);
});

test('unowned contact collision aborts without overwriting any record', function () {
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    $collision = PulseSignup::factory()->create(['contact' => 'investor@rozine-demo.invalid']);
    $before = $collision->fresh()->getRawOriginal();
    expect(Artisan::call('demo:reset', ['--confirm' => ResetDemoFixtures::VERSION]))->toBe(1)
        ->and(Artisan::output())->toContain('DEMO_FIXTURE_PROVENANCE_COLLISION');
    expect($collision->fresh()->getRawOriginal())->toBe($before)
        ->and(PulseSignup::count())->toBe(1);
});

test('database rejection rolls back the complete fixture batch without leaking SQL or contacts', function () {
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    $collision = PulseSignup::factory()->business()->create(['queue_number' => 'DEMO-BIZ-0001']);
    $before = $collision->fresh()->getRawOriginal();
    DB::statement("CREATE TRIGGER reject_demo_business BEFORE INSERT ON pulse_signups WHEN NEW.contact = 'business@rozine-demo.invalid' BEGIN SELECT RAISE(ABORT, 'synthetic-private-error'); END");
    expect(Artisan::call('demo:reset', ['--confirm' => ResetDemoFixtures::VERSION]))->toBe(1)
        ->and(Artisan::output())->toContain('DEMO_FIXTURE_DATABASE_REJECTED')
        ->not->toContain($collision->contact, 'insert into', 'synthetic-private-error');
    expect($collision->fresh()->getRawOriginal())->toBe($before)
        ->and(PulseSignup::count())->toBe(1);
});

test('a queue collision rolls back earlier fixtures and preserves the conflicting record', function () {
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    $collision = PulseSignup::factory()->business()->create(['queue_number' => 'DEMO-BIZ-0001']);
    $before = $collision->fresh()->getRawOriginal();
    expect(Artisan::call('demo:reset', ['--confirm' => ResetDemoFixtures::VERSION]))->toBe(1)
        ->and(PulseSignup::count())->toBe(1)
        ->and($collision->fresh()->getRawOriginal())->toBe($before);
});

test('demo reset rejects a misdirected database before resolving its connection', function () {
    $sentinel = User::factory()->create();
    prepareDemoFixtureDatabase($this->fixtureDirectory);
    config(['database.default' => $this->originalConnection]);
    expect(fn () => app(ResetDemoFixtures::class)->handle())
        ->toThrow(LogicException::class, 'ISOLATION_DEDICATED_DATABASE_REQUIRED');
    $this->assertModelExists($sentinel);
});
