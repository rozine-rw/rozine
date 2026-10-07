<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\StagingMailAllowance;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Providers\EnvironmentSafetyServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
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

/**
 * Staging's Resend relay as the server's .env would state it.
 *
 * @return array<string, mixed>
 */
function stagingResendMail(): array
{
    return [
        'mail.default' => 'smtp',
        'mail.mailers.smtp' => [
            'transport' => 'smtp',
            'scheme' => 'smtps',
            'url' => null,
            'host' => 'smtp.resend.com',
            'port' => 465,
            'username' => 'resend',
            'password' => 'staging-key-do-not-expose',
            'timeout' => null,
            'local_domain' => 'uat.example.test',
        ],
        'mail.from.address' => 'staging@mail.rozine.rw',
        'mail.from.name' => 'Rozine Staging',
        'isolation.staging_mail.recipients' => ['tester@example.test', '@rozine.rw'],
        'isolation.staging_mail.hourly_limit' => 50,
    ];
}

/**
 * Boots staging with real mail switched on, then routes delivery to the
 * in-memory transport so the boundary can be observed without a network.
 *
 * @param  array<string, mixed>  $overrides
 * @return Collection<int, MessageSent>
 */
function stagingMailDeliveries(array $overrides = []): Collection
{
    $isolation = isolatedConfiguration('staging');
    config([...stagingResendMail(), ...$overrides]);
    $isolation->configure();
    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);
    config(['mail.default' => 'array']);
    Mail::forgetMailers();

    $deliveries = collect();
    Event::listen(MessageSent::class, fn (MessageSent $event) => $deliveries->push($event));

    return $deliveries;
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

test('staging may send real mail only through its Resend relay', function (string $environment) {
    $isolation = isolatedConfiguration($environment);
    config(stagingResendMail());
    $isolation->configure();

    expect($isolation->sendsStagingMail())->toBeTrue()
        ->and(config('mail.default'))->toBe('smtp')
        ->and(array_keys(config('mail.mailers')))->toBe(['array', 'smtp'])
        ->and(config('mail.mailers.smtp'))->not->toHaveKey('url')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.resend.com')
        ->and(config('mail.mailers.smtp.username'))->toBe('resend')
        ->and(config('services'))->toBe([]);

    $before = config()->all();
    $isolation->configure();
    expect(config()->all())->toBe($before)
        ->and(Artisan::call('isolation:check', ['--expect' => 'uat']))->toBe(0);
})->with(['staging', 'uat']);

test('staging mail refuses any other relay or credential shape without exposing the key', function (string $key, mixed $value) {
    $isolation = isolatedConfiguration('staging');
    config(stagingResendMail());
    config([$key => $value]);

    try {
        $isolation->configure();
        $this->fail('A staging mail relay other than Resend was accepted.');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toBe('ISOLATION_MAIL_PROVIDER_DENIED')
            ->not->toContain('staging-key-do-not-expose');
    }
})->with([
    'another relay' => ['mail.mailers.smtp.host', 'smtp.example.test'],
    'a relay hidden in a url' => ['mail.mailers.smtp.url', 'smtps://resend:staging-key-do-not-expose@smtp.example.test:465'],
    'another account' => ['mail.mailers.smtp.username', 'apikey'],
    'no key' => ['mail.mailers.smtp.password', ''],
    'another transport' => ['mail.mailers.smtp.transport', 'sendmail'],
    'plaintext or a STARTTLS downgrade' => ['mail.mailers.smtp.scheme', 'smtp'],
    'no scheme' => ['mail.mailers.smtp.scheme', null],
    'peer verification off' => ['mail.mailers.smtp.verify_peer', false],
    'peer verification off as text' => ['mail.mailers.smtp.verify_peer', 'false'],
    'TLS negotiation off' => ['mail.mailers.smtp.auto_tls', false],
    'an unreviewed transport option' => ['mail.mailers.smtp.peer_fingerprint', 'sha256'],
]);

