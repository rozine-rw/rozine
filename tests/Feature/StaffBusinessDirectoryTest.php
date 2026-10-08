<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(function (): void {
    $this->analyst = businessDirectoryStaff(['analyst']);
});

/** @param  list<string>  $roles */
function businessDirectoryStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Business directory assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/**
 * A published note on its own Business, renamed so rows read clearly.
 *
 * @param  array<string, mixed>  $payload
 * @param  array<string, mixed>  $attributes
 */
function businessDirectoryNote(string $business, string $industry, array $payload, array $attributes = []): BusinessCampaign
{
    $campaign = BusinessCampaign::factory()->create(['payload' => $payload, ...$attributes]);
    $profile = BusinessProfile::query()->whereKey($campaign->business_id)->sole();
    $profile->forceFill(['profile' => [...$profile->profile, 'name' => $business, 'industry' => $industry]])->save();

    return $campaign;
}

test('every staff role may open the Business directory; participants and guests may not', function (): void {
    $this->actingAs($this->analyst)->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('kind', 'business')->where('viewer.role', 'analyst')
            ->where('contract_version', 'staff-business-directory-v1')->where('nav.businesses.url', '/admin/businesses')
            ->where('nav.auditors', null)->where('nav.investors', null)->where('policy', [])->where('party', null));
    $this->actingAs(businessDirectoryStaff(['superadmin']))->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin')->where('nav.auditors.url', '/admin/auditors'));
    $this->actingAs(businessDirectoryStaff(['treasury']))->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'analyst'));
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.businesses.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.businesses.index'))->assertRedirect(route('login'));
});

test('an empty platform shows the design with zero figures, no sectors and no rows', function (): void {
    $this->actingAs($this->analyst)->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [['key' => 'total_businesses', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'avg_rating', 'value' => ['kind' => 'text', 'value' => '—']],
                ['key' => 'active_notes', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'capital_raised', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '0']]]])
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all() === ['all' => 0, 'healthy' => 0, 'watch' => 0, 'distressed' => 0, 'frozen' => 0])
            ->where('chips.0', ['key' => 'all', 'count' => 0, 'link' => ['url' => '/admin/businesses', 'method' => 'get'], 'active' => true])
            ->has('filters', 1)->where('filters.0.key', 'sort')->where('filters.0.value', 'raised')
            ->where('shown', 0)->where('total', 0)->where('directory', ['kind' => 'business', 'rows' => []]));
});

