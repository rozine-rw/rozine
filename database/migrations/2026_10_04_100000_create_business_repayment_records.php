<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S4-C1 (#96 5977479502): the Business side of a repayment, under the shared ledger.
 *
 * - `business_repayments` records one immutable `repayment.pay` per operation: the Business wallet,
 *   the acting Party, the note, the paid option, the exact amount and the servicing revision it
 *   was quoted against. Allocation to instalments and entitlements is the servicing domain's (S4-B).
 * - `business_repayment_debit` moves exactly that amount from the wallet's `business_available` to the
 *   platform `repayment_clearing` account. A deferred check binds each entry to its repayment and each
 *   repayment to exactly one such entry, so neither exists without the other or with another amount.
 *   The existing overdraw check refuses a debit beyond the available balance.
 * - `ledger_entry_wallet_owner` maps the new kind to `business`; `ledger_account_owner` admits the new
 *   platform account. No other ledger function is redefined.
 *
 * The installer takes ACCESS EXCLUSIVE locks on the altered ledger tables in the posting order (entry,
 * account, line); a writer always holds its wallet before them, so it never waits on a lock the
 * installer holds. Rollback refuses once a repayment or its ledger effects exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;

                CREATE TABLE business_repayments (
                    id char(26) PRIMARY KEY,
                    wallet_id char(26) NOT NULL,
                    business_id char(26) NOT NULL,
                    party_id char(26) NOT NULL REFERENCES parties (id) ON DELETE RESTRICT,
                    note_id char(26) NOT NULL CONSTRAINT business_repayment_note CHECK (note_id ~ '^[0-9a-hjkmnp-tv-z]{26}$'),
                    operation_id char(26) NOT NULL CONSTRAINT business_repayments_operation UNIQUE,
                    request_id uuid NOT NULL,
                    option varchar(20) NOT NULL CONSTRAINT business_repayment_option CHECK (option IN ('due_now', 'next_instalment')),
                    amount numeric(12, 0) NOT NULL CONSTRAINT business_repayment_amount CHECK (amount > 0),
                    servicing_revision integer NOT NULL CONSTRAINT business_repayment_revision CHECK (servicing_revision > 0),
                    payload text NOT NULL,
                    sha256 char(64) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_repayments_request UNIQUE (wallet_id, request_id),
                    CONSTRAINT business_repayment_wallet FOREIGN KEY (wallet_id, business_id)
                        REFERENCES business_wallets (id, business_id) ON DELETE RESTRICT,
                    CONSTRAINT business_repayment_operation FOREIGN KEY (operation_id)
                        REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED
                );
                CREATE INDEX business_repayments_note ON business_repayments (note_id, id);
                CREATE INDEX business_repayments_wallet_order ON business_repayments (wallet_id, id);
                CREATE TRIGGER business_repayments_immutable BEFORE UPDATE OR DELETE ON business_repayments
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                    END
                $$;

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind = 'business_available' AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing') AND wallet_id IS NULL));

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

                CREATE OR REPLACE FUNCTION business_repayment_debit_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    repayment business_repayments%ROWTYPE;
                    line_count integer;
                    debit_lines integer;
                    credit_lines integer;
                    debited numeric;
                    credited numeric;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS DISTINCT FROM 'business_repayment_debit' THEN
                        RETURN;
                    END IF;
                    SELECT * INTO repayment FROM business_repayments WHERE id = entry.source_id;
                    IF repayment.id IS NULL OR repayment.wallet_id IS DISTINCT FROM entry.wallet_id
                        OR repayment.operation_id IS DISTINCT FROM entry.origin_operation_id THEN
                        RAISE EXCEPTION 'Business repayment debit % must settle a recorded repayment of its wallet and operation', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*),
                            count(*) FILTER (WHERE account.kind = 'business_available' AND account.wallet_id = entry.wallet_id AND line.direction = 'debit'),
                            count(*) FILTER (WHERE account.kind = 'repayment_clearing' AND line.direction = 'credit'),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'business_available' AND line.direction = 'debit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'repayment_clearing' AND line.direction = 'credit'), 0)
                        INTO line_count, debit_lines, credit_lines, debited, credited
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    IF line_count <> 2 OR debit_lines <> 1 OR credit_lines <> 1 OR debited <> repayment.amount OR credited <> repayment.amount THEN
                        RAISE EXCEPTION 'Business repayment debit % must move exactly its repayment amount from available to repayment clearing', checked_entry
                            USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_business_repayment_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM business_repayment_debit_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_business_repayment_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM business_repayment_debit_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_business_repayment_debit() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT count(*) FROM ledger_entries WHERE kind = 'business_repayment_debit' AND source_type = 'business_repayment'
                            AND source_id = NEW.id) <> 1 THEN
                        RAISE EXCEPTION 'A Business repayment requires exactly one debit of its wallet' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_business_repayment_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.kind = 'business_repayment_debit')
                    EXECUTE FUNCTION assert_business_repayment_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_business_repayment_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_business_repayment_line_entry_bound();
                CREATE CONSTRAINT TRIGGER business_repayments_debited AFTER INSERT ON business_repayments
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_business_repayment_debit();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines, business_repayments IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM business_repayments) OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'business_repayment_debit')
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind = 'repayment_clearing') THEN
                        RAISE EXCEPTION 'Recorded Business repayments require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_business_repayment_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_business_repayment_bound ON ledger_entries;
                DROP TABLE business_repayments;
                DROP FUNCTION require_business_repayment_debit();
                DROP FUNCTION assert_business_repayment_line_entry_bound();
                DROP FUNCTION assert_business_repayment_entry_bound();
                DROP FUNCTION business_repayment_debit_entry_check(varchar);

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL));
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind = 'business_available' AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement') AND wallet_id IS NULL));
                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business'
                    END
                $$;
                SQL);
        });
    }
};