test('staging mail refuses to start without owner-approved testers and an hourly allowance', function (string $key, mixed $value, string $error) {
    $isolation = isolatedConfiguration('uat');
    config(stagingResendMail());
    config([$key => $value]);

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, $error);
})->with([
    'no testers' => ['isolation.staging_mail.recipients', [], 'ISOLATION_MAIL_RECIPIENTS_REQUIRED'],
    'not an address' => ['isolation.staging_mail.recipients', ['tester'], 'ISOLATION_MAIL_RECIPIENTS_REQUIRED'],
    'a bare at sign' => ['isolation.staging_mail.recipients', ['@'], 'ISOLATION_MAIL_RECIPIENTS_REQUIRED'],
    'a domain without a dot' => ['isolation.staging_mail.recipients', ['tester@example.test', '@rozine'], 'ISOLATION_MAIL_RECIPIENTS_REQUIRED'],
    'no allowance' => ['isolation.staging_mail.hourly_limit', false, 'ISOLATION_MAIL_LIMIT_REQUIRED'],
    'a zero allowance' => ['isolation.staging_mail.hourly_limit', 0, 'ISOLATION_MAIL_LIMIT_REQUIRED'],
    'an allowance as text' => ['isolation.staging_mail.hourly_limit', '50', 'ISOLATION_MAIL_LIMIT_REQUIRED'],
]);

test('the staging tester list and allowance are read from the environment strictly', function () {
    $_SERVER['STAGING_MAIL_RECIPIENTS'] = $_ENV['STAGING_MAIL_RECIPIENTS'] = ' tester@example.test, @rozine.rw ,, ';
    $_SERVER['STAGING_MAIL_HOURLY_LIMIT'] = $_ENV['STAGING_MAIL_HOURLY_LIMIT'] = '25';
    $parsed = require config_path('isolation.php');

    $_SERVER['STAGING_MAIL_HOURLY_LIMIT'] = $_ENV['STAGING_MAIL_HOURLY_LIMIT'] = '25 per hour';
    $unparsable = require config_path('isolation.php');

    unset($_SERVER['STAGING_MAIL_RECIPIENTS'], $_ENV['STAGING_MAIL_RECIPIENTS'], $_SERVER['STAGING_MAIL_HOURLY_LIMIT'], $_ENV['STAGING_MAIL_HOURLY_LIMIT']);
    $unset = require config_path('isolation.php');

    expect($parsed['staging_mail'])->toBe(['recipients' => ['tester@example.test', '@rozine.rw'], 'hourly_limit' => 25])
        ->and($unparsable['staging_mail']['hourly_limit'])->toBeFalse()
        ->and($unset['staging_mail'])->toBe(['recipients' => [], 'hourly_limit' => false]);
});

test('staging mail reaches approved testers only as the staging sender whatever the message asked for', function () {
    $deliveries = stagingMailDeliveries();

    Mail::raw('Approved by address.', fn ($message) => $message->to('Tester@Example.test')->subject('Verify Email Address')
        ->from('no-reply@mail.rozine.rw', 'Rozine')->sender('ops@mail.rozine.rw')->returnPath('bounce@mail.rozine.rw'));
    Mail::raw('Approved by domain.', fn ($message) => $message->to('someone@ROZINE.rw')->subject('Reset Password'));

    expect($deliveries)->toHaveCount(2);

    foreach ($deliveries as $delivery) {
        $from = $delivery->message->getFrom();

        expect($from)->toHaveCount(1)
            ->and($from[0]->getAddress())->toBe('staging@mail.rozine.rw')
            ->and($from[0]->getName())->toBe('Rozine Staging')
            ->and($delivery->message->getSender())->toBeNull()
            ->and($delivery->message->getReturnPath())->toBeNull()
            ->and($delivery->sent->getSymfonySentMessage()->getEnvelope()->getSender()->getAddress())->toBe('staging@mail.rozine.rw')
            ->and($delivery->message->getSubject())->toStartWith('[Staging] ');
    }
});

test('a staging message with any recipient outside the approved testers is withheld', function (Closure $address) {
    $deliveries = stagingMailDeliveries();

    Mail::raw('Not for outsiders.', fn ($message) => $address($message->to('tester@example.test')->subject('Hello')));

    expect($deliveries)->toBeEmpty();
})->with([
    'an outside To' => fn ($message) => $message->to('stranger@example.org'),
    'an outside Cc' => fn ($message) => $message->cc('stranger@example.org'),
    'an outside Bcc' => fn ($message) => $message->bcc('stranger@example.org'),
    'a lookalike domain' => fn ($message) => $message->to('someone@evilrozine.rw'),
    'a subdomain of an approved domain' => fn ($message) => $message->to('someone@mail.rozine.rw'),
]);

