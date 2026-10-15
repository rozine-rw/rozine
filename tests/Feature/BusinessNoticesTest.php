<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\DepositPolicy;
use App\Models\PartyNotice;
use App\Models\PartyNoticeDelivery;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;

/*
 * In-app notices behind the Business's bell (BRS v2.0 NTF-R01): held by the Business's entity Party,
 * read only under current Business authority, and shared by everyone who may view the Business.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $authority = BusinessAuthorityFixture::make('organization', 2, 'COMPANY-'.Str::ulid(), 1);
    $viewer = $authority['people'][1]->id;
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer ? [...$person, 'permissions' => ['business.view']] : $person,
        $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority);
    $this->business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->sole();
    [$this->owner, $this->viewer] = $authority['users'];
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $this->business->id]);
    $this->credit = function (string $amount = '50000', string $state = 'succeeded', ?string $event = null) use ($method): BusinessDepositIntent {
        $result = app(RecordBusinessDeposit::class)->handle($this->owner->id, 1, $this->business->id, (string) Str::uuid(),
            ['currency' => 'RWF', 'amount' => $amount], $method->id);
        $intent = BusinessDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole();
        app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
            'event_id' => $event ?? 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => $state,
            'amount' => $amount, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));

        return $intent;
    };
});

it('notices a credited Business deposit once, in-app only, and never a failed one', function (): void {
    ($this->credit)('50000', event: 'synthetic-event-business-once');
    ($this->credit)('7000', 'failed');

    $notice = PartyNotice::query()->sole();
    expect([$notice->party_id, $notice->kind, $notice->facts, $notice->event_key])->toEqual([$this->business->entity_party_id, 'business.deposit_received',
        ['amount' => '50000', 'currency' => 'RWF', 'business_id' => $this->business->id], 'business-deposit:'.BusinessDepositCredit::query()->sole()->id])
        ->and(PartyNoticeDelivery::query()->sole()->only(['channel', 'status', 'user_id']))->toBe(['channel' => 'in_app', 'status' => 'delivered', 'user_id' => null]);
});

it('lists the Business notices to everyone who may view it, and marks one, then all, read for them all', function (): void {
    ($this->credit)('50000');
    $this->travel(1)->seconds();
    ($this->credit)('20000');
    [$newest, $oldest] = PartyNotice::query()->orderByDesc('created_at')->pluck('id')->all();
    $business = $this->business->id;

    $this->actingAs($this->viewer)->getJson(route('business.notices.index', $business))->assertOk()
        ->assertJsonPath('data.unread', 2)->assertJsonCount(2, 'data.notices')
        ->assertJsonPath('data.notices.0.id', $newest)->assertJsonPath('data.notices.0.kind', 'business.deposit_received')
        ->assertJsonPath('data.notices.0.facts.amount', '20000')->assertJsonPath('data.notices.0.read', false)
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$business}/wallet", 'method' => 'get'])
        ->assertJsonPath('data.notices.0.read_action', ['url' => "/business/{$business}/notices/{$newest}/read", 'method' => 'post'])
        ->assertJsonPath('data.actions.read_all', ['url' => "/business/{$business}/notices/read-all", 'method' => 'post']);
    $this->get(route('business.show', $business))->assertInertia(fn ($page) => $page->where('unread_notifications', 2));

    $this->postJson(route('business.notices.read', [$business, $newest]))->assertOk()->assertJsonPath('data.unread', 1);
    $this->actingAs($this->owner)->getJson(route('business.notices.index', $business))->assertJsonPath('data.notices.0.read', true)
        ->assertJsonPath('data.notices.0.read_action', null)->assertJsonPath('data.notices.1.id', $oldest);
    $this->postJson(route('business.notices.read_all', $business))->assertOk()->assertJsonPath('data.unread', 0);
    $this->getJson(route('business.notices.index', $business))->assertJsonPath('data.unread', 0)->assertJsonPath('data.actions.read_all', null);
    $this->get(route('business.show', $business))->assertInertia(fn ($page) => $page->where('unread_notifications', 0)
        ->where('links.notifications', ['url' => "/business/{$business}/notices", 'method' => 'get']));
});

it('keeps a Business\'s notices from anyone outside its mandate', function (): void {
    ($this->credit)();
    $notice = PartyNotice::query()->sole();
    $stranger = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($stranger);
    $business = $this->business->id;

    $this->actingAs($stranger['users'][0])->getJson(route('business.notices.index', $business))->assertNotFound();
    $this->postJson(route('business.notices.read_all', $business))->assertNotFound();
    $this->actingAs(User::factory()->create())->getJson(route('business.notices.index', $business))->assertForbidden();
    $this->postJson(route('business.notices.read', [$business, $notice->id]))->assertForbidden();
    expect($notice->refresh()->read_at)->toBeNull();
});

it('reads Business notices over API v1 with business:read and marks them read only with business:command', function (): void {
    ($this->credit)();
    $notice = PartyNotice::query()->sole();
    $business = $this->business->id;

    Sanctum::actingAs($this->owner, ['investor:read']);
    $this->getJson(route('api.v1.business.notices.index', $business))->assertForbidden();
    Sanctum::actingAs($this->owner, ['business:read']);
    $this->getJson(route('api.v1.business.notices.index', $business))->assertOk()->assertJsonPath('data.unread', 1)
        ->assertJsonPath('data.notices.0.link.url', "/api/v1/business/{$business}/wallet")
        ->assertJsonPath('data.notices.0.read_action.url', "/api/v1/business/{$business}/notices/{$notice->id}/read");
    $this->postJson(route('api.v1.business.notices.read', [$business, $notice->id]))->assertForbidden();
    Sanctum::actingAs($this->owner, ['business:read', 'business:command']);
    $this->postJson(route('api.v1.business.notices.read', [$business, $notice->id]))->assertOk()->assertJsonPath('data.unread', 0);
});
