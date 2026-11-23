<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Party's agreement to the Terms, the Privacy Notice and the Disclaimers, append-only: each row
 * binds one document, its version, the digest of its exact wording, where it was agreed, the time
 * and its own digest. New wording is agreed in a new row; nothing is updated or deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('document', 20);
            $table->string('version', 80);
            $table->char('document_sha256', 64);
            $table->string('source', 20);
            $table->char('sha256', 64);
            $table->timestampTz('accepted_at', 6);
            $table->unique(['party_id', 'document', 'version', 'document_sha256'], 'legal_consent_once');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE legal_consents ADD CONSTRAINT legal_consent_document CHECK (document IN ('terms', 'privacy', 'disclaimers'));
            ALTER TABLE legal_consents ADD CONSTRAINT legal_consent_source CHECK (source IN ('sign_up', 'review'));
            ALTER TABLE legal_consents ADD CONSTRAINT legal_consent_digests
                CHECK (document_sha256 ~ '^[0-9a-f]{64}$' AND sha256 ~ '^[0-9a-f]{64}$');
            CREATE OR REPLACE FUNCTION reject_legal_consent_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Legal consents are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER legal_consents_immutable
            BEFORE UPDATE OR DELETE ON legal_consents
            FOR EACH ROW EXECUTE FUNCTION reject_legal_consent_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_consents');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_legal_consent_mutation()');
    }
};
