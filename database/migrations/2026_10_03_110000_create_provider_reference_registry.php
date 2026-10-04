<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * S4-A1 §4 (#96 5968236797): one provider-reference registry across wallet owners.
 *
 * - `provider_references(provider, reference_sha256)` is the one routing key for a provider
 *   reference. Each row names its owner and the intent it routes to; `(owner, intent_id)` is unique.
 * - `wallet_deposit_intents` gains a constant `owner = 'investor'` and a deferred
 *   `(provider, provider_reference_sha256, owner, id)` key to the registry, so every Investor intent is
 *   registered under its own owner. An AFTER INSERT trigger registers it, so no caller changes.
 * - A deferred constraint trigger requires every registry row to resolve to exactly one intent of its
 *   owner with the same provider and reference, so no row routes to nothing or to the other owner.
 *
 * The backfill audits every historical intent without repair before registering any: each retained
 * reference must decrypt and hash to its recorded digest, and every provider event must carry its
 * intent's provider. Any failure refuses the whole migration. Only `wallet_deposit_intents` is altered,
 * so the installer takes one ACCESS EXCLUSIVE lock on it and holds no table a deposit writer still needs.
 * Rollback refuses once a Business registration exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE wallet_deposit_intents IN ACCESS EXCLUSIVE MODE');
            $this->auditRetainedReferences();
            DB::unprepared(<<<'SQL'
                CREATE TABLE provider_references (
                    provider varchar(40) NOT NULL,
                    reference_sha256 char(64) NOT NULL CONSTRAINT provider_reference_digest CHECK (reference_sha256 ~ '^[0-9a-f]{64}$'),
                    owner varchar(10) NOT NULL CONSTRAINT provider_reference_owner CHECK (owner IN ('investor', 'business')),
                    intent_id char(26) NOT NULL,
                    created_at timestamptz NOT NULL,
                    CONSTRAINT provider_references_pkey PRIMARY KEY (provider, reference_sha256),
                    CONSTRAINT provider_references_intent UNIQUE (owner, intent_id),
                    CONSTRAINT provider_references_binding UNIQUE (provider, reference_sha256, owner, intent_id)
                );
                INSERT INTO provider_references (provider, reference_sha256, owner, intent_id, created_at)
                    SELECT provider, provider_reference_sha256, 'investor', id, created_at FROM wallet_deposit_intents;

                ALTER TABLE wallet_deposit_intents ADD COLUMN owner varchar(10) NOT NULL DEFAULT 'investor'
                    CONSTRAINT wallet_deposit_intent_owner CHECK (owner = 'investor');
                ALTER TABLE wallet_deposit_intents ADD CONSTRAINT wallet_deposit_intent_registered
                    FOREIGN KEY (provider, provider_reference_sha256, owner, id)
                    REFERENCES provider_references (provider, reference_sha256, owner, intent_id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;

                CREATE OR REPLACE FUNCTION register_provider_reference() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    INSERT INTO provider_references (provider, reference_sha256, owner, intent_id, created_at)
                        VALUES (NEW.provider, NEW.provider_reference_sha256, NEW.owner, NEW.id, NEW.created_at);
                    RETURN NULL;
                END;
                $$;
                CREATE TRIGGER wallet_deposit_intents_registered AFTER INSERT ON wallet_deposit_intents
                    FOR EACH ROW EXECUTE FUNCTION register_provider_reference();

                CREATE OR REPLACE FUNCTION require_provider_reference_intent() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.owner <> 'investor' OR (SELECT count(*) FROM wallet_deposit_intents WHERE id = NEW.intent_id
                            AND provider = NEW.provider AND provider_reference_sha256 = NEW.reference_sha256) <> 1 THEN
                        RAISE EXCEPTION 'A provider reference must route to exactly one intent of its owner' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER provider_references_resolved AFTER INSERT ON provider_references
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_provider_reference_intent();
                CREATE TRIGGER provider_references_immutable BEFORE UPDATE OR DELETE ON provider_references
                    FOR EACH ROW EXECUTE FUNCTION reject_wallet_deposit_mutation();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE wallet_deposit_intents, provider_references IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM provider_references WHERE owner <> 'investor') THEN
                        RAISE EXCEPTION 'Business provider references exist and require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER wallet_deposit_intents_registered ON wallet_deposit_intents;
                ALTER TABLE wallet_deposit_intents DROP CONSTRAINT wallet_deposit_intent_registered;
                ALTER TABLE wallet_deposit_intents DROP COLUMN owner;
                DROP TABLE provider_references;
                DROP FUNCTION require_provider_reference_intent();
                DROP FUNCTION register_provider_reference();
                SQL);
        });
    }

    /** Refuses the migration unless every retained reference and provider event binding is exact; nothing is repaired. */
    private function auditRetainedReferences(): void
    {
        foreach (DB::table('wallet_deposit_intents')->lazyById(500, 'id') as $intent) {
            try {
                $reference = Crypt::decryptString((string) $intent->provider_reference);
            } catch (Throwable) {
                throw new RuntimeException('Retained deposit intent '.$intent->id.' has an unreadable provider reference; refusing to register references.');
            }
            if (! hash_equals((string) $intent->provider_reference_sha256, hash('sha256', $reference))) {
                throw new RuntimeException('Retained deposit intent '.$intent->id.' does not hash to its provider reference digest; refusing to register references.');
            }
        }
        $foreign = DB::table('wallet_provider_events')->join('wallet_deposit_intents', 'wallet_deposit_intents.id', '=', 'wallet_provider_events.intent_id')
            ->whereColumn('wallet_provider_events.provider', '<>', 'wallet_deposit_intents.provider')->value('wallet_provider_events.id');
        if ($foreign !== null) {
            throw new RuntimeException('Retained provider event '.$foreign.' names a different provider than its intent; refusing to register references.');
        }
    }
};
