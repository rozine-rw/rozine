<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_applications ADD CONSTRAINT business_application_submission_state CHECK (
                (status = 'draft' AND step <> 'submitted' AND current_submission_id IS NULL)
                OR (status = 'submitted' AND step = 'submitted' AND current_submission_id IS NOT NULL AND current_quote_id IS NOT NULL)
            );
            CREATE UNIQUE INDEX business_applications_one_pending ON business_applications (business_id) WHERE status = 'submitted';
            ALTER TABLE business_application_submissions ADD CONSTRAINT application_submission_once UNIQUE (business_application_id);
            CREATE OR REPLACE FUNCTION reject_submitted_business_application_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.status = 'submitted' THEN
                    RAISE EXCEPTION 'Submitted business applications are immutable' USING ERRCODE = '23514';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER business_application_submitted_immutable
            BEFORE UPDATE OR DELETE ON business_applications
            FOR EACH ROW EXECUTE FUNCTION reject_submitted_business_application_mutation();
            SQL);
    }

    /** Submitted history requires a forward fix; rollback may only remove unused protection. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_applications, business_application_submissions IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM business_applications WHERE status = 'submitted')
                        OR EXISTS (SELECT 1 FROM business_application_submissions) THEN
                        RAISE EXCEPTION 'Submitted business applications require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER business_application_submitted_immutable ON business_applications;
                DROP FUNCTION reject_submitted_business_application_mutation();
                ALTER TABLE business_application_submissions DROP CONSTRAINT application_submission_once;
                DROP INDEX business_applications_one_pending;
                ALTER TABLE business_applications DROP CONSTRAINT business_application_submission_state;
                SQL);
        });
    }
};
