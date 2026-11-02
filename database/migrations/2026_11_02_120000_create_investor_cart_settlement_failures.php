<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stuck whole-cart expiries (#383 step 4c). A held checkout whose group expiry failed is recorded
 * here until a later sweep or a staff retry settles it; it is operational state, not evidence, so it
 * holds no foreign key and its row is deleted once the checkout settles.
 *
 * `cart.releases.resolve` lets Treasury, Compliance and superadmin retry a stuck expiry from the
 * console, and names who is alerted (Erastus, 1 Nov 2026: whoever holds an admin role).
 */
return new class extends Migration
{
    private const array ROLES = ['treasury', 'compliance', 'superadmin'];

    public function up(): void
    {
        Schema::create('investor_cart_settlement_failures', function (Blueprint $table): void {
            $table->ulid('investor_cart_checkout_id')->primary();
            $table->timestampTz('first_failed_at', 6);
            $table->timestampTz('last_attempted_at', 6)->index();
            $table->unsignedInteger('attempts');
            $table->string('reason_code', 100);
            $table->string('exception_class', 200);
        });
        DB::transaction(function (): void {
            $permission = DB::table('permissions')->insertGetId(['name' => 'cart.releases.resolve', 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
            foreach (DB::table('roles')->where('guard_name', 'staff')->whereIn('name', self::ROLES)->orderBy('id')->pluck('id') as $role) {
                DB::table('role_has_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $permission = DB::table('permissions')->where('name', 'cart.releases.resolve')->where('guard_name', 'staff')->value('id');
            DB::table('role_has_permissions')->where('permission_id', $permission)->delete();
            DB::table('permissions')->where('id', $permission)->delete();
        });
        Schema::drop('investor_cart_settlement_failures');
    }
};
