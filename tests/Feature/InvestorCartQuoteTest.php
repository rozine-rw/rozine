<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\QuoteInvestorCart;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\RiskStatements;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorCartLine;
use App\Models\InvestorCartQuote;
use App\Models\InvestorCartRoot;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The whole-cart quote (#383 step 3b, contract cart-checkout-1): the entire saved cart priced at the
 * revision the Investor saw, under one INV-R04 band from held-note principal plus the cart, kept as
 * an immutable quote that holds no units or cash. Any line that cannot be bought refuses the whole
 * cart.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $this->second = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->add = fn (string $campaign, string $units, ?User $user = null) => $this->actingAs($user ?? $this->investor['user'])
        ->postJson(route('investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => $units])->assertOk();
    $this->acknowledge = fn () => $this->actingAs($this->investor['user'])->postJson(route('investor.risks.acknowledge'), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
    $this->quote = fn (int $revision, ?User $user = null) => app(QuoteInvestorCart::class)
        ->handle(($user ?? $this->investor['user'])->id, ($user ?? $this->investor['user'])->context_revision, $revision);
    $this->refusal = function (Closure $quote): CommandRejection {
        try {
            $quote();
        } catch (CommandRejection $rejection) {
            return $rejection;
        }
        throw new RuntimeException('The quote was not refused.');
    };
    $this->capital = fn (string $outstanding, bool $complete = true) => app()->instance(HeldNoteCapital::class, new readonly class($outstanding, $complete) implements HeldNoteCapital
    {
        public function __construct(private string $outstanding, private bool $complete) {}

        public function current(string $partyId): array
        {
            return ['outstanding_principal' => $this->outstanding, 'revision' => 4, 'sha256' => str_repeat('c', 64), 'complete' => $this->complete, 'as_of' => '2026-11-01T00:00:00Z'];
        }
    });
});

it('prices every line of the cart under one band and keeps the quote, holding nothing', function (): void {
    ($this->add)($this->campaign->id, '2');
    ($this->add)($this->second->id, '3');
    ($this->acknowledge)();
    $root = InvestorCartRoot::query()->sole();

    $quote = ($this->quote)(2);

    $lines = InvestorCartLine::query()->get()->keyBy('business_campaign_id');
    $ordered = [$this->campaign->id, $this->second->id];
    sort($ordered);
    $standard = ['tier' => 'standard', 'rate_bps' => 1500, 'basis' => 'return_only', 'policy_version' => 'brs-2.0-inv-r04'];
    expect($quote)->toMatchArray(['revision' => 1, 'contract_version' => 'cart-checkout-1', 'cart_id' => $root->id, 'cart_revision' => 2,
        'cart_sha256' => $root->sha256, 'principal' => ['currency' => 'RWF', 'amount' => '25000'], 'earnings_fee' => ['currency' => 'RWF', 'amount' => '417'],
        'net_return' => ['currency' => 'RWF', 'amount' => '2358'], 'confirmation_required' => true,
        'created_at' => now('UTC')->format('Y-m-d\TH:i:s.u\Z'), 'expires_at' => now('UTC')->addSeconds(300)->format('Y-m-d\TH:i:s.u\Z')])
        ->and($quote['id'])->toMatch('/^[0-9a-hjkmnp-tv-z]{26}$/')
        ->and($quote['disclosure_sha256'])->toMatch('/^[0-9a-f]{64}$/')
        ->and($quote['fee_band'])->toBe([...$standard, 'rate_pct' => '15.00', 'held_note_principal' => ['currency' => 'RWF', 'amount' => '0'],
            'checkout_total' => ['currency' => 'RWF', 'amount' => '25000'], 'active_capital_after_checkout' => ['currency' => 'RWF', 'amount' => '25000'],
            'next_band_at' => ['currency' => 'RWF', 'amount' => '1000000'], 'next_band_distance' => ['currency' => 'RWF', 'amount' => '975000'],
            'capital_revision' => 0, 'capital_sha256' => $quote['fee_band']['capital_sha256']])
        ->and(array_column($quote['lines'], 'campaign_id'))->toBe($ordered);

    /** @var list<array<string, mixed>> $quotedLines */
    $quotedLines = $quote['lines'];
    $byCampaign = collect($quotedLines)->keyBy('campaign_id');
    expect($byCampaign[$this->campaign->id])->toMatchArray(['cart_line_id' => $lines[$this->campaign->id]->id, 'units' => '2', 'campaign_revision' => 1,
        'principal' => ['currency' => 'RWF', 'amount' => '10000'], 'basis' => 'indicative', 'contractual_return' => ['currency' => 'RWF', 'amount' => '1110'],
        'earnings_fee' => ['currency' => 'RWF', 'amount' => '167'], 'net_return' => ['currency' => 'RWF', 'amount' => '943'],
        'terms' => ['rate_pct' => '11.1', 'term_months' => 6, 'policy_version' => $byCampaign[$this->campaign->id]['terms']['policy_version'], 'earnings_fee' => $standard]])
        ->and($byCampaign[$this->campaign->id]['publication_sha256'])->toBe($this->campaign->refresh()->sha256)
        ->and($byCampaign[$this->second->id])->toMatchArray(['units' => '3', 'contractual_return' => ['currency' => 'RWF', 'amount' => '1665'],
            'earnings_fee' => ['currency' => 'RWF', 'amount' => '250'], 'net_return' => ['currency' => 'RWF', 'amount' => '1415']]);

    $record = InvestorCartQuote::query()->sole();
    expect($record->payload)->toBe($quote)
        ->and($record->only(['id', 'party_id', 'investor_cart_id', 'cart_revision', 'disclosure_sha256']))->toBe(['id' => $quote['id'],
            'party_id' => $this->investor['party']->id, 'investor_cart_id' => $root->id, 'cart_revision' => 2, 'disclosure_sha256' => $quote['disclosure_sha256']])
        ->and(DB::table('primary_reservations')->count())->toBe(0)
        ->and(fn () => DB::transaction(fn () => DB::table('investor_cart_quotes')->update(['cart_revision' => 3])))->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::table('investor_cart_quotes')->delete()))->toThrow(QueryException::class, 'immutable');
});

