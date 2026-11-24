<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Business's monthly submission (BRS v3.0 VIS-R04): the figures it declares for the month its
 * monthly audit covers, before the Audit Partner verifies them. One per business and period, final
 * once sent. Amounts are whole Rwandan francs; `forecast_basis` is the verified 3-month average the
 * forecast was pre-filled with, and `order` the signed order a forecast more than 25% away from it
 * rests on. Business-only: no Investor read touches this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_monthly_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->string('period', 7);
            $table->decimal('money_in', 15, 0);
            $table->jsonb('costs');
            $table->decimal('costs_total', 16, 0);
            $table->decimal('paid_to_owners', 15, 0);
            $table->string('forecast_period', 7);
            $table->decimal('forecast', 15, 0);
            $table->decimal('forecast_basis', 15, 0)->nullable();
            $table->string('order', 200)->nullable();
            $table->jsonb('changes');
            $table->string('policy_version', 60);
            $table->foreignUlid('submitted_by_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('submitted_at');
            $table->unique(['business_id', 'period']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_monthly_submissions ADD CONSTRAINT monthly_submission_period CHECK (period ~ '^[0-9]{4}-(0[1-9]|1[0-2])$' AND forecast_period ~ '^[0-9]{4}-(0[1-9]|1[0-2])$' AND forecast_period > period);
            ALTER TABLE business_monthly_submissions ADD CONSTRAINT monthly_submission_amounts CHECK (money_in >= 0 AND costs_total >= 0 AND paid_to_owners >= 0 AND forecast >= 0 AND (forecast_basis IS NULL OR forecast_basis >= 0));
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_monthly_submissions');
    }
};
