<?php

declare(strict_types=1);

use App\Models\AuditEngagementAcceptance;
use App\Models\RoleMembership;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;
use Tests\TestCase;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * The agreement page as each transport serves it: the web page's flat Inertia props for the
 * `auditor/engagement` component, or the API's JSON `data` envelope. Both are private and unstored.
 *
 * @return array<string, mixed>
 */
function engagementHttpPage(TestCase $test, bool $api): array
{
    if ($api) {
        return $test->getJson(route('api.v1.auditor.engagement.show'))->assertOk()->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'no-store')->json('data');
    }
    $props = [];
    $test->get(route('auditor.engagement.show'))->assertOk()->assertHeaderContains('Cache-Control', 'private')
        ->assertHeaderContains('Cache-Control', 'no-store')->assertInertia(function (Assert $page) use (&$props): Assert {
            $props = $page->toArray()['props'];

            return $page->component('auditor/engagement');
        });

    return $props;
}

it('serves exact private terms and records explicit acceptance through both transports', function (bool $api): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $prefix = $api ? 'api.v1.auditor.engagement.' : 'auditor.engagement.';
    if ($api) {
        Sanctum::actingAs($actor['user'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($actor['user']);
    }
    $page = engagementHttpPage($this, $api);
    expect($page['contract_version'])->toBe('auditor-engagement-v1')
        ->and($page['identity_context_revision'])->toBe(1)
        ->and($page['release']['sha256'])->toBe($release->sha256)
        ->and($page['release']['documents']['master_services']['body'])->toBe(AuditEngagementFixture::documents()['master_services']['body'])
        ->and($page['acceptance'])->toBeNull()
        ->and($page['allowed_actions'])->toBe(['audit.engagement.accept'])
        ->and($page['actions']['accept'])->toBe(['url' => route($prefix.'accept', [], false), 'method' => 'post'])
        ->and($page['links']['current'])->toBe(['url' => route($prefix.'show', [], false), 'method' => 'get'])
        ->and($page['release'])->not->toHaveKeys(['actor_user_id', 'approval_reference']);
    if ($api) {
        expect($page)->not->toHaveKey('open_jobs')
            ->and(array_keys($page['links']))->toBe(['current', 'operation']);
    } else {
        expect($page['open_jobs'])->toBe(0)
            ->and($page['links'])->toMatchArray(['home' => ['url' => '/auditor', 'method' => 'get'],
                'profile' => ['url' => '/auditor/profile', 'method' => 'get'], 'launcher' => ['url' => '/dashboard', 'method' => 'get'],
                'operation' => ['url' => '/auditor/engagement/operations/{request_id}', 'method' => 'get']]);
    }
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
    $request = (string) Str::uuid();
    $payload = ['identity_context_revision' => 1, 'expected_revision' => 1, 'release_id' => $release->id,
        'sha256' => $release->sha256, 'accepted' => true, 'request_id' => $request];
    $receipt = $this->postJson($page['actions']['accept']['url'], $payload)->assertOk()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTED');
    $this->postJson(route($prefix.'accept'), $payload)->assertOk()->assertJsonPath('operation_id', $receipt->json('operation_id'));
    $this->getJson(str_replace('{request_id}', $request, $page['links']['operation']['url']))->assertOk()
        ->assertJsonPath('data.acceptance', $receipt->json('data.acceptance'))->assertJsonMissingPath('data.documents');
    $after = engagementHttpPage($this, $api);
    expect($after['actions']['accept'])->toBeNull()
        ->and($after['allowed_actions'])->toBe([])
        ->and($after['acceptance']['id'])->toBe($receipt->json('data.acceptance.id'));
    $this->assertDatabaseCount('audit_engagement_acceptances', 1);
})->with([false, true]);

it('exposes no acceptance action while terms are unavailable or withdrawn', function (bool $api): void {
    $actor = AuditorFixture::make();
    if ($api) {
        Sanctum::actingAs($actor['user'], ['auditor:read']);
    } else {
        $this->actingAs($actor['user']);
    }
    $empty = engagementHttpPage($this, $api);
    expect($empty)->toHaveKeys(['release', 'acceptance', 'allowed_actions', 'actions'])
        ->and($empty['release'])->toBeNull()->and($empty['actions']['accept'])->toBeNull()->and($empty['allowed_actions'])->toBe([]);
    AuditEngagementFixture::release($actor['staff']);
    AuditEngagementFixture::release($actor['staff'], 1, 'withdrawn');
    $withdrawn = engagementHttpPage($this, $api);
    expect($withdrawn)->toHaveKeys(['release', 'acceptance', 'allowed_actions', 'actions'])
        ->and($withdrawn['release'])->toBeNull()->and($withdrawn['acceptance'])->toBeNull()->and($withdrawn['actions']['accept'])->toBeNull();
})->with([false, true]);

it('renders the web agreement page for an Inertia visit and keeps the API a JSON envelope', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::release($actor['staff']);
    $this->actingAs($actor['user']);
    $version = (string) $this->get(route('auditor.engagement.show'))->assertOk()->viewData('page')['version'];
    $this->get(route('auditor.engagement.show'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->assertOk()
        ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'auditor/engagement')
        ->assertJsonPath('props.contract_version', 'auditor-engagement-v1')->assertJsonMissingPath('props.data');
    Sanctum::actingAs($actor['user'], ['auditor:read']);
    $this->getJson(route('api.v1.auditor.engagement.show'))->assertOk()->assertHeaderMissing('X-Inertia')
        ->assertJsonPath('data.contract_version', 'auditor-engagement-v1')->assertJsonMissingPath('component');
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
