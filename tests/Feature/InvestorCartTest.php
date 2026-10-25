<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\InvestorCart;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\ManageInvestorCart;
use App\Domain\Operations\CommandRejection;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorCartLine;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Investor cart (BRS v2.0 INV-R02) on real data: adding a live deal from Deals, changing a
 * line's units within what is left and the 50% limit (INV-R01), taking a line out, a filled deal
 * staying in the cart marked filled, and the In cart filter on Deals. Every command runs under
 * the Investor role and current identity context, on the web session and API v1 alike.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->as = fn (?User $user = null) => $this->actingAs($user ?? $this->investor['user']);
    $this->add = fn (string $units = '2', ?User $user = null, ?string $campaign = null) => ($this->as)($user)
        ->postJson(route('investor.cart_add', ['campaign' => $campaign ?? $this->campaign->id]), ['identity_context_revision' => 1, 'units' => $units]);
    $this->units = fn (string $units, ?string $campaign = null) => ($this->as)()
        ->postJson(route('investor.cart_units', ['campaign' => $campaign ?? $this->campaign->id]), ['identity_context_revision' => 1, 'units' => $units]);
    $this->remove = fn () => ($this->as)()->postJson(route('investor.cart_remove', ['campaign' => $this->campaign->id]), ['identity_context_revision' => 1]);
    $this->cart = fn (?User $user = null) => ($this->as)($user)->get(route('investor.cart'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('investor/cart'))->viewData('page')['props'];
    $this->deals = fn (array $query = []) => ($this->as)()->get(route('investor.deals', $query))->assertOk()->viewData('page')['props'];
});

it('adds a live deal, lists it with its card, capacity and one unit\'s return, and links its own checkout', function (): void {
    $campaign = $this->campaign->id;

    ($this->add)('2')->assertOk()->assertExactJson(['data' => ['campaign_id' => $campaign, 'units' => 2, 'count' => 1, 'in_cart' => true]]);
    $props = ($this->cart)();
    $line = $props['cart']['lines'][0];

    expect($props)->toMatchArray(['contract_version' => 'investor-primary-v1', 'identity_context_revision' => 1])
        ->and($props['links']['wallet'])->toBe(['url' => '/investor/wallet', 'method' => 'get'])
        ->and($props['cart']['lines'])->toHaveCount(1)
        ->and($line)->toMatchArray(['campaign_id' => $campaign, 'units' => '2', 'max_units' => '1080', 'binding' => 'raise_cap', 'filled' => false,
            'unit_return' => ['currency' => 'RWF', 'amount' => '555'], 'checkout' => ['url' => "/investor/deals/{$campaign}/checkout", 'method' => 'get'],
            'actions' => ['units' => ['url' => "/investor/cart/{$campaign}/units", 'method' => 'post'],
                'remove' => ['url' => "/investor/cart/{$campaign}/remove", 'method' => 'post']]])
        ->and($line['deal'])->toMatchArray(['campaign_id' => $campaign, 'lifecycle' => 'live', 'rate_pct' => '11.1', 'term_months' => 6,
            'links' => ['detail' => ['url' => "/investor/deals/{$campaign}", 'method' => 'get']]])
        ->and($line['deal'])->toHaveKeys(['name', 'industry', 'district', 'raised', 'target', 'avg_monthly_revenue', 'left_to_fill', 'units', 'unit_price'])
        ->and($line['deal'])->not->toHaveKeys(['updates', 'audit', 'financials', 'use_of_funds']);
});

it('adds again by changing the units, and changes a line within the 50% limit with the most allowed shown', function (): void {
    ($this->add)('2')->assertOk();
    ($this->add)('3')->assertOk()->assertJsonPath('data.units', 3)->assertJsonPath('data.count', 1);
    ($this->units)('1080')->assertOk()->assertExactJson(['data' => ['campaign_id' => $this->campaign->id, 'units' => 1080, 'count' => 1, 'in_cart' => true]]);
    ($this->units)('1081')->assertUnprocessable()->assertJsonPath('code', 'CART_UNITS_ABOVE_LIMIT')
        ->assertJsonPath('errors.units.0', 'The most you may take in this raise is 1080 notes.');
    ($this->add)('5000')->assertUnprocessable()->assertJsonPath('code', 'CART_UNITS_ABOVE_LIMIT');

    expect(InvestorCartLine::query()->sole()->units)->toBe(1080)
        ->and(($this->cart)()['cart']['lines'][0]['units'])->toBe('1080');
});

