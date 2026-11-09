<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\BrowserJourney;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('navigates wallet history by keyboard, filters and browser history', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $investor = PrimaryReservationFixture::investor();
    $investor['user']->forceFill(['email' => 'wallet-history@rozine.invalid', 'password' => Hash::make('WalletTestOnly123!')])->save();
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    foreach (range(1, 24) as $index) {
        $deposit = InvestorWalletFixture::deposit($investor, '1000');
        InvestorWalletFixture::settle($deposit['data']['intent_id']);
        $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    }
    (new BrowserJourney('wallet-history', 8047))->within(function (BrowserJourney $journey): void {
        $journey->login('wh1', 'wallet-history@rozine.invalid', 'WalletTestOnly123!', '**/investor/deals');
        $result = $journey->code('wh1', '
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const history = page.getByRole("region", {name:"Transactions"});
            await history.getByRole("button", {name:"Show more"}).focus();
            await page.keyboard.press("Enter");
            await history.getByRole("link", {name:"Show older"}).focus();
            await page.keyboard.press("Enter");
            await page.waitForURL(/before=/);
            await page.goBack();
            await page.waitForURL(/investor\/wallet$/);
            await history.getByRole("link", {name:"Holds and commitments"}).click();
            await page.waitForURL(/movement=internal/);
            await page.goBack();
            await page.waitForURL(/investor\/wallet$/);
            await page.goForward();
            await page.waitForURL(/movement=internal/);
            await history.getByRole("button", {name:"Show more"}).click();
            await page.route("**/investor/wallet?**before=**", async (route) => {
                await new Promise((resolve) => setTimeout(resolve, 800));
                await route.fulfill({status:503, contentType:"text/plain", body:"Service unavailable"});
            });
            const failed = page.waitForResponse((response) => response.url().includes("before=") && response.status() === 503);
            await history.getByRole("link", {name:"Show older"}).click({noWaitAfter:true});
            await page.waitForTimeout(400);
            const loadingVisible = await history.getByRole("status").getByText("Loading transaction history…").isVisible();
            if (!loadingVisible || await history.locator("ul[aria-busy=true]").count() !== 1) throw new Error("Missing wallet history loading feedback");
            await failed;
            await history.getByText("Loading transaction history…").waitFor({state:"hidden"});
            if (await history.locator("ul[aria-busy=true]").count() !== 0) throw new Error("Wallet history remained busy after failure");
            await page.getByRole("alert").getByText(/Rozine couldn.t load this just now/).waitFor();
            const failedUrl = page.url();
            await page.unroute("**/investor/wallet?**before=**");
            await page.getByRole("button", {name:"Try again"}).click();
            await page.getByRole("alert").waitFor({state:"hidden"});
            if (await history.getByRole("button", {name:"Show more"}).isVisible()) await history.getByRole("button", {name:"Show more"}).click();
            await history.getByRole("link", {name:"Show older"}).click();
            await page.waitForURL(/before=/);
            return {failedUrl, retryUrl: page.url(), loadingVisible};
        ');
        expect($result['failedUrl'])->toContain('movement=internal')->not->toContain('before=')
            ->and($result['retryUrl'])->toContain('before=')
            ->and($result['loadingVisible'])->toBeTrue();
    });
});
