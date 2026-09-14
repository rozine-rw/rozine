<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Providers\EnvironmentSafetyServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

function isolatedConfiguration(string $environment = 'demo'): EnvironmentIsolation
{
    app()->detectEnvironment(fn (): string => $environment);
    $profile = $environment === 'demo' ? 'demo' : 'uat';

    config([
        'app.debug' => false,
        'app.url' => 'https://'.$profile.'.example.test',
        'isolation.demo_enabled' => false,
        'isolation.demo_reset_enabled' => false,
        'isolation.live_money_enabled' => false,
        'database.default' => 'pgsql',
        'database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'database' => 'rozine_'.$profile,
            'username' => 'rozine_'.$profile,
            'password' => 'isolated-test-only',
            'search_path' => 'public',
        ],
        'services' => [],
        'filesystems.disks' => [],
        'mail.mailers' => ['array' => ['transport' => 'array']],
    ]);

    return app(EnvironmentIsolation::class);
}

beforeEach(function () {
    $this->isolationDirectory = sys_get_temp_dir().'/rozine-isolation-'.Str::uuid();
    $this->originalConnection = config('database.default');
    File::ensureDirectoryExists($this->isolationDirectory.'/storage');
    File::ensureDirectoryExists($this->isolationDirectory.'/public');
    File::ensureDirectoryExists($this->isolationDirectory.'/database');
    app()->useStoragePath($this->isolationDirectory.'/storage');
    app()->usePublicPath($this->isolationDirectory.'/public');
    app()->useDatabasePath($this->isolationDirectory.'/database');
});

afterEach(function () {
    app()->detectEnvironment(fn (): string => 'testing');
    config(['database.default' => $this->originalConnection]);
    SeedCommand::prohibit(false);
    DB::prohibitDestructiveCommands(false);
    File::deleteDirectory($this->isolationDirectory);
});

test('local and testing keep their existing drivers and permit development fixtures', function (string $environment) {
    app()->detectEnvironment(fn (): string => $environment);
    $before = config()->all();
    $isolation = app(EnvironmentIsolation::class);
    $isolation->configure();

    expect(config()->all())->toBe($before)
        ->and($isolation->profile())->toBe($environment)
        ->and($isolation->canSeed())->toBeTrue()
        ->and($isolation->canReset())->toBeTrue();
})->with(['local', 'testing']);

test('unknown environments fail closed', function () {
    app()->detectEnvironment(fn (): string => 'prod');

    expect(fn () => app(EnvironmentIsolation::class)->configure())
        ->toThrow(LogicException::class, 'ISOLATION_UNKNOWN_ENVIRONMENT');
});

test('demo and uat get a non-live resource allowlist with no remote or fallback driver', function (string $environment, string $profile) {
    $isolation = isolatedConfiguration($environment);
    $isolation->configure();
    $root = storage_path('isolated/'.$profile);

    expect($isolation->profile())->toBe($profile)
        ->and(array_keys(config('database.connections')))->toBe(['pgsql'])
        ->and(config('database.redis'))->toBe([])
        ->and(config('cache.default'))->toBe('file')
        ->and(array_keys(config('cache.stores')))->toBe(['file'])
        ->and(config('cache.stores.file.path'))->toBe($root.'/cache')
        ->and(config('session.files'))->toBe($root.'/sessions')
        ->and(config('session.cookie'))->toBe('rozine_'.$profile.'-session')
        ->and(config('session.domain'))->toBeNull()
        ->and(config('session.encrypt'))->toBeTrue()
        ->and(config('session.secure'))->toBeTrue()
        ->and(config('view.compiled'))->toBe($root.'/views')
        ->and(array_keys(config('queue.connections')))->toBe(['database', 'sync'])
        ->and(config('queue.connections.database.connection'))->toBe('pgsql')
        ->and(config('queue.connections.database.queue'))->toBe('rozine_'.$profile)
        ->and(config('queue.batching.database'))->toBe('pgsql')
        ->and(config('queue.failed.database'))->toBe('pgsql')
        ->and(array_keys(config('filesystems.disks')))->toBe(['local', 'public'])
        ->and(config('filesystems.disks.local.root'))->toBe($root.'/private')
        ->and(config('filesystems.disks.public.root'))->toBe($root.'/public')
        ->and(config('filesystems.links'))->toBe([public_path('storage') => $root.'/public'])
        ->and(config('mail.mailers'))->toBe(['array' => ['transport' => 'array']])
        ->and(config('mail.default'))->toBe('array')
        ->and(config('services'))->toBe([])
        ->and(config('logging.channels.isolated.path'))->toBe($root.'/logs/laravel.log')
        ->and(config('app.maintenance'))->toBe(['driver' => 'file']);

    $before = config()->all();
    $isolation->configure();
    expect(config()->all())->toBe($before);
})->with([
    ['demo', 'demo'],
    ['staging', 'uat'],
    ['uat', 'uat'],
]);

