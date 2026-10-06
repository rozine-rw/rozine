<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\ResolveVerifiedOrganization;
use App\Application\Identity\WithVerifiedParties;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Support\Str;

it('resolves one canonical registry organization and audits attributable staff evidence', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Approved verifier.', (string) Str::uuid(), ['compliance']);
    $resolve = app(ResolveVerifiedOrganization::class);
    $request = (string) Str::uuid();
    $one = $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:registry-check', 'Registry checked.', $request);
    expect($resolve->handle($staff->id, 'rdb:company-001', 'fixture:registry-check', 'Registry checked.', $request))->toBe($one);
    $two = $resolve->handle($staff->id, 'RDB:COMPANY-001', 'fixture:second-check', 'Registry rechecked.', (string) Str::uuid());
    expect($two['party_id'])->toBe($one['party_id'])->and($one['contract_version'])->toBe('identity-organization-v1')
        ->and(Party::query()->where('kind', 'organization')->count())->toBe(1)
        ->and(VerifiedOrganizationIdentity::query()->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'organization.resolve')->count())->toBe(2)
        ->and($staff->refresh()->party_id)->toBeNull();
    $this->assertDatabaseHas('parties', ['id' => $one['party_id'], 'kind' => 'organization']);
    $identity = VerifiedOrganizationIdentity::query()->firstOrFail();
    expect($identity->toArray())->not->toHaveKey('registry_digest')->not->toHaveKey('evidence_reference');
    expect(fn () => $resolve->handle($staff->id, 'rdb:COMPANY-002', 'fixture:registry-check', 'Registry checked.', $request))
        ->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED');
});

it('requires current scoped staff authority even for a replay', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    $configure = app(ConfigureStaffAccess::class);
    $configure->handle($staff->id, true, 'Approver access.', (string) Str::uuid(), ['approver']);
    $request = (string) Str::uuid();
    $resolve = app(ResolveVerifiedOrganization::class);
    $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:registry', 'Verified registry.', $request);
    $configure->handle($staff->id, true, 'Read only now.', (string) Str::uuid(), ['analyst']);
    expect(fn () => $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:registry', 'Verified registry.', $request))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('accepts only an RDB registry namespace with evidence and a recorded reason', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Verifier.', (string) Str::uuid(), ['compliance']);
    $resolve = app(ResolveVerifiedOrganization::class);
    foreach (['tin:123456789', '123456789', 'rdb:', 'rdb:bad/code'] as $reference) {
        expect(fn () => $resolve->handle($staff->id, $reference, 'fixture:registry', 'Verified.', (string) Str::uuid()))
            ->toThrow(IdentityViolation::class, 'REGISTRY_REFERENCE_INVALID');
    }
    expect(fn () => $resolve->handle($staff->id, 'rdb:COMPANY-001', '', 'Verified.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'IDENTITY_EVIDENCE_REQUIRED');
    expect(fn () => $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:registry', '', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'IDENTITY_COMMAND_INVALID');
    $this->assertDatabaseCount('verified_organization_identities', 0);
});

it('cannot silently reactivate a revoked or inconsistent organization identity', function (string $failure): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Verifier.', (string) Str::uuid(), ['compliance']);
    $resolve = app(ResolveVerifiedOrganization::class);
    $result = $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:registry', 'Verified.', (string) Str::uuid());
    $party = Party::query()->whereKey($result['party_id'])->firstOrFail();
    $party->forceFill(match ($failure) {
        'revoked' => ['verified_at' => null],
        'future' => ['verified_at' => now()->addDay()],
        default => ['kind' => 'person'],
    })->save();
    expect(fn () => $resolve->handle($staff->id, 'rdb:COMPANY-001', 'fixture:new', 'Recheck.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'ORGANIZATION_VERIFICATION_REQUIRED');
})->with(['revoked', 'future', 'wrong kind']);

it('verifies a sole trader and a company with its actual required people without imposing two directors', function (): void {
    $person = Party::factory()->verified()->create();
    $another = Party::factory()->verified()->create();
    $organization = VerifiedOrganizationIdentity::factory()->create();
    $verify = app(WithVerifiedParties::class);
    expect($verify->handle('person', $person->id, [$person->id], fn (): string => 'sole trader'))->toBe('sole trader')
        ->and($verify->handle('organization', $organization->party_id, [$person->id], fn (): string => 'one signatory'))->toBe('one signatory')
        ->and($verify->handle('organization', $organization->party_id, [$person->id, $another->id, $person->id], fn (): string => 'verified set'))->toBe('verified set');
});

it('refuses missing, unverified, future-dated or wrongly typed mandate parties', function (): void {
    $person = Party::factory()->verified()->create();
    $organization = VerifiedOrganizationIdentity::factory()->create();
    $verify = app(WithVerifiedParties::class);
    foreach ([['unknown', $person->id, [$person->id]], ['person', $person->id, []], ['person', $person->id, [(string) Str::ulid()]], ['organization', (string) Str::ulid(), [$person->id]]] as [$kind, $entity, $people]) {
        expect(fn () => $verify->handle($kind, $entity, $people, fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    }
    $missingIdentity = Party::factory()->create(['verified_at' => now()]);
    expect(fn () => $verify->handle('organization', $organization->party_id, [$missingIdentity->id], fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $missingRegistry = Party::factory()->create(['kind' => 'organization', 'verified_at' => now()]);
    expect(fn () => $verify->handle('organization', $missingRegistry->id, [$person->id], fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    foreach ([null, now()->addDay()] as $verifiedAt) {
        $person->forceFill(['verified_at' => $verifiedAt])->save();
        expect(fn () => $verify->handle('organization', $organization->party_id, [$person->id], fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    }
    $person->forceFill(['verified_at' => now(), 'kind' => 'organization'])->save();
    expect(fn () => $verify->handle('organization', $organization->party_id, [$person->id], fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
});

it('keeps downstream writes inside the verified-party transaction', function (): void {
    $person = Party::factory()->verified()->create();
    expect(fn () => app(WithVerifiedParties::class)->handle('person', $person->id, [$person->id], function () use ($person): never {
        $person->forceFill(['verified_at' => null])->save();
        throw new RuntimeException('Abort dependent command.');
    }))->toThrow(RuntimeException::class, 'Abort dependent command.');
    expect($person->refresh()->verified_at)->not->toBeNull();
});
