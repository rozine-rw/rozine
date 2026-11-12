<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The monthly visit explains every difference by cause code before the photos (BRS v3 VIS-R01
 * step 9), so a monthly report may now stand at the `differences` step.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                (kind = 'monthly' AND step IN ('statements', 'count', 'differences', 'photos', 'seal'))
            );
            SQL);
    }

    /** A report already at the new step requires a forward fix; rollback may only remove unused support. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE audit_reports IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE step = 'differences') THEN
                        RAISE EXCEPTION 'Monthly reports at the differences step require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_step;
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                    (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                    (kind = 'monthly' AND step IN ('statements', 'count', 'photos', 'seal'))
                );
                SQL);
        });
    }
};
