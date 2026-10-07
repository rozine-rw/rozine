<?php

declare(strict_types=1);

namespace App\Application\Environment;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use LogicException;

class EnvironmentIsolation
{
    /** The only SMTP settings staging's Resend relay may carry. */
    private const array STAGING_MAILER_KEYS = ['transport', 'scheme', 'url', 'host', 'port', 'username', 'password', 'timeout', 'local_domain', 'verify_peer'];

    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
    ) {}

    public function profile(): string
    {
        return match ($this->app->environment()) {
            'local' => 'local',
            'testing' => 'testing',
            'demo' => 'demo',
            'staging', 'uat' => 'uat',
            'production' => 'production',
            default => throw new LogicException('ISOLATION_UNKNOWN_ENVIRONMENT'),
        };
    }

    public function isIsolated(): bool
    {
        return in_array($this->profile(), ['demo', 'uat'], true);
    }

    /**
     * Staging may opt into real mail, and only through its own Resend SMTP
     * key as a visibly staging sender. Every other non-live profile and every
     * staging configuration without the opt-in keeps mail in memory.
     */
    public function sendsStagingMail(): bool
    {
        return $this->profile() === 'uat' && $this->config->get('mail.default') === 'smtp';
    }

    public function assertSafeConfiguration(?string $expectedProfile = null): void
    {
        $profile = $this->profile();

        if ($expectedProfile !== null && $profile !== $expectedProfile) {
            throw new LogicException('ISOLATION_DEPLOYMENT_TARGET_MISMATCH');
        }

        $demoEnabled = $this->config->get('isolation.demo_enabled');
        $resetEnabled = $this->config->get('isolation.demo_reset_enabled');

        if (! is_bool($demoEnabled) || ! is_bool($resetEnabled)) {
            throw new LogicException('ISOLATION_FLAGS_MUST_BE_BOOLEAN');
        }

        if (($profile !== 'demo' && ($demoEnabled || $resetEnabled)) || ($resetEnabled && ! $demoEnabled)) {
            throw new LogicException('ISOLATION_DEMO_FLAGS_DENIED');
        }

        if ($this->config->get('isolation.live_money_enabled') !== false) {
            throw new LogicException('ISOLATION_LIVE_MONEY_NOT_ACTIVATED');
        }

        if ($demoEnabled && now('UTC')->toDateString() >= $this->config->string('isolation.demo_flags.expires_at')) {
            throw new LogicException('ISOLATION_DEMO_FLAGS_EXPIRED');
        }

        if ($profile === 'production') {
            $this->assertProductionDatabaseNamespace();
        }

        if (! $this->isIsolated()) {
            return;
        }

        $this->assertDatabaseBoundary();
        $this->assertStagingMailBoundary();
        $this->assertNoProviderCredentials();
        $this->assertStorageBoundary();

        if ($this->config->get('app.debug') !== false) {
            throw new LogicException('ISOLATION_DEBUG_MUST_BE_DISABLED');
        }

        $url = $this->config->string('app.url');
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($host) || in_array(strtolower($host), ['rozine.rw', 'www.rozine.rw'], true)
            || ! in_array($scheme, ['http', 'https'], true)
            || ($profile === 'uat' && $scheme !== 'https')) {
            throw new LogicException('ISOLATION_NON_LIVE_URL_REQUIRED');
        }
    }

    /**
     * Install a deliberately narrow, local-only non-live runtime before any
     * provider boots. No secondary database, cloud disk, Redis, SQS or fallback
     * driver remains selectable by an explicit connection name, and SMTP only
     * as staging's checked Resend relay.
     */
    public function configure(): void
    {
        $this->assertSafeConfiguration();

        if (! $this->isIsolated()) {
            return;
        }

        $connection = $this->config->string('database.default');
        $database = $this->config->array('database.connections.'.$connection);
        $namespace = 'rozine_'.$this->profile();
        $root = $this->storageRoot();

        $this->config->set([
            'database.connections' => [$connection => $database],
            'database.redis' => [],
            'cache.default' => 'file',
            'cache.stores' => ['file' => [
                'driver' => 'file',
                'path' => $root.'/cache',
                'lock_path' => $root.'/cache',
            ]],
            'cache.prefix' => $namespace.'-cache-',
            'session.driver' => 'file',
            'session.files' => $root.'/sessions',
            'session.cookie' => $namespace.'-session',
            'session.domain' => null,
            'session.path' => '/',
            'session.encrypt' => true,
            'session.secure' => str_starts_with($this->config->string('app.url'), 'https://'),
            'view.compiled' => $root.'/views',
            'queue.default' => 'database',
            'queue.connections' => [
                'database' => [
                    'driver' => 'database',
                    'connection' => $connection,
                    'table' => 'jobs',
                    'queue' => $namespace,
                    'retry_after' => 90,
                    'after_commit' => true,
                ],
                'sync' => ['driver' => 'sync'],
            ],
            'queue.batching' => ['database' => $connection, 'table' => 'job_batches'],
            'queue.failed' => ['driver' => 'database-uuids', 'database' => $connection, 'table' => 'failed_jobs'],
            'filesystems.default' => 'local',
            'filesystems.disks' => [
                'local' => ['driver' => 'local', 'root' => $root.'/private', 'throw' => true],
                'public' => [
                    'driver' => 'local',
                    'root' => $root.'/public',
                    'url' => rtrim($this->config->string('app.url'), '/').'/storage',
                    'visibility' => 'public',
                    'throw' => true,
                ],
            ],
            'filesystems.links' => [$this->app->publicPath('storage') => $root.'/public'],
            'mail.default' => $this->sendsStagingMail() ? 'smtp' : 'array',
            'mail.mailers' => ['array' => ['transport' => 'array']] + ($this->sendsStagingMail()
                ? ['smtp' => Arr::except($this->config->array('mail.mailers.smtp'), ['url'])]
                : []),
            'services' => [],
            'logging.default' => 'isolated',
            'logging.channels' => ['isolated' => [
                'driver' => 'daily',
                'path' => $root.'/logs/laravel.log',
                'level' => 'info',
                'days' => 14,
                'replace_placeholders' => true,
            ]],
            'app.maintenance' => ['driver' => 'file'],
        ]);
    }

    public function canSeed(): bool
    {
        $this->assertSafeConfiguration();

        return in_array($this->profile(), ['local', 'testing'], true)
            || ($this->profile() === 'demo' && $this->config->get('isolation.demo_enabled') === true);
    }

    public function canReset(): bool
    {
        return $this->canSeed() && ($this->profile() !== 'demo'
            || $this->config->get('isolation.demo_reset_enabled') === true);
    }

    public function assertSeedingAllowed(): void
    {
        if (! $this->canSeed()) {
            throw new LogicException('ISOLATION_SEED_DENIED');
        }
    }

    public function guardCommand(string $command): void
    {
        if ($command === 'db:seed') {
            $this->assertSeedingAllowed();
        }

        if (in_array($command, ['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'], true)
            && ! $this->canReset()) {
            throw new LogicException('ISOLATION_RESET_DENIED');
        }

        // The synthetic wallet seed, event hook and outbox worker exist for local and testing only.
        if (in_array($command, ['local:wallet', 'wallet:dispatch-deposits'], true)
            && (! in_array($this->profile(), ['local', 'testing'], true) || $this->config->get('isolation.live_money_enabled') !== false)) {
            throw new LogicException('ISOLATION_SYNTHETIC_WALLET_DENIED');
        }
    }

    public function assertDemoResetAllowed(): void
    {
        $this->assertSafeConfiguration('demo');

        if (! $this->canReset()) {
            throw new LogicException('ISOLATION_DEMO_RESET_DENIED');
        }
    }

    public function storageRoot(): string
    {
        return $this->app->storagePath('isolated/'.$this->profile());
    }

    private function assertDatabaseBoundary(): void
    {
        $connection = $this->config->string('database.default');
        $database = $this->config->array('database.connections.'.$connection);
        $namespace = 'rozine_'.$this->profile();

        if (! empty($database['url']) || isset($database['read']) || isset($database['write'])) {
            throw new LogicException('ISOLATION_DATABASE_OVERRIDE_DENIED');
        }

        if (($database['driver'] ?? null) === 'pgsql'
            && ($database['database'] ?? null) === $namespace
            && ($database['username'] ?? null) === $namespace
            && ($database['search_path'] ?? null) === 'public') {
            return;
        }

        if ($this->profile() === 'demo' && ($database['driver'] ?? null) === 'sqlite'
            && ($database['database'] ?? null) === $this->app->databasePath('isolated/rozine_demo.sqlite')
            && ! is_link($this->app->databasePath('isolated'))
            && ! is_link($this->app->databasePath('isolated/rozine_demo.sqlite'))) {
            return;
        }

        throw new LogicException('ISOLATION_DEDICATED_DATABASE_REQUIRED');
    }

    private function assertProductionDatabaseNamespace(): void
    {
        $connection = $this->config->string('database.default');
        $database = $this->config->array('database.connections.'.$connection);
        $url = $database['url'] ?? '';
        $urlDatabase = is_string($url) ? parse_url($url, PHP_URL_PATH) : null;
        $urlUser = is_string($url) ? parse_url($url, PHP_URL_USER) : null;

        foreach ([$database['database'] ?? '', $database['username'] ?? '', $urlDatabase, $urlUser] as $value) {
            if (is_string($value) && preg_match('~(?:^|[/\\\\])rozine_(demo|uat)(?:$|[_.])~', rawurldecode($value)) === 1) {
                throw new LogicException('ISOLATION_PRODUCTION_USES_NON_LIVE_DATABASE');
            }
        }
    }

    private function assertStagingMailBoundary(): void
    {
        if (! $this->sendsStagingMail()) {
            return;
        }

        $mailer = $this->config->array('mail.mailers.smtp');
        $host = $mailer['host'] ?? null;
        $password = $mailer['password'] ?? null;
        $verifyPeer = $mailer['verify_peer'] ?? true;

        // Implicit TLS only, with the peer certificate verified: no plaintext,
        // no STARTTLS downgrade and no option the boundary has not reviewed.
        if (array_diff(array_keys($mailer), self::STAGING_MAILER_KEYS) !== []
            || ($mailer['transport'] ?? null) !== 'smtp' || ($mailer['scheme'] ?? null) !== 'smtps'
            || ! empty($mailer['url']) || filter_var($verifyPeer, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) !== true
            || ! is_string($host) || strtolower($host) !== 'smtp.resend.com'
            || ($mailer['username'] ?? null) !== 'resend'
            || ! is_string($password) || $password === '') {
            throw new LogicException('ISOLATION_MAIL_PROVIDER_DENIED');
        }

        $recipients = $this->config->get('isolation.staging_mail.recipients');

        if (! is_array($recipients) || $recipients === []
            || array_filter($recipients, fn (mixed $entry): bool => ! is_string($entry)
                || (preg_match('/^@[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $entry) !== 1
                    && filter_var($entry, FILTER_VALIDATE_EMAIL) === false)) !== []) {
            throw new LogicException('ISOLATION_MAIL_RECIPIENTS_REQUIRED');
        }

        $hourlyLimit = $this->config->get('isolation.staging_mail.hourly_limit');

        if (! is_int($hourlyLimit) || $hourlyLimit < 1) {
            throw new LogicException('ISOLATION_MAIL_LIMIT_REQUIRED');
        }

        $address = $this->config->get('mail.from.address');
        $name = $this->config->get('mail.from.name');

        if (! is_string($address) || preg_match('/^staging[^@\s]*@[^@\s]+$/i', $address) !== 1
            || ! is_string($name) || stripos($name, 'staging') === false) {
            throw new LogicException('ISOLATION_MAIL_SENDER_NOT_STAGING');
        }
    }

    private function assertNoProviderCredentials(): void
    {
        $stagingMailCredentials = $this->sendsStagingMail() ? ['mailers.smtp.username', 'mailers.smtp.password'] : [];

        $values = Arr::dot([
            'services' => $this->config->array('services'),
            'mailers' => $this->config->array('mail.mailers'),
            'disks' => $this->config->array('filesystems.disks'),
            'queues' => $this->config->array('queue.connections'),
            'logs' => $this->config->array('logging.channels'),
            'redis' => $this->config->array('database.redis'),
        ]);

        foreach ($values as $key => $value) {
            if (preg_match('/(?:key|secret|token|password|username|bucket|url|endpoint)$/', $key) === 1
                && $value !== null && $value !== '' && $value !== false
                && ! in_array($key, ['disks.public.url', 'logs.slack.username', ...$stagingMailCredentials], true)) {
                throw new LogicException('ISOLATION_PROVIDER_CREDENTIALS_DENIED');
            }
        }
    }

    private function assertStorageBoundary(): void
    {
        foreach (['isolated', 'isolated/'.$this->profile()] as $path) {
            if (is_link($this->app->storagePath($path))) {
                throw new LogicException('ISOLATION_STORAGE_SYMLINK_DENIED');
            }
        }

        foreach (['cache', 'sessions', 'private', 'public', 'logs', 'views'] as $directory) {
            if (is_link($this->storageRoot().'/'.$directory)) {
                throw new LogicException('ISOLATION_STORAGE_SYMLINK_DENIED');
            }
        }

        $publicLink = $this->app->publicPath('storage');

        if ((file_exists($publicLink) || is_link($publicLink))
            && (! is_link($publicLink) || readlink($publicLink) !== $this->storageRoot().'/public')) {
            throw new LogicException('ISOLATION_PUBLIC_STORAGE_TARGET_MISMATCH');
        }
    }
}
