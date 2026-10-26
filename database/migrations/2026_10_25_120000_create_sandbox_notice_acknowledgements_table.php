<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Party's acknowledgement of the CMA sandbox notice before sign-up (BRS v2.0 KYC-R01),
 * append-only: each row binds the notice version and digest that was shown, the time and its own
 * digest. A new wording is a new version, acknowledged in a new row; nothing is updated or deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sandbox_notice_acknowledgements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('version', 80);
            $table->char('notice_sha256', 64);
            $table->char('sha256', 64);
            $table->timestampTz('acknowledged_at', 6);
            $table->unique(['party_id', 'version', 'notice_sha256'], 'sandbox_notice_acknowledgement_once');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE sandbox_notice_acknowledgements ADD CONSTRAINT sandbox_notice_acknowledgement_digests
                CHECK (notice_sha256 ~ '^[0-9a-f]{64}$' AND sha256 ~ '^[0-9a-f]{64}$');
            CREATE OR REPLACE FUNCTION reject_sandbox_notice_acknowledgement_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Sandbox notice acknowledgements are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER sandbox_notice_acknowledgements_immutable
            BEFORE UPDATE OR DELETE ON sandbox_notice_acknowledgements
            FOR EACH ROW EXECUTE FUNCTION reject_sandbox_notice_acknowledgement_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_notice_acknowledgements');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_sandbox_notice_acknowledgement_mutation()');
    }
};
