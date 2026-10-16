<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Disbursement records (C3 v2 §2e, MC-02, MC-08). Every table is append-only; the one permitted
 * change is a step-up proof's single consumption. The database folds the event log itself, so the
 * state machine, the maker/checker and hold-placer separation, the intent binding, the dispatch
 * order, the no-resend rule and the single terminal closing hold even against raw SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_campaign_id')->unique();
            $table->ulid('business_id')->index();
            $table->ulid('exposure_reservation_id')->unique();
            $table->decimal('amount', 12, 0);
            $table->char('currency', 3);
            $table->char('commitments_digest', 64);
            $table->integer('commitment_count');
            $table->smallInteger('term_months');
            $table->timestampTz('funded_at');
            $table->string('environment', 20);
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
        });
        Schema::create('disbursement_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('disbursement_id')->constrained('disbursements')->restrictOnDelete();
            $table->integer('revision');
            $table->string('kind', 24);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->ulid('operation_id')->nullable()->unique();
            $table->uuid('request_id')->nullable();
            $table->char('binding_sha256', 64)->nullable();
            $table->char('destination_sha256', 64)->nullable();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at', 6);
            $table->unique(['disbursement_id', 'revision']);
        });
        Schema::create('disbursement_step_up_proofs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('purpose', 40);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('disbursement_id')->constrained('disbursements')->restrictOnDelete();
            $table->integer('revision');
            $table->decimal('amount', 12, 0);
            $table->char('destination_sha256', 64);
            $table->char('intent_digest', 64);
            $table->char('credential_binding', 64);
            $table->char('proof_sha256', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->ulid('consumed_operation_id')->nullable()->unique();
            $table->timestampTz('created_at');
        });
        Schema::create('disbursement_step_up_markers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->char('marker', 64)->unique();
            $table->timestampTz('created_at');
        });
        Schema::create('disbursement_intents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('disbursement_id')->unique()->constrained('disbursements')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->uuid('request_id');
            $table->integer('revision');
            $table->decimal('amount', 12, 0);
            $table->char('currency', 3);
            $table->string('destination_id', 64);
            $table->integer('destination_revision');
            $table->char('destination_sha256', 64);
            $table->char('commitments_digest', 64);
            $table->char('binding_sha256', 64);
            $table->char('intent_digest', 64);
            $table->string('provider', 40);
            $table->text('provider_reference');
            $table->char('provider_reference_sha256', 64)->unique();
            $table->string('environment', 20);
            $table->boolean('idempotent_sends');
            $table->foreignId('maker_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('checker_user_id')->constrained('users')->restrictOnDelete();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
        });
        Schema::create('disbursement_dispatches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intent_id')->constrained('disbursement_intents')->restrictOnDelete();
            $table->string('phase', 16);
            $table->timestampTz('created_at', 6);
            $table->unique(['intent_id', 'phase']);
            $table->index(['phase', 'intent_id']);
        });
        Schema::create('disbursement_provider_calls', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intent_id')->constrained('disbursement_intents')->restrictOnDelete();
            $table->string('kind', 8);
            $table->string('source', 12);
            $table->ulid('operation_id')->nullable();
            $table->timestampTz('created_at', 6);
            $table->index(['intent_id', 'kind']);
        });
        Schema::create('disbursement_provider_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intent_id')->constrained('disbursement_intents')->restrictOnDelete();
            $table->string('provider', 40);
            $table->string('provider_event_id', 120);
            $table->char('content_sha256', 64);
            $table->string('source', 12);
            $table->string('state', 12);
            $table->decimal('amount', 12, 0)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('environment', 20)->nullable();
            $table->string('observed_operation_id', 64)->nullable();
            $table->char('provider_reference_sha256', 64)->nullable();
            $table->char('destination_sha256', 64)->nullable();
            $table->timestampTz('observed_at');
            $table->timestampTz('effective_at')->nullable();
            $table->string('disposition', 16);
            $table->jsonb('mismatches');
            $table->text('evidence');
            $table->timestampTz('created_at', 6);
            $table->unique(['provider', 'provider_event_id', 'content_sha256', 'intent_id'], 'disbursement_provider_events_content');
            $table->index(['intent_id', 'id']);
        });
        Schema::create('disbursement_reconciliations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intent_id')->constrained('disbursement_intents')->restrictOnDelete();
            $table->foreignUlid('provider_event_id')->nullable()->constrained('disbursement_provider_events')->restrictOnDelete();
            $table->string('decision', 16);
            $table->jsonb('causes');
            $table->jsonb('comparison');
            $table->timestampTz('created_at', 6);
            $table->index(['intent_id', 'id']);
        });
        Schema::create('disbursement_closings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('disbursement_id')->unique()->constrained('disbursements')->restrictOnDelete();
            $table->foreignUlid('intent_id')->nullable()->unique()->constrained('disbursement_intents')->restrictOnDelete();
            $table->foreignUlid('reconciliation_id')->nullable()->unique()->constrained('disbursement_reconciliations')->restrictOnDelete();
            $table->string('kind', 16);
            $table->string('cause', 20);
            $table->jsonb('causes');
            $table->ulid('operation_id')->nullable()->unique();
            $table->timestampTz('effective_at')->nullable();
            $table->date('effective_date')->nullable();
            $table->jsonb('due_dates')->nullable();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE disbursements ADD CONSTRAINT disbursement_facts CHECK (
                currency = 'RWF' AND amount > 0 AND amount % 5000 = 0 AND commitment_count > 0 AND term_months BETWEEN 1 AND 120
                AND commitments_digest ~ '^[0-9a-f]{64}$' AND sha256 ~ '^[0-9a-f]{64}$');
            ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_kind CHECK (
                kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded', 'dispatched', 'succeeded', 'failed_closing'));
            ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_attribution CHECK (
                revision > 0 AND (
                    (kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded')
                        AND actor_user_id IS NOT NULL AND operation_id IS NOT NULL AND request_id IS NOT NULL)
                    OR (kind IN ('dispatched', 'succeeded') AND actor_user_id IS NULL AND operation_id IS NULL AND request_id IS NULL)
                    OR (kind = 'failed_closing' AND ((actor_user_id IS NULL) = (operation_id IS NULL)) AND ((operation_id IS NULL) = (request_id IS NULL))))
                AND ((kind = 'authorized') = (binding_sha256 IS NOT NULL)) AND ((kind = 'authorized') = (destination_sha256 IS NOT NULL)));
            ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE disbursement_step_up_proofs ADD CONSTRAINT disbursement_proof_facts CHECK (
                purpose = 'disbursement.approve' AND revision > 0 AND amount > 0 AND expires_at > created_at
                AND ((consumed_at IS NULL) = (consumed_operation_id IS NULL)));
            ALTER TABLE disbursement_intents ADD CONSTRAINT disbursement_intent_facts CHECK (
                currency = 'RWF' AND amount > 0 AND revision > 0 AND maker_user_id <> checker_user_id);
            ALTER TABLE disbursement_intents ADD CONSTRAINT disbursement_intent_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE disbursement_dispatches ADD CONSTRAINT disbursement_dispatch_phase CHECK (
                phase IN ('queued', 'claimed', 'sent', 'unsent', 'recheck_failed'));
            CREATE UNIQUE INDEX disbursement_dispatches_outcome ON disbursement_dispatches (intent_id)
                WHERE phase IN ('sent', 'unsent', 'recheck_failed');
            ALTER TABLE disbursement_provider_calls ADD CONSTRAINT disbursement_provider_call_kind CHECK (
                (kind = 'send' AND source IN ('dispatch', 'recovery') AND operation_id IS NULL)
                OR (kind = 'query' AND source IN ('reconcile', 'requery') AND ((source = 'requery') = (operation_id IS NOT NULL))));
            ALTER TABLE disbursement_provider_events ADD CONSTRAINT disbursement_provider_event_facts CHECK (
                source IN ('callback', 'query', 'requery') AND state IN ('pending', 'unknown', 'succeeded', 'failed')
                AND disposition IN ('applied', 'duplicate', 'stale', 'after_final', 'conflict', 'key_conflict', 'unverifiable')
                AND (amount IS NULL OR amount >= 0) AND (currency IS NULL OR currency ~ '^[A-Z]{3}$')
                AND jsonb_typeof(mismatches) = 'array' AND ((disposition = 'unverifiable') = (jsonb_array_length(mismatches) > 0)));
            -- One observation claims a provider event identity: the first that matches its own intent.
            -- A key conflict and wrong-intent (unverifiable) evidence are retained but claim nothing.
            CREATE UNIQUE INDEX disbursement_provider_events_identity ON disbursement_provider_events (provider, provider_event_id)
                WHERE disposition NOT IN ('key_conflict', 'unverifiable');
            CREATE UNIQUE INDEX disbursement_provider_events_final ON disbursement_provider_events (intent_id)
                WHERE disposition = 'applied' AND state IN ('succeeded', 'failed');
            ALTER TABLE disbursement_reconciliations ADD CONSTRAINT disbursement_reconciliation_facts CHECK (
                decision IN ('matched_success', 'matched_failure', 'exception', 'open') AND jsonb_typeof(causes) = 'array'
                AND jsonb_typeof(comparison) = 'object'
                AND ((decision IN ('matched_success', 'matched_failure')) = (provider_event_id IS NOT NULL))
                AND ((decision = 'exception') = (jsonb_array_length(causes) > 0)));
            CREATE UNIQUE INDEX disbursement_reconciliations_terminal ON disbursement_reconciliations (intent_id)
                WHERE decision IN ('matched_success', 'matched_failure');
            ALTER TABLE disbursement_closings ADD CONSTRAINT disbursement_closing_facts CHECK (COALESCE(
                jsonb_typeof(causes) = 'array' AND (
                    (kind = 'issued' AND cause = 'reconciled_success' AND intent_id IS NOT NULL AND reconciliation_id IS NOT NULL
                        AND effective_at IS NOT NULL AND effective_date IS NOT NULL AND jsonb_typeof(due_dates) = 'array'
                        AND jsonb_array_length(due_dates) > 0 AND operation_id IS NULL)
                    OR (kind = 'failed_closing' AND effective_at IS NULL AND effective_date IS NULL AND due_dates IS NULL AND (
                        (cause = 'approve_recheck' AND intent_id IS NULL AND reconciliation_id IS NULL AND operation_id IS NOT NULL
                            AND jsonb_array_length(causes) > 0)
                        OR (cause = 'worker_recheck' AND intent_id IS NOT NULL AND reconciliation_id IS NULL AND operation_id IS NULL
                            AND jsonb_array_length(causes) > 0)
                        OR (cause = 'reconciled_failure' AND intent_id IS NOT NULL AND reconciliation_id IS NOT NULL AND operation_id IS NULL)))), false));
            ALTER TABLE disbursement_closings ADD CONSTRAINT disbursement_closing_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;

            CREATE OR REPLACE FUNCTION reject_disbursement_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
            END;
            $$;

            -- The event log folded exactly as App\Domain\Disbursement\DisbursementState folds it.
            CREATE OR REPLACE FUNCTION disbursement_fold(p_disbursement char(26), OUT f_state text, OUT f_revision integer,
                OUT f_maker bigint, OUT f_checker bigint, OUT f_placer bigint) LANGUAGE plpgsql AS $$
            DECLARE
                e record;
                held_from text;
            BEGIN
                f_state := 'ready'; f_revision := 0;
                FOR e IN SELECT ev.kind, ev.actor_user_id FROM disbursement_events ev WHERE ev.disbursement_id = p_disbursement ORDER BY ev.revision LOOP
                    f_revision := f_revision + 1;
                    IF e.kind = 'authorized' THEN
                        f_state := 'awaiting_second_approver'; f_maker := e.actor_user_id; f_checker := NULL; f_placer := NULL;
                    ELSIF e.kind = 'rejected' THEN
                        f_state := 'ready'; f_maker := NULL;
                    ELSIF e.kind = 'held' THEN
                        held_from := f_state; f_state := 'on_hold'; f_placer := e.actor_user_id;
                    ELSIF e.kind = 'hold_released' THEN
                        f_state := held_from; f_placer := NULL;
                    ELSIF e.kind = 'intent_recorded' THEN
                        f_state := 'queued'; f_checker := e.actor_user_id;
                    ELSE
                        f_state := e.kind;
                    END IF;
                END LOOP;
            END;
            $$;

            CREATE OR REPLACE FUNCTION protect_disbursement() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursements_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursements
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement();

            CREATE OR REPLACE FUNCTION protect_disbursement_event() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                folded record;
                allowed boolean;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM disbursements WHERE id = NEW.disbursement_id FOR UPDATE;
                SELECT * INTO folded FROM disbursement_fold(NEW.disbursement_id);
                IF NEW.revision <> folded.f_revision + 1 THEN
                    RAISE EXCEPTION 'A disbursement event must take the next revision' USING ERRCODE = '23514';
                END IF;
                allowed := CASE NEW.kind
                    WHEN 'authorized' THEN folded.f_state IN ('ready', 'awaiting_second_approver')
                    WHEN 'rejected' THEN folded.f_state = 'awaiting_second_approver' AND NEW.actor_user_id <> folded.f_maker
                    WHEN 'held' THEN folded.f_state IN ('ready', 'awaiting_second_approver')
                    WHEN 'hold_released' THEN folded.f_state = 'on_hold' AND NEW.actor_user_id <> folded.f_placer
                    WHEN 'intent_recorded' THEN folded.f_state = 'awaiting_second_approver' AND NEW.actor_user_id <> folded.f_maker
                        AND EXISTS (SELECT 1 FROM disbursement_intents i WHERE i.disbursement_id = NEW.disbursement_id
                            AND i.operation_id = NEW.operation_id AND i.checker_user_id = NEW.actor_user_id AND i.maker_user_id = folded.f_maker)
                    WHEN 'dispatched' THEN folded.f_state = 'queued'
                        AND EXISTS (SELECT 1 FROM disbursement_intents i JOIN disbursement_dispatches d ON d.intent_id = i.id
                            WHERE i.disbursement_id = NEW.disbursement_id AND d.phase IN ('sent', 'unsent'))
                    WHEN 'succeeded' THEN folded.f_state = 'dispatched'
                        AND EXISTS (SELECT 1 FROM disbursement_closings c WHERE c.disbursement_id = NEW.disbursement_id AND c.kind = 'issued')
                    WHEN 'failed_closing' THEN folded.f_state IN ('awaiting_second_approver', 'queued', 'dispatched')
                        AND EXISTS (SELECT 1 FROM disbursement_closings c WHERE c.disbursement_id = NEW.disbursement_id AND c.kind = 'failed_closing'
                            AND (c.operation_id IS NOT DISTINCT FROM NEW.operation_id))
                        AND (NEW.actor_user_id IS NULL OR NEW.actor_user_id <> folded.f_maker)
                    ELSE false
                END;
                IF NOT COALESCE(allowed, false) THEN
                    RAISE EXCEPTION 'The disbursement state or segregation of duties refuses this event' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_events_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_events
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_event();

            CREATE OR REPLACE FUNCTION protect_disbursement_step_up_proof() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    IF NEW.consumed_at IS NOT NULL THEN
                        RAISE EXCEPTION 'A step-up proof starts unconsumed' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END IF;
                IF TG_OP = 'UPDATE' AND OLD.consumed_at IS NULL AND NEW.consumed_at IS NOT NULL AND NEW.consumed_at < OLD.expires_at
                    AND (NEW.id, NEW.purpose, NEW.actor_user_id, NEW.disbursement_id, NEW.revision, NEW.amount, NEW.destination_sha256,
                        NEW.intent_digest, NEW.credential_binding, NEW.proof_sha256, NEW.expires_at, NEW.created_at)
                    IS NOT DISTINCT FROM (OLD.id, OLD.purpose, OLD.actor_user_id, OLD.disbursement_id, OLD.revision, OLD.amount,
                        OLD.destination_sha256, OLD.intent_digest, OLD.credential_binding, OLD.proof_sha256, OLD.expires_at, OLD.created_at) THEN
                    RETURN NEW;
                END IF;
                RAISE EXCEPTION 'A step-up proof is consumed once, before it expires, and never otherwise changed' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER disbursement_step_up_proofs_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_step_up_proofs
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_step_up_proof();
            CREATE TRIGGER disbursement_step_up_markers_immutable BEFORE UPDATE OR DELETE ON disbursement_step_up_markers
                FOR EACH ROW EXECUTE FUNCTION reject_disbursement_mutation();

            CREATE OR REPLACE FUNCTION protect_disbursement_intent() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                folded record;
                parent disbursements%ROWTYPE;
                authorized_event disbursement_events%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO parent FROM disbursements WHERE id = NEW.disbursement_id FOR UPDATE;
                SELECT * INTO folded FROM disbursement_fold(NEW.disbursement_id);
                SELECT * INTO authorized_event FROM disbursement_events WHERE disbursement_id = NEW.disbursement_id AND kind = 'authorized'
                    ORDER BY revision DESC LIMIT 1;
                IF folded.f_state <> 'awaiting_second_approver' OR NEW.revision <> folded.f_revision
                    OR NEW.maker_user_id IS DISTINCT FROM folded.f_maker OR NEW.checker_user_id = folded.f_maker
                    OR NEW.amount <> parent.amount OR NEW.currency <> parent.currency OR NEW.commitments_digest <> parent.commitments_digest
                    OR NEW.environment <> parent.environment
                    OR NEW.binding_sha256 IS DISTINCT FROM authorized_event.binding_sha256 OR NEW.destination_sha256 IS DISTINCT FROM authorized_event.destination_sha256 THEN
                    RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_intents_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_intents
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_intent();

            CREATE OR REPLACE FUNCTION protect_disbursement_dispatch() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM disbursement_intents WHERE id = NEW.intent_id FOR UPDATE;
                IF (NEW.phase IN ('claimed', 'recheck_failed') AND (NOT EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase = 'queued')
                        OR EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase IN ('claimed', 'recheck_failed'))))
                    OR (NEW.phase IN ('sent', 'unsent') AND NOT EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase = 'claimed'))
                    OR (NEW.phase = 'queued' AND NOT EXISTS (SELECT 1 FROM disbursement_intents WHERE id = NEW.intent_id)) THEN
                    RAISE EXCEPTION 'Dispatch phases must follow queued, then claimed or recheck_failed, then sent or unsent' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_dispatches_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_dispatches
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_dispatch();

            CREATE OR REPLACE FUNCTION protect_disbursement_provider_call() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                intent disbursement_intents%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO intent FROM disbursement_intents WHERE id = NEW.intent_id FOR UPDATE;
                IF NEW.kind = 'send' AND (NOT EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase = 'claimed')
                        OR (NOT intent.idempotent_sends AND EXISTS (SELECT 1 FROM disbursement_provider_calls WHERE intent_id = NEW.intent_id AND kind = 'send'))
                        OR (NEW.source = 'recovery' AND (NOT intent.idempotent_sends
                            OR EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase IN ('sent', 'unsent'))
                            OR EXISTS (SELECT 1 FROM disbursement_provider_events WHERE intent_id = NEW.intent_id))))
                    OR (NEW.kind = 'query' AND NOT EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase IN ('sent', 'unsent'))) THEN
                    RAISE EXCEPTION 'A payout is sent once from its claim, resent only by an idempotent provider before any outcome, and queried only once dispatched'
                        USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_provider_calls_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_provider_calls
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_provider_call();

            CREATE TRIGGER disbursement_provider_events_immutable BEFORE UPDATE OR DELETE ON disbursement_provider_events
                FOR EACH ROW EXECUTE FUNCTION reject_disbursement_mutation();

            -- Only refused (unverifiable) evidence may disagree with the intent it is recorded against,
            -- so a claimed identity, and any outcome that could settle, always belongs to its own intent.
            CREATE OR REPLACE FUNCTION protect_disbursement_provider_event() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                intent disbursement_intents%ROWTYPE;
            BEGIN
                SELECT * INTO intent FROM disbursement_intents WHERE id = NEW.intent_id;
                IF NEW.disposition <> 'unverifiable' AND (NEW.provider IS DISTINCT FROM intent.provider
                    OR NEW.observed_operation_id IS DISTINCT FROM intent.operation_id
                    OR NEW.provider_reference_sha256 IS DISTINCT FROM intent.provider_reference_sha256
                    OR NEW.amount IS DISTINCT FROM intent.amount OR NEW.currency IS DISTINCT FROM intent.currency
                    OR NEW.environment IS DISTINCT FROM intent.environment OR NEW.destination_sha256 IS DISTINCT FROM intent.destination_sha256) THEN
                    RAISE EXCEPTION 'A provider observation that does not match its intent can only be retained as unverifiable' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_provider_events_bound BEFORE INSERT ON disbursement_provider_events
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_provider_event();

            CREATE OR REPLACE FUNCTION protect_disbursement_reconciliation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM disbursement_intents WHERE id = NEW.intent_id FOR UPDATE;
                IF NEW.decision IN ('matched_success', 'matched_failure') AND (
                    NOT EXISTS (SELECT 1 FROM disbursement_provider_events WHERE id = NEW.provider_event_id AND intent_id = NEW.intent_id
                        AND disposition = 'applied' AND state = CASE NEW.decision WHEN 'matched_success' THEN 'succeeded' ELSE 'failed' END
                        AND (state = 'failed' OR effective_at IS NOT NULL))
                    OR EXISTS (SELECT 1 FROM disbursement_provider_events WHERE intent_id = NEW.intent_id
                        AND disposition IN ('after_final', 'conflict', 'key_conflict', 'unverifiable'))) THEN
                    RAISE EXCEPTION 'Only an unblocked, applied final observation of the same intent reconciles it' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_reconciliations_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_reconciliations
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_reconciliation();

            CREATE OR REPLACE FUNCTION protect_disbursement_closing() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                folded record;
                allowed boolean;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Disbursement records are append-only' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM disbursements WHERE id = NEW.disbursement_id FOR UPDATE;
                SELECT * INTO folded FROM disbursement_fold(NEW.disbursement_id);
                allowed := CASE NEW.cause
                    WHEN 'approve_recheck' THEN folded.f_state = 'awaiting_second_approver'
                        AND NOT EXISTS (SELECT 1 FROM disbursement_intents WHERE disbursement_id = NEW.disbursement_id)
                    WHEN 'worker_recheck' THEN folded.f_state = 'queued'
                        AND EXISTS (SELECT 1 FROM disbursement_intents i WHERE i.id = NEW.intent_id AND i.disbursement_id = NEW.disbursement_id)
                        AND EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase = 'recheck_failed')
                        AND NOT EXISTS (SELECT 1 FROM disbursement_dispatches WHERE intent_id = NEW.intent_id AND phase = 'claimed')
                    WHEN 'reconciled_failure' THEN folded.f_state = 'dispatched'
                        AND EXISTS (SELECT 1 FROM disbursement_reconciliations r JOIN disbursement_intents i ON i.id = r.intent_id
                            WHERE r.id = NEW.reconciliation_id AND r.intent_id = NEW.intent_id AND i.disbursement_id = NEW.disbursement_id
                                AND r.decision = 'matched_failure')
                    WHEN 'reconciled_success' THEN folded.f_state = 'dispatched'
                        AND EXISTS (SELECT 1 FROM disbursement_reconciliations r JOIN disbursement_intents i ON i.id = r.intent_id
                            JOIN disbursement_provider_events e ON e.id = r.provider_event_id
                            WHERE r.id = NEW.reconciliation_id AND r.intent_id = NEW.intent_id AND i.disbursement_id = NEW.disbursement_id
                                AND r.decision = 'matched_success' AND e.effective_at = NEW.effective_at)
                    ELSE false
                END;
                IF NOT COALESCE(allowed, false) THEN
                    RAISE EXCEPTION 'A disbursement closes once, only from a failed recheck before any send or a reconciled final outcome'
                        USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER disbursement_closings_protected BEFORE INSERT OR UPDATE OR DELETE ON disbursement_closings
                FOR EACH ROW EXECUTE FUNCTION protect_disbursement_closing();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursements IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM disbursements) OR EXISTS (SELECT 1 FROM disbursement_step_up_markers) THEN
                        RAISE EXCEPTION 'Recorded disbursements require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            foreach (['disbursement_closings', 'disbursement_reconciliations', 'disbursement_provider_events', 'disbursement_provider_calls',
                'disbursement_dispatches', 'disbursement_intents', 'disbursement_step_up_markers', 'disbursement_step_up_proofs',
                'disbursement_events', 'disbursements'] as $table) {
                Schema::drop($table);
            }
            DB::unprepared('DROP FUNCTION protect_disbursement_closing(); DROP FUNCTION protect_disbursement_reconciliation();
                DROP FUNCTION protect_disbursement_provider_event();
                DROP FUNCTION protect_disbursement_provider_call(); DROP FUNCTION protect_disbursement_dispatch();
                DROP FUNCTION protect_disbursement_intent(); DROP FUNCTION protect_disbursement_step_up_proof();
                DROP FUNCTION protect_disbursement_event(); DROP FUNCTION protect_disbursement();
                DROP FUNCTION disbursement_fold(char); DROP FUNCTION reject_disbursement_mutation();');
        });
    }
};