test('production settings are preserved and seeding and resetting are denied', function () {
    app()->detectEnvironment(fn (): string => 'production');
    $before = config()->all();
    $isolation = app(EnvironmentIsolation::class);
    $isolation->configure();

    expect(config()->all())->toBe($before)
        ->and($isolation->canSeed())->toBeFalse()
        ->and($isolation->canReset())->toBeFalse();
});

test('unsafe flag combinations cannot activate a demo or money path', function (string $environment, string $key, mixed $value, string $error) {
    $isolation = isolatedConfiguration($environment);
    config([$key => $value]);

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, $error);
})->with([
    ['demo', 'isolation.demo_enabled', 'true', 'ISOLATION_FLAGS_MUST_BE_BOOLEAN'],
    ['demo', 'isolation.demo_reset_enabled', 'false', 'ISOLATION_FLAGS_MUST_BE_BOOLEAN'],
    ['demo', 'isolation.demo_reset_enabled', true, 'ISOLATION_DEMO_FLAGS_DENIED'],
    ['uat', 'isolation.demo_enabled', true, 'ISOLATION_DEMO_FLAGS_DENIED'],
    ['production', 'isolation.demo_reset_enabled', true, 'ISOLATION_DEMO_FLAGS_DENIED'],
    ['demo', 'isolation.live_money_enabled', true, 'ISOLATION_LIVE_MONEY_NOT_ACTIVATED'],
]);

test('demo reset needs both operator switches', function (bool $enabled, bool $reset, bool $canSeed, bool $canReset) {
    $isolation = isolatedConfiguration();
    config(['isolation.demo_enabled' => $enabled, 'isolation.demo_reset_enabled' => $reset]);

    expect($isolation->canSeed())->toBe($canSeed)
        ->and($isolation->canReset())->toBe($canReset);
})->with([
    [false, false, false, false],
    [true, false, true, false],
    [true, true, true, true],
]);

test('a non-live database cannot use a production name user url or read write override', function (string $key, mixed $value, string $error) {
    $isolation = isolatedConfiguration('uat');
    config(['database.connections.pgsql.'.$key => $value]);

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, $error);
})->with([
    ['database', 'rozine', 'ISOLATION_DEDICATED_DATABASE_REQUIRED'],
    ['username', 'postgres', 'ISOLATION_DEDICATED_DATABASE_REQUIRED'],
    ['driver', 'mysql', 'ISOLATION_DEDICATED_DATABASE_REQUIRED'],
    ['search_path', 'live', 'ISOLATION_DEDICATED_DATABASE_REQUIRED'],
    ['url', 'pgsql://live:do-not-print@example.test/rozine', 'ISOLATION_DATABASE_OVERRIDE_DENIED'],
    ['read', ['database' => 'live'], 'ISOLATION_DATABASE_OVERRIDE_DENIED'],
    ['write', ['database' => 'live'], 'ISOLATION_DATABASE_OVERRIDE_DENIED'],
]);

