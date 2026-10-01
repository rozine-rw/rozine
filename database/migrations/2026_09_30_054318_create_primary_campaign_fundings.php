<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_reservations, primary_reservation_versions, business_campaign_closures, primary_commitments, investor_wallets, ledger_entries, command_operations IN SHARE ROW EXCLUSIVE MODE');
            Schema::create('primary_campaign_fundings', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->ulid('business_campaign_id')->unique();
                $table->ulid('business_id')->index();
                $table->ulid('exposure_reservation_id')->unique();
                $table->char('publication_sha256', 64);
                $table->decimal('principal', 9, 0);
                $table->text('payload');
                $table->char('sha256', 64);
                $table->timestampTz('created_at', 6);
                $table->foreign(['business_campaign_id', 'business_id', 'exposure_reservation_id', 'principal'], 'primary_funding_campaign')
                    ->references(['id', 'business_id', 'exposure_reservation_id', 'principal'])->on('business_campaigns')->restrictOnDelete();
                $table->foreign(['business_campaign_id', 'publication_sha256'], 'primary_funding_publication')
                    ->references(['id', 'sha256'])->on('business_campaigns')->restrictOnDelete();
            });
            Schema::create('primary_funding_commitments', function (Blueprint $table): void {
                $table->foreignUlid('funding_id')->constrained('primary_campaign_fundings')->restrictOnDelete();
                $table->ulid('commitment_id')->primary();
                $table->ulid('reservation_id')->unique();
                $table->ulid('hold_entry_id')->unique();
                $table->ulid('commit_entry_id')->unique();
                $table->ulid('wallet_id');
                $table->ulid('origin_operation_id');
                $table->foreign('commitment_id')->references('id')->on('primary_commitments')->restrictOnDelete();
                $table->foreign('reservation_id')->references('id')->on('primary_reservations')->restrictOnDelete();
                $table->foreign('hold_entry_id')->references('id')->on('ledger_entries')->restrictOnDelete();
                $table->foreign('commit_entry_id')->references('id')->on('ledger_entries')->restrictOnDelete();
                $table->foreign('wallet_id')->references('id')->on('investor_wallets')->restrictOnDelete();
                $table->foreign('origin_operation_id')->references('id')->on('command_operations')->restrictOnDelete();
            });
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION lock_primary_funding_campaign() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE campaign business_campaigns%ROWTYPE;
                BEGIN
                    PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;
                    SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                    PERFORM 1 FROM primary_reservations WHERE business_campaign_id = campaign.id ORDER BY id FOR UPDATE;
                    PERFORM 1 FROM primary_commitments WHERE primary_reservation_id IN
                        (SELECT id FROM primary_reservations WHERE business_campaign_id = campaign.id) ORDER BY id FOR UPDATE;
                    PERFORM 1 FROM investor_wallets WHERE party_id IN
                        (SELECT party_id FROM primary_reservations WHERE business_campaign_id = campaign.id) ORDER BY party_id FOR UPDATE;
                    IF EXISTS (SELECT 1 FROM business_campaign_closures WHERE business_campaign_id = campaign.id)
                        OR NEW.created_at < campaign.live_at
                        OR EXISTS (SELECT 1 FROM primary_commitments c JOIN primary_reservations r ON r.id = c.primary_reservation_id
                            WHERE r.business_campaign_id = campaign.id AND c.confirmed_at > NEW.created_at) THEN
                        RAISE EXCEPTION 'Funding requires an unclosed live publication and retained confirmation history' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_campaign_locked BEFORE INSERT ON primary_campaign_fundings
                    FOR EACH ROW EXECUTE FUNCTION lock_primary_funding_campaign();
                CREATE TRIGGER primary_funding_immutable BEFORE UPDATE OR DELETE ON primary_campaign_fundings
                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                CREATE TRIGGER primary_funding_commitments_immutable BEFORE UPDATE OR DELETE ON primary_funding_commitments
                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                CREATE OR REPLACE FUNCTION bind_primary_funding_commitment() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE funding primary_campaign_fundings%ROWTYPE;
                DECLARE reservation primary_reservations%ROWTYPE;
                DECLARE commitment primary_commitments%ROWTYPE;
                BEGIN
                    SELECT * INTO funding FROM primary_campaign_fundings WHERE id = NEW.funding_id;
                    SELECT * INTO reservation FROM primary_reservations WHERE id = NEW.reservation_id;
                    SELECT * INTO commitment FROM primary_commitments WHERE id = NEW.commitment_id;
                    IF reservation.business_campaign_id IS DISTINCT FROM funding.business_campaign_id
                        OR commitment.primary_reservation_id IS DISTINCT FROM reservation.id
                        OR NEW.origin_operation_id IS DISTINCT FROM reservation.origin_operation_id
                        OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id = NEW.wallet_id AND party_id = reservation.party_id)
                        OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE id = NEW.hold_entry_id AND kind = 'primary_hold'
                            AND source_type = 'primary_reservation' AND source_id = reservation.id AND wallet_id = NEW.wallet_id
                            AND origin_operation_id = NEW.origin_operation_id AND currency = 'RWF')
                        OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE id = NEW.commit_entry_id AND kind = 'primary_commit'
                            AND source_type = 'primary_reservation' AND source_id = reservation.id AND wallet_id = NEW.wallet_id
                            AND origin_operation_id = NEW.origin_operation_id AND currency = 'RWF') THEN
                        RAISE EXCEPTION 'Funding membership must bind the original commitment and cash' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_commitment_bound BEFORE INSERT ON primary_funding_commitments
                    FOR EACH ROW EXECUTE FUNCTION bind_primary_funding_commitment();
                CREATE OR REPLACE FUNCTION require_complete_primary_funding() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE funding primary_campaign_fundings%ROWTYPE;
                DECLARE target_id char(26);
                BEGIN
                    IF TG_TABLE_NAME = 'primary_campaign_fundings' THEN target_id := NEW.id;
                    ELSE target_id := NEW.funding_id; END IF;
                    SELECT * INTO funding FROM primary_campaign_fundings WHERE id = target_id;
                    IF NOT EXISTS (SELECT 1 FROM primary_funding_commitments WHERE funding_id = funding.id)
                        OR (SELECT COALESCE(SUM(r.principal), 0) FROM primary_funding_commitments m
                            JOIN primary_reservations r ON r.id = m.reservation_id WHERE m.funding_id = funding.id) <> funding.principal
                        OR EXISTS (SELECT 1 FROM primary_reservations r WHERE r.business_campaign_id = funding.business_campaign_id
                            AND NOT EXISTS (SELECT 1 FROM primary_funding_commitments m WHERE m.funding_id = funding.id AND m.reservation_id = r.id))
                        OR EXISTS (SELECT 1 FROM primary_funding_commitments m JOIN primary_commitments c ON c.id = m.commitment_id
                            JOIN primary_reservations r ON r.id = m.reservation_id
                            WHERE m.funding_id = funding.id AND ((SELECT state FROM primary_reservation_versions
                                WHERE primary_reservation_id = r.id ORDER BY revision DESC LIMIT 1) IS DISTINCT FROM 'confirmed'
                                OR EXISTS (SELECT 1 FROM ledger_entries e WHERE e.source_type = 'primary_reservation'
                                    AND e.source_id = r.id AND e.kind NOT IN ('primary_hold', 'primary_commit'))
                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'credit' AND a.kind = 'investor_committed'
                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal)
                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'debit' AND a.kind = 'investor_held'
                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal))) THEN
                        RAISE EXCEPTION 'Funding must retain the complete confirmed principal and unreturned committed cash' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_funding_complete AFTER INSERT ON primary_campaign_fundings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_complete_primary_funding();
                CREATE CONSTRAINT TRIGGER primary_funding_members_complete AFTER INSERT ON primary_funding_commitments
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_complete_primary_funding();
                CREATE OR REPLACE FUNCTION refuse_funded_primary_admission() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE campaign_id char(26);
                BEGIN
                    IF TG_TABLE_NAME = 'primary_reservations' THEN
                        campaign_id := NEW.business_campaign_id;
                    ELSE
                        IF NEW.state NOT IN ('held', 'confirmed') THEN RETURN NEW; END IF;
                        SELECT business_campaign_id INTO campaign_id FROM primary_reservations WHERE id = NEW.primary_reservation_id;
                    END IF;
                    PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = campaign_id) FOR UPDATE;
                    PERFORM 1 FROM business_campaigns WHERE id = campaign_id FOR UPDATE;
                    IF EXISTS (SELECT 1 FROM primary_campaign_fundings WHERE business_campaign_id = campaign_id) THEN
                        RAISE EXCEPTION 'A funded campaign cannot admit another hold or confirmation' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_reservation_gate BEFORE INSERT ON primary_reservations
                    FOR EACH ROW EXECUTE FUNCTION refuse_funded_primary_admission();
                CREATE TRIGGER primary_funding_version_gate BEFORE INSERT ON primary_reservation_versions
                    FOR EACH ROW EXECUTE FUNCTION refuse_funded_primary_admission();
                CREATE OR REPLACE FUNCTION refuse_unfunded_close_after_funding() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;
                    PERFORM 1 FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                    IF EXISTS (SELECT 1 FROM primary_campaign_fundings WHERE business_campaign_id = NEW.business_campaign_id) THEN
                        RAISE EXCEPTION 'Funded campaigns require authoritative failed closing or issue' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_closure_gate BEFORE INSERT ON business_campaign_closures
                    FOR EACH ROW EXECUTE FUNCTION refuse_unfunded_close_after_funding();
                CREATE OR REPLACE FUNCTION refuse_unilateral_funded_refund() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.kind = 'primary_refund' AND NEW.source_type = 'primary_reservation'
                        AND EXISTS (SELECT 1 FROM primary_funding_commitments WHERE reservation_id = NEW.source_id) THEN
                        RAISE EXCEPTION 'Funded principal requires authoritative failed closing' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_refund_gate BEFORE INSERT ON ledger_entries
                    FOR EACH ROW EXECUTE FUNCTION refuse_unilateral_funded_refund();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_campaign_fundings, primary_funding_commitments, ledger_entries IN ACCESS EXCLUSIVE MODE');
            DB::unprepared(<<<'SQL'
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_campaign_fundings) THEN
                        RAISE EXCEPTION 'Retained funding requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_funding_reservation_gate ON primary_reservations;
                DROP TRIGGER primary_funding_version_gate ON primary_reservation_versions;
                DROP TRIGGER primary_funding_closure_gate ON business_campaign_closures;
                DROP TRIGGER primary_funding_refund_gate ON ledger_entries;
                SQL);
            Schema::drop('primary_funding_commitments');
            Schema::drop('primary_campaign_fundings');
            DB::unprepared('DROP FUNCTION lock_primary_funding_campaign(), bind_primary_funding_commitment(), require_complete_primary_funding(), refuse_funded_primary_admission(), refuse_unfunded_close_after_funding(), refuse_unilateral_funded_refund()');
        });
    }
};