test('verification and password reset notifications only reach approved testers on staging', function (string $email, int $expected) {
    $deliveries = stagingMailDeliveries();
    $user = User::factory()->make(['id' => 4242, 'email' => $email]);

    $user->notify(new VerifyEmail);
    $user->notify(new ResetPassword('synthetic-reset-token'));

    expect($deliveries)->toHaveCount($expected);
})->with([
    'an approved tester' => ['tester@example.test', 2],
    'an unapproved sign-up' => ['stranger@example.org', 0],
]);

test('staging mail stops once the hourly allowance is spent', function () {
    $deliveries = stagingMailDeliveries(['isolation.staging_mail.hourly_limit' => 2]);

    foreach (range(1, 3) as $attempt) {
        Mail::raw('Attempt '.$attempt, fn ($message) => $message->to('tester@example.test')->subject('Attempt '.$attempt));
    }

    expect($deliveries->map(fn (MessageSent $delivery): ?string => $delivery->message->getSubject())->all())
        ->toBe(['[Staging] Attempt 1', '[Staging] Attempt 2']);
});

test('a message waits for another worker taking the last place and is withheld if it cannot', function () {
    $deliveries = stagingMailDeliveries();
    Sleep::fake(syncWithCarbon: true);
    // Another worker is part-way through its own reservation and holds the allowance lock.
    $store = Cache::store(config('cache.limiter'))->getStore();
    if (! $store instanceof LockProvider) {
        $this->fail('The configured limiter cache cannot lock.');
    }
    $otherWorker = $store->lock('staging-mail:reservation', 10);
    expect($otherWorker->get())->toBeTrue();

    Mail::raw('While another worker reserves.', fn ($message) => $message->to('tester@example.test')->subject('Blocked'));
    $otherWorker->release();
    Mail::raw('After it finished.', fn ($message) => $message->to('tester@example.test')->subject('Released'));

    expect($deliveries->map(fn (MessageSent $delivery): ?string => $delivery->message->getSubject())->all())
        ->toBe(['[Staging] Released']);
});

test('staging mail is withheld when the configured cache cannot lock the allowance', function () {
    config(['isolation.staging_mail.hourly_limit' => 50, 'cache.limiter' => 'apc-without-locks']);
    // APC is a real cache that offers no locks; nothing is stored, so the extension is not needed.
    Cache::extend('apc-without-locks', fn () => Cache::repository(new ApcStore(new ApcWrapper)));
    config(['cache.stores.apc-without-locks' => ['driver' => 'apc-without-locks']]);

    expect(app(StagingMailAllowance::class)->reserve())->toBeFalse();
});

test('independent workers together never exceed the hourly allowance', function () {
    $code = <<<'PHP'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) use ($argv) {
    $app['config']->set([
        'cache.default' => 'file', 'cache.limiter' => 'file',
        'cache.stores.file' => ['driver' => 'file', 'path' => $argv[1], 'lock_path' => $argv[1]],
        'isolation.staging_mail.hourly_limit' => 12,
    ]);
});
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$reserved = 0;
foreach (range(1, 8) as $attempt) {
    $reserved += $app->make(App\Application\Environment\StagingMailAllowance::class)->reserve() ? 1 : 0;
}
echo $reserved;
PHP;

    $cache = $this->isolationDirectory.'/shared-cache';
    File::ensureDirectoryExists($cache);
    $workers = array_map(fn (): Process => new Process([PHP_BINARY, '-r', $code, $cache], base_path()), range(1, 4));
    array_walk($workers, fn (Process $worker) => $worker->start());

    $reserved = array_sum(array_map(function (Process $worker): int {
        $worker->wait();
        $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput());

        return (int) $worker->getOutput();
    }, $workers));

    // 4 workers × 8 attempts = 32 tries for 12 places.
    expect($reserved)->toBe(12);
});