test('only the dedicated nonsymlinked demo sqlite file is accepted and never for uat', function () {
    $isolation = isolatedConfiguration();
    config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
        'driver' => 'sqlite', 'database' => database_path('isolated/rozine_demo.sqlite'),
    ]]);
    $isolation->assertSafeConfiguration();

    config(['database.connections.sqlite.database' => ':memory:']);
    expect(fn () => $isolation->assertSafeConfiguration())->toThrow(LogicException::class, 'ISOLATION_DEDICATED_DATABASE_REQUIRED');

    config(['database.connections.sqlite.database' => database_path('isolated/rozine_demo.sqlite')]);
    app()->detectEnvironment(fn (): string => 'uat');
    expect(fn () => $isolation->assertSafeConfiguration())->toThrow(LogicException::class, 'ISOLATION_DEDICATED_DATABASE_REQUIRED');
});

test('sqlite symlinks cannot alias another environment', function (bool $linkDirectory) {
    $isolation = isolatedConfiguration();
    File::put(database_path('sentinel.sqlite'), 'must remain untouched');

    if ($linkDirectory) {
        symlink(database_path(), database_path('isolated'));
    } else {
        File::ensureDirectoryExists(database_path('isolated'));
        symlink(database_path('sentinel.sqlite'), database_path('isolated/rozine_demo.sqlite'));
    }

    config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
        'driver' => 'sqlite', 'database' => database_path('isolated/rozine_demo.sqlite'),
    ]]);

    expect(fn () => $isolation->assertSafeConfiguration())->toThrow(LogicException::class, 'ISOLATION_DEDICATED_DATABASE_REQUIRED');
    expect(File::get(database_path('sentinel.sqlite')))->toBe('must remain untouched');
})->with([true, false]);

test('production rejects non-live database identities including encoded urls', function (string $key, mixed $value) {
    app()->detectEnvironment(fn (): string => 'production');
    config(['database.connections.'.config('database.default').'.'.$key => $value]);

    expect(fn () => app(EnvironmentIsolation::class)->assertSafeConfiguration())
        ->toThrow(LogicException::class, 'ISOLATION_PRODUCTION_USES_NON_LIVE_DATABASE');
})->with([
    ['database', 'rozine_uat'],
    ['database', '/private/database/rozine_demo.sqlite'],
    ['username', 'rozine_demo'],
    ['url', 'pgsql://user:secret@example.test/%72ozine_uat'],
    ['url', 'pgsql://rozine_demo:secret@example.test/rozine'],
]);

test('provider secrets are rejected without appearing in the exception', function (string $key) {
    $isolation = isolatedConfiguration();
    config([$key => 'sentinel-do-not-expose']);

    try {
        $isolation->configure();
        $this->fail('A provider credential was accepted.');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toBe('ISOLATION_PROVIDER_CREDENTIALS_DENIED')
            ->not->toContain('sentinel-do-not-expose');
    }
})->with([
    'services.postmark.key', 'services.resend.key', 'services.ses.secret',
    'services.slack.notifications.bot_user_oauth_token', 'mail.mailers.smtp.password',
    'mail.mailers.smtp.url', 'filesystems.disks.s3.bucket', 'filesystems.disks.s3.endpoint',
    'queue.connections.sqs.key', 'queue.connections.sqs.secret', 'logging.channels.slack.url',
    'database.redis.default.password', 'database.redis.default.url',
]);

test('debug mode and live or invalid application urls are refused', function (string $key, mixed $value, string $error) {
    $isolation = isolatedConfiguration('uat');
    config([$key => $value]);

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, $error);
})->with([
    ['app.debug', true, 'ISOLATION_DEBUG_MUST_BE_DISABLED'],
    ['app.url', 'https://rozine.rw', 'ISOLATION_NON_LIVE_URL_REQUIRED'],
    ['app.url', 'https://www.rozine.rw', 'ISOLATION_NON_LIVE_URL_REQUIRED'],
    ['app.url', 'not-a-url', 'ISOLATION_NON_LIVE_URL_REQUIRED'],
    ['app.url', 'ftp://uat.example.test', 'ISOLATION_NON_LIVE_URL_REQUIRED'],
    ['app.url', 'http://uat.example.test', 'ISOLATION_NON_LIVE_URL_REQUIRED'],
]);

