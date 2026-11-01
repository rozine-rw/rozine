<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BrowserJourney;
use Tests\Support\PrimaryHoldingFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('filters and restores the staff dashboard without exposing restricted panels', function (): void {
    $password = 'Synthetic-dashboard-filters-42!';
    $secret = 'JBSWY3DPEHPK3PXP';
    foreach (['treasury', 'analyst'] as $role) {
        $user = User::factory()->withTwoFactor()->create(['email' => "dashboard-{$role}@example.test", 'password' => $password]);
        $user->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()->subMinute()])->save();
        app(ConfigureStaffAccess::class)->handle($user->id, true, 'Synthetic dashboard journey.', (string) Str::uuid(), [$role]);
    }
    DB::beginTransaction();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::commit();
    auth()->logout();

    (new BrowserJourney('staff-dashboard-filters', 8054))->within(function (BrowserJourney $journey) use ($password, $secret): void {
        $journey->login('sdf1', 'dashboard-treasury@example.test', $password, landing: '**/admin/dashboard', secret: $secret);
        $rangeChecked = $journey->code('sdf1', '
            let releaseActivity;
            let activityStarted;
            const activitySeen = new Promise(resolve => { activityStarted = resolve; });
            let delayActivity = true;
            let failActivity = false;
            const activityRoute = async route => {
                if (route.request().headers()["x-inertia-partial-data"] !== "activity") return route.continue();
                if (delayActivity) {
                    delayActivity = false;
                    await new Promise(resolve => { releaseActivity = resolve; activityStarted(); });
                }
                if (failActivity) {
                    failActivity = false;
                    return route.abort("failed");
                }
                return route.continue();
            };
            await page.route("**/admin/dashboard*", activityRoute);
            await page.reload();
            await page.getByRole("status", {name:"Loading activity"}).waitFor();
            await activitySeen;
            releaseActivity();
            await page.getByRole("link", {name:"See all activity in the ledger"}).waitFor();
            failActivity = true;
            await page.reload();
            await page.getByRole("alert").filter({hasText:"Activity could not be loaded"}).waitFor();
            await page.getByRole("region", {name:"Live activity"}).getByRole("button", {name:"Try again"}).click();
            await page.getByRole("link", {name:"See all activity in the ledger"}).waitFor();
            await page.unroute("**/admin/dashboard*", activityRoute);
            const chart = page.getByRole("region", {name:"Capital raised"});
            await chart.waitFor();
            const filtered = page.waitForResponse(r => r.url().includes("/admin/dashboard") && r.request().headers()["x-inertia-partial-data"] === "capital_raised");
            await page.getByLabel("From date").fill("2027-01-01");
            const response = await filtered;
            const data = await response.json();
            if (response.status() !== 200 || !data.props.capital_raised || "activity" in data.props || "breaks" in data.props) throw new Error("Chart partial response is incorrect");
            await chart.getByRole("button", {name:"Clear"}).waitFor();
            const cleared = page.waitForResponse(r => r.url().includes("/admin/dashboard") && r.request().headers()["x-inertia-partial-data"] === "capital_raised");
            await chart.getByRole("button", {name:"Clear"}).click();
            if ((await cleared).status() !== 200) throw new Error("Clear failed");
            await chart.getByRole("button", {name:"Clear"}).waitFor({state:"detached"});
            await page.reload();
            if (await page.getByLabel("From date").inputValue() !== "") throw new Error("Clear did not survive reload");
            await page.goto('.json_encode($journey->base.'/admin/dashboard?from=2027-01-01T00%3A00').');
            if (await page.getByLabel("From date").inputValue() !== "2027-01-01") throw new Error("URL filter missing");
            await page.goBack();
            if (await page.getByLabel("From date").inputValue() !== "") throw new Error("Back did not restore range");
            await page.goForward();
            if (await page.getByLabel("From date").inputValue() !== "2027-01-01") throw new Error("Forward did not restore range");
            await page.waitForLoadState("networkidle");
            const reconnectRequests = [];
            const onRequest = request => {
                if (request.url().includes("/admin/dashboard") && request.headers()["x-inertia"] === "true") reconnectRequests.push(request.headers()["x-inertia-partial-data"] ?? "full");
            };
            page.on("request", onRequest);
            await page.context().setOffline(true);
            await page.getByRole("status").filter({hasText:"offline"}).waitFor();
            const reconnected = page.waitForResponse(r => r.url().includes("/admin/dashboard") && r.request().headers()["x-inertia"] === "true");
            await page.context().setOffline(false);
            if ((await reconnected).status() !== 200) throw new Error("Reconnect refresh failed");
            await page.getByRole("status").filter({hasText:"offline"}).waitFor({state:"detached"});
            await page.waitForTimeout(1000);
            page.off("request", onRequest);
            if (reconnectRequests.filter(value => value === "full").length !== 1 || reconnectRequests.filter(value => value === "activity").length !== 1 || reconnectRequests.length !== 2) throw new Error("Unexpected reconnect requests: " + reconnectRequests);
            await page.setViewportSize({width:390, height:844});
            await page.getByLabel("From date").focus();
            let advanced = false;
            for (let step = 0; step < 6 && !advanced; step++) {
                await page.keyboard.press("Tab");
                advanced = await page.getByLabel("From time").evaluate(input => input === document.activeElement);
            }
            if (!advanced) throw new Error("Keyboard focus did not reach the time control");
            await noOverflow();
            const invalid = await page.request.get('.json_encode($journey->base.'/admin/dashboard?from=invalid').', {maxRedirects:0});
            if (invalid.status() !== 302) throw new Error("Invalid date was not refused");
            return true;
        ');
        expect($rangeChecked)->toBeTrue();

        $treasury = User::query()->where('email', 'dashboard-treasury@example.test')->sole();
        app(ConfigureStaffAccess::class)->handle($treasury->id, true, 'Synthetic access change.', (string) Str::uuid(), ['analyst']);
        $revocationChecked = $journey->code('sdf1', '
            const refreshed = page.waitForResponse(r => r.url().includes("/admin/dashboard") && r.request().headers()["x-inertia"] === "true" && !r.request().headers()["x-inertia-partial-data"]);
            const activity = page.waitForResponse(r => r.url().includes("/admin/dashboard") && r.request().headers()["x-inertia-partial-data"] === "activity");
            await page.evaluate(() => window.dispatchEvent(new Event("online")));
            if ((await refreshed).status() !== 200) throw new Error("Refresh after role change failed");
            await page.getByText("Reconciliation not tracked yet").waitFor();
            if ((await activity).status() !== 200) throw new Error("Deferred access refresh failed");
            await page.getByRole("region", {name:"Live activity"}).getByText("Not tracked in the console yet.").waitFor();
            if (await page.getByRole("link", {name:"See all activity in the ledger"}).count() !== 0) throw new Error("Revoked ledger link remained visible");
            return true;
        ');
        expect($revocationChecked)->toBeTrue();
        $journey->login('sdf2', 'dashboard-analyst@example.test', $password, landing: '**/admin/dashboard', secret: $secret);
        $restrictedChecked = $journey->code('sdf2', '
            await page.getByText("Reconciliation not tracked yet").waitFor();
            if (await page.getByRole("link", {name:"See all activity in the ledger"}).count() !== 0) throw new Error("Restricted ledger link exposed");
            return true;
        ');
        expect($restrictedChecked)->toBeTrue();
    });
});
