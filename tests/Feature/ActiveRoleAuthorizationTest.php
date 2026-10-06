<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\ChangeMembership;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

/** @return array{User, Party, RoleMembership} */
function roleParticipant(string $role = 'investor'): array
{
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    $membership = RoleMembership::factory()->for($party)->active()->create(['role' => $role]);

    return [$user, $party, $membership];
}

it('persists a selected role and exposes identical facts after a new request through Inertia and API', function (): void {
    [$user] = roleParticipant();
    $payload = ['role' => 'investor', 'expected_revision' => 0, 'request_id' => (string) Str::uuid()];
    $selected = $this->actingAs($user)->postJson(route('identity.active-role.store'), $payload)
        ->assertOk()->assertJsonPath('data.active_role', 'investor')->assertJsonPath('data.context_revision', 1)->json('data');

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('identity', $selected)->missing('auth.user.active_membership_id')->missing('auth.user.context_revision'));
    $this->getJson(route('api.v1.identity.show'))->assertExactJson(['data' => $selected]);
    $this->getJson(route('identity.roles.show', 'investor'))->assertExactJson(['data' => $selected]);
    $this->getJson(route('api.v1.identity.roles.show', 'investor'))->assertExactJson(['data' => $selected]);
    expect($selected['allowed_actions'])->toBe(['identity.select_role', 'identity.view_role']);
});

it('requires an active selection and rejects access through another role after switching', function (): void {
    [$user, $party] = roleParticipant();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    $this->actingAs($user)->getJson(route('identity.roles.show', 'investor'))->assertForbidden()->assertJsonPath('code', 'ACTIVE_ROLE_REQUIRED');
    $action = app(SelectActiveRole::class);
    $action->handle($user->id, 'investor', 0, (string) Str::uuid());
    $action->handle($user->id, 'business', 1, (string) Str::uuid());
    $this->getJson(route('identity.roles.show', 'investor'))->assertForbidden()->assertJsonPath('code', 'ACTIVE_ROLE_REQUIRED');
    $this->getJson(route('identity.roles.show', 'business'))->assertOk()->assertJsonPath('data.active_role', 'business');
});

it('does not allow supplied Party user or membership selectors to change the authorization scope', function (): void {
    [$user, $party] = roleParticipant();
    [$other, $otherParty, $otherMembership] = roleParticipant('business');
    $this->actingAs($user)->postJson(route('api.v1.identity.active-role.store'), [
        'role' => 'business', 'expected_revision' => 0, 'request_id' => (string) Str::uuid(),
        'party_id' => $otherParty->id, 'user_id' => $other->id, 'membership_id' => $otherMembership->id,
    ])->assertForbidden()->assertJsonPath('code', 'ROLE_NOT_AVAILABLE');
    expect($user->refresh()->active_membership_id)->toBeNull();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    expect(fn () => app(AuthorizeActiveRole::class)->handle($user->id, 'investor', $otherParty->id, 1,
        fn () => throw new LogicException('Unauthorized operation ran.')))
        ->toThrow(IdentityViolation::class, 'IDENTITY_RECORD_NOT_FOUND');
    expect(app(AuthorizeActiveRole::class)->handle($user->id, 'investor', $party->id, 1, fn (array $context): string => $context['party']['id']))->toBe($party->id);
});

it('rechecks membership suspension revocation and revision changes for every authorization', function (string $status): void {
    [$user, $party, $membership] = roleParticipant();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $operator = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $operator->id]);
    app(ChangeMembership::class)->handle($operator->id, $party->id, 'investor', $status, 1, 'case:withdrawn', 'Authority changed.', (string) Str::uuid());
    $this->actingAs($user)->getJson(route('identity.roles.show', 'investor'))->assertForbidden();
    $this->getJson(route('api.v1.identity.show'))->assertOk()->assertJsonPath('data.active_role', null);
    if ($status === 'suspended') {
        app(ChangeMembership::class)->handle($operator->id, $party->id, 'investor', 'active', 2, 'case:restored', 'Eligibility restored.', (string) Str::uuid());
        $this->getJson(route('identity.roles.show', 'investor'))->assertForbidden()->assertJsonPath('code', 'ACTIVE_ROLE_REQUIRED');
        app(SelectActiveRole::class)->handle($user->id, 'investor', 1, (string) Str::uuid());
        $this->getJson(route('identity.roles.show', 'investor'))->assertOk();
    }
})->with(['suspended', 'revoked']);

it('keeps active roles independent for logins sharing a Party while applying membership changes to both', function (): void {
    [$one, $party] = roleParticipant();
    $two = User::factory()->for($party)->create();
    $business = RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($one->id, 'investor', 0, (string) Str::uuid());
    app(SelectActiveRole::class)->handle($two->id, 'business', 0, (string) Str::uuid());
    $this->actingAs($one)->getJson(route('api.v1.identity.show'))->assertJsonPath('data.active_role', 'investor');
    $this->actingAs($two)->getJson(route('api.v1.identity.show'))->assertJsonPath('data.active_role', 'business');
    $business->forceFill(['status' => 'revoked', 'revision' => 2])->save();
    $this->getJson(route('identity.roles.show', 'business'))->assertForbidden();
    $this->actingAs($one)->getJson(route('identity.roles.show', 'investor'))->assertOk();
});

