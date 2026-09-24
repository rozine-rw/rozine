<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\ResolveVerifiedPerson;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->withoutVite();
});

it('keeps Admin entry independent from identity operator and marketplace permissions', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user)->getJson(route('admin.home'))->assertForbidden()->assertJsonPath('code', 'STAFF_ACCESS_REQUIRED');
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('staff_access.can_open_admin', false));
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Approved console access.', (string) Str::uuid());
    $access = ['contract_version' => 'staff-access-v1', 'can_open_admin' => true, 'allowed_actions' => ['admin.open']];
    $this->get(route('admin.home'))->assertInertia(fn (Assert $page): Assert => $page->component('identity/staff-home', false)->where('staff_access', $access));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('staff_access', $access)->where('identity.available_roles', []));
    $this->getJson(route('api.v1.staff-access.show'))->assertExactJson(['data' => $access]);
    $this->getJson(route('business.home'))->assertForbidden();
    expect(Artisan::call('identity:staff', ['user' => $user->id, '--revoke' => true, '--reason' => 'Access ended.']))->toBe(0);
    $this->getJson(route('admin.home'))->assertForbidden();
    $this->getJson(route('api.v1.staff-access.show'))->assertJsonPath('data.allowed_actions', []);
});

it('grants only Admin entry and audits the dedicated account bootstrap with idempotency', function (): void {
    $party = Party::factory()->create();
    $user = User::factory()->withTwoFactor()->for($party)->create();
    $id = (string) Str::uuid();
    $action = app(ConfigureStaffAccess::class);
    $first = $action->handle($user->id, true, 'Dedicated staff account.', $id);
    expect($action->handle($user->id, true, 'Dedicated staff account.', $id))->toBe($first)
        ->and($user->refresh()->party_id)->toBeNull()->and($user->context_revision)->toBe(1);
    $this->assertDatabaseCount('identity_audit_events', 1);
    $this->assertDatabaseCount('identity_operators', 0);
    $this->actingAs($user)->postJson(route('identity.memberships.update'), [
        'party_id' => $party->id, 'role' => 'investor', 'status' => 'active', 'expected_revision' => 0,
        'evidence_reference' => 'case:test', 'reason' => 'Not authorized.', 'request_id' => (string) Str::uuid(),
    ])->assertForbidden()->assertJsonPath('code', 'IDENTITY_OPERATOR_REQUIRED');
    expect(Artisan::call('identity:staff', ['user' => $user->id, '--reason' => 'Confirmed access.']))->toBe(0);
    expect(fn () => $action->handle($user->id, false, 'Different command.', $id))->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED');
});

it('rechecks staff separation email MFA and enabled state on every entry', function (string $failure): void {
    $user = User::factory()->withTwoFactor()->create();
    $staff = StaffAccount::factory()->create(['user_id' => $user->id]);
    match ($failure) {
        'party' => $user->forceFill(['party_id' => Party::factory()->create()->id])->save(),
        'email' => $user->forceFill(['email_verified_at' => null])->save(),
        'future email' => $user->forceFill(['email_verified_at' => now()->addDay()])->save(),
        'mfa' => $user->forceFill(['two_factor_secret' => null])->save(),
        'unconfirmed' => $user->forceFill(['two_factor_confirmed_at' => null])->save(),
        'future mfa' => $user->forceFill(['two_factor_confirmed_at' => now()->addDay()])->save(),
        'disabled feature' => config(['fortify.features' => []]),
        'revoked' => $staff->forceFill(['enabled' => false])->save(),
        default => throw new LogicException('Unknown scenario.'),
    };
    $this->actingAs($user)->getJson(route('api.v1.staff-access.show'))->assertJsonPath('data.can_open_admin', false);
    $this->getJson(route('admin.home'))->assertForbidden();
    app(ConfigureStaffAccess::class)->handle($user->id, false, 'Revoke despite missing prerequisites.', (string) Str::uuid());
    expect($staff->refresh()->enabled)->toBeFalse();
})->with(['party', 'email', 'future email', 'mfa', 'unconfirmed', 'future mfa', 'disabled feature', 'revoked']);

it('refuses converting accounts with established or shared participant authority', function (string $kind): void {
    $party = $kind === 'verified' ? Party::factory()->verified()->create() : Party::factory()->create();
    $user = User::factory()->withTwoFactor()->for($party)->create();
    match ($kind) {
        'organization' => $party->forceFill(['kind' => 'organization'])->save(),
        'membership' => RoleMembership::factory()->for($party)->create(),
        'shared' => User::factory()->for($party)->create(),
        'verified' => null,
        default => throw new LogicException('Unknown scenario.'),
    };
    expect(fn () => app(ConfigureStaffAccess::class)->handle($user->id, true, 'Invalid conversion.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'DEDICATED_STAFF_ACCOUNT_REQUIRED');
    $this->assertDatabaseCount('staff_accounts', 0);
    $this->assertDatabaseCount('identity_audit_events', 0);
})->with(['verified', 'organization', 'membership', 'shared']);

it('requires verified MFA for provisioning and does not create records on invalid revocation', function (): void {
    $user = User::factory()->create();
    expect(Artisan::call('identity:staff', ['user' => $user->id, '--reason' => 'Missing MFA.']))->toBe(1);
    expect(fn () => app(ConfigureStaffAccess::class)->handle($user->id, false, 'Nothing to revoke.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_ACCOUNT_NOT_FOUND');
    $this->assertDatabaseCount('staff_accounts', 0);
});

it('keeps disabled staff accounts out of participant identity resolution', function (): void {
    $operator = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $operator->id]);
    $staff = StaffAccount::factory()->create(['enabled' => false]);
    expect(fn () => app(ResolveVerifiedPerson::class)->handle($operator->id, $staff->user_id, 'provider:staff', 'case:staff', 'Separate account required.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'VERIFIED_PARTICIPANT_ACCOUNT_REQUIRED');
});

it('requires authentication and staff token scope without treating scope as a grant', function (): void {
    $this->getJson(route('api.v1.staff-access.show'))->assertUnauthorized();
    $user = User::factory()->withTwoFactor()->create();
    Sanctum::actingAs($user, ['identity:access']);
    $this->getJson(route('api.v1.staff-access.show'))->assertForbidden();
    Sanctum::actingAs($user, ['staff:access']);
    $this->getJson(route('api.v1.staff-access.show'))->assertJsonPath('data.can_open_admin', false);
    StaffAccount::factory()->create(['user_id' => $user->id]);
    $this->getJson(route('api.v1.staff-access.show'))->assertJsonPath('data.can_open_admin', true);
});

it('rolls back staff grants if immutable audit persistence fails', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    IdentityAuditEvent::creating(fn () => throw new RuntimeException('Audit unavailable.'));
    try {
        expect(fn () => app(ConfigureStaffAccess::class)->handle($user->id, true, 'Audit must succeed.', (string) Str::uuid()))->toThrow(RuntimeException::class);
        $this->assertDatabaseCount('staff_accounts', 0);
        expect($user->refresh()->context_revision)->toBe(0);
    } finally {
        IdentityAuditEvent::flushEventListeners();
    }
});
