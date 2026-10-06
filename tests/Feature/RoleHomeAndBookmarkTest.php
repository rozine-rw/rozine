<?php

declare(strict_types=1);

use App\Application\Identity\GetRoleBookmark;
use App\Application\Identity\SaveRoleBookmark;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\BookmarkDestination;
use App\Domain\Identity\IdentityViolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\RoleBookmark;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->withoutVite();
});

/** @return array{User, RoleMembership} */
function bookmarkParticipant(string $role = 'investor'): array
{
    $party = Party::factory()->verified()->create();
    $user = User::factory()->withTwoFactor()->for($party)->create();
    $membership = RoleMembership::factory()->for($party)->active()->create(['role' => $role]);

    return [$user, $membership];
}

/** @return array<string, mixed> */
function bookmarkPayload(string $role = 'investor', string $section = 'access', int $revision = 1): array
{
    return ['role' => $role, 'route' => $role.'.home', 'parameters' => [], 'query' => ['section' => $section],
        'expected_revision' => $revision, 'request_id' => (string) Str::uuid()];
}

it('authorizes each home without switching roles and shares the API identity facts', function (string $role): void {
    [$user] = bookmarkParticipant($role);
    $this->actingAs($user)->getJson(route($role.'.home'))->assertForbidden()->assertJsonPath('code', 'ACTIVE_ROLE_REQUIRED');
    expect($user->refresh()->context_revision)->toBe(0);
    $selected = app(SelectActiveRole::class)->handle($user->id, $role, 0, (string) Str::uuid());
    $this->get(route($role.'.home', ['section' => 'access']))->assertInertia(fn (Assert $page): Assert => $page
        ->component('identity/role-home', false)->where('identity', $selected)->where('role', $role)->where('section', 'access'));
    $this->get(route($role.'.home'))->assertInertia(fn (Assert $page): Assert => $page->where('section', 'overview'));
    $this->getJson(route('api.v1.identity.roles.show', $role))->assertExactJson(['data' => $selected]);
    expect($user->refresh()->context_revision)->toBe(1);
})->with(['investor', 'business', 'auditor']);

it('saves and resumes the current account position with web and API parity', function (): void {
    [$user] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($user)->get(route('identity.roles.resume', 'investor'))->assertRedirectToRoute('investor.home');
    $payload = bookmarkPayload();
    $saved = $this->postJson(route('identity.bookmarks.store'), $payload)->assertOk()
        ->assertJsonPath('data.contract_version', 'role-bookmark-v1')->assertJsonPath('data.url', '/investor?section=access')->json('data');
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertExactJson(['data' => $saved]);
    $this->getJson(route('api.v1.identity.bookmarks.show', 'investor'))->assertExactJson(['data' => $saved]);
    $this->postJson(route('api.v1.identity.bookmarks.store'), $payload)->assertExactJson(['data' => $saved]);
    $this->get(route('identity.roles.resume', 'investor'))->assertRedirectToRoute('investor.home', ['section' => 'access']);
    $this->assertDatabaseCount('role_bookmarks', 1);
    expect(IdentityAuditEvent::query()->where('action', 'bookmark.save')->count())->toBe(1);
    $this->post(route('logout'))->assertRedirect();
    $this->get(route('identity.roles.resume', 'investor'))->assertRedirectToRoute('login');
    $this->actingAs($user->fresh())->get(route('identity.roles.resume', 'investor'))->assertRedirectToRoute('investor.home', ['section' => 'access']);
});

it('separates accounts and roles and rejects stale context writes', function (): void {
    [$user, $membership] = bookmarkParticipant();
    $other = User::factory()->create(['party_id' => $user->party_id]);
    RoleMembership::factory()->create(['party_id' => $user->party_id, 'role' => 'business', 'status' => 'active']);
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    app(SelectActiveRole::class)->handle($other->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($user)->postJson(route('identity.bookmarks.store'), array_merge(bookmarkPayload(), ['user_id' => $other->id]))->assertOk();
    $this->actingAs($other)->getJson(route('identity.bookmarks.show', 'investor'))->assertJsonPath('data.url', '/investor');
    app(SelectActiveRole::class)->handle($user->id, 'business', 1, (string) Str::uuid());
    $this->actingAs($user)->postJson(route('identity.bookmarks.store'), bookmarkPayload())->assertConflict();
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertForbidden();
    $this->getJson(route('identity.bookmarks.show', 'business'))->assertJsonPath('data.url', '/business');
    $this->postJson(route('identity.bookmarks.store'), bookmarkPayload('business', 'overview', 2))->assertOk();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 2, (string) Str::uuid());
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertJsonPath('data.url', '/investor?section=access');
    $membership->forceFill(['status' => 'suspended', 'revision' => 2])->save();
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertForbidden();
    $membership->forceFill(['status' => 'active', 'revision' => 3])->save();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 3, (string) Str::uuid());
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertJsonPath('data.url', '/investor');
});

