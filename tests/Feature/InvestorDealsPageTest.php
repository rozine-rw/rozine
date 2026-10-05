<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Infrastructure\Business\RetainedCampaignPublication;
use App\Models\BusinessApplicationQuote;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->evidence = $this->campaign->payload['public_evidence'];
    $this->draft = BusinessApplicationQuote::query()->whereKey($this->campaign->payload['quote']['quote_id'])->sole()->payload['draft'];
    $this->deals = fn (array $query = []) => $this->actingAs($this->investor['user'])->get(route('investor.deals', $query));
    $this->publications = function (Closure $change): void {
        app()->instance(PublishedCampaignEvidence::class, new readonly class($change) implements PublishedCampaignEvidence
        {
            public function __construct(private Closure $change) {}

            public function find(string $campaignId): array
            {
                return ($this->change)(app(RetainedCampaignPublication::class)->find($campaignId));
            }
        });
    };
});

it('lists the live publication with allowlisted facts, live fill and no quote or checkout', function (): void {
    $props = ($this->deals)()->assertOk()->assertInertia(fn ($page) => $page->component('investor/deals'))->viewData('page')['props'];
    $card = $props['deals'][0];
    $principal = $this->campaign->payload['principal'];
    $revenue = $this->evidence['totals']['revenue']['amount'];
    $months = $this->evidence['period']['months'];

    expect($props)->toMatchArray(['contract_version' => 'investor-primary-v1', 'identity_context_revision' => 1, 'allowed_actions' => [],
        'gate' => ['status' => 'eligible'], 'unread_notifications' => 0, 'quote' => null])
        ->and($props['wallet'])->toBe(['available' => ['currency' => 'RWF', 'amount' => '10000000'], 'next_payout' => null])
        ->and(array_column($props['sorts'], 'key'))->toBe(['all', 'top_interest', 'top_rated'])
        ->and($props['sorts'][0])->toBe(['key' => 'all', 'active' => true, 'link' => ['url' => '/investor/deals', 'method' => 'get']])
        ->and($props['industries'][0])->toMatchArray(['industry' => null, 'count' => 1, 'active' => true])
        ->and($props['industries'][1])->toMatchArray(['industry' => $this->evidence['business']['industry'], 'count' => 1, 'active' => false])
        ->and($props['links'])->toMatchArray(['deals' => ['url' => '/investor/deals', 'method' => 'get'], 'portfolio' => null, 'profile' => null,
            'notifications' => null, 'checkout' => null, 'wallet' => ['url' => '/investor/wallet', 'method' => 'get'],
            'deposit' => ['url' => '/investor/wallet?kind=deposit', 'method' => 'get']])
        ->and($props['deals'])->toHaveCount(1)
        ->and($card)->toMatchArray(['campaign_id' => $this->campaign->id, 'revision' => 1, 'name' => $this->evidence['business']['name'],
            'industry' => $this->evidence['business']['industry'], 'district' => $this->evidence['business']['district'],
            'rating' => ['band' => $this->evidence['rating']['band'], 'score' => $this->evidence['rating']['score']], 'audited' => true,
            'just_listed' => true, 'photos' => [], 'raised' => ['currency' => 'RWF', 'amount' => '0'],
            'target' => ['currency' => 'RWF', 'amount' => $principal], 'funded_pct' => '0.0', 'left_to_fill' => ['currency' => 'RWF', 'amount' => $principal],
            'avg_monthly_revenue' => ['currency' => 'RWF', 'amount' => (string) intdiv((int) $revenue + intdiv($months, 2), $months)],
            'units' => ['total' => $this->campaign->payload['quote']['units'], 'available' => $this->campaign->payload['quote']['units'], 'reserved' => '0', 'committed' => '0'],
            'unit_price' => ['currency' => 'RWF', 'amount' => '5000'], 'investors' => 0, 'lifecycle' => 'live', 'restriction' => null,
            'clock' => ['starts_at' => $this->campaign->payload['recorded_at'], 'expires_at' => $this->campaign->payload['expires_at']],
            'listing_fee' => ['currency' => 'RWF', 'amount' => '0'], 'story' => $this->draft['story'],
            'rate_pct' => $this->campaign->payload['quote']['rate_pct'], 'term_months' => $this->campaign->payload['quote']['term_months'],
            'links' => ['detail' => ['url' => '/investor/deals/'.$this->campaign->id, 'method' => 'get']]])
        ->and($card['accent'])->toBeIn(['green', 'blue', 'amber', 'purple', 'teal', 'magenta', 'ink'])
        ->and($props['focus'])->toMatchArray(['campaign_id' => $this->campaign->id, 'use_of_funds' => $this->draft['use_of_funds'],
            'financials' => ['avg_monthly_revenue' => $card['avg_monthly_revenue'], 'ebitda' => ['value' => null, 'unavailable' => 'NOT_SOURCED_AS_EBITDA']],
            'rationale' => null, 'track_record' => null, 'audit' => null, 'updates' => [], 'overdue_report' => null,
            'about' => ['description' => $this->draft['story'], 'industry' => $card['industry'], 'district' => $card['district']]]);

    $json = json_encode($props, JSON_THROW_ON_ERROR);
    foreach (['company_code', 'officers', 'public_evidence', 'binding', 'actor_user_id', 'exposure_reservation_id', 'application_id'] as $private) {
        expect($json)->not->toContain('"'.$private.'"');
    }

    $this->travel(73)->hours();
    expect(($this->deals)()->viewData('page')['props']['deals'][0]['just_listed'])->toBeFalse();
});

