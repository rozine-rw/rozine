<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The monthly visit records the staff count, the tax and RSSB expiry dates, the use of the loan
 * money and the four yes/no changes after the differences (BRS v3 VIS-R01 steps 10, 12, 13 and 14),
 * so a monthly report may now stand at the `staff`, `expiries`, `loan_use` or `changes` step.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                (kind = 'monthly' AND step IN ('statements', 'count', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal'))
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
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE step IN ('staff', 'expiries', 'loan_use', 'changes')) THEN
                        RAISE EXCEPTION 'Monthly reports at the staff, expiries, loan use or changes step require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                    (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                    (kind = 'monthly' AND step IN ('statements', 'count', 'drawings', 'loans', 'differences', 'photos', 'seal'))
                );
                SQL);
        });
    }
};
