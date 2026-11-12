<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Business's prospectus for one accepted application (BRS v3.0 PRO-R02 to PRO-R04). Each save
 * appends the next draft version with the acting Party and its operation; the official
 * representative's signature appends one `signed` version that locks the document under the SHA-256
 * fingerprint of its canonical form. An application has at most one signed version, and nothing is
 * ever edited or removed once written. Profiles name real people, so the document is held encrypted
 * beside a digest of its canonical form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_prospectus_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->foreignUlid('business_application_id')->constrained('business_applications')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 16);
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->char('fingerprint', 64)->nullable();
            $table->timestampTz('created_at', 6);
            $table->unique(['business_application_id', 'version']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_prospectus_versions ADD CONSTRAINT business_prospectus_version_positive CHECK (version >= 1);
            ALTER TABLE business_prospectus_versions ADD CONSTRAINT business_prospectus_version_kind
                CHECK ((kind = 'draft' AND fingerprint IS NULL) OR (kind = 'signed' AND fingerprint IS NOT NULL));
            CREATE UNIQUE INDEX business_prospectus_versions_one_signed ON business_prospectus_versions (business_application_id) WHERE kind = 'signed';
            CREATE OR REPLACE FUNCTION reject_business_prospectus_version_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business prospectus versions are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_prospectus_versions_immutable
            BEFORE UPDATE OR DELETE ON business_prospectus_versions
            FOR EACH ROW EXECUTE FUNCTION reject_business_prospectus_version_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_prospectus_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_prospectus_version_mutation()');
    }
};
