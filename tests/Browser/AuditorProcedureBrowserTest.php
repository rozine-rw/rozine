<?php

declare(strict_types=1);

use App\Application\Auditor\RecordIsolatedAuditSourceFacts;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\Support\AuditSourceFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('starts and completes the factual procedure, saves its note and withdraws stale readiness on a phone', function (string $kind): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
    }
    $directory = base_path('output/playwright/auditor-procedure-'.$kind);
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $fixture = BusinessQuoteFixture::ready(auditKind: $kind);
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $facts = AuditSourceFactsFixture::facts();
    $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
    $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];
    AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
    $user = $fixture['audit']['partners'][0]['user'];
    $password = 'Synthetic-browser-fixture-42!';
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $user->forceFill(['email' => 'procedure-auditor@example.test', 'password' => $password, 'two_factor_secret' => encrypt($secret)])->save();
    $base = 'http://127.0.0.1:8021';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8021', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => 'rozine_test', 'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_procedure_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $run = function (array $arguments) use ($cli, $kind, $directory, $server): string {
        $process = new Process([$cli, '--session', 'rozine-procedure-'.$kind, ...$arguments], $directory);
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
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8021) started'))) {
            throw new RuntimeException('The isolated procedure browser server did not start.');
        }
        $run(['open', $base.'/login']);
        $run(['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("procedure-auditor@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
        }']);
        $run(['run-code', 'async (page) => {
            await page.locator("input[name=code]").fill('.json_encode($totp->getCurrentOtp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.goto('.json_encode($base.'/auditor/jobs/'.$fixture['assignment']->id).');
            await page.getByRole("button", {name:"Start the audit", exact:true}).click();
            await page.waitForURL("**/auditor/reports/*");
        }']);
        $report = AuditReport::query()->where('assignment_id', $fixture['assignment']->id)->firstOrFail();
        expect($report->revision)->toBe(1);
        $flashSteps = 'await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.getByRole("heading", {name:"Check in on site", exact:true}).waitFor();
            await page.getByText(/^Synthetic test evidence \(isolated\)/).first().waitFor();
            await page.getByRole("button", {name:"Continue", exact:true}).click();';
        $monthlySteps = 'await page.getByRole("heading", {name:"Read the month", exact:true}).waitFor();
            await page.getByRole("button", {name:"Start the count", exact:true}).click();
            await page.getByRole("heading", {name:"Count & cash", exact:true}).waitFor();
            await page.getByRole("checkbox", {name:/^bank/}).click();
            await page.getByRole("checkbox", {name:/^momo/}).click();
            await page.getByRole("checkbox", {name:/^photo/}).click();
            await page.locator("#auditor-cash").fill("108000001");
            await page.locator("#auditor-stock").fill("189");
            await page.getByRole("radio", {name:"Active", exact:true}).click();
            await page.getByRole("button", {name:"Continue to photos", exact:true}).click();';
        file_put_contents($directory.'/ledger.csv', "date,amount\n2026-08-01,38000000\n");
        $ledger = $kind === 'flash' ? 'await page.getByRole("heading", {name:"Inventory & ledger sign-off", exact:true}).waitFor();
            await page.waitForLoadState("networkidle");
            const uploaded = page.waitForResponse(response => response.url().endsWith("/steps") && response.request().method() === "POST");
            await page.locator("input[type=file]").setInputFiles('.json_encode($directory.'/ledger.csv').');
            if (!(await uploaded).ok()) throw new Error("Ledger upload refused");
            await page.getByText(/^ledger-[a-z0-9]+\.csv$/).waitFor();
            const [original] = await Promise.all([
                page.waitForEvent("download"),
                page.getByRole("link", {name:"Download original", exact:true}).click(),
            ]);
            await original.saveAs("ledger-original.csv");
            await page.waitForLoadState("networkidle");
            await page.locator("#auditor-observed-stock").fill("37000000");
            await page.getByRole("checkbox", {name:/Secondary paper ledgers/}).click();
            await page.getByRole("button", {name:"Review & seal", exact:true}).click();' : '';
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            const requests = []; page.on("request", request => {
                if (request.url().includes("/auditor/")) requests.push(request.method() + " " + request.url().split("?")[0]);
            });
            try {
            await page.setViewportSize({width:390, height:844});
            '.($kind === 'flash' ? $flashSteps : $monthlySteps).'
            await page.getByRole("heading", {name:"Geo-tagged site photos", exact:true}).waitFor();
            await page.getByText(/^Synthetic test evidence \(isolated\)/).first().waitFor();
            await page.getByPlaceholder("What this shows — e.g. cold room #2 at capacity").fill("Rear store inventory");
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Photo page overflow");
            await page.screenshot({path:"photos-phone.png", fullPage:true, animations:"disabled"});
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            '.$ledger.'
            await page.locator("#auditor-seal-note").waitFor();
            await page.waitForLoadState("networkidle");
            await page.locator("#auditor-seal-note").fill("Observed stock differs from the declared inventory.");
            const saved = page.waitForResponse(response => response.url().endsWith("/steps") && response.request().method() === "POST");
            await page.getByRole("button", {name:"Save note", exact:true}).click();
            if (!(await saved).ok()) throw new Error("Note save refused");
            await page.waitForFunction(() => !document.querySelector("#auditor-seal-note-unsaved"));
            await page.reload();
            await page.locator("#auditor-seal-note").waitFor();
            if (await page.locator("#auditor-seal-note").inputValue() !== "Observed stock differs from the declared inventory.") throw new Error("Note did not persist");
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Seal preview overflow");
            await page.screenshot({path:"preview-phone.png", fullPage:true, animations:"disabled"});
            await page.setViewportSize({width:1280, height:900});
            await page.screenshot({path:"preview-desktop.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {kind:'.json_encode($kind).', saved:true, synthetic:true};
            } catch (failure) { throw new Error([String(failure), ...errors, ...requests].join("\n")); }
        }']);
        file_put_contents($directory.'/result.txt', $result);
        if ($kind === 'flash') {
            expect(file_get_contents($directory.'/ledger-original.csv'))->toBe(file_get_contents($directory.'/ledger.csv'));
        }
        expect($report->refresh()->step)->toBe('seal')->and($report->draft['note'])->toBe('Observed stock differs from the declared inventory.');
        $versions = AuditReportVersion::query()->where('audit_report_id', $report->id)->count();
        app(RecordIsolatedAuditSourceFacts::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id,
            $fixture['assignment']->refresh()->revision, 1, null, 'synthetic:browser-withdrawal', 'Withdraw synthetic source facts.', (string) Str::uuid());
        $run(['run-code', 'async (page) => {
            await page.reload();
            await page.locator("#auditor-seal-note").waitFor();
            await page.getByText('.json_encode($kind === 'flash'
                ? 'A verified capture package is required before this step can continue.'
                : 'The Business declaration needed for reconciliation is unavailable.').', {exact:true}).waitFor();
            await page.screenshot({path:"withdrawn-desktop.png", fullPage:true, animations:"disabled"});
        }']);
        expect(AuditReportVersion::query()->where('audit_report_id', $report->id)->count())->toBe($versions);
        if ($kind === 'routine') {
            $run(['run-code', 'async (page) => {
                await page.setViewportSize({width:390, height:844});
                for (const decision of [
                    {button:"Request changes", reason:"Original documents missing", heading:"Changes requested", file:"changes-requested"},
                    {button:"Reject filing", reason:"Evidence cannot be verified", heading:"Filing rejected", file:"rejected"},
                ]) {
                    const parent = page.url();
                    await page.getByRole("button", {name:decision.button, exact:true}).click();
                    const sheet = page.getByRole("dialog", {name:decision.button, exact:true});
                    await sheet.getByRole("radio", {name:decision.reason, exact:true}).click();
                    await sheet.locator("#auditor-reason").fill("The retained original cannot support this monthly filing.");
                    await sheet.getByRole("button", {name:decision.button, exact:true}).click();
                    await page.getByRole("alertdialog").getByRole("button", {name:"Done", exact:true}).click();
                    await page.getByRole("heading", {name:decision.heading, exact:true}).waitFor();
                    if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Returned page overflow");
                    await page.screenshot({path:decision.file + "-phone.png", fullPage:true, animations:"disabled"});
                    await page.getByRole("button", {name:"Start a linked amendment", exact:true}).click();
                    await page.waitForFunction(previous => location.href !== previous, parent);
                    await page.getByRole("heading", {name:"Read the month", exact:true}).waitFor();
                    await page.waitForLoadState("networkidle");
                    await page.getByRole("note").filter({hasText:"That report remains unchanged."}).waitFor();
                    await page.screenshot({path:decision.file + "-amendment-phone.png", fullPage:true, animations:"disabled"});
                }
            }']);
            $child = AuditReport::query()->where('amends_id', $report->id)->firstOrFail();
            $next = AuditReport::query()->where('amends_id', $child->id)->firstOrFail();
            expect($report->refresh()->status)->toBe('changes_requested')->and($child->status)->toBe('rejected')
                ->and($next->status)->toBe('draft')->and($next->step)->toBe('statements')->and($next->revision)->toBe(1);
        }
    } catch (Throwable $failure) {
        try {
            file_put_contents($directory.'/failure-snapshot.txt', $run(['snapshot']));
            file_put_contents($directory.'/failure-console.txt', $run(['console', 'error']));
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
})->with(['flash', 'routine'])->group('browser');
