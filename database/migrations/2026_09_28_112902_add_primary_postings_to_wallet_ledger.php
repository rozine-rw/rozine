<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Primary purchase postings (S3-C port): available → held, held → committed, held → available and
 * committed → available, each one balanced immutable entry per (kind, source). A posting binds its
 * reservation or commitment source to the operation that originated it; later movements must follow
 * the hold (or, for a refund, the commit) on the same wallet under the same originating operation,
 * and a hold ends exactly once, committed or released. At commit no Investor bucket may be negative.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE ledger_entries ADD COLUMN origin_operation_id varchar(26);
            ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
            ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent')
                OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                    AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL));
            CREATE INDEX ledger_entries_source ON ledger_entries (source_type, source_id);

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
            CREATE TRIGGER ledger_entries_primary_lifecycle BEFORE INSERT ON ledger_entries
                FOR EACH ROW EXECUTE FUNCTION protect_primary_posting();

            CREATE OR REPLACE FUNCTION ledger_entry_balance_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
            DECLARE
                line_count integer;
                debits numeric;
                credits numeric;
                entry_wallet varchar;
            BEGIN
                SELECT count(*), coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0), coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)
                    INTO line_count, debits, credits FROM ledger_lines WHERE entry_id = checked_entry;
                IF line_count < 2 OR debits = 0 OR debits <> credits THEN
                    RAISE EXCEPTION 'Ledger entry % must balance: debits % credits %', checked_entry, debits, credits USING ERRCODE = '23514';
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

    /** Recorded primary postings require a forward migration; rollback is refused once any exist. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind <> 'deposit_credit') THEN
                        RAISE EXCEPTION 'Recorded primary postings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER ledger_entries_primary_lifecycle ON ledger_entries;
                DROP FUNCTION protect_primary_posting();
                DROP INDEX ledger_entries_source;
                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent');
                ALTER TABLE ledger_entries DROP COLUMN origin_operation_id;
                CREATE OR REPLACE FUNCTION ledger_entry_balance_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $fn$
                DECLARE
                    line_count integer;
                    debits numeric;
                    credits numeric;
                BEGIN
                    SELECT count(*), coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0), coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)
                        INTO line_count, debits, credits FROM ledger_lines WHERE entry_id = checked_entry;
                    IF line_count < 2 OR debits = 0 OR debits <> credits THEN
                        RAISE EXCEPTION 'Ledger entry % must balance: debits % credits %', checked_entry, debits, credits USING ERRCODE = '23514';
                    END IF;
                    PERFORM set_config('rozine.sealed_ledger_entries',
                        coalesce(nullif(current_setting('rozine.sealed_ledger_entries', true), ''), ',') || checked_entry || ',', true);
                END;
                $fn$;
                SQL);
        });
    }
};
