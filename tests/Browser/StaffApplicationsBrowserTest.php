<?php

declare(strict_types=1);

use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * The C3 staff queue (staff.applications.index) in a real browser: an MFA staff member holding
 * applications.review signs in, sees the retained submitted application with unavailable capacity
 * shown as such, opens its review with the authoritative release gates and no invented audit or
 * Business link, and searches by note title.
 */

uses(TestCase::class, DatabaseTruncation::class);

const SA_PASSWORD = 'Synthetic-staff-queue-browser-42!';
const SA_PORT = 8034;
const SA_SECRET = 'JBSWY3DPEHPK3PXP';

it('lists, opens and searches the live staff applications queue in a real browser', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $staff = $fixture['audit']['staff'];
    $staff->forceFill(['email' => 'sa-staff@example.test', 'password' => SA_PASSWORD,
        'two_factor_secret' => encrypt(SA_SECRET), 'two_factor_confirmed_at' => now()->subMinute()])->save();
    $title = $fixture['application']->refresh()->draft['title'];

    (new BrowserJourney('staff-queue', SA_PORT))->within(function (BrowserJourney $journey) use ($title): void {
        $journey->login('sa1', 'sa-staff@example.test', SA_PASSWORD, SA_SECRET);
        $journey->code('sa1', '
            await page.goto("http://127.0.0.1:" + '.SA_PORT.' + "/admin/applications");
            const table = page.getByRole("table", {name:"Applications"});
            await table.waitFor();
            const rows = table.getByRole("row");
            if (await rows.count() !== 2) throw new Error("expected one application row");
            await table.getByText("Not available", {exact:true}).waitFor();
            if (await table.getByRole("progressbar").count() !== 0) throw new Error("capacity bar invented");
            await shot('.$journey->shot('queue').');
            await table.getByRole("link", {name:/Review/}).first().click();
            const drawer = page.getByRole("dialog");
            await drawer.getByText("Current release review required", {exact:false}).waitFor();
            await drawer.getByRole("region", {name:"Release for listing"}).waitFor();
            if (await drawer.getByText(/Listing audit (missing|in progress)/).count() !== 0) throw new Error("audit banner inferred");
            if (await drawer.getByRole("link", {name:/business profile/}).count() !== 0) throw new Error("business link invented");
            await shot('.$journey->shot('review').');
            await page.goto(page.url().split("?")[0]);
            const search = page.getByRole("searchbox");
            await search.fill('.json_encode(mb_substr($title, 0, 6)).');
            await search.press("Enter");
            await page.waitForURL(/search=/);
            if (!page.url().includes("search=") || page.url().includes("q=") || page.url().includes("application=")) throw new Error("search params wrong: " + page.url());
            await page.getByRole("table", {name:"Applications"}).getByText('.json_encode($title).', {exact:false}).waitFor();
            await shot('.$journey->shot('search').');
        ');
    });

    /* Browsing, opening and searching the queue is read-only: nothing is released or listed. */
    expect(BusinessApplicationRelease::query()->count())->toBe(0)
        ->and(BusinessCampaign::query()->count())->toBe(0);
});
