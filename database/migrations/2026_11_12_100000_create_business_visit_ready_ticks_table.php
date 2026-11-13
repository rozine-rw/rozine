<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Business's ticks on its visit-ready checklist (BRS v3 VIS-R03): for the instalment due on
 * `due_on`, each tick or untick of one item is appended with the acting Party and the operation
 * that wrote it; an item's latest row is its current state. The D−11 reminder lists only the items
 * not ticked. A row is never edited or removed once written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_visit_ready_ticks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->date('due_on');
            $table->string('item', 40);
            $table->boolean('ticked');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('created_at', 6);
            $table->index(['business_id', 'due_on', 'item', 'created_at']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_visit_ready_ticks ADD CONSTRAINT business_visit_ready_tick_item CHECK (item ~ '^[a-z_]{1,40}$');
            CREATE OR REPLACE FUNCTION reject_business_visit_ready_tick_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business visit-ready ticks are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_visit_ready_ticks_immutable
            BEFORE UPDATE OR DELETE ON business_visit_ready_ticks
            FOR EACH ROW EXECUTE FUNCTION reject_business_visit_ready_tick_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_visit_ready_ticks');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_visit_ready_tick_mutation()');
    }
};
