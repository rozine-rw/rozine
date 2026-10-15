<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Business's contact details and registered address (Business.dc.html "Company information"):
 * the e-mail, phone and the province, district, sector, cell and street the mandate keeps current.
 * Each save under `business.profile.update` appends the next version with the acting Party and the
 * operation that wrote it; the latest version is the current one. The details are personal data, so
 * they are held encrypted beside a digest of their canonical form. A version is never edited or
 * removed once written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_contact_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at', 6);
            $table->unique(['business_id', 'version']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_contact_versions ADD CONSTRAINT business_contact_version_positive CHECK (version >= 1);
            CREATE OR REPLACE FUNCTION reject_business_contact_version_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business contact versions are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_contact_versions_immutable
            BEFORE UPDATE OR DELETE ON business_contact_versions
            FOR EACH ROW EXECUTE FUNCTION reject_business_contact_version_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_contact_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_contact_version_mutation()');
    }
};
