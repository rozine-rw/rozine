<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementPayoutLedger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA #663: a disbursement's payout gets its own ledger entry. Until now only the platform fee left
 * `disbursement_settlement`, so the net the Business was sent stayed there and no entry showed it.
 *
 * - `disbursement_payout` is a Business-owned entry on the closing that issued the note. It moves the
 *   closing intent's amount (the principal less the platform fee) out of `disbursement_settlement`
 *   into `withdrawal_clearing`, the provider's clearing account a paid withdrawal also goes to. It
 *   touches no Business bucket.
 * - A deferred check binds each entry to an issued closing of its Business, its intent's operation and
 *   amount; the ledger's unique source already allows one per closing.
 * - Closings already issued are posted now, the same way, once.
 *
 * Rollback refuses once a payout entry exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;

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
                        WHEN 'disbursement_payout' THEN 'business'
                    END
                $$;

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
                        AND cause_type = 'business_repayment' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$')
                    OR (kind = 'disbursement_payout' AND source_type = 'disbursement_closing' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL));

                CREATE OR REPLACE FUNCTION disbursement_payout_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    intent disbursement_intents%ROWTYPE;
                    payout_business varchar;
                    line_count integer;
                    debited numeric;
                    credited numeric;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS DISTINCT FROM 'disbursement_payout' THEN
                        RETURN;
                    END IF;
                    SELECT intents.* INTO intent FROM disbursement_closings closing
                        JOIN disbursement_intents intents ON intents.id = closing.intent_id
                        WHERE closing.id = entry.source_id AND closing.kind = 'issued';
                    SELECT disbursements.business_id INTO payout_business FROM disbursements WHERE disbursements.id = intent.disbursement_id;
                    IF intent.id IS NULL OR intent.operation_id IS DISTINCT FROM entry.origin_operation_id
                        OR NOT EXISTS (SELECT 1 FROM business_wallets WHERE id = entry.wallet_id AND business_id = payout_business) THEN
                        RAISE EXCEPTION 'Disbursement payout % must settle an issued closing of its Business and operation', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*),
                            coalesce(sum(line.amount) FILTER (WHERE line.direction = 'debit' AND account.kind = 'disbursement_settlement' AND account.wallet_id IS NULL), 0),
                            coalesce(sum(line.amount) FILTER (WHERE line.direction = 'credit' AND account.kind = 'withdrawal_clearing' AND account.wallet_id IS NULL), 0)
                        INTO line_count, debited, credited
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    IF line_count <> 2 OR debited <> intent.amount OR credited <> intent.amount THEN
                        RAISE EXCEPTION 'Disbursement payout % must move exactly its intent amount from settlement to clearing', checked_entry USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_disbursement_payout_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM disbursement_payout_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_disbursement_payout_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM disbursement_payout_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_disbursement_payout_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.kind = 'disbursement_payout')
                    EXECUTE FUNCTION assert_disbursement_payout_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_disbursement_payout_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_disbursement_payout_line_entry_bound();
                SQL);

            $issued = DB::table('disbursement_closings')->join('disbursement_intents', 'disbursement_intents.id', '=', 'disbursement_closings.intent_id')
                ->join('disbursements', 'disbursements.id', '=', 'disbursement_closings.disbursement_id')->where('disbursement_closings.kind', 'issued')
                ->orderBy('disbursement_closings.id')->get(['disbursement_closings.id', 'disbursements.business_id', 'disbursement_intents.operation_id', 'disbursement_intents.amount']);
            foreach ($issued as $closing) {
                app(DisbursementPayoutLedger::class)->post((string) $closing->business_id, (string) $closing->id, (string) $closing->operation_id, (string) $closing->amount);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'disbursement_payout') THEN
                        RAISE EXCEPTION 'Recorded disbursement payouts require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_disbursement_payout_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_disbursement_payout_bound ON ledger_entries;
                DROP FUNCTION assert_disbursement_payout_line_entry_bound();
                DROP FUNCTION assert_disbursement_payout_entry_bound();
                DROP FUNCTION disbursement_payout_entry_check(varchar);

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
                SQL);
        });
    }
};
