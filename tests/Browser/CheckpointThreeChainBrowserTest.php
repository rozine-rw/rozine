<?php

declare(strict_types=1);

use App\Models\BusinessCampaign;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WalletDepositCredit;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

/*
 * The checkpoint 3 chain end to end in a real browser, on one published campaign:
 *
 *   Business Publish → Investor deposits credited (local:wallet) → reserve and confirm → full
 *   funding → staff authorize, then approve with step-up → synthetic payout succeeded → Holdings
 *   issued, in the Investor portfolio and on the Business campaign.
 *
 *   Failure branch: synthetic payout failed → failed closing → full refund to each wallet.
 *
 * Only the live prefix runs on dev today (Publish from #141/#146, deposits from #172; #175 and #176 are merged but gated). Every later
 * step is written against the UI it will drive and skipped with the exact dependency it waits for;
 * remove a skip only when that dependency is merged into dev. Two Investors each take half of the
 * raise, so the 50% single-investor cap (#99 C3) is met exactly and the last confirm funds it.
 * Synthetic evidence only: no real provider, destination or money, and no /preview/ page.
 */

uses(TestCase::class, DatabaseTruncation::class);

const C3_PASSWORD = 'Synthetic-c3-chain-browser-42!';
const C3_PORT = 8037;
const C3_MAKER_SECRET = 'JBSWY3DPEHPK3PXP';
const C3_CHECKER_SECRET = 'KRSXG5CTMVRXEZLU';
/** The synthetic deposit policy's maximum per deposit (local:wallet). */
const C3_DEPOSIT_MAXIMUM = '5000000';

const C3_AWAITS_RESERVE = 'awaiting activation of the Investor reserve/confirm HTTP commands (investor.primary.* web routes, checkout Resource and operation lookup): #175\'s checkout is merged but its routes stay gated until the #194 financial integration lands';

const C3_AWAITS_FUNDING = 'awaiting the gated Investor reserve/confirm routes (see C3_AWAITS_RESERVE): #175\'s full-funding transition and funded lock are merged, but no browser path reaches them yet';

const C3_AWAITS_APPROVAL = 'awaiting #194: #176\'s staff.disbursements.* routes and step-up are merged, but a disbursement only opens for a funded campaign through the concrete FundedCampaigns adapter (UnavailableFundedCampaigns is still bound)';

const C3_AWAITS_PAYOUT = 'awaiting #194: #176\'s local:disbursement hooks, disbursements:dispatch and disbursements:reconcile are merged; FundedCampaigns::issue on the concrete adapter is not';

const C3_AWAITS_HOLDINGS = 'awaiting #194: the Holding binding and issue-evidence migrations (#189 084737/114217) with #190\'s completeness guards, Holding writes from FundedCampaigns::issue, and the live Investor portfolio/holding Resources';

const C3_AWAITS_REFUND = 'awaiting #194: FundedCampaigns::failClose with authenticated forward failure settlement and fee-free WalletPostings::refund per commitment';

/**
 * A staff-released application its signatory can publish.
 *
 * @return array{publish: string, business: string, staff: User}
 */
function c3Released(TestCase $test): array
{
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $fixture['audit']['authority']['users'][0]->forceFill(['email' => 'c3-signatory@example.test', 'password' => C3_PASSWORD])->save();
    $application = $fixture['application'];
    $test->actingAs($fixture['audit']['staff'])->postJson(route('staff.applications.release', ['application' => $application->id]), [
        'request_id' => (string) Str::uuid(), 'application_id' => $application->id, 'expected_revision' => 0,
        'reason' => 'Verified current release gates.',
    ])->assertOk()->assertJsonPath('code', 'APPLICATION_RELEASED');

    return ['publish' => '/business/'.$fixture['audit']['business'].'/applications/'.$application->id.'/publish',
        'business' => $fixture['audit']['business'], 'staff' => $fixture['audit']['staff']];
}

