<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\PartyNotices;
use App\Http\Resources\BusinessNoticesResource;
use App\Models\PartyNotice;
use App\Models\PrimaryHolding;
use App\Models\User;
use App\Notifications\Investor\InvestmentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * The raise and disbursement notices of BRS v2.0 NTF-R01: "raise funded" to each buying Party and
 * the Business when the funding locks, and "disbursement made" when the payout issues the
 * Holdings: each Investor's Holding (also e-mailed) and the funds sent to the Business.
 */

/** @return list<PartyNotice> */
function lifecycleNotices(string $kind): array
{
    return array_values(PartyNotice::query()->where('kind', $kind)->orderBy('party_id')->get()->all());
}

it('tells each buying Party its units and amount, and the Business, once, when the raise is funded', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, , $third]] = PrimaryHoldingFixture::committed(['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $buyers = DB::table('primary_reservations')->whereIn('id', [$first->primary_reservation_id, $third->primary_reservation_id])
        ->orderBy('party_id')->pluck('party_id')->all();

    $investors = lifecycleNotices('primary.raise_funded');
    expect(array_column($investors, 'party_id'))->toBe($buyers)
        ->and(array_map(fn (PartyNotice $notice): array => [$notice->facts['units'], $notice->facts['amount']], $investors))->each->toBe(['1080', '5400000'])
        ->and($investors[0]->facts)->toMatchArray(['currency' => 'RWF', 'campaign_id' => $campaign->id])
        ->and($investors[0]->facts['business'])->not->toBe('')
        ->and($investors[0]->event_key)->toBe('raise-funded:'.$campaign->id.':'.$buyers[0]);
    [$business] = lifecycleNotices('business.raise_funded');
    expect($business->facts)->toMatchArray(['principal' => (string) $campaign->principal, 'investors' => '2', 'currency' => 'RWF',
        'business_id' => $campaign->business_id, 'campaign_id' => $campaign->id])
        ->and(lifecycleNotices('business.raise_funded'))->toHaveCount(1);

    $user = User::query()->where('party_id', $buyers[0])->sole();
    $this->actingAs($user)->getJson(route('investor.notices'))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'primary.raise_funded')
        ->assertJsonPath('data.notices.0.link', ['url' => '/investor/portfolio', 'method' => 'get']);
});

it('tells each Investor its issued Holding, by notice and e-mail, and the Business the funds sent to its account', function (): void {
    Notification::fake();
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00', units: ['1000', '80', '1080'], buyers: ['solo', 'solo', 'other']);
    $holdings = PrimaryHolding::query()->orderBy('id')->get();

    $issued = lifecycleNotices('primary.holding_issued');
    expect($issued)->toHaveCount(3);
    $large = PrimaryHolding::query()->where('units', 1000)->sole();
    $notice = PartyNotice::query()->where('event_key', 'holding-issued:'.$large->id)->sole();
    $rights = $large->rights;
    // Effective on 10 October in Kigali, the sixth and last instalment falls due on 10 April.
    $maturity = '2027-04-10';
    $return = (int) $rights['total_return']['amount'] - (int) $large->terms['payout_fee']['amount'];
    expect($notice->party_id)->toBe($large->party_id)
        ->and($notice->facts)->toMatchArray(['units' => '1000', 'amount' => '5000000', 'expected_return' => (string) $return,
            'maturity_value' => (string) (5000000 + $return), 'maturity_date' => $maturity, 'currency' => 'RWF',
            'reference' => $large->receipt_id, 'holding_id' => $large->id, 'campaign_id' => $large->business_campaign_id]);
    $user = User::query()->where('party_id', $large->party_id)->sole();
    Notification::assertSentToTimes($user, InvestmentConfirmed::class, 2);
    Notification::assertSentTo($user, InvestmentConfirmed::class, fn (InvestmentConfirmed $mail): bool => $mail->notes === 1000
        && $mail->maturityDate->format('Y-m-d') === $maturity && str_ends_with($mail->url, '/investor/holdings/'.$large->id));

    [$sent] = lifecycleNotices('business.disbursement_sent');
    // The Business receives its raise less the 3% platform fee (BRS v2.0 SVC-R01).
    expect($sent->facts)->toMatchArray(['amount' => '10476000', 'principal' => (string) $campaign->principal, 'platform_fee' => '324000',
        'currency' => 'RWF', 'business_id' => $campaign->businessId,
        'campaign_id' => $campaign->campaignId])
        ->and($sent->facts['account'])->toContain('••••')
        ->and($sent->facts['reference'])->toBe($holdings[0]->disbursement_closing_id);

    $this->actingAs($user)->getJson(route('investor.notices'))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'primary.holding_issued')
        ->assertJsonPath('data.notices.0.link.url', fn (string $url): bool => str_starts_with($url, '/investor/holdings/'));
});

it('opens the Business\'s raise-funded and funds-sent notices on the campaign', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $inbox = app(PartyNotices::class)->partyInbox(PartyNotice::query()->where('kind', 'business.raise_funded')->sole()->party_id, 20);
    $links = array_slice((new BusinessNoticesResource([...$inbox, 'business_id' => $campaign->businessId]))->toArray(request())['notices'], 0, 2);

    expect(array_column($links, 'kind'))->toBe(['business.disbursement_sent', 'business.raise_funded'])
        ->and(array_unique(array_column(array_column($links, 'link'), 'url')))->toBe(["/business/{$campaign->businessId}/campaigns/{$campaign->campaignId}"]);
});
