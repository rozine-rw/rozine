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
        Schema::create('business_exposure_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->ulid('business_application_id')->unique();
            $table->ulid('business_application_submission_id')->unique();
            $table->decimal('principal', 9, 0);
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->index(['business_id', 'id']);
            $table->foreign(['business_application_id', 'business_id'], 'exposure_application_business')
                ->references(['id', 'business_id'])->on('business_applications')->restrictOnDelete();
            $table->foreign(['business_application_id', 'business_application_submission_id'], 'exposure_submission_application')
                ->references(['business_application_id', 'id'])->on('business_application_submissions')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_exposure_reservations ADD CONSTRAINT exposure_principal_grid
                CHECK (principal >= 3000000 AND principal <= 100000000 AND mod(principal, 5000) = 0);
            CREATE OR REPLACE FUNCTION protect_business_exposure_reservation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Business exposure reservations are immutable' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER business_exposure_reservations_protected
                BEFORE INSERT OR UPDATE OR DELETE ON business_exposure_reservations
                FOR EACH ROW EXECUTE FUNCTION protect_business_exposure_reservation();
            SQL);
    }

    /** Accepted commitments require a forward migration once any reservation exists. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_exposure_reservations IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM business_exposure_reservations) THEN
                        RAISE EXCEPTION 'Reserved exposure requires a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('business_exposure_reservations');
            DB::unprepared('DROP FUNCTION protect_business_exposure_reservation()');
        });
    }
};
