<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Models\BusinessCampaign;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\Support\BusinessQuoteFixture as QuoteFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->props = function (User $user, string $business): array {
        $props = $this->actingAs($user)->get(route('business.show', ['business' => $business]))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('business/home'))->viewData('page')['props'];

        return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
    };
    $this->purchase = function (BusinessCampaign $campaign, string $units): void {
        $investor = PrimaryReservationFixture::investor();
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1, $version->payload['terms']['disclosure_version'],
            $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    };
});

it('offers a released application for publication before anything is listed', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $business = $fixture['audit']['business'];
    $application = $fixture['application'];
    $user = $fixture['audit']['authority']['users'][0];
    app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id, $application->id, 0, 'Verified release.', (string) Str::uuid());

    $props = ($this->props)($user, $business);

    expect($props)->toMatchArray(['rating' => null, 'live_raise' => null, 'notes' => [], 'headroom' => null, 'unread_notifications' => 0,
        'wallet' => ['available' => ['currency' => 'RWF', 'amount' => '0']], 'create_application' => null,
        'today' => [['kind' => 'application_approved', 'title' => $application->refresh()->draft['title'], 'fee' => ['currency' => 'RWF', 'amount' => '0'],
            'link' => ['url' => "/business/{$business}/applications/{$application->id}/publish", 'method' => 'get']]],
        'capital' => ['raised' => ['currency' => 'RWF', 'amount' => '0'], 'investors' => 0, 'active_notes' => 0,
            'repaid' => ['currency' => 'RWF', 'amount' => '0'], 'on_time_pct' => null]])
        ->and($props['business']['name'])->toBeString()->not->toBe('')
        ->and(array_keys($props['business']))->toBe(['name', 'company_code', 'industry', 'district'])
        ->and($props['links'])->toBe(['home' => ['url' => "/business/{$business}", 'method' => 'get'], 'launcher' => ['url' => '/dashboard', 'method' => 'get'],
            'reports' => ['url' => "/business/{$business}/reports", 'method' => 'get'], 'profile' => ['url' => "/business/{$business}/profile", 'method' => 'get'],
            'wallet' => ['url' => "/business/{$business}/wallet", 'method' => 'get'],
            'deposit' => ['url' => "/business/{$business}/wallet?kind=deposit", 'method' => 'get'], 'withdraw' => null, 'notifications' => null,
            'rating' => ['url' => "/business/{$business}/rating", 'method' => 'get'], 'apply' => null]);
});

it('shows the live raise, then funded capital, from retained publication and funding facts only', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $user = User::query()->findOrFail($campaign->actor_user_id);
    ($this->purchase)($campaign, '1080');

    $live = ($this->props)($user, $campaign->business_id);
    $note = ['id' => $campaign->id, 'title' => $campaign->payload['title'], 'status' => 'active', 'created_at' => $campaign->payload['recorded_at'],
        'funded_pct' => 50.0, 'investors' => 1, 'raised' => ['currency' => 'RWF', 'amount' => '5400000'],
        'target' => ['currency' => 'RWF', 'amount' => $campaign->payload['principal']],
        'link' => ['url' => "/business/{$campaign->business_id}/campaigns/{$campaign->id}", 'method' => 'get']];

    expect($live['notes'])->toBe([$note])
        ->and($live['live_raise'])->toBe(array_intersect_key($note, array_flip(['title', 'funded_pct', 'investors', 'raised', 'target', 'link'])))
        ->and($live['rating'])->toBe(['band' => $campaign->payload['public_evidence']['rating']['band'], 'score' => $campaign->payload['public_evidence']['rating']['score']])
        ->and($live['today'])->toBe([])
        ->and($live['capital']['active_notes'])->toBe(0);

    ($this->purchase)($campaign, '1080');
    app(PrimaryFunding::class)->lock($campaign->id, fn (string $id): array => ['campaign_id' => $id, 'publication_sha256' => $campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated test.']])]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $funded = ($this->props)($user, $campaign->business_id);

    expect($funded['live_raise'])->toBeNull()
        ->and($funded['notes'][0])->toMatchArray(['status' => 'funded', 'funded_pct' => 100.0, 'investors' => 2,
            'raised' => ['currency' => 'RWF', 'amount' => $campaign->payload['principal']]])
        ->and($funded['capital'])->toBe(['raised' => ['currency' => 'RWF', 'amount' => $campaign->payload['principal']], 'investors' => 2,
            'active_notes' => 1, 'repaid' => ['currency' => 'RWF', 'amount' => '0'], 'on_time_pct' => null]);
});

it('keeps a cancelled raise as a failed note and leaves no live raise', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    expect(app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid())['code'])
        ->toBe('CAMPAIGN_CANCELLED');

    $props = ($this->props)(User::query()->findOrFail($campaign->actor_user_id), $campaign->business_id);

    expect($props['live_raise'])->toBeNull()
        ->and($props['notes'][0])->toMatchArray(['id' => $campaign->id, 'status' => 'failed', 'funded_pct' => 0.0, 'investors' => 0,
            'raised' => ['currency' => 'RWF', 'amount' => '0']]);
});

