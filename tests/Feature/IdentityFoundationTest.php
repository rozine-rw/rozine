<?php

declare(strict_types=1);

use App\Application\Identity\GetIdentityContext;
use App\Application\Identity\RegisterIdentity;
use App\Http\Resources\IdentityContextResource;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Sanctum\Sanctum;

it('registers an unverified Party without accepting supplied authority', function (): void {
    $this->skipUnlessFortifyHas(Features::registration());
    $existingParty = Party::factory()->verified()->create();

    $this->post(route('register.store'), [
        'name' => 'Alpha Participant',
        'email' => 'alpha@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'party_id' => $existingParty->id,
        'role' => 'admin',
        'status' => 'active',
        'verified_at' => now()->toISOString(),
        'email_verified_at' => now()->toISOString(),
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'alpha@example.test')->sole();
    $party = $user->party()->sole();

    expect(Str::isUlid($party->id))->toBeTrue()
        ->and($party->id)->not->toBe($existingParty->id)
        ->and($party->kind)->toBe('person')
        ->and($party->verified_at)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
    $this->assertDatabaseCount('parties', 2);
    $this->assertDatabaseCount('role_memberships', 0);
    $this->assertAuthenticatedAs($user);
});

it('rolls back the new Party when account creation fails', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(RegisterIdentity::class)->handle('Duplicate', $user->email, 'password'))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('parties', 0);
    $this->assertDatabaseCount('users', 1);
});

it('does not create a Party when registration validation fails', function (): void {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Alpha Participant',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['email', 'password']);

    $this->assertDatabaseCount('parties', 0);
    $this->assertDatabaseCount('users', 0);
});

it('requires authentication for both identity transports', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->getJson(route('api.v1.identity.show'))->assertUnauthorized();
});

it('shares exact identity facts between Inertia and the versioned API', function (): void {
    $this->freezeTime();
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);

    $expected = [
        'contract_version' => 'identity-v2',
        'policy_version' => 'engineering-2026-09-23.4',
        'code' => 'IDENTITY_READY',
        'party' => ['id' => $party->id, 'kind' => 'person', 'verification_status' => 'verified'],
        'available_roles' => ['investor', 'business'],
        'active_role' => null,
        'context_revision' => 0,
        'allowed_actions' => ['identity.select_role'],
    ];

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('dashboard')
            ->where('identity', $expected)
            ->missing('auth.user.party_id')
            ->missing('auth.user.party')
            ->missing('auth.user.password'));

    $this->getJson(route('api.v1.identity.show'))->assertExactJson(['data' => $expected]);
});

it('returns the same unverified state on both transports', function (): void {
    $party = Party::factory()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('identity.code', 'IDENTITY_VERIFICATION_REQUIRED')
            ->where('identity.available_roles', [])
            ->where('identity.party.verification_status', 'unverified'));

    $this->getJson(route('api.v1.identity.show'))
        ->assertOk()
        ->assertJsonPath('data.code', 'IDENTITY_VERIFICATION_REQUIRED')
        ->assertJsonPath('data.available_roles', []);
});

it('keeps legacy accounts unlinked until identity onboarding', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('api.v1.identity.show'))
        ->assertOk()
        ->assertJsonPath('data.code', 'IDENTITY_NOT_LINKED')
        ->assertJsonPath('data.party', null)
        ->assertJsonPath('data.available_roles', []);
});

it('ignores supplied user and Party selectors and supports Sanctum authentication', function (): void {
    $mine = Party::factory()->verified()->create();
    $other = Party::factory()->verified()->create();
    $user = User::factory()->for($mine)->create();
    $otherUser = User::factory()->for($other)->create();
    RoleMembership::factory()->for($mine)->active()->create(['role' => 'investor']);
    RoleMembership::factory()->for($other)->active()->create(['role' => 'auditor']);
    Sanctum::actingAs($user, ['identity:read']);

    $this->getJson(route('api.v1.identity.show', [
        'user_id' => $otherUser->id,
        'party_id' => $other->id,
        'role' => 'auditor',
    ]))->assertOk()
        ->assertJsonPath('data.party.id', $mine->id)
        ->assertJsonPath('data.available_roles', ['investor'])
        ->assertJsonMissing(['id' => $other->id])
        ->assertJsonMissing(['email' => $otherUser->email]);
});

