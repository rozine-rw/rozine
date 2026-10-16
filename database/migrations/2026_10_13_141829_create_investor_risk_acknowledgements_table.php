<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An Investor's acknowledgement of the five risk statements (BRS v2.0 KYC-R05), append-only: each
 * row binds the statement version and digest it was given for, the time and its own digest. A new
 * wording is a new version, acknowledged in a new row; nothing is updated or deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_risk_acknowledgements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->string('version', 80);
            $table->char('statements_sha256', 64);
            $table->char('sha256', 64);
            $table->timestampTz('acknowledged_at', 6);
            $table->unique(['party_id', 'version', 'statements_sha256'], 'investor_risk_acknowledgement_once');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_risk_acknowledgements ADD CONSTRAINT investor_risk_acknowledgement_digests
                CHECK (statements_sha256 ~ '^[0-9a-f]{64}$' AND sha256 ~ '^[0-9a-f]{64}$');
            CREATE OR REPLACE FUNCTION reject_investor_risk_acknowledgement_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Investor risk acknowledgements are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER investor_risk_acknowledgements_immutable
            BEFORE UPDATE OR DELETE ON investor_risk_acknowledgements
            FOR EACH ROW EXECUTE FUNCTION reject_investor_risk_acknowledgement_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_risk_acknowledgements');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_investor_risk_acknowledgement_mutation()');
    }
};
