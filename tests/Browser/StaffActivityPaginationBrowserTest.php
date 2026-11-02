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
