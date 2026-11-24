<?php

declare(strict_types=1);

use App\Application\Auditor\SetAuditorAddress;
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

it('shows staff the districts a partner accepts, their weekly limit and registered address instead of an office to verify', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $id = $partner['party']->id;
    app(SetAuditorAddress::class)->handle($partner['user']->id, 1, 3, ['province' => 'Kigali City', 'district' => 'Gasabo',
        'sector' => 'Kacyiru', 'cell' => 'Kamatamu', 'street' => 'KG 7 Ave'], (string) Str::uuid());

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->missing('party.office')
            ->where('party.coverage', ['accepting' => true, 'districts' => ['Gasabo', 'Kicukiro'], 'max_jobs_per_week' => 10,
                'address' => ['province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Kacyiru', 'cell' => 'Kamatamu', 'street' => 'KG 7 Ave'],
                'address_line' => 'KG 7 Ave, Kamatamu, Kacyiru, Gasabo']));
    $this->post('/admin/auditors/'.$id.'/office', ['request_id' => (string) Str::uuid()])->assertNotFound();
    $this->assertDatabaseCount('audit_locations', 0);
});

it('shows a partner who has not chosen districts yet as covering none over the API', function (): void {
    $partner = AuditorFixture::make();
    $id = $partner['party']->id;
    Sanctum::actingAs($this->officer, ['staff:auditors:read']);
    $this->getJson('/api/v1/staff/auditors?auditor='.$id)->assertOk()->assertJsonPath('data.party.coverage', null);
    AuditorFixture::submit($partner['user']);

    Sanctum::actingAs($this->officer, ['staff:auditors:read']);
    $this->getJson('/api/v1/staff/auditors?auditor='.$id)->assertOk()
        ->assertJsonPath('data.party.coverage.accepting', false)->assertJsonPath('data.party.coverage.max_jobs_per_week', 4)
        ->assertJsonPath('data.party.coverage.districts', [])->assertJsonPath('data.party.coverage.address_line', null);
});

it('tells a partner on Jobs which districts they accept and their weekly limit, not an office', function (): void {
    $fixture = AuditAssignmentFixture::make(1);

    $this->actingAs($fixture['partners'][0]['user'])->get('/auditor/jobs')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->missing('office')->missing('radius_km')
            ->where('coverage', ['accepting' => true, 'districts' => ['Gasabo', 'Kicukiro'], 'max_jobs_per_week' => 10]));
});
