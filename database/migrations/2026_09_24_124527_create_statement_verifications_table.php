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
        Schema::create('statement_verifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('statement_evidence_id')->constrained('statement_evidence')->restrictOnDelete();
            $table->foreignUlid('transcription_id')->constrained('statement_transcriptions')->restrictOnDelete();
            $table->foreignUlid('assignment_id')->constrained('audit_assignments')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->unsignedInteger('source_revision');
            $table->foreignUlid('amends_id')->nullable()->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->string('policy_version', 80);
            $table->string('procedure_version', 80);
            $table->unsignedBigInteger('actor_user_id');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['statement_evidence_id', 'revision']);
        });
        Schema::table('statement_verifications', function (Blueprint $table): void {
            $table->foreign('amends_id')->references('id')->on('statement_verifications')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE statement_verifications ADD CONSTRAINT statement_verification_revisions CHECK (revision > 0 AND source_revision > 0);
            CREATE OR REPLACE FUNCTION reject_statement_verification_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Statement verifications are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER statement_verifications_immutable BEFORE UPDATE OR DELETE ON statement_verifications FOR EACH ROW EXECUTE FUNCTION reject_statement_verification_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_verifications');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_statement_verification_mutation()');
    }
};
