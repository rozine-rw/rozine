<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Models\BusinessCampaign;
use App\Models\DisbursementIntent;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryFailedClosingFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * The Investor's issued Holdings (C3 v2 §2d–§2e): Portfolio lists each Holding the primary issue
 * gave them with its business, and the server's totals, projected payouts, industries, risk bands
 * and concentration; the Holding page shows the issue as recorded. Nothing is serviced yet, so
 * nothing has been received.
 */

/** @return array{holdings: list<PrimaryHolding>, user: User} */
function issuedHoldings(): array
{
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00', units: ['1000', '80', '1080'], buyers: ['solo', 'solo', 'other']);
    $first = PrimaryHolding::query()->where('units', 1000)->sole();
    $holdings = array_values(PrimaryHolding::query()->where('party_id', $first->party_id)->orderBy('principal', 'desc')->get()->all());

    return ['holdings' => $holdings, 'user' => User::query()->where('party_id', $first->party_id)->sole()];
}

it('lists each issued Holding on Portfolio with the server totals and the instalments still to come', function (): void {
    ['holdings' => [$large, $small], 'user' => $user] = issuedHoldings();
    // The first instalment, due in November, has passed.
    $this->travelTo(CarbonImmutable::parse('2026-12-01 09:00:00', 'Africa/Kigali'));

    $props = $this->actingAs($user)->get(route('investor.portfolio'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/portfolio'))->viewData('page')['props'];
    $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

    expect(array_column($props['holdings'], 'id'))->toEqualCanonicalizing([$large->id, $small->id])
        ->and($props['holdings'][0])->toMatchArray(['health' => 'healthy', 'gain' => $money('0'), 'gain_pct' => '0.0', 'repaid_pct' => 0,
            'payments_made' => 0, 'payments_total' => 6, 'matures_on' => '2027-04-10T00:00:00+02:00'])
        ->and($props['holdings'][0]['link']['url'])->toStartWith('/investor/holdings/')
        ->and($props['totals'])->toMatchArray(['businesses' => 1, 'invested' => $money('5400000'), 'value' => $money('5400000'), 'gain' => $money('0'), 'this_month' => $money('0')])
        // November is past; December to April remain, net of the 15% earnings fee on each instalment's return.
        ->and(array_column($props['payouts'], 'month'))->toBe(['2026-12-01T00:00:00+02:00', '2027-01-01T00:00:00+02:00', '2027-02-01T00:00:00+02:00',
            '2027-03-01T00:00:00+02:00', '2027-04-01T00:00:00+02:00'])
        ->and($props['totals']['next_payout'])->toBe(['amount' => $props['payouts'][0]['amount'], 'month' => '2026-12-01T00:00:00+02:00'])
        ->and($props['payouts'][0]['payers'])->toHaveCount(1)
        ->and(array_column($props['payouts'], 'bar_pct'))->toContain(100)
        ->and($props['industries'])->toHaveCount(1)->and($props['industries'][0]['share_pct'])->toBe(100)
        ->and($props['risk'])->toHaveCount(1)->and($props['risk'][0]['share_pct'])->toBe(100)
        ->and($props['concentration'])->toMatchArray(['status' => 'concentrated', 'share_pct' => 100])
        ->and($props['commitments'])->toBe([]);

    $march = (int) $props['payouts'][0]['amount']['amount'];
    $instalment = $large->schedule[1];
    $fee = intdiv((int) $instalment['return']['amount'] * 1500 + 5000, 10000);
    $smallInstalment = $small->schedule[1];
    $smallFee = intdiv((int) $smallInstalment['return']['amount'] * 1500 + 5000, 10000);
    expect($march)->toBe((int) $instalment['principal']['amount'] + (int) $instalment['return']['amount'] - $fee
        + (int) $smallInstalment['principal']['amount'] + (int) $smallInstalment['return']['amount'] - $smallFee)
        ->and($props['totals']['projected_3m']['amount'])->toBe((string) array_sum(array_map(fn (array $month): int => (int) $month['amount']['amount'], array_slice($props['payouts'], 0, 3))));

    foreach (['matured', 'secondary', 'saved'] as $tab) {
        $this->get(route('investor.portfolio', ['tab' => $tab]))->assertOk()->assertInertia(fn (Assert $page) => $page->where('holdings', [])
            ->where('totals.invested', $money('5400000')));
    }
});

it('keeps an Investor with no Holding on the empty Portfolio', function (): void {
    $investor = InvestorWalletFixture::investor();

    $this->actingAs($investor['user'])->get(route('investor.portfolio'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('holdings', [])->where('payouts', [])->where('industries', [])->where('risk', [])
            ->where('concentration', null)->where('totals.next_payout', null)->where('totals.businesses', 0));
});

it('builds Holding figures from the authenticated issue and fails closed when a native column disagrees with it', function (string $read): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        $rights = $holding->rights;
        $rights['total_return']['amount'] = '1';
        $holding->forceFill(['rights' => $rights])->save();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }

    $this->withoutExceptionHandling()->actingAs($user);
    $url = $read === 'holding' ? route('investor.holdings.show', $holding->id) : route('investor.portfolio');
    expect(fn () => $this->get($url))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
})->with(['holding', 'portfolio']);

it('fails closed on an Investor Holding read when the issued membership is partial', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    try {
        DB::table('primary_holdings')->where('business_campaign_id', $holding->business_campaign_id)->where('party_id', '!=', $holding->party_id)->delete();
    } finally {
        DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    }

    $this->withoutExceptionHandling()->actingAs($user);
    expect(fn () => $this->get(route('investor.holdings.show', $holding->id)))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
});

