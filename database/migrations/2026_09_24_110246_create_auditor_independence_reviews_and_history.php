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
        Schema::create('auditor_independence_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->foreignUlid('party_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('state');
            $table->timestampsTz();
            $table->unique(['business_id', 'party_id']);
        });
        Schema::create('auditor_independence_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('review_id')->constrained('auditor_independence_reviews')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->text('reason');
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['review_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE auditor_independence_reviews ADD CONSTRAINT independence_review_revision CHECK (revision > 0);
            ALTER TABLE auditor_independence_versions ADD CONSTRAINT independence_version_revision CHECK (revision > 0);
            CREATE OR REPLACE FUNCTION reject_independence_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Auditor independence history is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER auditor_independence_versions_immutable
            BEFORE UPDATE OR DELETE ON auditor_independence_versions
            FOR EACH ROW EXECUTE FUNCTION reject_independence_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('auditor_independence_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_independence_history_mutation()');
        Schema::dropIfExists('auditor_independence_reviews');
    }
};
