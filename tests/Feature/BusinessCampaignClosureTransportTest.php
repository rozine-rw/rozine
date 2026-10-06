<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->fixture = AuditSealingFixture::ready(2, requiredSignatories: 1);
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->store = app(BusinessCampaignStore::class);
    $this->user = $this->fixture['audit']['authority']['users'][0];
    $this->business = $this->fixture['audit']['business'];
    $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $this->store->publish($this->user->id, 1, $this->business, $this->fixture['application']->id,
        $this->fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->campaign = BusinessCampaign::query()->sole();
    $this->parameters = ['business' => $this->business, 'campaign' => $this->campaign->id];
    $this->input = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id,
        'expected_campaign_revision' => 1, 'reason' => null];
});

it('serves cancellation and reconnect outcomes identically on web and scoped API', function (bool $api): void {
    $prefix = $api ? 'api.v1.' : '';
    if ($api) {
        Sanctum::actingAs($this->user, ['business:read', 'business:command']);
    } else {
        $this->actingAs($this->user);
    }
    $url = route($prefix.'business.campaigns.cancel', $this->parameters);
    $outcome = $this->postJson($url, $this->input)->assertOk()->assertJsonPath('code', 'CAMPAIGN_CANCELLED')
        ->assertJsonPath('revision', 2)->assertJsonPath('data.current.campaign.lifecycle', 'cancelled')
        ->assertJsonPath('data.current.note.progress.committed_refunded.amount', '0')
        ->assertJsonPath('data.current.note.progress.investors', 0)->assertJsonPath('data.current.actions.cancel', null)
        ->assertJsonPath('data.current.allowed_actions', [])->assertJsonMissingPath('data.current.note.progress.reason');
    expect($outcome->json('data.receipt.link.url'))->toContain('command=campaign.cancel');
    $this->travel(1)->minute();
    $this->postJson($url, $this->input)->assertOk()->assertJsonPath('data.receipt', $outcome->json('data.receipt'));
    $this->getJson($outcome->json('data.receipt.link.url'))->assertOk()->assertJsonPath('data.receipt', $outcome->json('data.receipt'))
        ->assertJsonPath('recorded_at', $outcome->json('recorded_at'))->assertJsonPath('data.current.campaign.revision', 2);
    $this->postJson($url, [...$this->input, 'reason' => 'changed'])->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    $this->postJson($url, [...$this->input, 'request_id' => (string) Str::uuid()])->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT')
        ->assertJsonPath('data.current.campaign.lifecycle', 'cancelled');
    if ($api) {
        $this->getJson(route($prefix.'business.campaigns.show', $this->parameters))->assertOk()->assertJsonPath('data.note.progress.phase', 'cancelled');
    } else {
        $this->get(route('business.campaigns.show', $this->parameters))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('business/campaign')
            ->where('campaign.lifecycle', 'cancelled')->where('note.progress.committed_refunded.amount', '0'));
    }
})->with([false, true]);

it('keeps cancellation controls out of read-only API output and refuses the command', function (): void {
    Sanctum::actingAs($this->user, ['business:read']);
    $this->getJson(route('api.v1.business.campaigns.show', $this->parameters))->assertOk()->assertJsonPath('data.actions.cancel', null)->assertJsonPath('data.allowed_actions', []);
    $this->postJson(route('api.v1.business.campaigns.cancel', $this->parameters), $this->input)->assertForbidden();
    Sanctum::actingAs($this->user, ['business:command']);
    $this->getJson(route('api.v1.business.campaigns.show', $this->parameters))->assertForbidden();
    $this->postJson(route('api.v1.business.campaigns.cancel', $this->parameters), $this->input)->assertForbidden();
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('requires a current required signatory even for replay', function (): void {
    $other = $this->fixture['audit']['authority']['users'][1];
    $this->actingAs($other)->get(route('business.campaigns.show', $this->parameters))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('actions.cancel', null));
    $this->postJson(route('business.campaigns.cancel', $this->parameters), $this->input)->assertForbidden();
    $this->actingAs($this->user)->postJson(route('business.campaigns.cancel', $this->parameters), $this->input)->assertOk();
    RoleMembership::query()->where('party_id', $this->user->party_id)->where('role', 'business')->update(['status' => 'revoked']);
    $this->postJson(route('business.campaigns.cancel', $this->parameters), $this->input)->assertForbidden();
    $this->getJson(route('business.applications.operations.show', ['request_id' => $this->input['request_id'], 'command' => 'campaign.cancel', 'identity_context_revision' => 1]))->assertForbidden();
});

it('enforces route binding validation identity revision and scoped campaign visibility', function (): void {
    $url = route('business.campaigns.cancel', $this->parameters);
    $this->postJson($url, $this->input)->assertUnauthorized();
    $this->actingAs($this->user)->postJson($url, [...$this->input, 'campaign_id' => (string) Str::ulid()])->assertUnprocessable()->assertJsonValidationErrors('campaign_id');
    $this->postJson($url, [...$this->input, 'reason' => str_repeat('x', 1001)])->assertUnprocessable()->assertJsonValidationErrors('reason');
    $this->postJson($url, [...$this->input, 'identity_context_revision' => 0])->assertConflict();
    $missing = (string) Str::ulid();
    $this->postJson(route('business.campaigns.cancel', [...$this->parameters, 'campaign' => $missing]), [...$this->input, 'campaign_id' => $missing])->assertNotFound();
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('returns a current projection alongside a retained validation refusal', function (): void {
    $this->actingAs($this->user)->postJson(route('business.campaigns.cancel', $this->parameters), [...$this->input, 'reason' => "Hidden\u{2028}reason"])
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED')->assertJsonPath('data.current.campaign.lifecycle', 'live');
});

it('rejects receipt lookups for nonexistent campaigns and unrelated aggregate types', function (string $target): void {
    $request = (string) Str::uuid();
    app(OperationJournal::class)->execute('party:'.$this->user->party_id, $this->user->id, 'campaign.cancel', $request, $target, (string) Str::ulid(), [],
        fn () => null, fn (): OperationResult => new OperationResult('RECORDED', [], 1));
    expect(fn () => $this->store->findCancellation($this->user->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $this->store->findCancellation(User::factory()->create()->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
})->with(['business', 'campaign']);

it('rechecks the receipt actor if identity relinking wins before authorization', function (): void {
    $request = $this->input['request_id'];
    $this->store->cancel($this->user->id, 1, $this->business, $this->campaign->id, 1, null, $request);
    $replacement = $this->fixture['audit']['authority']['users'][1]->refresh();
    $event = 'eloquent.retrieved: '.CommandOperation::class;
    Event::listen($event, function () use ($replacement): void {
        $this->user->forceFill(['party_id' => $replacement->party_id, 'context_revision' => 2,
            'active_membership_id' => $replacement->active_membership_id, 'active_membership_revision' => 1])->save();
    });
    try {
        expect(fn () => $this->store->findCancellation($this->user->id, 2, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    } finally {
        Event::forget($event);
    }
});
