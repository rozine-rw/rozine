<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The monthly visit records the cash sales not deposited and the month's costs in six categories
 * after the count (BRS v3 VIS-R01 steps 4 and 5), so a monthly report may now stand at the
 * `cash_sales` or `costs` step.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                (kind = 'monthly' AND step IN ('statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal'))
            );
            SQL);
    }

    /** A report already at a new step requires a forward fix; rollback may only remove unused support. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE audit_reports IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE step IN ('cash_sales', 'costs')) THEN
                        RAISE EXCEPTION 'Monthly reports at the cash sales or costs step require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                    (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                    (kind = 'monthly' AND step IN ('statements', 'count', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal'))
                );
                SQL);
        });
    }
};
