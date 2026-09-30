<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Holdings-side completeness guards (#96 5909932451 answer 2). Still UNACTIVATED: nothing in
 * the application inserts Holdings. Three rules, all separate from S3-C's funded-refund gate
 * (`primary_funding_refund_gate`), which this migration neither reads nor replaces.
 *
 * 1. A Holding needs its cash. When the transaction that inserted a Holding commits, exactly the
 *    `primary_issue` posting 100200 defines must exist for it: source `primary_reservation` naming
 *    the Holding's own reservation, cause `disbursement_closing` naming the Holding's own closing,
 *    on the wallet of the Holding's Party, under the reservation's originating operation, moving
 *    the Holding's principal from that wallet's committed bucket to `disbursement_settlement`.
 *    The check is DEFERRED to the outer commit, so the adapter can insert every Holding first and
 *    take the Party-sorted wallet locks afterwards; either insertion order passes.
 * 2. An issued closing is whole. At the same commit the Holdings of the closing must add up to
 *    its disbursement's amount, which 084737 already ties to the funding record's principal, so
 *    every funded commitment is issued or none is.
 * 3. Issued ownership is not refunded. A `primary_refund` posting for a reservation that has a
 *    Holding is refused at insert. 084737 refuses the opposite order (a Holding after a refund).
 *
 * 100200 and #175 already make a `primary_issue` follow its commit on the same wallet and
 * originating operation for exactly the committed amount, and a reservation's postings belong to
 * its Party. Rule 1 repeats those comparisons so it reads as one complete statement; on their own
 * they cannot fail while those guards stand. What rule 1 adds is that the posting exists at all and
 * names this Holding's closing.
 *
 * Not serialized here: a refund and a Holding inserted by two concurrent transactions that both
 * skip the wallet lock. The Holding's issue posting and a refund are mutually exclusive in the
 * ledger (100200) under the wallet row lock every `WalletPostings` call takes. Taking a Primary
 * row lock from the ledger trigger would invert Primary → wallet, so it is not done.
 *
 * Locks: the deferred check and the refund guard read without row locks. Install takes one
 * up-front SHARE ROW EXCLUSIVE lock on `primary_holdings` then `ledger_entries`, the order the
 * issuing transaction writes them, and nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_holdings, ledger_entries IN SHARE ROW EXCLUSIVE MODE;
                CREATE INDEX primary_holding_reservation_lookup ON primary_holdings (primary_reservation_id);

                CREATE OR REPLACE FUNCTION check_primary_holding_issue(holding_id varchar) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE
                    holding primary_holdings%ROWTYPE;
                    reservation primary_reservations%ROWTYPE;
                    closing_amount numeric;
                BEGIN
                    SELECT * INTO holding FROM primary_holdings WHERE id = holding_id;
                    SELECT * INTO reservation FROM primary_reservations WHERE id = holding.primary_reservation_id;
                    IF NOT EXISTS (SELECT 1 FROM ledger_entries entry JOIN investor_wallets wallet ON wallet.id = entry.wallet_id
                        WHERE entry.kind = 'primary_issue' AND entry.source_type = 'primary_reservation' AND entry.source_id = holding.primary_reservation_id
                            AND entry.cause_type = 'disbursement_closing' AND entry.cause_id = holding.disbursement_closing_id
                            AND wallet.party_id = holding.party_id AND entry.origin_operation_id = reservation.origin_operation_id
                            AND entry.currency = 'RWF'
                            AND EXISTS (SELECT 1 FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND line.direction = 'debit' AND account.kind = 'investor_committed'
                                    AND account.wallet_id = entry.wallet_id AND line.amount = holding.principal)
                            AND EXISTS (SELECT 1 FROM ledger_lines line JOIN ledger_accounts account ON account.id = line.account_id
                                WHERE line.entry_id = entry.id AND line.direction = 'credit' AND account.kind = 'disbursement_settlement'
                                    AND account.wallet_id IS NULL AND line.amount = holding.principal)) THEN
                        RAISE EXCEPTION 'A Holding requires its reservation''s exact primary issue posting for its own closing' USING ERRCODE = '23514';
                    END IF;
                    SELECT parent.amount INTO closing_amount FROM disbursement_closings closing
                        JOIN disbursements parent ON parent.id = closing.disbursement_id WHERE closing.id = holding.disbursement_closing_id;
                    IF (SELECT sum(principal) FROM primary_holdings WHERE disbursement_closing_id = holding.disbursement_closing_id) <> closing_amount THEN
                        RAISE EXCEPTION 'An issued closing must issue a Holding for every funded commitment' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;

                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM primary_holdings LOOP
                        PERFORM check_primary_holding_issue(retained_id);
                    END LOOP;
                END;
                $$;

                CREATE OR REPLACE FUNCTION require_primary_holding_issue() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM check_primary_holding_issue(NEW.id);
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER primary_holding_issue_bound AFTER INSERT ON primary_holdings
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_holding_issue();

                CREATE OR REPLACE FUNCTION refuse_refund_after_primary_holding() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_holdings WHERE primary_reservation_id = NEW.source_id) THEN
                        RAISE EXCEPTION 'Principal issued as a Holding cannot be refunded' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_holding_refund_refused BEFORE INSERT ON ledger_entries
                    FOR EACH ROW WHEN (NEW.kind = 'primary_refund' AND NEW.source_type = 'primary_reservation')
                    EXECUTE FUNCTION refuse_refund_after_primary_holding();
                SQL);
        });
    }

    /** Issued Holdings require a forward migration; otherwise 084737's table and the ledger's triggers are restored exactly. */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_holdings, ledger_entries IN SHARE ROW EXCLUSIVE MODE;
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM primary_holdings) THEN
                        RAISE EXCEPTION 'Issued Holdings require a forward migration; rollback is refused' USING ERRCODE = '23514';
                    END IF;
                END;
                $$;
                DROP TRIGGER primary_holding_refund_refused ON ledger_entries;
                DROP TRIGGER primary_holding_issue_bound ON primary_holdings;
                DROP FUNCTION refuse_refund_after_primary_holding(), require_primary_holding_issue(), check_primary_holding_issue(varchar);
                DROP INDEX primary_holding_reservation_lookup;
                SQL);
        });
    }
};
