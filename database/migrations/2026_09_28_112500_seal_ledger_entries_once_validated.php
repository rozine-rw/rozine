<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Closes a same-transaction gap (#96 5869021194). The entry-level balance check is a deferred
 * constraint trigger on the entry row; flushing it early with SET CONSTRAINTS ... IMMEDIATE used to
 * consume it, so a line appended afterwards in the same transaction queued no new check.
 *
 * Now every line insert queues its own deferred check of its entry, and a validated entry is
 * sealed for the rest of the transaction (a transaction-local setting lists validated entries):
 * any later line for it is refused at insert, balanced or not. Lines from a later transaction are
 * still refused by the creating-transaction seal. `SET CONSTRAINTS ALL IMMEDIATE` while an entry's
 * lines are still being written fails closed on that incomplete entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION ledger_entry_balance_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
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
            $$;

            CREATE OR REPLACE FUNCTION assert_ledger_entry_balanced() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                PERFORM ledger_entry_balance_check(NEW.id);
                RETURN NULL;
            END;
            $$;

            CREATE OR REPLACE FUNCTION assert_ledger_line_entry_balanced() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                PERFORM ledger_entry_balance_check(NEW.entry_id);
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER ledger_lines_entry_balanced AFTER INSERT ON ledger_lines
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_ledger_line_entry_balanced();

            CREATE OR REPLACE FUNCTION protect_ledger_line() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                entry ledger_entries%ROWTYPE;
                account ledger_accounts%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO entry FROM ledger_entries WHERE id = NEW.entry_id;
                IF entry.created_xid <> pg_current_xact_id() THEN
                    RAISE EXCEPTION 'Ledger entry is sealed: lines are only accepted in the transaction that created it' USING ERRCODE = '23514';
                END IF;
                IF coalesce(current_setting('rozine.sealed_ledger_entries', true), '') LIKE '%,' || NEW.entry_id || ',%' THEN
                    RAISE EXCEPTION 'Ledger entry is sealed: its balance was already validated in this transaction' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO account FROM ledger_accounts WHERE id = NEW.account_id;
                IF account.currency <> entry.currency OR (account.wallet_id IS NOT NULL AND account.wallet_id <> entry.wallet_id) THEN
                    RAISE EXCEPTION 'Ledger line account must belong to the entry wallet and currency' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            SQL);
    }

    /** Restores the entry-only check; integrity constraints carry no rows of their own. */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER ledger_lines_entry_balanced ON ledger_lines;
            DROP FUNCTION assert_ledger_line_entry_balanced();
            CREATE OR REPLACE FUNCTION assert_ledger_entry_balanced() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                line_count integer;
                debits numeric;
                credits numeric;
            BEGIN
                SELECT count(*), coalesce(sum(amount) FILTER (WHERE direction = 'debit'), 0), coalesce(sum(amount) FILTER (WHERE direction = 'credit'), 0)
                    INTO line_count, debits, credits FROM ledger_lines WHERE entry_id = NEW.id;
                IF line_count < 2 OR debits = 0 OR debits <> credits THEN
                    RAISE EXCEPTION 'Ledger entry % must balance: debits % credits %', NEW.id, debits, credits USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            DROP FUNCTION ledger_entry_balance_check(varchar);
            CREATE OR REPLACE FUNCTION protect_ledger_line() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                entry ledger_entries%ROWTYPE;
                account ledger_accounts%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO entry FROM ledger_entries WHERE id = NEW.entry_id;
                IF entry.created_xid <> pg_current_xact_id() THEN
                    RAISE EXCEPTION 'Ledger entry is sealed: lines are only accepted in the transaction that created it' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO account FROM ledger_accounts WHERE id = NEW.account_id;
                IF account.currency <> entry.currency OR (account.wallet_id IS NOT NULL AND account.wallet_id <> entry.wallet_id) THEN
                    RAISE EXCEPTION 'Ledger line account must belong to the entry wallet and currency' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            SQL);
    }
};
