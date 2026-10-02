<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The online propagation beacon (S4-E): an append-only feed saying only *what changed* for one
 * audience. Each row is written in its effect's own transaction and carries that transaction's
 * id, so a reader can advance past a transaction only once it has finished.
 *
 * The audience ids deliberately carry no foreign key: a key would take a KEY SHARE lock on the
 * Party or Business at the end of an effect that locked its wallet first, inverting the lock
 * order `withActiveRole` (user → Party → wallet) relies on. Callers pass server-derived ids only.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE change_feed (
                id bigserial PRIMARY KEY,
                created_xid xid8 NOT NULL,
                party_id char(26) NULL,
                business_id char(26) NULL,
                staff_queue varchar(40) NULL,
                topic varchar(40) NOT NULL,
                subject varchar(64) NOT NULL,
                revision bigint NOT NULL,
                created_at timestamptz NOT NULL,
                CONSTRAINT change_feed_one_audience CHECK (num_nonnulls(party_id, business_id, staff_queue) = 1),
                CONSTRAINT change_feed_topic_audience CHECK (
                    (topic = 'wallet' AND party_id IS NOT NULL)
                    OR (topic = 'campaign' AND business_id IS NOT NULL)
                    OR (topic = 'staff_queue' AND staff_queue = 'applications' AND subject = staff_queue)),
                CONSTRAINT change_feed_subject_shape CHECK (subject ~ '^[0-9A-Za-z_]{1,64}$'),
                CONSTRAINT change_feed_revision_positive CHECK (revision > 0)
            );
            CREATE INDEX change_feed_party ON change_feed (party_id, created_xid) WHERE party_id IS NOT NULL;
            CREATE INDEX change_feed_business ON change_feed (business_id, created_xid) WHERE business_id IS NOT NULL;
            CREATE INDEX change_feed_staff ON change_feed (staff_queue, created_xid) WHERE staff_queue IS NOT NULL;
            CREATE INDEX change_feed_subject ON change_feed (topic, subject, revision);
            CREATE INDEX change_feed_created_at ON change_feed (created_at);

            -- The row's transaction and time are the database's, never the caller's.
            CREATE OR REPLACE FUNCTION change_feed_stamp() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                NEW.created_xid := pg_current_xact_id();
                NEW.created_at := now();
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER change_feed_stamped BEFORE INSERT ON change_feed
                FOR EACH ROW EXECUTE FUNCTION change_feed_stamp();

            -- Rows never change. Only the guarded prune may delete, and only a row past the 24-hour
            -- minimum retention, inside a transaction that opted in with rozine.change_feed_prune.
            CREATE OR REPLACE FUNCTION protect_change_feed() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' AND current_setting('rozine.change_feed_prune', true) = 'on'
                    AND OLD.created_at < now() - interval '24 hours' THEN
                    RETURN OLD;
                END IF;
                RAISE EXCEPTION 'Change feed rows are append-only' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER change_feed_protected BEFORE UPDATE OR DELETE ON change_feed
                FOR EACH ROW EXECUTE FUNCTION protect_change_feed();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE change_feed IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM change_feed) THEN
                        RAISE EXCEPTION 'Recorded changes require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TABLE change_feed;
                DROP FUNCTION protect_change_feed();
                DROP FUNCTION change_feed_stamp();
                SQL);
        });
    }
};
