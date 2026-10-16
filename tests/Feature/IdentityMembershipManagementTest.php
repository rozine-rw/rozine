<?php

declare(strict_types=1);

use App\Application\Identity\ChangeMembership;
use App\Application\Identity\ConfigureIdentityOperator;
use App\Application\Identity\ResolveVerifiedPerson;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function membershipOperator(): User
{
    $user = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $user->id]);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function membershipCommand(Party $party, array $overrides = []): array
{
    return array_replace([
        'party_id' => $party->id, 'role' => 'investor', 'status' => 'active', 'expected_revision' => 0,
        'evidence_reference' => 'review:alpha-1', 'reason' => 'Eligibility reviewed.', 'request_id' => (string) Str::uuid(),
    ], $overrides);
}

it('provisions the same authorized membership through web and API with one audit event on retry', function (): void {
    $operator = membershipOperator();
    $party = Party::factory()->verified()->create();
    $command = membershipCommand($party);

    $result = $this->actingAs($operator)->postJson(route('identity.memberships.update'), $command)->assertOk()->json();
    $this->postJson(route('api.v1.identity.memberships.update'), $command)->assertOk()->assertExactJson($result);

    expect(RoleMembership::query()->sole()->status)->toBe('active');
    $event = IdentityAuditEvent::query()->sole();
    expect($event->actor_user_id)->toBe($operator->id)
        ->and($event->reason)->toBe($command['reason'])
        ->and($event->before)->toBe([])
        ->and($event->after['revision'])->toBe(1)
        ->and($event->policy_version)->toBe('engineering-2026-09-23.4');
});

it('denies self provisioning and ignores a supplied staff actor', function (string $route): void {
    $party = Party::factory()->verified()->create();
    $participant = User::factory()->for($party)->withTwoFactor()->create();
    $operator = membershipOperator();

    $this->actingAs($participant)->postJson(route($route), membershipCommand($party, ['actor_id' => $operator->id]))
        ->assertForbidden()->assertJsonPath('code', 'IDENTITY_OPERATOR_REQUIRED');
    $this->assertDatabaseCount('role_memberships', 0);
    $this->assertDatabaseCount('identity_audit_events', 0);
})->with(['identity.memberships.update', 'api.v1.identity.memberships.update']);

it('requires current operator status email verification MFA and a separate account', function (string $failure): void {
    $operator = membershipOperator();
    $party = Party::factory()->verified()->create();
    match ($failure) {
        'disabled' => IdentityOperator::query()->whereKey($operator->id)->update(['enabled' => false]),
        'email' => $operator->forceFill(['email_verified_at' => null])->save(),
        'mfa' => $operator->forceFill(['two_factor_confirmed_at' => null])->save(),
        'future mfa' => $operator->forceFill(['two_factor_confirmed_at' => now()->addDay()])->save(),
        'disabled mfa feature' => config(['fortify.features' => []]),
        'participant' => $operator->forceFill(['party_id' => $party->id])->save(),
        default => throw new LogicException('Unknown test scenario.'),
    };

    $this->actingAs($operator)->postJson(route('identity.memberships.update'), membershipCommand($party))
        ->assertForbidden()->assertJsonPath('code', 'IDENTITY_OPERATOR_REQUIRED');
})->with(['disabled', 'email', 'mfa', 'future mfa', 'disabled mfa feature', 'participant']);

it('requires a management ability on bearer tokens in addition to staff authority', function (): void {
    $operator = membershipOperator();
    $party = Party::factory()->verified()->create();
    Sanctum::actingAs($operator, ['identity:read']);
    $this->postJson(route('api.v1.identity.memberships.update'), membershipCommand($party))->assertForbidden();
    $this->postJson(route('api.v1.identity.people.resolve'), [])->assertForbidden();
    Sanctum::actingAs($operator, ['identity:manage']);
    $this->postJson(route('api.v1.identity.memberships.update'), membershipCommand($party))->assertOk();
});

