<?php

declare(strict_types=1);

use App\Application\Identity\ApproveStaffAccess;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\GrantStaffAccess;
use App\Application\Identity\StaffAccessRefused;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Tests\Support\StaffFixture;

const ORIGINAL_STAFF_PERMISSIONS = [
    'analyst' => ['businesses.view', 'audit.reports.view', 'primary.view'],
    'approver' => ['businesses.view', 'businesses.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view',
        'disbursements.view', 'disbursements.hold', 'primary.view'],
    'treasury' => ['businesses.view', 'audit.reports.view', 'ledger.view', 'disbursements.view', 'disbursements.hold', 'disbursements.requery', 'reconciliation.view', 'exceptions.view', 'primary.view', 'payments.view', 'health.view'],
    'compliance' => ['businesses.view', 'businesses.verify', 'investors.verify', 'audit.partners.verify', 'audit.reports.view', 'consent.documents.record', 'ledger.view',
        'disbursements.view', 'disbursements.hold', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
    // No superadmin bypass (§10.6, §11.1): disbursements are view-only here, never held or released.
    'superadmin' => ['businesses.view', 'businesses.verify', 'investors.verify', 'applications.review', 'audit.partners.verify', 'audit.assignments.manage', 'audit.reports.view', 'consent.documents.record', 'ledger.view',
        'disbursements.view', 'staging.mail.testers.manage', 'staff.manage', 'reconciliation.view', 'exceptions.view', 'messaging.view', 'primary.view', 'payments.view', 'complaints.answer', 'health.view'],
];

it('preserves the complete existing permission matrix in Spatie', function (string $role): void {
    expect(StaffFixture::permissions([$role]))->toEqualCanonicalizing(['admin.open', ...ORIGINAL_STAFF_PERMISSIONS[$role]]);
})->with(['analyst', 'approver', 'treasury', 'compliance', 'superadmin']);

it('migrates existing enabled disabled and entry-only accounts and restores assignments on rollback', function (): void {
    $schema = require database_path('migrations/2026_10_29_053409_create_staff_permission_tables.php');
    $data = require database_path('migrations/2026_10_29_053410_migrate_staff_roles_to_permissions.php');
    $data->down();
    $schema->down();
    $users = User::factory()->withTwoFactor()->count(3)->create();
    foreach ($users as $index => $user) {
        DB::table('staff_accounts')->insert(['user_id' => $user->id, 'enabled' => $index !== 1,
            'roles' => json_encode([['approver', 'compliance'], ['analyst', 'superadmin'], []][$index], JSON_THROW_ON_ERROR)]);
    }
    $event = IdentityAuditEvent::factory()->create(['target_id' => (string) $users[0]->id,
        'after' => ['roles' => ['approver', 'compliance'], 'enabled' => true]]);
    $history = $event->fresh()->getRawOriginal();
    $schema->up();
    $data->up();
    expect(Schema::hasColumn('staff_accounts', 'roles'))->toBeFalse()
        ->and(StaffAccount::query()->findOrFail($users[0]->id)->roleNames())->toBe(['approver', 'compliance'])
        ->and(StaffAccount::query()->findOrFail($users[1]->id)->roleNames())->toBe(['analyst', 'superadmin'])
        ->and(app(GetStaffAccess::class)->handle($users[1]->id)['allowed_actions'])->toBe([])
        ->and(app(GetStaffAccess::class)->handle($users[2]->id)['allowed_actions'])->toBe(['admin.open'])
        ->and($event->fresh()->getRawOriginal())->toBe($history);
    $data->down();
    expect(json_decode(DB::table('staff_accounts')->where('user_id', $users[1]->id)->value('roles'), true))->toBe(['analyst', 'superadmin']);
    $schema->down();
    $schema->up();
    $data->up();
    expect(StaffAccount::query()->findOrFail($users[0]->id)->roleNames())->toBe(['approver', 'compliance']);
});

it('uses fresh Spatie assignments while retaining staff eligibility and rejecting direct grants as authority', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Original role.', (string) Str::uuid(), ['approver']);
    $staff = StaffAccount::query()->findOrFail($user->id);
    $staff->load('roles.permissions');
    $staff->givePermissionTo(Permission::findByName('staff.manage', 'staff'));
    expect(app(GetStaffAccess::class)->handle($user->id)['allowed_actions'])->not->toContain('staff.manage');
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Revoke review authority.', (string) Str::uuid(), ['analyst']);
    expect($staff->allowedActions())->not->toContain('applications.review', 'staff.manage');
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => app(AuthorizeStaffPermission::class)->handle($user->id, 'businesses.view', fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
});

it('binds Spatie assignments to staff accounts rather than marketplace users', function (): void {
    $user = User::factory()->create();
    expect(method_exists($user, 'assignRole'))->toBeFalse();
    $roleId = DB::table('roles')->where('name', 'analyst')->where('guard_name', 'staff')->value('id');
    DB::statement('SAVEPOINT invalid_staff_assignment');
    try {
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id]);
        $this->fail('A participant cannot acquire a staff assignment.');
    } catch (QueryException $exception) {
        expect($exception->getCode())->toBeIn(['23503', '23514']);
    } finally {
        DB::statement('ROLLBACK TO SAVEPOINT invalid_staff_assignment');
    }
});

