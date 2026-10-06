<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Replaying a rejected command must never disagree with retained live purchase evidence. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM (
                            SELECT origin_operation_id AS operation_id FROM primary_reservations
                            UNION SELECT operation_id FROM primary_reservation_versions WHERE state <> 'expired'
                        ) evidence LEFT JOIN command_operations operation ON operation.id = evidence.operation_id
                        WHERE operation.result->>'status' IS DISTINCT FROM 'completed'
                    ) THEN
                        RAISE EXCEPTION 'Retained Primary live evidence requires a completed command outcome' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_completed_primary_outcome() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    bound_operation_id varchar;
                BEGIN
                    IF TG_TABLE_NAME = 'primary_reservations' THEN
                        bound_operation_id := NEW.origin_operation_id;
                    ELSE
                        bound_operation_id := NEW.operation_id;
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM command_operations
                        WHERE id = bound_operation_id AND result->>'status' = 'completed') THEN
                        RAISE EXCEPTION 'Primary live evidence requires a completed command outcome' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_reservation_outcome_bound AFTER INSERT ON primary_reservations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_completed_primary_outcome();
                CREATE CONSTRAINT TRIGGER primary_version_outcome_bound AFTER INSERT ON primary_reservation_versions
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.state <> 'expired')
                    EXECUTE FUNCTION require_completed_primary_outcome();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations) THEN
                        RAISE EXCEPTION 'Retained Primary outcomes require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER primary_reservation_outcome_bound ON primary_reservations;
                DROP TRIGGER primary_version_outcome_bound ON primary_reservation_versions;
                DROP FUNCTION require_completed_primary_outcome();
                SQL);
        });
    }
};
