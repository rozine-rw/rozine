<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The named UAT wallet testers: each grant and revocation of a Party's synthetic deposits, with the
 * account it named, the operator's written reason and its request id. Each Party's changes carry
 * revisions 1, 2, 3... allocated under the tester account's lock, and the highest revision is the
 * Party's current authorization (ids and clocks never order them). The rows are append-only, so the
 * history is never rewritten. Separate from the staging mail testers, which only decide who receives
 * staging mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synthetic_wallet_tester_changes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('action', 10);
            $table->text('reason');
            $table->string('actor_key', 40);
            $table->uuid('request_id')->unique();
            $table->timestampTz('created_at');
            $table->unique(['party_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE synthetic_wallet_tester_changes ADD CONSTRAINT synthetic_wallet_tester_action CHECK (action IN ('granted', 'revoked'));
            ALTER TABLE synthetic_wallet_tester_changes ADD CONSTRAINT synthetic_wallet_tester_revision CHECK (revision > 0);
            ALTER TABLE synthetic_wallet_tester_changes ADD CONSTRAINT synthetic_wallet_tester_reason CHECK (
                length(btrim(reason)) > 0 AND char_length(reason) <= 1000);

            CREATE OR REPLACE FUNCTION reject_synthetic_wallet_tester_change() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Synthetic wallet tester changes are append-only' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER synthetic_wallet_tester_changes_immutable BEFORE UPDATE OR DELETE ON synthetic_wallet_tester_changes
                FOR EACH ROW EXECUTE FUNCTION reject_synthetic_wallet_tester_change();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE synthetic_wallet_tester_changes IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM synthetic_wallet_tester_changes) THEN
                        RAISE EXCEPTION 'Recorded wallet tester changes require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('synthetic_wallet_tester_changes');
            DB::unprepared('DROP FUNCTION reject_synthetic_wallet_tester_change();');
        });
    }
};
