<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Models\PrimaryReservationRecord;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The floating "Held for checkout" countdown: every Investor page but the checkout itself shares the
 * signed-in Investor's live holds, soonest to lapse first, each opening its deal's checkout.
 */

/**
 * @param  TestResponse<Response>  $response
 */
function sharedCheckoutHolds(TestResponse $response): mixed
{
    return $response->assertOk()->viewData('page')['props']['checkout_holds'];
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (string $units) {
        $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $this->travel(1)->seconds();

        return $result['data']['reservation_id'];
    };
});

it('shares the live holds soonest to lapse first, each linking to its checkout', function (): void {
    $first = ($this->reserve)('1');
    $released = ($this->reserve)('2');
    $latest = ($this->reserve)('3');
    $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $released, 1, (string) Str::uuid());

    $title = app(PrimaryCampaignSource::class)->presentation($this->campaign->id)['title'];
    $shared = sharedCheckoutHolds($this->actingAs($this->investor['user'])->get(route('investor.wallet')));
    $firstRecord = PrimaryReservationRecord::query()->whereKey($first)->sole();

    expect($shared['server_time'])->toBe(now()->toIso8601String())
        ->and(array_column($shared['holds'], 'reservation_id'))->toBe([$first, $latest])
        ->and($shared['holds'][0])->toBe(['reservation_id' => $first, 'deal_name' => $title, 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'units' => '1',
            'expires_at' => $firstRecord->expires_at->utc()->toIso8601String(),
            'link' => ['url' => '/investor/deals/'.$this->campaign->id.'/checkout?reservation='.$first, 'method' => 'get']]);

    expect(sharedCheckoutHolds($this->get(route('investor.checkout', ['campaign' => $this->campaign->id, 'reservation' => $first]))))->toBeNull();
    $this->get(route('home'))->assertInertia(fn (Assert $page): Assert => $page->where('checkout_holds', null));

    $this->travel(1)->days();
    expect(sharedCheckoutHolds($this->get(route('investor.wallet')))['holds'])->toBe([]);
});

it('shares no other Party\'s holds, and none for a user with no Party', function (): void {
    ($this->reserve)('1');

    $other = PrimaryReservationFixture::investor();
    expect(sharedCheckoutHolds($this->actingAs($other['user'])->get(route('investor.wallet')))['holds'])->toBe([])
        ->and(app(PrimaryPurchaseIndex::class)->activeHoldsOfUser(User::factory()->create()->id))->toBe([]);
});

it('sends the holds on a partial reload, so a hold released elsewhere drops without a full visit', function (): void {
    $held = ($this->reserve)('1');
    $this->actingAs($this->investor['user']);
    // The asset version the page was served with, so a build's manifest hash does not turn the partial reload into a 409.
    $version = (string) $this->get(route('investor.wallet'))->assertOk()->viewData('page')['version'];
    $partial = fn (): TestResponse => $this->get(route('investor.wallet'), ['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'investor/wallet',
        'X-Inertia-Partial-Data' => 'wallet', 'X-Inertia-Version' => $version])->assertOk();

    expect($partial()->json('props.checkout_holds.holds.*.reservation_id'))->toBe([$held]);

    $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $held, 1, (string) Str::uuid());
    expect($partial()->json('props.checkout_holds.holds'))->toBe([]);
});
