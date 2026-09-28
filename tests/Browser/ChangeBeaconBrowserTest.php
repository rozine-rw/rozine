<?php

declare(strict_types=1);

use App\Models\WalletDepositCredit;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * S4-E in a real browser: the Investor wallet open in one context converges on a deposit made in
 * a second context, and on its later synthetic credit, through the change beacon alone, with no
 * manual refresh and no full page load. While the first context is offline the beacon stops
 * reading, and it catches up as soon as the connection returns.
 */

uses(TestCase::class, DatabaseTruncation::class);

const CB_PORT = 8037;

const CB_EMAIL = 'beacon@s4e.rozine.invalid';

it('updates an open wallet from another context\'s deposit and credit without a refresh', function (): void {
    (new BrowserJourney('change-beacon', CB_PORT))->within(function (BrowserJourney $journey): void {
        expect(Artisan::call('local:wallet', ['--seed' => CB_EMAIL]))->toBe(0);
        $journey->login('cb1', CB_EMAIL, SyntheticWalletSeeder::PASSWORD);
        $journey->login('cb2', CB_EMAIL, SyntheticWalletSeeder::PASSWORD);

        // Context 1 opens the wallet and marks the document, which only a full page load would clear.
        $journey->code('cb1', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            await page.getByRole("region", {name:"Wallet total"}).getByText("No deposits waiting").waitFor();
            await page.evaluate(() => { window.__beacon = "same document"; window.__reads = 0; });
            page.on("request", (request) => { if (request.url().includes("/changes?")) page.evaluate(() => window.__reads++).catch(() => {}); });');

        // Context 2 deposits through the live page.
        $request = $journey->code('cb2', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const form = page.getByRole("form", {name:"Add money"});
            const priced = page.waitForResponse((response) => response.url().includes("kind=deposit") && response.url().includes("amount=50000"));
            await form.getByLabel("AMOUNT").fill("50000");
            await priced;
            const answered = page.waitForResponse((response) => response.url().endsWith("/investor/wallet/deposits") && response.request().method() === "POST");
            await form.getByRole("button", {name:"Confirm deposit", exact:true}).click({timeout: 10000});
            const recorded = await (await answered).json();
            if (recorded.code !== "DEPOSIT_INTENT_RECORDED") throw new Error("Deposit not recorded: " + recorded.code);
            return recorded.data.receipt.request_id;');

        // Context 1 shows it within one beacon interval, in the same document.
        $journey->code('cb1', '
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("RWF 50,000 not yet confirmed — not in the total").waitFor({timeout: 25000});
            await page.getByRole("region", {name:"Deposits"}).getByText("Not yet confirmed", {exact:true}).waitFor();
            if (await page.evaluate(() => window.__beacon) !== "same document") throw new Error("The page was fully reloaded");
            await shot('.$journey->shot('pending-from-beacon').');');

        // Offline, context 1 stops reading; the credit lands meanwhile.
        $reads = $journey->code('cb1', '
            await page.context().setOffline(true);
            await page.waitForTimeout(500);
            return await page.evaluate(() => window.__reads);');
        expect(Artisan::call('local:wallet', ['--event' => $request, '--state' => 'succeeded']))->toBe(0)
            ->and(Artisan::output())->toContain('credited once');

        $journey->code('cb1', '
            await page.waitForTimeout(12000);
            const offlineReads = await page.evaluate(() => window.__reads);
            if (offlineReads !== '.json_encode($reads).') throw new Error("The beacon read while offline");
            if ((await page.getByRole("region", {name:"Wallet total"}).textContent()).includes("AvailableRWF 50,000")) throw new Error("Updated while offline");
            await page.context().setOffline(false);
            await page.evaluate(() => window.dispatchEvent(new Event("online")));
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("No deposits waiting").waitFor({timeout: 25000});
            const text = await balance.textContent();
            if (!text.includes("AvailableRWF 50,000")) throw new Error("Not credited: " + text);
            if (await page.evaluate(() => window.__beacon) !== "same document") throw new Error("The page was fully reloaded");
            await noOverflow();
            await shot('.$journey->shot('credited-after-reconnect').');');
    });

    expect(WalletDepositCredit::query()->count())->toBe(1);
});
