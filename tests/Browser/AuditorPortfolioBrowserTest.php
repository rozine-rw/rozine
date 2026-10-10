<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * The Auditor Portfolio against the design in a real browser: an MFA-enrolled partner with an
 * accepted Flash Audit sees the design's panels, finds the file on its due day in the audit
 * calendar and in the month's list, opens the day, and on a phone reads the page without
 * horizontal overflow.
 */

uses(TestCase::class, DatabaseTruncation::class);

const APF_PASSWORD = 'Synthetic-auditor-portfolio-browser-42!';

it('places an accepted Flash Audit on the design Portfolio calendar on a wide screen and a phone', function (): void {
    $secret = (new Google2FA)->generateSecretKey();
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $partner['user']->forceFill(['email' => 'apf-auditor@example.test', 'password' => APF_PASSWORD, 'two_factor_secret' => encrypt($secret)])->save();
    $due = CarbonImmutable::parse($assignment->refresh()->state['complete_by'])->setTimezone('Africa/Kigali');
    $offset = ($due->year - now('Africa/Kigali')->year) * 12 + $due->month - now('Africa/Kigali')->month;
    expect($offset)->toBeGreaterThanOrEqual(0);

    (new BrowserJourney('auditor-portfolio', 8047))->within(function (BrowserJourney $journey) use ($due, $offset, $secret): void {
        $journey->login('apf1', 'apf-auditor@example.test', APF_PASSWORD, $secret);
        $journey->code('apf1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/auditor/portfolio').');
            await page.getByRole("heading", {name:"Portfolio"}).waitFor();
            await page.getByRole("link", {name:/Your CPA card/}).waitFor();
            await page.getByRole("region", {name:"Deals you sourced"}).getByText(/origination commission/).waitFor();
            await page.getByRole("region", {name:"Managed deals"}).getByText("No managed deals yet. Pass a Flash Audit to become a business\'s account manager.").waitFor();
            if (await page.getByText("List a deal").count() !== 0) throw new Error("List a deal has no command");
            for (let step = 0; step < '.$offset.'; step++) await page.getByRole("button", {name:"Next month"}).click();
            const list = page.getByRole("region", {name:'.json_encode('Due in '.$due->format('F')).'});
            const file = list.getByRole("link", {name:/Synthetic business/});
            await file.waitFor();
            if (!(await file.textContent()).includes("Flash Audit")) throw new Error("file: " + await file.textContent());
            await shot('.$journey->shot('portfolio-desktop').');
            await page.getByRole("button", {name:'.json_encode($due->format('j M Y').', 1 verification due').'}).click();
            const day = page.getByRole("dialog", {name:'.json_encode($due->format('j M Y')).'});
            await day.getByRole("link", {name:/Synthetic business/}).waitFor();
            await shot('.$journey->shot('day-desktop').');
            await day.getByRole("button", {name:"Close"}).click();
            await file.click();
            await page.waitForURL("**/auditor/jobs/*");
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/auditor/portfolio').');
            await page.getByRole("heading", {name:"Audit calendar"}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('portfolio-phone').');');
    });
});