it('does not provision an unverified unresolved or organization Party', function (string $kind, bool $verified, string $code): void {
    $party = Party::factory()->create(['kind' => $kind, 'verified_at' => $verified ? now() : null]);
    $this->actingAs(membershipOperator())->postJson(route('identity.memberships.update'), membershipCommand($party))
        ->assertForbidden()->assertJsonPath('code', $code);
})->with([
    'unverified' => ['person', false, 'IDENTITY_VERIFICATION_REQUIRED'],
    'unresolved' => ['person', true, 'IDENTITY_VERIFICATION_REQUIRED'],
    'entity' => ['organization', true, 'PARTY_AUTHORITY_REQUIRED'],
]);

it('supports a controlled pending activation suspension and revocation lifecycle', function (): void {
    $party = Party::factory()->verified()->create();
    $this->actingAs(membershipOperator());
    foreach (['pending', 'active', 'suspended', 'active', 'revoked'] as $revision => $status) {
        $this->postJson(route('identity.memberships.update'), membershipCommand($party, [
            'status' => $status, 'expected_revision' => $revision,
        ]))->assertOk()->assertJsonPath('data.membership.revision', $revision + 1);
    }
    $this->postJson(route('identity.memberships.update'), membershipCommand($party, ['expected_revision' => 5]))
        ->assertConflict()->assertJsonPath('code', 'MEMBERSHIP_TRANSITION_DENIED');
    $this->assertDatabaseCount('identity_audit_events', 5);
});

it('rejects stale revisions conflicting role grants and reused keys without changing state', function (): void {
    $party = Party::factory()->verified()->create();
    $command = membershipCommand($party);
    $this->actingAs(membershipOperator())->postJson(route('identity.memberships.update'), $command)->assertOk();
    $this->postJson(route('identity.memberships.update'), membershipCommand($party))
        ->assertConflict()->assertJsonPath('code', 'MEMBERSHIP_REVISION_CONFLICT');
    $this->postJson(route('identity.memberships.update'), membershipCommand($party, ['role' => 'auditor']))
        ->assertConflict()->assertJsonPath('code', 'ROLE_MEMBERSHIP_CONFLICT');
    $this->postJson(route('identity.memberships.update'), array_replace($command, ['reason' => 'Changed payload.']))
        ->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
    $this->assertDatabaseCount('role_memberships', 1);
    $this->assertDatabaseCount('identity_audit_events', 1);
});

it('validates membership fields before applying a command', function (string $field, mixed $value): void {
    $party = Party::factory()->verified()->create();
    $this->actingAs(membershipOperator())->postJson(route('identity.memberships.update'), membershipCommand($party, [$field => $value]))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('role_memberships', 0);
})->with([
    'role' => ['role', 'admin'], 'status' => ['status', 'approved'], 'reason' => ['reason', '   '],
    'evidence' => ['evidence_reference', ''], 'revision' => ['expected_revision', -1], 'request' => ['request_id', 'not-a-uuid'],
]);

it('links separately registered logins to the same verified person without exposing identity evidence', function (): void {
    $operator = membershipOperator();
    $one = User::factory()->for(Party::factory())->create();
    $two = User::factory()->for(Party::factory())->create();
    $payload = ['user_id' => $one->id, 'identity_reference' => 'provider:canonical-person-1',
        'evidence_reference' => 'case:verified-1', 'reason' => 'Verified evidence matches this person.', 'request_id' => (string) Str::uuid()];
    $first = $this->actingAs($operator)->postJson(route('identity.people.resolve'), $payload)->assertOk()->json();
    $this->postJson(route('api.v1.identity.people.resolve'), $payload)->assertOk()->assertExactJson($first);
    $this->postJson(route('identity.people.resolve'), array_replace($payload, ['user_id' => $two->id, 'request_id' => (string) Str::uuid()]))->assertOk();
    expect($one->refresh()->party_id)->toBe($two->refresh()->party_id);
    $party = $one->party()->sole();
    $this->postJson(route('identity.memberships.update'), membershipCommand($party))->assertOk();
    $this->postJson(route('identity.memberships.update'), membershipCommand($party, ['role' => 'auditor']))->assertConflict();
    $this->actingAs($two)->getJson(route('api.v1.identity.show'))->assertOk()
        ->assertJsonPath('data.available_roles', ['investor'])->assertJsonMissingPath('data.identity_digest')
        ->assertJsonMissingPath('data.evidence_reference');
    $this->assertDatabaseCount('verified_person_identities', 1);
    expect(IdentityAuditEvent::query()->where('action', 'person.resolve')->count())->toBe(2);
});

