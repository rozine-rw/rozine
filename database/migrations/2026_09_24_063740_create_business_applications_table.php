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
        Schema::create('business_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->string('step', 20);
            $table->jsonb('draft');
            $table->unsignedInteger('mandate_version');
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });
        Schema::create('business_application_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_application_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->jsonb('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->unsignedInteger('mandate_version');
            $table->string('policy_version');
            $table->timestampTz('created_at');
            $table->unique(['business_application_id', 'revision'], 'business_application_version_unique');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_applications ADD CONSTRAINT business_application_status CHECK (status IN ('draft', 'submitted'));
            ALTER TABLE business_applications ADD CONSTRAINT business_application_step CHECK (step IN ('business', 'raise', 'review', 'submitted'));
            CREATE OR REPLACE FUNCTION reject_business_application_version_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business application versions are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_application_versions_immutable
            BEFORE UPDATE OR DELETE ON business_application_versions
            FOR EACH ROW EXECUTE FUNCTION reject_business_application_version_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_application_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_application_version_mutation()');
        Schema::dropIfExists('business_applications');
    }
};
