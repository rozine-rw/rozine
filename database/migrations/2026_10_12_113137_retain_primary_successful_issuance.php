<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Once-only original exposure conversion and complete Holding certificate. No legacy row is
 * certified or backfilled. PostgreSQL binds native membership/cash/closing; the source reader
 * separately authenticates encrypted evidence. The completeness trigger locks only the
 * caller-owned closing row. Installation and empty rollback take Business -> campaign -> closing
 * table gates first, so the producer cannot wait on a later schema lock while holding an earlier
 * gate the migration still needs. Original exposure remains counted once.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE business_profiles, business_campaigns, disbursement_closings IN ACCESS EXCLUSIVE MODE');
            Schema::create('primary_campaign_issuances', function (Blueprint $table): void {
                $table->foreignUlid('business_campaign_id')->primary()->constrained('business_campaigns')->restrictOnDelete();
                $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
                $table->foreignUlid('funding_id')->unique()->constrained('primary_campaign_fundings')->restrictOnDelete();
                $table->foreignUlid('exposure_reservation_id')->unique()->constrained('business_exposure_reservations')->restrictOnDelete();
                $table->foreignUlid('disbursement_closing_id')->unique()->constrained('disbursement_closings')->restrictOnDelete();
                $table->decimal('principal', 12, 0);
                $table->integer('commitment_count');
                $table->char('holdings_sha256', 64);
                $table->text('payload');
                $table->char('sha256', 64);
                $table->timestampTz('created_at');
            });
            DB::unprepared(<<<'SQL'
            ALTER TABLE primary_campaign_issuances ADD CONSTRAINT primary_issuance_facts CHECK
                (principal > 0 AND commitment_count > 0 AND sha256 ~ '^[0-9a-f]{64}$' AND holdings_sha256 ~ '^[0-9a-f]{64}$');
            CREATE OR REPLACE FUNCTION protect_primary_campaign_issuance() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Primary campaign issuance is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER primary_campaign_issuances_immutable BEFORE UPDATE OR DELETE ON primary_campaign_issuances
                FOR EACH ROW EXECUTE FUNCTION protect_primary_campaign_issuance();
            CREATE OR REPLACE FUNCTION require_primary_campaign_issuance() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                closing disbursement_closings%ROWTYPE;
                parent disbursements%ROWTYPE;
                funding primary_campaign_fundings%ROWTYPE;
                members integer;
                total numeric;
            BEGIN
                SELECT * INTO closing FROM disbursement_closings WHERE id = NEW.disbursement_closing_id FOR UPDATE;
                SELECT * INTO parent FROM disbursements WHERE id = closing.disbursement_id;
                SELECT * INTO funding FROM primary_campaign_fundings WHERE id = NEW.funding_id;
                SELECT count(*), COALESCE(sum(principal), 0) INTO members, total FROM primary_holdings
                    WHERE business_campaign_id = NEW.business_campaign_id AND disbursement_closing_id = closing.id;
                IF closing.kind IS DISTINCT FROM 'issued' OR closing.created_at <> NEW.created_at
                    OR parent.business_campaign_id IS DISTINCT FROM NEW.business_campaign_id
                    OR parent.business_id IS DISTINCT FROM NEW.business_id OR parent.exposure_reservation_id IS DISTINCT FROM NEW.exposure_reservation_id
                    OR parent.amount IS DISTINCT FROM NEW.principal OR parent.commitment_count IS DISTINCT FROM NEW.commitment_count
                    OR funding.business_campaign_id IS DISTINCT FROM NEW.business_campaign_id OR funding.business_id IS DISTINCT FROM NEW.business_id
                    OR funding.exposure_reservation_id IS DISTINCT FROM NEW.exposure_reservation_id OR funding.principal IS DISTINCT FROM NEW.principal
                    OR members <> NEW.commitment_count OR total <> NEW.principal
                    OR EXISTS (SELECT 1 FROM primary_funding_commitments member WHERE member.funding_id = NEW.funding_id AND NOT EXISTS
                        (SELECT 1 FROM primary_holdings holding WHERE holding.commitment_id = member.commitment_id
                            AND holding.business_campaign_id = NEW.business_campaign_id AND holding.disbursement_closing_id = closing.id))
                    OR EXISTS (SELECT 1 FROM business_campaign_closures WHERE business_campaign_id = NEW.business_campaign_id)
                    OR EXISTS (SELECT 1 FROM primary_holdings holding WHERE holding.business_campaign_id = NEW.business_campaign_id AND NOT EXISTS
                        (SELECT 1 FROM ledger_entries entry WHERE entry.kind = 'primary_issue' AND entry.source_type = 'primary_reservation'
                            AND entry.source_id = holding.primary_reservation_id AND entry.cause_type = 'disbursement_closing' AND entry.cause_id = closing.id)) THEN
                    RAISE EXCEPTION 'Primary issuance requires complete original funding Holdings cash and issued closing' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER primary_campaign_issuance_complete AFTER INSERT ON primary_campaign_issuances
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_campaign_issuance();
            SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles, business_campaigns, disbursement_closings, primary_campaign_issuances IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_campaign_issuances) THEN
                        RAISE EXCEPTION 'Retained primary issuances require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('primary_campaign_issuances');
            DB::unprepared('DROP FUNCTION protect_primary_campaign_issuance(); DROP FUNCTION require_primary_campaign_issuance();');
        });
    }
};
