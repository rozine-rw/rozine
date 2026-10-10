<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditorFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * The Auditor Profile against the design in a real browser: an MFA-enrolled partner walks the
 * design's nine sections on a wide screen, sees their own email under Personal & contact and
 * two-factor on under Security & devices, and on a phone opens a section from the menu without
 * horizontal overflow.
 */

uses(TestCase::class, DatabaseTruncation::class);

const AP_PASSWORD = 'Synthetic-auditor-profile-browser-42!';

it('walks the design Auditor Profile sections on a wide screen and a phone', function (): void {
    $secret = (new Google2FA)->generateSecretKey();
    $actor = AuditorFixture::make();
    $actor['user']->forceFill(['email' => 'ap-auditor@example.test', 'password' => AP_PASSWORD, 'two_factor_secret' => encrypt($secret)])->save();
    expect($actor['user']->refresh()->email)->toBe('ap-auditor@example.test');

    (new BrowserJourney('auditor-profile', 8046))->within(function (BrowserJourney $journey) use ($secret): void {
        $journey->login('ap1', 'ap-auditor@example.test', AP_PASSWORD, $secret);
        $journey->code('ap1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/auditor/profile').');
            const menu = page.getByRole("navigation", {name:"Profile sections"});
            await menu.waitFor();
            const rows = (await menu.getByRole("link").allTextContents()).map((t) => t.replace("›", "").trim());
            const expected = ["Earnings","Personal & contact","Accreditation","Payout bank account","Availability & coverage","Audit-the-Auditor telemetry","Security & devices","Auditor Academy","Terms & legal"];
            if (JSON.stringify(rows) !== JSON.stringify(expected)) throw new Error("menu: " + rows);
            await shot('.$journey->shot('accreditation-desktop').');
            await menu.getByRole("link", {name:/Personal & contact/}).click();
            await page.waitForURL("**/auditor/profile?section=contact");
            await page.getByText("ap-auditor@example.test").waitFor();
            await shot('.$journey->shot('contact-desktop').');
            await menu.getByRole("link", {name:/Audit-the-Auditor telemetry/}).click();
            await page.waitForURL("**/auditor/profile?section=telemetry");
            await page.getByText("On-time closing").waitFor();
            await shot('.$journey->shot('telemetry-desktop').');
            await menu.getByRole("link", {name:/Security & devices/}).click();
            await page.waitForURL("**/auditor/profile?section=security");
            await page.getByText("On · authenticator app code at sign-in").waitFor();
            await menu.getByRole("link", {name:/Terms & legal/}).click();
            await page.waitForURL("**/auditor/profile?section=legal");
            await page.getByRole("link", {name:/Engagement terms/}).waitFor();
            await shot('.$journey->shot('legal-desktop').');
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/auditor/profile').');
            await page.getByRole("navigation", {name:"Profile sections"}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('menu-phone').');
            await page.getByRole("link", {name:/Earnings/}).click();
            await page.waitForURL("**/auditor/profile?section=earnings");
            await page.getByText("No earnings yet").waitFor();
            await noOverflow();
            await shot('.$journey->shot('earnings-phone').');');
    });
});
