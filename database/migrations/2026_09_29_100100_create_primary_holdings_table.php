<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROPOSED for S3-C review (#96 answer 9): the immutable Holding a commitment becomes on a
 * verified, reconciled disbursement success (MC-02, §11.2, §11.4). S3-D never writes it; the
 * `FundedCampaigns` adapter that owns Primary effects does, inside the reconciliation transaction.
 * A Holding binds one commitment once, keeps its purchased ordinals, rights and terms, and takes
 * its effective instant and Kigali date from the issued closing, never from a callback's arrival.
 *
 * UNACTIVATED (#96 5872328809): nothing inserts Holdings yet. The fail-closed `FundedCampaigns`
 * port owns the write, and the synthetic test adapter records issue without touching this table.
 * TODO(S3-C integration): reference `primary_commitments.id` and require the Holding's Party,
 * campaign, units, principal, ordinals, rights and terms to equal the retained reservation and its
 * confirmed revision, with negative cases for each, before any adapter writes here.
 *
 * Lock order: the trigger locks only the issued closing row, never the disbursement, so it cannot
 * be the first lock of an issue. The reconciliation transaction reaches it after Business → campaign
 * → disbursement (and the closing it just inserted, already its own), then Primary, then wallets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('primary_holdings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('business_campaign_id')->index();
            $table->ulid('commitment_id')->unique();
            $table->ulid('party_id')->index();
            $table->foreignUlid('disbursement_closing_id')->constrained('disbursement_closings')->restrictOnDelete();
            $table->integer('units');
            $table->decimal('principal', 12, 0);
            $table->jsonb('ordinals');
            $table->jsonb('rights');
            $table->jsonb('terms');
            $table->jsonb('schedule');
            $table->timestampTz('issued_at');
            $table->timestampTz('disbursement_effective_at');
            $table->date('effective_date');
            $table->ulid('receipt_id')->unique();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE primary_holdings ADD CONSTRAINT primary_holding_facts CHECK (
                units > 0 AND principal = units * 5000 AND jsonb_typeof(ordinals) = 'array' AND jsonb_array_length(ordinals) > 0
                AND jsonb_typeof(rights) = 'object' AND jsonb_typeof(terms) = 'object' AND jsonb_typeof(schedule) = 'array'
                AND jsonb_array_length(schedule) > 0 AND issued_at >= disbursement_effective_at);

            CREATE OR REPLACE FUNCTION protect_primary_holding() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                closing disbursement_closings%ROWTYPE;
                parent disbursements%ROWTYPE;
                issued numeric;
            BEGIN
                IF TG_OP <> 'INSERT' THEN
                    RAISE EXCEPTION 'Holdings are immutable' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO closing FROM disbursement_closings WHERE id = NEW.disbursement_closing_id FOR UPDATE;
                SELECT * INTO parent FROM disbursements WHERE id = closing.disbursement_id;
                SELECT COALESCE(sum(principal), 0) INTO issued FROM primary_holdings WHERE disbursement_closing_id = NEW.disbursement_closing_id;
                IF closing.kind IS DISTINCT FROM 'issued' OR parent.business_campaign_id <> NEW.business_campaign_id
                    OR NEW.disbursement_effective_at <> closing.effective_at OR NEW.effective_date <> closing.effective_date
                    OR issued + NEW.principal > parent.amount THEN
                    RAISE EXCEPTION 'A Holding issues once from its campaign''s issued closing, within its principal' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER primary_holdings_protected BEFORE INSERT OR UPDATE OR DELETE ON primary_holdings
                FOR EACH ROW EXECUTE FUNCTION protect_primary_holding();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_holdings IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_holdings) THEN
                        RAISE EXCEPTION 'Issued Holdings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                SQL);
            Schema::drop('primary_holdings');
            DB::unprepared('DROP FUNCTION protect_primary_holding();');
        });
    }
};
