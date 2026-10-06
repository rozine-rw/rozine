<?php

declare(strict_types=1);

use App\Models\AuditEngagementAcceptance;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('reads and explicitly accepts the synthetic engagement terms from the Auditor landing page', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
    }
    $directory = base_path('output/playwright/auditor-engagement');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    /* Screenshots go where ENGAGEMENT_SHOTS points, or beside the run's other output. */
    $shots = getenv('ENGAGEMENT_SHOTS');
    $shots = is_string($shots) && $shots !== '' ? rtrim($shots, '/') : $directory;
    if (! is_dir($shots)) {
        mkdir($shots, 0777, true);
    }
    $shot = static fn (string $name): string => json_encode($shots.'/engagement-'.$name.'.png', JSON_THROW_ON_ERROR);

    /* One MFA-enrolled Auditor, and synthetic terms published but not yet accepted. */
    $password = 'Synthetic-browser-fixture-42!';
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $actor = AuditorFixture::make();
    $actor['user']->forceFill(['email' => 'engagement-auditor@example.test', 'password' => $password, 'two_factor_secret' => encrypt($secret)])->save();
    $release = AuditEngagementFixture::release($actor['staff']);

    $base = 'http://127.0.0.1:8015';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8015', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => 'rozine_test', 'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_auditor_engagement_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $session = 'rozine-engagement';
    $run = function (array $arguments) use ($cli, $session, $directory, $server): string {
        $process = new Process([$cli, '--session', $session, ...$arguments], $directory);
        $process->setTimeout(90)->start();
        while ($process->isRunning()) {
            $server->getIncrementalErrorOutput();
            $process->checkTimeout();
            usleep(10_000);
        }
        $output = $process->getOutput();
        if (! $process->isSuccessful() || str_contains($output, '### Error')) {
            throw new RuntimeException(trim($output."\n".$process->getErrorOutput()));
        }

        return $output;
    };
    $noOverflow = 'if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Mobile overflow");';
    $label = 'I have read and accept the Master Services Agreement and the Agreed Procedures';
    try {
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8015) started'))) {
            throw new RuntimeException('The isolated Auditor browser server did not start.');
        }
        $run(['open', $base.'/login']);
        $run(['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("engagement-auditor@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
        }']);

        /* Signs in, opens the Auditor landing page and follows its engagement banner. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await page.setViewportSize({width:1280, height:860});
            await page.locator("input[name=code]").fill('.json_encode($totp->getCurrentOtp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.getByRole("button", {name:"Auditor", exact:true}).click();
            await page.waitForURL("**/auditor");
            await page.getByRole("link", {name:/Review and accept the engagement terms to take new work/}).click();
            await page.waitForURL("**/auditor/engagement");
            await page.getByRole("heading", {name:"Engagement terms", level:1}).waitFor();
            await page.getByRole("note").getByText("Synthetic test terms", {exact:true}).waitFor();
            const body = await page.getByTestId("terms-master_services").textContent();
            if (body !== '.json_encode(AuditEngagementFixture::documents()['master_services']['body']).') throw new Error("Terms altered: " + body);
            const accept = page.getByRole("button", {name:"Accept the terms", exact:true});
            if (!(await accept.isDisabled())) throw new Error("Accept offered before the box is ticked");
            await page.emulateMedia({colorScheme:"light"}); await page.reload();
            await page.getByRole("heading", {name:"Engagement terms", level:1}).waitFor();
            await page.screenshot({path:'.$shot('desktop-light').', fullPage:true, animations:"disabled"});
            await page.emulateMedia({colorScheme:"dark"}); await page.reload();
            await page.getByRole("heading", {name:"Engagement terms", level:1}).waitFor();
            await page.screenshot({path:'.$shot('desktop-dark').', fullPage:true, animations:"disabled"});
            await page.setViewportSize({width:390, height:844});
            await page.reload();
            await page.getByRole("heading", {name:"Engagement terms", level:1}).waitFor();
            '.$noOverflow.'
            await page.screenshot({path:'.$shot('phone-dark').', fullPage:true, animations:"disabled"});
            await page.emulateMedia({colorScheme:"light"}); await page.reload();
            await page.getByRole("heading", {name:"Engagement terms", level:1}).waitFor();
            '.$noOverflow.'
            await page.screenshot({path:'.$shot('phone-light').', fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {banner:true, verbatim:true, gated:true};
        }']);
        file_put_contents($directory.'/read-result.txt', $result);

        /* Ticks the box and accepts; the page then reads its facts afresh and shows the receipt. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await page.setViewportSize({width:1280, height:860});
            await page.getByText('.json_encode($label).', {exact:true}).click();
            if (!(await page.getByRole("checkbox", {name:'.json_encode($label).'}).isChecked())) throw new Error("The box did not tick");
            const sent = page.waitForRequest(request => request.url().endsWith("/auditor/engagement/accept") && request.method() === "POST");
            await page.getByRole("button", {name:"Accept the terms", exact:true}).click();
            const body = (await sent).postDataJSON();
            const keys = Object.keys(body).sort().join(",");
            if (keys !== "accepted,expected_revision,identity_context_revision,release_id,request_id,sha256" || body.accepted !== true) throw new Error("Unexpected body: " + JSON.stringify(body));
            await page.getByText(/^You accepted version synthetic-terms-1 on /).waitFor();
            if (await page.getByRole("checkbox").count() !== 0) throw new Error("Accept still offered after acceptance");
            await page.screenshot({path:'.$shot('accepted-desktop-light').', fullPage:true, animations:"disabled"});
            await page.emulateMedia({colorScheme:"dark"}); await page.reload();
            await page.getByText(/^You accepted version synthetic-terms-1 on /).waitFor();
            await page.screenshot({path:'.$shot('accepted-desktop-dark').', fullPage:true, animations:"disabled"});
            await page.goto('.json_encode($base.'/auditor').');
            if (await page.getByRole("link", {name:/Review and accept the engagement terms/}).count() !== 0) throw new Error("Banner after acceptance");
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {accepted:true, body:keys};
        }']);
        file_put_contents($directory.'/accept-result.txt', $result);

        expect(AuditEngagementAcceptance::query()->where('party_id', $actor['party']->id)->where('audit_engagement_release_id', $release->id)->count())->toBe(1);
    } catch (Throwable $failure) {
        try {
            file_put_contents($directory.'/failure-snapshot.txt', $run(['snapshot']));
            $run(['run-code', 'async (page) => { await page.screenshot({path:"failure.png", fullPage:true, animations:"disabled"}); }']);
        } catch (Throwable $captureFailure) {
            file_put_contents($directory.'/failure-capture.txt', $captureFailure->getMessage());
        }

        throw $failure;
    } finally {
        try {
            $run(['close']);
        } catch (Throwable $closeFailure) {
            file_put_contents($directory.'/close-failure.txt', $closeFailure->getMessage());
        } finally {
            $server->stop();
            file_put_contents($directory.'/server.log', $server->getErrorOutput());
        }
    }
})->group('browser');
