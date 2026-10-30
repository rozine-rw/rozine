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
    private const string EXACT = 'OR h.created_at < r.created_at OR e.created_at < v.created_at';

    private const string LEDGER_PRECISION = "OR h.created_at < date_trunc('second', r.created_at) OR e.created_at < date_trunc('second', v.created_at)";

    public function up(): void
    {
        $this->replace(self::EXACT, self::LEDGER_PRECISION);
    }

    public function down(): void
    {
        $this->replace(self::LEDGER_PRECISION, self::EXACT);
    }

    private function replace(string $from, string $to): void
    {
        $definition = (string) DB::scalar("SELECT pg_get_functiondef('check_primary_held_release(char)'::regprocedure)");
        if (substr_count($definition, $from) !== 1) {
            throw new RuntimeException('HELD_RELEASE_CHECK_UNEXPECTED');
        }
        DB::unprepared(str_replace($from, $to, $definition));
    }
};
