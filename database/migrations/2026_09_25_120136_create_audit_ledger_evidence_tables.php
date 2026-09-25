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
        Schema::create('audit_ledger_originals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('report_revision');
            $table->text('filename');
            $table->string('media_type', 40);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->unsignedBigInteger('actor_user_id');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['audit_report_id', 'sha256']);
            $table->unique(['audit_report_id', 'report_revision']);
        });
        Schema::create('audit_ledger_extractions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_ledger_original_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('parser_version', 80);
            $table->string('status', 20);
            $table->jsonb('reason_codes');
            $table->unsignedInteger('record_count')->nullable();
            $table->text('text')->nullable();
            $table->timestampTz('created_at');
            $table->unique(['audit_ledger_original_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_ledger_originals ADD CONSTRAINT audit_ledger_size CHECK (size_bytes > 0 AND size_bytes <= 10485760);
            ALTER TABLE audit_ledger_originals ADD CONSTRAINT audit_ledger_media CHECK (media_type IN ('application/pdf', 'text/csv'));
            ALTER TABLE audit_ledger_originals ADD CONSTRAINT audit_ledger_revision CHECK (report_revision > 0);
            ALTER TABLE audit_ledger_extractions ADD CONSTRAINT audit_ledger_extraction_state CHECK (revision > 0 AND status IN ('pending', 'text_extracted', 'needs_review'));
            CREATE OR REPLACE FUNCTION reject_audit_ledger_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit ledger originals and extraction history are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_ledger_originals_immutable BEFORE UPDATE OR DELETE ON audit_ledger_originals FOR EACH ROW EXECUTE FUNCTION reject_audit_ledger_mutation();
            CREATE TRIGGER audit_ledger_extractions_immutable BEFORE UPDATE OR DELETE ON audit_ledger_extractions FOR EACH ROW EXECUTE FUNCTION reject_audit_ledger_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_ledger_originals, audit_ledger_extractions IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_ledger_originals')->exists() || DB::table('audit_ledger_extractions')->exists()) {
                throw new RuntimeException('Existing audit ledger evidence requires a forward migration.');
            }
            Schema::dropIfExists('audit_ledger_extractions');
            Schema::dropIfExists('audit_ledger_originals');
            DB::unprepared('DROP FUNCTION IF EXISTS reject_audit_ledger_mutation()');
        });
    }
};
