<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM primary_reservations r JOIN business_campaigns c ON c.id = r.business_campaign_id
                    GROUP BY c.id, c.principal HAVING SUM(r.principal) > c.principal) THEN
                    RAISE EXCEPTION 'Existing Primary allocations exceed campaign capacity' USING ERRCODE = '23514';
                END IF;
            END; $$;
            CREATE OR REPLACE FUNCTION bound_primary_campaign_inventory() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE campaign business_campaigns%ROWTYPE;
            DECLARE allocated numeric;
            BEGIN
                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                SELECT COALESCE(SUM(principal), 0) INTO allocated FROM primary_reservations WHERE business_campaign_id = NEW.business_campaign_id;
                IF allocated + NEW.principal > campaign.principal THEN
                    RAISE EXCEPTION 'Primary allocations exceed the published campaign capacity' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_campaign_capacity BEFORE INSERT ON primary_reservations
                FOR EACH ROW EXECUTE FUNCTION bound_primary_campaign_inventory();
            CREATE OR REPLACE FUNCTION require_open_primary_campaign() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE campaign_id char(26);
            BEGIN
                SELECT business_campaign_id INTO campaign_id FROM primary_reservations WHERE id = NEW.primary_reservation_id;
                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = campaign_id) FOR UPDATE;
                PERFORM 1 FROM business_campaigns WHERE id = campaign_id FOR UPDATE;
                IF NEW.state IN ('held', 'confirmed') AND EXISTS (SELECT 1 FROM business_campaign_closures WHERE business_campaign_id = campaign_id) THEN
                    RAISE EXCEPTION 'A closed campaign cannot hold or confirm a reservation' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_campaign_open BEFORE INSERT ON primary_reservation_versions
                FOR EACH ROW EXECUTE FUNCTION require_open_primary_campaign();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
            LOCK TABLE primary_reservations IN ACCESS EXCLUSIVE MODE;
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM primary_reservations) THEN
                    RAISE EXCEPTION 'Retained Primary capacity protection requires a forward migration' USING ERRCODE = '23514';
                END IF;
            END; $$;
            DROP TRIGGER primary_campaign_open ON primary_reservation_versions;
            DROP TRIGGER primary_campaign_capacity ON primary_reservations;
            DROP FUNCTION require_open_primary_campaign(), bound_primary_campaign_inventory();
            SQL);
        });
    }
};
