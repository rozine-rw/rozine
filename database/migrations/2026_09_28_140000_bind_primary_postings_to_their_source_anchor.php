<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Binds each primary movement to its own source anchor at the database boundary (#96 5871460536).
 * The deferred complete-entry check now requires a primary posting to be exactly one debit of its
 * source bucket and one credit of its destination bucket on the entry's wallet, and a commit,
 * release or refund to move exactly the amount of the hold (or, for a refund, the commit) it
 * follows. A partial or excessive movement can no longer consume or strand another source's funds.
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
                IF entry.kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund') THEN
                    SELECT count(*) FILTER (WHERE line.direction = 'debit' AND account.wallet_id = entry.wallet_id AND account.kind = CASE entry.kind
                            WHEN 'primary_hold' THEN 'investor_available' WHEN 'primary_refund' THEN 'investor_committed' ELSE 'investor_held' END),
                        count(*) FILTER (WHERE line.direction = 'credit' AND account.wallet_id = entry.wallet_id AND account.kind = CASE entry.kind
                            WHEN 'primary_hold' THEN 'investor_held' WHEN 'primary_commit' THEN 'investor_committed' ELSE 'investor_available' END),
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
                                AND anchor.kind = CASE WHEN entry.kind = 'primary_refund' THEN 'primary_commit' ELSE 'primary_hold' END;
                        IF anchor_amount IS NULL OR anchor_amount <> moved THEN
                            RAISE EXCEPTION 'Primary posting % must move exactly its source anchor amount % (moved %)', checked_entry, anchor_amount, moved USING ERRCODE = '23514';
                        END IF;
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

    /** Restores the aggregate-only check; integrity constraints carry no rows of their own. */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
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
};
