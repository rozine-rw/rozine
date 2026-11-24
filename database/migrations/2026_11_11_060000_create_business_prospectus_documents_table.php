<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The evidence a prospectus cites (BRS v3.0 PRO-R03 P-G05, P-G06): each supplier quote or invoice and
 * each signed contract the Business uploads for one application, with the acting Party and its
 * operation. A gate counts a line or month as evidenced only when it names one of these rows for the
 * same application and kind. The bytes are encrypted; a document is never edited or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_prospectus_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->foreignUlid('business_application_id')->constrained('business_applications')->restrictOnDelete();
            $table->string('kind', 16);
            $table->text('filename');
            $table->string('media_type', 32);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('created_at', 6);
            $table->index(['business_application_id', 'kind']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_prospectus_documents ADD CONSTRAINT business_prospectus_document_kind CHECK (kind IN ('quote', 'contract'));
            ALTER TABLE business_prospectus_documents ADD CONSTRAINT business_prospectus_document_size CHECK (size_bytes BETWEEN 1 AND 10485760);
            CREATE OR REPLACE FUNCTION reject_business_prospectus_document_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business prospectus documents are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_prospectus_documents_immutable
            BEFORE UPDATE OR DELETE ON business_prospectus_documents
            FOR EACH ROW EXECUTE FUNCTION reject_business_prospectus_document_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_prospectus_documents');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_prospectus_document_mutation()');
    }
};