it('shows live fill after a confirmed purchase, on both transports', function (): void {
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $initial = PrimaryReservationVersion::query()->sole()->payload;
    $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, PrimaryReservationRecord::query()->sole()->id, 1,
        $initial['terms']['disclosure_version'], $initial['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));

    $card = ($this->deals)()->viewData('page')['props']['deals'][0];
    expect([$card['raised']['amount'], $card['investors'], $card['units']['committed']])->toBe(['15000', 1, '3']);

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $api = $this->getJson(route('api.v1.investor.deals', ['identity_context_revision' => 1]))->assertOk()->json('data');
    expect($api['deals'][0]['raised']['amount'])->toBe('15000')
        ->and($api['deals'][0]['links']['detail']['url'])->toBe('/api/v1/investor/deals/'.$this->campaign->id)
        ->and($api['links']['launcher']['url'])->toBe('/api/v1/identity');
    $this->getJson(route('api.v1.investor.deals', ['sort' => 'newest']))->assertUnprocessable()->assertJsonValidationErrors('sort');
});

it('shows a funded publication as funded and fully committed', function (): void {
    app()->instance(CampaignFundingEvidence::class, new class implements CampaignFundingEvidence
    {
        public function find(string $campaignId): array
        {
            return ['principal' => '10800000', 'commitments' => [['party_id' => 'a'], ['party_id' => 'b'], ['party_id' => 'a']],
                'recorded_at' => now()->toIso8601String()];
        }
    });
    $card = ($this->deals)()->viewData('page')['props']['deals'][0];
    $total = $this->campaign->payload['quote']['units'];

    expect($card)->toMatchArray(['lifecycle' => 'funded', 'funded_pct' => '100.0', 'investors' => 2,
        'raised' => ['currency' => 'RWF', 'amount' => '10800000'], 'left_to_fill' => ['currency' => 'RWF', 'amount' => '0'],
        'units' => ['total' => $total, 'available' => '0', 'reserved' => '0', 'committed' => $total]]);
});

it('keeps an elapsed raise without recorded refunds as closing, not expired', function (): void {
    $this->travelTo($this->campaign->expires_at);

    expect(($this->deals)()->viewData('page')['props']['deals'][0]['lifecycle'])->toBe('closing_pending_settlement');
});

it('keeps a fully committed raise without a funding lock pending settlement, not funded', function (): void {
    $checkout = app(PrimaryCheckout::class);
    $half = (string) intdiv((int) $this->campaign->payload['quote']['units'], 2);
    foreach ([$this->investor, PrimaryReservationFixture::investor()] as $investor) {
        $request = (string) Str::uuid();
        $checkout->reserve($investor['user']->id, 1, $this->campaign->id, $half, $request, PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->where('party_id', $investor['party']->id)->sole();
        $initial = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole()->payload;
        $checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1, $initial['terms']['disclosure_version'],
            $initial['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    }
    $card = ($this->deals)()->viewData('page')['props']['deals'][0];

    expect(app(CampaignFundingEvidence::class)->find($this->campaign->id))->toBeNull()
        ->and($card)->toMatchArray(['lifecycle' => 'sold_out_pending_settlement', 'funded_pct' => '100.0', 'investors' => 2,
            'raised' => ['currency' => 'RWF', 'amount' => $this->campaign->payload['principal']], 'left_to_fill' => ['currency' => 'RWF', 'amount' => '0']])
        ->and($card['units'])->toMatchArray(['available' => '0', 'reserved' => '0']);
});

it('opens one deal with its deck, and refuses one that is unknown or closed', function (): void {
    $this->actingAs($this->investor['user'])->get(route('investor.deals.show', $this->campaign->id))->assertOk()
        ->assertInertia(fn ($page) => $page->component('investor/deal')->where('deal.campaign_id', $this->campaign->id)
            ->where('quote', null)->where('gate', ['status' => 'eligible'])
            ->where('links', ['back' => ['url' => '/investor/deals', 'method' => 'get'], 'checkout' => null])
            ->where('home.focus.campaign_id', $this->campaign->id));
    $this->actingAs($this->investor['user'])->get(route('investor.deals.show', strtolower((string) Str::ulid())))->assertNotFound();

    app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, 'Changed plans.',
        (string) Str::uuid());
    $props = ($this->deals)()->viewData('page')['props'];
    expect([$props['deals'], $props['focus']])->toBe([[], null]);
    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.deals.show', $this->campaign->id))->assertNotFound()->assertJsonPath('code', 'DEAL_NOT_FOUND');
});

it('never lists an unrated publication', function (): void {
    ($this->publications)(fn (array $payload): array => [...$payload, 'public_evidence' => [...$payload['public_evidence'], 'rating' => null]]);

    expect(($this->deals)()->viewData('page')['props']['deals'])->toBe([])
        ->and(app(InvestorDealCatalogue::class)->deal($this->campaign->id))->toBeNull();
});

it('fails closed when the pinned quote no longer matches the publication', function (): void {
    ($this->publications)(fn (array $payload): array => [...$payload, 'quote' => [...$payload['quote'], 'quote_id' => strtolower((string) Str::ulid())]]);

    expect(fn () => app(InvestorDealCatalogue::class)->deals())->toThrow(RuntimeException::class, 'CAMPAIGN_QUOTE_INTEGRITY_FAILED');
});

it('sorts and filters the deck by industry and focuses the requested deal', function (): void {
    app()->instance(InvestorDealCatalogue::class, new class implements InvestorDealCatalogue
    {
        private const array DEALS = [['campaign_id' => '01k0000000000000000000000a', 'industry' => 'Retail', 'funded_pct' => '10.0', 'rating' => ['score' => '4.5']],
            ['campaign_id' => '01k0000000000000000000000b', 'industry' => '2024', 'funded_pct' => '80.0', 'rating' => ['score' => '3.1']],
            ['campaign_id' => '01k0000000000000000000000c', 'industry' => 'Retail', 'funded_pct' => '40.0', 'rating' => ['score' => '4.9']]];

        public function deals(): array
        {
            return self::DEALS;
        }

        public function deal(string $campaignId): ?array
        {
            return array_find(self::DEALS, fn (array $deal): bool => $deal['campaign_id'] === $campaignId);
        }
    });
    $ids = fn (array $query): array => array_column(($this->deals)($query)->viewData('page')['props']['deals'], 'campaign_id');

    expect($ids([]))->toBe(['01k0000000000000000000000a', '01k0000000000000000000000b', '01k0000000000000000000000c'])
        ->and($ids(['sort' => 'top_interest']))->toBe(['01k0000000000000000000000b', '01k0000000000000000000000c', '01k0000000000000000000000a'])
        ->and($ids(['sort' => 'top_rated', 'industry' => 'Retail']))->toBe(['01k0000000000000000000000c', '01k0000000000000000000000a'])
        ->and($ids(['industry' => 'Unknown']))->toHaveCount(3);
    $props = ($this->deals)(['industry' => '2024', 'deal' => '01k0000000000000000000000c'])->viewData('page')['props'];
    expect(array_column($props['industries'], 'industry'))->toBe([null, '2024', 'Retail'])
        ->and($props['industries'][1])->toMatchArray(['count' => 1, 'active' => true, 'link' => ['url' => '/investor/deals?industry=2024', 'method' => 'get']])
        ->and($props['sorts'][1]['link']['url'])->toBe('/investor/deals?sort=top_interest&industry=2024')
        ->and($props['focus']['campaign_id'])->toBe('01k0000000000000000000000c');
});

it('refuses a caller without the Investor role', function (): void {
    $business = $this->campaign->actor_user_id;

    $this->actingAs(User::query()->findOrFail($business))->get(route('investor.deals'))->assertForbidden();
});
