<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\CartCheckouts;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\QuoteInvestorCart;
use App\Application\Primary\ReserveInvestorCart;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\RiskStatements;
use App\Infrastructure\Primary\EloquentCartCheckouts;
use App\Models\CommandOperation;
use App\Models\InvestorCartCheckout;
use App\Models\InvestorCartCheckoutVersion;
use App\Models\InvestorCartLine;
use App\Models\InvestorCartQuote;
use App\Models\User;
use App\Notifications\Staff\CartSettlementStuck;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\DecoratedPrimaryReservations;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;
use Tests\Support\StaffFixture;

/*
 * The whole-cart release and expiry (#383 step 4c, contract cart-checkout-1 `ReleaseCartReservation`
 * and P383-05): every held line returned at once, by the Investor or by the sweep once the last line
 * window closes, never one child on its own. A sweep that fails records the checkout as stuck, alerts
 * every staff member who may resolve it, and staff retry the same group expiry from Exceptions.
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
    $this->party = $this->investor['party']->id;
    $this->add = fn (string $campaign, string $units) => $this->actingAs($this->user)
        ->postJson(route('investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => $units])->assertOk();
    $this->acknowledge = fn () => $this->actingAs($this->user)->postJson(route('investor.risks.acknowledge'), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
    $this->quote = fn (int $revision) => app(QuoteInvestorCart::class)->handle($this->user->id, 1, $revision);
    $this->reserve = fn (array $quote, ?string $requestId = null, ?string $sha = null, int $quoteRevision = 1, ?User $user = null) => app(ReserveInvestorCart::class)
        ->handle(($user ?? $this->user)->id, ($user ?? $this->user)->context_revision, $quote['cart_revision'], $quote['id'], $quoteRevision,
            $sha ?? $quote['disclosure_sha256'], $requestId ?? (string) Str::uuid());
    $this->refusal = function (Closure $command): CommandRejection {
        try {
            $command();
        } catch (CommandRejection $rejection) {
            return $rejection;
        }
        throw new RuntimeException('The command was not refused.');
    };
    $this->capital = fn (string $outstanding) => app()->instance(HeldNoteCapital::class, new readonly class($outstanding) implements HeldNoteCapital
    {
        public function __construct(private string $outstanding) {}

        public function current(string $partyId): array
        {
            return ['outstanding_principal' => $this->outstanding, 'revision' => 4, 'sha256' => str_repeat('c', 64), 'complete' => true, 'as_of' => '2026-11-01T00:00:00Z'];
        }
    });
    $this->cart = function (string $first = '2', string $second = '3'): array {
        ($this->add)($this->campaign->id, $first);
        ($this->add)($this->second->id, $second);
        ($this->acknowledge)();

        return ($this->quote)(2);
    };
    $this->failingLock = fn (string $campaignId, int $failingCall) => app()->instance(PrimaryCampaignSource::class,
        new class(app(PrimaryCampaignSource::class), $campaignId, $failingCall) implements PrimaryCampaignSource
        {
            private int $calls = 0;

            public function __construct(private PrimaryCampaignSource $live, private string $campaignId, private int $failingCall) {}

            public function lockBusiness(string $campaignId): void
            {
                $this->live->lockBusiness($campaignId);
            }

            public function rejectKnownConnections(string $businessId, array $partyIds): void
            {
                $this->live->rejectKnownConnections($businessId, $partyIds);
            }

            public function lock(string $campaignId): array
            {
                if ($campaignId === $this->campaignId && ++$this->calls === $this->failingCall) {
                    throw new CommandRejection('CAMPAIGN_NOT_OPEN');
                }

                return $this->live->lock($campaignId);
            }

            public function lockForFunding(string $campaignId): array
            {
                return $this->live->lockForFunding($campaignId);
            }

            public function lockRetained(string $campaignId): array
            {
                return $this->live->lockRetained($campaignId);
            }

            public function presentation(string $campaignId): array
            {
                return $this->live->presentation($campaignId);
            }
        });
});


beforeEach(function (): void {
    $this->held = function (): array {
        $reserved = ($this->reserve)(($this->cart)());
        $checkout = InvestorCartCheckout::query()->whereKey($reserved['data']['checkout_id'])->sole();

        return ['reserved' => $reserved, 'checkout' => $checkout, 'quote' => InvestorCartQuote::query()->whereKey($checkout->investor_cart_quote_id)->sole()->payload];
    };
    $this->release = fn (string $checkoutId, int $revision = 1, ?string $requestId = null) => app(ReserveInvestorCart::class)
        ->release($this->user->id, 1, $checkoutId, $revision, $requestId ?? (string) Str::uuid());
    /** @param list<string> $roles */
    $this->staff = function (array $roles): User {
        $user = User::factory()->withTwoFactor()->create();
        StaffFixture::provision($user, $roles, 'Cart release duty.');

        return $user;
    };
    $this->versions = fn (): array => DB::table('primary_reservation_versions')->where('revision', '>', 1)->orderBy('primary_reservation_id')
        ->pluck('state')->all();
});

