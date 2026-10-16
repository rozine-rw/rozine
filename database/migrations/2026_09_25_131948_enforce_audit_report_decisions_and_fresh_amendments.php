<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports IN SHARE ROW EXCLUSIVE MODE');
            DB::unprepared(<<<'SQL'
                ALTER TABLE audit_reports ADD CONSTRAINT audit_report_monthly_decision
                    CHECK (status NOT IN ('changes_requested', 'rejected') OR kind = 'monthly');
                CREATE OR REPLACE FUNCTION validate_audit_report_amendment_origin() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.amends_id IS NOT NULL AND (NEW.status <> 'draft' OR NEW.revision <> 1
                        OR NEW.step <> CASE NEW.kind WHEN 'flash' THEN 'review' ELSE 'statements' END) THEN
                        RAISE EXCEPTION 'Audit amendment must start as a draft at revision one and its first step' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER zzz_audit_reports_amendment_origin BEFORE INSERT ON audit_reports
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_report_amendment_origin();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_reports')->exists()) {
                throw new RuntimeException('Existing audit report decision history requires a forward migration.');
            }
            DB::unprepared(<<<'SQL'
                DROP TRIGGER zzz_audit_reports_amendment_origin ON audit_reports;
                DROP FUNCTION validate_audit_report_amendment_origin();
                ALTER TABLE audit_reports DROP CONSTRAINT audit_report_monthly_decision;
                SQL);
        });
    }
};
