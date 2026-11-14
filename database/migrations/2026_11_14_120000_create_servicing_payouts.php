<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Investor payouts from a Business repayment (BRS v3 SVC-R03/R04, FEE-R01), as UAT's money
 * simulation posts them (Erastus, 14 Nov 2026) until the servicing domain (S4-C) replaces it.
 *
 * - `servicing_payouts` records, once per repayment and lot, what the units held when the repayment
 *   was collected received (SEC-R07): the instalments paid, principal, return, the earnings fee on the
 *   return and the net credited. Immutable.
 * - `servicing_payout` posts it: repayment clearing is debited the gross, the holder's
 *   `investor_available` is credited the net and `earnings_fee_revenue` the fee. A deferred check binds
 *   each entry to its payout row's wallet and amounts, and each payout row to exactly one entry.
 *
 * Rollback refuses once a payout exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;

                CREATE TABLE servicing_payouts (
                    id char(26) PRIMARY KEY,
                    repayment_id char(26) NOT NULL REFERENCES business_repayments (id) ON DELETE RESTRICT,
                    note_id char(26) NOT NULL,
                    lot_type varchar(20) NOT NULL CONSTRAINT servicing_payout_lot_type CHECK (lot_type IN ('primary_holding', 'secondary_transfer')),
                    lot_id char(26) NOT NULL,
                    party_id char(26) NOT NULL REFERENCES parties (id) ON DELETE RESTRICT,
                    wallet_id char(26) NOT NULL REFERENCES investor_wallets (id) ON DELETE RESTRICT,
                    units numeric(12, 0) NOT NULL CONSTRAINT servicing_payout_units CHECK (units > 0),
                    instalment_indexes jsonb NOT NULL,
                    principal numeric(12, 0) NOT NULL CONSTRAINT servicing_payout_principal CHECK (principal >= 0),
                    contractual_return numeric(12, 0) NOT NULL CONSTRAINT servicing_payout_return CHECK (contractual_return >= 0),
                    fee numeric(12, 0) NOT NULL CONSTRAINT servicing_payout_fee CHECK (fee >= 0 AND fee <= contractual_return),
                    net numeric(12, 0) NOT NULL,
                    rate_bps integer NOT NULL CONSTRAINT servicing_payout_rate CHECK (rate_bps BETWEEN 0 AND 10000),
                    policy_version varchar(40) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT servicing_payout_net CHECK (net = principal + contractual_return - fee AND net > 0),
                    CONSTRAINT servicing_payouts_once UNIQUE (repayment_id, lot_type, lot_id)
                );
                CREATE INDEX servicing_payouts_note ON servicing_payouts (note_id, id);
                CREATE INDEX servicing_payouts_wallet ON servicing_payouts (wallet_id, id);
                CREATE TRIGGER servicing_payouts_immutable BEFORE UPDATE OR DELETE ON servicing_payouts
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'secondary_hold' THEN 'investor' WHEN 'secondary_release' THEN 'investor'
                        WHEN 'secondary_purchase' THEN 'investor' WHEN 'secondary_sale' THEN 'investor'
                        WHEN 'servicing_payout' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                        WHEN 'business_withdrawal_hold' THEN 'business' WHEN 'business_withdrawal_payout' THEN 'business'
                        WHEN 'business_withdrawal_return' THEN 'business' WHEN 'disbursement_platform_fee' THEN 'business'
                    END
                $$;

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('business_available', 'business_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing',
                        'platform_fee_revenue', 'audit_partner_fee_payable', 'secondary_settlement', 'secondary_fee_revenue',
                        'earnings_fee_revenue') AND wallet_id IS NULL));

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
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('business_withdrawal_hold', 'business_withdrawal_payout', 'business_withdrawal_return') AND source_type = 'business_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'disbursement_platform_fee' AND source_type = 'disbursement_closing' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale') AND source_type = 'secondary_reservation'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'servicing_payout' AND source_type = 'servicing_payout' AND origin_operation_id IS NOT NULL
                        AND cause_type = 'business_repayment' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$'));

                CREATE OR REPLACE FUNCTION servicing_payout_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    payout servicing_payouts%ROWTYPE;
                    matched integer;
                    line_count integer;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS DISTINCT FROM 'servicing_payout' THEN
                        RETURN;
                    END IF;
                    SELECT * INTO payout FROM servicing_payouts WHERE id = entry.source_id;
                    IF payout.id IS NULL OR payout.wallet_id IS DISTINCT FROM entry.wallet_id OR payout.repayment_id IS DISTINCT FROM entry.cause_id THEN
                        RAISE EXCEPTION 'Servicing payout entry % must post a recorded payout to its wallet', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*),
                            count(*) FILTER (WHERE
                                (line.direction = 'debit' AND account.kind = 'repayment_clearing' AND account.wallet_id IS NULL
                                    AND line.amount = payout.principal + payout.contractual_return)
                                OR (line.direction = 'credit' AND account.kind = 'investor_available' AND account.wallet_id = entry.wallet_id
                                    AND line.amount = payout.net)
                                OR (line.direction = 'credit' AND account.kind = 'earnings_fee_revenue' AND account.wallet_id IS NULL
                                    AND line.amount = payout.fee))
                        INTO line_count, matched
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    IF matched <> line_count OR line_count <> (CASE WHEN payout.fee > 0 THEN 3 ELSE 2 END) THEN
                        RAISE EXCEPTION 'Servicing payout entry % must move exactly its payout from repayment clearing', checked_entry USING ERRCODE = '23514';
                    END IF;
                    IF (SELECT count(*) FROM ledger_entries WHERE kind = 'servicing_payout' AND source_type = 'servicing_payout' AND source_id = payout.id) <> 1 THEN
                        RAISE EXCEPTION 'Servicing payout % must be posted exactly once', payout.id USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_servicing_payout_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM servicing_payout_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_servicing_payout_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM servicing_payout_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_servicing_payout_posting() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT count(*) FROM ledger_entries WHERE kind = 'servicing_payout' AND source_type = 'servicing_payout' AND source_id = NEW.id) <> 1 THEN
                        RAISE EXCEPTION 'Servicing payout % must be posted exactly once', NEW.id USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_servicing_payout_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.kind = 'servicing_payout')
                    EXECUTE FUNCTION assert_servicing_payout_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_servicing_payout_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_servicing_payout_line_entry_bound();
                CREATE CONSTRAINT TRIGGER servicing_payouts_posted AFTER INSERT ON servicing_payouts
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_servicing_payout_posting();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE servicing_payouts, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM servicing_payouts) OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'servicing_payout')
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind = 'earnings_fee_revenue') THEN
                        RAISE EXCEPTION 'Recorded servicing payouts require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_servicing_payout_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_servicing_payout_bound ON ledger_entries;
                DROP TABLE servicing_payouts;
                DROP FUNCTION require_servicing_payout_posting();
                DROP FUNCTION assert_servicing_payout_line_entry_bound();
                DROP FUNCTION assert_servicing_payout_entry_bound();
                DROP FUNCTION servicing_payout_entry_check(varchar);

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
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('business_withdrawal_hold', 'business_withdrawal_payout', 'business_withdrawal_return') AND source_type = 'business_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'disbursement_platform_fee' AND source_type = 'disbursement_closing' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale') AND source_type = 'secondary_reservation'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL));

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('business_available', 'business_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing',
                        'platform_fee_revenue', 'audit_partner_fee_payable', 'secondary_settlement', 'secondary_fee_revenue') AND wallet_id IS NULL));

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'secondary_hold' THEN 'investor' WHEN 'secondary_release' THEN 'investor'
                        WHEN 'secondary_purchase' THEN 'investor' WHEN 'secondary_sale' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                        WHEN 'business_withdrawal_hold' THEN 'business' WHEN 'business_withdrawal_payout' THEN 'business'
                        WHEN 'business_withdrawal_return' THEN 'business' WHEN 'disbursement_platform_fee' THEN 'business'
                    END
                $$;
                SQL);
        });
    }
};
