<?php

declare(strict_types=1);

use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Tests\Support\BrowserJourney;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\StaffFixture;
use Tests\TestCase;

/*
 * Compliance's resale controls on a phone (QA #816, 28 Nov 2026): with a halt in force, an open
 * listing and an issued note, the Secondary Market page never scrolls sideways at 390px and every
 * Halt, Lift and Cancel button sits inside the screen.
 */

uses(TestCase::class, DatabaseTruncation::class);

const SM_PASSWORD = 'Synthetic-market-phone-browser-42!';
const SM_PORT = 8041;
const SM_SECRET = 'JBSWY3DPEHPK3PXP';

it('shows the Secondary Market controls on a phone without sideways scrolling', function (): void {
    config(['app.secondary_trading' => true]);
    twoInvestorRaiseCap();
    PrimaryIssuanceFixture::close(now()->subDay()->toIso8601String());
    PrimaryIssuanceFixture::close(now()->subDay()->toIso8601String());
    $holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $seller = User::query()->where('party_id', $holding->party_id)->sole();
    app(EloquentSecondaryMarket::class)->list($seller->id, 1, $holding->id, '10', '2700', (string) Str::uuid());
    PrimaryHoldingFixture::flushDeferredChecks();
    $staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($staff, ['compliance'], 'Market surveillance duty.');
    $staff->forceFill(['email' => 'sm-staff@example.test', 'password' => SM_PASSWORD,
        'two_factor_secret' => encrypt(SM_SECRET), 'two_factor_confirmed_at' => now()->subMinute()])->save();

    (new BrowserJourney('staff-market-phone', SM_PORT))->within(function (BrowserJourney $journey): void {
        $journey->login('sm1', 'sm-staff@example.test', SM_PASSWORD, landing: '**/admin/**', secret: SM_SECRET);
        $journey->code('sm1', '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto("http://127.0.0.1:'.SM_PORT.'/admin/secondary-market");
            await page.getByRole("group", {name:"Issued notes"}).waitFor();
            const wide = await page.evaluate(() => [...document.querySelectorAll("body *")]
                .filter((element) => element.getBoundingClientRect().right > innerWidth + 1 && !element.closest(".rz-hscroll"))
                .slice(0, 8).map((element) => element.tagName + "." + String(element.className).slice(0, 120)));
            if (wide.length) throw new Error("Wider than the phone: " + wide.join(" | "));
            await noOverflow();
            for (const name of [/^Halt /, /^Cancel /]) {
                for (const button of await page.getByRole("button", {name}).all()) {
                    await button.scrollIntoViewIfNeeded();
                    const box = await button.boundingBox();
                    if (box === null || box.x < 0 || box.x + box.width > 390) throw new Error("Button off screen: " + await button.getAttribute("aria-label"));
                }
            }
            await shot('.$journey->shot('market-phone').');
        ');
    });
});
