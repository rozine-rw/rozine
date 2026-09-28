<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The primary issue posting (S3-D wallet extension, #96 5871859618). On a verified, reconciled
 * disbursement success each commitment's committed principal moves, as one balanced entry, from the
 * Investor's committed bucket to the system `disbursement_settlement` account, for exactly the
 * amount it committed. Issue and refund are mutually exclusive terminals of the same commitment.
 * The entry keeps the commitment's immutable originating operation and adds its own cause: the
 * disbursement closing that issued it. #172's per-source anchor and bucket-shape checks are
 * unchanged for every other kind; issue is added to them, never exempted.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
            ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement') AND wallet_id IS NULL));
            ALTER TABLE ledger_entries ADD COLUMN cause_type varchar(40);
            ALTER TABLE ledger_entries ADD COLUMN cause_id varchar(26);
            ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
            ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                    AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                    AND cause_type IS NULL AND cause_id IS NULL)
                OR (kind = 'primary_issue' AND source_type = 'primary_commitment' AND origin_operation_id IS NOT NULL
                    AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false)));

            CREATE OR REPLACE FUNCTION protect_primary_posting() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                anchor ledger_entries%ROWTYPE;
            BEGIN
                IF NEW.kind NOT IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund', 'primary_issue') THEN
                    RETURN NEW;
                END IF;
                IF NEW.kind = 'primary_hold' THEN
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = NEW.source_type AND source_id = NEW.source_id) THEN
                        RAISE EXCEPTION 'A primary hold must be the first posting for its source' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END IF;
                SELECT * INTO anchor FROM ledger_entries WHERE source_type = NEW.source_type AND source_id = NEW.source_id
                    AND kind = CASE WHEN NEW.kind IN ('primary_refund', 'primary_issue') THEN 'primary_commit' ELSE 'primary_hold' END;
                IF anchor.id IS NULL OR anchor.wallet_id <> NEW.wallet_id OR anchor.origin_operation_id <> NEW.origin_operation_id
                    OR (NEW.kind IN ('primary_commit', 'primary_release') AND EXISTS (SELECT 1 FROM ledger_entries
                        WHERE source_type = NEW.source_type AND source_id = NEW.source_id AND kind IN ('primary_commit', 'primary_release'))) THEN
                    RAISE EXCEPTION 'A primary posting must follow its open hold or commit on the same wallet and originating operation' USING ERRCODE = '23514';
                END IF;
                IF NEW.kind IN ('primary_refund', 'primary_issue') AND EXISTS (SELECT 1 FROM ledger_entries
                        WHERE source_type = NEW.source_type AND source_id = NEW.source_id AND kind IN ('primary_refund', 'primary_issue')) THEN
                    RAISE EXCEPTION 'A committed source ends once: issued or refunded, never both' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE FUNCTION ledger_entry_balance_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
            DECLARE
                line_count integer;
                debits numeric;
                credits numeric;
                entry_wallet varchar;
                entry ledger_entries%ROWTYPE;
                debit_lines integer;
                credit_lines integer;
                moved numeric;
                anchor_amount numeric;
            BEGIN
                SELECT count(*), coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0), coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)
                    INTO line_count, debits, credits FROM ledger_lines WHERE entry_id = checked_entry;
                IF line_count < 2 OR debits = 0 OR debits <> credits THEN
                    RAISE EXCEPTION 'Ledger entry % must balance: debits % credits %', checked_entry, debits, credits USING ERRCODE = '23514';
                END IF;
                SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                IF entry.kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund', 'primary_issue') THEN
                    SELECT count(*) FILTER (WHERE line.direction = 'debit' AND account.wallet_id = entry.wallet_id AND account.kind = CASE entry.kind
                            WHEN 'primary_hold' THEN 'investor_available' WHEN 'primary_refund' THEN 'investor_committed'
                            WHEN 'primary_issue' THEN 'investor_committed' ELSE 'investor_held' END),
                        count(*) FILTER (WHERE line.direction = 'credit' AND CASE entry.kind
                            WHEN 'primary_issue' THEN account.wallet_id IS NULL AND account.kind = 'disbursement_settlement'
                            ELSE account.wallet_id = entry.wallet_id AND account.kind = CASE entry.kind
                                WHEN 'primary_hold' THEN 'investor_held' WHEN 'primary_commit' THEN 'investor_committed' ELSE 'investor_available' END END),
                        max(line.amount) FILTER (WHERE line.direction = 'debit')
                        INTO debit_lines, credit_lines, moved
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    IF line_count <> 2 OR debit_lines <> 1 OR credit_lines <> 1 THEN
                        RAISE EXCEPTION 'Primary posting % must move one amount from its source bucket to its destination bucket', checked_entry USING ERRCODE = '23514';
                    END IF;
                    IF entry.kind <> 'primary_hold' THEN
                        SELECT anchor_line.amount INTO anchor_amount FROM ledger_entries anchor
                            JOIN ledger_lines anchor_line ON anchor_line.entry_id = anchor.id AND anchor_line.direction = 'debit'
                            WHERE anchor.source_type = entry.source_type AND anchor.source_id = entry.source_id
                                AND anchor.kind = CASE WHEN entry.kind IN ('primary_refund', 'primary_issue') THEN 'primary_commit' ELSE 'primary_hold' END;
                        IF anchor_amount IS NULL OR anchor_amount <> moved THEN
                            RAISE EXCEPTION 'Primary posting % must move exactly its source anchor amount % (moved %)', checked_entry, anchor_amount, moved USING ERRCODE = '23514';
                        END IF;
                    END IF;
                    IF entry.kind IN ('primary_refund', 'primary_issue') AND EXISTS (SELECT 1 FROM ledger_entries other
                            WHERE other.source_type = entry.source_type AND other.source_id = entry.source_id
                                AND other.kind IN ('primary_refund', 'primary_issue') AND other.id <> entry.id) THEN
                        RAISE EXCEPTION 'Primary posting % ends a source that already ended: issued or refunded, never both', checked_entry USING ERRCODE = '23514';
                    END IF;
                END IF;
                SELECT wallet_id INTO entry_wallet FROM ledger_entries WHERE id = checked_entry;
                IF EXISTS (SELECT 1 FROM ledger_accounts account WHERE account.wallet_id = entry_wallet
                    AND (SELECT coalesce(sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END), 0)
                        FROM ledger_lines line WHERE line.account_id = account.id) < 0) THEN
                    RAISE EXCEPTION 'Ledger entry % would overdraw an Investor bucket', checked_entry USING ERRCODE = '23514';
                END IF;
                PERFORM set_config('rozine.sealed_ledger_entries',
                    coalesce(nullif(current_setting('rozine.sealed_ledger_entries', true), ''), ',') || checked_entry || ',', true);
            END;
            $$;
            SQL);
    }

    /** Recorded issue postings require a forward migration; otherwise #172's rules are restored exactly. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'primary_issue')
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind = 'disbursement_settlement') THEN
                        RAISE EXCEPTION 'Recorded issue postings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            (require __DIR__.'/2026_09_28_140000_bind_primary_postings_to_their_source_anchor.php')->up();
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION protect_primary_posting() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    anchor ledger_entries%ROWTYPE;
                BEGIN
                    IF NEW.kind NOT IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund') THEN
                        RETURN NEW;
                    END IF;
                    IF NEW.kind = 'primary_hold' THEN
                        IF EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = NEW.source_type AND source_id = NEW.source_id) THEN
                            RAISE EXCEPTION 'A primary hold must be the first posting for its source' USING ERRCODE = '23514';
                        END IF;
                        RETURN NEW;
                    END IF;
                    SELECT * INTO anchor FROM ledger_entries WHERE source_type = NEW.source_type AND source_id = NEW.source_id
                        AND kind = CASE WHEN NEW.kind = 'primary_refund' THEN 'primary_commit' ELSE 'primary_hold' END;
                    IF anchor.id IS NULL OR anchor.wallet_id <> NEW.wallet_id OR anchor.origin_operation_id <> NEW.origin_operation_id
                        OR (NEW.kind IN ('primary_commit', 'primary_release') AND EXISTS (SELECT 1 FROM ledger_entries
                            WHERE source_type = NEW.source_type AND source_id = NEW.source_id AND kind IN ('primary_commit', 'primary_release'))) THEN
                        RAISE EXCEPTION 'A primary posting must follow its open hold or commit on the same wallet and originating operation' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent')
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL));
                ALTER TABLE ledger_entries DROP COLUMN cause_id;
                ALTER TABLE ledger_entries DROP COLUMN cause_type;
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue') AND wallet_id IS NULL));
                SQL);
        });
    }
};
