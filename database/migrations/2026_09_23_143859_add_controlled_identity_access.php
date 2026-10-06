<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_memberships', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(1);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUlid('active_membership_id')->nullable()->constrained('role_memberships')->restrictOnDelete();
            $table->unsignedInteger('active_membership_revision')->nullable();
            $table->unsignedInteger('context_revision')->default(0);
        });

        Schema::create('identity_operators', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('verified_person_identities', function (Blueprint $table): void {
            $table->char('identity_digest', 64)->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->string('evidence_reference');
            $table->timestamps();
        });

        Schema::create('identity_audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('actor_key');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('target_type');
            $table->string('target_id');
            $table->string('action');
            $table->text('reason');
            $table->uuid('request_id');
            $table->char('request_hash', 64);
            $table->jsonb('before');
            $table->jsonb('after');
            $table->jsonb('result');
            $table->string('policy_version');
            $table->timestampTz('created_at');
            $table->unique(['actor_key', 'request_id']);
            $table->index(['target_type', 'target_id']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_identity_audit_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Identity audit events are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER identity_audit_events_immutable
            BEFORE UPDATE OR DELETE ON identity_audit_events
            FOR EACH ROW EXECUTE FUNCTION reject_identity_audit_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_audit_events');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_identity_audit_mutation()');
        Schema::dropIfExists('verified_person_identities');
        Schema::dropIfExists('identity_operators');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_membership_id');
            $table->dropColumn(['active_membership_revision', 'context_revision']);
        });

        Schema::table('role_memberships', function (Blueprint $table): void {
            $table->dropColumn('revision');
        });
    }
};
