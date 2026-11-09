<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\Support\StaffFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('loads, retries, and resets activity pages in the browser', function (): void {
    $password = 'Synthetic-activity-pagination-42!';
    $secret = 'JBSWY3DPEHPK3PXP';
    $officer = User::factory()->withTwoFactor()->create(['email' => 'activity-pagination@example.test', 'password' => $password]);
    $officer->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()->subMinute()])->save();
    StaffFixture::provision($officer, ['compliance'], 'Synthetic activity pagination.');
    foreach (range(1, 55) as $index) {
        CommandOperation::factory()->create(['command' => 'wallet.deposit', 'target_type' => 'investor_wallet', 'target_id' => 'pagination-'.$index, 'created_at' => now()->subMinutes($index)]);
    }
    auth()->logout();

    (new BrowserJourney('staff-activity-pagination', 8056))->within(function (BrowserJourney $journey) use ($password, $secret): void {
        $journey->login('sap1', 'activity-pagination@example.test', $password, landing: '**/admin/dashboard', secret: $secret);
        $result = $journey->code('sap1', '
            await page.goto('.json_encode($journey->base.'/admin/activity').');
            if (await page.getByRole("row").count() !== 51) throw new Error("Expected 50 initial activity rows");
            let fail = true;
            const route = async request => {
                if (fail && request.request().url().includes("cursor=")) { fail = false; return request.abort("failed"); }
                return request.continue();
            };
            await page.route("**/admin/activity*", route);
            await page.getByRole("button", {name:"Load more"}).click();
            await page.getByRole("alert").filter({hasText:"Unable to load more activity"}).waitFor();
            await page.getByRole("button", {name:"Retry"}).click();
            await page.getByRole("button", {name:"Retry"}).waitFor({state:"detached"});
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 57);
            if (await page.getByRole("button", {name:"Load more"}).count() !== 0) throw new Error("Last page still offers load more");
            await page.getByRole("searchbox", {name:"Search the audit trail"}).fill("pagination-55");
            await page.getByRole("searchbox", {name:"Search the audit trail"}).press("Enter");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 2);
            if (page.url().includes("cursor=")) throw new Error("Filter kept the old cursor");
            await page.unroute("**/admin/activity*", route);
            return true;
        ');
        expect($result)->toBeTrue();
    });
});

it('supports keyboard paging and browser history through a long filtered trail', function (): void {
    $password = 'Synthetic-trail-history-42!';
    $secret = 'JBSWY3DPEHPK3PXP';
    $officer = User::factory()->withTwoFactor()->create(['email' => 'activity-history@example.test', 'password' => $password]);
    $officer->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()->subMinute()])->save();
    StaffFixture::provision($officer, ['compliance'], 'Synthetic activity history.');
    $analyst = User::factory()->withTwoFactor()->create(['email' => 'activity-analyst@example.test', 'password' => $password]);
    $analyst->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()->subMinute()])->save();
    StaffFixture::provision($analyst, ['analyst'], 'Synthetic activity restriction.');
    foreach (range(1, 155) as $index) {
        CommandOperation::factory()->create(['command' => 'wallet.deposit', 'target_type' => 'investor_wallet', 'target_id' => 'history-'.$index, 'created_at' => now()->subMinutes($index)]);
    }
    auth()->logout();

    (new BrowserJourney('staff-activity-history', 8057))->within(function (BrowserJourney $journey) use ($password, $secret): void {
        $journey->login('sah1', 'activity-history@example.test', $password, landing: '**/admin/dashboard', secret: $secret);
        $result = $journey->code('sah1', '
            await page.goto('.json_encode($journey->base.'/admin/activity').');
            const button = page.getByRole("button", {name:"Load more"});
            await button.focus();
            if (!await button.evaluate(el => el === document.activeElement)) throw new Error("Load more cannot receive keyboard focus");
            await page.keyboard.press("Enter");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 101);
            await page.getByRole("button", {name:"Load more"}).focus();
            await page.keyboard.press("Enter");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 151);
            await page.getByRole("button", {name:"Load more"}).focus();
            await page.keyboard.press("Enter");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 158);
            const search = page.getByRole("searchbox", {name:"Search the audit trail"});
            await search.fill("history-155");
            await search.press("Enter");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 2);
            if (!page.url().includes("history-155")) throw new Error("Search URL missing");
            await page.goBack();
            await page.waitForURL(url => !url.searchParams.has("q"));
            if (!await page.getByRole("button", {name:"Load more"}).isVisible()) throw new Error("Back did not restore the unfiltered trail");
            await page.goForward();
            await page.waitForURL(url => url.searchParams.get("q") === "history-155");
            await page.waitForFunction(() => document.querySelectorAll("[role=row]").length === 2);
            if (await page.getByText("Investor wallet HISTORY-155").count() !== 1) throw new Error("Expected account-specific history was not present before switching accounts");
            await page.goto('.json_encode($journey->base.'/dashboard').');
            await page.getByRole("button", {name:"Sign out"}).click();
            await page.waitForURL('.json_encode($journey->base.'/').');
            await page.goto('.json_encode($journey->base.'/login').');
            await page.getByRole("textbox", {name:"Email address"}).fill("activity-analyst@example.test");
            await page.getByLabel("Password", {exact:true}).fill('.json_encode($password).');
            await page.getByRole("button", {name:"Log in", exact:true}).click();
            await page.waitForURL("**/two-factor-challenge");
            await page.locator("input[name=code]").fill('.json_encode($journey->otp($secret)).');
            await page.getByRole("button", {name:"Continue", exact:true}).click();
            await page.waitForURL("**/admin/dashboard");
            const denied = await page.goto('.json_encode($journey->base.'/admin/activity').');
            if (denied.status() !== 403) throw new Error("Second account was not denied the trail");
            if (await page.getByText("Investor wallet HISTORY-155").count() !== 0) throw new Error("Previous account activity remained visible");
            return true;
        ');
        expect($result)->toBeTrue();
        $historyUrls = [];
        for ($step = 0; $step < 8; $step++) {
            $back = $journey->code('sah1', '
                await page.goBack({waitUntil:"domcontentloaded"});
                return {url:page.url(), leaked:await page.getByText("Investor wallet HISTORY-155").count() > 0};
            ');
            expect($back['leaked'])->toBeFalse();
            $historyUrls[] = $back['url'];
        }
        expect(collect($historyUrls)->contains(fn (string $url): bool => str_contains($url, '/admin/activity')))->toBeTrue();
    });
});
