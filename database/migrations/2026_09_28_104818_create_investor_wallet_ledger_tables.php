<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One wallet per Party is the per-Party lock gate. The journal is append-only: an entry and all of
 * its lines commit in one transaction, every entry balances (sum of debits = sum of credits) at
 * commit, and a later transaction can never add a line, even a balanced one, to a committed entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_wallets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique()->constrained('parties')->restrictOnDelete();
            $table->char('currency', 3);
            $table->timestampTz('created_at');
            $table->unique(['id', 'party_id']);
        });
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('wallet_id')->nullable()->constrained('investor_wallets')->restrictOnDelete();
            $table->string('kind', 40);
            $table->char('currency', 3);
            $table->timestampTz('created_at');
        });
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('wallet_id')->constrained('investor_wallets')->restrictOnDelete();
            $table->string('kind', 40);
            $table->string('source_type', 60);
            $table->ulid('source_id');
            $table->char('currency', 3);
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['kind', 'source_type', 'source_id']);
            $table->index(['wallet_id', 'id']);
        });
        Schema::create('ledger_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('entry_id')->constrained('ledger_entries')->restrictOnDelete();
            $table->foreignUlid('account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('direction', 6);
            $table->decimal('amount', 20, 0);
            $table->timestampTz('created_at');
            $table->index(['account_id', 'direction']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_wallets ADD CONSTRAINT investor_wallet_currency CHECK (currency = 'RWF');
            ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_currency CHECK (currency = 'RWF');
            ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                OR (kind IN ('deposit_clearing', 'deposit_fee_revenue') AND wallet_id IS NULL));
            CREATE UNIQUE INDEX ledger_accounts_wallet_kind ON ledger_accounts (wallet_id, kind) WHERE wallet_id IS NOT NULL;
            CREATE UNIQUE INDEX ledger_accounts_system_kind ON ledger_accounts (kind) WHERE wallet_id IS NULL;
            ALTER TABLE ledger_entries ADD COLUMN created_xid xid8 NOT NULL DEFAULT pg_current_xact_id();
            ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_currency CHECK (currency = 'RWF');
            ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent');
            ALTER TABLE ledger_lines ADD CONSTRAINT ledger_line_direction CHECK (direction IN ('debit', 'credit'));
            ALTER TABLE ledger_lines ADD CONSTRAINT ledger_line_amount CHECK (amount > 0 AND amount <= 99999999999999999999);

            CREATE OR REPLACE FUNCTION reject_wallet_ledger_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER investor_wallets_immutable BEFORE UPDATE OR DELETE ON investor_wallets
                FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();
            CREATE TRIGGER ledger_accounts_immutable BEFORE UPDATE OR DELETE ON ledger_accounts
                FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

            CREATE OR REPLACE FUNCTION protect_ledger_entry() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM investor_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                NEW.created_xid := pg_current_xact_id();
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER ledger_entries_protected BEFORE INSERT OR UPDATE OR DELETE ON ledger_entries
                FOR EACH ROW EXECUTE FUNCTION protect_ledger_entry();

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
            CREATE TRIGGER ledger_lines_protected BEFORE INSERT OR UPDATE OR DELETE ON ledger_lines
                FOR EACH ROW EXECUTE FUNCTION protect_ledger_line();

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
            CREATE CONSTRAINT TRIGGER ledger_entries_balanced AFTER INSERT ON ledger_entries
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_ledger_entry_balanced();
            SQL);
    }

    /** Recorded balances require a forward migration once any wallet exists. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE investor_wallets IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM investor_wallets) OR EXISTS (SELECT 1 FROM ledger_accounts) THEN
                        RAISE EXCEPTION 'Recorded wallets require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('ledger_lines');
            Schema::drop('ledger_entries');
            Schema::drop('ledger_accounts');
            Schema::drop('investor_wallets');
            DB::unprepared('DROP FUNCTION assert_ledger_entry_balanced(); DROP FUNCTION protect_ledger_line(); DROP FUNCTION protect_ledger_entry(); DROP FUNCTION reject_wallet_ledger_mutation();');
        });
    }
};
