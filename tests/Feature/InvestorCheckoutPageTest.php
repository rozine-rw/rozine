<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryDisclosure;
use App\Domain\Primary\RiskStatements;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The live checkout (MVP-INVESTOR-SCR-03, C3 v2 §2c) on real data: the indicative quote at the
 * INV-R04 band this purchase would lock, capacity under the 50% limit (INV-R01), the KYC-R05 risk
 * step before Reserve, and the Investor's held reservation once units are held.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->page = fn (array $query = []) => $this->actingAs($this->investor['user'])
        ->get(route('investor.checkout', ['campaign' => $this->campaign->id, ...$query]))->assertOk()
        ->assertInertia(fn ($page) => $page->component('investor/checkout'))->viewData('page')['props'];
    $this->acknowledge = fn () => $this->actingAs($this->investor['user'])->postJson(route('investor.risks.acknowledge'), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
});

it('quotes one unit at the standard band and asks for the risk statements before any reserve', function (): void {
    $props = ($this->page)();
    $campaign = $this->campaign->id;

    expect($props)->toMatchArray(['contract_version' => 'investor-primary-v1', 'identity_context_revision' => 1, 'allowed_actions' => [],
        'reservation' => null, 'commitment' => null, 'refusal' => null, 'home' => null])
        ->and($props['deal'])->toMatchArray(['campaign_id' => $campaign, 'rate_pct' => '11.1', 'term_months' => 6, 'lifecycle' => 'live'])
        ->and($props['quote'])->toMatchArray(['basis' => 'indicative', 'units' => '1', 'amount' => ['currency' => 'RWF', 'amount' => '5000'],
            'expected_return' => ['currency' => 'RWF', 'amount' => '555'], 'payout_fee' => ['currency' => 'RWF', 'amount' => '83'],
            'maturity_value' => ['currency' => 'RWF', 'amount' => '5472'], 'maturity_date' => null, 'rights' => null, 'revision' => 1,
            'earnings_fee' => ['tier' => 'standard', 'rate_bps' => 1500, 'basis' => 'return_only', 'policy_version' => 'brs-2.0-inv-r04'],
            'capacity' => ['max_units' => '1080', 'binding' => 'raise_cap', 'remaining' => ['raise' => ['currency' => 'RWF', 'amount' => '5400000']]]])
        ->and(array_column($props['quick_picks'], 'units'))->toBe(['1', '2', '5', '10'])
        ->and($props['wallet'])->toBe(['available' => ['currency' => 'RWF', 'amount' => '10000000'], 'sufficient' => true, 'revision' => 1])
        ->and($props['disclosure']['version'])->toBe(PrimaryDisclosure::VERSION)->and($props['disclosure']['sha256'])->toBe('')
        ->and($props['disclosure']['points'])->toHaveCount(4)
        ->and($props['risks'])->toMatchArray(['version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => false,
            'action' => ['url' => '/investor/risk-statements/acknowledgements', 'method' => 'post']])
        ->and($props['risks']['statements'])->toHaveCount(5)
        ->and($props['links'])->toMatchArray(['close' => ['url' => "/investor/deals/{$campaign}", 'method' => 'get'],
            'deposit' => ['url' => '/investor/wallet?kind=deposit', 'method' => 'get'], 'portfolio' => ['url' => '/investor/portfolio', 'method' => 'get'],
            'operation' => ['url' => "/investor/deals/{$campaign}/primary-operations/{request_id}", 'method' => 'get']])
        ->and($props['actions'])->toBe(['reserve' => ['url' => "/investor/deals/{$campaign}/reservations", 'method' => 'post'], 'confirm' => null, 'release' => null]);
});

it('moves to the bronze band at exactly RWF 1,000,000 of active capital and offers Reserve once the risks are acknowledged', function (string $units, string $tier, string $fee): void {
    ($this->acknowledge)();
    $props = ($this->page)(['units' => $units]);

    expect($props['quote']['units'])->toBe($units)->and($props['quote']['earnings_fee']['tier'])->toBe($tier)
        ->and($props['quote']['payout_fee']['amount'])->toBe($fee)
        ->and($props['risks'])->toMatchArray(['acknowledged' => true, 'action' => null])
        ->and($props['allowed_actions'])->toBe(['primary.reserve']);
})->with([
    'RWF 995,000' => ['199', 'standard', '16567'],
    'RWF 1,000,000' => ['200', 'bronze', '13320'],
]);

it('clamps the units to the 50% limit and names availability once others have filled the raise', function (): void {
    expect(($this->page)(['units' => '5000'])['quote']['units'])->toBe('1080');

    foreach ([PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()] as $other) {
        PrimaryReservationFixture::reserve($this->campaign, $other, '600');
    }
    $capacity = ($this->page)()['quote']['capacity'];

    expect($capacity['max_units'])->toBe('960')->and($capacity['binding'])->toBe('availability');
});

