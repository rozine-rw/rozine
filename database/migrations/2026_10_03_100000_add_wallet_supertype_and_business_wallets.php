<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S4-A1 Option C (#96 5874433972, conditions closed in 5968236797): one ledger, two wallet owners.
 *
 * - `wallets(id, owner)` is the supertype of `investor_wallets` and the new `business_wallets`.
 *   Each subtype row creates its supertype row after insert and binds to it by a deferred
 *   `(id, owner)` key; a deferred trigger on `wallets` requires exactly one subtype of its owner.
 *   `investor_wallets` keeps every existing column, key and caller: `ON CONFLICT DO NOTHING` paths
 *   are unchanged, because AFTER row triggers do not fire for a skipped row.
 * - Ledger accounts and entries derive `wallet_owner` from their kind through IMMUTABLE functions
 *   with no ELSE branch and bind `(wallet_id, wallet_owner)` to `wallets` with MATCH FULL, so a
 *   wallet kind can only reach its own owner and no NULL owner bypasses the key. System accounts
 *   keep both NULL.
 * - `protect_ledger_entry` locks the owning subtype row. Generated columns are computed after
 *   BEFORE triggers, so it maps the kind itself. No other ledger function is redefined.
 *
 * Both directions take ACCESS EXCLUSIVE locks up front in the posting order (wallet, entry, account),
 * so the installer waits behind an in-flight posting at its first table and never holds a table that
 * posting still needs; no later lock upgrade can deadlock against a wallet row lock.
 * Rollback refuses once a Business wallet exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE investor_wallets, ledger_entries, ledger_accounts IN ACCESS EXCLUSIVE MODE;

                CREATE TABLE wallets (
                    id char(26) PRIMARY KEY,
                    owner varchar(10) NOT NULL CONSTRAINT wallet_owner_kind CHECK (owner IN ('investor', 'business')),
                    created_at timestamptz NOT NULL,
                    CONSTRAINT wallets_identity UNIQUE (id, owner)
                );
                INSERT INTO wallets (id, owner, created_at) SELECT id, 'investor', created_at FROM investor_wallets;

                ALTER TABLE investor_wallets ADD COLUMN owner varchar(10) NOT NULL DEFAULT 'investor'
                    CONSTRAINT investor_wallet_owner CHECK (owner = 'investor');
                ALTER TABLE investor_wallets ADD CONSTRAINT investor_wallet_supertype FOREIGN KEY (id, owner)
                    REFERENCES wallets (id, owner) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;

                CREATE TABLE business_wallets (
                    id char(26) PRIMARY KEY,
                    owner varchar(10) NOT NULL DEFAULT 'business' CONSTRAINT business_wallet_owner CHECK (owner = 'business'),
                    business_id char(26) NOT NULL CONSTRAINT business_wallets_business_unique UNIQUE
                        REFERENCES business_profiles (id) ON DELETE RESTRICT,
                    currency char(3) NOT NULL CONSTRAINT business_wallet_currency CHECK (currency = 'RWF'),
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_wallet_supertype FOREIGN KEY (id, owner)
                        REFERENCES wallets (id, owner) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED
                );

                CREATE OR REPLACE FUNCTION create_wallet_supertype() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    INSERT INTO wallets (id, owner, created_at) VALUES (NEW.id, NEW.owner, NEW.created_at);
                    RETURN NULL;
                END;
                $$;
                CREATE TRIGGER investor_wallets_supertype AFTER INSERT ON investor_wallets
                    FOR EACH ROW EXECUTE FUNCTION create_wallet_supertype();
                CREATE TRIGGER business_wallets_supertype AFTER INSERT ON business_wallets
                    FOR EACH ROW EXECUTE FUNCTION create_wallet_supertype();
                CREATE TRIGGER business_wallets_immutable BEFORE UPDATE OR DELETE ON business_wallets
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();
                CREATE TRIGGER wallets_immutable BEFORE UPDATE OR DELETE ON wallets
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_ledger_mutation();

                CREATE OR REPLACE FUNCTION require_one_wallet_subtype() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT count(*) FROM investor_wallets WHERE id = NEW.id AND owner = NEW.owner)
                        + (SELECT count(*) FROM business_wallets WHERE id = NEW.id AND owner = NEW.owner) <> 1
                        OR (SELECT count(*) FROM investor_wallets WHERE id = NEW.id)
                        + (SELECT count(*) FROM business_wallets WHERE id = NEW.id) <> 1 THEN
                        RAISE EXCEPTION 'A wallet needs exactly one subtype of its own owner' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER wallets_one_subtype AFTER INSERT ON wallets
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_one_wallet_subtype();

                CREATE OR REPLACE FUNCTION ledger_account_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'investor_available' THEN 'investor' WHEN 'investor_held' THEN 'investor' WHEN 'investor_committed' THEN 'investor'
                        WHEN 'business_available' THEN 'business'
                    END
                $$;
                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business'
                    END
                $$;

                ALTER TABLE ledger_accounts ADD COLUMN wallet_owner varchar(10)
                    GENERATED ALWAYS AS (ledger_account_wallet_owner(kind)) STORED;
                ALTER TABLE ledger_entries ADD COLUMN wallet_owner varchar(10)
                    GENERATED ALWAYS AS (ledger_entry_wallet_owner(kind)) STORED;
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_accounts_wallet_id_foreign;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_wallet FOREIGN KEY (wallet_id, wallet_owner)
                    REFERENCES wallets (id, owner) MATCH FULL ON DELETE RESTRICT;
                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entries_wallet_id_foreign;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_wallet FOREIGN KEY (wallet_id, wallet_owner)
                    REFERENCES wallets (id, owner) MATCH FULL ON DELETE RESTRICT;
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind = 'business_available' AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement') AND wallet_id IS NULL));

                CREATE OR REPLACE FUNCTION protect_ledger_entry() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
                    END IF;
                    IF ledger_entry_wallet_owner(NEW.kind) = 'business' THEN
                        PERFORM 1 FROM business_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                    ELSE
                        PERFORM 1 FROM investor_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                    END IF;
                    NEW.created_xid := pg_current_xact_id();
                    RETURN NEW;
                END;
                $$;
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE investor_wallets, ledger_entries, ledger_accounts, business_wallets, wallets IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM business_wallets) THEN
                        RAISE EXCEPTION 'Business wallets exist and require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;

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
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement') AND wallet_id IS NULL));
                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_wallet;
                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_wallet;
                ALTER TABLE ledger_entries DROP COLUMN wallet_owner;
                ALTER TABLE ledger_accounts DROP COLUMN wallet_owner;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_wallet_id_foreign FOREIGN KEY (wallet_id)
                    REFERENCES investor_wallets (id) ON DELETE RESTRICT;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_accounts_wallet_id_foreign FOREIGN KEY (wallet_id)
                    REFERENCES investor_wallets (id) ON DELETE RESTRICT;
                DROP FUNCTION ledger_entry_wallet_owner(text);
                DROP FUNCTION ledger_account_wallet_owner(text);

                DROP TRIGGER investor_wallets_supertype ON investor_wallets;
                ALTER TABLE investor_wallets DROP CONSTRAINT investor_wallet_supertype;
                ALTER TABLE investor_wallets DROP COLUMN owner;
                DROP TABLE business_wallets;
                DROP TABLE wallets;
                DROP FUNCTION require_one_wallet_subtype();
                DROP FUNCTION create_wallet_supertype();
                SQL);
        });
    }
};
