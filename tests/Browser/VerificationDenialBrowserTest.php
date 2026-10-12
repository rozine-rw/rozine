<?php

declare(strict_types=1);

use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\WalletDepositIntent;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\BrowserJourney;
use Tests\TestCase;

uses(TestCase::class, DatabaseTruncation::class);

it('explains an unverified wallet refusal and safely returns to the launcher across repeated mobile and desktop reads', function (): void {
    $password = 'Synthetic-verification-denial-42!';
    $user = User::factory()->create(['party_id' => Party::factory(), 'email' => 'verification-denial@example.test', 'password' => $password]);

    (new BrowserJourney('verification-denial', 8052))->within(function (BrowserJourney $journey) use ($password): void {
        $journey->login('vd1', 'verification-denial@example.test', $password, landing: '**/dashboard');
        $result = $journey->code('vd1', '
            const posts = [];
            page.on("request", request => {
                if (request.method() === "POST") posts.push(request.url());
            });
            for (const viewport of ['.BrowserJourney::PHONE.', '.BrowserJourney::DESKTOP.']) {
                await page.setViewportSize(viewport);
                const refused = await page.goto('.json_encode($journey->base.'/investor/wallet').');
                if (refused.status() !== 403) throw new Error("Wallet read was not refused");
                await page.getByRole("heading", {name:"Identity verification required", exact:true}).waitFor();
                if (!(await page.getByRole("alert").textContent()).includes("Your identity needs to be verified")) throw new Error("Wrong verification message");
                if (await page.getByText(/IDENTITY_VERIFICATION_REQUIRED|can no longer open/).count()) throw new Error("Support code or lost-access copy shown");
                const refreshed = await page.reload();
                if (refreshed.status() !== 403) throw new Error("Reload opened wallet access");
                await page.getByRole("heading", {name:"Identity verification required", exact:true}).waitFor();
                await noOverflow();
                await shot(viewport.width === 390 ? '.$journey->shot('phone').' : '.$journey->shot('desktop').');
                const recovery = page.getByRole("link", {name:"Choose an app", exact:true});
                if (await recovery.getAttribute("href") !== "/dashboard") throw new Error("Recovery route changed");
                await recovery.click();
                await page.waitForURL("**/dashboard");
                await page.getByRole("heading", {name:"Your apps", exact:true}).waitFor();
                await page.getByRole("link", {name:/Verify your identity/}).waitFor();
            }
            return posts;');
        expect($result)->toBe([]);
    });

    expect($user->refresh()->active_membership_id)->toBeNull()->and($user->context_revision)->toBe(0)
        ->and(RoleMembership::query()->count())->toBe(0)
        ->and(InvestorWallet::query()->count())->toBe(0)
        ->and(WalletDepositIntent::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe(0);
});
