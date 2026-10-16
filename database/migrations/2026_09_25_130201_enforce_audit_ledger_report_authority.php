<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports, audit_ledger_originals IN SHARE ROW EXCLUSIVE MODE');
            $invalid = DB::table('audit_ledger_originals as original')->join('audit_reports as report', 'report.id', '=', 'original.audit_report_id')
                ->whereColumn('original.actor_party_id', '<>', 'report.author_party_id')->orWhere('report.kind', '<>', 'flash')->exists();
            if ($invalid) {
                throw new RuntimeException('Existing audit ledger ownership must be reconciled before protection.');
            }
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION validate_audit_ledger_report_authority() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    report audit_reports%ROWTYPE;
                BEGIN
                    SELECT * INTO report FROM audit_reports WHERE id = NEW.audit_report_id FOR SHARE;
                    IF NOT FOUND OR report.author_party_id IS DISTINCT FROM NEW.actor_party_id
                        OR report.kind <> 'flash' OR report.status <> 'draft' OR report.step <> 'ledger'
                        OR NEW.report_revision <> report.revision + 1 THEN
                        RAISE EXCEPTION 'Audit ledger requires its author and current draft Flash ledger revision' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_ledger_originals_report_authority BEFORE INSERT ON audit_ledger_originals
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_ledger_report_authority();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_ledger_originals IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_ledger_originals')->exists()) {
                throw new RuntimeException('Existing audit ledger authority history requires a forward migration.');
            }
            DB::unprepared('DROP TRIGGER audit_ledger_originals_report_authority ON audit_ledger_originals; DROP FUNCTION validate_audit_ledger_report_authority()');
        });
    }
};