it('returns every held line at once, each under its own release receipt, and keeps the cart', function (): void {
    ['checkout' => $checkout, 'reserved' => $reserved] = ($this->held)();
    $requestId = (string) Str::uuid();

    $result = ($this->release)($checkout->id, requestId: $requestId);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'CART_RELEASED', 'revision' => 2])
        ->and($result['data'])->toMatchArray(['checkout_id' => $checkout->id, 'state' => 'released', 'total_returned' => ['currency' => 'RWF', 'amount' => '25000']])
        ->and(array_column($result['data']['lines'], 'reservation_id'))->toBe(array_column($reserved['data']['lines'], 'reservation_id'))
        ->and(($this->versions)())->toBe(['released', 'released'])
        ->and(InvestorCartLine::query()->count())->toBe(2)
        ->and(DB::table('investor_cart_live_checkouts')->count())->toBe(0)
        ->and(InvestorCartCheckoutVersion::query()->where('revision', 2)->sole()->only(['state', 'operation_id']))->toBe(['state' => 'released',
            'operation_id' => $result['operation_id']]);

    foreach ($result['data']['lines'] as $line) {
        $child = CommandOperation::query()->where('command', 'primary.release')->where('target_id', $line['reservation_id'])->sole();
        expect($child->request_id)->toBe(EloquentCartCheckouts::childRequestId($result['operation_id'], 'release', $line['campaign_id']))
            ->and($child->result)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_RELEASED'])
            ->and($child->result['data'])->toMatchArray(['entry_id' => $line['entry_id'], 'cart_operation_id' => $result['operation_id']]);
    }

    $service = app(ReserveInvestorCart::class);
    expect(($this->release)($checkout->id, requestId: $requestId))->toBe($result)
        ->and($service->findOperation($this->user->id, 1, 'cart.release', $checkout->id, $requestId))->toBe($result)
        ->and(($this->refusal)(fn () => $service->findOperation($this->user->id, 1, 'cart.reserve', $checkout->id, $requestId)))->reason->toBe('OPERATION_NOT_FOUND')
        ->and(($this->release)($checkout->id, 2))->toMatchArray(['status' => 'rejected', 'code' => 'CART_RESERVATION_NOT_HELD'])
        ->and(($this->release)($checkout->id))->toMatchArray(['status' => 'rejected', 'code' => 'VERSION_CONFLICT'])
        ->and(($this->refusal)(fn () => ($this->release)(strtolower((string) Str::ulid()))))->reason->toBe('CART_RESERVATION_NOT_FOUND')
        ->and(($this->reserve)(($this->quote)(2))['code'])->toBe('CART_RESERVED');
});