it('returns current positions on retry without undoing a newer save and rejects request ID reuse', function (): void {
    [$user] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $first = bookmarkPayload();
    $this->actingAs($user)->postJson(route('identity.bookmarks.store'), $first)->assertOk();
    $this->postJson(route('identity.bookmarks.store'), bookmarkPayload('investor', 'overview'))->assertOk();
    $this->postJson(route('identity.bookmarks.store'), $first)->assertJsonPath('data.url', '/investor?section=overview');
    $this->postJson(route('identity.bookmarks.store'), array_replace($first, ['query' => []]))->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
    $this->assertDatabaseCount('role_bookmarks', 1);
});

it('rejects arbitrary routes URLs parameters and stored page content', function (array $changes): void {
    [$user] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($user)->postJson(route('identity.bookmarks.store'), array_replace(bookmarkPayload(), $changes))->assertUnprocessable();
    $this->assertDatabaseCount('role_bookmarks', 0);
})->with([
    [['route' => 'admin.home']], [['route' => 'business.home']], [['route' => 'https://example.com']],
    [['parameters' => ['user' => 'other']]], [['query' => ['amount' => '5000']]],
    [['query' => ['section' => 'https://example.com']]], [['query' => ['section' => ['access']]]],
    [['query' => ['section' => null]]], [['request_id' => 'invalid']],
]);

it('validates destination authority inside the shared action and falls back for obsolete stored destinations', function (): void {
    [$user, $membership] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    expect(fn () => app(SaveRoleBookmark::class)->handle($user->id, 'investor', 'admin.home', [], [], 1, (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'BOOKMARK_DESTINATION_INVALID');
    expect(fn () => app(BookmarkDestination::class)->validate('admin', 'admin.home', [], []))->toThrow(IdentityViolation::class);
    RoleBookmark::factory()->create(['user_id' => $user->id, 'membership_id' => $membership->id, 'route' => 'removed.route']);
    expect(app(GetRoleBookmark::class)->handle($user->id, 'investor')['route'])->toBe('investor.home');
});

it('rolls back navigation writes when their audit cannot be recorded', function (): void {
    [$user] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    IdentityAuditEvent::creating(fn () => throw new RuntimeException('Audit unavailable.'));
    try {
        expect(fn () => app(SaveRoleBookmark::class)->handle($user->id, 'investor', 'investor.home', [], [], 1, (string) Str::uuid()))->toThrow(RuntimeException::class);
        $this->assertDatabaseCount('role_bookmarks', 0);
    } finally {
        IdentityAuditEvent::flushEventListeners();
    }
});

it('requires authentication and scoped token ability for navigation', function (): void {
    $this->getJson(route('identity.bookmarks.show', 'investor'))->assertUnauthorized();
    $this->postJson(route('identity.bookmarks.store'), bookmarkPayload())->assertUnauthorized();
    [$user] = bookmarkParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    Sanctum::actingAs($user, ['identity:read']);
    $this->getJson(route('api.v1.identity.bookmarks.show', 'investor'))->assertForbidden();
    $this->postJson(route('api.v1.identity.bookmarks.store'), bookmarkPayload())->assertForbidden();
    Sanctum::actingAs($user, ['identity:access']);
    $this->postJson(route('api.v1.identity.bookmarks.store'), bookmarkPayload())->assertOk();
});

it('replaces stale Inertia home content with a denied page while keeping JSON errors stable', function (): void {
    [$user] = bookmarkParticipant();
    $this->actingAs($user)->get(route('investor.home'))->assertForbidden()->assertInertia(fn (Assert $page): Assert => $page
        ->component('identity/access-denied')->where('code', 'ACTIVE_ROLE_REQUIRED')->missing('identity'));
    $version = app(HandleInertiaRequests::class)->version(Request::create('/investor'));
    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('investor.home'))->assertForbidden()
        ->assertJsonPath('component', 'identity/access-denied')->assertJsonMissingPath('props.identity');
});

it('rolls back and reapplies the checkpoint schema without disturbing identity accounts', function (): void {
    $user = User::factory()->create();
    $migration = require database_path('migrations/2026_09_24_020204_create_staff_access_and_role_bookmarks.php');
    $migration->down();
    expect(Schema::hasTable('staff_accounts'))->toBeFalse()
        ->and(Schema::hasTable('role_bookmarks'))->toBeFalse()
        ->and(User::query()->find($user->id))->not->toBeNull();
    $migration->up();
    expect(Schema::hasTable('staff_accounts'))->toBeTrue()
        ->and(Schema::hasTable('role_bookmarks'))->toBeTrue();
});
