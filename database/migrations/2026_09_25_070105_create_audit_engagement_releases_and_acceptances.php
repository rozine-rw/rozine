<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_engagement_releases', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedInteger('revision')->unique();
            $table->string('status', 20);
            $table->string('version', 80)->nullable()->unique();
            $table->string('procedure_version', 80);
            $table->text('documents');
            $table->char('sha256', 64);
            $table->boolean('synthetic');
            $table->text('approval_reference');
            $table->text('reason');
            $table->unsignedBigInteger('actor_user_id');
            $table->timestampTz('created_at');
            $table->unique(['id', 'revision', 'sha256'], 'audit_engagement_release_pin');
        });
        Schema::create('audit_engagement_acceptances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('audit_engagement_release_id');
            $table->unsignedInteger('release_revision');
            $table->char('release_sha256', 64);
            $table->foreignUlid('party_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('actor_user_id');
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['audit_engagement_release_id', 'party_id'], 'audit_engagement_party_acceptance');
            $table->foreign(['audit_engagement_release_id', 'release_revision', 'release_sha256'], 'audit_engagement_acceptance_release')
                ->references(['id', 'revision', 'sha256'])->on('audit_engagement_releases')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_engagement_releases ADD CONSTRAINT audit_engagement_release_revision CHECK (revision > 0);
            ALTER TABLE audit_engagement_releases ADD CONSTRAINT audit_engagement_release_state CHECK (
                (status = 'active' AND version IS NOT NULL) OR (status = 'withdrawn' AND version IS NULL)
            );
            CREATE OR REPLACE FUNCTION reject_audit_engagement_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit engagement terms and acceptance history are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_engagement_releases_immutable BEFORE UPDATE OR DELETE ON audit_engagement_releases FOR EACH ROW EXECUTE FUNCTION reject_audit_engagement_mutation();
            CREATE TRIGGER audit_engagement_acceptances_immutable BEFORE UPDATE OR DELETE ON audit_engagement_acceptances FOR EACH ROW EXECUTE FUNCTION reject_audit_engagement_mutation();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_engagement_releases, audit_engagement_acceptances IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_engagement_releases')->exists() || DB::table('audit_engagement_acceptances')->exists()) {
                throw new RuntimeException('Existing audit engagement history requires a forward migration.');
            }
            Schema::dropIfExists('audit_engagement_acceptances');
            Schema::dropIfExists('audit_engagement_releases');
            DB::unprepared('DROP FUNCTION IF EXISTS reject_audit_engagement_mutation()');
        });
    }
};
