<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\ManageInvestorCart;
use App\Application\Primary\QuoteInvestorCart;
use App\Application\Primary\ReserveInvestorCart;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\RiskStatements;
use App\Infrastructure\Primary\EloquentCartCheckouts;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorCartCheckout;
use App\Models\InvestorCartCheckoutLine;
use App\Models\InvestorCartCheckoutVersion;
use App\Models\InvestorCartRoot;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The whole-cart reserve (#383 step 4a, contract cart-checkout-1): every line of the quoted cart held
 * at once as ordinary primary reservations under one `cart.reserve`, each with its own child receipt,
 * or none at all. While the checkout is held, the cart cannot change and no single-line command can
 * move a child on its own.
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

it('holds every line of the quoted cart at once, each with its own child receipt naming the parent', function (): void {
    $quote = ($this->cart)();
    $requestId = (string) Str::uuid();

    $result = ($this->reserve)($quote, $requestId);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    $checkout = InvestorCartCheckout::query()->sole();
    $children = PrimaryReservationRecord::query()->get()->keyBy('business_campaign_id');
    $ordered = [$this->campaign->id, $this->second->id];
    sort($ordered);
    $deadline = now('UTC')->addSeconds(300)->format('Y-m-d\TH:i:s.u\Z');
    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'CART_RESERVED', 'revision' => 1])
        ->and($result['data'])->toMatchArray(['checkout_id' => $checkout->id, 'cart_id' => $quote['cart_id'], 'cart_revision' => 2, 'quote_id' => $quote['id'],
            'disclosure_sha256' => $quote['disclosure_sha256'], 'state' => 'held', 'fee_band' => $quote['fee_band'], 'expires_at' => $deadline,
            'total_held' => ['currency' => 'RWF', 'amount' => '25000']])
        ->and(array_column($result['data']['lines'], 'campaign_id'))->toBe($ordered)
        ->and($result['data']['lines'][0])->toHaveKeys(['cart_line_id', 'reservation_id', 'reservation_revision', 'principal', 'disclosure_sha256', 'entry_id'])
        ->and($children)->toHaveCount(2)
        ->and($children[$this->campaign->id]->principal)->toBe('10000')
        ->and($children[$this->second->id]->principal)->toBe('15000')
        ->and($checkout->only(['party_id', 'investor_cart_quote_id', 'cart_revision', 'origin_operation_id', 'principal']))->toBe(['party_id' => $this->party,
            'investor_cart_quote_id' => $quote['id'], 'cart_revision' => 2, 'origin_operation_id' => $result['operation_id'], 'principal' => '25000'])
        ->and(InvestorCartCheckoutVersion::query()->sole()->only(['revision', 'state', 'operation_id']))->toBe(['revision' => 1, 'state' => 'held',
            'operation_id' => $result['operation_id']])
        ->and((array) DB::table('investor_cart_live_checkouts')->sole())->toBe(['party_id' => $this->party, 'investor_cart_checkout_id' => $checkout->id]);

    foreach (InvestorCartCheckoutLine::query()->orderBy('position')->get() as $index => $line) {
        $child = CommandOperation::query()->findOrFail($line->child_operation_id);
        expect($line->business_campaign_id)->toBe($ordered[$index])
            ->and($line->primary_reservation_id)->toBe($children[$line->business_campaign_id]->id)
            ->and($children[$line->business_campaign_id]->origin_operation_id)->toBe($child->id)
            ->and($child->only(['actor_key', 'command', 'target_type', 'target_id', 'request_id']))->toBe(['actor_key' => 'party:'.$this->party,
                'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $line->business_campaign_id,
                'request_id' => EloquentCartCheckouts::childRequestId($result['operation_id'], 'reserve', $line->business_campaign_id)])
            ->and($child->result)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_HELD'])
            ->and($child->result['data'])->toMatchArray(['reservation_id' => $line->primary_reservation_id, 'cart_operation_id' => $result['operation_id']]);
    }

    expect(($this->reserve)($quote, $requestId))->toBe($result)
        ->and(app(ReserveInvestorCart::class)->find($this->user->id, 1, $requestId))->toBe($result)
        ->and(InvestorCartCheckout::query()->count())->toBe(1);

    $read = app(ReserveInvestorCart::class)->reservation($this->user->id, 1, $checkout->id);
    expect($read)->toMatchArray(['id' => $checkout->id, 'cart_id' => $quote['cart_id'], 'cart_revision' => 2, 'revision' => 1, 'state' => 'held',
        'quote' => $quote, 'expires_at' => $deadline, 'total_held' => ['currency' => 'RWF', 'amount' => '25000']])
        ->and(array_column($read['lines'], 'reservation_id'))->toBe(array_column($result['data']['lines'], 'reservation_id'))
        ->and(($this->refusal)(fn () => app(ReserveInvestorCart::class)->reservation($this->user->id, 1, strtolower((string) Str::ulid()))))
        ->reason->toBe('CART_RESERVATION_NOT_FOUND');

    $this->travelTo(now()->addSeconds(301));
    expect(app(ReserveInvestorCart::class)->reservation($this->user->id, 1, $checkout->id)['state'])->toBe('release_pending')
        ->and(app(PrimaryReservations::class)->expireDue(100))->toBe(0)
        ->and(DB::table('primary_reservation_versions')->where('state', 'expired')->count())->toBe(0);
});

