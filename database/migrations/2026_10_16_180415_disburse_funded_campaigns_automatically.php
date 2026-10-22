<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BRS v2.0 SVC-R01 and ADM-R02, ruled on #154 (16 Oct 2026): a funded campaign disburses automatically,
 * with no approval screen and no second signatory.
 *
 * - `auto_intent_recorded` is the system's own event: no actor, operation or request, and it binds the
 *   destination and binding digests the `authorized` event bound before. It moves a `ready` (or a
 *   legacy `awaiting_second_approver`) disbursement straight to `queued`.
 * - A system intent names no maker or checker and carries the rule it was recorded under
 *   (`brs-2.0-svc-r01`). Its operation id is the provider idempotency key and is never a staff
 *   command, so the operation foreign key becomes a deferred check that still requires a legacy
 *   intent's recorded command to exist, as the foreign key did.
 * - `auto_recheck` closes a funded campaign whose recheck fails before any intent exists, with no
 *   staff authority, from `ready` or `awaiting_second_approver`; Primary's funded failure authority
 *   accepts it the same way it accepted an approve-time failure.
 * - One authorised staff member may release a hold, including the one who placed it.
 *
 * Legacy maker/checker rows keep folding and closing exactly as before. Rollback refuses once any
 * automatic event, system intent or automatic closing exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursements, disbursement_events, disbursement_intents, disbursement_closings IN ACCESS EXCLUSIVE MODE;

                ALTER TABLE disbursement_events DROP CONSTRAINT disbursement_event_kind;
                ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_kind CHECK (
                    kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded', 'auto_intent_recorded', 'dispatched', 'succeeded', 'failed_closing'));
                ALTER TABLE disbursement_events DROP CONSTRAINT disbursement_event_attribution;
                ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_attribution CHECK (
                    revision > 0 AND (
                        (kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded')
                            AND actor_user_id IS NOT NULL AND operation_id IS NOT NULL AND request_id IS NOT NULL)
                        OR (kind IN ('auto_intent_recorded', 'dispatched', 'succeeded') AND actor_user_id IS NULL AND operation_id IS NULL AND request_id IS NULL)
                        OR (kind = 'failed_closing' AND ((actor_user_id IS NULL) = (operation_id IS NULL)) AND ((operation_id IS NULL) = (request_id IS NULL))))
                    AND ((kind IN ('authorized', 'auto_intent_recorded')) = (binding_sha256 IS NOT NULL))
                    AND ((kind IN ('authorized', 'auto_intent_recorded')) = (destination_sha256 IS NOT NULL)));

                ALTER TABLE disbursement_intents ALTER COLUMN maker_user_id DROP NOT NULL, ALTER COLUMN checker_user_id DROP NOT NULL,
                    ADD COLUMN rule_version varchar(40);
                ALTER TABLE disbursement_intents DROP CONSTRAINT disbursement_intent_facts;
                ALTER TABLE disbursement_intents ADD CONSTRAINT disbursement_intent_facts CHECK (
                    currency = 'RWF' AND amount > 0 AND COALESCE(
                        (maker_user_id IS NOT NULL AND checker_user_id IS NOT NULL AND maker_user_id <> checker_user_id AND rule_version IS NULL AND revision > 0)
                        OR (maker_user_id IS NULL AND checker_user_id IS NULL AND rule_version = 'brs-2.0-svc-r01' AND revision >= 0), false));
                ALTER TABLE disbursement_intents DROP CONSTRAINT disbursement_intent_operation;

                CREATE OR REPLACE FUNCTION require_disbursement_intent_operation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.rule_version IS NULL AND NOT EXISTS (SELECT 1 FROM command_operations o WHERE o.id = NEW.operation_id) THEN
                        RAISE EXCEPTION 'A staff-approved disbursement intent commits only with its recorded command' USING ERRCODE = '23514';
                    END IF;
                    IF NEW.rule_version IS NOT NULL AND EXISTS (SELECT 1 FROM command_operations o WHERE o.id = NEW.operation_id) THEN
                        RAISE EXCEPTION 'An automatic disbursement intent is never a staff command' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER disbursement_intents_operation AFTER INSERT ON disbursement_intents
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_disbursement_intent_operation();

                ALTER TABLE disbursement_closings DROP CONSTRAINT disbursement_closing_facts;
                ALTER TABLE disbursement_closings ADD CONSTRAINT disbursement_closing_facts CHECK (COALESCE(
                    jsonb_typeof(causes) = 'array' AND (
                        (kind = 'issued' AND cause = 'reconciled_success' AND intent_id IS NOT NULL AND reconciliation_id IS NOT NULL
                            AND effective_at IS NOT NULL AND effective_date IS NOT NULL AND jsonb_typeof(due_dates) = 'array'
                            AND jsonb_array_length(due_dates) > 0 AND operation_id IS NULL)
                        OR (kind = 'failed_closing' AND effective_at IS NULL AND effective_date IS NULL AND due_dates IS NULL AND (
                            (cause = 'approve_recheck' AND intent_id IS NULL AND reconciliation_id IS NULL AND operation_id IS NOT NULL
                                AND jsonb_array_length(causes) > 0)
                            OR (cause = 'auto_recheck' AND intent_id IS NULL AND reconciliation_id IS NULL AND operation_id IS NULL
                                AND jsonb_array_length(causes) > 0)
                            OR (cause = 'worker_recheck' AND intent_id IS NOT NULL AND reconciliation_id IS NULL AND operation_id IS NULL
                                AND jsonb_array_length(causes) > 0)
                            OR (cause = 'reconciled_failure' AND intent_id IS NOT NULL AND reconciliation_id IS NOT NULL AND operation_id IS NULL)))), false));

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
                        ELSIF e.kind = 'auto_intent_recorded' THEN
                            f_state := 'queued'; f_maker := NULL; f_checker := NULL;
                        ELSE
                            f_state := e.kind;
                        END IF;
                    END LOOP;
                END;
                $$;

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
                        WHEN 'hold_released' THEN folded.f_state = 'on_hold'
                        WHEN 'intent_recorded' THEN folded.f_state = 'awaiting_second_approver' AND NEW.actor_user_id <> folded.f_maker
                            AND EXISTS (SELECT 1 FROM disbursement_intents i WHERE i.disbursement_id = NEW.disbursement_id
                                AND i.operation_id = NEW.operation_id AND i.checker_user_id = NEW.actor_user_id AND i.maker_user_id = folded.f_maker)
                        WHEN 'auto_intent_recorded' THEN folded.f_state IN ('ready', 'awaiting_second_approver')
                            AND EXISTS (SELECT 1 FROM disbursement_intents i JOIN disbursement_dispatches d ON d.intent_id = i.id AND d.phase = 'queued'
                                WHERE i.disbursement_id = NEW.disbursement_id AND i.rule_version IS NOT NULL AND i.maker_user_id IS NULL
                                    AND i.checker_user_id IS NULL AND i.revision = folded.f_revision
                                    AND i.binding_sha256 = NEW.binding_sha256 AND i.destination_sha256 = NEW.destination_sha256)
                        WHEN 'dispatched' THEN folded.f_state = 'queued'
                            AND EXISTS (SELECT 1 FROM disbursement_intents i JOIN disbursement_dispatches d ON d.intent_id = i.id
                                WHERE i.disbursement_id = NEW.disbursement_id AND d.phase IN ('sent', 'unsent'))
                        WHEN 'succeeded' THEN folded.f_state = 'dispatched'
                            AND EXISTS (SELECT 1 FROM disbursement_closings c WHERE c.disbursement_id = NEW.disbursement_id AND c.kind = 'issued')
                        WHEN 'failed_closing' THEN (folded.f_state IN ('awaiting_second_approver', 'queued', 'dispatched')
                                OR (folded.f_state = 'ready' AND NEW.actor_user_id IS NULL AND EXISTS (SELECT 1 FROM disbursement_closings c
                                    WHERE c.disbursement_id = NEW.disbursement_id AND c.cause = 'auto_recheck')))
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
                    IF NEW.amount <> parent.amount - parent.platform_fee OR NEW.currency <> parent.currency
                        OR NEW.commitments_digest <> parent.commitments_digest OR NEW.environment <> parent.environment
                        OR NEW.revision <> folded.f_revision THEN
                        RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                    END IF;
                    IF NEW.rule_version IS NOT NULL THEN
                        IF folded.f_state NOT IN ('ready', 'awaiting_second_approver') THEN
                            RAISE EXCEPTION 'An automatic disbursement intent is recorded only for a disbursement that is not held or in flight' USING ERRCODE = '23514';
                        END IF;
                        RETURN NEW;
                    END IF;
                    SELECT * INTO authorized_event FROM disbursement_events WHERE disbursement_id = NEW.disbursement_id AND kind = 'authorized'
                        ORDER BY revision DESC LIMIT 1;
                    IF folded.f_state <> 'awaiting_second_approver' OR NEW.maker_user_id IS DISTINCT FROM folded.f_maker OR NEW.checker_user_id = folded.f_maker
                        OR NEW.binding_sha256 IS DISTINCT FROM authorized_event.binding_sha256 OR NEW.destination_sha256 IS DISTINCT FROM authorized_event.destination_sha256 THEN
                        RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;

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
                        WHEN 'auto_recheck' THEN folded.f_state IN ('ready', 'awaiting_second_approver')
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

                DO $$ DECLARE definition text := pg_get_functiondef('check_primary_failed_closing_authority(varchar)'::regprocedure);
                BEGIN
                    IF array_length(string_to_array(definition, 'IF closing.cause = ''approve_recheck'' THEN'), 1) IS DISTINCT FROM 2 THEN
                        RAISE EXCEPTION 'Failed closing authority no longer branches on the approve recheck as expected' USING ERRCODE = '23514';
                    END IF;
                    EXECUTE replace(definition, 'IF closing.cause = ''approve_recheck'' THEN', 'IF closing.cause = ''auto_recheck'' THEN
                        IF closing.intent_id IS NOT NULL OR closing.operation_id IS NOT NULL OR closing.actor_user_id IS NOT NULL OR closing.request_id IS NOT NULL
                            OR closing.causes = ''[]''::jsonb
                            OR EXISTS (SELECT 1 FROM disbursement_intents WHERE disbursement_id = payment.id) THEN
                            RAISE EXCEPTION ''Automatic failure cannot refund an in-flight intent'' USING ERRCODE = ''23514'';
                        END IF;
                    ELSIF closing.cause = ''approve_recheck'' THEN');
                END; $$;
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursements, disbursement_events, disbursement_intents, disbursement_closings IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM disbursement_events WHERE kind = 'auto_intent_recorded')
                        OR EXISTS (SELECT 1 FROM disbursement_intents WHERE rule_version IS NOT NULL)
                        OR EXISTS (SELECT 1 FROM disbursement_closings WHERE cause = 'auto_recheck')
                        OR EXISTS (SELECT 1 FROM disbursement_events released JOIN disbursement_events held
                            ON held.disbursement_id = released.disbursement_id AND held.kind = 'held' AND held.revision < released.revision
                            AND held.actor_user_id = released.actor_user_id
                            WHERE released.kind = 'hold_released') THEN
                        RAISE EXCEPTION 'Recorded automatic disbursements require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DO $$ DECLARE definition text := pg_get_functiondef('check_primary_failed_closing_authority(varchar)'::regprocedure);
                    automatic text := 'IF closing.cause = ''auto_recheck'' THEN
                        IF closing.intent_id IS NOT NULL OR closing.operation_id IS NOT NULL OR closing.actor_user_id IS NOT NULL OR closing.request_id IS NOT NULL
                            OR closing.causes = ''[]''::jsonb
                            OR EXISTS (SELECT 1 FROM disbursement_intents WHERE disbursement_id = payment.id) THEN
                            RAISE EXCEPTION ''Automatic failure cannot refund an in-flight intent'' USING ERRCODE = ''23514'';
                        END IF;
                    ELSIF closing.cause = ''approve_recheck'' THEN';
                BEGIN
                    IF array_length(string_to_array(definition, automatic), 1) IS DISTINCT FROM 2 THEN
                        RAISE EXCEPTION 'Failed closing authority no longer carries the automatic branch as expected' USING ERRCODE = '23514';
                    END IF;
                    EXECUTE replace(definition, automatic, 'IF closing.cause = ''approve_recheck'' THEN');
                END; $$;

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
                        OR NEW.amount <> parent.amount - parent.platform_fee OR NEW.currency <> parent.currency
                        OR NEW.commitments_digest <> parent.commitments_digest OR NEW.environment <> parent.environment
                        OR NEW.binding_sha256 IS DISTINCT FROM authorized_event.binding_sha256 OR NEW.destination_sha256 IS DISTINCT FROM authorized_event.destination_sha256 THEN
                        RAISE EXCEPTION 'A disbursement intent must bind the authorized amount, destination and commitments' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;

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

                ALTER TABLE disbursement_closings DROP CONSTRAINT disbursement_closing_facts;
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

                DROP TRIGGER disbursement_intents_operation ON disbursement_intents;
                DROP FUNCTION require_disbursement_intent_operation();
                ALTER TABLE disbursement_intents ADD CONSTRAINT disbursement_intent_operation FOREIGN KEY (operation_id)
                    REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
                ALTER TABLE disbursement_intents DROP CONSTRAINT disbursement_intent_facts;
                ALTER TABLE disbursement_intents ADD CONSTRAINT disbursement_intent_facts CHECK (
                    currency = 'RWF' AND amount > 0 AND revision > 0 AND maker_user_id <> checker_user_id);
                ALTER TABLE disbursement_intents DROP COLUMN rule_version;
                ALTER TABLE disbursement_intents ALTER COLUMN maker_user_id SET NOT NULL, ALTER COLUMN checker_user_id SET NOT NULL;

                ALTER TABLE disbursement_events DROP CONSTRAINT disbursement_event_attribution;
                ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_attribution CHECK (
                    revision > 0 AND (
                        (kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded')
                            AND actor_user_id IS NOT NULL AND operation_id IS NOT NULL AND request_id IS NOT NULL)
                        OR (kind IN ('dispatched', 'succeeded') AND actor_user_id IS NULL AND operation_id IS NULL AND request_id IS NULL)
                        OR (kind = 'failed_closing' AND ((actor_user_id IS NULL) = (operation_id IS NULL)) AND ((operation_id IS NULL) = (request_id IS NULL))))
                    AND ((kind = 'authorized') = (binding_sha256 IS NOT NULL)) AND ((kind = 'authorized') = (destination_sha256 IS NOT NULL)));
                ALTER TABLE disbursement_events DROP CONSTRAINT disbursement_event_kind;
                ALTER TABLE disbursement_events ADD CONSTRAINT disbursement_event_kind CHECK (
                    kind IN ('authorized', 'rejected', 'held', 'hold_released', 'intent_recorded', 'dispatched', 'succeeded', 'failed_closing'));
                SQL);
        });
    }
};
