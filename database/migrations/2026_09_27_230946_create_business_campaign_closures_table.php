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
        Schema::table('business_campaigns', function (Blueprint $table): void {
            $table->unique(['id', 'business_id', 'exposure_reservation_id', 'principal'], 'campaign_closure_parent');
            $table->index(['expires_at', 'id'], 'campaign_expiry_sweep');
        });
        Schema::create('business_campaign_closures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_campaign_id')->unique();
            $table->ulid('business_id')->index();
            $table->ulid('exposure_reservation_id')->unique();
            $table->decimal('principal', 9, 0);
            $table->string('phase', 20);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('closed_at');
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign(['business_campaign_id', 'business_id', 'exposure_reservation_id', 'principal'], 'closure_campaign_parent')
                ->references(['id', 'business_id', 'exposure_reservation_id', 'principal'])->on('business_campaigns')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_campaign_closures ADD CONSTRAINT campaign_closure_actor CHECK (
                (phase = 'cancelled' AND actor_user_id IS NOT NULL) OR (phase = 'expired' AND actor_user_id IS NULL));
            CREATE OR REPLACE FUNCTION protect_business_campaign_closure() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE campaign business_campaigns%ROWTYPE;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Campaign closure records are immutable' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;
                SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                IF NEW.closed_at < campaign.live_at OR NEW.created_at < NEW.closed_at
                    OR (NEW.phase = 'cancelled' AND NEW.closed_at >= campaign.expires_at)
                    OR (NEW.phase = 'expired' AND NEW.closed_at <> campaign.expires_at) THEN
                    RAISE EXCEPTION 'Campaign closure must respect the retained deadline' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER business_campaign_closures_protected BEFORE INSERT OR UPDATE OR DELETE ON business_campaign_closures
                FOR EACH ROW EXECUTE FUNCTION protect_business_campaign_closure();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_campaign_closures IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM business_campaign_closures) THEN
                        RAISE EXCEPTION 'Closed campaigns require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('business_campaign_closures');
            Schema::table('business_campaigns', function (Blueprint $table): void {
                $table->dropUnique('campaign_closure_parent');
                $table->dropIndex('campaign_expiry_sweep');
            });
            DB::unprepared('DROP FUNCTION protect_business_campaign_closure()');
        });
    }
};
