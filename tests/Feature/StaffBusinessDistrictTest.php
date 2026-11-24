<?php

declare(strict_types=1);

use App\Application\Auditor\SetAuditorAvailability;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->withoutVite();
});

it('no longer asks staff to verify a Business\'s premises before it can be audited', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    $id = BusinessAuthorityFixture::configure($fixture)['data']['business']['id'];

    $this->actingAs($fixture['staff'])->get(route('staff.businesses.index', ['business' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->missing('party.premises'));
    $this->post('/admin/businesses/'.$id.'/premises', ['request_id' => (string) Str::uuid()])->assertNotFound();
    $this->assertDatabaseCount('audit_locations', 0);

    Sanctum::actingAs($fixture['staff'], ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/businesses?business='.$id)->assertOk()->assertJsonMissingPath('data.party.premises');
});

it('offers a Flash audit to a partner who accepts the Business\'s district, with no coordinates captured', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);

    expect($assignment->status)->toBe('offered')->and($assignment->party_id)->toBe($fixture['partners'][0]['party']->id);
    $this->assertDatabaseCount('audit_locations', 0);
});

it('sends the audit to Audit Operations when no partner accepts the Business\'s district', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    app(SetAuditorAvailability::class)->handle($partner['user']->id, 1, 3, true, (string) Str::uuid(), ['Musanze', 'Rubavu'], 4);
    $assignment = AuditAssignmentFixture::request($fixture);

    expect($assignment->status)->toBe('operations')->and($assignment->party_id)->toBeNull();
    $this->actingAs($fixture['staff'])->get(route('staff.businesses.index', ['business' => $fixture['business']]))
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.audit.engagement.operations_reason', 'AUDIT_NO_PARTNER_IN_DISTRICT'));
});
