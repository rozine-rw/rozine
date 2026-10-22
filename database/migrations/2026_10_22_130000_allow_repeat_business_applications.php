<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repeat raises (Erastus, 22 Oct 2026): a Business may submit a new application once each earlier submitted
 * application's raise has funded or closed. One pending submission per Business is still enforced in the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_applications IN ACCESS EXCLUSIVE MODE;
                DROP INDEX business_applications_one_pending;
                CREATE INDEX business_applications_submitted ON business_applications (business_id) WHERE status = 'submitted';
                CREATE OR REPLACE FUNCTION reject_second_pending_business_application() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.status <> 'submitted' THEN
                        RETURN NEW;
                    END IF;
                    PERFORM pg_advisory_xact_lock(hashtextextended('business_application_pending:' || NEW.business_id, 0));
                    IF EXISTS (
                        SELECT 1 FROM business_applications a
                        WHERE a.business_id = NEW.business_id AND a.status = 'submitted' AND a.id <> NEW.id
                            AND NOT EXISTS (
                                SELECT 1 FROM business_campaigns c
                                WHERE c.business_application_id = a.id
                                    AND (EXISTS (SELECT 1 FROM primary_campaign_fundings f WHERE f.business_campaign_id = c.id)
                                        OR EXISTS (SELECT 1 FROM business_campaign_closures x WHERE x.business_campaign_id = c.id))
                            )
                    ) THEN
                        RAISE EXCEPTION 'A business may have only one pending application' USING ERRCODE = '23505';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER business_application_one_pending
                BEFORE INSERT OR UPDATE OF status ON business_applications
                FOR EACH ROW EXECUTE FUNCTION reject_second_pending_business_application();
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
                DROP FUNCTION reject_second_pending_business_application();
                DROP INDEX business_applications_submitted;
                CREATE UNIQUE INDEX business_applications_one_pending ON business_applications (business_id) WHERE status = 'submitted';
                SQL);
        });
    }
};
