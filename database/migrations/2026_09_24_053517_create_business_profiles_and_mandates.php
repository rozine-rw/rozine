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
        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('entity_party_id')->unique()->constrained('parties')->restrictOnDelete();
            $table->string('entity_kind', 20);
            $table->jsonb('profile');
            $table->unsignedInteger('revision');
            $table->unsignedInteger('mandate_version');
            $table->timestamps();
        });
        Schema::create('business_mandates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('terms');
            $table->jsonb('profile');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('evidence_reference');
            $table->text('reason');
            $table->string('policy_version');
            $table->timestampTz('created_at');
            $table->unique(['business_id', 'version']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_profiles ADD CONSTRAINT business_entity_kind CHECK (entity_kind IN ('person', 'organization'));
            CREATE OR REPLACE FUNCTION reject_business_mandate_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business mandates are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_mandates_immutable
            BEFORE UPDATE OR DELETE ON business_mandates
            FOR EACH ROW EXECUTE FUNCTION reject_business_mandate_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_mandates');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_mandate_mutation()');
        Schema::dropIfExists('business_profiles');
    }
};
