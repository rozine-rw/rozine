<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Binds every Investor deposit credit to its intent at the database boundary (#96 5874790523 item 7).
 * The balance check alone let a balanced but orphaned or mispriced `deposit_credit` entry commit, and
 * the credit receipt trigger compared only the receipt to the intent, never the ledger lines.
 *
 * A retained `deposit_credit` entry must now, at commit, settle a recorded intent on that intent's
 * wallet; be exactly one clearing debit, one Investor available credit and, only when the intent
 * carries a fee, one fee revenue credit; move exactly the intent's gross, net and fee; and be bound
 * by exactly one credit receipt to an applied success whose amount and currency match the intent.
 *
 * The check is a deferred constraint trigger on the entry and on every line, like the balance
 * check, so a line written after an early flush queues it again. It takes no locks: every row it
 * reads is immutable once written. The install audits existing entries under table locks taken
 * in the provider callback's order, and refuses (rolling back every change) on any bad history.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE investor_wallets, wallet_deposit_intents, wallet_provider_events, ledger_entries, ledger_accounts, ledger_lines,
                    wallet_deposit_credits IN EXCLUSIVE MODE;

                CREATE OR REPLACE FUNCTION deposit_credit_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    intent wallet_deposit_intents%ROWTYPE;
                    line_count integer;
                    clearing_lines integer;
                    available_lines integer;
                    fee_lines integer;
                    expected_fee_lines integer;
                    gross numeric;
                    net numeric;
                    fee numeric;
                    bindings integer;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS DISTINCT FROM 'deposit_credit' THEN
                        RETURN;
                    END IF;
                    SELECT * INTO intent FROM wallet_deposit_intents WHERE id = entry.source_id;
                    IF intent.id IS NULL THEN
                        RAISE EXCEPTION 'Deposit credit entry % must settle a recorded deposit intent', checked_entry USING ERRCODE = '23514';
                    END IF;
                    IF intent.wallet_id IS DISTINCT FROM entry.wallet_id THEN
                        RAISE EXCEPTION 'Deposit credit entry % must post to its intent wallet', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*),
                            count(*) FILTER (WHERE account.kind = 'deposit_clearing' AND line.direction = 'debit'),
                            count(*) FILTER (WHERE account.kind = 'investor_available' AND line.direction = 'credit'),
                            count(*) FILTER (WHERE account.kind = 'deposit_fee_revenue' AND line.direction = 'credit'),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'deposit_clearing' AND line.direction = 'debit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'investor_available' AND line.direction = 'credit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'deposit_fee_revenue' AND line.direction = 'credit'), 0)
                        INTO line_count, clearing_lines, available_lines, fee_lines, gross, net, fee
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    expected_fee_lines := CASE WHEN intent.fee > 0 THEN 1 ELSE 0 END;
                    IF clearing_lines <> 1 OR available_lines <> 1 OR fee_lines <> expected_fee_lines OR line_count <> 2 + fee_lines THEN
                        RAISE EXCEPTION 'Deposit credit entry % must debit clearing once, credit available once and credit fee revenue only for a fee', checked_entry
                            USING ERRCODE = '23514';
                    END IF;
                    IF gross <> intent.amount THEN
                        RAISE EXCEPTION 'Deposit credit entry % must debit clearing by its intent gross % (debited %)', checked_entry, intent.amount, gross USING ERRCODE = '23514';
                    END IF;
                    IF net <> intent.credited THEN
                        RAISE EXCEPTION 'Deposit credit entry % must credit available by its intent net % (credited %)', checked_entry, intent.credited, net USING ERRCODE = '23514';
                    END IF;
                    IF fee <> intent.fee THEN
                        RAISE EXCEPTION 'Deposit credit entry % must credit fee revenue by its intent fee % (credited %)', checked_entry, intent.fee, fee USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*) INTO bindings FROM wallet_deposit_credits credit
                        JOIN wallet_provider_events event ON event.id = credit.provider_event_id
                        WHERE credit.ledger_entry_id = checked_entry AND event.amount = intent.amount AND event.currency = intent.currency;
                    IF bindings <> 1 THEN
                        RAISE EXCEPTION 'Deposit credit entry % must be bound to one applied success of its intent', checked_entry USING ERRCODE = '23514';
                    END IF;
                END;
                $$;

                DO $$
                DECLARE
                    retained record;
                BEGIN
                    FOR retained IN SELECT id FROM ledger_entries WHERE kind = 'deposit_credit' ORDER BY id LOOP
                        PERFORM deposit_credit_entry_check(retained.id);
                    END LOOP;
                END;
                $$;

                CREATE OR REPLACE FUNCTION assert_deposit_credit_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM deposit_credit_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_deposit_credit_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_deposit_credit_entry_bound();

                CREATE OR REPLACE FUNCTION assert_deposit_credit_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM deposit_credit_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_deposit_credit_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_deposit_credit_line_entry_bound();
                SQL);
        });
    }

    /** Drops only what this migration added; no earlier function was replaced, and the checks carry no rows of their own. */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER ledger_lines_entry_deposit_credit_bound ON ledger_lines;
            DROP FUNCTION assert_deposit_credit_line_entry_bound();
            DROP TRIGGER ledger_entries_deposit_credit_bound ON ledger_entries;
            DROP FUNCTION assert_deposit_credit_entry_bound();
            DROP FUNCTION deposit_credit_entry_check(varchar);
            SQL);
    }
};
