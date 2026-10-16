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
        Schema::create('statement_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->unique()->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamps();
        });
        Schema::create('statement_originals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('statement_evidence_id')->constrained('statement_evidence')->restrictOnDelete();
            $table->unsignedInteger('evidence_revision');
            $table->string('filename', 180);
            $table->string('media_type', 40);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->unsignedBigInteger('actor_user_id');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['statement_evidence_id', 'sha256'], 'statement_original_digest_unique');
            $table->unique(['statement_evidence_id', 'evidence_revision'], 'statement_original_revision_unique');
        });
        Schema::create('statement_extractions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('statement_original_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('parser_version', 80);
            $table->string('status', 20);
            $table->jsonb('reason_codes');
            $table->unsignedInteger('record_count')->nullable();
            $table->text('text')->nullable();
            $table->timestampTz('created_at');
            $table->unique(['statement_original_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE statement_originals ADD CONSTRAINT statement_original_size CHECK (size_bytes > 0 AND size_bytes <= 10485760);
            ALTER TABLE statement_originals ADD CONSTRAINT statement_original_media CHECK (media_type IN ('application/pdf', 'text/csv'));
            CREATE OR REPLACE FUNCTION reject_statement_evidence_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Statement originals and extractions are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER statement_originals_immutable
            BEFORE UPDATE OR DELETE ON statement_originals
            FOR EACH ROW EXECUTE FUNCTION reject_statement_evidence_mutation();
            CREATE TRIGGER statement_extractions_immutable
            BEFORE UPDATE OR DELETE ON statement_extractions
            FOR EACH ROW EXECUTE FUNCTION reject_statement_evidence_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_extractions');
        Schema::dropIfExists('statement_originals');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_statement_evidence_mutation()');
        Schema::dropIfExists('statement_evidence');
    }
};
