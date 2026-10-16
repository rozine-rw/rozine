<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BRS v2.0 SVC-R01, FEE-R01 and FEE-R02, ruled on #154 (16 Oct 2026): the 3% platform fee is taken
 * from the raise at disbursement and 40% of it is credited to the Audit Partner who verified the note.
 *
 * - `disbursements` carries the fee it was opened under. A disbursement opened before this rule has no
 *   fee version and a zero fee; one opened under `brs-2.0-fee-r01` must carry exactly 3% of its
 *   principal, rounded half up, and an Audit Partner share of exactly 40% of that fee.
 * - An intent now binds the principal less the fee: the Business receives what is left, and the
 *   return stays owed on the full principal. A funded failure's retained intent is checked the same way.
 * - `disbursement_platform_fee` is a Business-owned entry on the closing that issued the note. It moves
 *   the fee out of `disbursement_settlement` into `platform_fee_revenue` (Rozine's share) and
 *   `audit_partner_fee_payable` (the Audit Partner's share). It touches no Business bucket.
 * - `audit_partner_credits` names who the share is owed to: the author of the sealed report on the
 *   campaign's application, or nobody when no sealed report exists (a synthetic campaign), in which
 *   case the share stays unattributed in the payable account. Each fee entry needs its credit at
 *   commit, and each credit must repeat its disbursement's split and its entry's lines exactly.
 *
 * Rollback refuses once a disbursement carries a fee or its ledger effects exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursements, disbursement_intents, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;


                ALTER TABLE disbursements ADD COLUMN platform_fee numeric(12, 0) NOT NULL DEFAULT 0,
                    ADD COLUMN audit_partner_fee numeric(12, 0) NOT NULL DEFAULT 0, ADD COLUMN fee_version varchar(40);
                ALTER TABLE disbursements ALTER COLUMN platform_fee DROP DEFAULT, ALTER COLUMN audit_partner_fee DROP DEFAULT;
                ALTER TABLE disbursements ADD CONSTRAINT disbursement_platform_fee CHECK (
                    (fee_version IS NULL AND platform_fee = 0 AND audit_partner_fee = 0)
                    OR (COALESCE(fee_version = 'brs-2.0-fee-r01', false) AND platform_fee = round(amount * 0.03) AND audit_partner_fee = round(platform_fee * 0.4)));

                CREATE OR REPLACE FUNCTION protect_disbursement_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    folded record;
                    parent disbursements%ROWTYPE;
                    authorized_event disbursement_events%ROWTYPE;
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO parent FROM disbursements WHERE id = NEW.disbursement_id FOR UPDATE;
                    SELECT * INTO folded FROM disbursement_fold(NEW.disbursement_id);
                    SELECT * INTO authorized_event FROM disbursement_events WHERE disbursement_id = NEW.disbursement_id AND kind = 'authorized'
                        ORDER BY revision DESC LIMIT 1;
                    IF folded.f_state <> 'awaiting_second_approver' OR NEW.revision <> folded.f_revision
                        OR NEW.maker_user_id IS DISTINCT FROM folded.f_maker OR NEW.checker_user_id = folded.f_maker
                        OR NEW.amount <> parent.amount - parent.platform_fee OR NEW.currency <> parent.currency
                        OR NEW.commitments_digest <> parent.commitments_digest OR NEW.environment <> parent.environment
                        OR NEW.binding_sha256 IS DISTINCT FROM authorized_event.binding_sha256 OR NEW.destination_sha256 IS DISTINCT FROM authorized_event.destination_sha256 THEN
                        RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
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
                        'platform_fee_revenue', 'audit_partner_fee_payable') AND wallet_id IS NULL));

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
                        AND cause_type IS NULL AND cause_id IS NULL));

                CREATE TABLE audit_partner_credits (
                    id char(26) PRIMARY KEY,
                    disbursement_id char(26) NOT NULL CONSTRAINT audit_partner_credits_disbursement UNIQUE REFERENCES disbursements (id) ON DELETE RESTRICT,
                    ledger_entry_id char(26) NOT NULL CONSTRAINT audit_partner_credits_entry UNIQUE REFERENCES ledger_entries (id) ON DELETE RESTRICT,
                    audit_report_id char(26) REFERENCES audit_reports (id) ON DELETE RESTRICT,
                    auditor_party_id char(26) REFERENCES parties (id) ON DELETE RESTRICT,
                    kind varchar(24) NOT NULL,
                    amount numeric(12, 0) NOT NULL,
                    fee_version varchar(40) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT audit_partner_credit_facts CHECK (kind = 'platform_fee_share' AND amount > 0 AND fee_version = 'brs-2.0-fee-r01'
                        AND (audit_report_id IS NULL) = (auditor_party_id IS NULL))
                );
                CREATE INDEX audit_partner_credits_party ON audit_partner_credits (auditor_party_id, created_at);
                CREATE TRIGGER audit_partner_credits_immutable BEFORE UPDATE OR DELETE ON audit_partner_credits
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

                CREATE OR REPLACE FUNCTION assert_audit_partner_credit() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    parent disbursements%ROWTYPE;
                    entry ledger_entries%ROWTYPE;
                    settled numeric;
                    payable numeric;
                    revenue numeric;
                    line_count integer;
                BEGIN
                    SELECT * INTO parent FROM disbursements WHERE id = NEW.disbursement_id;
                    SELECT * INTO entry FROM ledger_entries WHERE id = NEW.ledger_entry_id;
                    SELECT count(*),
                        coalesce(sum(line.amount) FILTER (WHERE account.kind = 'disbursement_settlement' AND line.direction = 'debit'), 0),
                        coalesce(sum(line.amount) FILTER (WHERE account.kind = 'audit_partner_fee_payable' AND line.direction = 'credit'), 0),
                        coalesce(sum(line.amount) FILTER (WHERE account.kind = 'platform_fee_revenue' AND line.direction = 'credit'), 0)
                        INTO line_count, settled, payable, revenue
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = NEW.ledger_entry_id;
                    IF parent.fee_version IS DISTINCT FROM NEW.fee_version OR parent.audit_partner_fee <> NEW.amount
                        OR entry.kind IS DISTINCT FROM 'disbursement_platform_fee'
                        OR NOT EXISTS (SELECT 1 FROM disbursement_closings WHERE id = entry.source_id AND disbursement_id = parent.id AND kind = 'issued')
                        OR line_count <> 3 OR settled <> parent.platform_fee OR payable <> parent.audit_partner_fee
                        OR revenue <> parent.platform_fee - parent.audit_partner_fee THEN
                        RAISE EXCEPTION 'An Audit Partner credit must repeat its disbursement''s platform fee split and its ledger entry' USING ERRCODE = '23514';
                    END IF;
                    IF NEW.audit_report_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM audit_reports report JOIN business_campaigns campaign
                        ON campaign.business_application_id = report.application_id
                        WHERE report.id = NEW.audit_report_id AND report.author_party_id = NEW.auditor_party_id AND report.status = 'sealed'
                            AND campaign.id = parent.business_campaign_id) THEN
                        RAISE EXCEPTION 'An Audit Partner credit must name the author of the sealed report on the campaign''s application' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_partner_credits_bound BEFORE INSERT ON audit_partner_credits
                    FOR EACH ROW EXECUTE FUNCTION assert_audit_partner_credit();

                CREATE OR REPLACE FUNCTION require_audit_partner_credit() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.kind = 'disbursement_platform_fee' AND NOT EXISTS (SELECT 1 FROM audit_partner_credits WHERE ledger_entry_id = NEW.id) THEN
                        RAISE EXCEPTION 'A platform fee entry % needs its Audit Partner credit', NEW.id USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_platform_fee_credited AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_audit_partner_credit();


                DO $$ DECLARE definition text := pg_get_functiondef('check_primary_failed_closing_authority(varchar)'::regprocedure);
                BEGIN
                    IF array_length(string_to_array(definition, 'intent.amount IS DISTINCT FROM payment.amount OR'), 1) IS DISTINCT FROM 2 THEN
                        RAISE EXCEPTION 'Failed closing authority no longer binds the intent amount as expected' USING ERRCODE = '23514';
                    END IF;
                    EXECUTE replace(definition, 'intent.amount IS DISTINCT FROM payment.amount OR', 'intent.amount IS DISTINCT FROM payment.amount - payment.platform_fee OR');
                END; $$;
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursements, disbursement_intents, audit_partner_credits, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM disbursements WHERE fee_version IS NOT NULL) OR EXISTS (SELECT 1 FROM audit_partner_credits)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'disbursement_platform_fee')
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind IN ('platform_fee_revenue', 'audit_partner_fee_payable')) THEN
                        RAISE EXCEPTION 'Recorded platform fees require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DO $$ DECLARE definition text := pg_get_functiondef('check_primary_failed_closing_authority(varchar)'::regprocedure);
                BEGIN
                    IF array_length(string_to_array(definition, 'intent.amount IS DISTINCT FROM payment.amount - payment.platform_fee OR'), 1) IS DISTINCT FROM 2 THEN
                        RAISE EXCEPTION 'Failed closing authority no longer binds the intent amount as expected' USING ERRCODE = '23514';
                    END IF;
                    EXECUTE replace(definition, 'intent.amount IS DISTINCT FROM payment.amount - payment.platform_fee OR', 'intent.amount IS DISTINCT FROM payment.amount OR');
                END; $$;

                DROP TRIGGER ledger_entries_platform_fee_credited ON ledger_entries;
                DROP FUNCTION require_audit_partner_credit();
                DROP TABLE audit_partner_credits;
                DROP FUNCTION assert_audit_partner_credit();

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
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL));
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('business_available', 'business_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing')
                        AND wallet_id IS NULL));
                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                        WHEN 'business_withdrawal_hold' THEN 'business' WHEN 'business_withdrawal_payout' THEN 'business'
                        WHEN 'business_withdrawal_return' THEN 'business'
                    END
                $$;

                CREATE OR REPLACE FUNCTION protect_disbursement_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    folded record;
                    parent disbursements%ROWTYPE;
                    authorized_event disbursement_events%ROWTYPE;
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO parent FROM disbursements WHERE id = NEW.disbursement_id FOR UPDATE;
                    SELECT * INTO folded FROM disbursement_fold(NEW.disbursement_id);
                    SELECT * INTO authorized_event FROM disbursement_events WHERE disbursement_id = NEW.disbursement_id AND kind = 'authorized'
                        ORDER BY revision DESC LIMIT 1;
                    IF folded.f_state <> 'awaiting_second_approver' OR NEW.revision <> folded.f_revision
                        OR NEW.maker_user_id IS DISTINCT FROM folded.f_maker OR NEW.checker_user_id = folded.f_maker
                        OR NEW.amount <> parent.amount OR NEW.currency <> parent.currency OR NEW.commitments_digest <> parent.commitments_digest
                        OR NEW.environment <> parent.environment
                        OR NEW.binding_sha256 IS DISTINCT FROM authorized_event.binding_sha256 OR NEW.destination_sha256 IS DISTINCT FROM authorized_event.destination_sha256 THEN
                        RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;

                ALTER TABLE disbursements DROP CONSTRAINT disbursement_platform_fee;
                ALTER TABLE disbursements DROP COLUMN fee_version, DROP COLUMN audit_partner_fee, DROP COLUMN platform_fee;
                SQL);
        });
    }
};
