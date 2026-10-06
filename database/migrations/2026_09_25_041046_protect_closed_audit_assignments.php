<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_closed_audit_assignment_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.status = 'closed' THEN
                    RAISE EXCEPTION 'Closed audit assignments are immutable' USING ERRCODE = '23514';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_assignment_closed_immutable
            BEFORE UPDATE OR DELETE ON audit_assignments
            FOR EACH ROW EXECUTE FUNCTION reject_closed_audit_assignment_mutation();
            SQL);
    }

    /** Closed engagement history requires a forward fix; rollback may only remove unused protection. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE audit_assignments IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM audit_assignments WHERE status = 'closed') THEN
                        RAISE EXCEPTION 'Closed audit assignments require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER audit_assignment_closed_immutable ON audit_assignments;
                DROP FUNCTION reject_closed_audit_assignment_mutation();
                SQL);
        });
    }
};
