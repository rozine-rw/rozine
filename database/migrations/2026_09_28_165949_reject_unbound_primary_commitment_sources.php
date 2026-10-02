<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Commitment postings remain unavailable until their retained source binding is implemented. */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;
                ALTER TABLE ledger_entries ADD CONSTRAINT primary_commitment_source_unavailable
                    CHECK (source_type <> 'primary_commitment');
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind <> 'deposit_credit') THEN
                        RAISE EXCEPTION 'Recorded Primary postings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE ledger_entries DROP CONSTRAINT primary_commitment_source_unavailable;
                SQL);
        });
    }
};
