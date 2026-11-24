<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BRS v3.0 §12 (SEC-R01 to R09) and LED-R02: the secondary market's records and its ledger postings.
 *
 * - `secondary_listings` is one seller's offer of whole units from one lot they hold, at a whole-franc
 *   price, for exactly seven days. Its identity, lot, quantity listed and window never change; only its
 *   price, the units still listed and its state move, and a closed listing never reopens.
 * - `secondary_reservations` is a buyer's five-minute hold on some of a listing's units. At most one is
 *   open per listing. It ends once, as `settled` or `released` (lapsed, cancelled by a halt), and nothing
 *   else about it ever changes. Each reservation needs exactly one `secondary_hold` at commit.
 * - `secondary_transfers` is a settled sale: the lowest ordinals of the seller's lot move to the buyer,
 *   and the transfer's id names the buyer's new lot. It is immutable and needs its `secondary_purchase`
 *   and `secondary_sale` postings at commit, so cash and units move together or not at all (SEC-R06).
 * - `secondary_halts` and `secondary_halt_lifts` keep Compliance's market or note halts, each with a
 *   recorded reason; both are append-only (SEC-R08).
 *
 * Ledger (the same wallet hold a commitment uses, LED-R02):
 * - `secondary_hold` moves the buyer's price from `investor_available` to `investor_held`.
 * - `secondary_release` moves it back when the reservation lapses or a halt releases it.
 * - `secondary_purchase` moves it from the buyer's `investor_held` to the platform `secondary_settlement`.
 * - `secondary_sale` moves it from `secondary_settlement` to the seller's `investor_available`, less the
 *   2% seller fee to `secondary_fee_revenue`. Purchase and sale post in one transaction.
 *
 * Rollback refuses once any listing, halt or resale posting exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;

                CREATE TABLE secondary_listings (
                    id char(26) PRIMARY KEY,
                    seller_party_id char(26) NOT NULL,
                    business_campaign_id char(26) NOT NULL,
                    lot_type varchar(20) NOT NULL CONSTRAINT secondary_listing_lot_type CHECK (lot_type IN ('primary_holding', 'secondary_transfer')),
                    lot_id char(26) NOT NULL,
                    listed_units integer NOT NULL,
                    remaining_units integer NOT NULL,
                    price numeric(12, 0) NOT NULL,
                    listed_at timestamptz(6) NOT NULL,
                    expires_at timestamptz(6) NOT NULL,
                    state varchar(12) NOT NULL CONSTRAINT secondary_listing_state CHECK (state IN ('open', 'sold', 'cancelled', 'expired')),
                    cancelled_by varchar(8) CONSTRAINT secondary_listing_cancelled_by CHECK (cancelled_by IN ('seller', 'staff', 'halt')),
                    cancel_reason text,
                    terms_version varchar(40) NOT NULL,
                    operation_id char(26) NOT NULL REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    updated_at timestamptz(6) NOT NULL,
                    CONSTRAINT secondary_listing_facts CHECK (listed_units > 0 AND remaining_units >= 0 AND remaining_units <= listed_units AND price > 0
                        AND expires_at = listed_at + interval '7 days'
                        AND (state = 'sold') = (remaining_units = 0)
                        AND (state = 'cancelled') = (cancelled_by IS NOT NULL)
                        AND (cancelled_by IN ('staff', 'halt')) = (cancel_reason IS NOT NULL AND btrim(cancel_reason) <> ''))
                );
                CREATE INDEX secondary_listings_lot ON secondary_listings (lot_type, lot_id, state);
                CREATE INDEX secondary_listings_note ON secondary_listings (business_campaign_id, state);
                CREATE INDEX secondary_listings_seller ON secondary_listings (seller_party_id, id);

                CREATE TABLE secondary_reservations (
                    id char(26) PRIMARY KEY,
                    listing_id char(26) NOT NULL REFERENCES secondary_listings (id) ON DELETE RESTRICT,
                    buyer_party_id char(26) NOT NULL,
                    business_campaign_id char(26) NOT NULL,
                    units integer NOT NULL,
                    price numeric(12, 0) NOT NULL,
                    gross numeric(14, 0) NOT NULL,
                    reserved_at timestamptz(6) NOT NULL,
                    expires_at timestamptz(6) NOT NULL,
                    operation_id char(26) NOT NULL REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    ended varchar(10) CONSTRAINT secondary_reservation_ended CHECK (ended IN ('settled', 'released')),
                    end_reason varchar(10) CONSTRAINT secondary_reservation_end_reason CHECK (end_reason IN ('settled', 'lapsed', 'halted')),
                    ended_at timestamptz(6),
                    CONSTRAINT secondary_reservation_facts CHECK (units > 0 AND price > 0 AND gross = units * price
                        AND expires_at = reserved_at + interval '5 minutes'
                        AND (ended IS NULL) = (ended_at IS NULL) AND (ended IS NULL) = (end_reason IS NULL)
                        AND (ended = 'settled') = (end_reason = 'settled'))
                );
                CREATE UNIQUE INDEX secondary_reservations_open ON secondary_reservations (listing_id) WHERE ended IS NULL;
                CREATE INDEX secondary_reservations_buyer ON secondary_reservations (buyer_party_id, business_campaign_id) WHERE ended IS NULL;
                CREATE INDEX secondary_reservations_lapsing ON secondary_reservations (expires_at) WHERE ended IS NULL;

                CREATE TABLE secondary_transfers (
                    id char(26) PRIMARY KEY,
                    reservation_id char(26) NOT NULL CONSTRAINT secondary_transfers_reservation UNIQUE REFERENCES secondary_reservations (id) ON DELETE RESTRICT,
                    listing_id char(26) NOT NULL REFERENCES secondary_listings (id) ON DELETE RESTRICT,
                    business_campaign_id char(26) NOT NULL,
                    seller_party_id char(26) NOT NULL,
                    buyer_party_id char(26) NOT NULL,
                    source_lot_type varchar(20) NOT NULL CONSTRAINT secondary_transfer_lot_type CHECK (source_lot_type IN ('primary_holding', 'secondary_transfer')),
                    source_lot_id char(26) NOT NULL,
                    units integer NOT NULL,
                    ordinals jsonb NOT NULL,
                    price numeric(12, 0) NOT NULL,
                    gross numeric(14, 0) NOT NULL,
                    seller_fee numeric(14, 0) NOT NULL,
                    seller_receives numeric(14, 0) NOT NULL,
                    terms_version varchar(40) NOT NULL,
                    operation_id char(26) NOT NULL REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    settled_at timestamptz(6) NOT NULL,
                    CONSTRAINT secondary_transfer_facts CHECK (units > 0 AND price > 0 AND gross = units * price AND seller_fee >= 0
                        AND seller_receives > 0 AND seller_fee + seller_receives = gross AND seller_party_id <> buyer_party_id
                        AND jsonb_typeof(ordinals) = 'array' AND jsonb_array_length(ordinals) > 0)
                );
                CREATE INDEX secondary_transfers_source ON secondary_transfers (source_lot_type, source_lot_id, settled_at, id);
                CREATE INDEX secondary_transfers_note ON secondary_transfers (business_campaign_id, buyer_party_id);

                CREATE TABLE secondary_halts (
                    id char(26) PRIMARY KEY,
                    business_campaign_id char(26),
                    reason text NOT NULL CONSTRAINT secondary_halt_reason CHECK (btrim(reason) <> ''),
                    staff_user_id bigint NOT NULL,
                    operation_id char(26) NOT NULL REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    halted_at timestamptz(6) NOT NULL
                );
                CREATE INDEX secondary_halts_scope ON secondary_halts (business_campaign_id);

                CREATE TABLE secondary_halt_lifts (
                    halt_id char(26) PRIMARY KEY REFERENCES secondary_halts (id) ON DELETE RESTRICT,
                    reason text NOT NULL CONSTRAINT secondary_halt_lift_reason CHECK (btrim(reason) <> ''),
                    staff_user_id bigint NOT NULL,
                    operation_id char(26) NOT NULL REFERENCES command_operations (id) ON DELETE RESTRICT DEFERRABLE INITIALLY DEFERRED,
                    lifted_at timestamptz(6) NOT NULL
                );

                CREATE OR REPLACE FUNCTION protect_secondary_listing() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'Secondary listings are never deleted' USING ERRCODE = '23514';
                    END IF;
                    IF (NEW.id, NEW.seller_party_id, NEW.business_campaign_id, NEW.lot_type, NEW.lot_id, NEW.listed_units, NEW.listed_at,
                            NEW.expires_at, NEW.terms_version, NEW.operation_id)
                        IS DISTINCT FROM (OLD.id, OLD.seller_party_id, OLD.business_campaign_id, OLD.lot_type, OLD.lot_id, OLD.listed_units,
                            OLD.listed_at, OLD.expires_at, OLD.terms_version, OLD.operation_id)
                        OR OLD.state <> 'open' OR NEW.remaining_units > OLD.remaining_units THEN
                        RAISE EXCEPTION 'A listing keeps its lot, quantity and window, and a closed listing never changes' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER secondary_listings_protected BEFORE UPDATE OR DELETE ON secondary_listings
                    FOR EACH ROW EXECUTE FUNCTION protect_secondary_listing();

                CREATE OR REPLACE FUNCTION protect_secondary_reservation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'Secondary reservations are never deleted' USING ERRCODE = '23514';
                    END IF;
                    IF OLD.ended IS NOT NULL OR NEW.ended IS NULL
                        OR (NEW.id, NEW.listing_id, NEW.buyer_party_id, NEW.business_campaign_id, NEW.units, NEW.price, NEW.gross, NEW.reserved_at,
                            NEW.expires_at, NEW.operation_id)
                        IS DISTINCT FROM (OLD.id, OLD.listing_id, OLD.buyer_party_id, OLD.business_campaign_id, OLD.units, OLD.price, OLD.gross,
                            OLD.reserved_at, OLD.expires_at, OLD.operation_id) THEN
                        RAISE EXCEPTION 'A reservation ends once and nothing else about it changes' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER secondary_reservations_protected BEFORE UPDATE OR DELETE ON secondary_reservations
                    FOR EACH ROW EXECUTE FUNCTION protect_secondary_reservation();

                CREATE OR REPLACE FUNCTION reject_secondary_record_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    RAISE EXCEPTION 'Resale transfers and halts are immutable' USING ERRCODE = '23514';
                END;
                $$;
                CREATE TRIGGER secondary_transfers_immutable BEFORE UPDATE OR DELETE ON secondary_transfers
                    FOR EACH ROW EXECUTE FUNCTION reject_secondary_record_mutation();
                CREATE TRIGGER secondary_halts_immutable BEFORE UPDATE OR DELETE ON secondary_halts
                    FOR EACH ROW EXECUTE FUNCTION reject_secondary_record_mutation();
                CREATE TRIGGER secondary_halt_lifts_immutable BEFORE UPDATE OR DELETE ON secondary_halt_lifts
                    FOR EACH ROW EXECUTE FUNCTION reject_secondary_record_mutation();

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'secondary_hold' THEN 'investor' WHEN 'secondary_release' THEN 'investor'
                        WHEN 'secondary_purchase' THEN 'investor' WHEN 'secondary_sale' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                        WHEN 'business_withdrawal_hold' THEN 'business' WHEN 'business_withdrawal_payout' THEN 'business'
                        WHEN 'business_withdrawal_return' THEN 'business' WHEN 'disbursement_platform_fee' THEN 'business'
                    END
                $$;

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('business_available', 'business_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing',
                        'platform_fee_revenue', 'audit_partner_fee_payable', 'secondary_settlement', 'secondary_fee_revenue') AND wallet_id IS NULL));

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'business_repayment_debit' AND source_type = 'business_repayment' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return') AND source_type = 'wallet_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('business_withdrawal_hold', 'business_withdrawal_payout', 'business_withdrawal_return') AND source_type = 'business_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'disbursement_platform_fee' AND source_type = 'disbursement_closing' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale') AND source_type = 'secondary_reservation'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL));

                CREATE OR REPLACE FUNCTION secondary_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    entry ledger_entries%ROWTYPE;
                    reservation secondary_reservations%ROWTYPE;
                    transfer secondary_transfers%ROWTYPE;
                    owner_party varchar;
                    line_count integer;
                    matched integer;
                BEGIN
                    SELECT * INTO entry FROM ledger_entries WHERE id = checked_entry;
                    IF entry.kind IS NULL OR entry.kind NOT IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale') THEN
                        RETURN;
                    END IF;
                    SELECT * INTO reservation FROM secondary_reservations WHERE id = entry.source_id;
                    SELECT party_id INTO owner_party FROM investor_wallets WHERE id = entry.wallet_id;
                    IF reservation.id IS NULL OR entry.origin_operation_id IS DISTINCT FROM reservation.operation_id
                        OR owner_party IS DISTINCT FROM (CASE entry.kind WHEN 'secondary_sale'
                            THEN (SELECT seller_party_id FROM secondary_listings WHERE id = reservation.listing_id) ELSE reservation.buyer_party_id END) THEN
                        RAISE EXCEPTION 'Resale posting % must belong to its reservation''s operation and the right party''s wallet', checked_entry USING ERRCODE = '23514';
                    END IF;
                    SELECT count(*) INTO line_count FROM ledger_lines WHERE entry_id = checked_entry;
                    IF entry.kind = 'secondary_sale' THEN
                        SELECT * INTO transfer FROM secondary_transfers WHERE reservation_id = reservation.id;
                        SELECT count(*) INTO matched FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                            WHERE line.entry_id = checked_entry AND (
                                (line.direction = 'debit' AND account.kind = 'secondary_settlement' AND account.wallet_id IS NULL AND line.amount = transfer.gross)
                                OR (line.direction = 'credit' AND account.kind = 'investor_available' AND account.wallet_id = entry.wallet_id
                                    AND line.amount = transfer.seller_receives)
                                OR (line.direction = 'credit' AND account.kind = 'secondary_fee_revenue' AND account.wallet_id IS NULL
                                    AND line.amount = transfer.seller_fee));
                        IF transfer.id IS NULL OR matched <> line_count OR line_count <> (CASE WHEN transfer.seller_fee > 0 THEN 3 ELSE 2 END)
                            OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'secondary_purchase' AND source_type = 'secondary_reservation'
                                AND source_id = reservation.id) THEN
                            RAISE EXCEPTION 'Resale sale % must pay the seller its transfer''s proceeds and the fee, after the purchase', checked_entry USING ERRCODE = '23514';
                        END IF;
                    ELSE
                        SELECT count(*) INTO matched FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                            WHERE line.entry_id = checked_entry AND line.amount = reservation.gross AND (
                                (line.direction = 'debit' AND account.wallet_id = entry.wallet_id
                                    AND account.kind = CASE entry.kind WHEN 'secondary_hold' THEN 'investor_available' ELSE 'investor_held' END)
                                OR (line.direction = 'credit' AND CASE entry.kind
                                    WHEN 'secondary_purchase' THEN account.wallet_id IS NULL AND account.kind = 'secondary_settlement'
                                    ELSE account.wallet_id = entry.wallet_id
                                        AND account.kind = CASE entry.kind WHEN 'secondary_hold' THEN 'investor_held' ELSE 'investor_available' END END));
                        IF line_count <> 2 OR matched <> 2 THEN
                            RAISE EXCEPTION 'Resale posting % must move exactly its reservation''s price between its buckets', checked_entry USING ERRCODE = '23514';
                        END IF;
                        IF entry.kind <> 'secondary_hold' AND (
                                NOT EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'secondary_hold' AND source_type = 'secondary_reservation'
                                    AND source_id = reservation.id)
                                OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind IN ('secondary_release', 'secondary_purchase')
                                    AND source_type = 'secondary_reservation' AND source_id = reservation.id AND id <> entry.id)) THEN
                            RAISE EXCEPTION 'Resale posting % must end its held reservation once: released or bought, never both', checked_entry USING ERRCODE = '23514';
                        END IF;
                    END IF;
                    IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind = entry.kind AND source_type = 'secondary_reservation'
                            AND source_id = reservation.id AND id <> entry.id) THEN
                        RAISE EXCEPTION 'Resale posting % repeats its kind for one reservation', checked_entry USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_secondary_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM secondary_entry_check(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION assert_secondary_line_entry_bound() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM secondary_entry_check(NEW.entry_id);
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_secondary_reservation_postings() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    current_row secondary_reservations%ROWTYPE;
                BEGIN
                    SELECT * INTO current_row FROM secondary_reservations WHERE id = NEW.id;
                    IF (SELECT count(*) FROM ledger_entries WHERE kind = 'secondary_hold' AND source_type = 'secondary_reservation'
                            AND source_id = NEW.id) <> 1
                        OR (current_row.ended = 'released') <> EXISTS (SELECT 1 FROM ledger_entries WHERE kind = 'secondary_release'
                            AND source_type = 'secondary_reservation' AND source_id = NEW.id)
                        OR (current_row.ended = 'settled') <> EXISTS (SELECT 1 FROM secondary_transfers WHERE reservation_id = NEW.id) THEN
                        RAISE EXCEPTION 'A reservation holds its price once and is released or settled with its own postings' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE OR REPLACE FUNCTION require_secondary_transfer_postings() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF (SELECT count(*) FROM ledger_entries WHERE kind IN ('secondary_purchase', 'secondary_sale') AND source_type = 'secondary_reservation'
                            AND source_id = NEW.reservation_id) <> 2
                        OR NOT EXISTS (SELECT 1 FROM secondary_reservations WHERE id = NEW.reservation_id AND ended = 'settled'
                            AND units = NEW.units AND price = NEW.price AND buyer_party_id = NEW.buyer_party_id AND listing_id = NEW.listing_id) THEN
                        RAISE EXCEPTION 'A transfer settles its reservation with its purchase and sale postings' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER ledger_entries_secondary_bound AFTER INSERT ON ledger_entries
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW
                    WHEN (NEW.kind IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale'))
                    EXECUTE FUNCTION assert_secondary_entry_bound();
                CREATE CONSTRAINT TRIGGER ledger_lines_entry_secondary_bound AFTER INSERT ON ledger_lines
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_secondary_line_entry_bound();
                CREATE CONSTRAINT TRIGGER secondary_reservations_posted AFTER INSERT OR UPDATE ON secondary_reservations
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_secondary_reservation_postings();
                CREATE CONSTRAINT TRIGGER secondary_transfers_posted AFTER INSERT ON secondary_transfers
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_secondary_transfer_postings();
                SQL);

            if (! Schema::hasTable('permissions')) {
                return;
            }
            foreach (['secondary.halt', 'secondary.listings.cancel'] as $name) {
                $permission = DB::table('permissions')->insertGetId(['name' => $name, 'guard_name' => 'staff', 'created_at' => now(), 'updated_at' => now()]);
                foreach (DB::table('roles')->where('guard_name', 'staff')->where('name', 'compliance')->pluck('id') as $role) {
                    DB::table('role_has_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE secondary_listings, secondary_reservations, secondary_transfers, secondary_halts, secondary_halt_lifts,
                    ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM secondary_listings) OR EXISTS (SELECT 1 FROM secondary_halts)
                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE kind IN ('secondary_hold', 'secondary_release', 'secondary_purchase', 'secondary_sale'))
                        OR EXISTS (SELECT 1 FROM ledger_accounts WHERE kind IN ('secondary_settlement', 'secondary_fee_revenue')) THEN
                        RAISE EXCEPTION 'Recorded resale activity requires a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END; $$;

                DROP TRIGGER ledger_lines_entry_secondary_bound ON ledger_lines;
                DROP TRIGGER ledger_entries_secondary_bound ON ledger_entries;
                DROP TABLE secondary_halt_lifts;
                DROP TABLE secondary_halts;
                DROP TABLE secondary_transfers;
                DROP TABLE secondary_reservations;
                DROP TABLE secondary_listings;
                DROP FUNCTION require_secondary_transfer_postings();
                DROP FUNCTION require_secondary_reservation_postings();
                DROP FUNCTION assert_secondary_line_entry_bound();
                DROP FUNCTION assert_secondary_entry_bound();
                DROP FUNCTION secondary_entry_check(varchar);
                DROP FUNCTION protect_secondary_reservation();
                DROP FUNCTION protect_secondary_listing();
                DROP FUNCTION reject_secondary_record_mutation();

                ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entry_source;
                ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_source CHECK (
                    (kind = 'deposit_credit' AND source_type = 'wallet_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('primary_hold', 'primary_commit', 'primary_release', 'primary_refund')
                        AND source_type IN ('primary_reservation', 'primary_commitment') AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'primary_issue' AND source_type = 'primary_reservation' AND origin_operation_id IS NOT NULL
                        AND COALESCE(cause_type = 'disbursement_closing' AND cause_id ~ '^[0-9a-hjkmnp-tv-z]{26}$', false))
                    OR (kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'business_repayment_debit' AND source_type = 'business_repayment' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('withdrawal_hold', 'withdrawal_payout', 'withdrawal_return') AND source_type = 'wallet_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind IN ('business_withdrawal_hold', 'business_withdrawal_payout', 'business_withdrawal_return') AND source_type = 'business_withdrawal'
                        AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)
                    OR (kind = 'disbursement_platform_fee' AND source_type = 'disbursement_closing' AND origin_operation_id IS NOT NULL
                        AND cause_type IS NULL AND cause_id IS NULL));

                ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner;
                ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_owner CHECK (
                    (kind IN ('investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('business_available', 'business_withdrawing') AND wallet_id IS NOT NULL)
                    OR (kind IN ('deposit_clearing', 'deposit_fee_revenue', 'disbursement_settlement', 'repayment_clearing', 'withdrawal_clearing',
                        'platform_fee_revenue', 'audit_partner_fee_payable') AND wallet_id IS NULL));

                CREATE OR REPLACE FUNCTION ledger_entry_wallet_owner(kind text) RETURNS varchar LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
                    SELECT CASE kind
                        WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
                        WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor'
                        WHEN 'withdrawal_hold' THEN 'investor' WHEN 'withdrawal_payout' THEN 'investor' WHEN 'withdrawal_return' THEN 'investor'
                        WHEN 'business_deposit_credit' THEN 'business' WHEN 'business_repayment_debit' THEN 'business'
                        WHEN 'business_withdrawal_hold' THEN 'business' WHEN 'business_withdrawal_payout' THEN 'business'
                        WHEN 'business_withdrawal_return' THEN 'business' WHEN 'disbursement_platform_fee' THEN 'business'
                    END
                $$;
                SQL);
            if (! Schema::hasTable('permissions')) {
                return;
            }
            $permissions = DB::table('permissions')->whereIn('name', ['secondary.halt', 'secondary.listings.cancel'])->where('guard_name', 'staff')->pluck('id');
            DB::table('role_has_permissions')->whereIn('permission_id', $permissions)->delete();
            DB::table('permissions')->whereIn('id', $permissions)->delete();
        });
    }
};
