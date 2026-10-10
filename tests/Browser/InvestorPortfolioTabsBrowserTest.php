<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\Support\InvestorWalletFixture;
use Tests\TestCase;

/*
 * The Investor Portfolio's tabs in a real browser, against the design: a verified investor sees
 * Active, Matured, Secondary and Saved; Secondary and Saved open with the design's empty states,
 * and Saved's "Browse opportunities" leads to Deals. On a phone the tabs fit without horizontal
 * overflow.
 */

uses(TestCase::class, DatabaseTruncation::class);

const PT_PASSWORD = 'Synthetic-portfolio-tabs-browser-42!';

it('offers the design Portfolio tabs with Secondary and Saved empty on a wide screen and a phone', function (): void {
    $fixture = InvestorWalletFixture::investor('portfolio-tabs@example.test', PT_PASSWORD);
    expect($fixture['user']->email)->toBe('portfolio-tabs@example.test');

    (new BrowserJourney('investor-portfolio-tabs', 8049))->within(function (BrowserJourney $journey): void {
        $journey->login('pt1', 'portfolio-tabs@example.test', PT_PASSWORD);
        $journey->code('pt1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/portfolio').');
            const tabs = page.getByRole("navigation", {name:"Holdings"});
            await tabs.waitFor();
            const labels = (await tabs.getByRole("link").allTextContents()).map((t) => t.trim());
            if (JSON.stringify(labels) !== JSON.stringify(["Active","Matured","Secondary","Saved"])) throw new Error("tabs: " + labels);
            await tabs.getByRole("link", {name:"Secondary"}).click();
            await page.waitForURL("**/investor/portfolio?tab=secondary");
            await page.getByText("Holdings in this category will appear here.").waitFor();
            await shot('.$journey->shot('secondary-desktop').');
            await tabs.getByRole("link", {name:"Saved"}).click();
            await page.waitForURL("**/investor/portfolio?tab=saved");
            await page.getByText("No saved deals yet").waitFor();
            if (await page.getByText(/heart/).count() !== 0) throw new Error("a save hint with no save command");
            await shot('.$journey->shot('saved-desktop').');
            await page.getByRole("link", {name:"Browse opportunities"}).click();
            await page.waitForURL("**/investor/deals");
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor/portfolio?tab=saved').');
            await page.getByText("No saved deals yet").waitFor();
            await noOverflow();
            await shot('.$journey->shot('saved-phone').');');
    });
});