it('keeps the checkout evidence immutable and refuses a checkout without its receipts', function (): void {
    ($this->reserve)(($this->cart)());
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $checkout = InvestorCartCheckout::query()->sole();

    expect(fn () => DB::transaction(fn () => DB::table('investor_cart_checkouts')->update(['cart_revision' => 3])))->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::table('investor_cart_checkout_versions')->delete()))->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::table('investor_cart_checkout_lines')->update(['position' => 9])))->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(function () use ($checkout): void {
            DB::table('investor_cart_checkouts')->insert([...$checkout->getAttributes(), 'id' => strtolower((string) Str::ulid()),
                'origin_operation_id' => strtolower((string) Str::ulid())]);
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        }))->toThrow(QueryException::class, 'completed cart.reserve receipt');
});

it('refuses a cart at another revision, an unknown or expired quote, and a stale quote, holding nothing', function (): void {
    $other = User::factory()->create(['party_id' => Party::factory()]);
    expect(($this->refusal)(fn () => app(ReserveInvestorCart::class)->handle($this->user->id, 1, 0, strtolower((string) Str::ulid()), 1,
        str_repeat('a', 64), (string) Str::uuid())))->reason->toBe('CART_REVISION_CONFLICT')->revision->toBe(0)
        ->and(($this->refusal)(fn () => ($this->reserve)(['cart_revision' => 0, 'id' => 'x', 'disclosure_sha256' => 'y'], user: $other->refresh())))
        ->reason->toBe('IDENTITY_VERIFICATION_REQUIRED');

    $quote = ($this->cart)();

    expect(($this->reserve)([...$quote, 'cart_revision' => 1]))->toMatchArray(['status' => 'rejected', 'code' => 'CART_REVISION_CONFLICT', 'revision' => 2])
        ->and(($this->reserve)([...$quote, 'id' => strtolower((string) Str::ulid())]))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_NOT_FOUND',
            'http_status' => 404])
        ->and(($this->reserve)($quote, sha: str_repeat('b', 64)))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_STALE'])
        ->and(($this->reserve)($quote, quoteRevision: 2)['code'])->toBe('CART_QUOTE_STALE');

    ($this->capital)('990000');
    $stale = ($this->reserve)($quote);
    expect($stale)->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_STALE', 'http_status' => 409])
        ->and($stale['data'])->toEqual(['cart_id' => $quote['cart_id'], 'quote_id' => $quote['id'], 'atomic' => true, 'financial_effect' => 'none',
            'requires_reconfirmation' => true]);

    $this->travelTo(now()->addSeconds(300));
    expect(($this->reserve)($quote))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_EXPIRED'])
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(InvestorCartCheckout::query()->count())->toBe(0);
});

it('refuses every line when one cannot be held, naming each refused line, and leaves no hold or cash behind', function (): void {
    $quote = ($this->cart)('1080', '1080');

    $refused = ($this->reserve)($quote);

    $ordered = [$this->campaign->id, $this->second->id];
    sort($ordered);
    expect($refused)->toMatchArray(['status' => 'rejected', 'code' => 'INSUFFICIENT_AVAILABLE_FUNDS', 'http_status' => 422])
        ->and($refused['data'])->toMatchArray(['cart_id' => $quote['cart_id'], 'cart_revision' => 2, 'atomic' => true, 'financial_effect' => 'none', 'cart_refusals' => []])
        ->and($refused['data']['line_refusals'])->toHaveCount(1)
        ->and($refused['data']['line_refusals'][0])->toMatchArray(['campaign_id' => $ordered[1], 'code' => 'INSUFFICIENT_AVAILABLE_FUNDS', 'http_status' => 422])
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.reserve')->count())->toBe(0)
        ->and(InvestorCartCheckout::query()->count())->toBe(0);
});

