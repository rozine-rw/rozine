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
        Schema::create('consent_releases', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedInteger('revision')->unique();
            $table->string('status', 20);
            $table->jsonb('documents');
            $table->jsonb('disclosures');
            $table->boolean('synthetic');
            $table->string('approval_reference');
            $table->text('reason');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('policy_version');
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE consent_releases ADD CONSTRAINT consent_release_status CHECK (status IN ('active', 'withdrawn'));
            CREATE OR REPLACE FUNCTION reject_consent_release_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Consent releases are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER consent_releases_immutable
            BEFORE UPDATE OR DELETE ON consent_releases
            FOR EACH ROW EXECUTE FUNCTION reject_consent_release_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_releases');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_consent_release_mutation()');
    }
};
