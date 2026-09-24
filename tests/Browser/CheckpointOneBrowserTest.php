<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('completes the real browser identity switch return logout MFA and staff journeys', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
    }
    $directory = base_path('output/playwright/checkpoint-one');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $password = 'Synthetic-browser-fixture-42!';
    $party = Party::factory()->verified()->create();
    $participant = User::factory()->for($party)->create(['email' => 'checkpoint-participant@example.test', 'password' => $password]);
    User::factory()->for($party)->create(['email' => 'checkpoint-other-login@example.test', 'password' => $password]);
    foreach (['investor', 'business'] as $role) {
        RoleMembership::factory()->for($party)->active()->create(['role' => $role]);
    }
    $auditorParty = Party::factory()->verified()->create();
    User::factory()->for($auditorParty)->create(['email' => 'checkpoint-auditor@example.test', 'password' => $password]);
    RoleMembership::factory()->for($auditorParty)->active()->create(['role' => 'auditor']);
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $staff = User::factory()->withTwoFactor()->create([
        'email' => 'checkpoint-staff@example.test', 'password' => $password, 'two_factor_secret' => encrypt($secret),
    ]);
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Synthetic browser fixture.', (string) Str::uuid());
    $base = 'http://127.0.0.1:8013';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port=8013', '--no-reload'], base_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => 'rozine_test', 'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_checkpoint_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    ]);
    $server->setTimeout(null)->start();
    $session = 'rozine-checkpoint-test';
    $run = function (array $arguments) use ($cli, $session, $directory): string {
        $process = new Process([$cli, '--session', $session, ...$arguments], $directory);
        $process->setTimeout(60)->run();
        $output = $process->getOutput();
        if (! $process->isSuccessful() || str_contains($output, '### Error')) {
            throw new RuntimeException(trim($output."\n".$process->getErrorOutput()));
        }

        return $output;
    };
    try {
        $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Server running'));
        $run(['open', $base.'/login']);
        file_put_contents($directory.'/login-snapshot.txt', $run(['snapshot']));
        $login = static fn (string $email): string => 'await page.goto('.json_encode($base.'/login').');'
            .'await page.getByRole("textbox", {name:"Email address"}).fill('.json_encode($email).');'
            .'await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');'
            .'await page.getByRole("button", {name:"Log in", exact:true}).click();';
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            '.$login($participant->email).'
            await page.waitForURL("**/dashboard");
            await page.getByRole("button", {name:"Investor", exact:true}).click();
            await page.waitForURL("**/investor");
            await page.getByRole("button", {name:"Account access", exact:true}).click();
            await page.waitForURL("**/investor?section=access");
            await page.reload();
            if (await page.getByRole("button", {name:"Account access", exact:true}).getAttribute("aria-pressed") !== "true") throw new Error("Cold reload lost position");
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.getByRole("button", {name:"Business", exact:true}).click();
            await page.waitForURL("**/business");
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.getByRole("button", {name:"Investor", exact:true}).click();
            await page.waitForURL("**/investor?section=access");
            await page.screenshot({path:"return-position-desktop.png", fullPage:true, animations:"disabled"});
            await page.setViewportSize({width:390,height:844});
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.getByRole("button", {name:"Business", exact:true}).waitFor();
            await page.screenshot({path:"launcher-mobile.png", fullPage:true, animations:"disabled"});
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Mobile overflow");
            await page.getByRole("button", {name:"Investor", exact:true}).click();
            await page.waitForURL("**/investor?section=access");
            const otherTab = await page.context().newPage();
            await otherTab.goto('.json_encode($base.'/dashboard').');
            await otherTab.getByRole("button", {name:"Business", exact:true}).click();
            await otherTab.waitForURL("**/business");
            await page.bringToFront();
            await page.evaluate(() => window.dispatchEvent(new Event("focus")));
            await page.getByRole("heading", {name:"Access needs to be checked"}).waitFor();
            await page.screenshot({path:"stale-role-denial.png", fullPage:true, animations:"disabled"});
            await otherTab.close();
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.getByRole("button", {name:"Sign out", exact:true}).click();
            await page.waitForURL('.json_encode($base.'/').');
            await page.goto('.json_encode($base.'/investor').');
            await page.waitForURL("**/login");
            '.$login('checkpoint-other-login@example.test').'
            await page.waitForURL("**/investor");
            await page.getByRole("heading", {name:"Access needs to be checked"}).waitFor();
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.getByRole("button", {name:"Investor", exact:true}).click();
            await page.waitForURL("**/investor");
            await page.getByRole("link", {name:"Choose an app", exact:true}).click();
            await page.getByRole("button", {name:"Sign out", exact:true}).click();
            await page.waitForURL('.json_encode($base.'/').');
            '.$login('checkpoint-auditor@example.test').'
            await page.waitForURL("**/dashboard");
            if (!await page.getByRole("button", {name:"Auditor", exact:true}).isDisabled()) throw new Error("MFA gate missing");
            await page.getByRole("link", {name:"Set up two-factor authentication"}).waitFor();
            await page.screenshot({path:"auditor-mfa-gate.png", fullPage:true, animations:"disabled"});
            await page.getByRole("button", {name:"Sign out", exact:true}).click();
            await page.waitForURL('.json_encode($base.'/').');
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {roleSwitch:true, bookmark:true, coldReload:true, crossTabDenial:true, crossLoginIsolation:true, logout:true, auditorMfa:true, javascriptErrors:errors};
        }']);
        file_put_contents($directory.'/participant-result.txt', $result);
        $run(['run-code', 'async (page) => {'.$login($staff->email).'await page.waitForURL("**/two-factor-challenge");}']);
        file_put_contents($directory.'/mfa-snapshot.txt', $run(['snapshot']));
        $result = $run(['run-code', 'async (page) => {
            await page.locator("input[name=code]").fill('.json_encode($totp->getCurrentOtp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/dashboard");
            await page.getByRole("link", {name:"Open staff workspace"}).click();
            await page.waitForURL("**/admin");
            await page.getByRole("heading", {name:"Staff workspace"}).waitFor();
            await page.screenshot({path:"staff-entry.png", fullPage:true, animations:"disabled"});
            return {staffMfa:true, staffEntry:true};
        }']);
        file_put_contents($directory.'/staff-result.txt', $result);
        expect($participant->refresh()->context_revision)->toBe(4)
            ->and(DB::table('role_bookmarks')->where('user_id', $participant->id)->count())->toBe(1);
    } finally {
        try {
            $run(['close']);
        } finally {
            $server->stop();
        }
    }
})->group('browser');