it('follows a web reserve to the held checkout, shows its exact quote, then follows the confirm to the commitment', function (): void {
    ($this->acknowledge)();
    $reserved = $this->postJson(route('investor.primary.reserve', ['campaign' => $this->campaign->id]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'units' => '2', 'expected_campaign_revision' => 1, 'quote_revision' => 1])
        ->assertOk()->assertJsonPath('code', 'RESERVATION_HELD')->json();
    $id = (string) $reserved['data']['receipt']['reservation_id'];

    expect($reserved['data']['next'])->toBe(['url' => "/investor/deals/{$this->campaign->id}/checkout?reservation={$id}", 'method' => 'get']);

    $props = ($this->page)();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->sole();

    expect($props['reservation'])->toMatchArray(['id' => $id, 'revision' => 1, 'units' => '2', 'state' => 'held'])
        ->and($props['quote'])->toMatchArray(['basis' => 'reserved', 'units' => '2', 'amount' => ['currency' => 'RWF', 'amount' => '10000']])
        ->and($props['quote']['rights'])->not->toBeNull()
        ->and($props['disclosure']['sha256'])->toBe($version->payload['disclosure_sha256'])
        ->and($props['allowed_actions'])->toBe(['primary.confirm', 'primary.release'])
        ->and($props['actions']['confirm'])->toBe(['url' => "/investor/reservations/{$id}/confirm", 'method' => 'post'])
        ->and($props['links']['operation']['url'])->toBe("/investor/deals/{$this->campaign->id}/primary-operations/{request_id}?reservation={$id}")
        ->and(($this->page)(['reservation' => $id])['reservation']['id'])->toBe($id);

    $confirmed = $this->postJson(route('investor.primary.confirm', ['reservation' => $id]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'reservation_id' => $id, 'expected_reservation_revision' => 1,
        'disclosure_version' => $version->payload['terms']['disclosure_version'], 'disclosure_sha256' => $version->payload['disclosure_sha256'],
        'acknowledged' => true])->assertOk()->assertJsonPath('code', 'RESERVATION_CONFIRMED')->json();

    expect($confirmed['data']['next'])->toBe(['url' => route('investor.commitments.show', ['commitment' => $confirmed['data']['receipt']['commitment_id']], false),
        'method' => 'get'])
        ->and(($this->page)(['reservation' => $id])['reservation'])->toBeNull();
});

it('leaves the checkout at its quote for an unknown, another Investor\'s or an expired reservation', function (): void {
    $other = PrimaryReservationFixture::investor();
    $theirs = PrimaryReservationFixture::reserve($this->campaign, $other, '2')['data']['reservation_id'];
    $mine = PrimaryReservationFixture::reserve($this->campaign, $this->investor, '2')['data']['reservation_id'];

    expect(($this->page)(['reservation' => strtolower((string) Str::ulid())])['reservation'])->toBeNull()
        ->and(($this->page)(['reservation' => $theirs])['reservation'])->toBeNull()
        ->and(($this->page)()['reservation']['id'])->toBe($mine);

    $this->travelTo(PrimaryReservationRecord::query()->whereKey($mine)->sole()->expires_at);

    expect(($this->page)(['reservation' => $mine])['reservation'])->toBeNull();
});

it('shows a person still being verified the quote with no wallet, no risks and nothing to do', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    $props = $this->actingAs($person)->get(route('investor.checkout', $this->campaign->id))->assertOk()->viewData('page')['props'];

    expect($props['allowed_actions'])->toBe([])->and($props['risks'])->toBeNull()
        ->and($props['quote']['capacity'])->toMatchArray(['max_units' => '0', 'binding' => 'restriction'])
        ->and($props['wallet'])->toBe(['available' => ['currency' => 'RWF', 'amount' => '0'], 'sufficient' => false, 'revision' => 0]);
});

it('serves the checkout on API v1 with API links and refuses an unknown deal', function (): void {
    Sanctum::actingAs($this->investor['user'], ['investor:read']);

    $this->getJson(route('api.v1.investor.checkout', ['campaign' => $this->campaign->id, 'units' => '3']))->assertOk()
        ->assertJsonPath('data.quote.units', '3')->assertJsonPath('data.links.portfolio', null)
        ->assertJsonPath('data.links.close', ['url' => "/api/v1/investor/deals/{$this->campaign->id}", 'method' => 'get'])
        ->assertJsonPath('data.risks.action', ['url' => '/api/v1/investor/risk-statements/acknowledgements', 'method' => 'post']);
    $this->getJson(route('api.v1.investor.checkout', ['campaign' => strtolower((string) Str::ulid())]))
        ->assertNotFound()->assertJsonPath('code', 'DEAL_NOT_FOUND');
    $this->getJson(route('api.v1.investor.checkout', ['campaign' => $this->campaign->id, 'units' => '0']))
        ->assertUnprocessable()->assertJsonValidationErrors('units');
});