it('opens a Holding with its issue as recorded, its maturity value and its next instalment', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();
    $receipt = $holding->payload['receipt'];

    $props = $this->actingAs($user)->get(route('investor.holdings.show', $holding->id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/holding'))->viewData('page')['props'];
    $issue = $props['holding']['issue'];
    $first = $holding->schedule[0];
    $fee = intdiv((int) $first['return']['amount'] * 1500 + 5000, 10000);

    expect($props['contract_version'])->toBe('investor-primary-v1')
        ->and($props['holding'])->toMatchArray(['id' => $holding->id, 'invested' => ['currency' => 'RWF', 'amount' => '5000000'],
            'rate_pct' => $holding->terms['rate_pct'], 'expected_profit' => $holding->rights['total_return'],
            'maturity_value' => ['currency' => 'RWF', 'amount' => (string) (5000000 + (int) $holding->rights['total_return']['amount'] - (int) $holding->terms['payout_fee']['amount'])],
            'received' => ['currency' => 'RWF', 'amount' => '0'], 'months_left' => 6, 'on_time' => ['made' => 0, 'on_time' => 0, 'late' => 0],
            'recovery_plan' => null, 'arrears' => null, 'updates' => [],
            'next_payment' => ['amount' => ['currency' => 'RWF', 'amount' => (string) ((int) $first['principal']['amount'] + (int) $first['return']['amount'] - $fee)],
                'due_on' => '2026-11-10T00:00:00+02:00']])
        ->and($issue)->toMatchArray(['id' => $holding->id, 'campaign_id' => $holding->business_campaign_id, 'units' => '1000',
            'ordinals' => [['first' => '1', 'last' => '1000']], 'effective_date' => '2026-10-10', 'disbursement_effective_at' => '2026-10-09T22:30:00+00:00'])
        ->and(array_column($issue['schedule'], 'due_on'))->toBe(['2026-11-10', '2026-12-10', '2027-01-10', '2027-02-10', '2027-03-10', '2027-04-10'])
        ->and($issue['terms'])->toEqual(['rate_pct' => $holding->terms['rate_pct'], 'term_months' => 6, 'total_return' => $holding->rights['total_return'],
            'earnings_fee' => $holding->terms['earnings_fee'], 'policy_version' => $holding->terms['policy_version'], 'disclosure_version' => $holding->terms['disclosure_version']])
        ->and($issue['issue_receipt'])->toMatchArray(['receipt_id' => $holding->receipt_id, 'operation_id' => $receipt['operation_id'], 'code' => 'HOLDING_ISSUED',
            /* Paid automatically (SVC-R01): the request is the system intent's own, never a staff command's. */
            'request_id' => DisbursementIntent::query()->where('operation_id', $receipt['operation_id'])->value('request_id'),
            'reference' => 'RZH-'.strtoupper(substr($holding->receipt_id, -10)), 'link' => ['url' => '/investor/holdings/'.$holding->id, 'method' => 'get']])
        ->and($issue['source'])->toBe(['commitment_id' => $holding->commitment_id, 'disbursement_operation_id' => $receipt['operation_id']])
        ->and($props['links']['back'])->toBe(['url' => '/investor/portfolio', 'method' => 'get']);

    /** @var array{props: array{holding: array<string, mixed>, links: array<string, mixed>}} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path('fixtures/ui/investor-holding-live-minimal.json')), true, flags: JSON_THROW_ON_ERROR);
    expect(array_keys(array_intersect_key($props, $fixture['props'])))->toEqualCanonicalizing(array_keys($fixture['props']))
        ->and(array_keys($props['holding']))->toEqualCanonicalizing(array_keys($fixture['props']['holding']))
        ->and(array_keys($issue))->toEqualCanonicalizing(array_keys($fixture['props']['holding']['issue']))
        ->and(array_keys($issue['issue_receipt']))->toEqualCanonicalizing(array_keys($fixture['props']['holding']['issue']['issue_receipt']))
        ->and(array_keys($props['links']))->toEqualCanonicalizing(array_keys($fixture['props']['links']));
});

it('shows the business\'s published monthly reports on the Holding page', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();
    $business = BusinessCampaign::query()->whereKey($holding->business_campaign_id)->value('business_id');
    app()->instance(PublishedMonthlyReports::class, new class($business) implements PublishedMonthlyReports
    {
        public function __construct(private string $businessId) {}

        public function forBusiness(string $businessId): array
        {
            return $businessId !== $this->businessId ? [] : [['report_id' => 'r1', 'period' => '2026-11', 'inflow' => '900', 'outflow' => '1000', 'net' => '-100',
                'operational_status' => 'active', 'differences' => false, 'auditor' => ['name' => 'Diane Uwase', 'licence' => 'ICPAR/P-2026/0481', 'sealed_on' => '2026-12-07'],
                'auditor_note' => '', 'business_note' => '']];
        }
    });

    $this->actingAs($user)->get(route('investor.holdings.show', $holding->id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('holding.updates.0.id', 'r1')->where('holding.updates.0.month', '2026-11-01T00:00:00+02:00')
            ->where('holding.updates.0.net', ['currency' => 'RWF', 'amount' => '-100'])->where('holding.updates.0.status', 'healthy'));
});

it('keeps a Holding from anyone but its holder, and answers over API v1 with investor:read', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();

    $this->actingAs(InvestorWalletFixture::investor()['user'])->getJson(route('investor.holdings.show', $holding->id))->assertNotFound()
        ->assertJsonPath('code', 'HOLDING_NOT_FOUND');
    $this->actingAs($user)->getJson(route('investor.holdings.show', strtolower((string) Str::ulid())))->assertNotFound();

    Sanctum::actingAs($user, ['investor:command']);
    $this->getJson(route('api.v1.investor.holdings.show', $holding->id))->assertForbidden();
    Sanctum::actingAs($user, ['investor:read']);
    $this->getJson(route('api.v1.investor.holdings.show', $holding->id))->assertOk()
        ->assertJsonPath('data.holding.issue.issue_receipt.link.url', '/api/v1/investor/holdings/'.$holding->id)
        ->assertJsonPath('data.links.portfolio', null)->assertJsonPath('data.links.wallet.url', '/api/v1/investor/wallet');
});

it('opens an issued commitment as issued, linked to its Holding, and leaves it off the awaiting list', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();

    $this->actingAs($user)->get(route('investor.commitments.show', $holding->commitment_id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('commitment.state', 'issued')->where('commitment.closing', null)
            ->where('commitment.holding', ['id' => $holding->id, 'link' => ['url' => '/investor/holdings/'.$holding->id, 'method' => 'get']]));
    $this->get(route('investor.portfolio'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('commitments', []));
});

it('gives Portfolio, the commitment and the Holding their change beacon at the render-time cursor, on the web and over API v1', function (): void {
    ['holdings' => [$holding], 'user' => $user] = issuedHoldings();
    $pages = [
        [route('investor.portfolio'), '/changes?topics=purchase%2Cwallet&after='],
        [route('investor.commitments.show', $holding->commitment_id), '/changes?topics=purchase&after='],
        [route('investor.holdings.show', $holding->id), '/changes?topics=purchase&after='],
    ];
    foreach ($pages as [$url, $prefix]) {
        $link = $this->actingAs($user)->get($url)->assertOk()->viewData('page')['props']['links']['changes'];
        expect($link['method'])->toBe('get')->and($link['url'])->toStartWith($prefix)
            ->and($this->getJson($link['url'])->assertOk()->json('changes'))->toBe([]);
    }

    Sanctum::actingAs($user, ['investor:read']);
    expect($this->getJson(route('api.v1.investor.commitments.show', ['commitment' => $holding->commitment_id, 'identity_context_revision' => 1]))
        ->json('data.links.changes.url'))->toStartWith('/api/v1/changes?topics=purchase&after=')
        ->and($this->getJson(route('api.v1.investor.holdings.show', $holding->id))->json('data.links.changes.url'))
        ->toStartWith('/api/v1/changes?topics=purchase&after=');
});

it('lists purchases awaiting issue apart from the Holdings: confirmed while the raise is open, funded once it is locked', function (bool $fund, ?bool $sent, string $state, ?array $closing, ?string $observed = null): void {
    ['campaign' => $campaign, 'commitments' => [$commitment]] = PrimaryHoldingFixture::committed(fund: $fund);
    if ($sent !== null) {
        PrimaryHoldingFixture::inFlight($campaign, $sent, $observed);
    } elseif ($observed === 'approval') {
        PrimaryHoldingFixture::awaitingApproval($campaign);
    }
    $user = User::query()->where('party_id', PrimaryReservationRecord::query()->whereKey($commitment->primary_reservation_id)->value('party_id'))->sole();

    $this->actingAs($user)->get(route('investor.portfolio'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('holdings', [])
        ->where('totals.invested', ['currency' => 'RWF', 'amount' => '0'])
        ->where('commitments', [['id' => $commitment->id, 'deal_name' => $page->toArray()['props']['commitments'][0]['deal_name'], 'units' => '1080',
            'principal' => ['currency' => 'RWF', 'amount' => '5400000'], 'state' => $state, 'closing' => $closing,
            'link' => ['url' => '/investor/commitments/'.$commitment->id, 'method' => 'get']]]));
    $this->get(route('investor.commitments.show', $commitment->id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('commitment.state', $state)->where('commitment.closing', $closing));
})->with([
    'raise open' => [false, null, 'confirmed', null],
    'funding locked' => [true, null, 'funded', ['stage' => 'awaiting_disbursement']],
    'payout awaiting its second approver' => [true, null, 'funded', ['stage' => 'awaiting_disbursement'], 'approval'],
    'payout sent' => [true, true, 'funded', ['stage' => 'in_flight', 'provider' => 'pending']],
    'payout send unconfirmed' => [true, false, 'funded', ['stage' => 'in_flight', 'provider' => 'unknown']],
    'provider reports pending' => [true, false, 'funded', ['stage' => 'in_flight', 'provider' => 'pending'], 'pending'],
    'provider reports unknown' => [true, true, 'funded', ['stage' => 'in_flight', 'provider' => 'unknown'], 'unknown'],
]);

it('shows a purchase whose funded raise failed its closing as failed, with the refund, and leaves it off Portfolio', function (): void {
    ['campaign' => $campaign] = PrimaryFailedClosingFixture::close();
    PrimaryHoldingFixture::flushDeferredChecks();
    $purchase = $campaign->commitments[0];
    $user = User::query()->where('party_id', $purchase->partyId)->sole();
    $refund = DB::table('primary_campaign_closure_returns')->where('primary_commitment_id', $purchase->id)->sole();

    $this->actingAs($user)->get(route('investor.commitments.show', $purchase->id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('commitment.state', 'failed_closing')->where('commitment.cancelled_by', null)
            ->where('commitment.closing', null)->where('commitment.holding', null)
            ->where('commitment.refund.receipt_id', $refund->return_entry_id)->where('commitment.refund.code', 'COMMITMENT_REFUNDED')
            ->where('commitment.refund.amount', ['currency' => 'RWF', 'amount' => $purchase->principal])
            ->where('commitment.refund.link', ['url' => '/investor/wallet?receipt='.$refund->return_entry_id, 'method' => 'get']));
    $this->get(route('investor.portfolio'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('holdings', [])->where('commitments', []));
});
