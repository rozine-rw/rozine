<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A person's application to switch from Investor to Business representative (with the company's KYB)
 * or to Auditor (with the ICPAR licence): encrypted answers, hash-pinned private documents and an
 * append-only history. Only Compliance's approval switches the role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->string('role', 20);
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->text('state');
            $table->text('search_name')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->foreignUlid('business_id')->nullable()->constrained('business_profiles')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['role', 'status', 'submitted_at']);
        });
        Schema::create('role_application_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_application_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->text('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 80);
            $table->text('reason')->nullable();
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['role_application_id', 'revision']);
        });
        Schema::create('role_application_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_application_id')->constrained()->restrictOnDelete();
            $table->string('slot', 40);
            $table->text('filename');
            $table->string('media_type', 40);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->unsignedBigInteger('actor_user_id');
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE role_applications ADD CONSTRAINT role_application_role CHECK (role IN ('business', 'auditor'));
            ALTER TABLE role_applications ADD CONSTRAINT role_application_revision CHECK (revision > 0);
            ALTER TABLE role_applications ADD CONSTRAINT role_application_status CHECK (status IN ('draft', 'submitted', 'changes_requested', 'approved', 'rejected'));
            ALTER TABLE role_applications ADD CONSTRAINT role_application_submitted CHECK ((status = 'draft') = (submitted_at IS NULL));
            ALTER TABLE role_applications ADD CONSTRAINT role_application_business CHECK (business_id IS NULL OR (role = 'business' AND status = 'approved'));
            ALTER TABLE role_application_versions ADD CONSTRAINT role_application_version_revision CHECK (revision > 0);
            ALTER TABLE role_application_documents ADD CONSTRAINT role_application_document_size CHECK (size_bytes > 0 AND size_bytes <= 10485760);
            ALTER TABLE role_application_documents ADD CONSTRAINT role_application_document_media CHECK (media_type IN ('application/pdf', 'image/png', 'image/jpeg'));
            CREATE OR REPLACE FUNCTION reject_role_application_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Role application history and documents are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER role_application_versions_immutable
            BEFORE UPDATE OR DELETE ON role_application_versions
            FOR EACH ROW EXECUTE FUNCTION reject_role_application_history_mutation();
            CREATE TRIGGER role_application_documents_immutable
            BEFORE UPDATE OR DELETE ON role_application_documents
            FOR EACH ROW EXECUTE FUNCTION reject_role_application_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_application_documents');
        Schema::dropIfExists('role_application_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_role_application_history_mutation()');
        Schema::dropIfExists('role_applications');
    }
};
