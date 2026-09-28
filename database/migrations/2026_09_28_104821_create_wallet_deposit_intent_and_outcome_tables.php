<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A deposit intent is recorded with its provider-dispatch outbox row and its journal receipt in one
 * transaction, before any provider is called. Provider events are append-only evidence: an event
 * identity is recorded once, a changed body under the same identity is kept as a key conflict, and
 * only one final outcome is ever applied per intent. A credit binds exactly one balanced ledger
 * entry and one applied success to the intent it settles, under its own receipt identity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_deposit_intents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('wallet_id');
            $table->ulid('party_id');
            $table->ulid('operation_id')->unique();
            $table->uuid('request_id');
            $table->ulid('method_id');
            $table->foreignUlid('policy_id')->constrained('deposit_policies')->restrictOnDelete();
            $table->decimal('amount', 12, 0);
            $table->decimal('fee', 12, 0);
            $table->decimal('credited', 12, 0);
            $table->char('currency', 3);
            $table->string('provider', 40);
            $table->text('provider_reference');
            $table->char('provider_reference_sha256', 64)->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['wallet_id', 'request_id']);
            $table->unique(['id', 'wallet_id']);
            $table->index(['wallet_id', 'id']);
            $table->foreign(['wallet_id', 'party_id'], 'deposit_intent_wallet_party')->references(['id', 'party_id'])->on('investor_wallets')->restrictOnDelete();
            $table->foreign(['method_id', 'party_id'], 'deposit_intent_method_party')->references(['id', 'party_id'])->on('investor_funding_methods')->restrictOnDelete();
        });
        Schema::create('wallet_deposit_dispatches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intent_id')->constrained('wallet_deposit_intents')->restrictOnDelete();
            $table->string('phase', 16);
            $table->timestampTz('created_at');
            $table->unique(['intent_id', 'phase']);
            $table->index(['phase', 'intent_id']);
        });
        Schema::create('wallet_provider_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('provider', 40);
            $table->string('provider_event_id', 120);
            $table->foreignUlid('intent_id')->constrained('wallet_deposit_intents')->restrictOnDelete();
            $table->char('content_sha256', 64);
            $table->string('state', 12);
            $table->decimal('amount', 12, 0);
            $table->char('currency', 3);
            $table->string('environment', 20);
            $table->timestampTz('observed_at');
            $table->string('disposition', 16);
            $table->text('evidence');
            $table->timestampTz('created_at', 6);
            $table->unique(['provider', 'provider_event_id', 'content_sha256'], 'wallet_provider_events_content');
            $table->index(['intent_id', 'id']);
        });
        Schema::create('wallet_deposit_credits', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('intent_id')->unique();
            $table->ulid('wallet_id');
            $table->foreignUlid('ledger_entry_id')->unique()->constrained('ledger_entries')->restrictOnDelete();
            $table->foreignUlid('provider_event_id')->unique()->constrained('wallet_provider_events')->restrictOnDelete();
            $table->ulid('operation_id');
            $table->uuid('request_id');
            $table->decimal('amount', 12, 0);
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->index(['wallet_id', 'id']);
            $table->foreign(['intent_id', 'wallet_id'], 'deposit_credit_intent_wallet')->references(['id', 'wallet_id'])->on('wallet_deposit_intents')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE wallet_deposit_intents ADD CONSTRAINT deposit_intent_amounts CHECK (
                currency = 'RWF' AND amount > 0 AND fee >= 0 AND credited > 0 AND amount = fee + credited);
            ALTER TABLE wallet_deposit_intents ADD CONSTRAINT deposit_intent_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE wallet_deposit_dispatches ADD CONSTRAINT deposit_dispatch_phase CHECK (phase IN ('queued', 'claimed', 'acknowledged', 'unacknowledged'));
            CREATE UNIQUE INDEX wallet_deposit_dispatches_outcome ON wallet_deposit_dispatches (intent_id) WHERE phase IN ('acknowledged', 'unacknowledged');
            ALTER TABLE wallet_provider_events ADD CONSTRAINT provider_event_state CHECK (state IN ('pending', 'succeeded', 'failed', 'unknown'));
            ALTER TABLE wallet_provider_events ADD CONSTRAINT provider_event_disposition CHECK (
                disposition IN ('applied', 'duplicate', 'after_final', 'conflict', 'mismatch', 'key_conflict'));
            ALTER TABLE wallet_provider_events ADD CONSTRAINT provider_event_facts CHECK (amount >= 0 AND currency ~ '^[A-Z]{3}$');
            CREATE UNIQUE INDEX wallet_provider_events_identity ON wallet_provider_events (provider, provider_event_id) WHERE disposition <> 'key_conflict';
            CREATE UNIQUE INDEX wallet_provider_events_final ON wallet_provider_events (intent_id)
                WHERE disposition = 'applied' AND state IN ('succeeded', 'failed');
            ALTER TABLE wallet_deposit_credits ADD CONSTRAINT deposit_credit_amount CHECK (amount > 0);

            CREATE OR REPLACE FUNCTION reject_wallet_deposit_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
            END;
            $$;

            CREATE OR REPLACE FUNCTION protect_wallet_deposit_intent() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM investor_wallets WHERE id = NEW.wallet_id FOR UPDATE;
                IF NOT EXISTS (SELECT 1 FROM investor_funding_methods WHERE id = NEW.method_id AND party_id = NEW.party_id
                        AND verified_at IS NOT NULL AND revoked_at IS NULL)
                    OR NOT EXISTS (SELECT 1 FROM deposit_policies WHERE id = NEW.policy_id AND status = 'active') THEN
                    RAISE EXCEPTION 'A deposit intent requires a verified method and an active policy' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER wallet_deposit_intents_protected BEFORE INSERT OR UPDATE OR DELETE ON wallet_deposit_intents
                FOR EACH ROW EXECUTE FUNCTION protect_wallet_deposit_intent();

            CREATE OR REPLACE FUNCTION protect_wallet_deposit_dispatch() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                END IF;
                IF (NEW.phase = 'claimed' AND NOT EXISTS (SELECT 1 FROM wallet_deposit_dispatches WHERE intent_id = NEW.intent_id AND phase = 'queued'))
                    OR (NEW.phase IN ('acknowledged', 'unacknowledged')
                        AND NOT EXISTS (SELECT 1 FROM wallet_deposit_dispatches WHERE intent_id = NEW.intent_id AND phase = 'claimed')) THEN
                    RAISE EXCEPTION 'Dispatch phases must follow queued, claimed, outcome' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER wallet_deposit_dispatches_protected BEFORE INSERT OR UPDATE OR DELETE ON wallet_deposit_dispatches
                FOR EACH ROW EXECUTE FUNCTION protect_wallet_deposit_dispatch();

            CREATE TRIGGER wallet_provider_events_immutable BEFORE UPDATE OR DELETE ON wallet_provider_events
                FOR EACH ROW EXECUTE FUNCTION reject_wallet_deposit_mutation();

            CREATE OR REPLACE FUNCTION protect_wallet_deposit_credit() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                intent wallet_deposit_intents%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Deposit intents, dispatches, provider events and credits are immutable' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO intent FROM wallet_deposit_intents WHERE id = NEW.intent_id;
                IF NEW.amount <> intent.credited OR NEW.operation_id <> intent.operation_id OR NEW.request_id <> intent.request_id
                    OR NOT EXISTS (SELECT 1 FROM wallet_provider_events WHERE id = NEW.provider_event_id AND intent_id = NEW.intent_id
                        AND disposition = 'applied' AND state = 'succeeded')
                    OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE id = NEW.ledger_entry_id AND wallet_id = NEW.wallet_id
                        AND kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND source_id = NEW.intent_id) THEN
                    RAISE EXCEPTION 'A deposit credit must bind its intent, applied success and ledger entry' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER wallet_deposit_credits_protected BEFORE INSERT OR UPDATE OR DELETE ON wallet_deposit_credits
                FOR EACH ROW EXECUTE FUNCTION protect_wallet_deposit_credit();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE wallet_deposit_intents IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM wallet_deposit_intents) THEN
                        RAISE EXCEPTION 'Recorded deposits require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('wallet_deposit_credits');
            Schema::drop('wallet_provider_events');
            Schema::drop('wallet_deposit_dispatches');
            Schema::drop('wallet_deposit_intents');
            DB::unprepared('DROP FUNCTION protect_wallet_deposit_credit(); DROP FUNCTION protect_wallet_deposit_dispatch();
                DROP FUNCTION protect_wallet_deposit_intent(); DROP FUNCTION reject_wallet_deposit_mutation();');
        });
    }
};
