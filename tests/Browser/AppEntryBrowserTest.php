<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BrowserJourney;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\TestCase;

/*
 * The launcher opens every app straight on its designed home, in a real browser: a verified
 * Investor lands on Deals, a Business member on their business's Home, an Auditor on the Auditor
 * Home and a staff member on the Admin console's Dashboard. No page sits between the launcher and
 * an app, and the bare app URLs (/investor, /business, /admin) go to the same places. A sealed
 * report waiting for the Business's co-signatures is reached from the Business Home's Today list.
 */

uses(TestCase::class, DatabaseTruncation::class);

const AE_PASSWORD = 'Synthetic-app-entry-browser-42!';
const AE_PORT = 8041;
const AE_SECRET = 'JBSWY3DPEHPK3PXP';

/** Page code that fails if the removed in-between pages render anything. */
function aeNoInBetween(): string
{
    return '
            for (const text of ["Choose an app", "Investor workspace", "Business workspace", "Staff workspace", "Account access"]) {
                if (await page.getByText(text, {exact:true}).count() !== 0) throw new Error("in-between page shown: " + text);
            }';
}

it('opens each app from the launcher straight on its designed home', function (): void {
    $investor = InvestorWalletFixture::investor('ae-investor@example.test', AE_PASSWORD)['user'];
    $authority = BusinessAuthorityFixture::make();
    $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $authority['users'][0]->forceFill(['email' => 'ae-business@example.test', 'password' => AE_PASSWORD, 'two_factor_secret' => null,
        'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    $auditor = AuditorFixture::make();
    foreach (['ae-auditor@example.test' => $auditor['user'], 'ae-staff@example.test' => $auditor['staff']] as $email => $user) {
        $user->forceFill(['email' => $email, 'password' => AE_PASSWORD, 'two_factor_secret' => encrypt(AE_SECRET),
            'two_factor_confirmed_at' => now()->subMinute()])->save();
    }
    expect($investor->refresh()->email)->toBe('ae-investor@example.test')->and(Str::isUlid($business))->toBeTrue();

    (new BrowserJourney('app-entry', AE_PORT))->within(function (BrowserJourney $journey) use ($business): void {
        $journey->login('ae1', 'ae-investor@example.test', AE_PASSWORD);
        $journey->code('ae1', '
            await page.getByRole("button", {name:"Investor", exact:true}).click();
            await page.waitForURL("**/investor/deals");'.aeNoInBetween().'
            await page.getByRole("link", {name:"Portfolio"}).first().waitFor();
            await shot('.$journey->shot('investor-deals').');
            await page.goto('.json_encode($journey->base.'/investor').');
            await page.waitForURL("**/investor/deals");');

        $journey->login('ae2', 'ae-business@example.test', AE_PASSWORD);
        $journey->code('ae2', '
            await page.getByRole("button", {name:"Business", exact:true}).click();
            await page.waitForURL("**/business/'.$business.'");'.aeNoInBetween().'
            await shot('.$journey->shot('business-home').');
            await page.goto('.json_encode($journey->base.'/business/'.$business.'/wallet').');
            await page.getByRole("link", {name:"Home", exact:true}).first().click();
            await page.waitForURL("**/business/'.$business.'");'.aeNoInBetween().'
            await page.goto('.json_encode($journey->base.'/business').');
            await page.waitForURL("**/business/'.$business.'");');

        $journey->login('ae3', 'ae-auditor@example.test', AE_PASSWORD, AE_SECRET);
        $journey->code('ae3', '
            await page.getByRole("button", {name:"Auditor", exact:true}).click();
            await page.waitForURL(/\/auditor$/);'.aeNoInBetween().'
            await shot('.$journey->shot('auditor-home').');');

        $journey->login('ae4', 'ae-staff@example.test', AE_PASSWORD, AE_SECRET);
        $journey->code('ae4', '
            await page.getByRole("link", {name:"Open staff workspace"}).click();
            await page.waitForURL("**/admin/dashboard");'.aeNoInBetween().'
            await page.getByText("Rozine Operations Center").first().waitFor();
            await shot('.$journey->shot('staff-dashboard').');
            await page.goto('.json_encode($journey->base.'/admin').');
            await page.waitForURL("**/admin/dashboard");');
    });
});

it('opens a sealed flash report awaiting co-signature from the Business Home it launches into', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    $business = $fixture['audit']['business'];
    $report = $fixture['report']->id;
    $fixture['audit']['authority']['users'][0]->forceFill(['email' => 'ae-cosign@example.test', 'password' => AE_PASSWORD, 'two_factor_secret' => null,
        'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    expect($fixture['report']->refresh()->status)->toBe('sealed');

    (new BrowserJourney('app-entry-cosign', AE_PORT))->within(function (BrowserJourney $journey) use ($business, $report): void {
        $journey->login('ae5', 'ae-cosign@example.test', AE_PASSWORD);
        $journey->code('ae5', '
            await page.getByRole("button", {name:"Business", exact:true}).click();
            await page.waitForURL("**/business/'.$business.'");'.aeNoInBetween().'
            await page.getByText("Flash audit report", {exact:true}).first().waitFor();
            await shot('.$journey->shot('business-home-cosign').');
            await page.getByRole("link", {name:/Review & co-sign/}).first().click();
            await page.waitForURL("**/business/'.$business.'/audit-reports/'.$report.'");
            await page.getByRole("heading", {name:"Co-sign the audit report", exact:true}).first().waitFor();
            await shot('.$journey->shot('flash-cosign').');');
    });
});
