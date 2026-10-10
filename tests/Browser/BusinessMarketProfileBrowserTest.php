<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\TestCase;

/*
 * The Business app's design tabs and Profile in a real browser: a mandate holder opens Market from
 * the sidebar and sees the overview with nothing measured, then walks the design's seven Profile
 * sections, with the mandate's people under Permissions & roles; a phone shows both pages without
 * horizontal overflow.
 */

uses(TestCase::class, DatabaseTruncation::class);

const BMP_PASSWORD = 'Synthetic-business-market-browser-42!';

it('opens Business Market and every Profile section on a wide screen and a phone', function (): void {
    $authority = AuthorityFixture::make('organization', 2, 'COMPANY-001', 1);
    $business = AuthorityFixture::configure($authority)['data']['business']['id'];
    $holder = $authority['users'][0];
    $holder->forceFill(['email' => 'bmp-holder@example.test', 'password' => BMP_PASSWORD])->save();
    expect($holder->refresh()->email)->toBe('bmp-holder@example.test');

    (new BrowserJourney('business-market-profile', 8044))->within(function (BrowserJourney $journey) use ($business): void {
        $journey->login('bmp1', 'bmp-holder@example.test', BMP_PASSWORD);
        $journey->code('bmp1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/business/'.$business).');
            const nav = page.getByRole("navigation", {name:"App navigation"}).first();
            const tabs = (await nav.getByRole("link").allTextContents()).map((t) => t.trim());
            if (JSON.stringify(tabs) !== JSON.stringify(["Home","Reports","Market","Profile"])) throw new Error("tabs: " + tabs);
            await nav.getByRole("link", {name:"Market", exact:true}).click();
            await page.waitForURL("**/business/*/market");
            await page.getByRole("heading", {name:"Market Overview"}).waitFor();
            await page.getByText("No secondary trades yet.").waitFor();
            await shot('.$journey->shot('market-desktop').');
            await nav.getByRole("link", {name:"Profile", exact:true}).click();
            await page.waitForURL("**/business/*/profile");
            const menu = page.getByRole("navigation", {name:"Profile menu"});
            const rows = (await menu.getByRole("link").allTextContents()).map((t) => t.replace("›", "").trim());
            const expected = ["Company information","Security center","Permissions & roles","Linked accounts","Support center","Terms & Conditions","Privacy Note"];
            if (JSON.stringify(rows) !== JSON.stringify(expected)) throw new Error("menu: " + rows);
            await menu.getByRole("link", {name:/Permissions & roles/}).click();
            await page.waitForURL("**/profile/permissions");
            await page.getByText("Verified person 0").first().waitFor();
            await shot('.$journey->shot('permissions-desktop').');
            await menu.getByRole("link", {name:/Security center/}).click();
            await page.waitForURL("**/profile/security");
            await page.getByText("Off · add an authenticator app code at sign-in").waitFor();
            await shot('.$journey->shot('security-desktop').');
            await menu.getByRole("link", {name:/Support center/}).click();
            await page.waitForURL("**/profile/support");
            await page.getByText("Coming to the app soon").waitFor();
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/business/'.$business.'/market').');
            await page.getByText("None of your notes trade yet").waitFor();
            await noOverflow();
            await shot('.$journey->shot('market-phone').');
            await page.goto('.json_encode($journey->base.'/business/'.$business.'/profile/permissions').');
            await page.getByText("Verified person 1").first().waitFor();
            await noOverflow();
            await shot('.$journey->shot('permissions-phone').');');
    });
});
