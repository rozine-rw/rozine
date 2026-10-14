<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The wallet's internal history (design wallet history, internal view; BRS APP wallet history):
 * every hold, release, commitment and refund of the Investor's own purchases, newest first, named
 * by the deal, each opening its posting receipt.
 */

/**
 * @param  TestResponse<Response>  $response
 * @return array<string, mixed>
 */
function internalWalletPage(TestResponse $response): array
{
    return $response->assertOk()->viewData('page')['props'];
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (string $units, string $requestId) {
        $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, $units, $requestId, PrimaryReservationFixture::terms(...));
        $this->travel(1)->seconds();

        return $result['data']['reservation_id'];
    };
});

it('lists each hold, release, commitment and refund newest first, named by the deal, with its receipt', function (): void {
    $committedRequest = (string) Str::uuid();
    $committed = ($this->reserve)('3', $committedRequest);
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $committed)->sole();
    $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $committed, 1, $version->payload['terms']['disclosure_version'],
        $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travel(1)->seconds();
    $this->checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $committed, 2, (string) Str::uuid());
    $this->travel(1)->seconds();
    $released = ($this->reserve)('2', (string) Str::uuid());
    $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $released, 1, (string) Str::uuid());

    $title = app(PrimaryCampaignSource::class)->presentation($this->campaign->id)['title'];
    $internal = internalWalletPage($this->actingAs($this->investor['user'])->get(route('investor.wallet', ['movement' => 'internal'])));
    expect(array_map(fn (array $item): array => [$item['kind'], $item['from'], $item['to'], $item['amount']['amount'], $item['deal_name']], $internal['history']['items']))
        ->toBe([['hold_release', 'held', 'available', '10000', $title], ['hold', 'available', 'held', '10000', $title],
            ['commitment_refund', 'committed', 'available', '15000', $title], ['commitment', 'held', 'committed', '15000', $title],
            ['hold', 'available', 'held', '15000', $title]])
        ->and($internal['history']['items'][3]['link']['url'])->toBe('/investor/wallet?receipt='.$internal['history']['items'][3]['id'])
        ->and($internal['history']['pagination']['next'])->toBeNull();

    $commit = LedgerEntry::query()->where('kind', 'primary_commit')->sole();
    $receipt = internalWalletPage($this->get(route('investor.wallet', ['receipt' => strtoupper($commit->id)])))['receipt'];
    expect($receipt['kind'])->toBe('commitment')
        ->and($receipt['receipt'])->toMatchArray(['receipt_id' => $commit->id, 'operation_id' => $commit->origin_operation_id, 'request_id' => $committedRequest,
            'code' => 'PRIMARY_COMMITTED', 'amount' => ['currency' => 'RWF', 'amount' => '15000'], 'units' => '3',
            'reference' => 'RZW-'.strtoupper(substr($commit->id, -10)), 'revision' => 1,
            'policy_version' => $version->payload['terms']['policy_version'], 'disclosure_version' => null]);

    $other = PrimaryReservationFixture::investor();
    expect(internalWalletPage($this->actingAs($other['user'])->get(route('investor.wallet', ['receipt' => $commit->id])))['receipt'])->toBeNull()
        ->and(internalWalletPage($this->get(route('investor.wallet', ['movement' => 'internal'])))['history']['items'])->toBe([]);

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.wallet', ['movement' => 'internal']))->assertOk()
        ->assertJsonCount(5, 'data.history.items')->assertJsonPath('data.history.items.0.kind', 'hold_release');
});

it('pages internal history twenty at a time', function (): void {
    foreach (range(1, 21) as $index) {
        ($this->reserve)('1', (string) Str::uuid());
    }

    $first = internalWalletPage($this->actingAs($this->investor['user'])->get(route('investor.wallet', ['movement' => 'internal'])));
    $next = $first['history']['pagination']['next'];
    $second = internalWalletPage($this->get($next['url']));

    expect($first['history']['items'])->toHaveCount(20)->and($next['url'])->toStartWith('/investor/wallet?movement=internal&before=')
        ->and($second['history']['items'])->toHaveCount(1)->and($second['history']['pagination']['next'])->toBeNull();
});
