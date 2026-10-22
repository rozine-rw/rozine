<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repeat raises (Erastus, 22 Oct 2026): a Business may submit a new application once each earlier submitted
 * application's raise has funded or closed. One pending submission per Business is still enforced in the database.
 *
 * Each Business holds one row in business_application_pending_slots naming its latest submission. The primary key
 * check sees rows other transactions committed after this transaction's snapshot, and replacing a slot another
 * transaction changed fails, so the rule holds under READ COMMITTED, REPEATABLE READ and SERIALIZABLE alike.
 * Slots change only from inside the submission trigger and must name an application of the same Business,
 * so direct row edits cannot clear or repoint the slot that the invariant relies on.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_applications IN ACCESS EXCLUSIVE MODE;
                CREATE TABLE business_application_pending_slots (
                    business_id varchar(26) PRIMARY KEY,
                    business_application_id varchar(26) NOT NULL UNIQUE
                );
                CREATE UNIQUE INDEX business_applications_id_business ON business_applications (id, business_id);
                ALTER TABLE business_application_pending_slots ADD CONSTRAINT business_application_pending_slots_application
                    FOREIGN KEY (business_application_id, business_id) REFERENCES business_applications (id, business_id)
                    ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
                INSERT INTO business_application_pending_slots (business_id, business_application_id)
                    SELECT business_id, id FROM business_applications WHERE status = 'submitted';
                DROP INDEX business_applications_one_pending;
                CREATE INDEX business_applications_submitted ON business_applications (business_id) WHERE status = 'submitted';
                CREATE OR REPLACE FUNCTION claim_business_application_pending_slot() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE previous varchar;
                BEGIN
                    IF NEW.status <> 'submitted' OR (TG_OP = 'UPDATE' AND OLD.status = 'submitted') THEN
                        RETURN NEW;
                    END IF;
                    SELECT business_application_id INTO previous FROM business_application_pending_slots
                        WHERE business_id = NEW.business_id FOR UPDATE;
                    IF NOT FOUND THEN
                        INSERT INTO business_application_pending_slots (business_id, business_application_id) VALUES (NEW.business_id, NEW.id);
                        RETURN NEW;
                    END IF;
                    IF NOT EXISTS (
                        SELECT 1 FROM business_campaigns c
                        WHERE c.business_application_id = previous
                            AND (EXISTS (SELECT 1 FROM primary_campaign_fundings f WHERE f.business_campaign_id = c.id)
                                OR EXISTS (SELECT 1 FROM business_campaign_closures x WHERE x.business_campaign_id = c.id))
                    ) THEN
                        RAISE EXCEPTION 'A business may have only one pending application' USING ERRCODE = '23505';
                    END IF;
                    UPDATE business_application_pending_slots SET business_application_id = NEW.id WHERE business_id = NEW.business_id;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_application_one_pending
                BEFORE INSERT OR UPDATE OF status ON business_applications
                FOR EACH ROW EXECUTE FUNCTION claim_business_application_pending_slot();
                CREATE OR REPLACE FUNCTION guard_business_application_pending_slot() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP = 'DELETE' OR pg_trigger_depth() < 2
                        OR (TG_OP = 'UPDATE' AND NEW.business_id IS DISTINCT FROM OLD.business_id) THEN
                        RAISE EXCEPTION 'Pending application slots change only through application submission' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_application_pending_slot_guard
                BEFORE INSERT OR UPDATE OR DELETE ON business_application_pending_slots
                FOR EACH ROW EXECUTE FUNCTION guard_business_application_pending_slot();
                SQL);
        });
    }

    /** Rollback is refused once any Business has a repeat submission, since the old unique index cannot hold it. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_applications IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM business_applications WHERE status = 'submitted' GROUP BY business_id HAVING count(*) > 1) THEN
                        RAISE EXCEPTION 'Repeat business applications require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER business_application_one_pending ON business_applications;
                DROP FUNCTION claim_business_application_pending_slot();
                DROP TABLE business_application_pending_slots;
                DROP FUNCTION guard_business_application_pending_slot();
                DROP INDEX business_applications_id_business;
                DROP INDEX business_applications_submitted;
                CREATE UNIQUE INDEX business_applications_one_pending ON business_applications (business_id) WHERE status = 'submitted';
                SQL);
        });
    }
};
