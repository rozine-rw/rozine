<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An Investor's own identity submission: encrypted answers, hash-pinned private documents and an
 * append-only history. A submission never verifies anyone by itself; only the existing verified-person
 * writer sets Party verification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_verifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->text('state');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'submitted_at']);
        });
        Schema::create('investor_verification_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_verification_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->text('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 80);
            $table->text('reason')->nullable();
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['investor_verification_id', 'revision']);
        });
        Schema::create('investor_verification_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_verification_id')->constrained()->restrictOnDelete();
            $table->string('slot', 20);
            $table->text('filename');
            $table->string('media_type', 40);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->unsignedBigInteger('actor_user_id');
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_verifications ADD CONSTRAINT investor_verification_revision CHECK (revision > 0);
            ALTER TABLE investor_verifications ADD CONSTRAINT investor_verification_status CHECK (status IN ('draft', 'submitted', 'approved', 'rejected'));
            ALTER TABLE investor_verifications ADD CONSTRAINT investor_verification_submitted CHECK ((status = 'draft') = (submitted_at IS NULL));
            ALTER TABLE investor_verification_versions ADD CONSTRAINT investor_verification_version_revision CHECK (revision > 0);
            ALTER TABLE investor_verification_documents ADD CONSTRAINT investor_verification_document_slot CHECK (slot IN ('front', 'back', 'selfie'));
            ALTER TABLE investor_verification_documents ADD CONSTRAINT investor_verification_document_size CHECK (size_bytes > 0 AND size_bytes <= 10485760);
            ALTER TABLE investor_verification_documents ADD CONSTRAINT investor_verification_document_media CHECK (media_type IN ('application/pdf', 'image/png', 'image/jpeg'));
            CREATE OR REPLACE FUNCTION reject_investor_verification_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Investor verification history and documents are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER investor_verification_versions_immutable
            BEFORE UPDATE OR DELETE ON investor_verification_versions
            FOR EACH ROW EXECUTE FUNCTION reject_investor_verification_history_mutation();
            CREATE TRIGGER investor_verification_documents_immutable
            BEFORE UPDATE OR DELETE ON investor_verification_documents
            FOR EACH ROW EXECUTE FUNCTION reject_investor_verification_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_verification_documents');
        Schema::dropIfExists('investor_verification_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_investor_verification_history_mutation()');
        Schema::dropIfExists('investor_verifications');
    }
};
