<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The command authority of an approve-time failed closing (#96 5925545895). The operation journal
 * records a command only after its effect returns, so inside the approve that closes, its
 * `command_operations` row does not exist yet. The closing therefore retains the approve's actor
 * and request natively (and in its digested payload), and a deferred constraint trigger requires,
 * at the outer commit, the recorded `disbursement.approve` of this disbursement by that actor and
 * request whose completed `CAMPAIGN_FAILED_CLOSING` receipt names this closing. Nothing commits an
 * approve-time closing without its own command.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disbursement_closings', function (Blueprint $table): void {
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('request_id')->nullable();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE disbursement_closings ADD CONSTRAINT disbursement_closing_authority CHECK (
                ((cause = 'approve_recheck') = (actor_user_id IS NOT NULL)) AND ((actor_user_id IS NULL) = (request_id IS NULL)));

            CREATE OR REPLACE FUNCTION authenticate_disbursement_closing_authority() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NEW.cause = 'approve_recheck' AND NOT EXISTS (SELECT 1 FROM command_operations o WHERE o.id = NEW.operation_id
                    AND o.command = 'disbursement.approve' AND o.target_type = 'disbursement' AND o.target_id = NEW.disbursement_id::text
                    AND o.actor_user_id = NEW.actor_user_id AND o.request_id = NEW.request_id
                    AND o.result->>'status' = 'completed' AND o.result->>'code' = 'CAMPAIGN_FAILED_CLOSING'
                    AND o.result->'data'->'receipt'->>'receipt_id' = NEW.id::text) THEN
                    RAISE EXCEPTION 'An approve-time failed closing commits only with its own recorded approve command' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER disbursement_closings_authority AFTER INSERT ON disbursement_closings
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION authenticate_disbursement_closing_authority();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE disbursement_closings IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM disbursement_closings WHERE actor_user_id IS NOT NULL) THEN
                        RAISE EXCEPTION 'Recorded closing authority requires a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER disbursement_closings_authority ON disbursement_closings;
                DROP FUNCTION authenticate_disbursement_closing_authority();
                ALTER TABLE disbursement_closings DROP CONSTRAINT disbursement_closing_authority;
                SQL);
            Schema::table('disbursement_closings', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('actor_user_id');
                $table->dropColumn('request_id');
            });
        });
    }
};
