<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Audit Operations may withdraw an audit that can't finish (#489). A withdrawn assignment has no
 * recipient, ends that audit only, and is as immutable as a closed one.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_status;
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_recipient;
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_status CHECK (status IN ('offered', 'accepted', 'operations', 'completed', 'closed', 'withdrawn'));
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_recipient CHECK ((status IN ('operations', 'closed', 'withdrawn') AND party_id IS NULL) OR (status NOT IN ('operations', 'closed', 'withdrawn') AND party_id IS NOT NULL));
            CREATE OR REPLACE FUNCTION reject_closed_audit_assignment_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.status = 'closed' THEN
                    RAISE EXCEPTION 'Closed audit assignments are immutable' USING ERRCODE = '23514';
                END IF;
                IF OLD.status = 'withdrawn' THEN
                    RAISE EXCEPTION 'Withdrawn audit assignments are immutable' USING ERRCODE = '23514';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$;
            SQL);
    }

    /** Withdrawn engagement history requires a forward fix; rollback may only remove unused support. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE audit_assignments IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM audit_assignments WHERE status = 'withdrawn') THEN
                        RAISE EXCEPTION 'Withdrawn audit assignments require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_status;
                ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_recipient;
                ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_status CHECK (status IN ('offered', 'accepted', 'operations', 'completed', 'closed'));
                ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_recipient CHECK ((status IN ('operations', 'closed') AND party_id IS NULL) OR (status NOT IN ('operations', 'closed') AND party_id IS NOT NULL));
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
                SQL);
        });
    }
};
