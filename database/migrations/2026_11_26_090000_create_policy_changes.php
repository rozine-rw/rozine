<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Policies the console can change (Erastus, 26 Nov 2026): the per-investor caps of a raise (10% for an
 * individual, 25% for an institution, replacing INV-R01's 50%), the institution's minimum monthly
 * commitment and the day top-up reminders start. A change is proposed on the Policies page and takes
 * effect only once both founders have approved it, each separately; either founder may refuse it.
 *
 * `policy_settings` holds each value in force. `policy_changes` and `policy_change_decisions` are the
 * trail: a decision is never changed or removed, and one key has at most one change waiting.
 *
 * The `founder` staff role holds `policies.approve`; `policies.change` (proposing) is held by founders
 * and Super-admins, and is no longer withheld.
 */
return new class extends Migration
{
    /** @var array<string, int> */
    private const array SETTINGS = ['investor_cap_percent' => 10, 'institution_cap_percent' => 25,
        'institution_min_commitment' => 30000000, 'institution_reminder_day' => 25];

    public function up(): void
    {
        Schema::create('policy_settings', function (Blueprint $table): void {
            $table->string('key', 60)->primary();
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('revision')->default(1);
            $table->timestampTz('updated_at');
        });
        Schema::create('policy_changes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('setting_key', 60);
            $table->foreign('setting_key')->references('key')->on('policy_settings')->restrictOnDelete();
            $table->unsignedBigInteger('from_value');
            $table->unsignedBigInteger('to_value');
            $table->text('reason');
            $table->foreignId('proposed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('created_at');
            $table->timestampTz('decided_at')->nullable();
            $table->index(['setting_key', 'created_at']);
        });
        Schema::create('policy_change_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('policy_change_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 10);
            $table->text('reason');
            $table->timestampTz('created_at');
            $table->unique(['policy_change_id', 'user_id']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE policy_changes ADD CONSTRAINT policy_change_status CHECK (status IN ('pending', 'applied', 'rejected'));
            ALTER TABLE policy_changes ADD CONSTRAINT policy_change_differs CHECK (from_value <> to_value);
            ALTER TABLE policy_changes ADD CONSTRAINT policy_change_decided CHECK ((status = 'pending') = (decided_at IS NULL));
            CREATE UNIQUE INDEX policy_changes_one_pending ON policy_changes (setting_key) WHERE status = 'pending';
            ALTER TABLE policy_change_decisions ADD CONSTRAINT policy_change_decision CHECK (decision IN ('approve', 'reject'));
            CREATE OR REPLACE FUNCTION policy_change_decisions_append_only() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'POLICY_DECISION_APPEND_ONLY';
            END;
            $$;
            CREATE TRIGGER policy_change_decisions_append_only BEFORE UPDATE OR DELETE ON policy_change_decisions
                FOR EACH ROW EXECUTE FUNCTION policy_change_decisions_append_only();
        SQL);
        DB::transaction(function (): void {
            foreach (self::SETTINGS as $key => $value) {
                DB::table('policy_settings')->insert(['key' => $key, 'value' => $value, 'revision' => 1, 'updated_at' => now()]);
            }
            $founder = DB::table('roles')->insertGetId(['name' => 'founder', 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
            $change = DB::table('permissions')->insertGetId(['name' => 'policies.change', 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
            $approve = DB::table('permissions')->insertGetId(['name' => 'policies.approve', 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
            $superadmin = DB::table('roles')->where('guard_name', 'staff')->where('name', 'superadmin')->value('id');
            DB::table('role_has_permissions')->insert([['role_id' => $founder, 'permission_id' => $change], ['role_id' => $founder, 'permission_id' => $approve],
                ['role_id' => $superadmin, 'permission_id' => $change]]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $permissions = DB::table('permissions')->where('guard_name', 'staff')->whereIn('name', ['policies.change', 'policies.approve'])->pluck('id');
            $founder = DB::table('roles')->where('guard_name', 'staff')->where('name', 'founder')->value('id');
            DB::table('role_has_permissions')->whereIn('permission_id', $permissions)->delete();
            DB::table('model_has_roles')->where('role_id', $founder)->delete();
            DB::table('permissions')->whereIn('id', $permissions)->delete();
            DB::table('roles')->where('id', $founder)->delete();
        });
        Schema::drop('policy_change_decisions');
        DB::unprepared('DROP FUNCTION policy_change_decisions_append_only()');
        Schema::drop('policy_changes');
        Schema::drop('policy_settings');
    }
};
