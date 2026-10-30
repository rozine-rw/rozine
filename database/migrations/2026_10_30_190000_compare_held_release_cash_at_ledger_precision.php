<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ledger entries keep `created_at` to the whole second (timestamp(0)), while reservation roots and
 * versions keep microseconds. A hold or release posted in the same second as its root or version
 * therefore read as earlier than it, so every released or expired hold failed its retirement check
 * and the next reserve on that campaign answered 500. The ordering is now compared at the ledger's
 * precision; every other condition of `check_primary_held_release` is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$ DECLARE definition text := pg_get_functiondef('check_primary_held_release(char)'::regprocedure);
            BEGIN
                IF array_length(string_to_array(definition, 'OR h.created_at < r.created_at OR e.created_at < v.created_at'), 1) IS DISTINCT FROM 2 THEN
                    RAISE EXCEPTION 'Held release check no longer compares cash and reservation times as expected' USING ERRCODE = '23514';
                END IF;
                EXECUTE replace(definition, 'OR h.created_at < r.created_at OR e.created_at < v.created_at',
                    'OR h.created_at < date_trunc(''second'', r.created_at) OR e.created_at < date_trunc(''second'', v.created_at)');
            END; $$;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$ DECLARE definition text := pg_get_functiondef('check_primary_held_release(char)'::regprocedure);
            BEGIN
                IF array_length(string_to_array(definition, 'OR h.created_at < date_trunc(''second'', r.created_at) OR e.created_at < date_trunc(''second'', v.created_at)'), 1) IS DISTINCT FROM 2 THEN
                    RAISE EXCEPTION 'Held release check no longer compares cash and reservation times as expected' USING ERRCODE = '23514';
                END IF;
                EXECUTE replace(definition, 'OR h.created_at < date_trunc(''second'', r.created_at) OR e.created_at < date_trunc(''second'', v.created_at)',
                    'OR h.created_at < r.created_at OR e.created_at < v.created_at');
            END; $$;
            SQL);
    }
};
