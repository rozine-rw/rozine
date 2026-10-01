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
        Schema::create('primary_campaign_expiry_settlements', function (Blueprint $table): void {
            $table->ulid('business_campaign_closure_id')->primary();
            $table->foreignUlid('business_campaign_id')->unique()->constrained('business_campaigns')->restrictOnDelete();
            $table->timestampTz('created_at', 6);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE primary_campaign_expiry_settlements ADD CONSTRAINT primary_expiry_settlement_closure
                FOREIGN KEY (business_campaign_closure_id) REFERENCES business_campaign_closures(id)
                ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            CREATE OR REPLACE FUNCTION require_primary_expiry_settlement_closure() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM business_campaign_closures closure
                    JOIN business_campaigns campaign ON campaign.id = closure.business_campaign_id
                    WHERE closure.id = NEW.business_campaign_closure_id AND campaign.id = NEW.business_campaign_id
                        AND closure.phase = 'expired' AND closure.actor_user_id IS NULL
                        AND closure.closed_at = campaign.expires_at AND NEW.created_at >= campaign.expires_at) THEN
                    RAISE EXCEPTION 'Primary expiry settlement requires its exact system closure' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER primary_expiry_settlement_bound AFTER INSERT ON primary_campaign_expiry_settlements
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_expiry_settlement_closure();
            CREATE TRIGGER primary_expiry_settlements_immutable BEFORE UPDATE OR DELETE ON primary_campaign_expiry_settlements
                FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE business_campaigns, business_campaign_closures, primary_campaign_expiry_settlements IN ACCESS EXCLUSIVE MODE');
            if (DB::table('primary_campaign_expiry_settlements')->exists()) {
                throw new RuntimeException('Retained Primary expiry settlements require a forward migration.');
            }
            Schema::dropIfExists('primary_campaign_expiry_settlements');
            DB::unprepared('DROP FUNCTION require_primary_expiry_settlement_closure()');
        });
    }
};
