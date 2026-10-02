<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Binds a proposed Holding (100100) to the Primary evidence S3-C retains (#175), closing the
 * TODO in 100100. Still UNACTIVATED: nothing in the application inserts Holdings; the S3-C
 * `FundedCampaigns` adapter will, inside the reconciliation transaction.
 *
 * What PostgreSQL now checks on its own, from plaintext immutable columns, at insert:
 * - `commitment_id` is a real `primary_commitments.id` (FK) that is a member of a durable funding
 *   record (FK to `primary_funding_commitments`), and the Holding's closing belongs to the
 *   disbursement of that funding record: same Business, exposure reservation and principal.
 *   The disbursement accounts for the full retained funded principal. If an approved policy ever
 *   separates gross principal from a net provider transfer, those must be recorded and reconciled
 *   as separate facts; this binding to the original principal is never the thing to weaken.
 * - campaign, Party, units and principal equal the commitment's retained reservation.
 * - `ordinals` is exactly the reservation's `ordinal_ranges`, as one canonical list of
 *   `{"first": int, "last": int}` in ascending order. #175 derives `primary_ordinal_claims` from
 *   that same column in a trigger and keeps both immutable, so this is the claimed ordinal set.
 * - the source pins: `primary_reservation_id` and `reservation_sha256` equal the reservation and
 *   its digest; `confirmation_version_id`, `confirmation_revision` and `confirmation_sha256`
 *   equal the one revision the commitment confirmed. #175 already guarantees that revision is
 *   `confirmed`, belongs to the reservation and is its last; this trigger does not repeat that.
 * - no `primary_refund` exists for the reservation. #175 refuses such a refund for a funded
 *   reservation today; the check stays for the authoritative failed-closing refund to come.
 *
 * REPRESENTATION BOUNDARY: PostgreSQL CANNOT compare `rights` or `terms`. The originals exist only
 * inside the encrypted `primary_reservations.payload` (rights, ordinals, campaign schedule) and
 * `primary_reservation_versions.payload` (terms, disclosure digest). The database reads only their
 * plaintext SHA-256 digests. It therefore proves which retained reservation and which confirmed
 * revision a Holding claims to copy, not that the copied `rights` and `terms` JSON equals them. A
 * Holding with correct pins and forged rights or terms is ACCEPTED by this trigger. The
 * application check is `HoldingSource::verify` (`RetainedHoldingSource`), which decrypts the
 * retained evidence, re-derives both digests and compares ordinals, rights and terms; the adapter
 * must write from `HoldingSource::facts` and verify in the same transaction.
 *
 * Not checked here: the matching `primary_issue` posting and a refund after a Holding (both in
 * 114217), or exposure conversion, which stays with the S3-C issue integration.
 *
 * Locks: the trigger still locks only the issued closing row (FOR UPDATE, as 100100). It reads the
 * Primary, funding and ledger rows without row locks. The two commitment FKs and the reservation
 * and revision FKs take FOR KEY SHARE on rows that are never updated or deleted; that waits only
 * behind a FOR UPDATE holder (the funding lock takes reservations and commitments FOR UPDATE) and
 * never blocks one. The issuing transaction already holds Business → campaign → disbursement and
 * its closing before it reaches Primary rows, then Party-sorted wallets, then the ledger, so this
 * adds no lock ahead of that order.
 *
 * Install locks, before anything else, every table it alters or references, in the order commands
 * write them: reservations, revisions, commitments, funding members (SHARE ROW EXCLUSIVE, all an
 * added foreign key needs), then Holdings (ACCESS EXCLUSIVE). `down()` takes the same five in the
 * same order in ACCESS EXCLUSIVE mode, because dropping a foreign key needs that on the referenced
 * table too; dropping them one by one locked revisions before reservations and deadlocked against
 * a reserve in flight (found by PrimaryHoldingInstallProbeTest). Neither direction touches a
 * Business, wallet, ledger or `command_operations` table or upgrades a lock it already holds.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments, primary_funding_commitments IN SHARE ROW EXCLUSIVE MODE;
                LOCK TABLE primary_holdings IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_holdings) THEN
                        RAISE EXCEPTION 'Existing Holdings require verified source pins through a forward migration' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                ALTER TABLE primary_holdings
                    ADD COLUMN primary_reservation_id char(26) NOT NULL,
                    ADD COLUMN reservation_sha256 char(64) NOT NULL,
                    ADD COLUMN confirmation_version_id char(26) NOT NULL,
                    ADD COLUMN confirmation_revision integer NOT NULL,
                    ADD COLUMN confirmation_sha256 char(64) NOT NULL,
                    ADD CONSTRAINT primary_holding_commitment FOREIGN KEY (commitment_id)
                        REFERENCES primary_commitments (id) ON DELETE RESTRICT,
                    ADD CONSTRAINT primary_holding_funding_member FOREIGN KEY (commitment_id)
                        REFERENCES primary_funding_commitments (commitment_id) ON DELETE RESTRICT,
                    ADD CONSTRAINT primary_holding_reservation FOREIGN KEY (primary_reservation_id)
                        REFERENCES primary_reservations (id) ON DELETE RESTRICT,
                    ADD CONSTRAINT primary_holding_confirmation FOREIGN KEY (confirmation_version_id, primary_reservation_id)
                        REFERENCES primary_reservation_versions (id, primary_reservation_id) ON DELETE RESTRICT;

                CREATE OR REPLACE FUNCTION protect_primary_holding() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    closing disbursement_closings%ROWTYPE;
                    parent disbursements%ROWTYPE;
                    issued numeric;
                    commitment primary_commitments%ROWTYPE;
                    funding primary_campaign_fundings%ROWTYPE;
                    reservation primary_reservations%ROWTYPE;
                    confirmation primary_reservation_versions%ROWTYPE;
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
                    SELECT * INTO commitment FROM primary_commitments WHERE id = NEW.commitment_id;
                    SELECT f.* INTO funding FROM primary_funding_commitments m JOIN primary_campaign_fundings f ON f.id = m.funding_id
                        WHERE m.commitment_id = NEW.commitment_id;
                    IF funding.id IS NULL THEN
                        RAISE EXCEPTION 'A Holding must name a commitment retained in a funding record' USING ERRCODE = '23514';
                    END IF;
                    IF funding.business_id <> parent.business_id OR funding.exposure_reservation_id <> parent.exposure_reservation_id
                        OR funding.principal <> parent.amount THEN
                        RAISE EXCEPTION 'A Holding must issue from the disbursement of its commitment''s funding record' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO reservation FROM primary_reservations WHERE id = commitment.primary_reservation_id;
                    SELECT * INTO confirmation FROM primary_reservation_versions WHERE id = commitment.primary_reservation_version_id;
                    IF reservation.business_campaign_id <> NEW.business_campaign_id OR reservation.party_id <> NEW.party_id
                        OR reservation.units <> NEW.units OR reservation.principal <> NEW.principal THEN
                        RAISE EXCEPTION 'A Holding must keep its commitment''s campaign, Party, units and principal' USING ERRCODE = '23514';
                    END IF;
                    IF NEW.ordinals IS DISTINCT FROM (SELECT jsonb_agg(jsonb_build_object('first', lower(claimed), 'last', upper(claimed) - 1) ORDER BY lower(claimed))
                        FROM unnest(reservation.ordinal_ranges) claimed) THEN
                        RAISE EXCEPTION 'A Holding must keep exactly its reservation''s claimed ordinals' USING ERRCODE = '23514';
                    END IF;
                    IF NEW.primary_reservation_id <> reservation.id OR NEW.reservation_sha256 <> reservation.sha256
                        OR NEW.confirmation_version_id <> confirmation.id OR NEW.confirmation_revision <> confirmation.revision
                        OR NEW.confirmation_sha256 <> confirmation.sha256 THEN
                        RAISE EXCEPTION 'A Holding must pin its reservation and the revision its commitment confirmed' USING ERRCODE = '23514';
                    END IF;
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation' AND source_id = reservation.id
                        AND kind = 'primary_refund') THEN
                        RAISE EXCEPTION 'A refunded commitment cannot become a Holding' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                SQL);
        });
    }

    /** Issued Holdings require a forward migration; otherwise 100100's table and trigger body are restored exactly. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments, primary_funding_commitments, primary_holdings
                    IN ACCESS EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_holdings) THEN
                        RAISE EXCEPTION 'Issued Holdings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
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
                ALTER TABLE primary_holdings
                    DROP CONSTRAINT primary_holding_confirmation,
                    DROP CONSTRAINT primary_holding_reservation,
                    DROP CONSTRAINT primary_holding_funding_member,
                    DROP CONSTRAINT primary_holding_commitment,
                    DROP COLUMN confirmation_sha256,
                    DROP COLUMN confirmation_revision,
                    DROP COLUMN confirmation_version_id,
                    DROP COLUMN reservation_sha256,
                    DROP COLUMN primary_reservation_id;
                SQL);
        });
    }
};