it('serves the bearer transport with its own links and scoped abilities', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $user = User::query()->findOrFail($campaign->actor_user_id);
    $url = route('api.v1.business.show', ['business' => $campaign->business_id]);

    Sanctum::actingAs($user, ['business:read']);
    $this->getJson($url)->assertOk()
        ->assertJsonPath('data.links.home.url', "/api/v1/business/{$campaign->business_id}")
        ->assertJsonPath('data.links.launcher.url', '/api/v1/identity')
        ->assertJsonPath('data.notes.0.link.url', "/api/v1/business/{$campaign->business_id}/campaigns/{$campaign->id}")
        ->assertJsonPath('data.links.reports', null)->assertJsonPath('data.links.profile', null)->assertJsonPath('data.links.rating', null)
        ->assertJsonPath('data.create_application', null);
    Sanctum::actingAs($user, ['business:command']);
    $this->getJson($url)->assertForbidden();
    $this->actingAs($user)->getJson(route('business.show', ['business' => $campaign->business_id, 'identity_context_revision' => -1]))
        ->assertUnprocessable()->assertJsonValidationErrors('identity_context_revision');
});

it('refuses a person outside the business mandate', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $other = AuditSealingFixture::ready()['audit']['authority']['users'][0];

    $this->actingAs($other)->get(route('business.show', ['business' => $campaign->business_id]))->assertNotFound();
});

it('starts a raise where the mandate allows it, and resumes an open draft', function (): void {
    $ready = QuoteFixture::ready();
    $authority = $ready['audit']['authority'];
    $user = $authority['users'][0];
    $none = AuthorityFixture::configure(AuthorityFixture::relatedOrganization($authority), 1)['data']['business']['id'];
    $draft = AuthorityFixture::configure(AuthorityFixture::relatedOrganization($authority), 1)['data']['business']['id'];
    $created = $this->actingAs($user)->postJson("/business/{$draft}/applications",
        ['identity_context_revision' => 1, 'expected_revision' => 0, 'request_id' => (string) Str::uuid()])->assertOk()->json();
    /** @var array<string, mixed> $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path('fixtures/ui/business-home.json')), true, flags: JSON_THROW_ON_ERROR)['props'];

    $fresh = ($this->props)($user, $none);
    expect(array_keys($fresh))->toEqualCanonicalizing(array_keys($fixture))
        ->and(array_keys($fresh['links']))->toEqualCanonicalizing(array_keys($fixture['links']))
        ->and(array_keys($fresh['capital']))->toEqualCanonicalizing(array_keys($fixture['capital']))
        ->and($fresh['create_application'])->toBe(['action' => ['url' => "/business/{$none}/applications", 'method' => 'post'],
            'operation' => ['url' => '/business/application-operations/{request_id}', 'method' => 'get'], 'identity_context_revision' => 1, 'expected_revision' => 0])
        ->and($fresh['links']['apply'])->toBeNull();

    $resumed = ($this->props)($user, $draft);
    expect($resumed['links']['apply'])->toBe($created['data']['next'])
        ->and($resumed['create_application'])->toBeNull()
        ->and($resumed['notes'])->toBe([]);
});
