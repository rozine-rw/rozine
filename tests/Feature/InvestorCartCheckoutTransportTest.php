<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Primary\RiskStatements;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorCartCheckout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The whole-cart checkout transport (#383 step 5, contract cart-checkout-1 §5): quote the saved cart,
 * hold every line at once, read the held checkout, confirm or release it as a whole and look a
 * command up by its own request id, on the web session and API v1 alike. The cart page carries the
 * live checkout and the commands the Investor may send now.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $this->second = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->user = $this->investor['user'];
    $this->as = fn () => $this->actingAs($this->user);
    $this->add = fn (string $campaign, string $units) => ($this->as)()
        ->postJson(route('investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => $units])->assertOk();
    $this->acknowledge = fn () => ($this->as)()->postJson(route('investor.risks.acknowledge'), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
    $this->filled = function (): void {
        ($this->add)($this->campaign->id, '2');
        ($this->add)($this->second->id, '3');
    };
    $this->quote = fn (int $revision = 2) => ($this->as)()->postJson(route('investor.cart_quote'), ['identity_context_revision' => 1, 'expected_cart_revision' => $revision]);
    $this->reserve = fn (array $quote, ?string $requestId = null) => ($this->as)()->postJson(route('investor.cart_reserve'), [
        'request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_cart_revision' => $quote['cart_revision'],
        'quote_id' => $quote['id'], 'expected_quote_revision' => $quote['revision'], 'quote_disclosure_sha256' => $quote['disclosure_sha256']]);
    // Runs the deferred checks a commit would, then defers again: the next command is a new request.
    $this->committed = function (): void {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    };
    $this->held = function (): array {
        ($this->filled)();
        ($this->acknowledge)();
        $quote = ($this->quote)()->assertOk()->json('data');
        $requestId = (string) Str::uuid();
        $reserved = ($this->reserve)($quote, $requestId)->assertOk()->json();
        ($this->committed)();

        return ['quote' => $quote, 'reserved' => $reserved, 'request_id' => $requestId, 'checkout' => (string) $reserved['data']['receipt']['checkout_id']];
    };
    $this->page = fn () => ($this->as)()->get(route('investor.cart'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('investor/cart'))->viewData('page')['props'];
    $this->lookup = fn (string $requestId, array $query) => ($this->as)()
        ->getJson(route('investor.cart_operation', ['request_id' => $requestId, 'identity_context_revision' => 1, ...$query]));
});

it('quotes the whole saved cart under one band, names the next band and holds nothing', function (): void {
    ($this->filled)();
    ($this->acknowledge)();

    $response = ($this->quote)()->assertOk();

    expect($response->json())->toMatchArray(['operation_id' => null, 'status' => 'completed', 'code' => 'CART_QUOTED', 'revision' => 1,
        'policy_version' => 'cart-checkout-1'])
        ->and($response->json('data'))->toMatchArray(['cart_revision' => 2, 'principal' => ['currency' => 'RWF', 'amount' => '25000'],
            'next_fee_band' => ['tier' => 'bronze', 'rate_bps' => 1200, 'rate_pct' => '12.00']])
        ->and($response->json('data.fee_band'))->toMatchArray(['tier' => 'standard', 'rate_pct' => '15.00',
            'next_band_distance' => ['currency' => 'RWF', 'amount' => '975000']])
        ->and($response->json('data.lines'))->toHaveCount(2)
        ->and(DB::table('primary_reservations')->count())->toBe(0);
});

it('names no next band from the top band', function (): void {
    app()->instance(HeldNoteCapital::class, new class implements HeldNoteCapital
    {
        public function current(string $partyId): array
        {
            return ['outstanding_principal' => '150000000', 'revision' => 4, 'sha256' => str_repeat('c', 64), 'complete' => true, 'as_of' => '2026-11-01T00:00:00Z'];
        }
    });
    ($this->filled)();
    ($this->acknowledge)();

    expect(($this->quote)()->assertOk()->json('data'))->toMatchArray(['next_fee_band' => null])
        ->and(($this->quote)()->json('data.fee_band.tier'))->toBe('platinum');
});

it('answers a refused quote with its code, status and facts', function (): void {
    ($this->filled)();

    ($this->quote)()->assertConflict()->assertJson(['operation_id' => null, 'status' => 'rejected', 'code' => 'RISK_ACKNOWLEDGEMENT_REQUIRED', 'data' => null]);

    ($this->acknowledge)();
    ($this->quote)(1)->assertConflict()->assertJson(['status' => 'rejected', 'code' => 'CART_REVISION_CONFLICT', 'revision' => 2]);
});

it('answers a quote above the 50% limit with the most each line may take', function (): void {
    ($this->add)($this->campaign->id, '1080');
    ($this->add)($this->second->id, '1');
    ($this->acknowledge)();
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1');

    ($this->quote)()->assertUnprocessable()->assertJson(['status' => 'rejected', 'code' => 'CART_UNITS_ABOVE_LIMIT',
        'data' => ['max_units' => [$this->campaign->id => '1079']]])->assertJsonValidationErrors('lines');
});

it('validates the cart commands before sending them', function (): void {
    ($this->as)()->postJson(route('investor.cart_quote'), ['identity_context_revision' => 1])->assertUnprocessable()
        ->assertJsonValidationErrors('expected_cart_revision');
    ($this->as)()->postJson(route('investor.cart_reserve'), ['identity_context_revision' => 1, 'request_id' => 'nope', 'expected_cart_revision' => 2,
        'quote_id' => 'nope', 'expected_quote_revision' => 1, 'quote_disclosure_sha256' => 'ABC'])->assertUnprocessable()
        ->assertJsonValidationErrors(['request_id', 'quote_id', 'quote_disclosure_sha256']);
    $checkout = strtolower((string) Str::ulid());
    ($this->as)()->postJson(route('investor.cart_confirm', ['checkout' => $checkout]), ['identity_context_revision' => 1, 'request_id' => (string) Str::uuid(),
        'checkout_id' => strtolower((string) Str::ulid()), 'expected_revision' => 1, 'quote_id' => strtolower((string) Str::ulid()), 'expected_quote_revision' => 1,
        'acknowledged_disclosure_sha256' => str_repeat('a', 64)])->assertUnprocessable()->assertJsonValidationErrors(['checkout_id', 'acknowledged']);
    ($this->as)()->postJson(route('investor.cart_release', ['checkout' => $checkout]), ['identity_context_revision' => 1, 'request_id' => (string) Str::uuid()])
        ->assertUnprocessable()->assertJsonValidationErrors('expected_revision');
    ($this->lookup)((string) Str::uuid(), ['command' => 'primary.reserve'])->assertUnprocessable()->assertJsonValidationErrors('command');
    ($this->lookup)((string) Str::uuid(), ['command' => 'cart.confirm'])->assertUnprocessable()->assertJsonValidationErrors('checkout');
    ($this->as)()->getJson(route('investor.cart_reservation', ['checkout' => $checkout]))->assertUnprocessable()
        ->assertJsonValidationErrors('identity_context_revision');
});

it('holds the whole cart, opens the cart page on the held checkout and finds the reserve by its request id', function (): void {
    $props = ($this->page)();
    expect($props)->toMatchArray(['checkout' => null, 'actions' => ['quote' => ['url' => '/investor/cart/quote', 'method' => 'post'],
        'reserve' => ['url' => '/investor/cart/reservations', 'method' => 'post']],
        'operation' => ['url' => '/investor/cart/operations/{request_id}', 'method' => 'get']]);

    ['quote' => $quote, 'reserved' => $reserved, 'request_id' => $requestId, 'checkout' => $checkout] = ($this->held)();

    expect($reserved)->toMatchArray(['status' => 'completed', 'code' => 'CART_RESERVED'])
        ->and($reserved['data'])->toMatchArray(['current' => null, 'next' => null])
        ->and($reserved['data']['receipt'])->toMatchArray(['checkout_id' => $checkout, 'quote_id' => $quote['id'], 'state' => 'held']);

    $props = ($this->page)();
    expect($props['checkout'])->toMatchArray(['id' => $checkout, 'revision' => 1, 'state' => 'held', 'total_held' => ['currency' => 'RWF', 'amount' => '25000'],
        'actions' => ['confirm' => ['url' => "/investor/cart/reservations/{$checkout}/confirm", 'method' => 'post'],
            'release' => ['url' => "/investor/cart/reservations/{$checkout}/release", 'method' => 'post']]])
        ->and($props['checkout']['quote']['id'])->toBe($quote['id'])
        ->and($props['actions'])->toBe(['quote' => null, 'reserve' => null])
        ->and(array_column($props['cart']['lines'], 'checkout'))->toBe([null, null])
        ->and(array_column(array_column($props['cart']['lines'], 'actions'), 'units'))->toBe([null, null]);

    expect(($this->lookup)($requestId, ['command' => 'cart.reserve'])->assertOk()->json())->toMatchArray(['code' => 'CART_RESERVED',
        'operation_id' => $reserved['operation_id']])
        ->and(($this->as)()->getJson(route('investor.cart_reservation', ['checkout' => $checkout, 'identity_context_revision' => 1]))->assertOk()
            ->json('data'))->toMatchArray(['id' => $checkout, 'state' => 'held', 'revision' => 1]);
    ($this->lookup)((string) Str::uuid(), ['command' => 'cart.reserve'])->assertNotFound()->assertJson(['code' => 'OPERATION_NOT_FOUND']);
});

it('reads each held line at the return and fee its own reservation fixed, not the indicative quote', function (): void {
    ['checkout' => $checkout] = ($this->held)();

    $data = ($this->as)()->getJson(route('investor.cart_reservation', ['checkout' => $checkout, 'identity_context_revision' => 1]))->assertOk()->json('data');
    $return = 0;
    $fee = 0;
    foreach ($data['lines'] as $line) {
        $facts = DB::transaction(fn (): array => app(PrimaryReservations::class)
            ->reservationFacts($line['campaign_id'], $this->investor['party']->id, $line['reservation_id']));
        $return += (int) $facts['rights']['total_return']['amount'];
        $fee += (int) $facts['terms']['payout_fee']['amount'];
        expect($line)->toMatchArray(['contractual_return' => ['currency' => 'RWF', 'amount' => $facts['rights']['total_return']['amount']],
            'earnings_fee' => ['currency' => 'RWF', 'amount' => $facts['terms']['payout_fee']['amount']],
            'net_return' => ['currency' => 'RWF', 'amount' => (string) ((int) $facts['rights']['total_return']['amount'] - (int) $facts['terms']['payout_fee']['amount'])],
            'disclosure_sha256' => $facts['disclosure_sha256']]);
    }

    expect($data['reserved'])->toBe(['contractual_return' => ['currency' => 'RWF', 'amount' => (string) $return],
        'earnings_fee' => ['currency' => 'RWF', 'amount' => (string) $fee], 'net_return' => ['currency' => 'RWF', 'amount' => (string) ($return - $fee)]])
        ->and(($this->page)()['checkout']['reserved'])->toBe($data['reserved']);
});

it('confirms the held cart as a whole and finds the confirmation by its request id and checkout', function (): void {
    ['quote' => $quote, 'checkout' => $checkout] = ($this->held)();
    $requestId = (string) Str::uuid();

    $confirmed = ($this->as)()->postJson(route('investor.cart_confirm', ['checkout' => $checkout]), ['request_id' => $requestId,
        'identity_context_revision' => 1, 'checkout_id' => $checkout, 'expected_revision' => 1, 'quote_id' => $quote['id'], 'expected_quote_revision' => 1,
        'acknowledged_disclosure_sha256' => $quote['disclosure_sha256'], 'acknowledged' => true])->assertOk()->json();
    ($this->committed)();

    expect($confirmed)->toMatchArray(['status' => 'completed', 'code' => 'CART_CONFIRMED'])
        ->and($confirmed['data']['receipt'])->toMatchArray(['checkout_id' => $checkout, 'total_principal' => ['currency' => 'RWF', 'amount' => '25000']])
        ->and(($this->lookup)($requestId, ['command' => 'cart.confirm', 'checkout' => $checkout])->assertOk()->json('operation_id'))
        ->toBe($confirmed['operation_id'])
        ->and(($this->page)())->toMatchArray(['checkout' => null])
        ->and(($this->page)()['cart']['lines'])->toBe([]);
});

it('releases the held cart as a whole, and offers neither command once the hold has ended', function (): void {
    ['checkout' => $checkout] = ($this->held)();
    $this->travelTo(InvestorCartCheckout::query()->whereKey($checkout)->sole()->expires_at);

    expect(($this->page)()['checkout'])->toMatchArray(['state' => 'release_pending', 'actions' => ['confirm' => null, 'release' => null]]);

    $this->travelBack();
    $this->freezeSecond();
    $requestId = (string) Str::uuid();
    $released = ($this->as)()->postJson(route('investor.cart_release', ['checkout' => $checkout]), ['request_id' => $requestId,
        'identity_context_revision' => 1, 'expected_revision' => 1])->assertOk()->json();
    ($this->committed)();

    expect($released)->toMatchArray(['status' => 'completed', 'code' => 'CART_RELEASED'])
        ->and(($this->lookup)($requestId, ['command' => 'cart.release', 'checkout' => $checkout])->assertOk()->json('code'))->toBe('CART_RELEASED')
        ->and(($this->page)()['checkout'])->toBeNull();
});

it('offers no whole-cart command to a restricted account', function (): void {
    ($this->filled)();
    InvestorAccountRestriction::factory()->create(['party_id' => $this->investor['party']->id, 'effective_at' => now()->subHour()->startOfSecond()]);

    expect(($this->page)()['actions'])->toBe(['quote' => null, 'reserve' => null]);
});

it('serves the same commands on API v1 to a token that may command, and reads with a read token', function (): void {
    ($this->filled)();
    ($this->acknowledge)();

    Sanctum::actingAs($this->user, ['investor:read']);
    $this->postJson(route('api.v1.investor.cart_quote'), ['identity_context_revision' => 1, 'expected_cart_revision' => 2])->assertForbidden();

    Sanctum::actingAs($this->user, ['investor:read', 'investor:command']);
    $quote = $this->postJson(route('api.v1.investor.cart_quote'), ['identity_context_revision' => 1, 'expected_cart_revision' => 2])->assertOk()->json('data');
    $checkout = $this->postJson(route('api.v1.investor.cart_reserve'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'expected_cart_revision' => 2, 'quote_id' => $quote['id'], 'expected_quote_revision' => 1, 'quote_disclosure_sha256' => $quote['disclosure_sha256']])
        ->assertOk()->json('data.receipt.checkout_id');
    ($this->committed)();

    Sanctum::actingAs($this->user, ['investor:read']);
    $this->getJson(route('api.v1.investor.cart_reservation', ['checkout' => $checkout, 'identity_context_revision' => 1]))->assertOk()
        ->assertJsonPath('data.id', $checkout);
});

it('refuses an Investor who is signed out', function (): void {
    $this->postJson(route('investor.cart_quote'), ['identity_context_revision' => 1, 'expected_cart_revision' => 0])->assertUnauthorized();
});