it('rejects stale context commands and never replays an old selection over a newer one', function (): void {
    [$user, $party] = roleParticipant();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    $payload = ['role' => 'investor', 'expected_revision' => 0, 'request_id' => (string) Str::uuid()];
    $this->actingAs($user)->postJson(route('identity.active-role.store'), $payload)->assertOk();
    $this->postJson(route('api.v1.identity.active-role.store'), $payload)->assertOk()->assertJsonPath('data.context_revision', 1);
    $this->postJson(route('identity.active-role.store'), array_replace($payload, ['role' => 'business']))
        ->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
    $this->postJson(route('identity.active-role.store'), array_replace($payload, ['request_id' => (string) Str::uuid()]))
        ->assertConflict()->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');
    app(SelectActiveRole::class)->handle($user->id, 'business', 1, (string) Str::uuid());
    $this->postJson(route('identity.active-role.store'), $payload)->assertOk()->assertJsonPath('data.active_role', 'business')
        ->assertJsonPath('data.context_revision', 2);
    expect(fn () => app(AuthorizeActiveRole::class)->handle($user->id, 'business', $party->id, 1, fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    $this->assertDatabaseCount('identity_audit_events', 2);
});

it('requires current confirmed MFA for the Auditor context', function (): void {
    [$user] = roleParticipant('auditor');
    $this->actingAs($user)->postJson(route('identity.active-role.store'), [
        'role' => 'auditor', 'expected_revision' => 0, 'request_id' => (string) Str::uuid(),
    ])->assertForbidden()->assertJsonPath('code', 'MFA_REQUIRED');
    $this->getJson(route('api.v1.identity.show'))->assertJsonPath('data.allowed_actions', []);
    $user->forceFill(['two_factor_secret' => encrypt('synthetic'), 'two_factor_confirmed_at' => now()])->save();
    app(SelectActiveRole::class)->handle($user->id, 'auditor', 0, (string) Str::uuid());
    $this->getJson(route('identity.roles.show', 'auditor'))->assertOk();
    $features = config('fortify.features');
    config(['fortify.features' => []]);
    $this->getJson(route('identity.roles.show', 'auditor'))->assertForbidden()->assertJsonPath('code', 'MFA_REQUIRED');
    config(['fortify.features' => $features]);
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->getJson(route('identity.roles.show', 'auditor'))->assertForbidden()->assertJsonPath('code', 'MFA_REQUIRED');
    $this->getJson(route('api.v1.identity.show'))->assertJsonPath('data.active_role', null);
});

it('denies role selection when verification or retained memberships invalidate the person', function (string $failure): void {
    [$user, $party] = roleParticipant();
    match ($failure) {
        'email' => $user->forceFill(['email_verified_at' => null])->save(),
        'identity' => $party->forceFill(['verified_at' => null])->save(),
        'future identity' => $party->forceFill(['verified_at' => now()->addDay()])->save(),
        'conflict' => RoleMembership::factory()->for($party)->create(['role' => 'auditor']),
        default => throw new LogicException('Unknown test scenario.'),
    };
    $this->actingAs($user)->postJson(route('identity.active-role.store'), [
        'role' => 'investor', 'expected_revision' => 0, 'request_id' => (string) Str::uuid(),
    ])->assertForbidden();
    $this->assertDatabaseCount('identity_audit_events', 0);
})->with(['email', 'identity', 'future identity', 'conflict']);

it('requires authentication for all identity mutations', function (string $route): void {
    $this->postJson(route($route), [])->assertUnauthorized();
})->with(['identity.people.resolve', 'identity.memberships.update', 'identity.active-role.store',
    'api.v1.identity.people.resolve', 'api.v1.identity.memberships.update', 'api.v1.identity.active-role.store']);

it('checks token abilities as well as current membership for selection and role access', function (): void {
    [$user] = roleParticipant();
    Sanctum::actingAs($user, ['identity:read']);
    $this->postJson(route('api.v1.identity.active-role.store'), [])->assertForbidden();
    $this->getJson(route('api.v1.identity.roles.show', 'investor'))->assertForbidden();
    Sanctum::actingAs($user, ['identity:select-role', 'identity:access']);
    $this->postJson(route('api.v1.identity.active-role.store'), [
        'role' => 'investor', 'expected_revision' => 0, 'request_id' => (string) Str::uuid(),
    ])->assertOk();
    $this->getJson(route('api.v1.identity.roles.show', 'investor'))->assertOk();
});
