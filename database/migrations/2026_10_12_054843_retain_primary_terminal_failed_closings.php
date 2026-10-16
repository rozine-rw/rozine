<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Extend immutable unfunded closure protection only for a complete, terminal funded failure. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared('LOCK TABLE business_profiles, business_campaigns, disbursements, disbursement_closings, primary_reservations, primary_reservation_versions, primary_commitments, investor_wallets, ledger_entries, primary_campaign_fundings, primary_funding_commitments, business_campaign_closures, primary_campaign_closure_returns IN ACCESS EXCLUSIVE MODE');
            Schema::create('primary_failed_closings', function (Blueprint $table): void {
                $table->ulid('disbursement_closing_id')->primary();
                $table->ulid('business_campaign_closure_id')->unique();
                $table->foreignUlid('funding_id')->unique()->constrained('primary_campaign_fundings')->restrictOnDelete();
                $table->char('closing_sha256', 64);
                $table->char('disbursement_sha256', 64);
                $table->foreign('disbursement_closing_id')->references('id')->on('disbursement_closings')->restrictOnDelete();
            });
            DB::unprepared(<<<'SQL'
                ALTER TABLE primary_failed_closings ADD CONSTRAINT primary_failed_closure_parent
                    FOREIGN KEY (business_campaign_closure_id) REFERENCES business_campaign_closures(id)
                    ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
                ALTER TABLE primary_failed_closings ADD CONSTRAINT primary_failed_closure_identity
                    CHECK (business_campaign_closure_id = disbursement_closing_id);
                ALTER TABLE business_campaign_closures DROP CONSTRAINT campaign_closure_actor;
                ALTER TABLE business_campaign_closures ADD CONSTRAINT campaign_closure_actor CHECK (
                    (phase = 'cancelled' AND actor_user_id IS NOT NULL)
                    OR (phase IN ('expired', 'failed_closing') AND actor_user_id IS NULL));

                CREATE OR REPLACE FUNCTION check_primary_failed_closing_authority(checked_closure varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    binding primary_failed_closings%ROWTYPE;
                    funding primary_campaign_fundings%ROWTYPE;
                    closing disbursement_closings%ROWTYPE;
                    payment disbursements%ROWTYPE;
                    intent disbursement_intents%ROWTYPE;
                    reconciliation disbursement_reconciliations%ROWTYPE;
                    observation disbursement_provider_events%ROWTYPE;
                BEGIN
                    SELECT * INTO binding FROM primary_failed_closings WHERE business_campaign_closure_id = checked_closure;
                    SELECT * INTO funding FROM primary_campaign_fundings WHERE id = binding.funding_id;
                    SELECT * INTO closing FROM disbursement_closings WHERE id = binding.disbursement_closing_id;
                    SELECT * INTO payment FROM disbursements WHERE id = closing.disbursement_id;
                    IF funding.id IS NULL OR closing.kind IS DISTINCT FROM 'failed_closing'
                        OR binding.closing_sha256 IS DISTINCT FROM closing.sha256 OR binding.disbursement_sha256 IS DISTINCT FROM payment.sha256
                        OR payment.business_campaign_id IS DISTINCT FROM funding.business_campaign_id
                        OR payment.business_id IS DISTINCT FROM funding.business_id
                        OR payment.exposure_reservation_id IS DISTINCT FROM funding.exposure_reservation_id
                        OR payment.amount IS DISTINCT FROM funding.principal OR payment.currency IS DISTINCT FROM 'RWF'
                        OR payment.commitment_count IS DISTINCT FROM (SELECT count(*) FROM primary_funding_commitments WHERE funding_id = funding.id)
                        OR EXISTS (SELECT 1 FROM primary_holdings WHERE business_campaign_id = funding.business_campaign_id)
                        OR EXISTS (SELECT 1 FROM ledger_entries e JOIN primary_funding_commitments m ON m.reservation_id = e.source_id
                            WHERE m.funding_id = funding.id AND e.source_type = 'primary_reservation' AND e.kind = 'primary_issue') THEN
                        RAISE EXCEPTION 'Funded refund requires matching unissued terminal closing authority' USING ERRCODE = '23514';
                    END IF;
                    IF closing.cause = 'approve_recheck' THEN
                        IF closing.intent_id IS NOT NULL OR closing.operation_id IS NULL OR closing.actor_user_id IS NULL OR closing.request_id IS NULL
                            OR closing.causes = '[]'::jsonb
                            OR EXISTS (SELECT 1 FROM disbursement_intents WHERE disbursement_id = payment.id) THEN
                            RAISE EXCEPTION 'Approve failure cannot refund an in-flight intent' USING ERRCODE = '23514';
                        END IF;
                    ELSE
                        SELECT * INTO intent FROM disbursement_intents WHERE id = closing.intent_id;
                        IF intent.id IS NULL OR intent.disbursement_id IS DISTINCT FROM payment.id
                            OR intent.amount IS DISTINCT FROM payment.amount OR intent.currency IS DISTINCT FROM 'RWF'
                            OR intent.commitments_digest IS DISTINCT FROM payment.commitments_digest THEN
                            RAISE EXCEPTION 'Funded failure must retain its original intent' USING ERRCODE = '23514';
                        END IF;
                        IF closing.cause = 'worker_recheck' THEN
                            IF closing.causes = '[]'::jsonb OR closing.reconciliation_id IS NOT NULL
                                OR EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = intent.id AND phase NOT IN ('queued', 'recheck_failed'))
                                OR EXISTS (SELECT 1 FROM disbursement_provider_calls WHERE intent_id = intent.id)
                                OR EXISTS (SELECT 1 FROM disbursement_provider_events WHERE intent_id = intent.id) THEN
                                RAISE EXCEPTION 'Worker failure cannot refund dispatched or unknown principal' USING ERRCODE = '23514';
                            END IF;
                        ELSIF closing.cause = 'reconciled_failure' THEN
                            SELECT * INTO reconciliation FROM disbursement_reconciliations WHERE id = closing.reconciliation_id;
                            SELECT * INTO observation FROM disbursement_provider_events WHERE id = reconciliation.provider_event_id;
                            IF reconciliation.intent_id IS DISTINCT FROM intent.id OR reconciliation.decision IS DISTINCT FROM 'matched_failure'
                                OR reconciliation.causes IS DISTINCT FROM '[]'::jsonb OR observation.intent_id IS DISTINCT FROM intent.id
                                OR observation.disposition IS DISTINCT FROM 'applied' OR observation.state IS DISTINCT FROM 'failed'
                                OR observation.provider IS DISTINCT FROM intent.provider OR observation.observed_operation_id IS DISTINCT FROM intent.operation_id
                                OR observation.provider_reference_sha256 IS DISTINCT FROM intent.provider_reference_sha256
                                OR observation.amount IS DISTINCT FROM intent.amount OR observation.currency IS DISTINCT FROM 'RWF'
                                OR observation.environment IS DISTINCT FROM intent.environment OR observation.destination_sha256 IS DISTINCT FROM intent.destination_sha256 THEN
                                RAISE EXCEPTION 'Refund requires authenticated reconciled terminal failure' USING ERRCODE = '23514';
                            END IF;
                        ELSE
                            RAISE EXCEPTION 'Unrecognized funded closing cause' USING ERRCODE = '23514';
                        END IF;
                    END IF;
                END;
                $$;

                DROP FUNCTION IF EXISTS check_primary_campaign_closure_returns_before_terminal(varchar, boolean),
                    refuse_unfunded_close_after_funding_before_terminal(), refuse_unilateral_funded_refund_before_terminal();
                ALTER FUNCTION check_primary_campaign_closure_returns(varchar, boolean) RENAME TO check_primary_campaign_closure_returns_before_terminal;
                CREATE OR REPLACE FUNCTION check_primary_campaign_closure_returns(checked_closure varchar, lock_returns boolean DEFAULT true) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE closure business_campaign_closures%ROWTYPE; funding primary_campaign_fundings%ROWTYPE; root_id varchar;
                BEGIN
                    SELECT * INTO closure FROM business_campaign_closures WHERE id = checked_closure;
                    IF closure.phase IS DISTINCT FROM 'failed_closing' THEN
                        PERFORM check_primary_campaign_closure_returns_before_terminal(checked_closure, lock_returns);
                        RETURN;
                    END IF;
                    IF lock_returns THEN
                        PERFORM 1 FROM business_profiles WHERE id = closure.business_id FOR UPDATE;
                        PERFORM 1 FROM business_campaigns WHERE id = closure.business_campaign_id FOR UPDATE;
                        PERFORM 1 FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id ORDER BY id FOR UPDATE;
                        PERFORM 1 FROM primary_commitments WHERE primary_reservation_id IN
                            (SELECT id FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id) ORDER BY id FOR UPDATE;
                        PERFORM 1 FROM investor_wallets WHERE party_id IN
                            (SELECT party_id FROM primary_reservations WHERE business_campaign_id = closure.business_campaign_id) ORDER BY party_id FOR UPDATE;
                    END IF;
                    PERFORM check_primary_failed_closing_authority(checked_closure);
                    SELECT f.* INTO funding FROM primary_campaign_fundings f JOIN primary_failed_closings b ON b.funding_id = f.id
                        WHERE b.business_campaign_closure_id = checked_closure;
                    IF closure.business_campaign_id IS DISTINCT FROM funding.business_campaign_id
                        OR closure.business_id IS DISTINCT FROM funding.business_id OR closure.exposure_reservation_id IS DISTINCT FROM funding.exposure_reservation_id
                        OR closure.principal IS DISTINCT FROM funding.principal
                        OR closure.closed_at IS DISTINCT FROM (SELECT created_at FROM disbursement_closings WHERE id = checked_closure)
                        OR (SELECT count(*) FROM primary_campaign_closure_returns WHERE business_campaign_closure_id = checked_closure)
                            <> (SELECT count(*) FROM primary_funding_commitments WHERE funding_id = funding.id)
                        OR EXISTS (SELECT 1 FROM primary_funding_commitments m WHERE m.funding_id = funding.id AND NOT EXISTS (
                            SELECT 1 FROM primary_campaign_closure_returns r WHERE r.business_campaign_closure_id = checked_closure
                                AND r.primary_reservation_id = m.reservation_id AND r.primary_commitment_id = m.commitment_id
                                AND r.wallet_id = m.wallet_id AND r.hold_entry_id = m.hold_entry_id AND r.commit_entry_id = m.commit_entry_id
                                AND r.origin_operation_id = m.origin_operation_id AND r.return_kind = 'primary_refund')) THEN
                        RAISE EXCEPTION 'Terminal failed closing requires complete original funded refunds' USING ERRCODE = '23514';
                    END IF;
                    FOR root_id IN SELECT reservation_id FROM primary_funding_commitments WHERE funding_id = funding.id ORDER BY reservation_id LOOP
                        PERFORM check_primary_closure_return(root_id);
                    END LOOP;
                END;
                $$;

                DROP TRIGGER primary_funding_closure_gate ON business_campaign_closures;
                ALTER FUNCTION refuse_unfunded_close_after_funding() RENAME TO refuse_unfunded_close_after_funding_before_terminal;
                CREATE OR REPLACE FUNCTION refuse_unfunded_close_after_funding() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.phase = 'failed_closing' THEN
                        PERFORM check_primary_failed_closing_authority(NEW.id);
                    ELSIF EXISTS (SELECT 1 FROM primary_campaign_fundings WHERE business_campaign_id = NEW.business_campaign_id) THEN
                        RAISE EXCEPTION 'Funded campaigns require authoritative failed closing or issue' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_closure_gate BEFORE INSERT ON business_campaign_closures
                    FOR EACH ROW EXECUTE FUNCTION refuse_unfunded_close_after_funding();
                DROP TRIGGER primary_funding_refund_gate ON ledger_entries;
                ALTER FUNCTION refuse_unilateral_funded_refund() RENAME TO refuse_unilateral_funded_refund_before_terminal;
                CREATE OR REPLACE FUNCTION refuse_unilateral_funded_refund() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE closing_id varchar;
                BEGIN
                    IF NEW.kind = 'primary_refund' AND NEW.source_type = 'primary_reservation'
                        AND EXISTS (SELECT 1 FROM primary_funding_commitments WHERE reservation_id = NEW.source_id) THEN
                        SELECT b.business_campaign_closure_id INTO closing_id FROM primary_failed_closings b
                            JOIN primary_funding_commitments m ON m.funding_id = b.funding_id WHERE m.reservation_id = NEW.source_id;
                        IF closing_id IS NULL THEN
                            RAISE EXCEPTION 'Funded principal requires authoritative failed closing' USING ERRCODE = '23514';
                        END IF;
                        PERFORM check_primary_failed_closing_authority(closing_id);
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_funding_refund_gate BEFORE INSERT ON ledger_entries
                    FOR EACH ROW EXECUTE FUNCTION refuse_unilateral_funded_refund();
                CREATE TRIGGER primary_failed_closings_immutable BEFORE UPDATE OR DELETE ON primary_failed_closings
                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                CREATE OR REPLACE FUNCTION require_primary_failed_closing() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_campaign_closure_returns(NEW.business_campaign_closure_id);
                    RETURN NEW;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_failed_closing_complete AFTER INSERT ON primary_failed_closings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_failed_closing();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles, business_campaigns, disbursements, disbursement_closings, primary_reservations, primary_reservation_versions, primary_commitments, investor_wallets, ledger_entries, primary_campaign_fundings, primary_funding_commitments, primary_failed_closings, business_campaign_closures, primary_campaign_closure_returns IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_failed_closings) OR EXISTS (SELECT 1 FROM business_campaign_closures WHERE phase = 'failed_closing') THEN
                        RAISE EXCEPTION 'Retained terminal funded failures require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_funding_closure_gate ON business_campaign_closures;
                DROP TRIGGER primary_funding_refund_gate ON ledger_entries;
                DROP FUNCTION refuse_unfunded_close_after_funding(), refuse_unilateral_funded_refund();
                ALTER FUNCTION refuse_unfunded_close_after_funding_before_terminal() RENAME TO refuse_unfunded_close_after_funding;
                ALTER FUNCTION refuse_unilateral_funded_refund_before_terminal() RENAME TO refuse_unilateral_funded_refund;
                CREATE TRIGGER primary_funding_closure_gate BEFORE INSERT ON business_campaign_closures FOR EACH ROW EXECUTE FUNCTION refuse_unfunded_close_after_funding();
                CREATE TRIGGER primary_funding_refund_gate BEFORE INSERT ON ledger_entries FOR EACH ROW EXECUTE FUNCTION refuse_unilateral_funded_refund();
                DROP TRIGGER primary_failed_closing_complete ON primary_failed_closings;
                DROP FUNCTION require_primary_failed_closing();
                DROP FUNCTION check_primary_campaign_closure_returns(varchar, boolean);
                ALTER FUNCTION check_primary_campaign_closure_returns_before_terminal(varchar, boolean) RENAME TO check_primary_campaign_closure_returns;
                DROP FUNCTION check_primary_failed_closing_authority(varchar);
                ALTER TABLE business_campaign_closures DROP CONSTRAINT campaign_closure_actor;
                ALTER TABLE business_campaign_closures ADD CONSTRAINT campaign_closure_actor CHECK (
                    (phase = 'cancelled' AND actor_user_id IS NOT NULL) OR (phase = 'expired' AND actor_user_id IS NULL));
                SQL);
            Schema::drop('primary_failed_closings');
        });
    }
};