it('can resolve a legacy account but never treats email verification as identity evidence', function (): void {
    $user = User::factory()->create();
    $result = app(ResolveVerifiedPerson::class)->handle(membershipOperator()->id, $user->id,
        'provider:legacy-person', 'case:reviewed', 'Evidence checked.', (string) Str::uuid());
    expect($user->refresh()->party_id)->toBe($result['party_id']);
    $this->assertDatabaseCount('role_memberships', 0);
});

it('refuses to merge existing identity or membership histories into another person', function (bool $withMembership): void {
    $party = $withMembership ? Party::factory()->create() : Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    if ($withMembership) {
        RoleMembership::factory()->for($party)->create();
    }
    $this->actingAs(membershipOperator())->postJson(route('identity.people.resolve'), [
        'user_id' => $user->id, 'identity_reference' => 'provider:another-person', 'evidence_reference' => 'case:review',
        'reason' => 'Must be reconciled.', 'request_id' => (string) Str::uuid(),
    ])->assertConflict()->assertJsonPath('code', 'IDENTITY_RECONCILIATION_REQUIRED');
    expect($user->refresh()->party_id)->toBe($party->id);
})->with([true, false]);

it('refuses resolution for an unverified email staff account or missing user', function (string $case): void {
    $operator = membershipOperator();
    $user = $case === 'staff' ? $operator : User::factory()->unverified()->create();
    $this->actingAs($operator)->postJson(route('identity.people.resolve'), [
        'user_id' => $case === 'missing' ? 999999 : $user->id, 'identity_reference' => 'provider:person',
        'evidence_reference' => 'case:review', 'reason' => 'Identity checked.', 'request_id' => (string) Str::uuid(),
    ])->assertStatus($case === 'missing' ? 404 : 403);
})->with(['email', 'staff', 'missing']);

