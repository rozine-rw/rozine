<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Terminal evidence, its cash movement and any observed expiry receipt must agree at commit. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles, primary_reservations,
                    primary_reservation_versions, primary_commitments, investor_wallets,
                    ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check_primary_terminal_cash(reservation_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    latest_state varchar;
                    committed boolean;
                    released boolean;
                BEGIN
                    SELECT state INTO latest_state FROM primary_reservation_versions
                        WHERE primary_reservation_id = reservation_id ORDER BY revision DESC LIMIT 1;
                    SELECT EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation'
                        AND source_id = reservation_id AND kind = 'primary_commit') INTO committed;
                    SELECT EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation'
                        AND source_id = reservation_id AND kind = 'primary_release') INTO released;
                    IF latest_state IS NULL
                        OR (latest_state = 'held' AND (committed OR released))
                        OR (latest_state = 'confirmed' AND (NOT committed OR released))
                        OR (latest_state IN ('released', 'expired') AND (NOT released OR committed)) THEN
                        RAISE EXCEPTION 'Primary terminal version and cash movement must agree' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM primary_reservations
                        UNION SELECT source_id FROM ledger_entries WHERE source_type = 'primary_reservation'
                    LOOP
                        PERFORM check_primary_terminal_cash(retained_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION check_primary_expiry_outcome(operation_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                BEGIN
                    IF operation_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM command_operations
                        WHERE id = operation_id AND result->>'status' = 'rejected' AND result->>'code' = 'RESERVATION_EXPIRED') THEN
                        RAISE EXCEPTION 'Primary actor expiry requires a rejected RESERVATION_EXPIRED outcome' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DO $$
                DECLARE retained_operation varchar;
                BEGIN
                    FOR retained_operation IN SELECT operation_id FROM primary_reservation_versions WHERE state = 'expired'
                    LOOP
                        PERFORM check_primary_expiry_outcome(retained_operation);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_primary_expiry_outcome() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_expiry_outcome(NEW.operation_id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_expiry_outcome_bound AFTER INSERT ON primary_reservation_versions
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.state = 'expired')
                    EXECUTE FUNCTION require_primary_expiry_outcome();
                CREATE OR REPLACE FUNCTION require_primary_terminal_cash() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_TABLE_NAME = 'primary_reservation_versions' THEN
                        PERFORM check_primary_terminal_cash(NEW.primary_reservation_id);
                    ELSE
                        PERFORM check_primary_terminal_cash(NEW.source_id);
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_version_cash_bound AFTER INSERT ON primary_reservation_versions
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_terminal_cash();
                CREATE CONSTRAINT TRIGGER ledger_primary_terminal_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.source_type = 'primary_reservation')
                    EXECUTE FUNCTION require_primary_terminal_cash();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, ledger_entries IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation') THEN
                        RAISE EXCEPTION 'Retained Primary terminal cash protection requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_expiry_outcome_bound ON primary_reservation_versions;
                DROP FUNCTION require_primary_expiry_outcome(), check_primary_expiry_outcome(varchar);
                DROP TRIGGER primary_version_cash_bound ON primary_reservation_versions;
                DROP TRIGGER ledger_primary_terminal_bound ON ledger_entries;
                DROP FUNCTION require_primary_terminal_cash(), check_primary_terminal_cash(varchar);
                SQL);
        });
    }
};