test('storage cannot be redirected through a symlink', function (string $path) {
    $isolation = isolatedConfiguration();
    File::ensureDirectoryExists(dirname(storage_path($path)));
    symlink(public_path(), storage_path($path));

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, 'ISOLATION_STORAGE_SYMLINK_DENIED');
})->with(['isolated', 'isolated/demo', 'isolated/demo/private', 'isolated/demo/cache', 'isolated/demo/sessions', 'isolated/demo/public', 'isolated/demo/logs', 'isolated/demo/views']);

test('an existing public storage path must be the isolated link', function (bool $symlink) {
    $isolation = isolatedConfiguration();

    if ($symlink) {
        symlink(storage_path(), public_path('storage'));
    } else {
        File::ensureDirectoryExists(public_path('storage'));
    }

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, 'ISOLATION_PUBLIC_STORAGE_TARGET_MISMATCH');
})->with([true, false]);

test('a correct isolated public storage link is accepted', function () {
    $isolation = isolatedConfiguration();
    symlink($isolation->storageRoot().'/public', public_path('storage'));
    $isolation->assertSafeConfiguration();
    expect(readlink(public_path('storage')))->toBe($isolation->storageRoot().'/public');
});

test('forced seed and reset commands stop before database access', function (string $environment, string $command) {
    $user = User::factory()->create();
    $original = DB::connection();
    $isolation = isolatedConfiguration($environment);

    if ($environment === 'production') {
        config(['database.connections.pgsql.database' => 'rozine', 'database.connections.pgsql.username' => 'rozine']);
    }

    $input = new ArrayInput(['command' => $command, '--force' => true]);

    expect(fn () => Event::dispatch(new CommandStarting($command, $input, new BufferedOutput)))
        ->toThrow(LogicException::class, $command === 'db:seed' ? 'ISOLATION_SEED_DENIED' : 'ISOLATION_RESET_DENIED');
    expect($original->table('users')->where('id', $user->id)->value('email'))->toBe($user->email);
})->with(['production', 'uat', 'demo'])->with(['db:seed', 'db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']);

test('the seeder itself rejects direct invocation outside an enabled demo or development', function () {
    $user = User::factory()->create();
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app()->call([new DatabaseSeeder, 'run']))->toThrow(LogicException::class, 'ISOLATION_SEED_DENIED');
    expect(User::count())->toBe(1)->and(User::findOrFail($user->id)->email)->toBe($user->email);
});

test('development seeding still uses the existing factory', function () {
    $this->seed(DatabaseSeeder::class);
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('framework seed prohibition survives provider boot order for nested calls without command events', function (string $environment) {
    $user = User::factory()->create();
    $original = DB::connection();
    $isolation = isolatedConfiguration($environment);

    if ($environment === 'production') {
        config(['database.connections.pgsql.database' => 'rozine', 'database.connections.pgsql.username' => 'rozine']);
    }

    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);
    (new AppServiceProvider(app()))->boot();

    $command = app(SeedCommand::class);
    $command->setLaravel(app());
    expect($command->run(new ArrayInput(['--force' => true]), new BufferedOutput))->toBe(1);
    expect($original->table('users')->where('id', $user->id)->value('email'))->toBe($user->email);
})->with(['production', 'uat', 'staging', 'demo']);

test('real forced artisan calls respect the booted environment prohibitions', function (string $environment, string $command) {
    $user = User::factory()->create();
    $original = DB::connection();
    $isolation = isolatedConfiguration($environment);

    if ($environment === 'production') {
        config(['database.connections.pgsql.database' => 'rozine', 'database.connections.pgsql.username' => 'rozine']);
    }

    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);
    (new AppServiceProvider(app()))->boot();

    expect(Artisan::call($command, ['--force' => true]))->toBe(1);
    expect($original->table('users')->where('id', $user->id)->value('email'))->toBe($user->email);
})->with(['production', 'uat', 'demo'])->with(['db:seed', 'db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']);

