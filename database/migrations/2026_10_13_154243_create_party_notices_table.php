<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notices (BRS v2.0 NTF-R01): every message comes from one recorded event, keyed so the same
 * event never produces a second notice, and every delivery leaves a receipt. A notice's facts never
 * change; only its first read time is set, once. Receipts are append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_notices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('kind', 60);
            $table->string('event_key', 200)->unique();
            $table->jsonb('facts');
            $table->timestampTz('created_at', 6);
            $table->timestampTz('read_at', 6)->nullable();
            $table->index(['party_id', 'created_at', 'id'], 'party_notices_inbox');
        });
        Schema::create('party_notice_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_notice_id')->constrained('party_notices')->restrictOnDelete();
            $table->string('channel', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status', 20);
            $table->string('detail', 200)->nullable();
            $table->timestampTz('created_at', 6);
            $table->index('party_notice_id');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE party_notice_deliveries ADD CONSTRAINT party_notice_delivery_known
                CHECK ((channel = 'in_app' AND status = 'delivered' AND user_id IS NULL)
                    OR (channel = 'mail' AND status IN ('sent', 'failed') AND user_id IS NOT NULL));
            CREATE OR REPLACE FUNCTION reject_party_notice_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'UPDATE' AND OLD.read_at IS NULL AND NEW.read_at IS NOT NULL
                    AND (to_jsonb(NEW) - 'read_at') = (to_jsonb(OLD) - 'read_at') THEN
                    RETURN NEW;
                END IF;
                RAISE EXCEPTION 'Party notices are immutable once recorded' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER party_notices_immutable
            BEFORE UPDATE OR DELETE ON party_notices
            FOR EACH ROW EXECUTE FUNCTION reject_party_notice_mutation();
            CREATE OR REPLACE FUNCTION reject_party_notice_delivery_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Notice delivery receipts are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER party_notice_deliveries_immutable
            BEFORE UPDATE OR DELETE ON party_notice_deliveries
            FOR EACH ROW EXECUTE FUNCTION reject_party_notice_delivery_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('party_notice_deliveries');
        Schema::dropIfExists('party_notices');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_party_notice_delivery_mutation(); DROP FUNCTION IF EXISTS reject_party_notice_mutation();');
    }
};