it('counts the units the Investor already holds against the 50% limit', function (): void {
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1080');

    ($this->add)('1')->assertUnprocessable()->assertJsonPath('errors.units.0', 'The most you may take in this raise is 0 notes.');
});

it('validates the units and identity context, and refuses a line that is not in the cart', function (): void {
    ($this->as)()->postJson(route('investor.cart_add', ['campaign' => $this->campaign->id]), ['units' => '0'])
        ->assertUnprocessable()->assertJsonValidationErrors(['units', 'identity_context_revision']);
    ($this->as)()->postJson(route('investor.cart_remove', ['campaign' => $this->campaign->id]), [])
        ->assertUnprocessable()->assertJsonValidationErrors('identity_context_revision');
    ($this->as)()->postJson(route('investor.cart_add', ['campaign' => $this->campaign->id]), ['identity_context_revision' => 7, 'units' => '1'])->assertStatus(409);
    ($this->units)('2')->assertNotFound()->assertJsonPath('code', 'CART_LINE_NOT_FOUND');
    ($this->add)('1', null, strtolower((string) str()->ulid()))->assertNotFound()->assertJsonPath('code', 'DEAL_NOT_FOUND');

    expect(InvestorCartLine::query()->count())->toBe(0);
});

it('takes a line out, and taking it out again changes nothing', function (): void {
    ($this->add)('2')->assertOk();

    ($this->remove)()->assertOk()->assertExactJson(['data' => ['campaign_id' => $this->campaign->id, 'removed' => true, 'count' => 0, 'in_cart' => false]]);
    ($this->remove)()->assertOk()->assertJsonPath('data.removed', false);

    expect(($this->cart)()['cart']['lines'])->toBe([]);
});

it('takes a deal out of the cart once its purchase is confirmed', function (): void {
    ($this->add)('3')->assertOk();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $initial = PrimaryReservationVersion::query()->sole()->payload;
    expect(InvestorCartLine::query()->count())->toBe(1);

    $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, $initial['terms']['disclosure_version'],
        $initial['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));

    expect(InvestorCartLine::query()->count())->toBe(0)
        ->and(($this->cart)()['cart']['lines'])->toBe([]);
});

it('keeps a deal that filled in the cart marked filled, out of checkout, and refuses to add or change it', function (): void {
    ($this->add)('2')->assertOk();
    foreach ([PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()] as $other) {
        PrimaryReservationFixture::reserve($this->campaign, $other, '1080');
    }
    $line = ($this->cart)()['cart']['lines'][0];

    expect($line)->toMatchArray(['units' => '2', 'filled' => true, 'max_units' => '0', 'checkout' => null,
        'actions' => ['units' => null, 'remove' => ['url' => "/investor/cart/{$this->campaign->id}/remove", 'method' => 'post']]])
        ->and($line['deal']['lifecycle'])->toBe('fully_reserved');

    ($this->units)('3')->assertConflict()->assertJsonPath('code', 'DEAL_FULLY_FUNDED');
    ($this->add)('1', PrimaryReservationFixture::investor()['user'])->assertConflict()->assertJsonPath('code', 'DEAL_FULLY_FUNDED');
    expect(($this->deals)()['cart'])->toBe(['campaign_ids' => [$this->campaign->id], 'add' => null,
        'remove' => ['url' => "/investor/cart/{$this->campaign->id}/remove", 'method' => 'post']]);
});

it('shows a closed deal in the cart from its card, marked filled', function (): void {
    ($this->add)('2')->assertOk();
    $live = app(InvestorDealCatalogue::class);
    app()->instance(InvestorDealCatalogue::class, new readonly class($live) implements InvestorDealCatalogue
    {
        public function __construct(private InvestorDealCatalogue $live) {}

        public function deals(): array
        {
            return [];
        }

        public function deal(string $campaignId): ?array
        {
            return null;
        }

        public function card(string $campaignId): ?array
        {
            return $this->live->card($campaignId);
        }

        public function updates(string $campaignId): array
        {
            return [];
        }
    });

    expect(($this->cart)()['cart']['lines'][0])->toMatchArray(['campaign_id' => $this->campaign->id, 'filled' => true, 'checkout' => null]);
});

it('offers Add to cart on the focused live deal, then Remove, and filters Deals to the cart', function (): void {
    $campaign = $this->campaign->id;
    $props = ($this->deals)();

    expect($props['cart'])->toBe(['campaign_ids' => [], 'add' => ['url' => "/investor/deals/{$campaign}/cart", 'method' => 'post'], 'remove' => null])
        ->and(array_column($props['sorts'], 'key'))->toContain('in_cart')
        ->and($props['sorts'][4]['link'])->toBe(['url' => '/investor/deals?sort=in_cart', 'method' => 'get'])
        ->and(($this->deals)(['sort' => 'in_cart'])['deals'])->toBe([]);

    ($this->add)('2')->assertOk();
    $props = ($this->deals)(['sort' => 'in_cart']);

    expect($props['cart'])->toBe(['campaign_ids' => [$campaign], 'add' => null, 'remove' => ['url' => "/investor/cart/{$campaign}/remove", 'method' => 'post']])
        ->and(array_column($props['deals'], 'campaign_id'))->toBe([$campaign])
        ->and($props['sorts'][4]['active'])->toBeTrue();
});

it('gives a person still being verified no cart and refuses their commands', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);

    expect(($this->cart)($person))->toMatchArray(['cart' => null])
        ->and(($this->cart)($person)['links']['wallet'])->toBeNull();
    $this->actingAs($person)->postJson(route('investor.cart_add', ['campaign' => $this->campaign->id]),
        ['identity_context_revision' => $person->refresh()->context_revision, 'units' => '1'])->assertForbidden()->assertJsonPath('code', 'IDENTITY_VERIFICATION_REQUIRED');
    expect(($this->actingAs($person)->get(route('investor.deals'))->viewData('page')['props'])['cart'])->toBeNull();
});

