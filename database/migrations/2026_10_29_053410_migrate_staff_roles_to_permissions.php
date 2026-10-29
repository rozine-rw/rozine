<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLES = [
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

    /** Backfill and cutover are atomic so an old writer cannot change JSON after it is copied. */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE staff_accounts IN ACCESS EXCLUSIVE MODE');
            $permissionIds = [];
            $roleIds = [];
            foreach (self::ROLES as $name => $permissions) {
                $roleId = DB::table('roles')->insertGetId(['name' => $name, 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
                $roleIds[$name] = $roleId;
                foreach (['admin.open', ...$permissions] as $permission) {
                    $permissionIds[$permission] ??= DB::table('permissions')->insertGetId([
                        'name' => $permission, 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionIds[$permission]]);
                }
            }
            foreach (DB::table('staff_accounts')->orderBy('user_id')->cursor() as $staff) {
                $roles = json_decode($staff->roles, true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($roles) || ! array_is_list($roles)) {
                    throw new RuntimeException('STAFF_ROLE_INVALID');
                }
                foreach ($roles as $name) {
                    if (! is_string($name) || ! isset($roleIds[$name])) {
                        throw new RuntimeException('STAFF_ROLE_INVALID');
                    }
                    DB::table('model_has_roles')->insertOrIgnore(['model_type' => 'App\\Models\\StaffAccount', 'model_id' => $staff->user_id, 'role_id' => $roleIds[$name]]);
                }
            }
            Schema::table('staff_accounts', function (Blueprint $table): void {
                $table->dropColumn('roles');
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            Schema::table('staff_accounts', function (Blueprint $table): void {
                $table->jsonb('roles')->default('[]');
            });
            foreach (DB::table('staff_accounts')->orderBy('user_id')->cursor() as $staff) {
                $roles = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_id', $staff->user_id)->where('model_type', 'App\\Models\\StaffAccount')
                    ->where('guard_name', 'staff')->orderBy('name')->pluck('name')->all();
                DB::table('staff_accounts')->where('user_id', $staff->user_id)->update(['roles' => json_encode($roles, JSON_THROW_ON_ERROR)]);
            }
            DB::table('model_has_roles')->delete();
            DB::table('role_has_permissions')->delete();
            DB::table('roles')->delete();
            DB::table('permissions')->delete();
        });
    }
};