/** Publishes from the sheet as the signatory and returns the live campaign's path. */
function c3Publish(BrowserJourney $journey, string $publish): string
{
    $journey->login('c3b', 'c3-signatory@example.test', C3_PASSWORD);
    $path = $journey->code('c3b', '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.$publish).');
            const answered = page.waitForResponse(response => response.url().endsWith("/publish") && response.request().method() === "POST");
            await page.getByRole("dialog", {name:"Publish to the Investor feed"}).getByRole("button", {name:"Publish", exact:true}).click();
            const published = await (await answered).json();
            if (published.code !== "LISTING_PUBLISHED") throw new Error("Publish failed: " + published.code);
            await page.goto('.json_encode($journey->base).' + published.data.next.url);
            await page.getByRole("dialog", {name:"Synthetic equipment purchase"}).getByText("Raising · live", {exact:false}).first().waitFor();
            await noOverflow();
            await shot('.$journey->shot('published-phone').');
            return published.data.next.url;');
    expect($path)->toBeString();

    return (string) $path;
}

/** Page code that fails on any /preview/ URL: the page, its links, and every request it made. */
function c3NoPreview(): string
{
    return '
            const hrefs = await page.locator("a[href]").evaluateAll((links) => links.map((link) => link.getAttribute("href")));
            const seen = [page.url(), ...hrefs, ...requested].filter((url) => url.includes("/preview/"));
            if (seen.length) throw new Error("Preview URL reached: " + seen.join(", "));';
}

/**
 * The browser deposits that credit this total, none larger than the synthetic policy's maximum.
 *
 * @param  numeric-string  $total
 * @return list<numeric-string>
 */
function c3Tranches(string $total): array
{
    $tranches = [];
    $left = $total;
    while (bccomp($left, '0') > 0) {
        $tranches[] = $tranche = bccomp($left, C3_DEPOSIT_MAXIMUM) > 0 ? C3_DEPOSIT_MAXIMUM : $left;
        $left = bcsub($left, $tranche);
    }

    return $tranches;
}

/**
 * Seeds an Investor through local:wallet and credits this total in browser deposits. Each deposit
 * is recorded but not credited until a verified synthetic success, which credits it once.
 *
 * @param  numeric-string  $total
 */
