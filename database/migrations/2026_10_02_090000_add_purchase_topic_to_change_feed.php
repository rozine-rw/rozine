<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Party-scoped `purchase` topic to the change feed (S4-E, #96 5947594768). Re-adding the
 * audience check validates every existing row as it stands: a row the new check refuses fails the
 * migration rather than being repaired, deleted or moved to another audience.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE change_feed IN ACCESS EXCLUSIVE MODE;
                ALTER TABLE change_feed DROP CONSTRAINT change_feed_topic_audience;
                ALTER TABLE change_feed ADD CONSTRAINT change_feed_topic_audience CHECK (
                    (topic IN ('wallet', 'purchase') AND party_id IS NOT NULL)
                    OR (topic = 'campaign' AND business_id IS NOT NULL)
                    OR (topic = 'staff_queue' AND staff_queue = 'applications' AND subject = staff_queue));
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE change_feed IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM change_feed WHERE topic = 'purchase') THEN
                        RAISE EXCEPTION 'Recorded purchase changes require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                ALTER TABLE change_feed DROP CONSTRAINT change_feed_topic_audience;
                ALTER TABLE change_feed ADD CONSTRAINT change_feed_topic_audience CHECK (
                    (topic = 'wallet' AND party_id IS NOT NULL)
                    OR (topic = 'campaign' AND business_id IS NOT NULL)
                    OR (topic = 'staff_queue' AND staff_queue = 'applications' AND subject = staff_queue));
                SQL);
        });
    }
};