it('refuses the cart when a raise closes before its hold, by line or as unavailable', function (): void {
    $quote = ($this->cart)();

    ($this->failingLock)($this->second->id, 2);
    $refused = ($this->reserve)($quote);
    expect($refused)->toMatchArray(['status' => 'rejected', 'code' => 'CART_LINES_REFUSED', 'http_status' => 409])
        ->and($refused['data']['line_refusals'])->toEqual([['cart_line_id' => array_column($quote['lines'], 'cart_line_id', 'campaign_id')[$this->second->id],
            'campaign_id' => $this->second->id, 'code' => 'CAMPAIGN_NOT_OPEN', 'http_status' => 409, 'field_errors' => []]]);

    ($this->failingLock)($this->second->id, 1);
    expect(($this->reserve)($quote))->toMatchArray(['status' => 'rejected', 'code' => 'CART_LINE_UNAVAILABLE'])
        ->and(PrimaryReservationRecord::query()->count())->toBe(0);
});

it('refuses a cart that a standalone hold already reserves in, and a second checkout while one is held', function (): void {
    $quote = ($this->cart)();
    $standalone = PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1');

    $refused = ($this->reserve)($quote);
    expect($refused)->toMatchArray(['status' => 'rejected', 'code' => 'CART_EXISTING_RESERVATION'])
        ->and($refused['data']['reservation_ids'])->toBe([$standalone['data']['reservation_id']]);

    app(PrimaryCheckout::class)->release($this->user->id, 1, $this->campaign->id, $standalone['data']['reservation_id'], 1, (string) Str::uuid());
    $held = ($this->reserve)($quote);
    expect($held['code'])->toBe('CART_RESERVED');

    $again = ($this->reserve)(($this->quote)(2));
    expect($again)->toMatchArray(['status' => 'rejected', 'code' => 'CART_CHECKOUT_ACTIVE'])
        ->and($again['data']['checkout_id'])->toBe($held['data']['checkout_id']);
});

it('keeps the cart and every child still while the checkout is held', function (): void {
    $quote = ($this->cart)();
    $held = ($this->reserve)($quote);
    $child = $held['data']['lines'][0];
    $cart = app(ManageInvestorCart::class);

    expect(($this->refusal)(fn () => $cart->add($this->user->id, 1, $this->campaign->id, 4)))->reason->toBe('CART_CHECKOUT_ACTIVE')
        ->and(($this->refusal)(fn () => $cart->remove($this->user->id, 1, $this->second->id)))->reason->toBe('CART_CHECKOUT_ACTIVE')
        ->and(InvestorCartRoot::query()->sole()->revision)->toBe(2);

    $checkout = app(PrimaryCheckout::class);
    $released = $checkout->release($this->user->id, 1, $child['campaign_id'], $child['reservation_id'], 1, (string) Str::uuid());
    $confirmed = $checkout->confirm($this->user->id, 1, $child['campaign_id'], $child['reservation_id'], 1, 'x', str_repeat('d', 64),
        (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $single = $checkout->reserve($this->user->id, 1, $child['campaign_id'], '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect($released)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_IN_CART_CHECKOUT', 'data' => ['reservation_id' => $child['reservation_id']]])
        ->and($confirmed)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_IN_CART_CHECKOUT'])
        ->and($single)->toMatchArray(['status' => 'rejected', 'code' => 'CART_CHECKOUT_ACTIVE', 'data' => ['checkout_id' => $held['data']['checkout_id']]])
        ->and(DB::table('primary_reservation_versions')->where('state', '<>', 'held')->count())->toBe(0);
});

it('defers campaign expiry while a cart checkout still holds a line in it, expiring no child on its own', function (): void {
    $held = ($this->reserve)(($this->cart)());
    $this->travelTo($this->campaign->expires_at->max($this->second->expires_at)->addSecond());

    expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(DB::table('primary_reservation_versions')->where('state', '<>', 'held')->count())->toBe(0)
        ->and(($this->refusal)(fn () => DB::transaction(fn () => app(PrimaryReservations::class)
            ->settleExpiredCampaign($this->campaign->id, (string) Str::ulid()))))->reason->toBe('CAMPAIGN_SETTLEMENT_REQUIRED')
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0)
        ->and($held['data']['lines'])->toHaveCount(2);
});

it('gives a restricted account no cart hold', function (): void {
    $quote = ($this->cart)();
    InvestorAccountRestriction::factory()->create(['party_id' => $this->party, 'effective_at' => now()->subHour()->startOfSecond()]);

    expect(($this->refusal)(fn () => ($this->reserve)($quote)))->reason->toBe('RESTRICTION_ACTIVE');
});