test('rows carry real notes, ratings and KYC, and the chips, sectors, sort and search narrow them', function (): void {
    $seed = businessDirectoryNote('Isoko Farms', 'Agriculture', ['title' => 'Seed stock', 'public_evidence' => ['rating' => ['band' => 'stable', 'score' => '3.1']]]);
    $mill = businessDirectoryNote('Kivu Mills', 'Manufacturing', ['title' => 'Mill line', 'public_evidence' => ['rating' => ['band' => 'strong', 'score' => '4.4']]]);
    $cancelled = businessDirectoryNote('Rugali Freight', 'Retail', ['title' => 'Trucks'], ['live_at' => now()->subDay()->startOfSecond()]);
    BusinessCampaignClosure::factory()->create(['business_campaign_id' => $cancelled->id, 'closed_at' => now()->subHour()->startOfSecond()]);
    $bakery = BusinessProfile::factory()->create(['entity_party_id' => Party::factory(),
        'profile' => ['name' => 'Amahoro Bakery', 'company_code' => 'RDB-77', 'industry' => 'Food', 'district' => 'Huye', 'established_year' => 2019]]);

    $this->actingAs($this->analyst)->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.0.value.value', 4)->where('stats.1.value', ['kind' => 'text', 'value' => '3.8 / 5'])
            ->where('stats.2.value.value', 2)->where('stats.3.value.value.amount', '0')
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all() === ['all' => 4, 'healthy' => 4, 'watch' => 0, 'distressed' => 0, 'frozen' => 0])
            ->where('filters.0', ['key' => 'sector', 'value' => 'all', 'options' => [['value' => 'all', 'label' => 'All sectors'],
                ['value' => 'Agriculture', 'label' => 'Agriculture'], ['value' => 'Food', 'label' => 'Food'],
                ['value' => 'Manufacturing', 'label' => 'Manufacturing'], ['value' => 'Retail', 'label' => 'Retail']]])
            ->where('shown', 4)->where('total', 4)
            ->where('directory.rows', function (Collection $rows) use ($seed, $bakery): bool {
                return $rows->pluck('name')->all() === ['Amahoro Bakery', 'Isoko Farms', 'Kivu Mills', 'Rugali Freight']
                    && $rows->firstWhere('name', 'Isoko Farms') === ['id' => $seed->business_id, 'name' => 'Isoko Farms', 'sector' => 'Agriculture',
                        'rating' => ['band' => 'stable', 'score' => '3.1'], 'active_notes' => 1, 'investors' => 0, 'raised' => ['currency' => 'RWF', 'amount' => '0'],
                        'capacity_used_pct' => null, 'health' => 'healthy', 'frozen' => false, 'kyc' => 'verified',
                        'link' => ['url' => '/admin/businesses?business='.$seed->business_id, 'method' => 'get']]
                    && $rows->firstWhere('name', 'Amahoro Bakery')['kyc'] === 'pending' && $rows->firstWhere('name', 'Amahoro Bakery')['rating'] === null
                    && $rows->firstWhere('name', 'Amahoro Bakery')['id'] === $bakery->id
                    && $rows->firstWhere('name', 'Rugali Freight')['active_notes'] === 0 && $rows->firstWhere('name', 'Rugali Freight')['rating'] === null;
            }));

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['sort' => 'name', 'sector' => 'Agriculture', 'q' => 'isoko', 'chip' => 'healthy']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'isoko')->where('filters.0.value', 'Agriculture')->where('filters.1.value', 'name')
            ->where('stats.0.value.value', 1)->where('stats.1.value.value', '3.1 / 5')
            ->where('chips.1', ['key' => 'healthy', 'count' => 1, 'active' => true,
                'link' => ['url' => '/admin/businesses?q=isoko&sector=Agriculture&sort=name&chip=healthy', 'method' => 'get']])
            ->where('shown', 1)->where('total', 1)->where('directory.rows.0.name', 'Isoko Farms')
            ->where('directory.rows.0.link.url', '/admin/businesses?q=isoko&sector=Agriculture&sort=name&chip=healthy&business='.$seed->business_id));
    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['sector' => 'all', 'q' => 'kivu']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.0.value', 'all')->where('directory.rows.0.id', $mill->business_id));
    foreach (['watch', 'distressed', 'frozen'] as $chip) {
        $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['chip' => $chip]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('total', 0)->where('directory.rows', [])->where('stats.0.value.value', 4));
    }
    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['chip' => 'everyone']))->assertSessionHasErrors('chip');
});

