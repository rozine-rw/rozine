<?php

declare(strict_types=1);

use App\Models\InvestorFundingMethod;
use App\Models\LedgerEntry;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * S3-B in a real browser, against the live investor.wallet page: a synthetic Investor deposits,
 * sees the intent recorded but not credited, and only a signed synthetic provider success (the
 * local:wallet hook) credits the wallet, once. Also: a lost answer resolved by lookup, unknown and
 * failed outcomes, the section 11.4 hold that keeps deposits open, and a withdrawn policy that
 * closes them. No page, link or request ever touches /preview/.
 */

uses(TestCase::class, DatabaseTruncation::class);

const IW_PORT = 8036;

/** Seeds a synthetic Investor through the local hook and signs them in. */
function iwSignIn(BrowserJourney $journey, string $session, string $email): void
{
    expect(Artisan::call('local:wallet', ['--seed' => $email]))->toBe(0);
    $journey->login($session, $email, SyntheticWalletSeeder::PASSWORD);
}

/** Delivers a synthetic provider event for a recorded request through the local hook. */
function iwEvent(string $requestId, string $state): string
{
    expect(Artisan::call('local:wallet', ['--event' => $requestId, '--state' => $state]))->toBe(0);

    return Artisan::output();
}

/** Page code that fails on any /preview/ URL: the page, its links, and every request it made. */
function iwNoPreview(): string
{
    return '
            const hrefs = await page.locator("a[href]").evaluateAll((links) => links.map((link) => link.getAttribute("href")));
            const seen = [page.url(), ...hrefs, ...requested].filter((url) => url.includes("/preview/"));
            if (seen.length) throw new Error("Preview URL reached: " + seen.join(", "));';
}

/** Opens the wallet, prices an amount and confirms the deposit, returning its request id. */
function iwDeposit(BrowserJourney $journey, string $viewport, string $amount): string
{
    return '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.setViewportSize('.$viewport.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            if ('.json_encode($viewport === BrowserJourney::PHONE).') await page.getByRole("link", {name:"Deposit", exact:true}).click();
            const form = page.getByRole("form", {name:"Add money"});
            const priced = page.waitForResponse((response) => response.url().includes("kind=deposit") && response.url().includes("amount='.$amount.'"));
            await form.getByLabel("AMOUNT").fill('.json_encode($amount).');
            await priced;
            const answered = page.waitForResponse((response) => response.url().endsWith("/investor/wallet/deposits") && response.request().method() === "POST");
            await form.getByRole("button", {name:"Confirm deposit", exact:true}).click({timeout: 10000});
            const response = await answered;
            const sent = response.request().postDataJSON();';
}