it('gives a fresh quote of the same cart a new id and the same disclosure, and ends it at the earliest raise deadline', function (): void {
    ($this->add)($this->campaign->id, '2');
    ($this->acknowledge)();
    $first = ($this->quote)(1);
    $this->travelTo($this->campaign->refresh()->expires_at->subSeconds(60));

    $second = ($this->quote)(1);

    expect($second['id'])->not->toBe($first['id'])
        ->and($second['disclosure_sha256'])->toBe($first['disclosure_sha256'])
        ->and($second['expires_at'])->toBe($this->campaign->expires_at->utc()->format('Y-m-d\TH:i:s.u\Z'))
        ->and(InvestorCartQuote::query()->count())->toBe(2);
});

it('chooses the band from held-note principal plus the cart, and refuses when held capital cannot be proved complete', function (): void {
    ($this->add)($this->campaign->id, '2');
    ($this->acknowledge)();
    ($this->capital)('990000');

    $quote = ($this->quote)(1);

    expect($quote['fee_band'])->toMatchArray(['tier' => 'bronze', 'rate_bps' => 1200, 'rate_pct' => '12.00',
        'held_note_principal' => ['currency' => 'RWF', 'amount' => '990000'], 'active_capital_after_checkout' => ['currency' => 'RWF', 'amount' => '1000000'],
        'next_band_at' => ['currency' => 'RWF', 'amount' => '5000000'], 'next_band_distance' => ['currency' => 'RWF', 'amount' => '4000000'],
        'capital_revision' => 4, 'capital_sha256' => str_repeat('c', 64)])
        ->and($quote['lines'][0]['earnings_fee'])->toBe(['currency' => 'RWF', 'amount' => '133']);

    ($this->capital)('100000000');
    expect(($this->quote)(1)['fee_band'])->toMatchArray(['tier' => 'platinum', 'next_band_at' => null, 'next_band_distance' => null]);

    ($this->capital)('0', false);
    expect(($this->refusal)(fn () => ($this->quote)(1)))->reason->toBe('POLICY_INPUT_REQUIRED');
});

