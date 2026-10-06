<?php

declare(strict_types=1);

use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\BusinessApplication;
use Database\Seeders\CheckpointTwoSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('takes the seeded draft through application submission, auditor sealing and Business publication', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    Storage::fake('local');
    $seeder = app(CheckpointTwoSeeder::class);
    $pack = $seeder->prepare();
    $case = $pack['scenarios']['draft'];
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable.');
    }
    $directory = base_path('output/playwright/c2-manual-journey');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $base = 'http://127.0.0.1:8028';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8028', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => 'rozine_test',
        'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_manual_browser', 'CACHE_STORE' => 'database',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $run = function (string $role, array $arguments) use ($cli, $directory, $server): string {
        $process = new Process([$cli, '--session', 'c2-pack-'.$role, ...$arguments], $directory);
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
    $login = static fn (string $alias): string => 'await page.getByRole("textbox", {name:"Email address"}).fill('.json_encode($alias.'@c2.rozine.invalid').');
        await page.getByLabel("Password", {exact:true}).fill('.json_encode(CheckpointTwoSeeder::PASSWORD).');
        await page.getByRole("button", {name:"Log in", exact:true}).click();';
    try {
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8028) started'))) {
            throw new RuntimeException('Manual journey browser server did not start.');
        }
        $run('business', ['open', $base.'/login']);
        $run('business', ['run-code', 'async (page) => {
            '.$login('business-draft').'
            await page.waitForURL("**/dashboard");
            await page.goto('.json_encode($base.$case['business_path']).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.getByRole("heading", {name:"Review & sign", exact:true}).waitFor();
            const sheet = page.getByRole("dialog", {name:"Raise application"});
            const unticked = sheet.getByRole("checkbox", {checked:false});
            while (await unticked.count()) await unticked.first().click();
            await sheet.getByLabel("Your full name").fill("Synthetic manual tester");
            await page.getByRole("button", {name:"Sign application", exact:true}).click();
            await page.getByText("Your application has been submitted").waitFor();
            await page.screenshot({path:"application-submitted.png", fullPage:true});
        }']);
        expect(BusinessApplication::query()->findOrFail($case['application'])->status)->toBe('submitted');
        $run('auditor', ['open', $base.'/login']);
        $run('auditor', ['run-code', 'async (page) => {
            '.$login('auditor-draft').'
            await page.waitForURL("**/two-factor-challenge");
        }']);
        $loginCode = $seeder->otp('auditor-draft');
        $run('auditor', ['run-code', 'async (page) => {
            await page.locator("input[name=code]").fill('.json_encode($loginCode).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.$case['auditor_path']).');
            await page.getByRole("button", {name:"Start the audit", exact:true}).click();
            await page.waitForURL("**/auditor/reports/*");
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.getByRole("heading", {name:"Check in on site", exact:true}).waitFor();
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.getByRole("heading", {name:"Geo-tagged site photos", exact:true}).waitFor();
            await page.getByPlaceholder("What this shows — e.g. cold room #2 at capacity").fill("Stock room");
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.locator("#auditor-observed-stock").fill("38000000");
            await page.getByRole("checkbox", {name:/Secondary paper ledgers/}).click();
            await page.getByRole("button", {name:"Review & seal", exact:true}).click();
            await page.getByRole("button", {name:"Preview findings", exact:true}).click();
            await page.getByRole("button", {name:"Confirm with your authenticator", exact:true}).click();
            await page.getByLabel("Six-digit authenticator code", {exact:true}).waitFor();
        }']);
        $deadline = microtime(true) + 35;
        while (($sealCode = $seeder->otp('auditor-draft')) === $loginCode && microtime(true) < $deadline) {
            usleep(100_000);
        }
        expect($sealCode)->not->toBe($loginCode);
        $run('auditor', ['run-code', 'async (page) => {
            await page.getByLabel("Six-digit authenticator code", {exact:true}).fill('.json_encode($sealCode).');
            await page.getByRole("button", {name:"Seal & submit to Rozine", exact:true}).click();
            await page.getByRole("heading", {name:"Sealed and filed", exact:true}).waitFor();
            await page.screenshot({path:"audit-sealed-phone.png", fullPage:true});
        }']);
        $report = AuditReport::query()->where('application_id', $case['application'])->firstOrFail();
        expect($report->status)->toBe('sealed');
        $run('business', ['run-code', 'async (page) => {
            await page.goto('.json_encode($base.'/business/'.$case['business'].'/audit-reports/'.$report->id).');
            await page.setViewportSize({width:390, height:844});
            await page.getByRole("checkbox", {name:"I have reviewed the audit findings and co-sign this report.", exact:true}).click();
            await page.getByRole("button", {name:"Co-sign report", exact:true}).click();
            await page.getByText("Every required signature is in and the report is published.", {exact:true}).waitFor();
            await page.reload();
            await page.getByText("Every required signature is in and the report is published.", {exact:true}).waitFor();
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Published report overflows phone");
            await page.screenshot({path:"report-published-phone.png", fullPage:true});
        }']);
        expect(AuditReportPublication::query()->where('audit_report_id', $report->id)->firstOrFail()->status)->toBe('published');
        expect($seeder->prepare()['scenarios']['draft']['report'])->toBe($report->id);
        expect(AuditReportPublication::query()->where('audit_report_id', $report->id)->firstOrFail()->status)->toBe('published');
    } catch (Throwable $failure) {
        foreach (['business', 'auditor'] as $role) {
            try {
                file_put_contents($directory.'/'.$role.'-failure.txt', $run($role, ['snapshot']));
            } catch (Throwable) {
            }
        }
        throw $failure;
    } finally {
        foreach (['business', 'auditor'] as $role) {
            try {
                $run($role, ['close']);
            } catch (Throwable) {
            }
        }
        $server->stop();
        file_put_contents($directory.'/server.log', $server->getErrorOutput());
    }
});