function c3Credit(BrowserJourney $journey, string $session, string $email, string $total): void
{
    expect(Artisan::call('local:wallet', ['--seed' => $email]))->toBe(0);
    $journey->login($session, $email, SyntheticWalletSeeder::PASSWORD);
    $credited = '0';
    foreach (c3Tranches($total) as $amount) {
        $request = $journey->code($session, '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const form = page.getByRole("form", {name:"Add money"});
            const priced = page.waitForResponse((response) => response.url().includes("kind=deposit") && response.url().includes("amount='.$amount.'"));
            await form.getByLabel("AMOUNT").fill('.json_encode($amount).');
            await priced;
            const answered = page.waitForResponse((response) => response.url().endsWith("/investor/wallet/deposits") && response.request().method() === "POST");
            await form.getByRole("button", {name:"Confirm deposit", exact:true}).click({timeout: 10000});
            const recorded = await (await answered).json();
            if (recorded.code !== "DEPOSIT_INTENT_RECORDED") throw new Error("Deposit not recorded: " + recorded.code);
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText('.json_encode(c3Rwf($amount).' not yet confirmed — not in the total').').waitFor();
            if (!(await balance.textContent()).includes('.json_encode('Available'.c3Rwf($credited)).')) throw new Error("Credited before confirmation");'.c3NoPreview().'
            return recorded.data.receipt.request_id;');
        expect($request)->toBeString()
            ->and(Artisan::call('local:wallet', ['--event' => $request, '--state' => 'succeeded']))->toBe(0)
            ->and(Artisan::output())->toContain('credited once');
        $credited = bcadd($credited, $amount);
    }
    $journey->code($session, '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.reload();
            const balance = page.getByRole("region", {name:"Wallet total"});
            await balance.getByText("No deposits waiting").waitFor();
            if (!(await balance.textContent()).includes('.json_encode('Available'.c3Rwf($total)).')) throw new Error("Not credited: " + await balance.textContent());
            await noOverflow();
            await shot('.$journey->shot($session.'-credited-desktop').');'.c3NoPreview());
}

/** An exact whole-franc amount as the wallet draws it. */
function c3Rwf(string $amount): string
{
    return 'RWF '.number_format((int) $amount);
}

/**
 * The live prefix: Publish, then two Investors credited with half the raise each.
 *
 * @return array{campaign: string, business: string, staff: User, half: string, units: int}
 */
function c3Prefix(TestCase $test, BrowserJourney $journey): array
{
    ['publish' => $publish, 'business' => $business, 'staff' => $staff] = c3Released($test);
    $campaign = c3Publish($journey, $publish);
    $principal = BusinessCampaign::query()->sole()->principal;
    $half = bcdiv($principal, '2', 0);
    expect(bcmul($half, '2', 0))->toBe($principal);
    c3Credit($journey, 'c3i1', 'c3-investor-one@s3b.rozine.invalid', $half);
    c3Credit($journey, 'c3i2', 'c3-investor-two@s3b.rozine.invalid', $half);

    return ['campaign' => $campaign, 'business' => $business, 'staff' => $staff, 'half' => $half, 'units' => (int) bcdiv($half, '5000', 0)];
}

/** Reserves and confirms this Investor's units from Deals → deal → Invest, returning the confirm body. */
function c3ReserveAndConfirm(BrowserJourney $journey, string $session, int $units): mixed
{
    return $journey->code($session, '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor').');
            await page.getByRole("link", {name:"Synthetic equipment purchase"}).first().click();
            await page.getByRole("link", {name:"Invest", exact:true}).click();
            /* The sheet steps one note at a time and re-quotes through ?units=, so ask for the half directly. */
            const quoting = new URL(page.url());
            quoting.searchParams.set("units", '.json_encode((string) $units).');
            await page.goto(quoting.toString());
            await page.locator("output[aria-label=\"INVESTMENT AMOUNT\"]", {hasText:'.json_encode(number_format($units * 5000)).'}).waitFor();
            await page.getByRole("button", {name:/^Reserve · /}).click();
            await page.getByRole("checkbox").check();
            const confirmed = page.waitForResponse((response) => response.url().includes("/confirm") && response.request().method() === "POST");
            await page.getByRole("button", {name:/^Confirm · /}).click();
            const response = await confirmed;
            const sent = response.request().postDataJSON();
            const body = await response.json();
            if (body.code !== "PRIMARY_COMMITTED") throw new Error("Confirm not committed: " + body.code);
            if (!sent.disclosure_version || !sent.disclosure_sha256) throw new Error("Confirm sent without the disclosure binding");
            await page.getByText("Committed — issued after disbursement").waitFor();
            await noOverflow();
            await shot('.$journey->shot($session.'-committed-phone').');'.c3NoPreview().'
            return body;');
}

/** Both Investors reserve and confirm their half; the second confirm funds the raise. */
function c3Fund(BrowserJourney $journey, int $units): void
{
    c3ReserveAndConfirm($journey, 'c3i1', $units);
    c3ReserveAndConfirm($journey, 'c3i2', $units);
}

/**
 * Opens the funded campaign's disbursement, authorizes it as a treasury maker and approves it with
 * step-up as a different approver, returning the recorded payout intent's id.
 */
function c3Approve(BrowserJourney $journey): string
{
    $maker = User::factory()->create(['email' => 'c3-maker@example.test', 'password' => C3_PASSWORD,
        'two_factor_secret' => encrypt(C3_MAKER_SECRET), 'two_factor_confirmed_at' => now()->subMinute()]);
    $checker = User::factory()->create(['email' => 'c3-checker@example.test', 'password' => C3_PASSWORD,
        'two_factor_secret' => encrypt(C3_CHECKER_SECRET), 'two_factor_confirmed_at' => now()->subMinute()]);
    expect(Artisan::call('identity:staff', ['user' => $maker->id, '--role' => ['treasury'], '--reason' => 'Synthetic C3 maker.']))->toBe(0)
        ->and(Artisan::call('identity:staff', ['user' => $checker->id, '--role' => ['approver'], '--reason' => 'Synthetic C3 checker.']))->toBe(0)
        /* The scheduled reconciler also opens newly funded campaigns (#176). */
        ->and(Artisan::call('disbursements:reconcile'))->toBe(0);
    $journey->login('c3m', 'c3-maker@example.test', C3_PASSWORD, C3_MAKER_SECRET);
    $authorized = $journey->code('c3m', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/admin/disbursements').');
            await page.getByRole("link", {name:/Synthetic equipment purchase/}).first().click();
            const drawer = page.getByRole("dialog");
            await drawer.getByRole("button", {name:"Authorize release", exact:true}).click();
            await drawer.getByRole("checkbox").check();
            await drawer.getByRole("textbox").fill("Funded raise checked against the published terms.");
            const answered = page.waitForResponse((response) => response.url().endsWith("/authorize") && response.request().method() === "POST");
            await page.getByRole("button", {name:"Authorize", exact:true}).click();
            const body = await (await answered).json();
            await noOverflow();
            await shot('.$journey->shot('authorized-desktop').');
            return body.code;');
    $journey->login('c3c', 'c3-checker@example.test', C3_PASSWORD, C3_CHECKER_SECRET);
    /* The login consumed this window's code: step-up needs the next one. */
    sleep(30 - (time() % 30) + 1);
    $approved = $journey->code('c3c', '
            await page.setViewportSize('.BrowserJourney::DESKTOP.');
            await page.goto('.json_encode($journey->base.'/admin/disbursements').');
            await page.getByRole("link", {name:/Synthetic equipment purchase/}).first().click();
            const drawer = page.getByRole("dialog");
            await drawer.getByRole("button", {name:"Approve release", exact:true}).click();
            await drawer.getByLabel("Six-digit authenticator code").fill('.json_encode($journey->otp(C3_CHECKER_SECRET)).');
            await drawer.getByRole("button", {name:"Confirm code", exact:true}).click();
            await drawer.getByRole("checkbox").check();
            await drawer.getByRole("textbox").last().fill("Second approver: amount and destination match.");
            const answered = page.waitForResponse((response) => response.url().endsWith("/approve") && response.request().method() === "POST");
            await drawer.getByRole("button", {name:"Approve and record intent", exact:true}).click();
            const response = await answered;
            if (!response.request().postDataJSON().step_up_proof) throw new Error("Approve sent without its step-up proof");
            const body = await response.json();
            await noOverflow();
            await shot('.$journey->shot('approved-desktop').');
            return body.code;');
    expect($authorized)->toBe('DISBURSEMENT_AUTHORIZED')->and($approved)->toBe('DISBURSEMENT_INTENT_RECORDED')
        ->and(DB::table('disbursement_intents')->count())->toBe(1);

    return (string) DB::table('disbursement_intents')->value('id');
}

/** Sends the approved payout through the synthetic provider and reconciles a final outcome. */
function c3Payout(string $intent, string $state): void
{
    expect(Artisan::call('local:disbursement', ['--script-send' => 'ack']))->toBe(0)
        ->and(Artisan::call('disbursements:dispatch', ['--intent' => $intent]))->toBe(0)
        ->and(Artisan::call('local:disbursement', ['--event' => $intent, '--state' => $state]))->toBe(0)
        ->and(Artisan::call('disbursements:reconcile'))->toBe(0);
}

it('publishes a released campaign and credits two Investors once each through the synthetic deposit hook', function (): void {
    (new BrowserJourney('c3-chain', C3_PORT))->within(function (BrowserJourney $journey): void {
        c3Prefix($this, $journey);
    });

    /* Every verified success credited exactly once, and together the wallets hold the whole raise. */
    $half = bcdiv(BusinessCampaign::query()->sole()->principal, '2', 0);
    $expected = [...c3Tranches($half), ...c3Tranches($half)];
    expect(WalletDepositCredit::query()->pluck('amount')->all())->toEqualCanonicalizing($expected)
        ->and(LedgerEntry::query()->count())->toBe(count($expected))
        ->and(BusinessCampaign::query()->count())->toBe(1);
});

it('reserves and confirms each Investor\'s half of the raise, leaving it committed and awaiting issue', function (): void {
    (new BrowserJourney('c3-reserve', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units] = c3Prefix($this, $journey);
        c3ReserveAndConfirm($journey, 'c3i1', $units);
        $journey->code('c3i1', '
            await page.goto('.json_encode($journey->base.'/investor/portfolio').');
            await page.getByText("Awaiting issue", {exact:true}).waitFor();
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const balance = page.getByRole("region", {name:"Wallet total"});
            if (!(await balance.textContent()).includes("AvailableRWF 0")) throw new Error("Committed cash still available");');
    });
})->skip(C3_AWAITS_RESERVE);

it('funds the raise with the last confirm and locks it against cancel and further reserves', function (): void {
    (new BrowserJourney('c3-funded', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units, 'campaign' => $campaign] = c3Prefix($this, $journey);
        c3Fund($journey, $units);
        $journey->code('c3b', '
            await page.goto('.json_encode($journey->base.$campaign).');
            await page.getByText("Fully funded on", {exact:false}).first().waitFor();
            if (await page.getByRole("button", {name:"Cancel this raise", exact:true}).count() !== 0) throw new Error("Cancel offered on a funded raise");
            await noOverflow();
            await shot('.$journey->shot('funded-phone').');');
        $journey->code('c3i1', '
            await page.goto('.json_encode($journey->base.'/investor/portfolio').');
            await page.getByText("Fully funded — awaiting payout").first().waitFor();');
    });
})->skip(C3_AWAITS_FUNDING);

it('authorizes the funded disbursement as the maker and approves it with step-up as a different checker', function (): void {
    (new BrowserJourney('c3-approve', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units] = c3Prefix($this, $journey);
        c3Fund($journey, $units);
        c3Approve($journey);
    });

    /* Queued, not paid: nothing is dispatched, issued or refunded before the worker's recheck. */
    expect(DB::table('disbursement_provider_calls')->count())->toBe(0)
        ->and(DB::table('primary_holdings')->count())->toBe(0);
})->skip(C3_AWAITS_APPROVAL);

it('dispatches the approved payout, reconciles a synthetic success and issues each Holding once', function (): void {
    (new BrowserJourney('c3-payout', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units] = c3Prefix($this, $journey);
        c3Fund($journey, $units);
        c3Payout(c3Approve($journey), 'succeeded');
    });

    expect(DB::table('disbursement_provider_calls')->where('kind', 'send')->count())->toBe(1)
        ->and(DB::table('disbursement_closings')->where('kind', 'issued')->count())->toBe(1)
        ->and(DB::table('primary_holdings')->count())->toBe(2);
})->skip(C3_AWAITS_PAYOUT);

it('shows the issued Holdings in each Investor portfolio and the disbursement on the Business campaign', function (): void {
    (new BrowserJourney('c3-holdings', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units, 'campaign' => $campaign] = c3Prefix($this, $journey);
        c3Fund($journey, $units);
        c3Payout(c3Approve($journey), 'succeeded');
        foreach (['c3i1', 'c3i2'] as $session) {
            $journey->code($session, '
            const requested = [];
            page.on("request", (request) => requested.push(request.url()));
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor/portfolio').');
            if (await page.getByText("Awaiting issue", {exact:true}).count() !== 0) throw new Error("Commitment still awaiting issue");
            await page.getByRole("link", {name:/Synthetic equipment purchase/}).first().click();
            await page.getByRole("region", {name:"Issue record"}).waitFor();
            await noOverflow();
            await shot('.$journey->shot($session.'-holding-phone').');'.c3NoPreview());
        }
        $journey->code('c3b', '
            await page.goto('.json_encode($journey->base.$campaign).');
            await page.getByText("was disbursed to", {exact:false}).waitFor();
            await noOverflow();
            await shot('.$journey->shot('disbursed-phone').');');
    });
})->skip(C3_AWAITS_HOLDINGS);

it('closes a raise whose payout failed and refunds every commitment in full to the wallets', function (): void {
    (new BrowserJourney('c3-failed', C3_PORT))->within(function (BrowserJourney $journey): void {
        ['units' => $units, 'campaign' => $campaign, 'half' => $half] = c3Prefix($this, $journey);
        c3Fund($journey, $units);
        c3Payout(c3Approve($journey), 'failed');
        $journey->code('c3b', '
            await page.goto('.json_encode($journey->base.$campaign).');
            await page.getByText("This raise closed without paying out").waitFor();
            await noOverflow();
            await shot('.$journey->shot('failed-closing-phone').');');
        foreach (['c3i1', 'c3i2'] as $session) {
            $journey->code($session, '
            await page.setViewportSize('.BrowserJourney::PHONE.');
            await page.goto('.json_encode($journey->base.'/investor/portfolio').');
            await page.getByText("Closed — refunded").first().waitFor();
            await page.goto('.json_encode($journey->base.'/investor/wallet').');
            const balance = page.getByRole("region", {name:"Wallet total"});
            if (!(await balance.textContent()).includes('.json_encode('Available'.c3Rwf($half)).')) throw new Error("Not refunded in full");
            await noOverflow();
            await shot('.$journey->shot($session.'-refunded-phone').');');
        }
    });

    expect(DB::table('disbursement_closings')->where('kind', 'failed_closing')->count())->toBe(1)
        ->and(DB::table('primary_holdings')->count())->toBe(0);
})->skip(C3_AWAITS_REFUND);