it('refuses a cart at another revision, an empty cart, and a cart before the risk statements are acknowledged', function (): void {
    expect(($this->refusal)(fn () => ($this->quote)(0)))->reason->toBe('CART_REVISION_CONFLICT')->revision->toBe(0);

    ($this->add)($this->campaign->id, '2');
    expect(($this->refusal)(fn () => ($this->quote)(1)))->reason->toBe('RISK_ACKNOWLEDGEMENT_REQUIRED');
    ($this->acknowledge)();
    expect(($this->refusal)(fn () => ($this->quote)(2)))->reason->toBe('CART_REVISION_CONFLICT')->revision->toBe(1);

    $this->actingAs($this->investor['user'])->postJson(route('investor.cart_remove', ['campaign' => $this->campaign->id]), ['identity_context_revision' => 1])->assertOk();
    expect(($this->refusal)(fn () => ($this->quote)(2)))->reason->toBe('CART_EMPTY')
        ->and(InvestorCartQuote::query()->count())->toBe(0);
});

it('refuses the whole cart when one raise has closed or no longer lists, naming the line', function (): void {
    ($this->add)($this->campaign->id, '2');
    ($this->add)($this->second->id, '3');
    ($this->acknowledge)();
    $live = app(InvestorDealCatalogue::class);
    app()->instance(InvestorDealCatalogue::class, new readonly class($live, $this->second->id) implements InvestorDealCatalogue
    {
        public function __construct(private InvestorDealCatalogue $live, private string $hidden) {}

        public function deals(): array
        {
            return [];
        }

        public function deal(string $campaignId): ?array
        {
            return $campaignId === $this->hidden ? null : $this->live->deal($campaignId);
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
    expect(($this->refusal)(fn () => ($this->quote)(2)))->reason->toBe('CART_LINE_UNAVAILABLE')->data->toBe(['campaign_ids' => [$this->second->id]]);

    app()->instance(InvestorDealCatalogue::class, $live);
    $this->travelTo($this->campaign->refresh()->expires_at->addSecond());
    $refusal = ($this->refusal)(fn () => ($this->quote)(2));
    expect($refusal)->reason->toBe('CART_LINE_UNAVAILABLE')->status->toBe(409)
        ->and($refusal->data['campaign_ids'])->toContain($this->campaign->id)
        ->and(InvestorCartQuote::query()->count())->toBe(0);
});

it('refuses the whole cart when a line is above the 50% limit, with the most allowed', function (): void {
    ($this->add)($this->campaign->id, '1080');
    ($this->add)($this->second->id, '1');
    ($this->acknowledge)();
    PrimaryReservationFixture::reserve($this->campaign, $this->investor, '1');

    expect(($this->refusal)(fn () => ($this->quote)(2)))->reason->toBe('CART_UNITS_ABOVE_LIMIT')->status->toBe(422)
        ->data->toBe(['max_units' => [$this->campaign->id => '1079']]);
});

it('gives a person still being verified, or a restricted account, no quote', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    expect(($this->refusal)(fn () => ($this->quote)(0, $person->refresh())))->reason->toBe('IDENTITY_VERIFICATION_REQUIRED');

    ($this->add)($this->campaign->id, '2');
    InvestorAccountRestriction::factory()->create(['party_id' => $this->investor['party']->id, 'effective_at' => now()->subHour()->startOfSecond()]);
    expect(($this->refusal)(fn () => ($this->quote)(1)))->reason->toBe('RESTRICTION_ACTIVE');
});
