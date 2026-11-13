<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The late visit day 7 of arrears sends (BRS v3 AUD-R08) is a Flash visit with two steps of its own
 * (VIS-R02): the money available now with its proof, and the reason from a fixed list. A Flash
 * report may now stand at either.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'history', 'premises', 'people', 'registration', 'trading', 'available', 'reason', 'photos', 'ledger', 'seal')) OR
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
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE step IN ('available', 'reason')) THEN
                        RAISE EXCEPTION 'Reports at a late visit step require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                    (kind = 'flash' AND step IN ('review', 'check_in', 'history', 'premises', 'people', 'registration', 'trading', 'photos', 'ledger', 'seal')) OR
                    (kind = 'monthly' AND step IN ('statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal'))
                );
                SQL);
        });
    }
};
