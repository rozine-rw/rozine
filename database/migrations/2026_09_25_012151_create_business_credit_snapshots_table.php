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
        Schema::create('business_credit_snapshots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->string('source_kind', 40);
            $table->text('source_reference');
            $table->text('facts')->nullable();
            $table->char('sha256', 64);
            $table->unsignedBigInteger('actor_user_id');
            $table->text('reason');
            $table->timestampTz('created_at');
            $table->unique(['business_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_credit_snapshots ADD CONSTRAINT business_credit_snapshot_state
                CHECK (revision > 0 AND source_kind = 'isolated_alpha' AND ((status = 'available' AND facts IS NOT NULL) OR (status = 'withdrawn' AND facts IS NULL)));
            CREATE OR REPLACE FUNCTION reject_business_credit_snapshot_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business credit snapshots are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_credit_snapshots_immutable
            BEFORE UPDATE OR DELETE ON business_credit_snapshots
            FOR EACH ROW EXECUTE FUNCTION reject_business_credit_snapshot_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_credit_snapshots');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_credit_snapshot_mutation()');
    }
};