it('does not restore verification when linking another login to a revoked identity', function (): void {
    $operator = membershipOperator();
    $one = User::factory()->create();
    $two = User::factory()->create();
    app(ResolveVerifiedPerson::class)->handle($operator->id, $one->id, 'provider:revoked', 'case:initial', 'Evidence checked.', (string) Str::uuid());
    $one->refresh()->party()->update(['verified_at' => null]);
    expect(fn () => app(ResolveVerifiedPerson::class)->handle($operator->id, $two->id, 'provider:revoked', 'case:retry', 'Relink request.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
    expect($two->refresh()->party_id)->toBeNull();
});

it('rolls back a membership change if its audit record cannot be written', function (): void {
    $operator = membershipOperator();
    $party = Party::factory()->verified()->create();
    IdentityAuditEvent::creating(function (): never {
        throw new RuntimeException('audit unavailable');
    });
    try {
        expect(fn () => app(ChangeMembership::class)->handle($operator->id, $party->id, 'investor', 'active', 0,
            'case:review', 'Evidence checked.', (string) Str::uuid()))->toThrow(RuntimeException::class, 'audit unavailable');
    } finally {
        IdentityAuditEvent::flushEventListeners();
    }
    $this->assertDatabaseCount('role_memberships', 0);
});

it('protects the audit history from update and deletion at the database boundary', function (string $mutation): void {
    $event = IdentityAuditEvent::factory()->create();
    expect(fn () => DB::transaction(function () use ($event, $mutation): void {
        if ($mutation === 'update') {
            $event->forceFill(['reason' => 'Rewritten'])->save();
        } else {
            $event->delete();
        }
    }))->toThrow(QueryException::class);
    expect($event->fresh()->reason)->toBe('Synthetic test event');
})->with(['update', 'delete']);

it('bootstraps and revokes only a dedicated verified MFA staff account through the console', function (): void {
    $user = User::factory()->for(Party::factory())->withTwoFactor()->create();
    expect(Artisan::call('identity:operator', ['user' => $user->id, '--reason' => 'Authorized staff onboarding.']))->toBe(0);
    expect($user->refresh()->party_id)->toBeNull()
        ->and(IdentityOperator::query()->findOrFail($user->id)->enabled)->toBeTrue();
    expect(Artisan::call('identity:operator', ['user' => $user->id, '--revoke' => true, '--reason' => 'Access withdrawn.']))->toBe(0);
    expect(IdentityOperator::query()->findOrFail($user->id)->enabled)->toBeFalse()
        ->and(IdentityAuditEvent::query()->where('actor_key', 'console')->count())->toBe(2);
});

it('rejects console privilege grants without MFA reason or a dedicated account', function (string $case): void {
    $user = User::factory()->withTwoFactor()->create();
    if ($case === 'mfa') {
        $user->forceFill(['two_factor_confirmed_at' => null])->save();
    }
    if ($case === 'participant') {
        $user->forceFill(['party_id' => Party::factory()->verified()->create()->id])->save();
    }
    expect(Artisan::call('identity:operator', ['user' => $user->id, '--reason' => $case === 'reason' ? '' : 'Staff onboarding.']))->toBe(1);
    $this->assertDatabaseCount('identity_operators', 0);
})->with(['mfa', 'reason', 'participant']);

it('replays console commands once and rejects a changed body', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $requestId = (string) Str::uuid();
    $action = app(ConfigureIdentityOperator::class);
    $first = $action->handle($user->id, true, 'Staff onboarding.', $requestId);
    expect($action->handle($user->id, true, 'Staff onboarding.', $requestId))->toBe($first);
    expect(fn () => $action->handle($user->id, false, 'Staff onboarding.', $requestId))->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED');
    $this->assertDatabaseCount('identity_audit_events', 1);
});

it('rejects invalid trusted identity references and missing evidence even outside HTTP validation', function (string $reference, string $evidence, string $code): void {
    $user = User::factory()->create();
    $operator = membershipOperator();
    expect(fn () => app(ResolveVerifiedPerson::class)->handle($operator->id, $user->id, $reference, $evidence, 'Review recorded.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, $code);
    expect($user->refresh()->party_id)->toBeNull();
    $this->assertDatabaseCount('identity_audit_events', 0);
})->with([
    ['not a provider reference', 'case:review', 'IDENTITY_REFERENCE_INVALID'],
    [str_repeat('p', 255).':person', 'case:review', 'IDENTITY_REFERENCE_INVALID'],
    ['provider:person', '   ', 'IDENTITY_EVIDENCE_REQUIRED'],
    ['provider:person', str_repeat('x', 256), 'IDENTITY_EVIDENCE_REQUIRED'],
]);

it('returns not found for a missing Party without leaving an audit event or membership', function (): void {
    $operator = membershipOperator();
    $party = Party::factory()->make();
    $party->id = (string) Str::ulid();
    $this->actingAs($operator)->postJson(route('identity.memberships.update'), membershipCommand($party))
        ->assertNotFound()->assertJsonPath('code', 'IDENTITY_RECORD_NOT_FOUND');
    $this->assertDatabaseCount('role_memberships', 0);
    $this->assertDatabaseCount('identity_audit_events', 0);
});

it('rechecks operator authority before returning a previously successful command receipt', function (): void {
    $operator = membershipOperator();
    $party = Party::factory()->verified()->create();
    $payload = membershipCommand($party);
    $this->actingAs($operator)->postJson(route('identity.memberships.update'), $payload)->assertOk();
    app(ConfigureIdentityOperator::class)->handle($operator->id, false, 'Staff authority withdrawn.', (string) Str::uuid());
    $this->postJson(route('identity.memberships.update'), $payload)->assertForbidden()->assertJsonPath('code', 'IDENTITY_OPERATOR_REQUIRED');
    $this->assertDatabaseCount('identity_audit_events', 2);
});

it('does not turn a participant into a staff account when revoking a nonexistent operator grant', function (): void {
    $user = User::factory()->for(Party::factory()->verified())->create();
    expect(fn () => app(ConfigureIdentityOperator::class)->handle($user->id, false, 'Mistaken staff identifier.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'IDENTITY_OPERATOR_NOT_FOUND');
    $this->assertDatabaseCount('identity_operators', 0);
    $this->assertDatabaseCount('identity_audit_events', 0);
});
