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
            $table->unique(['id', 'sha256'], 'primary_publication_parent');
        });
        Schema::create('primary_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_campaign_id');
            $table->char('publication_sha256', 64);
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('origin_operation_id')->unique();
            $table->integer('units');
            $table->decimal('principal', 9, 0);
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at', 6);
            $table->timestampTz('expires_at', 6);
            $table->foreign(['business_campaign_id', 'publication_sha256'], 'primary_reservation_publication')
                ->references(['id', 'sha256'])->on('business_campaigns')->restrictOnDelete();
            $table->index(['business_campaign_id', 'id'], 'primary_campaign_reservations');
            $table->index(['party_id', 'id'], 'primary_party_reservations');
            $table->index(['expires_at', 'id'], 'primary_reservation_expiry');
        });
        Schema::create('primary_reservation_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('primary_reservation_id')->constrained('primary_reservations')->restrictOnDelete();
            $table->integer('revision');
            $table->string('state', 20);
            $table->ulid('operation_id')->nullable()->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->char('previous_sha256', 64)->nullable();
            $table->timestampTz('created_at', 6);
            $table->unique(['primary_reservation_id', 'revision'], 'primary_reservation_revision');
            $table->unique(['id', 'primary_reservation_id'], 'primary_confirmation_parent');
        });
        Schema::create('primary_commitments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('primary_reservation_id')->unique();
            $table->ulid('primary_reservation_version_id')->unique();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('confirmed_at', 6);
            $table->timestampTz('created_at', 6);
            $table->foreign(['primary_reservation_version_id', 'primary_reservation_id'], 'primary_commitment_confirmation')
                ->references(['id', 'primary_reservation_id'])->on('primary_reservation_versions')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE primary_reservations ADD CONSTRAINT primary_reservation_amount
                CHECK (units BETWEEN 1 AND 20000 AND principal = units::numeric * 5000);
            ALTER TABLE primary_reservations ADD CONSTRAINT primary_reservation_operation FOREIGN KEY (origin_operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE primary_reservation_versions ADD CONSTRAINT primary_version_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE primary_commitments ADD CONSTRAINT primary_commitment_operation FOREIGN KEY (operation_id)
                REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED;
            ALTER TABLE primary_reservation_versions ADD CONSTRAINT primary_reservation_state CHECK (
                revision > 0 AND state IN ('held', 'confirmed', 'released', 'expired')
                AND (operation_id IS NOT NULL OR state = 'expired'));
            CREATE OR REPLACE FUNCTION reject_primary_record_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Primary reservation and commitment evidence is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER primary_reservations_immutable BEFORE UPDATE OR DELETE ON primary_reservations
                FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
            CREATE TRIGGER primary_reservation_versions_immutable BEFORE UPDATE OR DELETE ON primary_reservation_versions
                FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
            CREATE TRIGGER primary_commitments_immutable BEFORE UPDATE OR DELETE ON primary_commitments
                FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
            CREATE OR REPLACE FUNCTION validate_primary_reservation() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE campaign business_campaigns%ROWTYPE;
            BEGIN
                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                IF NOT FOUND OR NEW.principal > campaign.principal OR NEW.created_at < campaign.live_at
                    OR NEW.created_at >= campaign.expires_at
                    OR NEW.expires_at <> LEAST(NEW.created_at + interval '300 seconds', campaign.expires_at)
                    OR EXISTS (SELECT 1 FROM business_campaign_closures WHERE business_campaign_id = campaign.id) THEN
                    RAISE EXCEPTION 'Reservation must bind an open publication and its exact hold window' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_reservation_valid BEFORE INSERT ON primary_reservations
                FOR EACH ROW EXECUTE FUNCTION validate_primary_reservation();
            CREATE OR REPLACE FUNCTION validate_primary_reservation_version() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE reservation primary_reservations%ROWTYPE;
            DECLARE previous primary_reservation_versions%ROWTYPE;
            BEGIN
                SELECT * INTO reservation FROM primary_reservations WHERE id = NEW.primary_reservation_id;
                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = reservation.business_campaign_id) FOR UPDATE;
                PERFORM 1 FROM business_campaigns WHERE id = reservation.business_campaign_id FOR UPDATE;
                SELECT * INTO reservation FROM primary_reservations WHERE id = NEW.primary_reservation_id FOR UPDATE;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Reservation parent is required' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO previous FROM primary_reservation_versions WHERE primary_reservation_id = reservation.id ORDER BY revision DESC LIMIT 1;
                IF NEW.revision <> COALESCE(previous.revision, 0) + 1
                    OR NEW.previous_sha256 IS DISTINCT FROM previous.sha256
                    OR NEW.created_at < COALESCE(previous.created_at, reservation.created_at)
                    OR (previous.id IS NULL AND (NEW.state <> 'held' OR NEW.created_at <> reservation.created_at OR NEW.operation_id IS DISTINCT FROM reservation.origin_operation_id))
                    OR (previous.id IS NOT NULL AND previous.state <> 'held')
                    OR (NEW.state <> 'expired' AND NEW.created_at >= reservation.expires_at)
                    OR (NEW.state = 'expired' AND NEW.created_at < reservation.expires_at) THEN
                    RAISE EXCEPTION 'Reservation revisions must extend a live hold without rewriting history' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_reservation_version_valid BEFORE INSERT ON primary_reservation_versions
                FOR EACH ROW EXECUTE FUNCTION validate_primary_reservation_version();
            CREATE OR REPLACE FUNCTION validate_primary_commitment() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE confirmation primary_reservation_versions%ROWTYPE;
            BEGIN
                SELECT * INTO confirmation FROM primary_reservation_versions WHERE id = NEW.primary_reservation_version_id;
                IF NOT FOUND OR confirmation.primary_reservation_id <> NEW.primary_reservation_id OR confirmation.state <> 'confirmed'
                    OR NEW.operation_id <> confirmation.operation_id OR NEW.confirmed_at <> confirmation.created_at
                    OR NEW.created_at < NEW.confirmed_at THEN
                    RAISE EXCEPTION 'Commitment must retain its exact confirmed reservation revision' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_commitment_valid BEFORE INSERT ON primary_commitments
                FOR EACH ROW EXECUTE FUNCTION validate_primary_commitment();
            CREATE OR REPLACE FUNCTION require_primary_reservation_evidence() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_TABLE_NAME = 'primary_reservations' THEN
                    IF NOT EXISTS (SELECT 1 FROM primary_reservation_versions WHERE primary_reservation_id = NEW.id AND revision = 1) THEN
                        RAISE EXCEPTION 'Reservation requires its initial retained revision' USING ERRCODE = '23514';
                    END IF;
                ELSIF NEW.state = 'confirmed' AND NOT EXISTS (SELECT 1 FROM primary_commitments WHERE primary_reservation_version_id = NEW.id) THEN
                    RAISE EXCEPTION 'Confirmed reservation requires a commitment in the same transaction' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER primary_reservation_evidence AFTER INSERT ON primary_reservations
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_reservation_evidence();
            CREATE CONSTRAINT TRIGGER primary_confirmation_evidence AFTER INSERT ON primary_reservation_versions
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_reservation_evidence();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations) THEN
                        RAISE EXCEPTION 'Retained Primary evidence requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('primary_commitments');
            Schema::drop('primary_reservation_versions');
            Schema::drop('primary_reservations');
            Schema::table('business_campaigns', function (Blueprint $table): void {
                $table->dropUnique('primary_publication_parent');
            });
            DB::unprepared('DROP FUNCTION reject_primary_record_mutation(), validate_primary_reservation(), validate_primary_reservation_version(), validate_primary_commitment(), require_primary_reservation_evidence()');
        });
    }
};
