<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Successful actor refund receipts require the exact retained purchase and original cash return.
 * Only the journal is altered. Its lock serializes receipt inserts with the historical audit;
 * immutable Primary and cash dependencies are read without blocking their writers or taking row locks.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check_primary_refund_receipt(checked_operation varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    operation command_operations%ROWTYPE;
                    root primary_reservations%ROWTYPE;
                    version primary_reservation_versions%ROWTYPE;
                    commitment primary_commitments%ROWTYPE;
                    refunded ledger_entries%ROWTYPE;
                    entry ledger_entries%ROWTYPE;
                BEGIN
                    SELECT * INTO operation FROM command_operations WHERE id = checked_operation;
                    IF operation.command IS DISTINCT FROM 'primary.refund' THEN
                        RETURN;
                    END IF;
                    IF operation.result->>'status' = 'rejected' AND operation.result->>'code' IS DISTINCT FROM 'COMMITMENT_REFUNDED' THEN
                        RETURN;
                    END IF;
                    SELECT * INTO root FROM primary_reservations WHERE id = operation.target_id;
                    SELECT * INTO version FROM primary_reservation_versions WHERE primary_reservation_id = root.id ORDER BY revision DESC LIMIT 1;
                    SELECT * INTO commitment FROM primary_commitments WHERE primary_reservation_id = root.id;
                    SELECT * INTO refunded FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = root.id AND kind = 'primary_refund';
                    IF root.id IS NULL OR version.state IS DISTINCT FROM 'confirmed' OR commitment.id IS NULL OR refunded.id IS NULL
                        OR operation.target_type IS DISTINCT FROM 'primary_reservation'
                        OR operation.actor_key IS DISTINCT FROM 'party:' || root.party_id
                        OR NOT EXISTS (SELECT 1 FROM users WHERE id = operation.actor_user_id AND party_id = root.party_id)
                        OR commitment.primary_reservation_version_id IS DISTINCT FROM version.id
                        OR commitment.operation_id IS DISTINCT FROM version.operation_id
                        OR commitment.confirmed_at IS DISTINCT FROM version.created_at
                        OR NOT (operation.result @> jsonb_build_object('operation_id', operation.id,
                            'status', 'completed', 'code', 'COMMITMENT_REFUNDED', 'revision', version.revision,
                            'data', jsonb_build_object('reservation_id', root.id, 'commitment_id', commitment.id,
                                'entry_id', refunded.id, 'origin_operation_id', root.origin_operation_id,
                                'amount', root.principal::text, 'currency', 'RWF', 'fee', '0')))
                        OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id = refunded.wallet_id AND party_id = root.party_id AND currency = 'RWF') THEN
                        RAISE EXCEPTION 'Primary refund receipt requires its exact retained purchase and cash return' USING ERRCODE = '23514';
                    END IF;
                    IF (SELECT count(*) FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = root.id) <> 3 THEN
                        RAISE EXCEPTION 'Primary refund receipt requires complete original cash' USING ERRCODE = '23514';
                    END IF;
                    FOR entry IN SELECT * FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = root.id LOOP
                        IF entry.kind NOT IN ('primary_hold', 'primary_commit', 'primary_refund')
                            OR entry.wallet_id IS DISTINCT FROM refunded.wallet_id OR entry.currency <> 'RWF'
                            OR entry.origin_operation_id IS DISTINCT FROM root.origin_operation_id
                            OR (SELECT count(*) FROM ledger_lines WHERE entry_id = entry.id) <> 2
                            OR (SELECT count(*) FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND account.wallet_id = refunded.wallet_id AND account.currency = 'RWF'
                                    AND line.amount = root.principal AND line.direction = 'debit' AND account.kind = CASE entry.kind
                                        WHEN 'primary_hold' THEN 'investor_available' WHEN 'primary_commit' THEN 'investor_held' ELSE 'investor_committed' END) <> 1
                            OR (SELECT count(*) FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND account.wallet_id = refunded.wallet_id AND account.currency = 'RWF'
                                    AND line.amount = root.principal AND line.direction = 'credit' AND account.kind = CASE entry.kind
                                        WHEN 'primary_hold' THEN 'investor_held' WHEN 'primary_commit' THEN 'investor_committed' ELSE 'investor_available' END) <> 1 THEN
                            RAISE EXCEPTION 'Primary refund receipt cash must return its exact original principal' USING ERRCODE = '23514';
                        END IF;
                    END LOOP;
                END;
                $$;
                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM command_operations WHERE command = 'primary.refund' LOOP
                        PERFORM check_primary_refund_receipt(retained_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_primary_refund_receipt() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_refund_receipt(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_refund_receipt_bound AFTER INSERT ON command_operations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.command = 'primary.refund')
                    EXECUTE FUNCTION require_primary_refund_receipt();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE command_operations IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM command_operations WHERE command = 'primary.refund') THEN
                        RAISE EXCEPTION 'Retained Primary refund receipts require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_refund_receipt_bound ON command_operations;
                DROP FUNCTION require_primary_refund_receipt(), check_primary_refund_receipt(varchar);
                SQL);
        });
    }
};
