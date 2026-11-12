<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The monthly visit records owner drawings and other loans after the count (BRS v3 VIS-R01 steps 7
 * and 8), so a monthly report may now stand at the `drawings` or `loans` step.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                (kind = 'monthly' AND step IN ('statements', 'count', 'drawings', 'loans', 'differences', 'photos', 'seal'))
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
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE step IN ('drawings', 'loans')) THEN
                        RAISE EXCEPTION 'Monthly reports at the drawings or loans step require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                    (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                    (kind = 'monthly' AND step IN ('statements', 'count', 'differences', 'photos', 'seal'))
                );
                SQL);
        });
    }
};