it('records a deposit without crediting it and credits it once after a verified synthetic success', function (): void {
    (new BrowserJourney('wallet-deposit', IW_PORT))->within(function (BrowserJourney $journey): void {
        iwSignIn($journey, 'iw1', 'wallet-deposit@s3b.rozine.invalid');
        $request = $journey->code('iw1', iwDeposit($journey, BrowserJourney::PHONE, '50000').'
            const recorded = await response.json();
            if (recorded.code !== "DEPOSIT_INTENT_RECORDED" || recorded.data.receipt.request_id !== sent.request_id) throw new Error("Deposit not recorded: " + recorded.code);
            if ("party_id" in sent || "provider_reference" in sent) throw new Error("The page sent authority it must not");
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("RWF 50,000 not yet confirmed — not in the total").waitFor();
            if (!(await balance.textContent()).includes("AvailableRWF 0")) throw new Error("Credited before confirmation");
            await page.getByRole("region", {name:"Deposits"}).getByText("Not yet confirmed", {exact:true}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('pending-phone').');'.iwNoPreview().'
            return sent.request_id;');

        expect(LedgerEntry::query()->count())->toBe(0)
            ->and(iwEvent($request, 'succeeded'))->toContain('credited once');

        $journey->code('iw1', '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.reload();
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("No deposits waiting").waitFor();
            const text = await balance.textContent();
            if (!text.includes("RWF 50,000") || !text.includes("AvailableRWF 50,000")) throw new Error("Not credited: " + text);
            await page.getByRole("region", {name:"Deposits"}).getByRole("link", {name:/Deposit from MTN MoMo/}).click();
            await page.getByText("Credit receipt").waitFor();
            await noOverflow();
            await shot('.$journey->shot('credited-phone').');
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.reload();
            await page.getByText("Credit receipt").waitFor();
            await noOverflow();
            await shot('.$journey->shot('credited-desktop').');'.iwNoPreview());
    });

    expect(WalletDepositCredit::query()->count())->toBe(1)->and(LedgerEntry::query()->count())->toBe(1)
        ->and(WalletDepositCredit::query()->sole()->amount)->toBe('50000');
});

it('resolves a deposit whose answer was lost by looking it up, never sending it twice', function (): void {
    (new BrowserJourney('wallet-lost-answer', IW_PORT))->within(function (BrowserJourney $journey): void {
        iwSignIn($journey, 'iw2', 'wallet-lost@s3b.rozine.invalid');
        $answer = $journey->code('iw2', '
            const requested = [];
            const posts = [];
            const lookups = [];
            page.on("request", (request) => {
                requested.push(request.url());
                if (request.method() === "POST" && request.url().endsWith("/investor/wallet/deposits")) posts.push(request.postDataJSON().request_id);
                if (request.method() === "GET" && request.url().includes("/investor/wallet-operations/")) lookups.push(request.url());
            });
            /* The server records the deposit, but the browser never sees the answer. */
            await page.route("**/investor/wallet/deposits", async (route) => {
                await route.fetch();
                await route.abort("failed");
            }, {times: 1});
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const form = page.getByRole("form", {name:"Add money"});
            const priced = page.waitForResponse((response) => response.url().includes("amount=20000"));
            await form.getByLabel("AMOUNT").fill("20000");
            await priced;
            await form.getByRole("button", {name:"Confirm deposit", exact:true}).click({timeout: 10000});
            await page.getByRole("region", {name:"Wallet total"}).getByText("RWF 20,000 not yet confirmed — not in the total").waitFor({timeout: 20000});
            await noOverflow();
            await shot('.$journey->shot('recovered-desktop').');'.iwNoPreview().'
            return {posts, lookups};');
        expect($answer['posts'])->toHaveCount(1)->and($answer['lookups'])->not->toBeEmpty()
            ->and($answer['lookups'][0])->toContain('/investor/wallet-operations/'.$answer['posts'][0])->toContain('command=wallet.deposit');
    });

    expect(WalletDepositIntent::query()->count())->toBe(1)->and(LedgerEntry::query()->count())->toBe(0);
});

it('keeps unknown outcomes not yet confirmed and shows a verified failure as nothing credited', function (): void {
    (new BrowserJourney('wallet-outcomes', IW_PORT))->within(function (BrowserJourney $journey): void {
        iwSignIn($journey, 'iw3', 'wallet-outcomes@s3b.rozine.invalid');
        $unknown = $journey->code('iw3', iwDeposit($journey, BrowserJourney::PHONE, '30000').'
            return sent.request_id;');
        iwEvent($unknown, 'unknown');
        $journey->code('iw3', '
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            await page.getByRole("region", {name:"Deposits"}).getByText("Not yet confirmed — we\'re checking with the provider").waitFor();
            await page.getByRole("region", {name:"Wallet total"}).getByText("RWF 30,000 not yet confirmed — not in the total").waitFor();
            await shot('.$journey->shot('unknown-phone').');');
        expect(iwEvent($unknown, 'failed'))->toContain('nothing credited');
        $journey->code('iw3', '
            const requested = [];
            await page.reload();
            await page.getByRole("region", {name:"Deposits"}).getByText("Didn\'t go through — nothing credited").waitFor();
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("No deposits waiting").waitFor();
            if (!(await balance.textContent()).includes("AvailableRWF 0")) throw new Error("A failure credited money");
            await noOverflow();
            await shot('.$journey->shot('failed-phone').');'.iwNoPreview());
    });

    expect(WalletDepositCredit::query()->count())->toBe(0)->and(LedgerEntry::query()->count())->toBe(0);
});

it('keeps the deposit form for an Investor under the section 11.4 hold and accepts the intent without crediting it', function (): void {
    (new BrowserJourney('wallet-restricted', IW_PORT))->within(function (BrowserJourney $journey): void {
        iwSignIn($journey, 'iw4', 'wallet-restricted@s3b.rozine.invalid');
        expect(Artisan::call('local:wallet', ['--restrict' => 'wallet-restricted@s3b.rozine.invalid']))->toBe(0);
        $journey->code('iw4', iwDeposit($journey, BrowserJourney::PHONE, '10000').'
            const recorded = await response.json();
            if (recorded.code !== "DEPOSIT_INTENT_RECORDED") throw new Error("Restricted deposit refused: " + recorded.code);
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("Deposits still work.", {exact:false}).waitFor();
            await balance.getByText("RWF 10,000 not yet confirmed — not in the total").waitFor();
            await noOverflow();
            await shot('.$journey->shot('restricted-phone').');'.iwNoPreview());
    });

    expect(WalletDepositIntent::query()->count())->toBe(1)->and(LedgerEntry::query()->count())->toBe(0);
});

it('offers no deposit form without a policy and refuses a direct post POLICY_INPUT_REQUIRED', function (): void {
    (new BrowserJourney('wallet-no-policy', IW_PORT))->within(function (BrowserJourney $journey): void {
        iwSignIn($journey, 'iw5', 'wallet-no-policy@s3b.rozine.invalid');
        expect(Artisan::call('local:wallet', ['--no-policy' => true]))->toBe(0);
        $refused = $journey->code('iw5', '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            await page.getByText("Deposits aren\'t available yet: no deposit policy has been set.").waitFor();
            if (await page.getByRole("form", {name:"Add money"}).count() !== 0) throw new Error("Deposit form shown without a policy");
            await noOverflow();
            await shot('.$journey->shot('no-policy-desktop').');'.iwNoPreview().'
            return await page.evaluate(async (methodId) => {
                const token = decodeURIComponent(document.cookie.split("; ").find((cookie) => cookie.startsWith("XSRF-TOKEN=")).split("=")[1]);
                const response = await fetch("/investor/wallet/deposits", {method: "POST", headers: {"Content-Type": "application/json", "Accept": "application/json", "X-XSRF-TOKEN": token},
                    body: JSON.stringify({request_id: crypto.randomUUID(), identity_context_revision: 1, amount: {currency: "RWF", amount: "10000"}, method_id: methodId})});
                return {status: response.status, body: await response.json()};
            }, '.json_encode(InvestorFundingMethod::query()->sole()->id).');');
        expect($refused['status'])->toBe(409)->and($refused['body']['code'])->toBe('POLICY_INPUT_REQUIRED');
    });

    expect(WalletDepositIntent::query()->count())->toBe(0);
});
