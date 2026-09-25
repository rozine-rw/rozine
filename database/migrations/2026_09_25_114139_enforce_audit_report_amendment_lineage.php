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
            $invalid = DB::selectOne(<<<'SQL'
                SELECT child.id FROM audit_reports child JOIN audit_reports parent ON parent.id = child.amends_id
                WHERE parent.status NOT IN ('sealed', 'changes_requested', 'rejected')
                    OR ROW(child.business_id, child.application_id, child.application_revision, child.application_version_id, child.submission_id, child.quote_id, child.kind)
                    IS DISTINCT FROM ROW(parent.business_id, parent.application_id, parent.application_revision, parent.application_version_id, parent.submission_id, parent.quote_id, parent.kind)
                LIMIT 1
                SQL);
            if ($invalid !== null) {
                throw new RuntimeException('Existing audit amendment lineage must be reconciled before protection.');
            }
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION validate_audit_report_amendment() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    parent audit_reports%ROWTYPE;
                BEGIN
                    IF NEW.amends_id IS NULL THEN
                        RETURN NEW;
                    END IF;
                    SELECT * INTO parent FROM audit_reports WHERE id = NEW.amends_id FOR SHARE;
                    IF NOT FOUND OR parent.status NOT IN ('sealed', 'changes_requested', 'rejected')
                        OR ROW(NEW.business_id, NEW.application_id, NEW.application_revision, NEW.application_version_id, NEW.submission_id, NEW.quote_id, NEW.kind)
                        IS DISTINCT FROM ROW(parent.business_id, parent.application_id, parent.application_revision, parent.application_version_id, parent.submission_id, parent.quote_id, parent.kind) THEN
                        RAISE EXCEPTION 'Audit amendment requires a terminal report with identical submitted source lineage' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER zz_audit_reports_amendment_lineage BEFORE INSERT ON audit_reports
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_report_amendment();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_reports')->whereNotNull('amends_id')->exists()) {
                throw new RuntimeException('Existing audit amendment history requires a forward migration.');
            }
            DB::unprepared('DROP TRIGGER zz_audit_reports_amendment_lineage ON audit_reports; DROP FUNCTION validate_audit_report_amendment()');
        });
    }
};
