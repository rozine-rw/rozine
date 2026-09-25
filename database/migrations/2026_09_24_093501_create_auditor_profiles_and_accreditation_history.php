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
        Schema::create('auditor_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('state');
            $table->timestampsTz();
        });
        Schema::create('auditor_profile_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('auditor_profile_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 80);
            $table->text('reason')->nullable();
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['auditor_profile_id', 'revision']);
        });
        Schema::create('auditor_certificates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('auditor_profile_id')->constrained()->restrictOnDelete();
            $table->text('filename');
            $table->string('media_type', 40);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->unsignedBigInteger('actor_user_id');
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE auditor_profiles ADD CONSTRAINT auditor_profile_revision CHECK (revision > 0);
            ALTER TABLE auditor_profile_versions ADD CONSTRAINT auditor_profile_version_revision CHECK (revision > 0);
            ALTER TABLE auditor_certificates ADD CONSTRAINT auditor_certificate_size CHECK (size_bytes > 0 AND size_bytes <= 10485760);
            ALTER TABLE auditor_certificates ADD CONSTRAINT auditor_certificate_media CHECK (media_type IN ('application/pdf', 'image/png', 'image/jpeg'));
            CREATE OR REPLACE FUNCTION reject_auditor_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Auditor profile history and certificates are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER auditor_profile_versions_immutable
            BEFORE UPDATE OR DELETE ON auditor_profile_versions
            FOR EACH ROW EXECUTE FUNCTION reject_auditor_history_mutation();
            CREATE TRIGGER auditor_certificates_immutable
            BEFORE UPDATE OR DELETE ON auditor_certificates
            FOR EACH ROW EXECUTE FUNCTION reject_auditor_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('auditor_certificates');
        Schema::dropIfExists('auditor_profile_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_auditor_history_mutation()');
        Schema::dropIfExists('auditor_profiles');
    }
};
