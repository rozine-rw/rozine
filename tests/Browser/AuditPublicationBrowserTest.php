<?php

declare(strict_types=1);

use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\Support\AuditSealingFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('seals with a current authenticator and publishes after the Business co-signature in real phone browsers', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable.');
    }
    $directory = base_path('output/playwright/audit-publication');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $fixture = AuditSealingFixture::ready(findings: true);
    $password = 'Synthetic-publication-browser-42!';
    $auditor = $fixture['user'];
    $owner = $fixture['audit']['authority']['users'][0];
    $auditor->forceFill(['email' => 'seal-auditor@example.test', 'password' => $password])->save();
    $owner->forceFill(['email' => 'seal-owner@example.test', 'password' => $password])->save();
    $base = 'http://127.0.0.1:8022';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8022', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => 'rozine_test',
        'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_publication_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $run = function (string $role, array $arguments) use ($cli, $directory, $server): string {
        $process = new Process([$cli, '--session', 'rozine-publication-'.$role, ...$arguments], $directory);
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
    try {
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8022) started'))) {
            throw new RuntimeException('The isolated publication browser server did not start.');
        }
        $run('auditor', ['open', $base.'/login']);
        $run('auditor', ['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("seal-auditor@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
        }']);
        $totp = new Google2FA;
        $run('auditor', ['run-code', 'async (page) => {
            await page.locator("input[name=code]").fill('.json_encode($totp->getCurrentOtp('JBSWY3DPEHPK3PXP')).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.'/auditor').');
            await page.getByRole("link", {name:"Jobs", exact:true}).click();
            await page.waitForURL("**/auditor/jobs");
            await page.goto('.json_encode($base.'/auditor/reports/'.$fixture['report']->id).');
            await page.getByRole("button", {name:"Preview findings", exact:true}).click();
            await page.getByRole("button", {name:"Confirm with your authenticator", exact:true}).click();
            await page.getByLabel("Six-digit authenticator code", {exact:true}).waitFor();
            await page.screenshot({path:"confirm-phone.png", fullPage:true, animations:"disabled"});
        }']);
        $run('auditor', ['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", e => errors.push(e.message));
            await page.getByLabel("Six-digit authenticator code", {exact:true}).fill('.json_encode($totp->getCurrentOtp('JBSWY3DPEHPK3PXP')).');
            await page.getByRole("button", {name:"Seal & submit to Rozine", exact:true}).click();
            await page.getByRole("heading", {name:"Sealed and filed", exact:true}).waitFor();
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Sealed report overflows phone");
            await page.screenshot({path:"sealed-phone.png", fullPage:true, animations:"disabled"});
            await page.locator('.json_encode('a[href="/audit-seals/'.$fixture['report']->id.'"]').').click();
            await page.waitForURL("**/audit-seals/*");
            await page.goto('.json_encode($base.'/auditor/jobs').');
            await page.getByText("Awaiting co-signature", {exact:true}).waitFor();
            await page.screenshot({path:"awaiting-cosign-phone.png", fullPage:true, animations:"disabled"});
            await page.goto('.json_encode($base.'/auditor/reports/'.$fixture['report']->id).');
            if (errors.length) throw new Error(errors.join("\n"));
        }']);
        expect($fixture['report']->refresh()->status)->toBe('sealed');
        $run('business', ['open', $base.'/login']);
        $run('business', ['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", e => errors.push(e.message));
            await page.getByRole("textbox", {name:"Email address"}).fill("seal-owner@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.'/business/'.$fixture['audit']['business'].'/audit-reports/'.$fixture['report']->id).');
            await page.getByRole("button", {name:"Co-sign report", exact:true}).waitFor();
            await page.screenshot({path:"cosign-phone.png", fullPage:true, animations:"disabled"});
            await page.getByRole("checkbox", {name:"I have reviewed the audit findings and co-sign this report.", exact:true}).click();
            await page.locator("#cosign-note").fill("Reviewed against retained originals.");
            await page.getByRole("button", {name:"Co-sign report", exact:true}).click();
            await page.getByText("Every required signature is in and the report is published.", {exact:true}).waitFor();
            await page.reload();
            await page.getByText("Every required signature is in and the report is published.", {exact:true}).waitFor();
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Published report overflows phone");
            await page.screenshot({path:"published-phone.png", fullPage:true, animations:"disabled"});
            await page.setViewportSize({width:1280, height:900});
            await page.screenshot({path:"published-desktop.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(errors.join("\n"));
        }']);
        expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('published')
            ->and(AuditReportSignature::query()->firstOrFail()->payload['note'])->toBe('Reviewed against retained originals.');
        $this->assertDatabaseCount('audit_report_signatures', 1);
        $run('auditor', ['run-code', 'async (page) => {
            await page.reload();
            await page.getByRole("heading", {name:"Sealed and filed", exact:true}).waitFor();
            await page.screenshot({path:"auditor-published-phone.png", fullPage:true, animations:"disabled"});
            await page.goto('.json_encode($base.'/auditor/jobs').');
            if (await page.getByText("Awaiting co-signature", {exact:true}).count()) throw new Error("Published work remains active");
            const response = await page.goto('.json_encode($base.'/missing-walkthrough-page').');
            if (response?.status() !== 404) throw new Error("Missing page did not return 404");
            await page.getByRole("heading", {name:/find that page/}).waitFor();
            await page.screenshot({path:"missing-page-phone.png", fullPage:true, animations:"disabled"});
        }']);
    } catch (Throwable $failure) {
        foreach (['auditor', 'business'] as $role) {
            try {
                file_put_contents($directory.'/'.$role.'-failure.txt', $run($role, ['snapshot']));
                $run($role, ['run-code', 'async (page) => { await page.screenshot({path:"'.$role.'-failure.png", fullPage:true, animations:"disabled"}); }']);
            } catch (Throwable) {
            }
        }
        throw $failure;
    } finally {
        foreach (['auditor', 'business'] as $role) {
            try {
                $run($role, ['close']);
            } catch (Throwable) {
            }
        }
        $server->stop();
        file_put_contents($directory.'/server.log', $server->getErrorOutput());
    }
});
