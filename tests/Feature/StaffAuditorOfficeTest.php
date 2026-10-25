<?php

declare(strict_types=1);

use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->officer = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($this->officer, ['compliance'], 'Audit Partner network assignment.');
});

/** @return array<string, int|string> */
function officeVerification(int $revision = 0, array $overrides = []): array
{
    return ['request_id' => (string) Str::uuid(), 'expected_revision' => $revision, 'latitude' => '-1.9441', 'longitude' => '30.0619',
        'uncertainty_m' => 50, 'reference' => 'ICPAR register entry', 'reason' => 'Office address confirmed by phone.', ...$overrides];
}

it('shows a partner with no verified office and records one the 30 km dispatch can measure from', function (): void {
    $partner = AuditorFixture::make();
    $id = $partner['party']->id;
    $url = '/admin/auditors/'.$id.'/office';

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.office', ['state' => 'missing', 'latitude' => null, 'longitude' => null,
            'uncertainty_m' => null, 'verified_at' => null, 'reference' => null, 'revision' => 0, 'verify' => ['url' => $url, 'method' => 'post']]));
    $this->actingAs($partner['user'])->get('/auditor/jobs')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('auditor/jobs')->where('office', 'missing'));

    $this->actingAs($this->officer)->from(route('staff.auditors.index', ['auditor' => $id]))
        ->post($url, officeVerification(overrides: ['latitude' => '91', 'uncertainty_m' => 40000]))
        ->assertSessionHasErrors(['latitude' => 'Enter the latitude in decimal degrees, for example -1.9441.',
            'uncertainty_m' => 'Enter how many metres the point may be off, from 0 to 30,000.']);
    $this->assertDatabaseCount('audit_locations', 0);

    $this->post($url, officeVerification())->assertRedirect(route('staff.auditors.index', ['auditor' => $id]));
    expect(AuditLocation::query()->sole()->state)->toMatchArray(['point' => ['latitude' => '-1.9441', 'longitude' => '30.0619'],
        'uncertainty_m' => 50, 'evidence_reference' => 'ICPAR register entry'])
        ->and(AuditLocationVersion::query()->sole()->actor_user_id)->toBe($this->officer->id);
    $this->get(route('staff.auditors.index', ['auditor' => $id]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.office.state', 'verified')->where('party.office.latitude', '-1.9441')
            ->where('party.office.uncertainty_m', 50)->where('party.office.revision', 1)
            ->where('party.office.verified_at', now('UTC')->format('Y-m-d\TH:i:s\Z')));
    $this->actingAs($partner['user'])->get('/auditor/jobs')->assertInertia(fn (Assert $page): Assert => $page->where('office', 'verified'));

    // A form opened before that verification answers the revision it saw.
    $this->actingAs($this->officer)->post($url, officeVerification())
        ->assertSessionHasErrors(['reason' => "This partner's office record changed since you opened it. Review it again."]);
    $this->post($url, officeVerification(1, ['reference' => "Register\u{0007}entry"]))
        ->assertSessionHasErrors(['reason' => 'Say what evidence you checked and why, in plain text.']);

    $this->travel(366)->days();
    $this->get(route('staff.auditors.index', ['auditor' => $id]))->assertInertia(fn (Assert $page): Assert => $page->where('party.office.state', 'expired'));
    $this->actingAs($partner['user'])->get('/auditor/jobs')->assertInertia(fn (Assert $page): Assert => $page->where('office', 'expired'));
});

it('leaves the office read-only over the API and to staff who do not verify partners', function (): void {
    $partner = AuditorFixture::make();
    $id = $partner['party']->id;

    Sanctum::actingAs($this->officer, ['staff:auditors:read']);
    $this->getJson('/api/v1/staff/auditors?auditor='.$id)->assertOk()
        ->assertJsonPath('data.party.office.state', 'missing')->assertJsonPath('data.party.office.verify', null);

    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Reporting.');
    $this->actingAs($analyst)->post('/admin/auditors/'.$id.'/office', officeVerification())->assertForbidden();
    $this->assertDatabaseCount('audit_locations', 0);
});

it('tells a partner whose office dispatch already measures from nothing more on Jobs', function (): void {
    $fixture = AuditAssignmentFixture::make(1);

    $this->actingAs($fixture['partners'][0]['user'])->get('/auditor/jobs')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('office', 'verified'));
});
