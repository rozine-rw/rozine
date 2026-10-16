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
        Schema::create('business_application_signatures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_application_id')->constrained()->restrictOnDelete();
            $table->ulid('business_application_quote_id');
            $table->foreign(['business_application_id', 'business_application_quote_id'], 'application_signature_quote_owner')
                ->references(['business_application_id', 'id'])->on('business_application_quotes')->restrictOnDelete();
            $table->foreignUlid('consent_release_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->char('binding_sha256', 64);
            $table->char('sha256', 64);
            $table->text('payload');
            $table->timestampTz('created_at');
            $table->unique(['business_application_id', 'binding_sha256', 'actor_party_id'], 'application_signature_party_unique');
        });
        Schema::create('business_application_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_application_id')->constrained()->restrictOnDelete();
            $table->ulid('business_application_quote_id');
            $table->foreign(['business_application_id', 'business_application_quote_id'], 'application_submission_quote_owner')
                ->references(['business_application_id', 'id'])->on('business_application_quotes')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->char('binding_sha256', 64);
            $table->char('sha256', 64);
            $table->text('payload');
            $table->timestampTz('created_at');
            $table->unique(['business_application_id', 'revision'], 'application_submission_revision_unique');
            $table->unique(['business_application_id', 'id'], 'application_submission_owner_unique');
        });
        Schema::table('business_applications', function (Blueprint $table): void {
            $table->ulid('current_submission_id')->nullable();
            $table->foreign(['id', 'current_submission_id'], 'application_current_submission_owner')
                ->references(['business_application_id', 'id'])->on('business_application_submissions')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_application_submissions ADD CONSTRAINT application_submission_revision_positive CHECK (revision > 0);
            CREATE OR REPLACE FUNCTION reject_business_application_acceptance_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business application acceptances are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_application_signatures_immutable
            BEFORE UPDATE OR DELETE ON business_application_signatures
            FOR EACH ROW EXECUTE FUNCTION reject_business_application_acceptance_mutation();
            CREATE TRIGGER business_application_submissions_immutable
            BEFORE UPDATE OR DELETE ON business_application_submissions
            FOR EACH ROW EXECUTE FUNCTION reject_business_application_acceptance_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::table('business_applications', function (Blueprint $table): void {
            $table->dropForeign('application_current_submission_owner');
            $table->dropColumn('current_submission_id');
        });
        Schema::dropIfExists('business_application_submissions');
        Schema::dropIfExists('business_application_signatures');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_application_acceptance_mutation()');
    }
};
