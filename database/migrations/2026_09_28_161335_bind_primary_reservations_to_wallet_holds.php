<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** A retained reservation and its exact cash hold must survive the same outer commit. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles, business_campaigns, primary_reservations,
                    investor_wallets, ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;

                CREATE OR REPLACE FUNCTION check_primary_hold_binding(reservation_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    reservation primary_reservations%ROWTYPE;
                    hold_id varchar;
                BEGIN
                    SELECT * INTO reservation FROM primary_reservations WHERE id = reservation_id;
                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Primary posting requires its retained reservation' USING ERRCODE = '23514';
                    END IF;
                    SELECT entry.id INTO hold_id FROM ledger_entries entry
                        JOIN investor_wallets wallet ON wallet.id = entry.wallet_id
                        WHERE entry.kind = 'primary_hold' AND entry.source_type = 'primary_reservation'
                            AND entry.source_id = reservation.id AND wallet.party_id = reservation.party_id
                            AND entry.origin_operation_id = reservation.origin_operation_id;
                    IF hold_id IS NULL THEN
                        RAISE EXCEPTION 'Primary reservation requires its matching Party wallet hold and origin operation' USING ERRCODE = '23514';
                    END IF;
                    IF EXISTS (
                        SELECT entry.id FROM ledger_entries entry
                        JOIN investor_wallets wallet ON wallet.id = entry.wallet_id
                        LEFT JOIN ledger_lines line ON line.entry_id = entry.id
                        LEFT JOIN ledger_accounts account ON account.id = line.account_id
                        WHERE entry.source_type = 'primary_reservation' AND entry.source_id = reservation.id
                        GROUP BY entry.id, wallet.party_id
                        HAVING wallet.party_id IS DISTINCT FROM reservation.party_id
                            OR entry.origin_operation_id IS DISTINCT FROM reservation.origin_operation_id
                            OR count(line.id) <> 2
                            OR count(line.id) FILTER (WHERE line.direction = 'debit') <> 1
                            OR count(line.id) FILTER (WHERE line.direction = 'credit') <> 1
                            OR sum(line.amount) FILTER (WHERE line.direction = 'debit') IS DISTINCT FROM reservation.principal
                            OR sum(line.amount) FILTER (WHERE line.direction = 'credit') IS DISTINCT FROM reservation.principal
                            OR (entry.kind = 'primary_hold' AND (
                                count(line.id) FILTER (WHERE line.direction = 'debit' AND account.kind = 'investor_available' AND account.wallet_id = entry.wallet_id) <> 1
                                OR count(line.id) FILTER (WHERE line.direction = 'credit' AND account.kind = 'investor_held' AND account.wallet_id = entry.wallet_id) <> 1))
                    ) THEN
                        RAISE EXCEPTION 'Primary wallet posting must bind the reservation Party origin and exact principal' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;

                DO $$
                DECLARE
                    retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM primary_reservations
                        UNION SELECT source_id FROM ledger_entries WHERE source_type = 'primary_reservation'
                    LOOP
                        PERFORM check_primary_hold_binding(retained_id);
                    END LOOP;
                END;
                $$;

                CREATE OR REPLACE FUNCTION require_primary_hold_binding() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_TABLE_NAME = 'primary_reservations' THEN
                        PERFORM check_primary_hold_binding(NEW.id);
                    ELSE
                        PERFORM check_primary_hold_binding(NEW.source_id);
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_reservation_wallet_bound AFTER INSERT ON primary_reservations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_hold_binding();
                CREATE CONSTRAINT TRIGGER ledger_primary_reservation_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.source_type = 'primary_reservation')
                    EXECUTE FUNCTION require_primary_hold_binding();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, ledger_entries IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation') THEN
                        RAISE EXCEPTION 'Recorded Primary wallet bindings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER primary_reservation_wallet_bound ON primary_reservations;
                DROP TRIGGER ledger_primary_reservation_bound ON ledger_entries;
                DROP FUNCTION require_primary_hold_binding();
                DROP FUNCTION check_primary_hold_binding(varchar);
                SQL);
        });
    }
};
