<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Models\AuditEngagementRelease;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeTime();
});

it('projects only current engagement status and the matching transport link on entry pages', function (bool $api): void {
    $actor = AuditorFixture::make();
    if ($api) {
        Sanctum::actingAs($actor['user'], ['auditor:read']);
    } else {
        $this->actingAs($actor['user']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $pages = $api ? ['profile' => 'auditor/profile', 'jobs.index' => 'auditor/jobs']
        : ['home' => 'identity/role-home', 'profile' => 'auditor/profile', 'jobs.index' => 'auditor/jobs'];
    $read = function (string $status) use ($api, $prefix, $pages): void {
        $summary = ['status' => $status, 'link' => ['url' => route($prefix.'engagement.show', [], false), 'method' => 'get']];
        foreach ($pages as $route => $component) {
            $response = $api ? $this->getJson(route($prefix.$route)) : $this->get(route($prefix.$route));
            $response->assertOk()->assertHeaderContains('Cache-Control', 'private');
            if ($api) {
                $response->assertJsonPath('data.engagement', $summary);
            } else {
                $response->assertInertia(fn (Assert $page): Assert => $page->component($component)->where('engagement', $summary));
            }
        }
    };

    $read('unavailable');
    $release = AuditEngagementFixture::release($actor['staff']);
    $read('required');
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
    AuditEngagementFixture::accept($actor['user'], $release);
    $read('current');
    AuditEngagementFixture::release($actor['staff'], 1);
    $read('required');
    AuditEngagementFixture::release($actor['staff'], 2, 'withdrawn');
    $read('unavailable');
    $this->assertDatabaseCount('audit_engagement_acceptances', 1);
})->with([false, true]);

it('keeps existing offers readable without terms while refusing acceptance and allowing decline or conflict', function (bool $api, string $decision, string $catalog): void {
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    if ($catalog === 'procedure_mismatch') {
        AuditEngagementRelease::factory()->create(['revision' => 2, 'procedure_version' => 'MVP-AUP-0']);
    } else {
        AuditEngagementFixture::release($fixture['staff'], 1, $catalog);
    }
    if ($api) {
        Sanctum::actingAs($partner['user'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($partner['user']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $summary = ['status' => $catalog === 'active' ? 'required' : 'unavailable',
        'link' => ['url' => route($prefix.'engagement.show', [], false), 'method' => 'get']];
    $actions = ['conflict.declare', 'assignment.decline'];
    $fileUrl = route($prefix.'jobs.show', ['assignment' => $assignment->id]);
    if ($api) {
        $this->getJson($fileUrl)->assertOk()->assertJsonPath('data.engagement', $summary)
            ->assertJsonPath('data.jobs.engagement', $summary)->assertJsonPath('data.allowed_actions', $actions)
            ->assertJsonPath('data.jobs.eligible.0.id', $assignment->id);
    } else {
        $this->get($fileUrl)->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('auditor/file')
            ->where('engagement', $summary)->where('jobs.engagement', $summary)->where('allowed_actions', $actions)
            ->where('jobs.eligible.0.id', $assignment->id));
    }
    $payload = ['identity_context_revision' => 1, 'expected_revision' => $assignment->revision];
    $this->postJson(route($prefix.'jobs.accept', ['assignment' => $assignment->id]), [...$payload, 'request_id' => (string) Str::uuid()])
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    expect($assignment->refresh()->revision)->toBe($payload['expected_revision']);
    $this->postJson(route($prefix.'jobs.'.$decision, ['assignment' => $assignment->id]), [...$payload,
        'kind' => 'family_or_business', 'reason' => 'A personal tie prevents this engagement.', 'reason_code' => 'other', 'request_id' => (string) Str::uuid()])
        ->assertOk()->assertJsonPath('code', $decision === 'conflict' ? 'CONFLICT_RECORDED' : 'ASSIGNMENT_DECLINED');
})->with([false, true])->with(['decline', 'conflict'])->with(['active', 'withdrawn', 'procedure_mismatch']);

it('reports required for another Party and current for another login of the accepting Party', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    $other = AuditorFixture::make();
    $this->actingAs($other['user'])->get(route('auditor.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('engagement.status', 'required'));
    $login = User::factory()->withTwoFactor()->for($actor['party'])->create();
    app(SelectActiveRole::class)->handle($login->id, 'auditor', 0, (string) Str::uuid());
    $this->actingAs($login)->get(route('auditor.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('engagement.status', 'current'));
});

it('does not project Auditor engagement status onto other role homes', function (string $role): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    $party = Party::factory()->verified()->create();
    $user = User::factory()->withTwoFactor()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => $role]);
    app(SelectActiveRole::class)->handle($user->id, $role, 0, (string) Str::uuid());
    $this->actingAs($user)->get(route($role.'.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('engagement', null));
    $this->getJson(route('auditor.home'))->assertForbidden()->assertJsonMissingPath('engagement');
})->with(['business', 'investor']);

it('requires current identity and token authority for entry summaries', function (string $route): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    Sanctum::actingAs($actor['user'], ['auditor:command']);
    $this->getJson(route($route))->assertForbidden()->assertJsonMissingPath('data.engagement');
    Sanctum::actingAs($actor['user'], ['auditor:read']);
    RoleMembership::query()->where('party_id', $actor['party']->id)->update(['status' => 'revoked']);
    $this->getJson(route($route))->assertForbidden()->assertJsonMissingPath('data.engagement');
})->with(['api.v1.auditor.profile', 'api.v1.auditor.jobs.index']);

it('does not advertise synthetic acceptance outside an isolated environment', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    $this->app->instance('env', 'production');
    Sanctum::actingAs($actor['user'], ['auditor:read']);
    $this->getJson(route('api.v1.auditor.profile'))->assertOk()->assertJsonPath('data.engagement.status', 'unavailable');
});
