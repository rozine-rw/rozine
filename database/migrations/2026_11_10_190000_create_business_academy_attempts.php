<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Business's Academy test attempts (BRS v3.0 RDY-R07): one row per module and attempt, numbered
 * from 1, scored on the server out of 10. `questions` keeps the drawn question ids in the order
 * shown and `answers` the option chosen for each. Business-only: no Investor read touches this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_academy_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->string('module', 40);
            $table->unsignedSmallInteger('attempt');
            $table->jsonb('questions');
            $table->jsonb('answers');
            $table->unsignedSmallInteger('score');
            $table->boolean('passed');
            $table->string('policy_version', 60);
            $table->foreignUlid('submitted_by_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('submitted_at');
            $table->unique(['business_id', 'module', 'attempt']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_academy_attempts ADD CONSTRAINT academy_attempt_score CHECK (attempt >= 1 AND score BETWEEN 0 AND 10 AND passed = (score >= 8));
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_academy_attempts');
    }
};
