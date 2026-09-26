<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\Support\AuditSealingFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('submits retained proof, escalates through CPA review and displays staff publication in real phone browsers', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable.');
    }
    $directory = base_path('output/playwright/monthly-audit-review');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $fixture = AuditSealingFixture::ready(kind: 'monthly', findings: true);
    AuditSealingFixture::seal($fixture);
    $proofContent = "%PDF-1.4\nSynthetic monthly proof\n%%EOF";
    $proofPath = $directory.'/synthetic-proof.pdf';
    file_put_contents($proofPath, $proofContent);
    $password = 'Synthetic-publication-browser-42!';
    $auditor = $fixture['user'];
    $owner = $fixture['audit']['authority']['users'][0];
    $auditor->forceFill(['email' => 'seal-auditor@example.test', 'password' => $password])->save();
    $owner->forceFill(['email' => 'seal-owner@example.test', 'password' => $password])->save();
    $base = 'http://127.0.0.1:8027';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8027', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => 'rozine_test',
        'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_monthly_review_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $run = function (string $role, array $arguments) use ($cli, $directory, $server): string {
        $process = new Process([$cli, '--session', 'rozine-monthly-review-'.$role, ...$arguments], $directory);
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
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8027) started'))) {
            throw new RuntimeException('The isolated monthly browser server did not start.');
        }
        $run('business', ['open', $base.'/login']);
        $run('business', ['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("seal-owner@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.'/business/'.$fixture['audit']['business'].'/audit-reports/'.$fixture['report']->id).');
            await page.getByText("24-hour review window", {exact:true}).waitFor();
            await page.getByRole("button", {name:"Submit a dispute", exact:true}).click();
            await page.locator("#dispute-supporting").fill("The delivery receipt belongs to this reporting period.");
            await page.locator("input[type=file]").setInputFiles('.json_encode($proofPath).');
            await page.getByRole("button", {name:"Submit dispute", exact:true}).click();
            await page.getByText("Dispute under review", {exact:true}).waitFor();
            await page.reload();
            await page.getByText("Dispute under review", {exact:true}).waitFor();
            const original = await page.request.get(await page.getByRole("link", {name:/^Download audit-proof-/}).evaluate(link => link.href));
            if (!original.ok() || await original.text() !== '.json_encode($proofContent).') throw new Error("Protected proof differs from uploaded original");
            if (await page.getByRole("button", {name:"Co-sign report", exact:true}).count()) throw new Error("Dispute retained a co-sign action");
            await page.screenshot({path:"disputed-phone.png", fullPage:true, animations:"disabled"});
        }']);
        expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('disputed');
        $run('auditor', ['open', $base.'/login']);
        $run('auditor', ['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("seal-auditor@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
        }']);
        $run('auditor', ['run-code', 'async (page) => {
            await page.locator("input[name=code]").fill('.json_encode((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.'/auditor/reports/'.$fixture['report']->id).');
            await page.getByText("Business dispute", {exact:true}).waitFor();
            await page.getByRole("button", {name:"Uphold findings", exact:true}).click();
            await page.getByLabel("Your reason (required)", {exact:true}).fill("The retained receipt postdates the cutoff, so these findings stand.");
            await page.getByRole("button", {name:"Send to Rozine staff", exact:true}).click();
            await page.getByText("With Rozine staff", {exact:true}).waitFor();
            await page.getByText("Your reason for upholding", {exact:true}).waitFor();
            if (await page.getByText("Rozine staff note", {exact:true}).count()) throw new Error("CPA reason was attributed to staff");
            if (await page.getByRole("button", {name:"Start a linked amendment", exact:true}).count()) throw new Error("Escalated report allowed an unauthorized amendment");
            await page.screenshot({path:"escalated-phone.png", fullPage:true, animations:"disabled"});
        }']);
        $publication = AuditReportPublication::query()->firstOrFail();
        expect($publication->status)->toBe('escalated')->and($publication->published_at)->toBeNull();
        $resolved = app(AuditReportPublicationStore::class)->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id,
            $fixture['report']->id, 'audit.dispute.resolve', ['request_id' => (string) Str::uuid(), 'expected_revision' => $publication->revision,
                'report_revision' => $publication->report_revision, 'digest' => $publication->digest,
                'decision' => 'uphold', 'reason' => 'The retained originals confirm the reporting cutoff.']);
        expect($resolved['code'])->toBe('REPORT_PUBLISHED')->and(AuditReportSignature::query()->count())->toBe(0);
        $run('business', ['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", e => errors.push(e.message));
            await page.reload();
            await page.getByText("Published by Rozine staff", {exact:true}).waitFor();
            await page.getByRole("heading", {name:"Audit report, published by Rozine staff", exact:true}).waitFor();
            await page.getByText("Rozine staff note", {exact:true}).waitFor();
            await page.getByText("Rozine staff reviewed your dispute, kept the findings and published the report. No signature was recorded for it.", {exact:true}).waitFor();
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Monthly report overflows phone");
            await page.screenshot({path:"staff-published-phone.png", fullPage:true, animations:"disabled"});
            await page.setViewportSize({width:1280, height:900});
            await page.screenshot({path:"staff-published-desktop.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(errors.join("\n"));
        }']);
        $run('auditor', ['run-code', 'async (page) => {
            await page.reload();
            await page.getByText("Not co-signed", {exact:true}).waitFor();
            await page.getByText("Rozine staff note", {exact:true}).waitFor();
            await page.screenshot({path:"auditor-staff-published-phone.png", fullPage:true, animations:"disabled"});
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
