<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** A purchase receipt must identify the immutable purchase, not a requote or another commitment. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check_primary_confirmation_receipt(commitment_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM primary_commitments commitment
                        JOIN primary_reservation_versions version ON version.id = commitment.primary_reservation_version_id
                        JOIN primary_reservations reservation ON reservation.id = commitment.primary_reservation_id
                        JOIN command_operations operation ON operation.id = commitment.operation_id
                        WHERE commitment.id = commitment_id
                          AND operation.result @> jsonb_build_object(
                              'status', 'completed', 'code', 'RESERVATION_CONFIRMED',
                              'operation_id', commitment.operation_id, 'revision', version.revision,
                              'data', jsonb_build_object('reservation_id', reservation.id,
                                  'commitment_id', commitment.id, 'amount', reservation.principal::text))
                    ) THEN
                        RAISE EXCEPTION 'Primary confirmation receipt must identify the retained purchase' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM primary_commitments LOOP
                        PERFORM check_primary_confirmation_receipt(retained_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_primary_confirmation_receipt() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_confirmation_receipt(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_commitment_receipt_bound AFTER INSERT ON primary_commitments
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_confirmation_receipt();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_commitments) THEN
                        RAISE EXCEPTION 'Retained Primary confirmation receipts require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_commitment_receipt_bound ON primary_commitments;
                DROP FUNCTION require_primary_confirmation_receipt(), check_primary_confirmation_receipt(varchar);
                SQL);
        });
    }
};
