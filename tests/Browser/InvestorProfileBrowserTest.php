<?php

declare(strict_types=1);

use App\Domain\Identity\InvestorVerificationCase;
use App\Models\InvestorVerification;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\Support\InvestorWalletFixture;
use Tests\TestCase;

/*
 * The Investor Profile in a real browser, against the design: on a wide screen the menu sits beside
 * Personal information, which shows the approved ID number masked; the design's eight rows open
 * their sub-pages; on a phone the menu opens each sub-page full screen with a way back.
 */

uses(TestCase::class, DatabaseTruncation::class);

const PROFILE_PASSWORD = 'Synthetic-profile-browser-42!';

it('shows the design profile with the masked ID number on a wide screen and a phone', function (): void {
    $fixture = InvestorWalletFixture::investor('profile-investor@example.test', PROFILE_PASSWORD);
    (new InvestorVerification)->forceFill(['party_id' => $fixture['party']->id, 'revision' => 3, 'status' => 'approved', 'submitted_at' => now(),
        'state' => [...app(InvestorVerificationCase::class)->empty(), 'step' => 'liveness', 'date_of_birth' => '1990-08-01',
            'id_number' => '1199080012345678']])->save();
    expect(InvestorVerification::query()->where('party_id', $fixture['party']->id)->value('status'))->toBe('approved');

    (new BrowserJourney('investor-profile', 8043))->within(function (BrowserJourney $journey): void {
        $journey->login('pf1', 'profile-investor@example.test', PROFILE_PASSWORD);
        $journey->code('pf1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/profile').');
            await page.getByRole("heading", {name:"Personal information"}).waitFor();
            await page.getByText("•••• •••• •••• 5678").waitFor();
            if ((await page.content()).includes("1199080012345678")) throw new Error("full ID number reached the page");
            const menu = page.getByRole("navigation", {name:"Profile menu"});
            const rows = (await menu.getByRole("link").allTextContents()).map((t) => t.replace("›", "").trim());
            const expected = ["Personal information","Rozine Plus · how to unlock","Security center","Linked accounts","Statements & tax","Help center","Terms & Conditions","Privacy Note"];
            if (JSON.stringify(rows) !== JSON.stringify(expected)) throw new Error("menu: " + rows);
            if (await page.getByRole("button", {name:"Save changes"}).count() !== 0) throw new Error("a save with no command");
            await shot('.$journey->shot('personal-desktop').');
            await menu.getByRole("link", {name:/Security center/}).click();
            await page.waitForURL("**/investor/profile?section=security");
            await page.getByText("Off · add an authenticator app code at sign-in").waitFor();
            await shot('.$journey->shot('security-desktop').');
            await menu.getByRole("link", {name:/Rozine Plus/}).click();
            await page.waitForURL("**/investor/profile?section=plan");
            await page.getByText("Rozine Plus is not open yet").waitFor();
            await shot('.$journey->shot('plan-desktop').');
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor/profile').');
            await page.getByRole("navigation", {name:"Profile menu"}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('menu-phone').');
            await page.getByRole("link", {name:/Personal information/}).click();
            await page.waitForURL("**/investor/profile?section=personal");
            await page.getByText("•••• •••• •••• 5678").waitFor();
            await noOverflow();
            await shot('.$journey->shot('personal-phone').');
            await page.getByRole("link", {name:"Back"}).click();
            await page.waitForURL("**/investor/profile");
            await page.getByRole("navigation", {name:"Profile menu"}).waitFor();');
    });
});
