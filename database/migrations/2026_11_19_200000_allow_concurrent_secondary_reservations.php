<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BRS v3.0 SEC-R04 and SEC-R05: a buyer may take some of a listing, and starting a purchase holds
 * those units, so other buyers may still reserve the units nobody holds (QA #663 retest). The one open
 * reservation per listing becomes one open reservation per buyer per listing, and a deferred check
 * keeps the units held across a listing's open reservations within what the listing still offers.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                DROP INDEX secondary_reservations_open;
                CREATE UNIQUE INDEX secondary_reservations_open_buyer ON secondary_reservations (listing_id, buyer_party_id) WHERE ended IS NULL;

                CREATE OR REPLACE FUNCTION secondary_listing_reserved_within(listing varchar) RETURNS void LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT COALESCE(sum(units), 0) FROM secondary_reservations WHERE listing_id = listing AND ended IS NULL)
                        > (SELECT CASE WHEN state = 'open' THEN remaining_units ELSE 0 END FROM secondary_listings WHERE id = listing) THEN
                        RAISE EXCEPTION 'A listing''s open reservations hold no more units than it still offers' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_secondary_reservation_within() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM secondary_listing_reserved_within(NEW.listing_id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_secondary_listing_reservations_within() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM secondary_listing_reserved_within(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER secondary_reservations_within_listing AFTER INSERT OR UPDATE ON secondary_reservations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_secondary_reservation_within();
                CREATE CONSTRAINT TRIGGER secondary_listings_reservations_within AFTER UPDATE ON secondary_listings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_secondary_listing_reservations_within();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE secondary_reservations IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM secondary_reservations WHERE ended IS NULL GROUP BY listing_id HAVING count(*) > 1) THEN
                        RAISE EXCEPTION 'Listings with more than one open reservation require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER secondary_listings_reservations_within ON secondary_listings;
                DROP TRIGGER secondary_reservations_within_listing ON secondary_reservations;
                DROP FUNCTION assert_secondary_listing_reservations_within();
                DROP FUNCTION assert_secondary_reservation_within();
                DROP FUNCTION secondary_listing_reserved_within(varchar);
                DROP INDEX secondary_reservations_open_buyer;
                CREATE UNIQUE INDEX secondary_reservations_open ON secondary_reservations (listing_id) WHERE ended IS NULL;
                SQL);
        });
    }
};
