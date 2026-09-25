<?php

declare(strict_types=1);

use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\Support\BusinessQuoteFixture as QuoteFixture;
use Tests\Support\ConsentFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('runs the real-record Business Apply resume save evaluate sign and submit journey', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('rozine_test');
    $cli = getenv('PLAYWRIGHT_CLI');
    if (! is_string($cli) || ! is_executable($cli)) {
        throw new RuntimeException('Set PLAYWRIGHT_CLI to the installed playwright-cli executable or wrapper.');
    }
    $directory = base_path('output/playwright/business-apply');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    /*
     * Three real records: a sole trader's saved Raise draft with 36 verified months, credit facts
     * and published legal text; a two-signatory organisation ready to sign; and a ready review
     * whose evidence is withdrawn while it is open.
     */
    $password = 'Synthetic-browser-fixture-42!';
    $solo = QuoteFixture::make();
    ConsentFixture::record($solo['audit']['staff']);
    /* A second business the sole trader represents, with no application yet: started from the launcher. */
    $related = AuthorityFixture::relatedOrganization($solo['audit']['authority']);
    $relatedBusiness = AuthorityFixture::configure($related, 1)['data']['business']['id'];
    $pair = QuoteFixture::ready(2);
    $stale = QuoteFixture::ready();
    $login = static function (User $user, string $email) use ($password): string {
        $user->forceFill(['email' => $email, 'password' => $password])->save();

        return $email;
    };
    $soloEmail = $login($solo['audit']['authority']['users'][0], 'apply-solo@example.test');
    $firstEmail = $login($pair['audit']['authority']['users'][0], 'apply-first-signer@example.test');
    $secondEmail = $login($pair['audit']['authority']['users'][1], 'apply-second-signer@example.test');
    $staleEmail = $login($stale['audit']['authority']['users'][0], 'apply-stale@example.test');
    $path = static fn (array $fixture): string => '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;

    $base = 'http://127.0.0.1:8015';
    $connection = config('database.connections.pgsql');
    $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8015', base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], public_path(), [
        'APP_ENV' => 'testing', 'APP_NAME' => 'Rozine', 'APP_URL' => $base, 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
        'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => 'rozine_test', 'DB_USERNAME' => (string) $connection['username'], 'DB_PASSWORD' => (string) $connection['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'rozine_business_apply_browser', 'CACHE_STORE' => 'array',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'PHP_CLI_SERVER_WORKERS' => false,
    ]);
    $server->setTimeout(null)->start();
    $sessions = ['apply-a', 'apply-b'];
    $run = function (array $arguments, int $session = 0) use ($cli, $sessions, $directory, $server): string {
        $process = new Process([$cli, '--session', $sessions[$session], ...$arguments], $directory);
        $process->setTimeout(120)->start();
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
    $signIn = static fn (string $email): string => 'await page.context().clearCookies();'
        .'await page.goto('.json_encode($base.'/login').');'
        .'await page.getByRole("textbox", {name:"Email address"}).fill('.json_encode($email).');'
        .'await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');'
        .'await page.getByRole("button", {name:"Log in", exact:true}).click();'
        .'await page.waitForURL("**/dashboard");'
        .'await page.getByRole("button", {name:"Business", exact:true}).click();'
        .'await page.waitForURL("**/business");';
    $noOverflow = 'if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error("Mobile overflow");';
    $accept = 'const review = page.getByRole("dialog", {name:"Raise application"});
        await review.getByRole("checkbox", {name:/^I accept this offer/}).click();
        const unticked = review.getByRole("checkbox", {checked:false});
        while (await unticked.count() > 0) await unticked.first().click();
        await review.getByLabel("Your full name").fill("Verified synthetic signer");';
    try {
        if (! $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server (http://127.0.0.1:8015) started'))) {
            throw new RuntimeException('The isolated Business Apply browser server did not start.');
        }
        $run(['open', $base.'/login']);
        $run(['open', $base.'/login'], 1);

        /*
         * From the launcher: the Business landing lists both businesses. Apply for a raise starts the
         * second one's application and follows next into Apply; the first resumes its saved draft,
         * whose Back link opens the earlier Business view, and Continue saves on to Raise.
         */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await page.setViewportSize({width:1113, height:750});
            '.$signIn($soloEmail).'
            const list = page.getByRole("region", {name:"Raise applications"});
            await list.getByRole("link", {name:"Continue your application"}).waitFor();
            await list.getByText("Saved at Your raise").waitFor();
            await page.screenshot({path:"ua-live-rolehome-desktop.png", fullPage:true, animations:"disabled"});
            const created = page.waitForResponse(response => response.url().endsWith('.json_encode('/business/'.$relatedBusiness.'/applications').') && response.request().method() === "POST");
            await list.getByRole("button", {name:"Apply for a raise", exact:true}).click();
            const creation = await (await created).json();
            if (creation.code !== "APPLICATION_CREATED") throw new Error("Create: " + creation.code);
            await page.waitForURL("**" + creation.data.next.url);
            const started = page.getByRole("dialog", {name:"Raise application"});
            await started.getByRole("heading", {name:"Business & finances"}).waitFor();
            await page.goto('.json_encode($base.'/business').');
            if (await list.getByRole("button", {name:"Apply for a raise"}).count() !== 0) throw new Error("The started business still offers a new raise");
            if (await list.getByRole("link", {name:"Continue your application"}).count() !== 2) throw new Error("Both drafts should resume");
            await list.getByText("Saved at Your raise").waitFor();
            await list.getByRole("listitem").filter({hasText:"Saved at Your raise"}).getByRole("link", {name:"Continue your application"}).click();
            await page.waitForURL('.json_encode('**'.$path($solo)).');
            const sheet = page.getByRole("dialog", {name:"Raise application"});
            await sheet.getByRole("heading", {name:"Your raise"}).waitFor();
            await page.getByRole("link", {name:"Back", exact:true}).click();
            await page.waitForURL("**?view_step=business");
            await sheet.getByRole("heading", {name:"Business & finances"}).waitFor();
            await sheet.getByText(/ · 36 months$/).waitFor();
            if (await sheet.getByText(/^RDB /).count() !== 0) throw new Error("Sole trader shows a company code");
            await page.screenshot({path:"ua-live-business-desktop.png", animations:"disabled"});
            const saved = page.waitForRequest(request => request.url().endsWith("/save") && request.method() === "POST");
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            const body = (await saved).postDataJSON();
            if (body.step !== "raise") throw new Error("Business Continue did not ask for raise: " + JSON.stringify(body));
            await page.waitForURL('.json_encode('**'.$path($solo)).');
            await sheet.getByRole("heading", {name:"Your raise"}).waitFor();
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {launcher:true, created:creation.code, resumed:true, continueStep:body.step};
        }']);
        file_put_contents($directory.'/resume-result.txt', $result);

        /* The saved request is evaluated to the server quote; a cold reload reads it back. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            const sheet = page.getByRole("dialog", {name:"Raise application"});
            await sheet.getByText("RWF 10,800,000").first().waitFor();
            await page.screenshot({path:"ua-live-raise-quote-desktop.png", animations:"disabled"});
            await page.reload();
            await sheet.getByText("RWF 10,800,000").first().waitFor();

            const autosave = page.waitForRequest(request => request.url().endsWith("/save") && request.method() === "POST");
            const evaluated = page.waitForResponse(response => response.url().endsWith("/evaluate") && response.request().method() === "POST");
            await sheet.getByRole("button", {name:"4", exact:true}).click();
            const saveBody = (await autosave).postDataJSON();
            if ("step" in saveBody) throw new Error("Autosave restated a pointer: " + JSON.stringify(saveBody));
            const refusal = await (await evaluated).json();
            if (refusal.code !== "APPLICATION_EVALUATED" || refusal.data.quote.status !== "refused") throw new Error("Unexpected evaluation: " + JSON.stringify(refusal.data.quote));
            await sheet.getByText(refusal.data.quote.message).waitFor();
            await page.screenshot({path:"ua-live-raise-refused-desktop.png", animations:"disabled"});

            const reevaluated = page.waitForResponse(response => response.url().endsWith("/evaluate") && response.request().method() === "POST");
            await sheet.getByRole("button", {name:"6", exact:true}).click();
            const evaluation = await (await reevaluated).json();
            if (evaluation.data.quote.status !== "ready" || evaluation.data.quote.term_months !== 6) throw new Error("Unexpected evaluation: " + JSON.stringify(evaluation.data.quote));
            await sheet.getByText("RWF 10,800,000").first().waitFor();

            const advance = page.waitForRequest(request => request.url().endsWith("/save") && request.method() === "POST");
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            if ((await advance).postDataJSON().step !== "review") throw new Error("Raise Continue did not ask for review");
            await sheet.getByRole("heading", {name:"Review & sign"}).waitFor();
            await page.screenshot({path:"ua-live-review-desktop.png", animations:"disabled"});
            '.$accept.'
            await page.getByRole("button", {name:"Sign application", exact:true}).click();
            await page.getByText("Your application has been submitted").waitFor();
            await page.screenshot({path:"ua-live-submitted-desktop.png", animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {quoted:true, reloaded:true, autosaveWithoutStep:true, refused:refusal.data.quote.code, reevaluated:evaluation.data.quote.quote_id, submitted:true};
        }']);
        file_put_contents($directory.'/solo-result.txt', $result);

        /* Two required signatories: the first signature waits for the second, who completes it on a phone. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            '.$signIn($firstEmail).'
            await page.goto('.json_encode($base.$path($pair)).');
            '.$accept.'
            const signed = page.waitForResponse(response => response.url().endsWith("/submit"));
            await page.getByRole("button", {name:"Sign application", exact:true}).click();
            const receipt = await (await signed).json();
            if (receipt.code !== "APPLICATION_SIGNATURE_RECORDED") throw new Error("First signature: " + receipt.code);
            await review.getByText("Waiting for Verified person 1 to sign.", {exact:false}).waitFor();
            if (await page.getByRole("button", {name:"Sign application"}).count() !== 0) throw new Error("Signed person may sign again");
            await review.getByText("Waiting for Verified person 1 to sign.", {exact:false}).scrollIntoViewIfNeeded();
            await page.screenshot({path:"ua-live-signature-recorded-desktop.png", animations:"disabled"});
            await page.reload();
            await review.getByText("Waiting for Verified person 1 to sign.", {exact:false}).waitFor();
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {firstSignature:receipt.code, survivesReload:true};
        }']);
        file_put_contents($directory.'/first-signature-result.txt', $result);

        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            await page.setViewportSize({width:390, height:844});
            '.$signIn($secondEmail).'
            await page.getByRole("region", {name:"Raise applications"}).getByRole("link", {name:"Continue your application"}).waitFor();
            '.$noOverflow.'
            await page.screenshot({path:"ua-live-rolehome-phone.png", fullPage:true, animations:"disabled"});
            await page.getByRole("region", {name:"Raise applications"}).getByRole("link", {name:"Continue your application"}).click();
            await page.waitForURL('.json_encode('**'.$path($pair)).');
            '.$accept.'
            '.$noOverflow.'
            await page.screenshot({path:"ua-live-review-phone.png", fullPage:true, animations:"disabled"});
            await page.getByRole("button", {name:"Sign application", exact:true}).click();
            await page.getByText("Your application has been submitted").waitFor();
            '.$noOverflow.'
            await page.screenshot({path:"ua-live-submitted-phone.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {secondSignature:"APPLICATION_SUBMITTED", mobile:true};
        }'], 1);
        file_put_contents($directory.'/second-signature-result.txt', $result);

        /* A review opened before its evidence is withdrawn is refused on signing and read afresh. */
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            '.$signIn($staleEmail).'
            const denied = await page.goto('.json_encode($base.$path($solo)).');
            if ((denied?.status() ?? 0) < 400) throw new Error("Another business application was readable: " + denied?.status());
            await page.goto('.json_encode($base.$path($stale)).');
            '.$accept.'
            '.$noOverflow.'
            return {deniedStatus:denied?.status()};
        }'], 1);
        file_put_contents($directory.'/denied-result.txt', $result);
        app(RecordIsolatedBusinessCreditFacts::class)->handle($stale['audit']['staff']->id, $stale['audit']['business'], 1, null,
            'synthetic:withdrawn', 'Withdraw inaccurate synthetic facts.', (string) Str::uuid());
        $result = $run(['run-code', 'async (page) => {
            const errors = []; page.on("pageerror", error => errors.push(error.message));
            const refused = page.waitForResponse(response => response.url().endsWith("/submit"));
            await page.getByRole("button", {name:"Sign application", exact:true}).click();
            const response = await refused;
            const body = await response.json();
            if (response.ok()) throw new Error("A stale review was accepted: " + body.code);
            await page.getByRole("alert").first().waitFor();
            /* The refusal reads the page afresh: the withdrawn evidence leaves no offer to sign. */
            await page.getByText("There is no offer to accept yet. Go back to your raise to get a quote.").waitFor();
            if (await page.getByRole("button", {name:"Sign application"}).count() !== 0) throw new Error("A stale offer can still be signed");
            if (page.url().includes("submitted")) throw new Error("Moved on after a refusal");
            await page.screenshot({path:"ua-live-stale-phone.png", fullPage:true, animations:"disabled"});
            if (errors.length) throw new Error(JSON.stringify(errors));
            return {staleStatus:response.status(), staleCode:body.code};
        }'], 1);
        file_put_contents($directory.'/stale-result.txt', $result);

        expect(BusinessApplication::query()->where('business_id', $relatedBusiness)->count())->toBe(1)
            ->and(BusinessApplicationSubmission::query()->count())->toBe(2)
            ->and(BusinessApplicationSignature::query()->where('business_application_id', $pair['application']->id)->count())->toBe(2)
            ->and(BusinessApplicationSubmission::query()->where('business_application_id', $stale['application']->id)->exists())->toBeFalse();
    } catch (Throwable $failure) {
        foreach ([0, 1] as $session) {
            try {
                file_put_contents($directory."/failure-snapshot-{$session}.txt", $run(['snapshot'], $session));
                $run(['run-code', 'async (page) => { await page.screenshot({path:"failure-'.$session.'.png", fullPage:true, animations:"disabled"}); }'], $session);
            } catch (Throwable $captureFailure) {
                file_put_contents($directory."/failure-capture-{$session}.txt", $captureFailure->getMessage());
            }
        }

        throw $failure;
    } finally {
        foreach ([0, 1] as $session) {
            try {
                $run(['close'], $session);
            } catch (Throwable $closeFailure) {
                file_put_contents($directory."/close-failure-{$session}.txt", $closeFailure->getMessage());
            }
        }
        $server->stop();
        file_put_contents($directory.'/server.log', $server->getErrorOutput());
    }
})->group('browser');