it('retains staff assignments when account deletion is refused', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, ['approver'], 'Retained operator.');
    $staff = StaffAccount::query()->findOrFail($user->id);
    expect(fn () => $staff->delete())->toThrow(IdentityViolation::class, 'STAFF_ACCOUNT_RETAINED');
    expect($staff->fresh()->roleNames())->toBe(['approver'])
        ->and(app(GetStaffAccess::class)->handle($user->id)['allowed_actions'])->toContain('applications.review');
});

it('refuses unknown legacy roles atomically without discarding the original assignment', function (): void {
    $data = require database_path('migrations/2026_10_29_053410_migrate_staff_roles_to_permissions.php');
    $data->down();
    $user = User::factory()->withTwoFactor()->create();
    DB::table('staff_accounts')->insert(['user_id' => $user->id, 'enabled' => true, 'roles' => '["unrecognized"]']);
    expect(fn () => $data->up())->toThrow(RuntimeException::class, 'STAFF_ROLE_INVALID');
    expect(Schema::hasColumn('staff_accounts', 'roles'))->toBeTrue()
        ->and(DB::table('roles')->count())->toBe(0)
        ->and(json_decode(DB::table('staff_accounts')->where('user_id', $user->id)->value('roles'), true))->toBe(['unrecognized']);
    DB::table('staff_accounts')->where('user_id', $user->id)->update(['roles' => '["analyst"]']);
    $data->up();
    expect(StaffAccount::query()->findOrFail($user->id)->roleNames())->toBe(['analyst']);
});

it('retains pending superadmin proposals and their independent approval across the migration', function (): void {
    $requester = User::factory()->withTwoFactor()->create();
    $approver = User::factory()->withTwoFactor()->create();
    $recipient = User::factory()->withTwoFactor()->create();
    foreach ([$requester, $approver] as $manager) {
        StaffFixture::provision($manager, ['superadmin'], 'Pre-existing administrator.');
    }
    $proposal = app(GrantStaffAccess::class)->handle($requester->id, $recipient->email, 'superadmin',
        'Pending administrator.', (string) Str::uuid());
    $event = IdentityAuditEvent::query()->whereKey($proposal['proposal_id'])->firstOrFail();
    $original = $event->getRawOriginal();
    $data = require database_path('migrations/2026_10_29_053410_migrate_staff_roles_to_permissions.php');
    $schema = require database_path('migrations/2026_10_29_053409_create_staff_permission_tables.php');
    $data->down();
    $schema->down();
    $schema->up();
    $data->up();
    expect($event->fresh()->getRawOriginal())->toBe($original);
    expect(fn () => app(ApproveStaffAccess::class)->handle($requester->id, $proposal['proposal_id'],
        'Self approval.', (string) Str::uuid()))->toThrow(StaffAccessRefused::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
    $result = app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Independent review.', (string) Str::uuid());
    expect($result['code'])->toBe('STAFF_ACCESS_ENABLED')
        ->and(StaffAccount::query()->findOrFail($recipient->id)->roleNames())->toBe(['superadmin'])
        ->and(IdentityAuditEvent::query()->where('after->proposal_id', $proposal['proposal_id'])->sole()->actor_user_id)->toBe($approver->id);
});

it('refuses package CLI writes that bypass audited grants or superadmin approval', function (string $command, array $arguments): void {
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, ['analyst'], 'Existing analyst.');
    $events = IdentityAuditEvent::query()->count();
    if ($command === 'permission:assign-role') {
        $arguments['userId'] = $user->id;
        $arguments['userModelNamespace'] = StaffAccount::class;
    }
    expect(fn () => Artisan::call($command, $arguments))->toThrow(CommandNotFoundException::class);
    expect(StaffAccount::query()->findOrFail($user->id)->roleNames())->toBe(['analyst'])
        ->and(app(GetStaffAccess::class)->handle($user->id)['allowed_actions'])->not->toContain('staff.manage')
        ->and(IdentityAuditEvent::query()->count())->toBe($events)
        ->and(Permission::query()->where('name', 'arbitrary.override')->exists())->toBeFalse();
})->with([
    'direct superadmin assignment' => ['permission:assign-role', ['name' => 'superadmin', 'guard' => 'staff']],
    'changing an existing role' => ['permission:create-role', ['name' => 'analyst', 'guard' => 'staff', 'permissions' => 'staff.manage']],
    'adding a permission' => ['permission:create-permission', ['name' => 'arbitrary.override', 'guard' => 'staff']],
    'changing the assignment schema' => ['permission:setup-teams', []],
]);

it('allows package cache maintenance without granting staff access', function (): void {
    expect(Artisan::call('permission:show', ['guard' => 'staff']))->toBe(0)
        ->and(Artisan::call('permission:cache-reset'))->toBe(0)
        ->and(StaffAccount::query()->count())->toBe(0);
});
