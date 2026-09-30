<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Every closure retains complete original cash returns before accepted exposure is released. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared('LOCK TABLE business_profiles, business_campaigns, business_campaign_closures, primary_reservations, primary_reservation_versions, primary_commitments, investor_wallets, ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE');
            Schema::create('primary_campaign_closure_returns', function (Blueprint $table): void {
                $table->ulid('primary_reservation_id')->primary();
                $table->ulid('business_campaign_closure_id')->index();
                $table->foreignUlid('primary_reservation_version_id')->constrained('primary_reservation_versions')->restrictOnDelete();
                $table->char('version_sha256', 64);
                $table->foreignUlid('primary_commitment_id')->nullable()->constrained('primary_commitments')->restrictOnDelete();
                $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
                $table->foreignUlid('wallet_id')->constrained('investor_wallets')->restrictOnDelete();
                $table->decimal('principal', 9, 0);
                $table->ulid('origin_operation_id');
                $table->foreignUlid('hold_entry_id')->constrained('ledger_entries')->restrictOnDelete();
                $table->foreignUlid('commit_entry_id')->nullable()->constrained('ledger_entries')->restrictOnDelete();
                $table->foreignUlid('return_entry_id')->unique()->constrained('ledger_entries')->restrictOnDelete();
                $table->string('return_kind', 20);
            });
            DB::unprepared(<<<'SQL'
                ALTER TABLE primary_campaign_closure_returns ADD CONSTRAINT primary_closure_return_parent
                    FOREIGN KEY (business_campaign_closure_id) REFERENCES business_campaign_closures (id)
                    ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
                ALTER TABLE primary_campaign_closure_returns ADD CONSTRAINT primary_closure_return_reservation
                    FOREIGN KEY (primary_reservation_id) REFERENCES primary_reservations (id) ON DELETE RESTRICT;
                ALTER TABLE primary_campaign_closure_returns ADD CONSTRAINT primary_closure_return_kind CHECK (
                    (return_kind = 'primary_refund' AND primary_commitment_id IS NOT NULL AND commit_entry_id IS NOT NULL)
                    OR (return_kind = 'primary_release' AND primary_commitment_id IS NULL AND commit_entry_id IS NULL));

                CREATE OR REPLACE FUNCTION require_primary_closure_read_committed() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF current_setting('transaction_isolation') <> 'read committed' THEN
                        RAISE EXCEPTION 'Campaign cash-return closure requires READ COMMITTED' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_campaign_closure_isolation BEFORE INSERT ON business_campaign_closures
                    FOR EACH ROW EXECUTE FUNCTION require_primary_closure_read_committed();
                CREATE OR REPLACE FUNCTION check_primary_closure_return(checked_reservation varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    binding primary_campaign_closure_returns%ROWTYPE;
                    root primary_reservations%ROWTYPE;
                    version primary_reservation_versions%ROWTYPE;
                    commitment primary_commitments%ROWTYPE;
                    closure business_campaign_closures%ROWTYPE;
                    entry ledger_entries%ROWTYPE;
                BEGIN
                    SELECT * INTO binding FROM primary_campaign_closure_returns WHERE primary_reservation_id = checked_reservation;
                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Campaign closure requires every original cash return' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO closure FROM business_campaign_closures WHERE id = binding.business_campaign_closure_id;
                    SELECT * INTO root FROM primary_reservations WHERE id = checked_reservation;
                    SELECT * INTO version FROM primary_reservation_versions WHERE primary_reservation_id = root.id ORDER BY revision DESC LIMIT 1;
                    SELECT * INTO commitment FROM primary_commitments WHERE primary_reservation_id = root.id;
                    IF closure.id IS NULL OR root.business_campaign_id IS DISTINCT FROM closure.business_campaign_id
                        OR binding.primary_reservation_version_id IS DISTINCT FROM version.id OR binding.version_sha256 IS DISTINCT FROM version.sha256
                        OR binding.party_id IS DISTINCT FROM root.party_id OR binding.principal IS DISTINCT FROM root.principal
                        OR binding.origin_operation_id IS DISTINCT FROM root.origin_operation_id
                        OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id = binding.wallet_id AND party_id = root.party_id AND currency = 'RWF')
                        OR (version.state = 'confirmed' AND (binding.return_kind <> 'primary_refund'
                            OR binding.primary_commitment_id IS DISTINCT FROM commitment.id
                            OR commitment.primary_reservation_version_id IS DISTINCT FROM version.id
                            OR commitment.operation_id IS DISTINCT FROM version.operation_id OR commitment.confirmed_at IS DISTINCT FROM version.created_at))
                        OR (version.state IN ('released', 'expired') AND (binding.return_kind <> 'primary_release' OR commitment.id IS NOT NULL))
                        OR version.state NOT IN ('confirmed', 'released', 'expired') THEN
                        RAISE EXCEPTION 'Campaign closure return must bind its exact retained purchase and terminal state' USING ERRCODE = '23514';
                    END IF;
                    IF (SELECT count(*) FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = root.id)
                        <> (CASE WHEN binding.return_kind = 'primary_refund' THEN 3 ELSE 2 END) THEN
                        RAISE EXCEPTION 'Campaign closure requires complete original cash' USING ERRCODE = '23514';
                    END IF;
                    FOR entry IN SELECT * FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = root.id LOOP
                        IF entry.wallet_id IS DISTINCT FROM binding.wallet_id OR entry.origin_operation_id IS DISTINCT FROM root.origin_operation_id
                            OR entry.currency <> 'RWF'
                            OR entry.id IS DISTINCT FROM (CASE entry.kind WHEN 'primary_hold' THEN binding.hold_entry_id
                                WHEN 'primary_commit' THEN binding.commit_entry_id WHEN binding.return_kind THEN binding.return_entry_id ELSE NULL END)
                            OR (SELECT count(*) FROM ledger_lines WHERE entry_id = entry.id) <> 2
                            OR (SELECT count(*) FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND account.wallet_id = entry.wallet_id AND account.currency = 'RWF'
                                    AND line.amount = root.principal AND line.direction = 'debit' AND account.kind = CASE entry.kind
                                        WHEN 'primary_hold' THEN 'investor_available' WHEN 'primary_refund' THEN 'investor_committed' ELSE 'investor_held' END) <> 1
                            OR (SELECT count(*) FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND account.wallet_id = entry.wallet_id AND account.currency = 'RWF'
                                    AND line.amount = root.principal AND line.direction = 'credit' AND account.kind = CASE entry.kind
                                        WHEN 'primary_hold' THEN 'investor_held' WHEN 'primary_commit' THEN 'investor_committed' ELSE 'investor_available' END) <> 1 THEN
                            RAISE EXCEPTION 'Campaign closure cash must return its exact original principal from the correct bucket' USING ERRCODE = '23514';
                        END IF;
                    END LOOP;
                END;
                $$;
                DROP FUNCTION IF EXISTS check_primary_campaign_closure_returns(varchar);
                CREATE OR REPLACE FUNCTION check_primary_campaign_closure_returns(checked_closure varchar, lock_returns boolean DEFAULT true) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    closure business_campaign_closures%ROWTYPE;
                    root_id varchar;
                BEGIN
                    SELECT * INTO closure FROM business_campaign_closures WHERE id = checked_closure;
                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Primary cash returns require a retained campaign closure' USING ERRCODE = '23514';
                    END IF;
                    IF lock_returns THEN
                        PERFORM 1 FROM business_profiles WHERE id = closure.business_id FOR UPDATE;
                        PERFORM 1 FROM business_campaigns WHERE id = closure.business_campaign_id FOR UPDATE;
                        PERFORM 1 FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id ORDER BY id FOR UPDATE;
                        PERFORM 1 FROM primary_commitments WHERE primary_reservation_id IN (
                            SELECT id FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id) ORDER BY id FOR UPDATE;
                        PERFORM 1 FROM investor_wallets WHERE party_id IN (
                            SELECT party_id FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id) ORDER BY party_id FOR UPDATE;
                    END IF;
                    IF EXISTS (SELECT 1 FROM primary_campaign_fundings WHERE business_campaign_id = closure.business_campaign_id)
                        OR (SELECT count(*) FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id)
                            <> (SELECT count(*) FROM primary_campaign_closure_returns WHERE business_campaign_closure_id = closure.id) THEN
                        RAISE EXCEPTION 'Campaign closure requires complete unfunded cash-return membership' USING ERRCODE = '23514';
                    END IF;
                    FOR root_id IN SELECT id FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id ORDER BY id LOOP
                        PERFORM check_primary_closure_return(root_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION protect_primary_closure_return() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Campaign closure cash-return evidence is immutable' USING ERRCODE = '23514';
                    END IF;
                    PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = (
                        SELECT business_campaign_id FROM primary_reservations WHERE id = NEW.primary_reservation_id)) FOR UPDATE;
                    PERFORM 1 FROM business_campaigns WHERE id = (SELECT business_campaign_id FROM primary_reservations WHERE id = NEW.primary_reservation_id) FOR UPDATE;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_closure_returns_protected BEFORE INSERT OR UPDATE OR DELETE ON primary_campaign_closure_returns
                    FOR EACH ROW EXECUTE FUNCTION protect_primary_closure_return();
                CREATE OR REPLACE FUNCTION require_primary_campaign_closure_returns() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_TABLE_NAME = 'business_campaign_closures' THEN
                        PERFORM check_primary_campaign_closure_returns(NEW.id);
                    ELSE
                        PERFORM check_primary_campaign_closure_returns(NEW.business_campaign_closure_id);
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER campaign_closure_primary_returns AFTER INSERT ON business_campaign_closures
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_campaign_closure_returns();
                CREATE CONSTRAINT TRIGGER primary_returns_campaign_closure AFTER INSERT ON primary_campaign_closure_returns
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_campaign_closure_returns();
                DO $$ DECLARE closure_id varchar; BEGIN
                    FOR closure_id IN SELECT id FROM business_campaign_closures LOOP
                        PERFORM check_primary_campaign_closure_returns(closure_id);
                    END LOOP;
                END; $$;
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles, business_campaigns, business_campaign_closures, primary_reservations, primary_campaign_closure_returns IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_campaign_closure_returns)
                        OR EXISTS (SELECT 1 FROM business_campaign_closures c JOIN primary_reservations r ON r.business_campaign_id = c.business_campaign_id) THEN
                        RAISE EXCEPTION 'Retained campaign cash-return closure protection requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER business_campaign_closure_isolation ON business_campaign_closures;
                DROP TRIGGER campaign_closure_primary_returns ON business_campaign_closures;
                DROP TRIGGER primary_returns_campaign_closure ON primary_campaign_closure_returns;
                DROP TRIGGER primary_closure_returns_protected ON primary_campaign_closure_returns;
                DROP FUNCTION require_primary_closure_read_committed(), require_primary_campaign_closure_returns(), protect_primary_closure_return(), check_primary_campaign_closure_returns(varchar, boolean), check_primary_closure_return(varchar);
                SQL);
            Schema::drop('primary_campaign_closure_returns');
        });
    }
};
