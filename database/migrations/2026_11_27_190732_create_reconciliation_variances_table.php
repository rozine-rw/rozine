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
        Schema::create('reconciliation_variances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('account_id', 20);
            $table->date('business_date');
            $table->timestampTz('cutoff_at', 6);
            $table->decimal('ledger', 21, 0);
            $table->decimal('statement', 21, 0);
            $table->decimal('difference', 21, 0);
            $table->string('statement_source', 40);
            $table->string('statement_reference');
            $table->jsonb('alert_audiences');
            $table->timestampTz('created_at', 6)->index();
            $table->unique(['business_date', 'account_id']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE reconciliation_variances ADD CONSTRAINT reconciliation_variance_account
                CHECK (account_id IN ('deposits', 'withdrawals'));
            ALTER TABLE reconciliation_variances ADD CONSTRAINT reconciliation_variance_difference
                CHECK (difference = ledger - statement AND difference <> 0 AND statement_source = 'authoritative_statement' AND length(trim(statement_reference)) > 0);
            CREATE OR REPLACE FUNCTION reject_reconciliation_variance_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Reconciliation cutoff evidence is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER reconciliation_variances_immutable BEFORE UPDATE OR DELETE ON reconciliation_variances
                FOR EACH ROW EXECUTE FUNCTION reject_reconciliation_variance_mutation();
            SQL);
    }

    /** Retained financial evidence requires a forward migration once recorded. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE reconciliation_variances IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM reconciliation_variances) THEN
                        RAISE EXCEPTION 'Recorded reconciliation evidence requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('reconciliation_variances');
            DB::unprepared('DROP FUNCTION reject_reconciliation_variance_mutation();');
        });
    }
};
