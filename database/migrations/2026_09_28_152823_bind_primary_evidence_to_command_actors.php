<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION require_primary_command_binding() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE reservation primary_reservations%ROWTYPE;
            DECLARE bound_operation_id char(26);
            DECLARE expected_target_kind text;
            DECLARE expected_target_id char(26);
            DECLARE expected_commands text[];
            BEGIN
                IF TG_TABLE_NAME = 'primary_reservations' THEN
                    reservation := NEW;
                    bound_operation_id := NEW.origin_operation_id;
                    expected_target_kind := 'campaign';
                    expected_target_id := NEW.business_campaign_id;
                    expected_commands := ARRAY['primary.reserve'];
                ELSE
                    SELECT * INTO reservation FROM primary_reservations WHERE id = NEW.primary_reservation_id;
                    bound_operation_id := NEW.operation_id;
                    IF bound_operation_id IS NULL AND NEW.state = 'expired' THEN
                        RETURN NULL;
                    END IF;
                    IF NEW.revision = 1 THEN
                        expected_target_kind := 'campaign';
                        expected_target_id := reservation.business_campaign_id;
                        expected_commands := ARRAY['primary.reserve'];
                    ELSE
                        expected_target_kind := 'primary_reservation';
                        expected_target_id := reservation.id;
                        expected_commands := CASE NEW.state
                            WHEN 'held' THEN ARRAY['primary.confirm']
                            WHEN 'confirmed' THEN ARRAY['primary.confirm']
                            WHEN 'released' THEN ARRAY['primary.release']
                            WHEN 'expired' THEN ARRAY['primary.confirm', 'primary.release']
                            ELSE ARRAY[]::text[] END;
                    END IF;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM command_operations o JOIN users u ON u.id = o.actor_user_id
                    WHERE o.id = bound_operation_id AND o.actor_key = 'party:' || reservation.party_id
                        AND u.party_id = reservation.party_id AND o.command = ANY(expected_commands)
                        AND o.target_type = expected_target_kind AND o.target_id = expected_target_id) THEN
                    RAISE EXCEPTION 'Primary evidence requires its Party command and target binding' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER primary_reservation_command_bound AFTER INSERT ON primary_reservations
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_command_binding();
            CREATE CONSTRAINT TRIGGER primary_version_command_bound AFTER INSERT ON primary_reservation_versions
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_command_binding();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations) THEN
                        RAISE EXCEPTION 'Retained Primary command bindings require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_reservation_command_bound ON primary_reservations;
                DROP TRIGGER primary_version_command_bound ON primary_reservation_versions;
                DROP FUNCTION require_primary_command_binding();
                SQL);
        });
    }
};
