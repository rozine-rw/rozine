<?php

declare(strict_types=1);

use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->fixture = BusinessAuthorityFixture::make();
    $this->business = BusinessAuthorityFixture::configure($this->fixture)['data']['business']['id'];
    $this->officer = $this->fixture['staff'];
});

/**
 * @param  array<string, int|string>  $overrides
 * @return array<string, int|string>
 */
function premisesVerification(int $revision = 0, array $overrides = []): array
{
    return ['request_id' => (string) Str::uuid(), 'expected_revision' => $revision, 'latitude' => '-2.6000', 'longitude' => '29.7400',
        'uncertainty_m' => 30, 'reference' => 'Site visit', 'reason' => 'Premises confirmed on a site visit.', ...$overrides];
}

it('shows a Business with no verified premises and records the point the 30 km dispatch measures to', function (): void {
    $id = $this->business;
    $url = '/admin/businesses/'.$id.'/premises';
    $page = route('staff.businesses.index', ['business' => $id]);

    $this->actingAs($this->officer)->get($page)->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.premises', ['state' => 'missing', 'latitude' => null, 'longitude' => null,
            'uncertainty_m' => null, 'verified_at' => null, 'reference' => null, 'revision' => 0, 'verify' => ['url' => $url, 'method' => 'post']]));

    $this->from($page)->post($url, premisesVerification(overrides: ['longitude' => '181']))
        ->assertSessionHasErrors(['longitude' => 'Enter the longitude in decimal degrees, for example 30.0619.']);
    $this->assertDatabaseCount('audit_locations', 0);

    $this->post($url, premisesVerification())->assertRedirect($page);
    expect(AuditLocation::query()->sole()->state)->toMatchArray(['point' => ['latitude' => '-2.6000', 'longitude' => '29.7400'],
        'uncertainty_m' => 30, 'evidence_reference' => 'Site visit'])
        ->and(AuditLocation::query()->sole()->business_id)->toBe($id)
        ->and(AuditLocationVersion::query()->sole()->actor_user_id)->toBe($this->officer->id);
    $this->get($page)->assertInertia(fn (Assert $page): Assert => $page->where('party.premises.state', 'verified')
        ->where('party.premises.latitude', '-2.6000')->where('party.premises.revision', 1));

    $this->from($page)->post($url, premisesVerification())
        ->assertSessionHasErrors(['reason' => "This business's premises record changed since you opened it. Review it again."]);
});

it('refuses a Business without a current mandate in the words of the form', function (): void {
    $this->fixture['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($this->fixture, 1);
    $url = '/admin/businesses/'.$this->business.'/premises';

    $this->actingAs($this->officer)->post($url, premisesVerification())
        ->assertSessionHasErrors(['reason' => "This business has no current mandate, so its premises can't be verified yet."]);
    $this->post('/admin/businesses/'.strtolower((string) Str::ulid()).'/premises', premisesVerification())->assertNotFound();
    $this->assertDatabaseCount('audit_locations', 0);
});

it('leaves the premises out for staff who do not verify Businesses and read-only over the API', function (): void {
    $id = $this->business;
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Reporting.');

    $this->actingAs($analyst)->get(route('staff.businesses.index', ['business' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.premises', null));
    $this->post('/admin/businesses/'.$id.'/premises', premisesVerification())->assertForbidden();

    Sanctum::actingAs($this->officer, ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/businesses?business='.$id)->assertOk()
        ->assertJsonPath('data.party.premises.state', 'missing')->assertJsonPath('data.party.premises.verify', null);
    $this->assertDatabaseCount('audit_locations', 0);
});
