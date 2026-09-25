<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GetStaffAccess;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

it('derives checkpoint two permissions from explicit staff roles without any override', function (string $role, bool $canVerify, bool $canApprove, bool $canAccredit): void {
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Checkpoint two staff assignment.', (string) Str::uuid(), [$role]);
    $access = app(GetStaffAccess::class)->handle($user->id);
    expect($access['allowed_actions'])->toContain('admin.open', 'businesses.view', 'audit.reports.view')
        ->and(in_array('businesses.verify', $access['allowed_actions'], true))->toBe($canVerify)
        ->and(in_array('applications.review', $access['allowed_actions'], true))->toBe($canApprove)
        ->and(in_array('audit.partners.verify', $access['allowed_actions'], true))->toBe($canAccredit)
        ->and($access['allowed_actions'])->not->toContain('underwriting.override', '*');
})->with([
    'analyst' => ['analyst', false, false, false],
    'approver' => ['approver', true, true, true],
    'treasury' => ['treasury', false, false, false],
    'compliance' => ['compliance', true, false, true],
    'superadmin' => ['superadmin', true, true, true],
]);

it('denies ungranted staff operations and rechecks permission and MFA after revocation', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $configure = app(ConfigureStaffAccess::class);
    $authorize = app(AuthorizeStaffPermission::class);
    $configure->handle($user->id, true, 'Entry only.', (string) Str::uuid());
    expect(fn () => $authorize->handle($user->id, 'applications.review', fn (): string => 'should not run'))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    $configure->handle($user->id, true, 'Scoped approver.', (string) Str::uuid(), ['approver']);
    expect($authorize->handle($user->id, 'applications.review', fn (): string => 'authorized'))->toBe('authorized');
    $configure->handle($user->id, true, 'Downgrade to analyst.', (string) Str::uuid(), ['analyst']);
    expect(fn () => $authorize->handle($user->id, 'applications.review', fn (): string => 'should not run'))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => $authorize->handle($user->id, 'businesses.view', fn (): string => 'should not run'))
        ->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
});

it('audits normalized role changes and binds replay identity to the chosen roles', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $configure = app(ConfigureStaffAccess::class);
    $key = (string) Str::uuid();
    $configure->handle($user->id, true, 'Joint operational responsibilities.', $key, ['approver', 'analyst', 'analyst']);
    $configure->handle($user->id, true, 'Joint operational responsibilities.', $key, ['analyst', 'approver']);
    expect(StaffAccount::query()->findOrFail($user->id)->roles)->toBe(['analyst', 'approver']);
    $this->assertDatabaseCount('identity_audit_events', 1);
    expect(IdentityAuditEvent::query()->firstOrFail()->after['roles'])->toBe(['analyst', 'approver']);
    expect(fn () => $configure->handle($user->id, true, 'Joint operational responsibilities.', $key, ['superadmin']))
        ->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED');
    expect(fn () => $configure->handle($user->id, true, 'Unknown role.', (string) Str::uuid(), ['root']))
        ->toThrow(IdentityViolation::class, 'STAFF_ROLE_INVALID');
});

it('accepts explicit roles only through the trusted staff bootstrap', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    expect(Artisan::call('identity:staff', ['user' => $user->id, '--role' => ['compliance'], '--reason' => 'Approved compliance assignment.']))->toBe(0)
        ->and(StaffAccount::query()->findOrFail($user->id)->roles)->toBe(['compliance']);
    $this->actingAs($user)->getJson(route('api.v1.staff-access.show'))
        ->assertOk()->assertJsonPath('data.allowed_actions.0', 'admin.open');
});

it('rolls back the protected operation while keeping the staff authority transaction boundary', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Scoped access.', (string) Str::uuid(), ['approver']);
    expect(fn () => app(AuthorizeStaffPermission::class)->handle($user->id, 'applications.review', function () use ($user): never {
        $user->forceFill(['name' => 'Must roll back'])->save();
        throw new RuntimeException('Operation failed.');
    }))->toThrow(RuntimeException::class, 'Operation failed.');
    expect($user->refresh()->name)->not->toBe('Must roll back');
});
