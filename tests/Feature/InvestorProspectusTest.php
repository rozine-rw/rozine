<?php

declare(strict_types=1);

use App\Application\Business\GetBusinessProspectus;
use App\Application\Business\SaveBusinessProspectus;
use App\Application\Business\SignBusinessProspectus;
use App\Application\Business\UploadProspectusDocument;
use App\Models\BusinessProspectusVersion;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The signed prospectus as an Investor reads it (BRS v3.0 PRO-R04): the Prospectus card leads the
 * deal's Details, and Read opens exactly the signed version, with its fingerprint and signer, in
 * the app. Evidence documents stay private; only which lines and months cite one is shown.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    InvestorWalletFixture::policy(maximum: null);
    $this->signed = function (array $fixture): void {
        $user = $fixture['audit']['authority']['users'][0]->id;
        $business = $fixture['audit']['business'];
        $application = $fixture['application']->id;
        $people = app(GetBusinessProspectus::class)->handle($user, 1, $business, $application)['inputs']['profiles'];
        $upload = fn (string $kind, string $filename, string $content): string => app(UploadProspectusDocument::class)->handle($user, 1, $business, $application,
            $kind, $filename, $content, (string) Str::uuid())['data']['document_id'];
        $quote = $upload('quote', 'cold-room-quote.pdf', "%PDF-1.7\nSupplier quote\n");
        $contract = $upload('contract', 'hotel-contract.pdf', "%PDF-1.7\nSigned supply contract\n");
        $profile = 'I have run the workshop for eleven years, '.str_repeat('managing suppliers, staff and the cold chain every week ', 5).'and I keep the books myself.';
        app(SaveBusinessProspectus::class)->handle($user, 1, $business, $application, 0, [
            'purpose' => 'Buy and install a solar cold room so fresh produce keeps for a week, cutting spoilage and letting us take larger orders.',
            'use_of_funds' => [['label' => 'Solar cold room', 'amount' => '8476000', 'quote_amount' => '8500000', 'quote_document_id' => $quote],
                ['label' => 'Installation and wiring', 'amount' => '2000000', 'quote_amount' => null, 'quote_document_id' => null]],
            'forecast' => array_map(fn (int $month): array => ['month' => sprintf('2027-%02d', $month), 'revenue' => '4000000', 'costs' => '1000000',
                'contract_document_id' => $month === 2 ? $contract : null], range(1, 6)),
            'assumptions' => 'Sales hold at the verified average while the cold room cuts spoilage.',
            'downside' => 'If sales fall by a third we pause new stock purchases and still meet each instalment.',
            'profiles' => array_map(fn (array $person): array => [...$person, 'text' => $profile], $people), 'largest_customer_share_percent' => 10,
            'risks' => [['text' => 'Power cuts can spoil stock before the batteries take over.', 'concentration' => false],
                ['text' => 'Produce prices swing with the harvest season.', 'concentration' => false],
                ['text' => 'A new competitor could open nearby.', 'concentration' => false]]], (string) Str::uuid());
        foreach (array_slice($fixture['audit']['authority']['users'], 1) as $other) {
            expect(app(SignBusinessProspectus::class)->handle($other->id, 1, $business, $application, 1, (string) Str::uuid()))
                ->toMatchArray(['status' => 'rejected', 'code' => 'ACTION_FORBIDDEN']);
        }
        expect(app(SignBusinessProspectus::class)->handle($user, 1, $business, $application, 1, (string) Str::uuid())['status'])->toBe('completed');
        $this->evidence = [$quote, $contract];
        $this->representative = $people[0]['name'];
        $this->signer = $fixture['audit']['authority']['people'][0]->id;
    };
});

it('leads the deal\'s details with the signed prospectus and opens it in the app', function (): void {
    $campaign = PrimaryReservationFixture::campaign(beforeRelease: $this->signed, signatories: 2, requiredSignatories: 2);
    $investor = PrimaryReservationFixture::investor();
    $signed = BusinessProspectusVersion::query()->where('kind', 'signed')->sole();
    $route = ['campaign' => $campaign->id];

    $this->actingAs($investor['user'])->get(route('investor.deals.show', $route))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('investor/deal')
            ->where('deal.prospectus', ['version' => 2, 'fingerprint' => $signed->fingerprint, 'signed_at' => $signed->created_at->toIso8601String(),
                'signed_by' => $this->representative])
            ->where('deal.links.prospectus', ['url' => route('investor.deals.prospectus', $route, false), 'method' => 'get']));

    $response = $this->get(route('investor.deals.prospectus', $route))->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page->component('investor/prospectus')
        ->where('contract_version', 'investor-prospectus-v1')->where('deal.campaign_id', $campaign->id)
        ->where('prospectus.version', 2)->where('prospectus.fingerprint', $signed->fingerprint)
        ->where('inputs.use_of_funds.0.label', 'Solar cold room')->where('quoted_lines', [0])->where('contracted_months', ['2027-02'])
        ->where('facts', ['principal' => '10800000', 'term_months' => 6, 'instalment' => '1999800', 'verified_monthly_revenue' => '4000000',
            'latest_sealed_report_at' => $signed->payload['document']['facts']['latest_sealed_report_at'] ?? null, 'platform_fee' => '324000', 'net_of_fee' => '10476000'])
        ->where('links.back', ['url' => route('investor.deals.show', $route, false), 'method' => 'get'])->where('links.wallet', null));
    $api = $this->getJson(route('api.v1.investor.deals.prospectus', $route))->assertOk()
        ->assertJsonPath('data.prospectus.fingerprint', $signed->fingerprint)->assertJsonPath('data.prospectus.signed_by', $this->representative)
        ->assertJsonPath('data.links.portfolio', null)->assertJsonPath('data.links.back.url', route('api.v1.investor.deals.show', $route, false));
    $signer = collect($signed->payload['document'] ?? [])->only(['signed_by_party_id', 'signed_by_name'])->all();
    expect($signer)->toBe(['signed_by_party_id' => $this->signer,
        'signed_by_name' => $this->representative]);
    foreach ([json_encode($response->viewData('page')['props']), $api->getContent()] as $payload) {
        expect($payload)->not->toContain('cold-room-quote.pdf')->not->toContain('hotel-contract.pdf')->not->toContain('registered_names')
            ->not->toContain('evidence')->not->toContain('quote_document_id')->not->toContain('contract_document_id')
            ->not->toContain('quote_amount')->not->toContain($this->evidence[0])->not->toContain($this->evidence[1]);
    }
});

it('shows no prospectus card before one is signed, and has nothing to read for it or an unknown deal', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $this->get(route('investor.deals.prospectus', ['campaign' => $campaign->id]))->assertRedirect(route('login'));

    $this->actingAs($investor['user'])->get(route('investor.deals.show', ['campaign' => $campaign->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('deal.prospectus', null)->where('deal.links.prospectus', null));
    $this->getJson(route('api.v1.investor.deals.prospectus', ['campaign' => $campaign->id]))->assertNotFound()->assertJsonPath('code', 'PROSPECTUS_NOT_FOUND');
    $this->getJson(route('api.v1.investor.deals.prospectus', ['campaign' => (string) Str::ulid()]))->assertNotFound()->assertJsonPath('code', 'DEAL_NOT_FOUND');
});
