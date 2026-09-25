<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\ListBusinessApplications;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\RoleMembership;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessApplicationFixture as DraftFixture;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->freezeTime();
    $this->withoutVite();
});

it('links the actual Business role home to create resume and submitted applications without invented wallet facts', function (): void {
    $authority = AuthorityFixture::make();
    $business = AuthorityFixture::configure($authority)['data']['business']['id'];
    $this->actingAs($authority['users'][0])->get('/business')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/role-home')
            ->where('business_applications.identity_context_revision', 1)->where('business_applications.entries.0.business_id', $business)
            ->where('business_applications.entries.0.application', null)->where('business_applications.entries.0.actions.create.url', '/business/'.$business.'/applications')
            ->where('business_applications.operation.url', '/business/application-operations/{request_id}')->missing('wallet')->missing('capital'));
    $created = $this->postJson('/business/'.$business.'/applications', ['identity_context_revision' => 1, 'expected_revision' => 0, 'request_id' => (string) Str::uuid()])->assertOk()->json();
    $this->get('/business')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('business_applications.entries.0.application.link.url', $created['data']['next']['url'])
        ->where('business_applications.entries.0.application.status', 'draft')->where('business_applications.entries.0.application.step', 'business'));
    $ready = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($ready, BusinessQuoteFixture::acceptance($ready));
    $this->actingAs($ready['audit']['authority']['users'][0])->get('/business')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('business_applications.entries.0.application.status', 'submitted')->where('business_applications.entries.0.application.step', 'submitted'));
});

it('provides API links and strips mutation capabilities from read-only tokens', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    Sanctum::actingAs($user, ['business:read']);
    $page = $this->getJson('/api/v1/business')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.entries.0.allowed_actions', [])->assertJsonPath('data.entries.0.actions.create', null)
        ->assertJsonPath('data.entries.0.application.link.url', '/api/v1/business/'.$fixture['business']->id.'/applications/'.$fixture['application']->id)
        ->assertJsonPath('data.operation.url', '/api/v1/business/application-operations/{request_id}')->json('data');
    expect(json_encode($page, JSON_THROW_ON_ERROR))->not->toContain('mandate', 'company_code', 'story', 'wallet', 'credit', 'actor_user_id');
    $this->getJson($page['entries'][0]['application']['link']['url'])->assertOk()->assertJsonPath('data.shell_links.home.url', '/api/v1/business');
    Sanctum::actingAs($user, ['business:read', 'business:command']);
    $this->getJson('/api/v1/business?identity_context_revision=1')->assertOk()->assertJsonPath('data.entries.0.allowed_actions', ['application.create'])
        ->assertJsonPath('data.entries.0.actions.create.url', '/api/v1/business/'.$fixture['business']->id.'/applications');
});

it('only lists the current mandate and gives a view-only representative a read link without create', function (): void {
    $fixture = DraftFixture::make('organization', 2);
    $authority = $fixture['authority'];
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => ['business.view'], 'roles' => ['representative']] : $person, $authority['terms']['people']);
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    AuthorityFixture::configure($authority, 1);
    $this->actingAs($authority['users'][1])->get('/business')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->has('business_applications.entries', 1)->where('business_applications.entries.0.allowed_actions', [])
        ->where('business_applications.entries.0.actions.create', null)->where('business_applications.entries.0.application.id', $fixture['application']->id));
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => []] : $person, $authority['terms']['people']);
    AuthorityFixture::configure($authority, 2);
    $this->get('/business')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('business_applications.entries', []));
});

it('paginates only related effective mandates with a bounded stable opaque cursor', function (): void {
    $base = AuthorityFixture::make();
    $own = AuthorityFixture::configure($base)['data']['business']['id'];
    $related = [AuthorityFixture::relatedOrganization($base), AuthorityFixture::relatedOrganization($base)];
    $excluded = [AuthorityFixture::relatedOrganization($base), AuthorityFixture::relatedOrganization($base), AuthorityFixture::relatedOrganization($base)];
    $excluded[0]['terms']['status'] = 'revoked';
    $excluded[1]['terms']['effective_at'] = now('UTC')->subDays(2)->format('Y-m-d\TH:i:s\Z');
    $excluded[1]['terms']['expires_at'] = now('UTC')->subDay()->format('Y-m-d\TH:i:s\Z');
    $excluded[2]['terms']['effective_at'] = now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z');
    foreach ($excluded as $authority) {
        AuthorityFixture::configure($authority, 1);
    }
    AuthorityFixture::configure(AuthorityFixture::make('organization'));
    $expected = [$own];
    foreach ($related as $authority) {
        $business = AuthorityFixture::configure($authority, 1)['data']['business']['id'];
        $expected[] = $business;
    }
    rsort($expected);
    Sanctum::actingAs($base['users'][0], ['business:read']);
    $first = $this->getJson('/api/v1/business?limit=1')->assertOk()->assertJsonCount(1, 'data.entries')->json('data');
    expect($first['entries'][0]['business_id'])->toBe($expected[0]);
    $second = $this->getJson($first['pagination']['next']['url'])->assertOk()->assertJsonPath('data.entries.0.business_id', $expected[1])->json('data');
    $this->getJson($second['pagination']['next']['url'])->assertOk()->assertJsonPath('data.entries.0.business_id', $expected[2])->assertJsonPath('data.pagination.next', null);
    $this->actingAs($base['users'][0])->get('/business?limit=1')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('business_applications.pagination.next.url', '/business?before='.$expected[0].'&limit=1&identity_context_revision=1'));
    $this->getJson('/api/v1/business?before=not-an-id')->assertUnprocessable();
    $this->getJson('/api/v1/business?limit=51')->assertUnprocessable();
    $this->getJson('/api/v1/business?limit=0')->assertUnprocessable();
});

