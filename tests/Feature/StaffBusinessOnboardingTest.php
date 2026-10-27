<?php

declare(strict_types=1);

use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->fixture = BusinessAuthorityFixture::make();
    $this->officer = $this->fixture['staff'];
    $this->representative = $this->fixture['users'][0];
});

/**
 * @param  array<string, bool|int|string|null>  $overrides
 * @return array<string, bool|int|string|null>
 */
function onboarding(array $overrides = []): array
{
    return ['request_id' => (string) Str::uuid(), 'mandate_request_id' => (string) Str::uuid(), 'kind' => 'organization', 'rdb_code' => '123456789',
        'name' => 'Kigali Fresh Ltd', 'industry' => 'Agribusiness', 'district' => 'Gasabo', 'established_year' => 2019,
        'representative_email' => 'aline@example.test', 'evidence_reference' => 'RDB certificate and ID checked',
        'reason' => 'Onboarding for the pilot.', 'attested' => true, 'effective_at' => now('UTC')->subMinute()->format('Y-m-d\TH:i:s\Z'), ...$overrides];
}

it('onboards a company from its RDB code with its representative holding every role and says they can open the Business app', function (): void {
    $this->freezeSecond();
    $this->representative->forceFill(['email' => 'Aline@Example.test'])->save();
    $input = onboarding();

    $this->actingAs($this->officer)->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('onboard', ['url' => '/admin/businesses/onboard', 'method' => 'post',
            'effective_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')])->where('onboarded', null));
    $response = $this->post('/admin/businesses/onboard', $input);

    $business = BusinessProfile::query()->sole();
    $party = $this->representative->party_id;
    $response->assertRedirect(route('staff.businesses.index', ['business' => $business->id]))
        ->assertSessionHas('business_onboarded', ['business_id' => $business->id, 'representative' => $this->representative->name, 'business_access' => true]);
    expect($business->entity_kind)->toBe('organization')
        ->and($business->entity_party_id)->toBe(VerifiedOrganizationIdentity::query()->sole()->party_id)
        ->and($business->profile)->toEqual(['name' => 'Kigali Fresh Ltd', 'company_code' => '123456789', 'industry' => 'Agribusiness',
            'district' => 'Gasabo', 'established_year' => 2019])
        ->and(BusinessMandate::query()->sole()->terms)->toMatchArray(['required_signatories' => [$party], 'status' => 'active',
            'effective_at' => $input['effective_at'], 'expires_at' => null])
        ->and(BusinessMandate::query()->sole()->terms['people'][0])->toMatchArray(['party_id' => $party, 'name' => $this->representative->name,
            'roles' => ['controller', 'owner', 'representative', 'signatory']])
        ->and(BusinessMandate::query()->sole()->actor_user_id)->toBe($this->officer->id);

    $this->get(route('staff.businesses.index', ['business' => $business->id]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('onboarded.business_access', true)->where('party.name', 'Kigali Fresh Ltd'));
    // A retry of the same form after a delay replays the first result rather than conflicting with it.
    $this->travel(5)->seconds();
    $this->post('/admin/businesses/onboard', $input)->assertRedirect(route('staff.businesses.index', ['business' => $business->id]))
        ->assertSessionHasNoErrors();
    expect(BusinessProfile::query()->count())->toBe(1)->and(BusinessMandate::query()->count())->toBe(1);

    $this->from(route('staff.businesses.index'))->post('/admin/businesses/onboard', onboarding())
        ->assertSessionHasErrors(['name' => 'This business is already on Rozine. Open it from the directory instead.']);
    $this->post('/admin/businesses/onboard', onboarding(['request_id' => $input['request_id'], 'rdb_code' => '987654321']))
        ->assertSessionHasErrors(['reason' => 'That request was already used for different details. Try again.']);
    expect(BusinessProfile::query()->count())->toBe(1);
});

it('onboards a sole trader and says their representative still needs Business access', function (): void {
    $party = Party::factory()->verified()->create();
    $trader = User::factory()->for($party)->create(['email' => 'trader@example.test']);

    $this->actingAs($this->officer)->post('/admin/businesses/onboard', onboarding(['kind' => 'person', 'rdb_code' => null,
        'established_year' => null, 'representative_email' => 'trader@example.test']))
        ->assertSessionHas('business_onboarded.business_access', false);

    $business = BusinessProfile::query()->sole();
    expect($business->entity_kind)->toBe('person')
        ->and($business->entity_party_id)->toBe($party->id)
        ->and($business->profile)->toMatchArray(['company_code' => null, 'established_year' => null])
        ->and(BusinessMandate::query()->sole()->terms['people'][0]['name'])->toBe($trader->name);
});

it('refuses a representative who is not a verified person, and fields the server would reject, in the words of the form', function (): void {
    User::factory()->for(Party::factory()->create(['kind' => 'person']))->create(['email' => 'unverified@example.test']);
    $refusal = 'No verified person on Rozine uses this email. They sign up and complete their identity check first.';

    $this->actingAs($this->officer)->post('/admin/businesses/onboard', onboarding(['representative_email' => 'nobody@example.test']))
        ->assertSessionHasErrors(['representative_email' => $refusal]);
    $this->post('/admin/businesses/onboard', onboarding(['representative_email' => 'unverified@example.test']))
        ->assertSessionHasErrors(['representative_email' => $refusal]);
    $this->post('/admin/businesses/onboard', onboarding(['rdb_code' => '12345']))
        ->assertSessionHasErrors(['rdb_code' => 'The RDB company code is 9 digits.']);
    $this->post('/admin/businesses/onboard', onboarding(['kind' => 'person']))
        ->assertSessionHasErrors(['rdb_code' => 'A sole trader has no RDB company code.']);
    $this->post('/admin/businesses/onboard', onboarding(['rdb_code' => null]))
        ->assertSessionHasErrors(['rdb_code' => 'Enter the RDB company code.']);
    $this->post('/admin/businesses/onboard', onboarding(['effective_at' => now('UTC')->subDays(2)->format('Y-m-d\TH:i:s\Z')]))
        ->assertSessionHasErrors(['effective_at' => 'This form is out of date. Close it, open it again and re-enter the business.']);
    $this->post('/admin/businesses/onboard', onboarding(['established_year' => now()->year + 1, 'attested' => false]))
        ->assertSessionHasErrors(['established_year' => 'The year cannot be in the future.',
            'attested' => 'Confirm that the people named are everyone with authority over this business.']);
    expect(BusinessProfile::query()->count())->toBe(0)->and(VerifiedOrganizationIdentity::query()->count())->toBe(0);
});

it('keeps onboarding to staff who verify Businesses and out of the API', function (): void {
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Reporting.');

    $this->actingAs($analyst)->get(route('staff.businesses.index'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('onboard', null));
    $this->post('/admin/businesses/onboard', onboarding())->assertForbidden();
    expect(BusinessProfile::query()->count())->toBe(0);

    Sanctum::actingAs($this->officer, ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/businesses')->assertOk()->assertJsonPath('data.onboard', null);
});
