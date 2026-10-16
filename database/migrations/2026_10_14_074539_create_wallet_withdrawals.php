<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BRS v2.0 LED-R04: an Investor withdraws to their own verified account, under the shared ledger.
 *
 * - `wallet_withdrawals` records one immutable `wallet.withdraw` per operation: the wallet, the Party,
 *   the verified method paid to, the exact amount, the provider's charge, what reaches the account and
 *   the terms version. It is written with its dispatch outbox row before any provider is called.
 * - `withdrawal_hold` moves the amount from `investor_available` to the wallet's `investor_withdrawing`
 *   in the same transaction, so requested cash can never be spent twice; the existing overdraw check
 *   refuses a hold beyond the available balance. Exactly one hold binds each withdrawal.
 * - A verified provider outcome ends it once: `withdrawal_payout` moves the amount out to the platform
 *   `withdrawal_clearing` account after an applied success, and `withdrawal_return` moves it back to
 *   `investor_available` after an applied failure, never both.
 * - `wallet_withdrawal_events` keeps provider evidence append-only, as deposits do, and only one final
 *   outcome is ever applied per withdrawal.
 *
 * The installer takes ACCESS EXCLUSIVE locks on the altered ledger tables in the posting order (entry,
 * account, line); a writer always holds its wallet before them. Rollback refuses once a withdrawal or
 * its ledger effects exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;

                CREATE TABLE wallet_withdrawals (
                    id char(26) PRIMARY KEY,
                    wallet_id char(26) NOT NULL,
                    party_id char(26) NOT NULL,
                    method_id char(26) NOT NULL,
                    operation_id char(26) NOT NULL CONSTRAINT wallet_withdrawals_operation UNIQUE,
                    request_id uuid NOT NULL,
                    terms_version varchar(40) NOT NULL,
                    amount numeric(12, 0) NOT NULL,
                    provider_charge numeric(12, 0) NOT NULL,
                    received numeric(12, 0) NOT NULL,
                    currency char(3) NOT NULL,
                    provider varchar(40) NOT NULL,
                    provider_reference text NOT NULL,
                    provider_reference_sha256 char(64) NOT NULL CONSTRAINT wallet_withdrawals_reference UNIQUE,
                    payload text NOT NULL,
                    sha256 char(64) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT wallet_withdrawals_request UNIQUE (wallet_id, request_id),
                    CONSTRAINT wallet_withdrawal_amounts CHECK (currency = 'RWF' AND amount > 0 AND provider_charge >= 0 AND received > 0
                        AND amount = provider_charge + received),
                    CONSTRAINT wallet_withdrawal_wallet FOREIGN KEY (wallet_id, party_id)
                        REFERENCES investor_wallets (id, party_id) ON DELETE RESTRICT,
                    CONSTRAINT wallet_withdrawal_method FOREIGN KEY (method_id, party_id)
                        REFERENCES investor_funding_methods (id, party_id) ON DELETE RESTRICT,
                    CONSTRAINT wallet_withdrawal_operation FOREIGN KEY (operation_id)
                        REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED
                );
                CREATE INDEX wallet_withdrawals_wallet_order ON wallet_withdrawals (wallet_id, id);

                CREATE TABLE wallet_withdrawal_dispatches (
                    id char(26) PRIMARY KEY,
                    withdrawal_id char(26) NOT NULL REFERENCES wallet_withdrawals (id) ON DELETE RESTRICT,
                    phase varchar(16) NOT NULL CONSTRAINT withdrawal_dispatch_phase CHECK (phase IN ('queued', 'claimed', 'acknowledged', 'unacknowledged')),
                    created_at timestamptz NOT NULL,
                    CONSTRAINT wallet_withdrawal_dispatches_phase UNIQUE (withdrawal_id, phase)
                );
                CREATE UNIQUE INDEX wallet_withdrawal_dispatches_outcome ON wallet_withdrawal_dispatches (withdrawal_id)
                    WHERE phase IN ('acknowledged', 'unacknowledged');
                CREATE INDEX wallet_withdrawal_dispatches_claim ON wallet_withdrawal_dispatches (phase, withdrawal_id);

                CREATE TABLE wallet_withdrawal_events (
                    id char(26) PRIMARY KEY,
                    provider varchar(40) NOT NULL,
                    provider_event_id varchar(120) NOT NULL,
                    withdrawal_id char(26) NOT NULL REFERENCES wallet_withdrawals (id) ON DELETE RESTRICT,
                    content_sha256 char(64) NOT NULL,
                    state varchar(12) NOT NULL CONSTRAINT withdrawal_event_state CHECK (state IN ('pending', 'succeeded', 'failed', 'unknown')),
                    amount numeric(12, 0) NOT NULL,
                    currency char(3) NOT NULL,
                    environment varchar(20) NOT NULL,
                    observed_at timestamptz NOT NULL,
                    disposition varchar(16) NOT NULL CONSTRAINT withdrawal_event_disposition CHECK (
                        disposition IN ('applied', 'duplicate', 'after_final', 'conflict', 'mismatch', 'key_conflict')),
                    evidence text NOT NULL,
                    created_at timestamptz(6) NOT NULL,
                    CONSTRAINT withdrawal_event_facts CHECK (amount >= 0 AND currency ~ '^[A-Z]{3}$'),
                    CONSTRAINT wallet_withdrawal_events_content UNIQUE (provider, provider_event_id, content_sha256)
                );
                CREATE UNIQUE INDEX wallet_withdrawal_events_identity ON wallet_withdrawal_events (provider, provider_event_id)
                    WHERE disposition <> 'key_conflict';
                CREATE UNIQUE INDEX wallet_withdrawal_events_final ON wallet_withdrawal_events (withdrawal_id)
                    WHERE disposition = 'applied' AND state IN ('succeeded', 'failed');
                CREATE INDEX wallet_withdrawal_events_order ON wallet_withdrawal_events (withdrawal_id, id);

                CREATE OR REPLACE FUNCTION protect_wallet_withdrawal() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Withdrawals, their dispatches and provider events are immutable' USING ERRCODE = '23514';
                    END IF;
                    PERFORM 1 FROM investor_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                    IF NOT EXISTS (SELECT 1 FROM investor_funding_methods WHERE id = NEW.method_id AND party_id = NEW.party_id
                            AND verified_at IS NOT NULL AND revoked_at IS NULL) THEN
                        RAISE EXCEPTION 'A withdrawal goes only to the Investor''s own verified account' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER wallet_withdrawals_protected BEFORE INSERT OR UPDATE OR DELETE ON wallet_withdrawals
                    FOR EACH ROW EXECUTE FUNCTION protect_wallet_withdrawal();

                CREATE OR REPLACE FUNCTION protect_wallet_withdrawal_dispatch() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Withdrawals, their dispatches and provider events are immutable' USING ERRCODE = '23514';
                    END IF;
                    IF (NEW.phase = 'claimed' AND NOT EXISTS (SELECT 1 FROM wallet_withdrawal_dispatches WHERE withdrawal_id = NEW.withdrawal_id AND phase = 'queued'))
                        OR (NEW.phase IN ('acknowledged', 'unacknowledged')
                            AND NOT EXISTS (SELECT 1 FROM wallet_withdrawal_dispatches WHERE withdrawal_id = NEW.withdrawal_id AND phase = 'claimed')) THEN
                        RAISE EXCEPTION 'Dispatch phases must follow queued, claimed, outcome' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER wallet_withdrawal_dispatches_protected BEFORE INSERT OR UPDATE OR DELETE ON wallet_withdrawal_dispatches
                    FOR EACH ROW EXECUTE FUNCTION protect_wallet_withdrawal_dispatch();
                CREATE TRIGGER wallet_withdrawal_events_immutable BEFORE UPDATE OR DELETE ON wallet_withdrawal_events
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                    END
                $$;
                CREATE OR REPLACE FUNCTION ledger_account_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'investor_available' THEN 'investor' WHEN 'investor_held' THEN 'investor' WHEN 'investor_committed' THEN 'investor'
                        WHEN 'investor_withdrawing' THEN 'investor'
                        WHEN 'business_available' THEN 'business'
                    END
                $$;

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind = 'business_available' AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing')
                        AND wallet_id IS NULL));

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'business_repayment_debit' AND source_type = 'business_repayment' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return') AND source_type = 'wallet_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL));

                CREATE OR REPLACE FUNCTION wallet_withdrawal_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    withdrawal wallet_withdrawals%ROWTYPE;
                    source_kind varchar;
                    destination_kind varchar;
                    line_count integer;
                    debit_lines integer;
                    credit_lines integer;
                    debited numeric;
                    credited numeric;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS NULL OR entry.kind NOT IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return') THEN
                        RETURN;
                    END IF;
                    SELECT * INTO withdrawal FROM wallet_withdrawals WHERE id = entry.source_id;
                    IF withdrawal.id IS NULL OR withdrawal.wallet_id IS DISTINCT FROM entry.wallet_id
                        OR withdrawal.operation_id IS DISTINCT FROM entry.origin_operation_id THEN
                        RAISE EXCEPTION 'Withdrawal posting % must settle a recorded withdrawal of its wallet and operation', checked_entry USING ERRCODE = '23514';
                    END IF;
                    source_kind := CASE entry.kind WHEN 'withdrawal_hold' THEN 'investor_available' ELSE 'investor_withdrawing' END;
                    destination_kind := CASE entry.kind WHEN 'withdrawal_hold' THEN 'investor_withdrawing'
                        WHEN 'withdrawal_payout' THEN 'withdrawal_clearing' ELSE 'investor_available' END;
                    SELECT count(*),
                            count(*) FILTER (WHERE account.kind = source_kind AND account.wallet_id = entry.wallet_id AND line.direction = 'debit'),
                            count(*) FILTER (WHERE account.kind = destination_kind AND line.direction = 'credit'
                                AND CASE destination_kind WHEN 'withdrawal_clearing' THEN account.wallet_id IS NULL ELSE account.wallet_id = entry.wallet_id END),
                            coalesce(sum(line.amount) FILTER (WHERE line.direction = 'debit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE line.direction = 'credit'), 0)
                        INTO line_count, debit_lines, credit_lines, debited, credited
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    IF line_count <> 2 OR debit_lines <> 1 OR credit_lines <> 1 OR debited <> withdrawal.amount OR credited <> withdrawal.amount THEN
                        RAISE EXCEPTION 'Withdrawal posting % must move exactly its withdrawal amount between its own buckets', checked_entry USING ERRCODE = '23514';
                    END IF;
                    IF entry.kind = 'withdrawal_hold' THEN
                        RETURN;
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'withdrawal_hold' AND source_type = 'wallet_withdrawal' AND source_id = withdrawal.id)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind IN ('withdrawal_payout', 'withdrawal_return') AND source_type = 'wallet_withdrawal'
                            AND source_id = withdrawal.id AND id <> entry.id) THEN
                        RAISE EXCEPTION 'Withdrawal posting % must end its held withdrawal once: paid out or returned, never both', checked_entry USING ERRCODE = '23514';
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM wallet_withdrawal_events WHERE withdrawal_id = withdrawal.id AND disposition = 'applied'
                            AND state = CASE entry.kind WHEN 'withdrawal_payout' THEN 'succeeded' ELSE 'failed' END) THEN
                        RAISE EXCEPTION 'Withdrawal posting % must follow its applied provider outcome', checked_entry USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_wallet_withdrawal_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM wallet_withdrawal_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_wallet_withdrawal_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM wallet_withdrawal_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_wallet_withdrawal_hold() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT count(*) FROM ledger_entries WHERE kind = 'withdrawal_hold' AND source_type = 'wallet_withdrawal'
                            AND source_id = NEW.id) <> 1 THEN
                        RAISE EXCEPTION 'A withdrawal requires exactly one hold of its wallet''s available cash' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_withdrawal_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.kind IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return'))
                    EXECUTE FUNCTION assert_wallet_withdrawal_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_withdrawal_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_wallet_withdrawal_line_entry_bound();
                CREATE CONSTRAINT TRIGGER wallet_withdrawals_held AFTER INSERT ON wallet_withdrawals
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_wallet_withdrawal_hold();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                -- A withdrawal writer reads its Party and wallet, records its operation, then its withdrawal, then posts.
                -- Dropping the tables also locks the tables they reference, so take every lock up front in that order.
                LOCK TABLE parties, investor_wallets, investor_funding_methods, command_operations, wallet_withdrawals, wallet_withdrawal_dispatches,
                    wallet_withdrawal_events, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM wallet_withdrawals)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return'))
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind IN ('investor_withdrawing', 'withdrawal_clearing')) THEN
                        RAISE EXCEPTION 'Recorded withdrawals require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_withdrawal_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_withdrawal_bound ON ledger_entries;
                DROP TABLE wallet_withdrawal_events;
                DROP TABLE wallet_withdrawal_dispatches;
                DROP TABLE wallet_withdrawals;
                DROP FUNCTION require_wallet_withdrawal_hold();
                DROP FUNCTION assert_wallet_withdrawal_line_entry_bound();
                DROP FUNCTION assert_wallet_withdrawal_entry_bound();
                DROP FUNCTION wallet_withdrawal_entry_check(varchar);
                DROP FUNCTION protect_wallet_withdrawal_dispatch();
                DROP FUNCTION protect_wallet_withdrawal();

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'business_repayment_debit' AND source_type = 'business_repayment' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL));
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind = 'business_available' AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing') AND wallet_id IS NULL));
                CREATE OR REPLACE FUNCTION ledger_account_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'investor_available' THEN 'investor' WHEN 'investor_held' THEN 'investor' WHEN 'investor_committed' THEN 'investor'
                        WHEN 'business_available' THEN 'business'
                    END
                $$;
                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                    END
                $$;
                SQL);
        });
    }
};