it('validates pagination at the application port as well as HTTP', function (?string $before, int $limit): void {
    $fixture = DraftFixture::make();
    expect(fn () => app(ListBusinessApplications::class)->handle($fixture['authority']['users'][0]->id, 1, $before, $limit))
        ->toThrow(CommandRejection::class, 'APPLICATION_PAGE_INVALID');
})->with([['invalid', 20], [null, 0], [null, 51]]);

it('reauthorizes discovered entities and preserves continuation when authority is lost before projection', function (string $code): void {
    $fixture = DraftFixture::make();
    $cursor = (string) Str::ulid();
    $port = $this->createMock(BusinessAuthorityStore::class);
    $port->expects($this->once())->method('discover')->willReturn(['ids' => [$fixture['business']->id], 'next_cursor' => $cursor]);
    $port->expects($this->once())->method('withAuthority')->willThrowException(new CommandRejection($code, 403));
    app()->instance(BusinessAuthorityStore::class, $port);
    $result = app(ListBusinessApplications::class)->handle($fixture['authority']['users'][0]->id, 1);
    expect($result['entries'])->toBe([])->and($result['next_cursor'])->toBe($cursor);
})->with(['BUSINESS_NOT_FOUND', 'MANDATE_REQUIRED', 'ACTION_FORBIDDEN']);

it('propagates unexpected discovery projection failures instead of pretending there are no applications', function (bool $identityFailure): void {
    $fixture = DraftFixture::make();
    $port = $this->createMock(BusinessAuthorityStore::class);
    $port->expects($this->once())->method('discover')->willReturn(['ids' => [$fixture['business']->id], 'next_cursor' => null]);
    $failure = $identityFailure ? new IdentityViolation('SYNTHETIC_BACKEND_UNAVAILABLE', 503) : new CommandRejection('SYNTHETIC_BACKEND_UNAVAILABLE', 503);
    $port->expects($this->once())->method('withAuthority')->willThrowException($failure);
    app()->instance(BusinessAuthorityStore::class, $port);
    expect(fn () => app(ListBusinessApplications::class)->handle($fixture['authority']['users'][0]->id, 1))
        ->toThrow($failure::class, 'SYNTHETIC_BACKEND_UNAVAILABLE');
})->with([false, true]);

it('requires current role and token authorization for the entry point', function (): void {
    $this->get('/business')->assertRedirect(route('login'));
    $this->getJson('/api/v1/business')->assertUnauthorized();
    $fixture = DraftFixture::make();
    Sanctum::actingAs($fixture['authority']['users'][0], []);
    $this->getJson('/api/v1/business')->assertForbidden();
    Sanctum::actingAs($fixture['authority']['users'][0], ['business:read']);
    $this->getJson('/api/v1/business?identity_context_revision=0')->assertConflict();
    RoleMembership::query()->where('party_id', $fixture['authority']['people'][0]->id)->where('role', 'business')->update(['status' => 'revoked']);
    $this->getJson('/api/v1/business')->assertForbidden();
    $this->actingAs($fixture['authority']['users'][0])->get('/business')->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied'));
});

it('rejects membership withdrawal after entry projection before returning the page', function (): void {
    $fixture = DraftFixture::make();
    $real = app(BusinessAuthorityStore::class);
    $port = $this->createMock(BusinessAuthorityStore::class);
    $port->expects($this->once())->method('discover')->willReturn($real->discover($fixture['authority']['users'][0]->id, 1));
    $port->expects($this->once())->method('withAuthority')->willReturnCallback(
        function (int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation) use ($fixture, $real): array {
            $result = $real->withAuthority($userId, $contextRevision, $businessId, $permission, $mandateVersion,
                fn (array $business, array $identity): array => $operation($business, $identity));
            RoleMembership::query()->where('party_id', $fixture['authority']['people'][0]->id)->where('role', 'business')->update(['status' => 'revoked']);

            return $result;
        });
    app()->instance(BusinessAuthorityStore::class, $port);
    expect(fn () => app(ListBusinessApplications::class)->handle($fixture['authority']['users'][0]->id, 1))
        ->toThrow(IdentityViolation::class);
});

it('omits a related entity whose verification changed without hiding other authorized businesses', function (string $change): void {
    $authority = AuthorityFixture::make();
    $own = AuthorityFixture::configure($authority)['data']['business']['id'];
    $other = AuthorityFixture::relatedOrganization($authority);
    if ($change === 'person') {
        $other['people'][0]->forceFill(['verified_at' => null])->save();
    } else {
        VerifiedOrganizationIdentity::query()->where('party_id', $other['entity'])->update(['registry_digest' => hash('sha256', 'RDB:CHANGED')]);
    }
    Sanctum::actingAs($authority['users'][0], ['business:read']);
    $this->getJson('/api/v1/business')->assertOk()->assertJsonCount(1, 'data.entries')->assertJsonPath('data.entries.0.business_id', $own);
})->with(['person', 'registry']);
