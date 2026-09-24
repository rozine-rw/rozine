<?php

declare(strict_types=1);

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\SaveBusinessApplication;
use App\Models\AuditAssignment;
use App\Models\RoleMembership;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('runs the real-record Auditor Jobs accept decline conflict and receipt journey', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
    }
    $directory = base_path('output/playwright/auditor-jobs');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    /*
     * One accredited, MFA-enrolled Auditor holding three Flash offers from three Businesses: one to
     * accept and then declare a conflict on (with a raise draft, so its file has figures), one to
     * decline and one whose offer window closes while the page is open.
     */
    $password = 'Synthetic-browser-fixture-42!';
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $first = Fixture::make(1);
    $partner = $first['partners'][0];
    $partner['user']->forceFill(['email' => 'jobs-auditor@example.test', 'password' => $password, 'two_factor_secret' => encrypt($secret)])->save();
    $owner = $first['authority']['users'][0];
    $created = app(CreateBusinessApplication::class)->handle($owner->id, 1, $first['business'], 0, (string) Str::uuid());
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $first['business'], $created['data']['application']['id'], 1,
        BusinessApplicationFixture::fields(), 'raise', (string) Str::uuid());
    $accepted = Fixture::request($first);
    $another = function () use ($partner): AuditAssignment {
        $other = Fixture::make(0);
        Fixture::independence($other['staff'], $other['business'], $partner['party']->id);

        return Fixture::request($other);
    };
    $declined = $another();
    $expiring = $another();
    $reference = '…'.substr($accepted->id, -6);

    $base = 'http://127.0.0.1:8014';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port=8014', '--no-reload'], base_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => 'rozine_test', 'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_auditor_jobs_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    ]);
    $server->setTimeout(null)->start();
    $session = 'rozine-auditor-jobs-test';
    $run = function (array $arguments) use ($cli, $session, $directory): string {
        $process = new Process([$cli, '--session', $session, ...$arguments], $directory);
        $process->setTimeout(90)->run();
        $output = $process->getOutput();
        if (! $process->isSuccessful() || str_contains($output, '### Error')) {
            throw new RuntimeException(trim($output."\n".$process->getErrorOutput()));
        }

        return $output;
    };
    $card = static fn (AuditAssignment $assignment): string => 'page.locator("article", {has: page.locator('
        .json_encode('a[href="/auditor/jobs/'.$assignment->id.'"]').')})';
    $noOverflow = 'if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Mobile overflow");';
    try {
        $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Server running'));
        $run(['open', $base.'/login']);
        $run(['run-code', 'async (page) => {
            await page.getByRole("textbox", {name:"Email address"}).fill("jobs-auditor@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
        }']);
        file_put_contents($directory.'/mfa-snapshot.txt', $run(['snapshot']));

        /* Signs in with the authenticator code, opens the Auditor app and reads the live offers. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await page.locator("input[name=code]").fill('.json_encode($totp->getCurrentOtp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.getByRole("button", {name:"Auditor", exact:true}).click();
            await page.waitForURL("**/auditor");
            await page.goto('.json_encode($base.'/auditor/jobs').');
            await page.getByRole("heading", {name:"Flash Audits"}).waitFor();
            const offer = '.$card($accepted).';
            await offer.getByRole("button", {name:/^Accept · due /}).waitFor();
            const countdown = await offer.getByRole("timer", {name:"Time left to accept this offer"}).textContent();
            if (!/left$/.test(countdown ?? "")) throw new Error("Offer countdown missing: " + countdown);
            await '.$card($declined).'.waitFor();
            await '.$card($expiring).'.waitFor();
            await page.screenshot({path:"jobs-desktop.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {mfaLogin:true, offers:3, countdown};
        }']);
        file_put_contents($directory.'/jobs-result.txt', $result);

        /* The third offer's window closes on the server while the page still shows it open. */
        $past = now('UTC')->subMinute()->format('Y-m-d\TH:i:s\Z');
        $expiring->refresh()->forceFill(['state' => [...$expiring->state, 'accept_by' => $past], 'accept_by' => $past])->save();

        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await '.$card($expiring).'.getByRole("button", {name:/^Accept · due /}).click();
            await page.getByText(/This offer closed before your acceptance reached Rozine/).waitFor();
            await '.$card($expiring).'.waitFor({state:"detached"});
            await page.screenshot({path:"offer-closed-desktop.png", fullPage:true, animations:"disabled"});

            const declining = '.$card($declined).';
            await declining.getByRole("button", {name:"Decline", exact:true}).click();
            const sheet = page.getByRole("dialog", {name:/^Decline /});
            const options = await sheet.getByRole("radio").allTextContents();
            await sheet.getByRole("radio", {name:"At capacity", exact:true}).click();
            await sheet.getByRole("button", {name:"Decline job", exact:true}).click();
            const outcome = page.getByRole("alertdialog", {name:"Job declined"});
            await outcome.waitFor();
            await page.screenshot({path:"declined-desktop.png", fullPage:true, animations:"disabled"});
            await outcome.getByRole("button", {name:"Done", exact:true}).click();
            await page.waitForURL("**/auditor/jobs");
            await '.$card($declined).'.waitFor({state:"detached"});

            await '.$card($accepted).'.getByRole("button", {name:/^Accept · due /}).click();
            await page.waitForURL('.json_encode('**/auditor/jobs/'.$accepted->id).');
            const file = page.getByRole("dialog", {name:/business file$/});
            await file.getByText("Business file", {exact:true}).waitFor();
            await file.getByRole("timer", {name:"Time left on this job"}).waitFor();
            if (await file.getByRole("link", {name:"Continue the audit"}).count() !== 0) throw new Error("Procedure advertised before S-D");
            if (await file.getByRole("button", {name:/^Accept/}).count() !== 0) throw new Error("Accepted file still offers Accept");
            await page.screenshot({path:"file-accepted-desktop.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {offerClosed:true, declineOptions:options, declined:true, accepted:true};
        }']);
        file_put_contents($directory.'/commands-result.txt', $result);

        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            const file = page.getByRole("dialog", {name:/business file$/});
            await file.getByRole("button", {name:"Declare a conflict", exact:true}).click();
            const sheet = page.getByRole("dialog", {name:/^Declare an interest in /});
            await sheet.getByRole("radio", {name:"Close family or business tie", exact:true}).click();
            await sheet.getByLabel("Factual explanation (required)").fill("My cousin keeps the books for this business.");
            await sheet.getByRole("button", {name:"Declare interest", exact:true}).click();
            await page.waitForURL('.json_encode('**/auditor/jobs/'.$accepted->id.'/conflict').');
            await page.getByRole("heading", {name:"Your declared conflicts"}).waitFor();
            const receipt = page.getByRole("article", {name:'.json_encode('Business on record · Ref. '.$reference).'});
            await receipt.getByRole("heading", {name:"Conflict recorded"}).waitFor();
            await receipt.getByText("Close family or business tie", {exact:true}).waitFor();
            await receipt.getByText("My cousin keeps the books for this business.", {exact:true}).waitFor();
            await page.screenshot({path:"conflict-receipt-desktop.png", fullPage:true, animations:"disabled"});

            const denied = await page.goto('.json_encode($base.'/auditor/jobs/'.$accepted->id).');
            if (denied?.status() !== 404) throw new Error("Withdrawn file still readable: " + denied?.status());

            await page.goto('.json_encode($base.'/auditor/conflicts').');
            await page.getByRole("article", {name:'.json_encode('Business on record · Ref. '.$reference).'}).waitFor();
            await page.screenshot({path:"conflicts-desktop.png", fullPage:true, animations:"disabled"});

            await page.setViewportSize({width:390, height:844});
            await page.goto('.json_encode($base.'/auditor/jobs').');
            await page.getByRole("heading", {name:"Flash Audits"}).waitFor();
            '.$noOverflow.'
            await page.screenshot({path:"jobs-mobile.png", fullPage:true, animations:"disabled"});
            await page.goto('.json_encode($base.'/auditor/jobs/'.$accepted->id.'/conflict').');
            await page.getByRole("heading", {name:"Conflict recorded"}).waitFor();
            '.$noOverflow.'
            await page.screenshot({path:"conflict-receipt-mobile.png", fullPage:true, animations:"disabled"});
            await page.goto('.json_encode($base.'/auditor/jobs').');
            await page.getByRole("heading", {name:"Flash Audits"}).waitFor();
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {conflictReceipt:true, fileDenied:true, register:true, mobile:true};
        }']);
        file_put_contents($directory.'/conflict-result.txt', $result);

        /*
         * With Jobs still open, an operator withdraws the Auditor membership. Regaining focus makes
         * the page ask for its facts again, and it lands on the access-denied page.
         */
        RoleMembership::query()->where('party_id', $partner['party']->id)->where('role', 'auditor')->update(['status' => 'revoked']);
        $result = $run(['run-code', 'async (page) => {
            await page.evaluate(() => window.dispatchEvent(new Event("focus")));
            await page.getByRole("heading", {name:"Access needs to be checked"}).waitFor();
            if (!page.url().endsWith("/auditor/jobs")) throw new Error("Denied page moved: " + page.url());
            await page.screenshot({path:"access-withdrawn-mobile.png", fullPage:true, animations:"disabled"});
            return {withdrawnOnFocus:true};
        }']);
        file_put_contents($directory.'/withdrawn-result.txt', $result);

        expect($accepted->refresh()->party_id)->not->toBe($partner['party']->id)
            ->and($declined->refresh()->party_id)->not->toBe($partner['party']->id)
            ->and(DB::table('audit_assignment_versions')->where('assignment_id', $expiring->id)->count())->toBe(1);
    } finally {
        try {
            $run(['close']);
        } finally {
            $server->stop();
        }
    }
})->group('browser');
