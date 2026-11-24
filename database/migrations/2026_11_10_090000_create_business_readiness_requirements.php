<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Business's readiness requirements once triggered (BRS v3.0 RDY-R03). A triggered requirement
 * stays in force when the stage falls, so each is kept here from the Kigali day it was triggered,
 * one row per business and requirement. `due_on` is the day a Due or Lapsed requirement turns
 * Overdue; a Met one has none. `confirmation` holds the CPA's recorded evidence once the guided
 * visit confirms it (VIS-R01). Business-only: no Investor read touches this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_readiness_requirements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->string('code', 2);
            $table->string('status', 10);
            $table->date('triggered_on');
            $table->date('due_on')->nullable();
            $table->date('met_on')->nullable();
            $table->jsonb('confirmation')->nullable();
            $table->string('policy_version', 60);
            $table->unsignedInteger('revision');
            $table->timestampsTz();
            $table->unique(['business_id', 'code']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_readiness_requirements ADD CONSTRAINT readiness_requirement_code CHECK (code IN ('C1', 'F1', 'F2', 'F3', 'F4', 'P1', 'P2', 'G1', 'G2'));
            ALTER TABLE business_readiness_requirements ADD CONSTRAINT readiness_requirement_status CHECK (status IN ('due', 'overdue', 'met', 'lapsed'));
            ALTER TABLE business_readiness_requirements ADD CONSTRAINT readiness_requirement_due CHECK ((status IN ('due', 'lapsed')) = (due_on IS NOT NULL) OR status = 'overdue');
            ALTER TABLE business_readiness_requirements ADD CONSTRAINT readiness_requirement_revision CHECK (revision > 0);
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_readiness_requirements');
    }
};
