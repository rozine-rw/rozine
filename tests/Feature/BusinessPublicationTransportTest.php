<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Models\BusinessCampaign;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('serves the same authorized release and publication receipts on web and API', function (bool $api): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $prefix = $api ? 'api.v1.' : '';
    $parameters = ['business' => $fixture['audit']['business'], 'application' => $fixture['application']->id];
    if ($api) {
        Sanctum::actingAs($fixture['audit']['staff'], ['staff:applications:read', 'staff:applications:review']);
    } else {
        $this->actingAs($fixture['audit']['staff']);
    }
    $this->getJson(route($prefix.'staff.applications.show', $parameters))
        ->assertOk()->assertJsonPath('data.release.allowed_actions', ['application.release'])->assertJsonPath('data.release.revision', 0);
    $released = $this->postJson(route($prefix.'staff.applications.release', $parameters), ['application_id' => $fixture['application']->id,
        'request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Current review completed.'])
        ->assertOk()->assertJsonPath('code', 'APPLICATION_RELEASED')->assertJsonPath('data.current.release.state', 'released');
    $this->getJson($released->json('data.receipt.link.url'))->assertOk()->assertJsonPath('data.receipt', $released->json('data.receipt'));
    if ($api) {
        Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    } else {
        $this->actingAs($fixture['audit']['authority']['users'][0]);
    }
    $url = route($prefix.'business.applications.publish.show', $parameters);
    if ($api) {
        $this->getJson($url)->assertOk()->assertJsonPath('data.allowed_actions', ['application.publish']);
    } else {
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component('business/publish')->where('allowed_actions', ['application.publish'])->where('home', null));
    }
    $payload = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'application_id' => $fixture['application']->id,
        'expected_application_revision' => $fixture['application']->refresh()->revision, 'fee_disclosure_version' => 'listing-fee-waiver-1'];
    $result = $this->postJson(route($prefix.'business.applications.publish', $parameters), $payload)
        ->assertOk()->assertJsonPath('code', 'LISTING_PUBLISHED')->assertJsonPath('data.receipt.amount.amount', '0')->assertJsonPath('data.current.allowed_actions', []);
    expect($result->json('data.receipt.operation_id'))->toBe($result->json('operation_id'));
    $this->travel(2)->seconds();
    $this->getJson($result->json('data.receipt.link.url'))->assertOk()->assertJsonPath('data.receipt', $result->json('data.receipt'))
        ->assertJsonPath('recorded_at', $result->json('recorded_at'));
    $this->getJson($result->json('data.next.url'))->assertOk()->assertJsonPath('data.campaign.lifecycle', 'live')
        ->assertJsonPath('data.note.progress.committed.amount', '0')->assertJsonPath('data.note.progress.remaining.amount', '10800000')
        ->assertJsonMissingPath('data.actor_user_id')->assertJsonMissingPath('data.binding');
    $this->postJson(route($prefix.'business.applications.publish', $parameters), $payload)->assertOk()->assertJsonPath('data.receipt', $result->json('data.receipt'));
    expect(BusinessCampaign::query()->count())->toBe(1);
})->with([false, true]);

it('denies publication and release to read-only API tokens', function (): void {
    $fixture = AuditSealingFixture::ready();
    $parameters = ['business' => $fixture['audit']['business'], 'application' => $fixture['application']->id];
    Sanctum::actingAs($fixture['audit']['staff'], ['staff:applications:read']);
    $this->postJson(route('api.v1.staff.applications.release', $parameters), [])->assertForbidden();
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read']);
    $this->postJson(route('api.v1.business.applications.publish', $parameters), [])->assertForbidden();
});

it('rejects mismatched route and body application identifiers before commands run', function (): void {
    $fixture = AuditSealingFixture::ready();
    $parameters = ['business' => $fixture['audit']['business'], 'application' => $fixture['application']->id];
    $this->actingAs($fixture['audit']['staff'])->postJson(route('staff.applications.release', $parameters), [
        'application_id' => (string) Str::ulid(), 'request_id' => (string) Str::uuid(), 'expected_revision' => 0, 'reason' => 'Reviewed.',
    ])->assertUnprocessable()->assertJsonValidationErrors('application_id');
    expect(app(BusinessCampaignStore::class)->staffPage($fixture['audit']['staff']->id, $fixture['application']->id)['release'])->toBeNull();
});
