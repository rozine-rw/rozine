<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Receipt-to-purchase complement to 195022; replay must not invent a commitment. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check_primary_confirmation_operation(receipt_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM command_operations WHERE id = receipt_id AND result->>'code' = 'RESERVATION_CONFIRMED')
                        AND NOT EXISTS (
                            SELECT 1 FROM command_operations operation
                            JOIN primary_commitments commitment ON commitment.operation_id = operation.id
                            JOIN primary_reservation_versions version ON version.id = commitment.primary_reservation_version_id
                            JOIN primary_reservations reservation ON reservation.id = commitment.primary_reservation_id
                            WHERE operation.id = receipt_id
                              AND operation.command = 'primary.confirm'
                              AND operation.target_type = 'primary_reservation' AND operation.target_id = reservation.id
                              AND version.state = 'confirmed' AND version.operation_id = operation.id
                              AND version.primary_reservation_id = reservation.id
                              AND operation.result @> jsonb_build_object(
                                  'status', 'completed', 'code', 'RESERVATION_CONFIRMED',
                                  'operation_id', operation.id, 'revision', version.revision,
                                  'data', jsonb_build_object('reservation_id', reservation.id,
                                      'commitment_id', commitment.id, 'amount', reservation.principal::text))
                        ) THEN
                        RAISE EXCEPTION 'Confirmation operation requires its retained purchase' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DO $$
                DECLARE receipt_id varchar;
                BEGIN
                    FOR receipt_id IN SELECT id FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED' LOOP
                        PERFORM check_primary_confirmation_operation(receipt_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_primary_confirmation_operation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_confirmation_operation(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_confirmation_operation_bound AFTER INSERT ON command_operations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.result->>'code' = 'RESERVATION_CONFIRMED')
                    EXECUTE FUNCTION require_primary_confirmation_operation();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED') THEN
                        RAISE EXCEPTION 'Retained confirmation operations require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_confirmation_operation_bound ON command_operations;
                DROP FUNCTION require_primary_confirmation_operation(), check_primary_confirmation_operation(varchar);
                SQL);
        });
    }
};
