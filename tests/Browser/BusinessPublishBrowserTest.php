<?php

declare(strict_types=1);

use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * C3 staff release → Business Publish (AC-03) on real records: an accepted application whose final
 * signature reserved its exposure and whose audit report is published, released by staff through
 * the web command, then published by its signatory in a real browser. A historical submission with
 * no reservation shows the refusal and never offers Publish.
 */

uses(TestCase::class, DatabaseTruncation::class);

const BP_PASSWORD = 'Synthetic-publish-browser-42!';
const BP_PORT = 8033;

/**
 * An accepted application with a published report, and its signatory's known sign-in.
 *
 * @return array{fixture: array<string, mixed>, signatory: User, path: string}
 */
function bpAccepted(string $email): array
{
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $signatory = $fixture['audit']['authority']['users'][0];
    $signatory->forceFill(['email' => $email, 'password' => BP_PASSWORD])->save();

    return ['fixture' => $fixture, 'signatory' => $signatory->refresh(),
        'path' => '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id.'/publish'];
}

it('publishes a staff-released application once against its exposure reservation in a real browser', function (): void {
    ['fixture' => $fixture, 'signatory' => $signatory, 'path' => $path] = bpAccepted('bp-publish@example.test');
    $reservation = BusinessExposureReservation::query()->sole();
    $application = $fixture['application'];
    $business = $fixture['audit']['business'];

    /* Staff release through the real web command, as the MFA staff user holding applications.review. */
    $this->actingAs($fixture['audit']['staff'])->postJson(route('staff.applications.release', ['application' => $application->id]), [
        'request_id' => (string) Str::uuid(), 'application_id' => $application->id, 'expected_revision' => 0,
        'reason' => 'Verified current release gates.',
    ])->assertOk()->assertJsonPath('code', 'APPLICATION_RELEASED')->assertJsonPath('data.next', null);
    expect(BusinessApplicationRelease::query()->count())->toBe(1);

    (new BrowserJourney('publish', BP_PORT))->within(function (BrowserJourney $journey) use ($signatory, $path, $business): void {
        $journey->login('bp1', $signatory->email, BP_PASSWORD);
        $answer = $journey->code('bp1', '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.$path).');
            const sheet = page.getByRole("dialog", {name:"Publish to the Investor feed"});
            await sheet.getByText("Released for listing by Rozine staff.", {exact:true}).waitFor();
            const steps = sheet.getByRole("list", {name:"Before you publish"}).getByRole("listitem");
            if (await steps.count() !== 4) throw new Error("Expected four prerequisites, saw " + await steps.count());
            for (const step of await steps.all()) {
                if (!(await step.innerText()).trim().endsWith("Done")) throw new Error("Prerequisite not met: " + await step.innerText());
            }
            const fee = sheet.locator("dt", {hasText:"Listing fee"});
            await fee.getByText("Waived for the MVP", {exact:true}).waitFor();
            if ((await fee.locator("xpath=following-sibling::dd").innerText()).trim() !== "RWF 0") throw new Error("Listing fee is not RWF 0");
            await sheet.getByText("The listing fee is waived for the MVP. You pay RWF 0 to publish.", {exact:true}).waitFor();
            await sheet.getByText("Disclosure listing-fee-waiver-1", {exact:true}).waitFor();
            const publish = sheet.getByRole("button", {name:"Publish", exact:true});
            await publish.waitFor();
            await noOverflow();
            await shot('.$journey->shot('released-phone').');
            const answered = page.waitForResponse(response => response.url().endsWith("/publish") && response.request().method() === "POST");
            await publish.click();
            const response = await answered;
            const body = await response.json();
            /* The page follows the server: a null next reads the sheet afresh, otherwise it visits next. */
            const next = body.data.next;
            if (next !== null) {
                await page.waitForURL("**" + next.url);
                await page.getByRole("dialog", {name:"Synthetic equipment purchase"}).waitFor();
                if (await page.locator("iframe").count() !== 0) throw new Error("The campaign page was not an Inertia page");
                await noOverflow();
                await shot('.$journey->shot('campaign-phone').');
                await page.goto('.json_encode($journey->base.$path).');
            }
            const published = page.getByRole("dialog", {name:"Publish to the Investor feed"});
            await published.getByRole("heading", {name:"Listed for investors", exact:true}).waitFor();
            const receipt = published.locator("dl[aria-label=\"Listing receipt\"]");
            if (await receipt.count() !== 1 || await receipt.getByRole("definition").filter({hasText:/^RWF 0$/}).count() !== 1) throw new Error("No zero-fee listing receipt");
            await published.getByRole("link", {name:"View campaign", exact:true}).waitFor();
            if (await published.getByRole("button", {name:"Publish", exact:true}).count() !== 0) throw new Error("Publish offered after publishing");
            await noOverflow();
            await shot('.$journey->shot('published-phone').');
            return {status: response.status(), code: body.code, next: next === null ? null : next.url};');
        expect($answer['status'])->toBe(200)
            ->and($answer['code'])->toBe('LISTING_PUBLISHED')
            ->and($answer['next'])->toBe('/business/'.$business.'/campaigns/'.BusinessCampaign::query()->sole()->id);
    });

    $campaign = BusinessCampaign::query()->sole();
    $journaled = CommandOperation::query()->where('command', 'application.publish')->get()
        ->filter(fn (CommandOperation $operation): bool => $operation->result['code'] === 'LISTING_PUBLISHED');
    expect($campaign->business_application_id)->toBe($application->id)
        ->and($campaign->exposure_reservation_id)->toBe($reservation->id)
        ->and(BusinessExposureReservation::query()->sole()->id)->toBe($reservation->id)
        ->and($campaign->expires_at->diffInSeconds($campaign->live_at, true))->toBe(30 * 86400.0)
        ->and($campaign->payload['listing_fee'])->toEqual(['currency' => 'RWF', 'amount' => '0'])
        ->and($journaled)->toHaveCount(1)
        ->and($journaled->sole()->result['data']['receipt']['amount'])->toEqual(['currency' => 'RWF', 'amount' => '0']);
});

it('shows the missing reservation refusal and no Publish for a historical submission', function (): void {
    ['signatory' => $signatory, 'path' => $path] = bpAccepted('bp-historical@example.test');
    DB::statement('ALTER TABLE business_exposure_reservations DISABLE TRIGGER business_exposure_reservations_protected');
    BusinessExposureReservation::query()->delete();
    DB::statement('ALTER TABLE business_exposure_reservations ENABLE TRIGGER business_exposure_reservations_protected');

    (new BrowserJourney('historical', BP_PORT))->within(function (BrowserJourney $journey) use ($signatory, $path): void {
        $journey->login('bp2', $signatory->email, BP_PASSWORD);
        $journey->code('bp2', '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.$path).');
            const sheet = page.getByRole("dialog", {name:"Publish to the Investor feed"});
            await sheet.getByText("Not released for listing", {exact:true}).waitFor();
            await sheet.getByText("No borrowing reservation is on record for this application, so it can\'t be listed.", {exact:true}).waitFor();
            if (await sheet.getByRole("button", {name:"Publish", exact:true}).count() !== 0) throw new Error("Publish offered without a reservation");
            await noOverflow();
            await shot('.$journey->shot('historical-phone').');');
    });

    expect(BusinessCampaign::query()->count())->toBe(0)
        ->and(BusinessApplicationRelease::query()->count())->toBe(0);
});
