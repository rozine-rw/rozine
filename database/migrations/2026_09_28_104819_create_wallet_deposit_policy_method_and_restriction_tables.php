<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deposit inputs for S3-B. Every row here is synthetic by constraint: a live deposit policy, a
 * really verified funding method or a real account case needs its own forward migration, policy
 * and provider evidence. A withdrawn policy version leaves deposits unavailable, never free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_policies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('version', 80)->unique();
            $table->boolean('synthetic');
            $table->string('status', 12);
            $table->decimal('fee', 12, 0)->nullable();
            $table->decimal('minimum', 12, 0)->nullable();
            $table->decimal('maximum', 12, 0)->nullable();
            $table->timestampTz('effective_at');
            $table->timestampTz('created_at');
            $table->index(['effective_at', 'id']);
        });
        Schema::create('investor_funding_methods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('kind', 10);
            $table->string('label', 80);
            $table->string('masked', 40);
            $table->text('reference');
            $table->string('verification_source', 20);
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('created_at');
            $table->unique(['id', 'party_id']);
            $table->index(['party_id', 'id']);
        });
        Schema::create('investor_account_restrictions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('cause', 30);
            $table->jsonb('scope');
            $table->string('source', 20);
            $table->timestampTz('effective_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at');
            $table->index(['party_id', 'effective_at']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE deposit_policies ADD CONSTRAINT deposit_policy_synthetic CHECK (synthetic);
            ALTER TABLE deposit_policies ADD CONSTRAINT deposit_policy_terms CHECK (
                (status = 'withdrawn' AND fee IS NULL AND minimum IS NULL AND maximum IS NULL)
                OR (status = 'active' AND fee IS NOT NULL AND fee >= 0 AND (minimum IS NULL OR minimum > 0)
                    AND (maximum IS NULL OR (maximum >= coalesce(minimum, 1) AND maximum <= 999999999999))
                    AND (fee = 0 OR (minimum IS NOT NULL AND minimum > fee))));
            ALTER TABLE investor_funding_methods ADD CONSTRAINT funding_method_kind CHECK (kind IN ('mtn', 'airtel', 'bank'));
            ALTER TABLE investor_funding_methods ADD CONSTRAINT funding_method_synthetic CHECK (verification_source = 'synthetic');
            ALTER TABLE investor_funding_methods ADD CONSTRAINT funding_method_revocation CHECK (
                revoked_at IS NULL OR (verified_at IS NOT NULL AND revoked_at >= verified_at));
            ALTER TABLE investor_account_restrictions ADD CONSTRAINT account_restriction_synthetic CHECK (source = 'synthetic');
            ALTER TABLE investor_account_restrictions ADD CONSTRAINT account_restriction_window CHECK (expires_at IS NULL OR expires_at > effective_at);
            ALTER TABLE investor_account_restrictions ADD CONSTRAINT account_restriction_scope CHECK (
                jsonb_typeof(scope) = 'array' AND jsonb_array_length(scope) > 0
                AND scope <@ '["withdrawals", "primary_commitments", "secondary_trading", "deposits"]'::jsonb
                AND ((cause = 'high_risk_hold' AND scope @> '["withdrawals", "primary_commitments", "secondary_trading"]'::jsonb
                        AND NOT scope @> '["deposits"]'::jsonb)
                    OR cause = 'external_order'));

            CREATE OR REPLACE FUNCTION reject_wallet_input_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Deposit policies and account cases are append-only' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER deposit_policies_immutable BEFORE UPDATE OR DELETE ON deposit_policies
                FOR EACH ROW EXECUTE FUNCTION reject_wallet_input_mutation();
            CREATE TRIGGER investor_account_restrictions_immutable BEFORE UPDATE OR DELETE ON investor_account_restrictions
                FOR EACH ROW EXECUTE FUNCTION reject_wallet_input_mutation();

            CREATE OR REPLACE FUNCTION protect_investor_funding_method() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' OR OLD.revoked_at IS NOT NULL OR NEW.revoked_at IS NULL
                    OR (NEW.id, NEW.party_id, NEW.kind, NEW.label, NEW.masked, NEW.reference, NEW.verification_source, NEW.verified_at, NEW.created_at)
                        IS DISTINCT FROM (OLD.id, OLD.party_id, OLD.kind, OLD.label, OLD.masked, OLD.reference, OLD.verification_source, OLD.verified_at, OLD.created_at) THEN
                    RAISE EXCEPTION 'Funding methods are append-only; only a one-way revocation is allowed' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER investor_funding_methods_protected BEFORE UPDATE OR DELETE ON investor_funding_methods
                FOR EACH ROW EXECUTE FUNCTION protect_investor_funding_method();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE deposit_policies, investor_funding_methods, investor_account_restrictions IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM deposit_policies) OR EXISTS (SELECT 1 FROM investor_funding_methods)
                        OR EXISTS (SELECT 1 FROM investor_account_restrictions) THEN
                        RAISE EXCEPTION 'Recorded deposit inputs require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('investor_account_restrictions');
            Schema::drop('investor_funding_methods');
            Schema::drop('deposit_policies');
            DB::unprepared('DROP FUNCTION protect_investor_funding_method(); DROP FUNCTION reject_wallet_input_mutation();');
        });
    }
};
