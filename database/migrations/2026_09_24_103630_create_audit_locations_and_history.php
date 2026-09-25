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
        Schema::create('audit_locations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('office_party_id')->nullable()->unique()->constrained('parties')->restrictOnDelete();
            $table->foreignUlid('business_id')->nullable()->unique()->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('state');
            $table->timestampsTz();
        });
        Schema::create('audit_location_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_location_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('snapshot');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 80);
            $table->text('reason');
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['audit_location_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_locations ADD CONSTRAINT audit_location_subject CHECK ((office_party_id IS NULL) <> (business_id IS NULL));
            ALTER TABLE audit_locations ADD CONSTRAINT audit_location_revision CHECK (revision > 0);
            ALTER TABLE audit_location_versions ADD CONSTRAINT audit_location_version_revision CHECK (revision > 0);
            CREATE OR REPLACE FUNCTION reject_audit_location_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit location history is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_location_versions_immutable
            BEFORE UPDATE OR DELETE ON audit_location_versions
            FOR EACH ROW EXECUTE FUNCTION reject_audit_location_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_location_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_audit_location_history_mutation()');
        Schema::dropIfExists('audit_locations');
    }
};
