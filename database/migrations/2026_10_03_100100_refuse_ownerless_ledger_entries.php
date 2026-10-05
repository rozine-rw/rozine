<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S4-A1 design closure §3 (#96 5968236797): `protect_ledger_entry` refuses a kind with no wallet owner
 * and a wallet that is not of the entry owner, instead of falling back to the Investor wallet lock.
 * Only the function body changes; rollback restores the previous body exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION protect_ledger_entry() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                entry_owner varchar := ledger_entry_wallet_owner(NEW.kind);
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Wallet and ledger records are immutable' USING ERRCODE = '23514';
                END IF;
                IF entry_owner = 'business' THEN
                    PERFORM 1 FROM business_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                ELSIF entry_owner = 'investor' THEN
                    PERFORM 1 FROM investor_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                ELSE
                    RAISE EXCEPTION 'A ledger entry kind needs a wallet owner' USING ERRCODE = '23514';
                END IF;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'A ledger entry needs a wallet of its own owner' USING ERRCODE = '23514';
                END IF;
                NEW.created_xid := pg_current_xact_id();
                RETURN NEW;
            END;
            $$;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
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
    }
};