it('refuses to add while the account is restricted, and leaves the cart without checkout or Deals commands', function (): void {
    ($this->add)('2')->assertOk();
    InvestorAccountRestriction::factory()->create(['party_id' => $this->investor['party']->id, 'effective_at' => now()->subHour()->startOfSecond()]);

    ($this->add)('1')->assertConflict()->assertJsonPath('code', 'RESTRICTION_ACTIVE');
    $line = ($this->cart)()['cart']['lines'][0];

    expect($line)->toMatchArray(['filled' => false, 'max_units' => '0', 'binding' => 'restriction', 'checkout' => null])
        ->and($line['actions']['units'])->toBeNull()
        ->and(($this->deals)()['cart'])->toBeNull();
});

it('refuses a new line once the cart is full', function (): void {
    app()->instance(InvestorCart::class, new class implements InvestorCart
    {
        public function count(int $userId): int
        {
            return 0;
        }

        public function lines(int $userId, ?int $contextRevision): array
        {
            return [];
        }

        public function put(int $userId, int $contextRevision, string $campaignId, int $units, Closure $admit): array
        {
            $admit(false, InvestorCart::MAX_LINES);

            return ['campaign_id' => $campaignId, 'units' => $units, 'count' => 1];
        }

        public function remove(int $userId, int $contextRevision, string $campaignId): array
        {
            return ['campaign_id' => $campaignId, 'removed' => false, 'count' => 0];
        }
    });

    expect(fn () => app(ManageInvestorCart::class)->add($this->investor['user']->id, 1, $this->campaign->id, 1))
        ->toThrow(CommandRejection::class, 'CART_FULL');
});

it('runs the cart commands on API v1 with the command scope and shows API cart commands on Deals', function (): void {
    $campaign = $this->campaign->id;
    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->postJson(route('api.v1.investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => '1'])->assertForbidden();

    Sanctum::actingAs($this->investor['user'], ['investor:read', 'investor:command']);
    $this->postJson(route('api.v1.investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => '1'])
        ->assertOk()->assertJsonPath('data.in_cart', true);
    $this->postJson(route('api.v1.investor.cart_units', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => '4'])
        ->assertOk()->assertJsonPath('data.units', 4);
    $this->getJson(route('api.v1.investor.deals'))->assertOk()->assertJsonPath('data.cart.campaign_ids', [$campaign])
        ->assertJsonPath('data.cart.remove', ['url' => "/api/v1/investor/cart/{$campaign}/remove", 'method' => 'post']);
    $this->postJson(route('api.v1.investor.cart_remove', ['campaign' => $campaign]), ['identity_context_revision' => 1])
        ->assertOk()->assertJsonPath('data.removed', true);
});

it('counts the cart lines on every Investor page for the Cart tab, and nowhere else', function (): void {
    expect(($this->cart)()['cart_count'])->toBe(0);
    ($this->add)('2')->assertOk();

    expect(($this->cart)()['cart_count'])->toBe(1)
        ->and(($this->deals)()['cart_count'])->toBe(1);
    ($this->as)()->get(route('home'))->assertInertia(fn (Assert $page): Assert => $page->where('cart_count', null));
    expect(app(InvestorCart::class)->count(User::factory()->create()->id))->toBe(0);
});
