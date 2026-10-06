<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * One real-browser journey: an owned `php -S` server on the isolated `rozine_test` database, driven
 * through named playwright-cli sessions, each a separate browser context (so a separate person).
 * Session names stay short because macOS limits the length of the CLI daemon's socket path.
 *
 * Every `code()` call fails on an uncaught page error, and `within()` captures a snapshot and a
 * screenshot of every open session on failure, then closes the sessions and stops the server.
 */
final class BrowserJourney
{
    public const string PHONE = '{width:390, height:844}';

    public const string DESKTOP = '{width:1280, height:900}';

    public readonly string $base;

    public readonly string $directory;

    private readonly string $cli;

    private readonly string $shots;

    private readonly Process $server;

    /** @var array<string, true> */
    private array $sessions = [];

    public function __construct(private readonly string $name, int $port)
    {
        if (DB::connection()->getDatabaseName() !== 'rozine_test') {
            throw new RuntimeException('Browser journeys only run against the isolated rozine_test database.');
        }
        $cli = getenv('PLAYWRIGHT_CLI');
        if (! is_string($cli) || ! is_executable($cli)) {
            throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
        }
        $this->cli = $cli;
        $this->directory = base_path('output/playwright/'.$name);
        $shots = getenv('AUDIT_SEAL_SHOTS');
        $this->shots = is_string($shots) && $shots !== '' ? rtrim($shots, '/') : $this->directory;
        foreach ([$this->directory, $this->shots] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
        $this->base = 'http://127.0.0.1:'.$port;
        $connection = config('database.connections.pgsql');
        $this->server = new Process([PHP_BINARY, '-S', '127.0.0.1:'.$port, base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
            'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $this->base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
            'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => 'rozine_test',
            'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
            'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_'.str_replace('-', '_', $name), 'CACHE_STORE' => 'array',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
        ]);
        $this->server->setTimeout(null)->start();
        if (! $this->server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server ('.$this->base.') started'))) {
            throw new RuntimeException('The isolated browser server for '.$name.' did not start: '.$this->server->getErrorOutput());
        }
    }

    /** Runs the journey's steps, capturing every session on failure and always cleaning up. */
    public function within(callable $steps): void
    {
        try {
            $steps($this);
        } catch (Throwable $failure) {
            foreach (array_keys($this->sessions) as $session) {
                try {
                    file_put_contents($this->directory.'/'.$session.'-failure.txt', $this->run($session, ['snapshot'])."\n".$this->run($session, ['requests']));
                    $this->run($session, ['run-code', 'async (page) => { await page.screenshot({path:'.$this->shot($session.'-failure').', fullPage:true}); }']);
                } catch (Throwable) {
                }
            }

            throw $failure;
        } finally {
            foreach (array_keys($this->sessions) as $session) {
                try {
                    $this->run($session, ['close']);
                } catch (Throwable) {
                }
            }
            $this->server->stop();
            file_put_contents($this->directory.'/server.log', $this->server->getErrorOutput());
        }
    }

    /** @param list<string> $arguments */
    public function run(string $session, array $arguments): string
    {
        $process = new Process([$this->cli, '--session', $session, ...$arguments], $this->directory);
        $process->setTimeout(90)->start();
        while ($process->isRunning()) {
            $this->server->getIncrementalErrorOutput();
            $process->checkTimeout();
            usleep(10_000);
        }
        $output = $process->getOutput();
        if (! $process->isSuccessful() || str_contains($output, '### Error')) {
            throw new RuntimeException(trim($output."\n".$process->getErrorOutput()));
        }

        return $output;
    }

    /** Opens a fresh browser context for one person, with no cookies or storage. */
    public function open(string $session, string $path): void
    {
        $this->sessions[$session] = true;
        $this->run($session, ['open', $this->base.$path]);
    }

    /**
     * Runs an async function body against `page`. The body may `return` a JSON value, which comes
     * back decoded. `noOverflow()` and `shot(path)` are in scope; any uncaught page error fails it.
     */
    public function code(string $session, string $body): mixed
    {
        $output = $this->run($session, ['run-code', 'async (page) => {
            const errors = [];
            const onError = (error) => errors.push(error.message);
            const noOverflow = async () => {
                if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) {
                    throw new Error("Horizontal overflow at " + page.viewportSize().width + "px on " + page.url());
                }
            };
            const shot = (path) => page.screenshot({path, fullPage:true, animations:"disabled"});
            page.on("pageerror", onError);
            let result;
            try {
                result = await (async () => {'.$body.'})();
            } finally {
                page.off("pageerror", onError);
            }
            if (errors.length) throw new Error("Page errors: " + errors.join("\n"));
            return JSON.stringify(result === undefined ? null : result);
        }']);
        file_put_contents($this->directory.'/'.$session.'-last.txt', $output);
        if (preg_match('/### Result\s*\n(.*?)(?:\n###|\z)/s', $output, $match) !== 1) {
            return null;
        }
        $encoded = json_decode(trim($match[1]), true);

        return is_string($encoded) ? json_decode($encoded, true) : $encoded;
    }

    /** Signs a person in through the real login form, answering the TOTP challenge when enrolled. */
    public function login(string $session, string $email, string $password, ?string $secret = null): void
    {
        $this->open($session, '/login');
        $challenge = $secret === null ? '' : '
            await page.waitForURL("**/two-factor-challenge");
            await page.locator("input[name=code]").fill('.json_encode($this->otp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();';
        $this->code($session, '
            await page.getByRole("textbox", {name:"Email address"}).fill('.json_encode($email).');
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();'.$challenge.'
            await page.waitForURL("**/dashboard");');
    }

    /** The current six-digit code for a synthetic authenticator secret. */
    public function otp(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    /** A screenshot path under the journey's shots directory, JSON-encoded for use in page code. */
    public function shot(string $name): string
    {
        return json_encode($this->shots.'/ud-'.$this->name.'-'.$name.'.png', JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
