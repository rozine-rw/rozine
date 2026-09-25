<?php

declare(strict_types=1);

use App\Models\AuditEngagementAcceptance;
use App\Models\RoleMembership;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;

it('serves exact private terms and records explicit acceptance through both transports', function (bool $api): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $prefix = $api ? 'api.v1.auditor.engagement.' : 'auditor.engagement.';
    if ($api) {
        Sanctum::actingAs($actor['user'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($actor['user']);
    }
    $page = $this->getJson(route($prefix.'show'))->assertOk()->assertHeaderContains('Cache-Control', 'private')
        ->assertHeaderContains('Cache-Control', 'no-store')->assertJsonPath('data.contract_version', 'auditor-engagement-v1')
        ->assertJsonPath('data.identity_context_revision', 1)->assertJsonPath('data.release.sha256', $release->sha256)
        ->assertJsonPath('data.release.documents.master_services.body', AuditEngagementFixture::documents()['master_services']['body'])
        ->assertJsonPath('data.acceptance', null)->assertJsonPath('data.allowed_actions', ['audit.engagement.accept'])
        ->assertJsonMissingPath('data.release.actor_user_id')->assertJsonMissingPath('data.release.approval_reference');
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
    $request = (string) Str::uuid();
    $payload = ['identity_context_revision' => 1, 'expected_revision' => 1, 'release_id' => $release->id,
        'sha256' => $release->sha256, 'accepted' => true, 'request_id' => $request];
    $receipt = $this->postJson($page->json('data.actions.accept.url'), $payload)->assertOk()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTED');
    $this->postJson(route($prefix.'accept'), $payload)->assertOk()->assertJsonPath('operation_id', $receipt->json('operation_id'));
    $this->getJson(str_replace('{request_id}', $request, $page->json('data.links.operation.url')))->assertOk()
        ->assertJsonPath('data.acceptance', $receipt->json('data.acceptance'))->assertJsonMissingPath('data.documents');
    $this->getJson(route($prefix.'show'))->assertOk()->assertJsonPath('data.actions.accept', null)->assertJsonPath('data.allowed_actions', [])
        ->assertJsonPath('data.acceptance.id', $receipt->json('data.acceptance.id'));
    $this->assertDatabaseCount('audit_engagement_acceptances', 1);
})->with([false, true]);

it('exposes no acceptance action while terms are unavailable or withdrawn', function (): void {
    $actor = AuditorFixture::make();
    $this->actingAs($actor['user'])->getJson(route('auditor.engagement.show'))->assertOk()
        ->assertJsonStructure(['data' => ['release', 'acceptance', 'allowed_actions', 'actions']])->assertJsonPath('data.release', null)->assertJsonPath('data.actions.accept', null);
    AuditEngagementFixture::release($actor['staff']);
    AuditEngagementFixture::release($actor['staff'], 1, 'withdrawn');
    $this->getJson(route('auditor.engagement.show'))->assertOk()->assertJsonStructure(['data' => ['release', 'acceptance', 'allowed_actions', 'actions']])->assertJsonPath('data.release', null)->assertJsonPath('data.acceptance', null);
});

it('rejects malformed acceptance envelopes and never treats a string as explicit consent', function (string $field, mixed $value): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $payload = ['identity_context_revision' => 1, 'expected_revision' => 1, 'release_id' => $release->id,
        'sha256' => $release->sha256, 'accepted' => true, 'request_id' => (string) Str::uuid(), $field => $value];
    $this->actingAs($actor['user'])->postJson(route('auditor.engagement.accept'), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
})->with([['accepted', 'true'], ['accepted', 1], ['accepted', null], ['sha256', 'bad'], ['release_id', 'other'], ['request_id', 'bad'], ['expected_revision', -1], ['identity_context_revision', -1]]);

it('returns recorded unchecked and stale-version outcomes through the operation resource', function (bool $accepted): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $request = (string) Str::uuid();
    $response = $this->actingAs($actor['user'])->postJson(route('auditor.engagement.accept'), [
        'identity_context_revision' => 1, 'expected_revision' => $accepted ? 0 : 1, 'release_id' => $release->id,
        'sha256' => $release->sha256, 'accepted' => $accepted, 'request_id' => $request]);
    $code = $accepted ? 'AUDIT_ENGAGEMENT_VERSION_CONFLICT' : 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED';
    $response->assertStatus($accepted ? 409 : 422)->assertJsonPath('code', $code)->assertJsonPath('status', 'rejected');
    $this->getJson(route('auditor.engagement.operations.show', ['request_id' => $request]))
        ->assertStatus($accepted ? 409 : 422)->assertJsonPath('code', $code);
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
})->with([false, true]);

it('requires the matching Sanctum capability and current Auditor membership', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    Sanctum::actingAs($actor['user'], ['auditor:read']);
    $this->postJson(route('api.v1.auditor.engagement.accept'), ['identity_context_revision' => 1, 'expected_revision' => 1,
        'release_id' => $release->id, 'sha256' => $release->sha256, 'accepted' => true, 'request_id' => (string) Str::uuid()])->assertForbidden();
    Sanctum::actingAs($actor['user'], ['auditor:command']);
    $this->getJson(route('api.v1.auditor.engagement.show'))->assertForbidden();
    $this->getJson(route('api.v1.auditor.engagement.operations.show', ['request_id' => (string) Str::uuid()]))->assertForbidden();
    Sanctum::actingAs($actor['user'], ['auditor:read', 'auditor:command']);
    RoleMembership::query()->where('party_id', $actor['party']->id)->update(['status' => 'revoked']);
    $this->getJson(route('api.v1.auditor.engagement.show'))->assertForbidden();
    expect(AuditEngagementAcceptance::query()->count())->toBe(0);
});

it('rejects unauthenticated agreement reads writes and receipt lookup', function (): void {
    $this->getJson(route('auditor.engagement.show'))->assertUnauthorized();
    $this->postJson(route('auditor.engagement.accept'), [])->assertUnauthorized();
    $this->getJson(route('api.v1.auditor.engagement.operations.show', ['request_id' => (string) Str::uuid()]))->assertUnauthorized();
});
