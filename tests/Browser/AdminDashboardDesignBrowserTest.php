<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * The Admin console against the design in a real browser: an MFA approver signs in, sees the
 * design's quick commands that open a page (Review queue, Audit desk, Policies) and no Broadcast
 * or Run cycle, opens Oversight › Reports from the sidebar and from Audit desk, and reads the
 * Reports page's design on a phone without horizontal overflow.
 */

uses(TestCase::class, DatabaseTruncation::class);

const ADD_PASSWORD = 'Synthetic-admin-dashboard-browser-42!';
const ADD_SECRET = 'JBSWY3DPEHPK3PXP';

it('offers the design quick commands and Oversight › Reports in the live console', function (): void {
    $staff = User::factory()->withTwoFactor()->create(['email' => 'add-staff@example.test', 'password' => ADD_PASSWORD]);
    $staff->forceFill(['two_factor_secret' => encrypt(ADD_SECRET), 'two_factor_confirmed_at' => now()->subMinute()])->save();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Console design browser journey.', (string) Str::uuid(), ['approver']);
    $this->actingAs($staff)->get('/admin/reports')->assertOk();
    auth()->logout();

    (new BrowserJourney('admin-dashboard-design', 8048))->within(function (BrowserJourney $journey): void {
        $journey->login('add1', 'add-staff@example.test', ADD_PASSWORD, ADD_SECRET);
        $journey->code('add1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/admin/dashboard').');
            const commands = page.getByRole("navigation", {name:"Quick commands"});
            await commands.waitFor();
            const labels = (await commands.getByRole("link").allTextContents()).map((t) => t.trim());
            if (JSON.stringify(labels) !== JSON.stringify(["Review queue","Audit desk","Policies"])) throw new Error("commands: " + labels);
            await shot('.$journey->shot('dashboard-desktop').');
            await commands.getByRole("link", {name:"Audit desk"}).click();
            await page.waitForURL("**/admin/reports");
            const reports = page.getByRole("region", {name:"Monthly Reports"});
            await reports.getByRole("table", {name:"Field Flash Audit operations"}).waitFor();
            await reports.getByRole("table", {name:"Monthly audit compliance tracker"}).waitFor();
            await reports.getByText("No ISRS 4400 engagements recorded yet.").waitFor();
            await shot('.$journey->shot('reports-desktop').');
            await page.goto('.json_encode($journey->base.'/admin/dashboard').');
            await page.getByRole("link", {name:/^Reports$/}).first().click();
            await page.waitForURL("**/admin/reports");
            await commands.waitFor({state:"detached"});
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/admin/reports').');
            await page.getByRole("region", {name:"Monthly Reports"}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('reports-phone').');');
    });
});