test("a Business's 360 carries its notes, rating, KYC and history, and an unknown Business is not found", function (): void {
    $cancelled = businessDirectoryNote('Rugali Freight', 'Retail', ['title' => 'Trucks', 'public_evidence' => ['rating' => ['band' => 'weak', 'score' => '2.4']]],
        ['live_at' => now()->subDay()->startOfSecond()]);
    BusinessCampaignClosure::factory()->create(['business_campaign_id' => $cancelled->id, 'closed_at' => now()->subHours(2)->startOfSecond()]);
    $publisher = User::query()->whereKey($cancelled->actor_user_id)->sole();
    $expired = BusinessCampaign::factory()->create(['payload' => ['title' => 'Old raise'], 'live_at' => now()->subDays(40)->startOfSecond()]);
    BusinessCampaignClosure::factory()->create(['business_campaign_id' => $expired->id, 'phase' => 'expired', 'actor_user_id' => null, 'closed_at' => $expired->expires_at]);
    $bakery = BusinessProfile::factory()->create(['entity_party_id' => Party::factory(),
        'profile' => ['name' => 'Amahoro Bakery', 'company_code' => 'RDB-77', 'industry' => 'Food', 'district' => 'Huye', 'established_year' => 2019]]);
    $mandate = BusinessMandate::factory()->create(['business_id' => $bakery->id, 'actor_user_id' => $this->analyst->id, 'reason' => 'Reviewed the RDB extract.',
        'created_at' => now()->subDay()]);
    BusinessMandate::factory()->create(['business_id' => $bakery->id, 'version' => 2, 'actor_user_id' => $this->analyst->id, 'reason' => 'Mandate withdrawn.',
        'terms' => [...$mandate->terms, 'status' => 'revoked']]);

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['business' => $cancelled->business_id, 'chip' => 'healthy']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.id', $cancelled->business_id)->where('party.kind', 'business')->where('party.name', 'Rugali Freight')
            ->where('party.subtitle', 'Retail · Gasabo')->where('party.health', 'healthy')
            ->where('party.stats', [['key' => 'active_notes', 'value' => ['kind' => 'count', 'value' => 0]], ['key' => 'investors', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'raised', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '0']]],
                ['key' => 'rating', 'value' => ['kind' => 'rating', 'value' => ['band' => 'weak', 'score' => '2.4']]]])
            ->where('party.list', ['key' => 'active_notes', 'rows' => [['id' => $cancelled->id, 'title' => 'Trucks', 'tone' => 'red', 'detail' => ['kind' => 'note_status', 'value' => 'failed']]]])
            ->where('party.history.0.action', ['code' => 'campaign.cancel', 'label' => 'Note cancelled · Trucks', 'tone' => 'red'])
            ->where('party.history.0.actor', $publisher->name)
            ->where('party.history.1.action', ['code' => 'campaign.publish', 'label' => 'Note published · Trucks', 'tone' => 'green'])
            ->where('party.history.1.reason', null)
            ->where('party.kyc', ['state' => 'verified', 'due_on' => null])->where('party.licence', null)->where('party.freeze', null)
            ->where('party.restrictions', [])->where('party.actions', [])->where('party.links.close', ['url' => '/admin/businesses?chip=healthy', 'method' => 'get']));

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['business' => $expired->business_id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.history.0.action.label', 'Note closed unfunded at its deadline · Old raise')
            ->where('party.history.0.action.tone', 'amber')->where('party.history.0.actor', 'System')
            ->where('party.list.rows.0.detail.value', 'failed'));

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['business' => $bakery->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.subtitle', 'Food · RDB RDB-77 · Huye')->where('party.kyc.state', 'pending')
            ->where('party.list', ['key' => 'active_notes', 'rows' => []])
            ->where('party.history.0.action', ['code' => 'business.authority.revoke', 'label' => 'Authority revoked', 'tone' => 'red'])
            ->where('party.history.0.reason', 'Mandate withdrawn.')
            ->where('party.history.1.action', ['code' => 'business.authority.configure', 'label' => 'Authority recorded', 'tone' => 'blue'])
            ->where('party.history.1.actor', $this->analyst->name)->where('party.history.1.reason', 'Reviewed the RDB extract.'));

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['business' => strtolower((string) Str::ulid())]))->assertNotFound();
});

test('a funded Business counts its funded principal, its distinct Investors and its authority history', function (): void {
    // One Investor makes two purchases and another takes the rest of the raise, which funds the note.
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $principal = (string) DB::table('primary_campaign_fundings')->where('business_campaign_id', $campaign->id)->value('principal');
    $rating = $campaign->payload['public_evidence']['rating'] ?? null;

    $this->actingAs($this->analyst)->get(route('staff.businesses.index', ['business' => $campaign->business_id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('stats.2.value.value', 1)->where('stats.3.value.value.amount', $principal)
            ->where('directory.rows.0.id', $campaign->business_id)->where('directory.rows.0.investors', 2)
            ->where('directory.rows.0.raised', ['currency' => 'RWF', 'amount' => $principal])->where('directory.rows.0.active_notes', 1)
            ->where('directory.rows.0.rating', $rating === null ? null : ['band' => $rating['band'], 'score' => $rating['score']])
            ->where('party.list.rows.0', ['id' => $campaign->id, 'title' => $campaign->payload['title'], 'tone' => 'blue', 'detail' => ['kind' => 'note_status', 'value' => 'funded']])
            ->where('party.history', fn (Collection $history): bool => $history->pluck('action.code')->contains('business.authority.configure')
                && $history->firstWhere('action.code', 'business.authority.configure')['reason'] === 'Reviewed complete authority evidence.'));
});

test('the directory answers over the API with the read ability and links API routes', function (): void {
    $seed = businessDirectoryNote('Isoko Farms', 'Agriculture', ['title' => 'Seed stock']);

    Sanctum::actingAs($this->analyst, ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/businesses?business='.$seed->business_id)->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-business-directory-v1')
        ->assertJsonPath('data.nav.businesses.url', '/api/v1/staff/businesses')
        ->assertJsonPath('data.directory.rows.0.link.url', '/api/v1/staff/businesses?business='.$seed->business_id)
        ->assertJsonPath('data.party.links.close.url', '/api/v1/staff/businesses');
    Sanctum::actingAs($this->analyst, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/businesses')->assertForbidden();
});