test('environments without staging mail keep the sender and recipients the message asked for', function (string $environment) {
    $isolation = $environment === 'testing' ? app(EnvironmentIsolation::class) : isolatedConfiguration($environment);
    config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array']]);
    Mail::forgetMailers();
    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);
    $deliveries = collect();
    Event::listen(MessageSent::class, fn (MessageSent $event) => $deliveries->push($event));

    Mail::raw('Production path.', fn ($message) => $message->to('stranger@example.org')->from('no-reply@mail.rozine.rw', 'Rozine')->subject('Hello'));

    expect($deliveries)->toHaveCount(1)
        ->and($deliveries[0]->message->getFrom()[0]->getAddress())->toBe('no-reply@mail.rozine.rw');
})->with(['testing', 'uat']);

test('staging mail must come from a sender that says staging', function (mixed $address, mixed $name) {
    $isolation = isolatedConfiguration('uat');
    config(stagingResendMail());
    config(['mail.from.address' => $address, 'mail.from.name' => $name]);

    expect(fn () => $isolation->configure())->toThrow(LogicException::class, 'ISOLATION_MAIL_SENDER_NOT_STAGING');
})->with([
    'the production sender' => ['no-reply@mail.rozine.rw', 'Rozine Staging'],
    'a sender named like production' => ['staging@mail.rozine.rw', 'Rozine'],
    'no sender' => [null, 'Rozine Staging'],
    'staging only after the at sign' => ['hello@staging.rozine.rw', 'Rozine Staging'],
]);

test('demo never sends real mail even with the staging relay configured', function () {
    $isolation = isolatedConfiguration('demo');
    config(stagingResendMail());

    expect($isolation->sendsStagingMail())->toBeFalse()
        ->and(fn () => $isolation->configure())->toThrow(LogicException::class, 'ISOLATION_PROVIDER_CREDENTIALS_DENIED');

    config(['mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null]);
    $isolation->configure();

    expect(config('mail.default'))->toBe('array')
        ->and(config('mail.mailers'))->toBe(['array' => ['transport' => 'array']]);
});

test('every staging email is marked as staging and other environments keep their subjects', function (string $environment, string $subject) {
    $isolation = $environment === 'testing' ? app(EnvironmentIsolation::class) : isolatedConfiguration($environment);
    config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array']]);
    Mail::forgetMailers();
    (new EnvironmentSafetyServiceProvider(app()))->boot($isolation);
    $subjects = [];
    Event::listen(MessageSent::class, function (MessageSent $event) use (&$subjects): void {
        $subjects[] = $event->message->getSubject();
    });

    Mail::raw('Open the link to verify.', fn ($message) => $message->to('tester@example.test')->subject('Verify Email Address'));
    Mail::raw('Already marked.', fn ($message) => $message->to('tester@example.test')->subject('[Staging] Reset Password'));

    expect($subjects)->toBe([$subject, '[Staging] Reset Password']);
})->with([
    'staging' => ['staging', '[Staging] Verify Email Address'],
    'uat' => ['uat', '[Staging] Verify Email Address'],
    'demo' => ['demo', 'Verify Email Address'],
    'testing' => ['testing', 'Verify Email Address'],
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

test('the synthetic wallet seed, event hook and worker stop outside local and testing', function (string $environment, string $command) {
    isolatedConfiguration($environment);
    if ($environment === 'production') {
        config(['database.connections.pgsql.database' => 'rozine', 'database.connections.pgsql.username' => 'rozine']);
    }

    expect(fn () => Event::dispatch(new CommandStarting($command, new ArrayInput(['command' => $command]), new BufferedOutput)))
        ->toThrow(LogicException::class, 'ISOLATION_SYNTHETIC_WALLET_DENIED');
})->with(['production', 'uat', 'demo'])->with(['local:wallet', 'wallet:dispatch-deposits']);

test('the synthetic wallet commands also stop on testing once live money is switched on', function () {
    config(['isolation.live_money_enabled' => true]);

    expect(fn () => app(EnvironmentIsolation::class)->guardCommand('wallet:dispatch-deposits'))->toThrow(LogicException::class, 'ISOLATION_SYNTHETIC_WALLET_DENIED');
    config(['isolation.live_money_enabled' => false]);
    expect(fn () => app(EnvironmentIsolation::class)->guardCommand('local:wallet'))->not->toThrow(LogicException::class);
});

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
