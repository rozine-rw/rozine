<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\Support\InvestorWalletFixture;
use Tests\TestCase;

/*
 * The Investor app's five design tabs in a real browser: a verified Investor opens Market and Cart
 * from the sidebar, sees each page's design empty state, and goes from the empty cart back to Deals.
 */

uses(TestCase::class, DatabaseTruncation::class);

const MC_PASSWORD = 'Synthetic-market-cart-browser-42!';

it('opens Market and Cart from the Investor tabs on a wide screen and a phone', function (): void {
    $investor = InvestorWalletFixture::investor('mc-investor@example.test', MC_PASSWORD)['user'];
    expect($investor->refresh()->email)->toBe('mc-investor@example.test');

    (new BrowserJourney('investor-market-cart', 8042))->within(function (BrowserJourney $journey): void {
        $journey->login('mc1', 'mc-investor@example.test', MC_PASSWORD);
        $journey->code('mc1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/deals').');
            const nav = page.getByRole("navigation").first();
            const tabs = await nav.getByRole("link").allTextContents();
            if (JSON.stringify(tabs.map((t) => t.trim())) !== JSON.stringify(["Deals","Portfolio","Market","Cart","Profile"])) throw new Error("tabs: " + tabs);
            await nav.getByRole("link", {name:"Market", exact:true}).click();
            await page.waitForURL("**/investor/market");
            await page.getByText("No notes match these filters").waitFor();
            await shot('.$journey->shot('market-desktop').');
            await page.getByRole("tab", {name:"Orders"}).click();
            await page.getByText("No orders in this tab.").waitFor();
            await page.getByRole("tab", {name:"Saved"}).click();
            await page.getByText("No saved notes yet").waitFor();
            await nav.getByRole("link", {name:"Cart", exact:true}).click();
            await page.waitForURL("**/investor/cart");
            await page.getByText("Your cart is empty").waitFor();
            await shot('.$journey->shot('cart-desktop').');
            await page.getByRole("link", {name:"Browse deals"}).click();
            await page.waitForURL("**/investor/deals");
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor/market').');
            await page.getByText("Trade active Rozine Notes before maturity.").waitFor();
            await noOverflow();
            await shot('.$journey->shot('market-phone').');
            await page.goto('.json_encode($journey->base.'/investor/cart').');
            await page.getByText("Your cart is empty").waitFor();
            await noOverflow();
            await shot('.$journey->shot('cart-phone').');');
    });
});