test('nested fresh commands are prohibited without relying on command events', function () {
    $user = User::factory()->create();
    app()->detectEnvironment(fn (): string => 'production');
    (new AppServiceProvider(app()))->boot();

    $command = app(FreshCommand::class);
    $command->setLaravel(app());
    expect($command->run(new ArrayInput(['--force' => true]), new BufferedOutput))->toBe(1);
    expect(User::findOrFail($user->id)->email)->toBe($user->email);
});

test('non-live runtime denies unfaked HTTP but supports deterministic provider fixtures', function () {
    $isolation = isolatedConfiguration();
    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);

    expect(fn () => Http::post('https://payments.example.test/settle', ['amount' => 5000]))
        ->toThrow(StrayRequestException::class);

    Http::fake(['https://payments.example.test/*' => Http::response(['fixture' => true])]);
    expect(Http::post('https://payments.example.test/settle')->json())->toBe(['fixture' => true]);
});

test('deployment checks require the exact target and never claim live acceptance', function () {
    isolatedConfiguration('uat');

    expect(Artisan::call('isolation:check', ['--expect' => 'uat']))->toBe(0)
        ->and(Artisan::output())->toContain('passes for uat', 'does not certify host IAM');

    expect(Artisan::call('isolation:check', ['--expect' => 'production']))->toBe(1)
        ->and(Artisan::output())->toContain('ISOLATION_DEPLOYMENT_TARGET_MISMATCH');

    expect(Artisan::call('isolation:check'))->toBe(1)
        ->and(Artisan::output())->toContain('ISOLATION_EXPECTED_PROFILE_REQUIRED');
    expect(Artisan::call('isolation:check', ['--expect' => 'staging']))->toBe(1);
});

test('a fresh application boots into isolated resources before providers resolve drivers', function () {
    $code = <<<'PHP'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($argv[1].'/storage');
$app->usePublicPath($argv[1].'/public');
$app->useDatabasePath($argv[1].'/database');
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
    $app->detectEnvironment(fn () => 'demo');
    $app['config']->set([
        'app.env' => 'demo', 'app.debug' => false, 'app.url' => 'https://demo.example.test',
        'isolation.demo_enabled' => false, 'isolation.demo_reset_enabled' => false,
        'isolation.live_money_enabled' => false,
        'database.default' => 'sqlite',
        'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => $app->databasePath('isolated/rozine_demo.sqlite')]],
        'database.redis' => [], 'services' => [], 'mail.mailers' => [],
        'filesystems.disks' => [], 'queue.connections' => [], 'logging.channels' => [],
    ]);
});
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$root = $app->storagePath('isolated/demo');
if ($app['config']->get('session.files') !== $root.'/sessions'
    || $app['cache']->store()->getStore()->getDirectory() !== $root.'/cache'
    || $app['filesystem']->disk()->path('sentinel') !== $root.'/private/sentinel'
    || $app['config']->get('mail.default') !== 'array'
    || $app['config']->get('queue.connections.database.queue') !== 'rozine_demo') {
    exit(44);
}
exit($app->make(Illuminate\Contracts\Console\Kernel::class)->call('isolation:check', ['--expect' => 'demo']));
PHP;

    $process = new Process([PHP_BINARY, '-r', $code, $this->isolationDirectory], base_path(), [
        'APP_CONFIG_CACHE' => $this->isolationDirectory.'/config.php',
    ]);
    $process->run();

    $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
    expect(File::exists(database_path('isolated/rozine_demo.sqlite')))->toBeFalse();
});
