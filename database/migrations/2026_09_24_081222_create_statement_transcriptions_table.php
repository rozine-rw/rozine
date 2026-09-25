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
        Schema::create('statement_transcriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('statement_evidence_id')->constrained('statement_evidence')->restrictOnDelete();
            $table->unsignedInteger('evidence_revision');
            $table->foreignUlid('amends_id')->nullable()->unique();
            $table->string('classification_version');
            $table->text('payload');
            $table->char('sha256', 64);
            $table->unsignedBigInteger('actor_user_id');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['statement_evidence_id', 'evidence_revision'], 'statement_transcription_revision_unique');
        });
        Schema::table('statement_transcriptions', function (Blueprint $table): void {
            $table->foreign('amends_id')->references('id')->on('statement_transcriptions')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_statement_transcription_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Statement transcriptions are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER statement_transcriptions_immutable
            BEFORE UPDATE OR DELETE ON statement_transcriptions
            FOR EACH ROW EXECUTE FUNCTION reject_statement_transcription_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_transcriptions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_statement_transcription_mutation()');
    }
};