it('applies membership conflicts to every login linked to the same person', function (): void {
    $party = Party::factory()->verified()->create();
    $users = User::factory()->for($party)->count(2)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);
    RoleMembership::factory()->for($party)->active()->create(['role' => 'auditor']);

    foreach ($users as $user) {
        $this->actingAs($user)->getJson(route('api.v1.identity.show'))
            ->assertOk()
            ->assertJsonPath('data.code', 'ROLE_MEMBERSHIP_CONFLICT')
            ->assertJsonPath('data.available_roles', []);
    }
});

it('rechecks changed memberships instead of trusting a previous identity response', function (): void {
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    $membership = RoleMembership::factory()->for($party)->active()->create();

    $this->actingAs($user)->getJson(route('api.v1.identity.show'))
        ->assertJsonPath('data.available_roles', ['investor']);

    $membership->status = 'suspended';
    $membership->save();

    $this->getJson(route('api.v1.identity.show'))
        ->assertJsonPath('data.code', 'ROLE_MEMBERSHIP_REQUIRED')
        ->assertJsonPath('data.available_roles', []);

    $membership->status = 'revoked';
    $membership->save();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('identity.available_roles', []));
});

it('denies future-dated verification', function (string $field): void {
    $this->freezeTime();
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();

    if ($field === 'email') {
        $user->forceFill(['email_verified_at' => now()->addDay()])->save();
    } else {
        $party->forceFill(['verified_at' => now()->addDay()])->save();
    }

    $this->actingAs($user)->getJson(route('api.v1.identity.show'))
        ->assertJsonPath('data.code', $field === 'email' ? 'EMAIL_VERIFICATION_REQUIRED' : 'IDENTITY_VERIFICATION_REQUIRED')
        ->assertJsonPath('data.available_roles', []);
})->with(['email', 'party']);

it('requires effective entity authority before exposing organization roles', function (): void {
    $party = Party::factory()->verified()->create(['kind' => 'organization']);
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();

    $this->actingAs($user)->getJson(route('api.v1.identity.show'))
        ->assertJsonPath('data.code', 'PARTY_AUTHORITY_REQUIRED')
        ->assertJsonPath('data.available_roles', []);
});

it('enforces one membership per role and Party at the database boundary', function (): void {
    $membership = RoleMembership::factory()->create();

    expect($membership->status)->toBe('pending')
        ->and($membership->party->id)->toBe($membership->party_id)
        ->and(fn () => RoleMembership::factory()->create([
            'party_id' => $membership->party_id,
            'role' => $membership->role,
        ]))->toThrow(QueryException::class);
});

it('does not accept mass assigned verification or memberships', function (): void {
    $party = new Party;
    $party->fill(['kind' => 'person', 'verified_at' => now()]);
    $user = new User;
    $user->fill(['party_id' => '01J00000000000000000000000']);

    expect($party->verified_at)->toBeNull()
        ->and($user->party_id)->toBeNull()
        ->and((new RoleMembership)->isFillable('role'))->toBeFalse()
        ->and((new RoleMembership)->isFillable('status'))->toBeFalse();
});

it('does not serialize extra fields supplied to the identity Resource', function (): void {
    $request = Request::create('/api/v1/identity');
    $user = User::factory()->create();
    $context = app(GetIdentityContext::class)->handle($user->id);

    $resource = new IdentityContextResource([
        ...$context,
        'password' => 'must-not-leak',
        'raw_evidence' => 'must-not-leak',
    ]);

    expect($resource->resolve($request))->toBe($context);
});
