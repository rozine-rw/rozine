<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Checks the issued closing itself, including zero Holdings, for retained Primary
 * funding. Existing Holding/cash guards retain their separate responsibilities.
 * Callers still require verified funding and retain the Business-first gate
 * through outer commit before authorizing any disbursement.
 * The check takes no row locks. Install/rollback take the Business table gate first,
 * before closing/funding trigger DDL, so financial callers finish or wait there.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles IN EXCLUSIVE MODE;
                LOCK TABLE disbursement_closings, primary_campaign_fundings IN SHARE ROW EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check_primary_issued_closing_complete(closing_id varchar)
                RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    closing disbursement_closings%ROWTYPE;
                    parent disbursements%ROWTYPE;
                    funding primary_campaign_fundings%ROWTYPE;
                BEGIN
                    SELECT * INTO closing FROM disbursement_closings WHERE id = closing_id;
                    IF closing.kind IS DISTINCT FROM 'issued' THEN RETURN; END IF;
                    SELECT * INTO parent FROM disbursements WHERE id = closing.disbursement_id;
                    SELECT * INTO funding FROM primary_campaign_fundings WHERE business_campaign_id = parent.business_campaign_id;
                    IF NOT FOUND THEN RETURN; END IF;
                    IF parent.business_id IS DISTINCT FROM funding.business_id
                        OR parent.exposure_reservation_id IS DISTINCT FROM funding.exposure_reservation_id
                        OR parent.amount IS DISTINCT FROM funding.principal
                        OR NOT EXISTS (SELECT 1 FROM primary_funding_commitments WHERE funding_id = funding.id)
                        OR (SELECT COALESCE(sum(principal), 0) FROM primary_holdings
                            WHERE disbursement_closing_id = closing.id) <> funding.principal
                        OR EXISTS (SELECT 1 FROM primary_funding_commitments member WHERE member.funding_id = funding.id
                            AND NOT EXISTS (SELECT 1 FROM primary_holdings holding WHERE holding.disbursement_closing_id = closing.id
                                AND holding.commitment_id = member.commitment_id AND holding.primary_reservation_id = member.reservation_id))
                        OR EXISTS (SELECT 1 FROM primary_holdings holding WHERE holding.disbursement_closing_id = closing.id
                            AND NOT EXISTS (SELECT 1 FROM primary_funding_commitments member WHERE member.funding_id = funding.id
                                AND member.commitment_id = holding.commitment_id AND member.reservation_id = holding.primary_reservation_id)) THEN
                        RAISE EXCEPTION 'An issued closing must issue a Holding for every funded commitment' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM disbursement_closings WHERE kind = 'issued' LOOP
                        PERFORM check_primary_issued_closing_complete(retained_id);
                    END LOOP;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_primary_issued_closing_complete() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_issued_closing_complete(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_issued_closing_complete AFTER INSERT ON disbursement_closings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_issued_closing_complete();

                CREATE OR REPLACE FUNCTION require_primary_funded_closing_complete() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT closing.id FROM disbursement_closings closing
                        JOIN disbursements parent ON parent.id = closing.disbursement_id
                        WHERE parent.business_campaign_id = NEW.business_campaign_id AND closing.kind = 'issued' LOOP
                        PERFORM check_primary_issued_closing_complete(retained_id);
                    END LOOP;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_funded_closing_complete AFTER INSERT ON primary_campaign_fundings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_funded_closing_complete();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE business_profiles IN EXCLUSIVE MODE;
                LOCK TABLE disbursement_closings, primary_campaign_fundings IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM disbursement_closings closing JOIN disbursements parent ON parent.id = closing.disbursement_id
                        JOIN primary_campaign_fundings funding ON funding.business_campaign_id = parent.business_campaign_id WHERE closing.kind = 'issued') THEN
                        RAISE EXCEPTION 'Issued Primary closings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER primary_issued_closing_complete ON disbursement_closings;
                DROP TRIGGER primary_funded_closing_complete ON primary_campaign_fundings;
                DROP FUNCTION require_primary_issued_closing_complete(), require_primary_funded_closing_complete(), check_primary_issued_closing_complete(varchar);
                SQL);
        });
    }
};
