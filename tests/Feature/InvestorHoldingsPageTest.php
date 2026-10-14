<?php

declare(strict_types=1);

use App\Models\CommandOperation;
use App\Models\PrimaryHolding;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
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
        ->and($issue['terms'])->toBe(['rate_pct' => $holding->terms['rate_pct'], 'term_months' => 6, 'total_return' => $holding->rights['total_return'],
            'earnings_fee' => $holding->terms['earnings_fee'], 'policy_version' => $holding->terms['policy_version'], 'disclosure_version' => $holding->terms['disclosure_version']])
        ->and($issue['issue_receipt'])->toMatchArray(['receipt_id' => $holding->receipt_id, 'operation_id' => $receipt['operation_id'], 'code' => 'HOLDING_ISSUED',
            'request_id' => CommandOperation::query()->whereKey($receipt['operation_id'])->value('request_id'),
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
