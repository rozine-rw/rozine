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
        Schema::table('business_exposure_reservations', function (Blueprint $table): void {
            $table->unique(['id', 'business_application_id', 'business_id'], 'exposure_release_parent');
        });
        Schema::create('business_application_releases', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_id');
            $table->ulid('business_application_id')->unique();
            $table->ulid('exposure_reservation_id')->unique();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['id', 'business_application_id', 'business_id', 'exposure_reservation_id'], 'release_campaign_parent');
            $table->foreign(['exposure_reservation_id', 'business_application_id', 'business_id'], 'release_exposure_parent')
                ->references(['id', 'business_application_id', 'business_id'])->on('business_exposure_reservations')->restrictOnDelete();
        });
        Schema::create('business_campaigns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_id');
            $table->ulid('business_application_id')->unique();
            $table->ulid('exposure_reservation_id')->unique();
            $table->ulid('business_application_release_id')->unique();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('principal', 9, 0);
            $table->timestampTz('live_at');
            $table->timestampTz('expires_at');
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign(['business_application_release_id', 'business_application_id', 'business_id', 'exposure_reservation_id'], 'campaign_release_parent')
                ->references(['id', 'business_application_id', 'business_id', 'exposure_reservation_id'])->on('business_application_releases')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_campaigns ADD CONSTRAINT campaign_principal_grid
                CHECK (principal >= 3000000 AND principal <= 100000000 AND mod(principal, 5000) = 0);
            ALTER TABLE business_campaigns ADD CONSTRAINT campaign_exact_deadline CHECK (expires_at = live_at + interval '30 days');
            CREATE OR REPLACE FUNCTION protect_business_release_publication() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Business release and publication records are immutable' USING ERRCODE = '23514';
                END IF;
                PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER business_application_releases_protected BEFORE INSERT OR UPDATE OR DELETE ON business_application_releases
                FOR EACH ROW EXECUTE FUNCTION protect_business_release_publication();
            CREATE TRIGGER business_campaigns_protected BEFORE INSERT OR UPDATE OR DELETE ON business_campaigns
                FOR EACH ROW EXECUTE FUNCTION protect_business_release_publication();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_application_releases, business_campaigns IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM business_application_releases) OR EXISTS (SELECT 1 FROM business_campaigns) THEN
                        RAISE EXCEPTION 'Released applications require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('business_campaigns');
            Schema::drop('business_application_releases');
            Schema::table('business_exposure_reservations', fn (Blueprint $table) => $table->dropUnique('exposure_release_parent'));
            DB::unprepared('DROP FUNCTION protect_business_release_publication()');
        });
    }
};
