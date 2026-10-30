<?php

declare(strict_types=1);

use App\Application\Auditor\ResolveAuditAssignment;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
});

/** @return array{request_id: string, reason: string} */
function flashAuditRequest(string $reason = 'Unusual movement on the revenue account.'): array
{
    return ['request_id' => (string) Str::uuid(), 'reason' => $reason];
}

it('requests a Flash audit from a Business with none open and then shows it offered', function (): void {
    $fixture = Fixture::make(1);
    $id = $fixture['business'];
    $page = route('staff.businesses.index', ['business' => $id]);
    $url = '/admin/businesses/'.$id.'/flash-audit';

    $this->actingAs($fixture['staff'])->get($page)->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.audit', ['engagement' => null,
            'request_flash' => ['url' => $url, 'method' => 'post'], 'redispatch' => null]));

    $this->from($page)->post($url, flashAuditRequest(''))->assertSessionHasErrors(['reason' => 'Say why, in plain text.']);
    $this->assertDatabaseCount('audit_assignments', 0);

    $body = flashAuditRequest();
    $this->post($url, $body)->assertRedirect($page);
    $assignment = AuditAssignment::query()->sole();
    $version = AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->sole();
    expect($assignment->business_id)->toBe($id)->and($assignment->state['kind'])->toBe('flash')->and($assignment->status)->toBe('offered')
        ->and($assignment->state['complete_by'])->toBe(now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z'))
        ->and($version->command)->toBe('audit.assignment.request')->and($version->actor_user_id)->toBe($fixture['staff']->id);

    $this->get($page)->assertInertia(fn (Assert $page): Assert => $page->where('party.audit', ['engagement' => ['id' => $assignment->id,
        'kind' => 'flash', 'status' => 'offered', 'revision' => 1, 'attempt' => null, 'operations_reason' => null],
        'request_flash' => null, 'redispatch' => null]));

    $this->from($page)->post($url, $body)->assertRedirect($page);
    $this->from($page)->post($url, [...$body, 'reason' => 'A different reason.'])
        ->assertSessionHasErrors(['reason' => 'That request was already used for different details. Try again.']);
    $this->from($page)->post($url, flashAuditRequest())->assertSessionHasErrors(['reason' => 'This business already has an audit in progress.']);
    expect(AuditAssignment::query()->count())->toBe(1);
});

it('redispatches an audit that waits on Audit Operations once a partner is eligible', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $id = $fixture['business'];
    $page = route('staff.businesses.index', ['business' => $id]);
    $url = '/admin/businesses/'.$id.'/audits/'.$assignment->id.'/redispatch';

    $this->actingAs($fixture['staff'])->get($page)->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.audit', ['engagement' => ['id' => $assignment->id,
            'kind' => 'flash', 'status' => 'operations', 'revision' => 1, 'attempt' => 0, 'operations_reason' => 'AUDIT_NO_ELIGIBLE_PARTNER'],
            'request_flash' => null, 'redispatch' => ['url' => $url, 'method' => 'post']]));

    $this->post($url, ['expected_revision' => 1, ...flashAuditRequest('No partner yet; try again.')])->assertRedirect($page);
    expect($assignment->refresh()->status)->toBe('operations')->and($assignment->revision)->toBe(2);
    $this->from($page)->post($url, ['expected_revision' => 1, ...flashAuditRequest('Stale.')])
        ->assertSessionHasErrors(['reason' => 'This audit changed since you opened it. Review it again.']);

    $partner = Fixture::make(1)['partners'][0];
    Fixture::independence($fixture['staff'], $id, $partner['party']->id);
    $this->post($url, ['expected_revision' => 2, ...flashAuditRequest('A partner near the business was approved.')])->assertRedirect($page);
    expect($assignment->refresh()->status)->toBe('offered')->and($assignment->party_id)->toBe($partner['party']->id);
    $this->from($page)->post($url, ['expected_revision' => 3, ...flashAuditRequest('Again.')])
        ->assertSessionHasErrors(['reason' => 'This audit is no longer with Audit Operations.']);
});

it('offers no redispatch once a Flash audit has passed its deadline, and no new request after a closure', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $id = $fixture['business'];
    $page = route('staff.businesses.index', ['business' => $id]);
    $this->travel(24)->hours();

    $this->actingAs($fixture['staff'])->get($page)
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.audit.engagement.operations_reason', 'AUDIT_DISPATCH_EXHAUSTED')
            ->where('party.audit.redispatch', null)->where('party.audit.request_flash', null));
    $this->from($page)->post('/admin/businesses/'.$id.'/audits/'.$assignment->id.'/redispatch', ['expected_revision' => 1, ...flashAuditRequest('Try.')])
        ->assertSessionHasErrors(['reason' => 'This audit has used every dispatch attempt or passed its deadline, so it cannot be redispatched.']);

    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No partner in time.', (string) Str::uuid());
    $this->get($page)->assertInertia(fn (Assert $page): Assert => $page->where('party.audit.engagement', null)
        ->where('party.audit.request_flash.url', '/admin/businesses/'.$id.'/flash-audit'));
    $this->from($page)->post('/admin/businesses/'.$id.'/flash-audit', flashAuditRequest())
        ->assertSessionHasErrors(['reason' => "Audit Operations closed this business's last audit, so a new one can't be requested here."]);
});

it('leaves the audit out for staff who do not manage audits and gives the API no audit actions', function (): void {
    $fixture = Fixture::make(0);
    $assignment = Fixture::request($fixture);
    $id = $fixture['business'];
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Reporting.');

    $this->actingAs($analyst)->get(route('staff.businesses.index', ['business' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('party.audit', null));
    $this->post('/admin/businesses/'.$id.'/flash-audit', flashAuditRequest())->assertForbidden();
    $this->post('/admin/businesses/'.$id.'/audits/'.$assignment->id.'/redispatch', ['expected_revision' => 1, ...flashAuditRequest()])->assertForbidden();
    expect($assignment->refresh()->revision)->toBe(1);

    Sanctum::actingAs($fixture['staff'], ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/businesses?business='.$id)->assertOk()
        ->assertJsonPath('data.party.audit.engagement.status', 'operations')
        ->assertJsonPath('data.party.audit.request_flash', null)->assertJsonPath('data.party.audit.redispatch', null);
});
