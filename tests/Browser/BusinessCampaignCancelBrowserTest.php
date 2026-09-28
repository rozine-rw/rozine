<?php

declare(strict_types=1);

use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * C3 campaign cancellation (campaign.cancel) in a real browser: a staff-released application is
 * published by its signatory, who then cancels the unfunded raise from the campaign page. The page
 * shows the closed state with nothing to refund, and the exposure reservation is released once.
 */

uses(TestCase::class, DatabaseTruncation::class);

const CC_PASSWORD = 'Synthetic-cancel-browser-42!';
const CC_PORT = 8035;

/**
 * A staff-released application its signatory can publish, and the Publish path.
 *
 * @return array{publish: string, business: string}
 */
function ccReleased(TestCase $test, string $email): array
{
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $fixture['audit']['authority']['users'][0]->forceFill(['email' => $email, 'password' => CC_PASSWORD])->save();
    $application = $fixture['application'];
    $test->actingAs($fixture['audit']['staff'])->postJson(route('staff.applications.release', ['application' => $application->id]), [
        'request_id' => (string) Str::uuid(), 'application_id' => $application->id, 'expected_revision' => 0,
        'reason' => 'Verified current release gates.',
    ])->assertOk()->assertJsonPath('code', 'APPLICATION_RELEASED');

    return ['publish' => '/business/'.$fixture['audit']['business'].'/applications/'.$application->id.'/publish',
        'business' => $fixture['audit']['business']];
}

/** Publishes from the sheet in the browser and opens the live campaign page it leads to. */
function ccPublishAndOpen(BrowserJourney $journey, string $publish): string
{
    return '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.$publish).');
            const answered = page.waitForResponse(response => response.url().endsWith("/publish") && response.request().method() === "POST");
            await page.getByRole("dialog", {name:"Publish to the Investor feed"}).getByRole("button", {name:"Publish", exact:true}).click();
            const published = await (await answered).json();
            if (published.code !== "LISTING_PUBLISHED") throw new Error("Publish failed: " + published.code);
            await page.goto('.json_encode($journey->base).' + published.data.next.url);
            const campaign = page.getByRole("dialog", {name:"Synthetic equipment purchase"});
            await campaign.getByText("Raising · live", {exact:false}).first().waitFor();';
}

it('cancels an unfunded live campaign from the campaign page in a real browser', function (): void {
    ['publish' => $publish] = ccReleased($this, 'cc-signatory@example.test');

    (new BrowserJourney('campaign-cancel', CC_PORT))->within(function (BrowserJourney $journey) use ($publish): void {
        $journey->login('cc1', 'cc-signatory@example.test', CC_PASSWORD);
        $journey->code('cc1', ccPublishAndOpen($journey, $publish).'
            await shot('.$journey->shot('live-phone').');
            await campaign.getByRole("button", {name:"Cancel this raise", exact:true}).click();
            const confirm = page.getByRole("dialog", {name:"Cancel this raise?"});
            await confirm.getByRole("textbox").fill("Raising later with updated statements.");
            await noOverflow();
            await shot('.$journey->shot('confirm-phone').');
            const cancelled = page.waitForResponse(response => response.url().endsWith("/cancel") && response.request().method() === "POST");
            await confirm.getByRole("button", {name:"Cancel raise", exact:true}).click();
            const result = await (await cancelled).json();
            if (result.code !== "CAMPAIGN_CANCELLED") throw new Error("Cancel failed: " + result.code);
            await page.getByText("before any investor committed, so there was nothing to refund", {exact:false}).waitFor();
            if (await page.getByText(/went back to investors/).count() !== 0) throw new Error("Refund wording shown for an unfunded raise");
            if (await page.getByRole("button", {name:"Cancel this raise", exact:true}).count() !== 0) throw new Error("Cancel still offered after closing");
            await noOverflow();
            await shot('.$journey->shot('cancelled-phone').');');
    });

    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->phase)->toBe('cancelled')
        ->and($closure->business_campaign_id)->toBe(BusinessCampaign::query()->sole()->id)
        ->and($closure->payload['committed_refunded'])->toEqual(['currency' => 'RWF', 'amount' => '0'])
        ->and($closure->payload['reason'])->toBe('Raising later with updated statements.');
});

it('recovers a cancellation whose answer was lost by looking it up, never sending it twice', function (): void {
    ['publish' => $publish] = ccReleased($this, 'cc-replay@example.test');

    (new BrowserJourney('campaign-cancel-replay', CC_PORT))->within(function (BrowserJourney $journey) use ($publish): void {
        $journey->login('cr1', 'cc-replay@example.test', CC_PASSWORD);
        $answer = $journey->code('cr1', ccPublishAndOpen($journey, $publish).'
            const posts = [];
            const lookups = [];
            page.on("request", (request) => {
                if (request.method() === "POST" && request.url().endsWith("/cancel")) posts.push(request.postDataJSON().request_id);
                if (request.method() === "GET" && /operations\//.test(request.url())) lookups.push(request.url());
            });
            /* The server records the cancellation, but the browser never sees the answer. */
            await page.route("**/campaigns/*/cancel", async (route) => {
                await route.fetch();
                await route.abort("failed");
            }, {times: 1});
            await campaign.getByRole("button", {name:"Cancel this raise", exact:true}).click();
            await page.getByRole("dialog", {name:"Cancel this raise?"}).getByRole("button", {name:"Cancel raise", exact:true}).click();
            await page.getByText("before any investor committed, so there was nothing to refund", {exact:false}).waitFor({timeout: 20000});
            await noOverflow();
            await shot('.$journey->shot('recovered-phone').');
            return {posts, lookups: lookups.length};');
        expect($answer['posts'])->toHaveCount(1)
            ->and($answer['lookups'])->toBeGreaterThanOrEqual(1);
    });

    expect(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(BusinessCampaignClosure::query()->sole()->phase)->toBe('cancelled');
});

it('shows a raise the expiry sweep closed at its deadline as expired, with nothing to refund', function (): void {
    ['publish' => $publish] = ccReleased($this, 'cc-expiry@example.test');

    (new BrowserJourney('campaign-expiry', CC_PORT))->within(function (BrowserJourney $journey) use ($publish): void {
        $journey->login('ce1', 'cc-expiry@example.test', CC_PASSWORD);
        $campaignUrl = $journey->code('ce1', ccPublishAndOpen($journey, $publish).'
            return page.url();');

        /* The sweep runs after the retained deadline; it closes the raise at that deadline, once. */
        $campaign = BusinessCampaign::query()->sole();
        $this->travelTo($campaign->expires_at->addSecond());
        $this->artisan('campaigns:expire', ['--limit' => 100])->assertSuccessful();
        $this->travelBack();

        $journey->code('ce1', '
            await page.goto('.json_encode($campaignUrl).');
            await page.getByText("before any investor committed, so there was nothing to refund", {exact:false}).waitFor();
            await page.getByText("closed on", {exact:false}).first().waitFor();
            await page.getByText("Didn\x27t fill", {exact:true}).first().waitFor();
            if (await page.getByRole("button", {name:"Cancel this raise", exact:true}).count() !== 0) throw new Error("Cancel offered on an expired raise");
            await noOverflow();
            await shot('.$journey->shot('expired-phone').');');
    });

    $closure = BusinessCampaignClosure::query()->sole();
    expect($closure->phase)->toBe('expired')
        ->and($closure->closed_at->equalTo(BusinessCampaign::query()->sole()->expires_at))->toBeTrue();
});