it('leaves an elapsed checkout to the group expiry, and rolls back whole when a line will not release', function (): void {
    ['checkout' => $checkout] = ($this->held)();

    app()->instance(PrimaryReservations::class, new DecoratedPrimaryReservations(app(PrimaryReservations::class),
        releasing: function (string $campaignId): void {
            if ($campaignId === $this->second->id) {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        }));
    expect(fn () => ($this->release)($checkout->id))->toThrow(RuntimeException::class, 'CART_CHECKOUT_INTEGRITY_FAILED')
        ->and(($this->versions)())->toBe([])
        ->and(CommandOperation::query()->where('command', 'cart.release')->count())->toBe(0);

    app()->forgetInstance(PrimaryReservations::class);
    $this->travelTo($checkout->expires_at);
    expect(($this->release)($checkout->id))->toMatchArray(['status' => 'rejected', 'code' => 'CART_RESERVATION_EXPIRED',
        'data' => ['cart_id' => $checkout->investor_cart_id, 'checkout_id' => $checkout->id, 'cart_revision' => 2, 'atomic' => true,
            'financial_effect' => 'none', 'state' => 'release_pending']]);
});

it('expires every line of a checkout together once its last window closes, and not before', function (): void {
    ['checkout' => $checkout] = ($this->held)();
    $sweep = app(CartCheckouts::class);

    expect(($this->refusal)(fn () => $sweep->expireDue(0)))->reason->toBe('INVALID_SWEEP_LIMIT')
        ->and($sweep->expireDue(10))->toBe(0);

    $this->travelTo($checkout->settles_at);
    expect(Artisan::call('cart:expire-checkouts', ['--limit' => 'x']))->toBe(2)
        ->and(Artisan::call('cart:expire-checkouts'))->toBe(0)
        ->and(trim(Artisan::output()))->toBe('Expired 1 cart checkouts.');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(($this->versions)())->toBe(['expired', 'expired'])
        ->and(DB::table('primary_reservation_versions')->where('state', 'expired')->whereNotNull('operation_id')->count())->toBe(0)
        ->and(InvestorCartCheckoutVersion::query()->where('revision', 2)->sole()->only(['state', 'operation_id']))->toBe(['state' => 'expired', 'operation_id' => null])
        ->and(DB::table('investor_cart_live_checkouts')->count())->toBe(0)
        ->and(app(ReserveInvestorCart::class)->reservation($this->user->id, 1, $checkout->id)['state'])->toBe('expired')
        ->and($sweep->expireDue(10))->toBe(0)
        ->and($sweep->stuck())->toBe([]);
});

it('records a stuck expiry, alerts the staff who may resolve it once, and lets them retry it from Exceptions', function (): void {
    Notification::fake();
    $treasury = ($this->staff)(['treasury']);
    $analyst = ($this->staff)(['analyst']);
    $suspended = ($this->staff)(['superadmin']);
    DB::table('staff_accounts')->where('user_id', $suspended->id)->update(['enabled' => false]);
    $this->user->forceFill(['name' => 'Aline Uwase'])->save();
    ['checkout' => $checkout] = ($this->held)();
    $sweep = fn () => app(CartCheckouts::class)->expireDue(10);
    $retry = fn (User $staff) => $this->actingAs($staff)->from(route('staff.exceptions.index'))
        ->post(route('staff.exceptions.cart-releases.retry', ['checkout' => $checkout->id]), ['request_id' => (string) Str::uuid()]);

    $retry($treasury)->assertSessionHasErrors(['request_id' => 'This cart is still inside its hold window. Try again once it has closed.']);

    app()->instance(PrimaryReservations::class, new DecoratedPrimaryReservations(app(PrimaryReservations::class),
        expiring: function (string $campaignId, Closure $expire): mixed {
            if ($campaignId === $this->second->id) {
                throw new RuntimeException('LEDGER_UNAVAILABLE');
            }

            return $expire();
        }));
    $this->travelTo($checkout->settles_at);
    expect($sweep)->toThrow(RuntimeException::class, 'LEDGER_UNAVAILABLE')
        ->and($sweep())->toBe(0);
    $this->travel(CartCheckouts::RETRY_BACKOFF_MINUTES)->minutes();
    expect($sweep)->toThrow(RuntimeException::class, 'LEDGER_UNAVAILABLE')
        ->and($sweep())->toBe(0)
        ->and(($this->versions)())->toBe([])
        ->and((array) DB::table('investor_cart_settlement_failures')->sole())->toMatchArray(['investor_cart_checkout_id' => $checkout->id, 'attempts' => 2,
            'reason_code' => 'SETTLEMENT_FAILED', 'exception_class' => RuntimeException::class]);
    Notification::assertSentToTimes($treasury, CartSettlementStuck::class, 1);
    Notification::assertNotSentTo([$analyst, $suspended], CartSettlementStuck::class);
    Notification::assertSentTo($treasury, fn (CartSettlementStuck $alert): bool => $alert->checkoutId === $checkout->id && $alert->total === '25000'
        && $alert->reasonCode === 'SETTLEMENT_FAILED' && $alert->url === route('staff.exceptions.index', ['kind' => 'cart_release']));

    $page = [];
    $this->actingAs($treasury)->get(route('staff.exceptions.index', ['kind' => 'cart_release']))->assertOk()
        ->assertInertia(function (Assert $inertia) use (&$page): void {
            $page = $inertia->toArray()['props'];
        });
    expect($page['exceptions'])->toHaveCount(1)
        ->and($page['exceptions'][0])->toMatchArray(['id' => $checkout->id, 'kind' => 'cart_release', 'reference' => 'CRT-'.strtoupper(substr($checkout->id, -6)),
            'subject' => ['kind' => 'investor', 'label' => 'Aline Uwase'], 'description' => 'Cart release failed (SETTLEMENT_FAILED, 2 attempts): 2 lines still held',
            'amount' => ['currency' => 'RWF', 'amount' => '25000'], 'link' => null,
            'retry' => ['url' => '/admin/exceptions/cart-releases/'.$checkout->id.'/retry', 'method' => 'post']]);

    $retry($analyst)->assertForbidden();
    app()->forgetInstance(PrimaryReservations::class);
    $retry($treasury)->assertRedirect(route('staff.exceptions.index', ['kind' => 'cart_release']));
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    $settle = CommandOperation::query()->where('command', 'cart.settle')->where('result->status', 'completed')->sole();
    expect($settle->actor_key)->toBe('staff:'.$treasury->id)
        ->and($settle->result)->toMatchArray(['code' => 'CART_EXPIRED', 'revision' => 2])
        ->and(($this->versions)())->toBe(['expired', 'expired'])
        ->and(InvestorCartCheckoutVersion::query()->where('revision', 2)->sole()->operation_id)->toBe($settle->id)
        ->and(DB::table('investor_cart_settlement_failures')->count())->toBe(0)
        ->and(app(CartCheckouts::class)->stuck())->toBe([]);

    $retry($treasury)->assertSessionHasErrors(['request_id' => 'This cart was already released. Refresh the list.']);
    $this->actingAs($treasury)->post(route('staff.exceptions.cart-releases.retry', ['checkout' => strtolower((string) Str::ulid())]), ['request_id' => (string) Str::uuid()])
        ->assertSessionHasErrors(['request_id' => 'This cart checkout no longer exists.']);
});

it('skips a checkout that was settled after the sweep picked it, without recording a failure or alerting', function (): void {
    Notification::fake();
    $treasury = ($this->staff)(['treasury']);
    ['checkout' => $checkout] = ($this->held)();
    $this->travelTo($checkout->settles_at);
    $settledElsewhere = false;
    DB::listen(function (QueryExecuted $query) use (&$settledElsewhere, $checkout, $treasury): void {
        // The retry query runs after the sweep has picked its fresh candidates, before it locks any.
        if (! $settledElsewhere && str_contains($query->sql, 'make_interval')) {
            $settledElsewhere = true;
            app(CartCheckouts::class)->settleStuck($treasury->id, $checkout->id, (string) Str::uuid());
        }
    });

    expect(app(CartCheckouts::class)->expireDue(10))->toBe(0)
        ->and($settledElsewhere)->toBeTrue()
        ->and(InvestorCartCheckoutVersion::query()->where('investor_cart_checkout_id', $checkout->id)->orderBy('revision')->pluck('state')->all())->toBe(['held', 'expired'])
        ->and(DB::table('investor_cart_settlement_failures')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('shares every sweep between fresh checkouts and due retries so neither starves', function (int $limit, int $fresh, int $retries, bool $retryFirst, array $taken): void {
    expect(EloquentCartCheckouts::shares($limit, $fresh, $retries, $retryFirst))->toBe($taken);
})->with([
    'limit 1, both waiting, retry minute' => [1, 5, 60, true, [0, 1]],
    'limit 1, both waiting, fresh minute' => [1, 5, 60, false, [1, 0]],
    'limit 1, only retries' => [1, 0, 60, false, [0, 1]],
    'limit 1, only fresh' => [1, 5, 0, true, [1, 0]],
    'even split' => [10, 50, 50, true, [5, 5]],
    'odd slot to retries' => [5, 50, 50, true, [2, 3]],
    'odd slot to fresh' => [5, 50, 50, false, [3, 2]],
    'few retries give the rest to fresh' => [10, 50, 2, true, [8, 2]],
    'few fresh give the rest to retries' => [10, 1, 50, false, [1, 9]],
    'less than the limit due' => [10, 3, 2, true, [3, 2]],
]);

it('alternates the odd sweep slot on every sweep, however the sweeps are spaced', function (): void {
    $this->freezeTime();
    $sweep = app(CartCheckouts::class);
    Cache::forget(EloquentCartCheckouts::SWEEP_TURN_KEY);

    expect($sweep->expireDue(1))->toBe(0)
        ->and(Cache::get(EloquentCartCheckouts::SWEEP_TURN_KEY))->toBe(1)
        ->and($sweep->expireDue(1))->toBe(0)
        ->and(Cache::get(EloquentCartCheckouts::SWEEP_TURN_KEY))->toBe(2)
        ->and($sweep->expireDue(1))->toBe(0)
        ->and(Cache::get(EloquentCartCheckouts::SWEEP_TURN_KEY))->toBe(3);
});

it('shows a checkout the sweep has not reached as stuck, and keeps sweeping when an alert cannot be sent', function (): void {
    ['checkout' => $checkout] = ($this->held)();
    ($this->staff)(['compliance']);

    $this->travelTo($checkout->settles_at->addSeconds(CartCheckouts::STUCK_AFTER_SECONDS));
    $stuck = app(CartCheckouts::class)->stuck();
    expect($stuck)->toHaveCount(1)
        ->and($stuck[0])->toMatchArray(['checkout_id' => $checkout->id, 'party_id' => $this->party, 'total' => '25000', 'lines' => 2, 'attempts' => 0,
            'reason_code' => null, 'last_attempted_at' => null]);

    config(['mail.default' => 'missing']);
    Exceptions::fake();
    app()->instance(PrimaryReservations::class, new DecoratedPrimaryReservations(app(PrimaryReservations::class),
        expiring: fn (string $campaignId, Closure $expire): mixed => throw new CommandRejection('CAMPAIGN_NOT_OPEN')));
    expect(fn () => app(CartCheckouts::class)->expireDue(10))->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_OPEN')
        ->and(app(CartCheckouts::class)->stuck()[0])->toMatchArray(['attempts' => 1, 'reason_code' => 'CAMPAIGN_NOT_OPEN']);
    Exceptions::assertReported(InvalidArgumentException::class);
});

it('renders the stuck release alert for staff', function (): void {
    $user = User::factory()->create();
    $html = (string) (new CartSettlementStuck('01k0cartcheckout0000000000', '25000', 'SETTLEMENT_FAILED', 'https://rozine.test/admin/exceptions?kind=cart_release'))
        ->toMail($user)->render();

    expect($html)->toContain('A cart checkout could not be released')->toContain('RWF 25,000')->toContain('01K0CARTCHECKOUT0000000000')
        ->toContain('SETTLEMENT_FAILED')->toContain('Open Exceptions');
});

it('rolls the settlement failures and the retry permission back and forward', function (): void {
    $migration = require database_path('migrations/2026_11_02_120000_create_investor_cart_settlement_failures.php');

    $migration->down();
    expect(Schema::hasTable('investor_cart_settlement_failures'))->toBeFalse()
        ->and(DB::table('permissions')->where('name', 'cart.releases.resolve')->exists())->toBeFalse();

    $migration->up();
    expect(Schema::hasTable('investor_cart_settlement_failures'))->toBeTrue()
        ->and(StaffFixture::permissions(['treasury']))->toContain('cart.releases.resolve')
        ->and(StaffFixture::permissions(['analyst']))->not->toContain('cart.releases.resolve');
});
