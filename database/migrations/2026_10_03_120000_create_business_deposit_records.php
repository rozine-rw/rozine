<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S4-A1 layer 2b (#96 5968236797): Business deposit records under the shared ledger and registry.
 *
 * - `business_funding_methods` are synthetic, append-only Business methods with a one-way revocation.
 * - `business_deposit_intents` bind a Business wallet, its Business, the acting Party, a verified method
 *   and an active shared deposit policy. Each registers its provider reference under owner `business`
 *   through the same AFTER INSERT trigger and deferred registry key as Investor intents, so one reference
 *   routes to one owner.
 * - Dispatches, provider events and credits mirror the Investor records with the same phase, disposition
 *   and final-outcome rules.
 * - `business_deposit_credit` entries are admitted only with source `business_deposit_intent`; a deferred
 *   check binds each to its intent wallet and amounts (clearing debit = gross, `business_available`
 *   credit = net, fee revenue = fee) and to one applied success.
 * - The registry resolution check learns the Business owner; no Investor function is otherwise redefined.
 *
 * The installer takes ACCESS EXCLUSIVE locks on the tables it alters in the posting order (Business wallet,
 * entry, line); Investor writers lock no Business wallet, so it never holds a table they still need.
 * Rollback refuses once any Business method, intent or credit entry exists and restores the previous definitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_wallets, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;

                ALTER TABLE business_wallets ADD CONSTRAINT business_wallets_identity UNIQUE (id, business_id);

                CREATE TABLE business_funding_methods (
                    id char(26) PRIMARY KEY,
                    business_id char(26) NOT NULL REFERENCES business_profiles (id) ON DELETE RESTRICT,
                    kind varchar(10) NOT NULL CONSTRAINT business_funding_method_kind CHECK (kind IN ('mtn', 'airtel', 'bank')),
                    label varchar(80) NOT NULL,
                    masked varchar(40) NOT NULL,
                    reference text NOT NULL,
                    verification_source varchar(20) NOT NULL CONSTRAINT business_funding_method_synthetic CHECK (verification_source = 'synthetic'),
                    verified_at timestamptz,
                    revoked_at timestamptz,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_funding_method_revocation CHECK (revoked_at IS NULL OR (verified_at IS NOT NULL AND revoked_at >= verified_at)),
                    CONSTRAINT business_funding_methods_identity UNIQUE (id, business_id)
                );
                CREATE INDEX business_funding_methods_business ON business_funding_methods (business_id, id);

                CREATE TABLE business_deposit_intents (
                    id char(26) PRIMARY KEY,
                    owner varchar(10) NOT NULL DEFAULT 'business' CONSTRAINT business_deposit_intent_owner CHECK (owner = 'business'),
                    wallet_id char(26) NOT NULL,
                    business_id char(26) NOT NULL,
                    party_id char(26) NOT NULL REFERENCES parties (id) ON DELETE RESTRICT,
                    operation_id char(26) NOT NULL CONSTRAINT business_deposit_intents_operation UNIQUE,
                    request_id uuid NOT NULL,
                    method_id char(26) NOT NULL,
                    policy_id char(26) NOT NULL REFERENCES deposit_policies (id) ON DELETE RESTRICT,
                    amount numeric(12, 0) NOT NULL,
                    fee numeric(12, 0) NOT NULL,
                    credited numeric(12, 0) NOT NULL,
                    currency char(3) NOT NULL,
                    provider varchar(40) NOT NULL,
                    provider_reference text NOT NULL,
                    provider_reference_sha256 char(64) NOT NULL CONSTRAINT business_deposit_intents_reference UNIQUE,
                    payload text NOT NULL,
                    sha256 char(64) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_deposit_intent_amounts CHECK (currency = 'RWF' AND amount > 0 AND fee >= 0 AND credited > 0 AND amount = fee + credited),
                    CONSTRAINT business_deposit_intents_request UNIQUE (wallet_id, request_id),
                    CONSTRAINT business_deposit_intents_wallet UNIQUE (id, wallet_id),
                    CONSTRAINT business_deposit_intent_wallet FOREIGN KEY (wallet_id, business_id)
                        REFERENCES business_wallets (id, business_id) ON DELETE RESTRICT,
                    CONSTRAINT business_deposit_intent_method FOREIGN KEY (method_id, business_id)
                        REFERENCES business_funding_methods (id, business_id) ON DELETE RESTRICT,
                    CONSTRAINT business_deposit_intent_operation FOREIGN KEY (operation_id)
                        REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    CONSTRAINT business_deposit_intent_registered FOREIGN KEY (provider, provider_reference_sha256, owner, id)
                        REFERENCES provider_references (provider, reference_sha256, owner, intent_id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED
                );
                CREATE INDEX business_deposit_intents_wallet_order ON business_deposit_intents (wallet_id, id);

                CREATE TABLE business_deposit_dispatches (
                    id char(26) PRIMARY KEY,
                    intent_id char(26) NOT NULL REFERENCES business_deposit_intents (id) ON DELETE RESTRICT,
                    phase varchar(16) NOT NULL CONSTRAINT business_deposit_dispatch_phase CHECK (phase IN ('queued', 'claimed', 'acknowledged', 'unacknowledged')),
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_deposit_dispatches_phase UNIQUE (intent_id, phase)
                );
                CREATE INDEX business_deposit_dispatches_queue ON business_deposit_dispatches (phase, intent_id);
                CREATE UNIQUE INDEX business_deposit_dispatches_outcome ON business_deposit_dispatches (intent_id) WHERE phase IN ('acknowledged', 'unacknowledged');

                CREATE TABLE business_provider_events (
                    id char(26) PRIMARY KEY,
                    provider varchar(40) NOT NULL,
                    provider_event_id varchar(120) NOT NULL,
                    intent_id char(26) NOT NULL REFERENCES business_deposit_intents (id) ON DELETE RESTRICT,
                    content_sha256 char(64) NOT NULL,
                    state varchar(12) NOT NULL CONSTRAINT business_provider_event_state CHECK (state IN ('pending', 'succeeded', 'failed', 'unknown')),
                    amount numeric(12, 0) NOT NULL,
                    currency char(3) NOT NULL,
                    environment varchar(20) NOT NULL,
                    observed_at timestamptz NOT NULL,
                    disposition varchar(16) NOT NULL CONSTRAINT business_provider_event_disposition CHECK (
                        disposition IN ('applied', 'duplicate', 'after_final', 'conflict', 'mismatch', 'key_conflict')),
                    evidence text NOT NULL,
                    created_at timestamptz(6) NOT NULL,
                    CONSTRAINT business_provider_event_facts CHECK (amount >= 0 AND currency ~ '^[A-Z]{3}$'),
                    CONSTRAINT business_provider_events_content UNIQUE (provider, provider_event_id, content_sha256)
                );
                CREATE INDEX business_provider_events_intent ON business_provider_events (intent_id, id);
                CREATE UNIQUE INDEX business_provider_events_identity ON business_provider_events (provider, provider_event_id) WHERE disposition <> 'key_conflict';
                CREATE UNIQUE INDEX business_provider_events_final ON business_provider_events (intent_id)
                    WHERE disposition = 'applied' AND state IN ('succeeded', 'failed');

                CREATE TABLE business_deposit_credits (
                    id char(26) PRIMARY KEY,
                    intent_id char(26) NOT NULL CONSTRAINT business_deposit_credits_intent UNIQUE,
                    wallet_id char(26) NOT NULL,
                    ledger_entry_id char(26) NOT NULL CONSTRAINT business_deposit_credits_entry UNIQUE REFERENCES ledger_entries (id) ON DELETE RESTRICT,
                    provider_event_id char(26) NOT NULL CONSTRAINT business_deposit_credits_event UNIQUE REFERENCES business_provider_events (id) ON DELETE RESTRICT,
                    operation_id char(26) NOT NULL,
                    request_id uuid NOT NULL,
                    amount numeric(12, 0) NOT NULL CONSTRAINT business_deposit_credit_amount CHECK (amount > 0),
                    payload text NOT NULL,
                    sha256 char(64) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT business_deposit_credit_intent_wallet FOREIGN KEY (intent_id, wallet_id)
                        REFERENCES business_deposit_intents (id, wallet_id) ON DELETE RESTRICT
                );
                CREATE INDEX business_deposit_credits_wallet ON business_deposit_credits (wallet_id, id);

                CREATE OR REPLACE FUNCTION protect_business_funding_method() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP = 'DELETE' OR OLD.revoked_at IS NOT NULL OR NEW.revoked_at IS NULL
                        OR (NEW.id, NEW.business_id, NEW.kind, NEW.label, NEW.masked, NEW.reference, NEW.verification_source, NEW.verified_at, NEW.created_at)
                            IS DISTINCT FROM (OLD.id, OLD.business_id, OLD.kind, OLD.label, OLD.masked, OLD.reference, OLD.verification_source, OLD.verified_at, OLD.created_at) THEN
                        RAISE EXCEPTION 'Funding methods are append-only; only a one-way revocation is allowed' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_funding_methods_protected BEFORE UPDATE OR DELETE ON business_funding_methods
                    FOR EACH ROW EXECUTE FUNCTION protect_business_funding_method();

                CREATE OR REPLACE FUNCTION protect_business_deposit_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                    END IF;
                    PERFORM 1 FROM business_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                    IF NOT EXISTS (SELECT 1 FROM business_funding_methods WHERE id = NEW.method_id AND business_id = NEW.business_id
                            AND verified_at IS NOT NULL AND revoked_at IS NULL)
                        OR NOT EXISTS (SELECT 1 FROM deposit_policies WHERE id = NEW.policy_id AND status = 'active') THEN
                        RAISE EXCEPTION 'A deposit intent requires a verified method and an active policy' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_deposit_intents_protected BEFORE INSERT OR UPDATE OR DELETE ON business_deposit_intents
                    FOR EACH ROW EXECUTE FUNCTION protect_business_deposit_intent();
                CREATE TRIGGER business_deposit_intents_registered AFTER INSERT ON business_deposit_intents
                    FOR EACH ROW EXECUTE FUNCTION register_provider_reference();

                CREATE OR REPLACE FUNCTION protect_business_deposit_dispatch() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                    END IF;
                    IF (NEW.phase = 'claimed' AND NOT EXISTS (SELECT 1 FROM business_deposit_dispatches WHERE intent_id = NEW.intent_id AND phase = 'queued'))
                        OR (NEW.phase IN ('acknowledged', 'unacknowledged')
                            AND NOT EXISTS (SELECT 1 FROM business_deposit_dispatches WHERE intent_id = NEW.intent_id AND phase = 'claimed')) THEN
                        RAISE EXCEPTION 'Dispatch phases must follow queued, claimed, outcome' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_deposit_dispatches_protected BEFORE INSERT OR UPDATE OR DELETE ON business_deposit_dispatches
                    FOR EACH ROW EXECUTE FUNCTION protect_business_deposit_dispatch();
                CREATE TRIGGER business_provider_events_immutable BEFORE UPDATE OR DELETE ON business_provider_events
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_deposit_mutation();

                CREATE OR REPLACE FUNCTION protect_business_deposit_credit() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    intent business_deposit_intents%ROWTYPE;
                BEGIN
                    IF TG_OP <> 'INSERT' THEN
                        RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO intent FROM business_deposit_intents WHERE id = NEW.intent_id;
                    IF NEW.amount <> intent.credited OR NEW.operation_id <> intent.operation_id OR NEW.request_id <> intent.request_id
                        OR NOT EXISTS (SELECT 1 FROM business_provider_events WHERE id = NEW.provider_event_id AND intent_id = NEW.intent_id
                            AND disposition = 'applied' AND state = 'succeeded')
                        OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE id = NEW.ledger_entry_id AND wallet_id = NEW.wallet_id
                            AND kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND source_id = NEW.intent_id) THEN
                        RAISE EXCEPTION 'A deposit credit must bind its intent, applied success and ledger entry' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_deposit_credits_protected BEFORE INSERT OR UPDATE OR DELETE ON business_deposit_credits
                    FOR EACH ROW EXECUTE FUNCTION protect_business_deposit_credit();

                CREATE OR REPLACE FUNCTION require_provider_reference_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NOT ((NEW.owner = 'investor' AND (SELECT count(*) FROM wallet_deposit_intents WHERE id = NEW.intent_id
                                AND provider = NEW.provider AND provider_reference_sha256 = NEW.reference_sha256) = 1)
                            OR (NEW.owner = 'business' AND (SELECT count(*) FROM business_deposit_intents WHERE id = NEW.intent_id
                                AND provider = NEW.provider AND provider_reference_sha256 = NEW.reference_sha256) = 1)) THEN
                        RAISE EXCEPTION 'A provider reference must route to exactly one intent of its owner' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL));

                CREATE OR REPLACE FUNCTION business_deposit_credit_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    intent business_deposit_intents%ROWTYPE;
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
                    IF entry.kind IS DISTINCT FROM 'business_deposit_credit' THEN
                        RETURN;
                    END IF;
                    SELECT * INTO intent FROM business_deposit_intents WHERE id = entry.source_id;
                    IF intent.id IS NULL OR intent.wallet_id IS DISTINCT FROM entry.wallet_id THEN
                        RAISE EXCEPTION 'Business deposit credit entry % must settle a recorded intent of its wallet', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*),
                            count(*) FILTER (WHERE account.kind = 'deposit_clearing' AND line.direction = 'debit'),
                            count(*) FILTER (WHERE account.kind = 'business_available' AND account.wallet_id = entry.wallet_id AND line.direction = 'credit'),
                            count(*) FILTER (WHERE account.kind = 'deposit_fee_revenue' AND line.direction = 'credit'),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'deposit_clearing' AND line.direction = 'debit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'business_available' AND line.direction = 'credit'), 0),
                            coalesce(sum(line.amount) FILTER (WHERE account.kind = 'deposit_fee_revenue' AND line.direction = 'credit'), 0)
                        INTO line_count, clearing_lines, available_lines, fee_lines, gross, net, fee
                        FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id WHERE line.entry_id = checked_entry;
                    expected_fee_lines := CASE WHEN intent.fee > 0 THEN 1 ELSE 0 END;
                    IF clearing_lines <> 1 OR available_lines <> 1 OR fee_lines <> expected_fee_lines
                        OR line_count <> 2 + fee_lines OR gross <> intent.amount OR net <> intent.credited OR fee <> intent.fee THEN
                        RAISE EXCEPTION 'Business deposit credit entry % must debit clearing by its gross and credit available and fee revenue by its net and fee',
                            checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*) INTO bindings FROM business_deposit_credits credit
                        JOIN business_provider_events event ON event.id = credit.provider_event_id
                        WHERE credit.ledger_entry_id = checked_entry AND event.amount = intent.amount AND event.currency = intent.currency;
                    IF bindings <> 1 THEN
                        RAISE EXCEPTION 'Business deposit credit entry % must be bound to one applied success of its intent', checked_entry USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_business_deposit_credit_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM business_deposit_credit_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_business_deposit_credit_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM business_deposit_credit_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_business_deposit_credit_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.kind = 'business_deposit_credit')
                    EXECUTE FUNCTION assert_business_deposit_credit_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_business_deposit_credit_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_business_deposit_credit_line_entry_bound();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_wallets, ledger_entries, ledger_lines, business_funding_methods, business_deposit_intents
                    IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM business_funding_methods) OR EXISTS (SELECT 1 FROM business_deposit_intents)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'business_deposit_credit') THEN
                        RAISE EXCEPTION 'Recorded Business deposits require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_business_deposit_credit_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_business_deposit_credit_bound ON ledger_entries;
                DROP FUNCTION assert_business_deposit_credit_line_entry_bound();
                DROP FUNCTION assert_business_deposit_credit_entry_bound();
                DROP FUNCTION business_deposit_credit_entry_check(varchar);
                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false)));

                CREATE OR REPLACE FUNCTION require_provider_reference_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.owner <> 'investor' OR (SELECT count(*) FROM wallet_deposit_intents WHERE id = NEW.intent_id
                            AND provider = NEW.provider AND provider_reference_sha256 = NEW.reference_sha256) <> 1 THEN
                        RAISE EXCEPTION 'A provider reference must route to exactly one intent of its owner' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;

                DROP TABLE business_deposit_credits;
                DROP TABLE business_provider_events;
                DROP TABLE business_deposit_dispatches;
                DROP TABLE business_deposit_intents;
                DROP TABLE business_funding_methods;
                DROP FUNCTION protect_business_deposit_credit();
                DROP FUNCTION protect_business_deposit_dispatch();
                DROP FUNCTION protect_business_deposit_intent();
                DROP FUNCTION protect_business_funding_method();
                ALTER TABLE business_wallets DROP CONSTRAINT business_wallets_identity;
                SQL);
        });
    }
};
