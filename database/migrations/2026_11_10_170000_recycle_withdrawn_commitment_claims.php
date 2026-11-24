<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BRS v3.0 INV-R03 / LED-R02: an Investor may withdraw a commitment, for a full refund, until the
 * raise reaches 100%. The withdrawn units must then be free for other Investors, or the raise could
 * never fill. A withdrawn commitment therefore retires its claim exactly like a released or expired
 * hold, with one more piece of retained evidence: the commit entry between its hold and its refund.
 *
 * `check_primary_held_release` keeps its held branch unchanged (including the ledger-precision time
 * comparison) and gains a withdrawn branch: the latest version is the confirmed one its commitment
 * binds, and the reservation's cash is exactly hold → commit → refund of the full principal, the
 * refund moving it from Committed back to Available. Funded campaigns still refuse any retirement.
 *
 * Forward-only once a withdrawn claim is retired: the old check would reject that evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            LOCK TABLE primary_held_claim_releases IN ACCESS EXCLUSIVE MODE;
            ALTER TABLE primary_held_claim_releases
                ADD COLUMN commit_entry_id char(26) NULL REFERENCES ledger_entries(id) ON DELETE RESTRICT,
                ADD COLUMN commit_sha256 char(64) NULL,
                ADD CONSTRAINT primary_held_release_commit_pair CHECK ((commit_entry_id IS NULL) = (commit_sha256 IS NULL));
            CREATE OR REPLACE FUNCTION check_primary_held_release(target char(26)) RETURNS void LANGUAGE plpgsql AS $$
            DECLARE r primary_reservations%ROWTYPE; d primary_held_claim_releases%ROWTYPE; v primary_reservation_versions%ROWTYPE;
            DECLARE h ledger_entries%ROWTYPE; e ledger_entries%ROWTYPE; c ledger_entries%ROWTYPE; withdrawn boolean;
            BEGIN
             SELECT * INTO d FROM primary_held_claim_releases WHERE primary_reservation_id = target;
             IF NOT FOUND THEN RAISE EXCEPTION 'Held claim retirement requires retained evidence' USING ERRCODE='23514'; END IF;
             withdrawn := d.commit_entry_id IS NOT NULL;
             SELECT * INTO r FROM primary_reservations WHERE id = target;
             SELECT * INTO v FROM primary_reservation_versions WHERE primary_reservation_id = target ORDER BY revision DESC LIMIT 1;
             SELECT * INTO h FROM ledger_entries WHERE id = d.hold_entry_id;
             SELECT * INTO e FROM ledger_entries WHERE id = d.return_entry_id;
             SELECT * INTO c FROM ledger_entries WHERE id = d.commit_entry_id;
             IF r.sha256 IS DISTINCT FROM d.root_sha256 OR v.id IS DISTINCT FROM d.version_id OR v.sha256 IS DISTINCT FROM d.version_sha256
              OR (NOT withdrawn AND (v.state NOT IN ('released','expired') OR v.state IS NULL
                OR EXISTS (SELECT 1 FROM primary_commitments WHERE primary_reservation_id = target)
                OR e.kind IS DISTINCT FROM 'primary_release'
                OR (SELECT count(*) FROM ledger_entries WHERE source_type='primary_reservation' AND source_id=target)<>2
                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id=l.account_id
                  WHERE l.entry_id=e.id AND l.direction='debit' AND l.amount=r.principal AND a.kind='investor_held' AND a.wallet_id=d.wallet_id AND a.currency='RWF')))
              OR (withdrawn AND (v.state IS DISTINCT FROM 'confirmed'
                OR NOT EXISTS (SELECT 1 FROM primary_commitments WHERE primary_reservation_id = target AND primary_reservation_version_id = v.id)
                OR e.kind IS DISTINCT FROM 'primary_refund' OR c.kind IS DISTINCT FROM 'primary_commit'
                OR c.sha256 IS DISTINCT FROM d.commit_sha256 OR c.wallet_id IS DISTINCT FROM d.wallet_id
                OR c.source_type IS DISTINCT FROM 'primary_reservation' OR c.source_id IS DISTINCT FROM target
                OR c.origin_operation_id IS DISTINCT FROM r.origin_operation_id OR c.currency IS DISTINCT FROM 'RWF'
                OR c.cause_type IS NOT NULL OR c.cause_id IS NOT NULL OR c.created_at < h.created_at OR e.created_at < c.created_at
                OR (SELECT count(*) FROM ledger_entries WHERE source_type='primary_reservation' AND source_id=target)<>3
                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id=l.account_id
                  WHERE l.entry_id=e.id AND l.direction='debit' AND l.amount=r.principal AND a.kind='investor_committed' AND a.wallet_id=d.wallet_id AND a.currency='RWF')))
              OR EXISTS (SELECT 1 FROM primary_reservation_versions x WHERE x.primary_reservation_id=target AND x.id<>v.id AND x.state<>'held')
              OR EXISTS (SELECT 1 FROM (SELECT revision, previous_sha256, created_at, lag(sha256) OVER (ORDER BY revision) AS prior_sha,
                lag(created_at) OVER (ORDER BY revision) AS prior_at, row_number() OVER (ORDER BY revision) AS expected_revision
                FROM primary_reservation_versions WHERE primary_reservation_id=target) x
                WHERE revision<>expected_revision OR previous_sha256 IS DISTINCT FROM prior_sha OR created_at<prior_at)
              OR h.sha256 IS DISTINCT FROM d.hold_sha256 OR e.sha256 IS DISTINCT FROM d.return_sha256
              OR h.kind IS DISTINCT FROM 'primary_hold'
              OR h.wallet_id IS DISTINCT FROM d.wallet_id OR e.wallet_id IS DISTINCT FROM d.wallet_id
              OR h.source_type IS DISTINCT FROM 'primary_reservation' OR e.source_type IS DISTINCT FROM 'primary_reservation'
              OR h.source_id IS DISTINCT FROM target OR e.source_id IS DISTINCT FROM target
              OR h.origin_operation_id IS DISTINCT FROM r.origin_operation_id OR e.origin_operation_id IS DISTINCT FROM r.origin_operation_id
              OR h.currency IS DISTINCT FROM 'RWF' OR e.currency IS DISTINCT FROM 'RWF'
              OR h.cause_type IS NOT NULL OR h.cause_id IS NOT NULL OR e.cause_type IS NOT NULL OR e.cause_id IS NOT NULL
              OR h.created_at < date_trunc('second', r.created_at) OR e.created_at < date_trunc('second', v.created_at)
              OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id=d.wallet_id AND party_id=r.party_id AND currency='RWF')
              OR (SELECT count(*) FROM ledger_lines WHERE entry_id=e.id)<>2
              OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id=l.account_id
                WHERE l.entry_id=e.id AND l.direction='credit' AND l.amount=r.principal AND a.kind='investor_available' AND a.wallet_id=d.wallet_id AND a.currency='RWF') THEN
              RAISE EXCEPTION 'Held claim retirement requires full original terminal held cash return' USING ERRCODE='23514';
             END IF;
             PERFORM check_primary_hold_binding(target::varchar);
             PERFORM check_primary_terminal_cash(target::varchar);
            END; $$;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            LOCK TABLE primary_held_claim_releases IN ACCESS EXCLUSIVE MODE;
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM primary_held_claim_releases WHERE commit_entry_id IS NOT NULL) THEN
                    RAISE EXCEPTION 'Retained withdrawn claim releases require a forward migration' USING ERRCODE = '23514';
                END IF;
            END; $$;
            CREATE OR REPLACE FUNCTION check_primary_held_release(target char(26)) RETURNS void LANGUAGE plpgsql AS $$
            DECLARE r primary_reservations%ROWTYPE; d primary_held_claim_releases%ROWTYPE; v primary_reservation_versions%ROWTYPE;
            DECLARE h ledger_entries%ROWTYPE; e ledger_entries%ROWTYPE;
            BEGIN
             SELECT * INTO d FROM primary_held_claim_releases WHERE primary_reservation_id = target;
             IF NOT FOUND THEN RAISE EXCEPTION 'Held claim retirement requires retained evidence' USING ERRCODE='23514'; END IF;
             SELECT * INTO r FROM primary_reservations WHERE id = target;
             SELECT * INTO v FROM primary_reservation_versions WHERE primary_reservation_id = target ORDER BY revision DESC LIMIT 1;
             SELECT * INTO h FROM ledger_entries WHERE id = d.hold_entry_id;
             SELECT * INTO e FROM ledger_entries WHERE id = d.return_entry_id;
             IF r.sha256 IS DISTINCT FROM d.root_sha256 OR v.id IS DISTINCT FROM d.version_id OR v.sha256 IS DISTINCT FROM d.version_sha256
              OR v.state NOT IN ('released','expired') OR v.state IS NULL
              OR EXISTS (SELECT 1 FROM primary_commitments WHERE primary_reservation_id = target)
              OR EXISTS (SELECT 1 FROM primary_reservation_versions x WHERE x.primary_reservation_id=target AND x.id<>v.id AND x.state<>'held')
              OR EXISTS (SELECT 1 FROM (SELECT revision, previous_sha256, created_at, lag(sha256) OVER (ORDER BY revision) AS prior_sha,
                lag(created_at) OVER (ORDER BY revision) AS prior_at, row_number() OVER (ORDER BY revision) AS expected_revision
                FROM primary_reservation_versions WHERE primary_reservation_id=target) x
                WHERE revision<>expected_revision OR previous_sha256 IS DISTINCT FROM prior_sha OR created_at<prior_at)
              OR h.sha256 IS DISTINCT FROM d.hold_sha256 OR e.sha256 IS DISTINCT FROM d.return_sha256
              OR h.kind IS DISTINCT FROM 'primary_hold' OR e.kind IS DISTINCT FROM 'primary_release'
              OR h.wallet_id IS DISTINCT FROM d.wallet_id OR e.wallet_id IS DISTINCT FROM d.wallet_id
              OR h.source_type IS DISTINCT FROM 'primary_reservation' OR e.source_type IS DISTINCT FROM 'primary_reservation'
              OR h.source_id IS DISTINCT FROM target OR e.source_id IS DISTINCT FROM target
              OR h.origin_operation_id IS DISTINCT FROM r.origin_operation_id OR e.origin_operation_id IS DISTINCT FROM r.origin_operation_id
              OR h.currency IS DISTINCT FROM 'RWF' OR e.currency IS DISTINCT FROM 'RWF'
              OR h.cause_type IS NOT NULL OR h.cause_id IS NOT NULL OR e.cause_type IS NOT NULL OR e.cause_id IS NOT NULL
              OR h.created_at < date_trunc('second', r.created_at) OR e.created_at < date_trunc('second', v.created_at)
              OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id=d.wallet_id AND party_id=r.party_id AND currency='RWF')
              OR (SELECT count(*) FROM ledger_entries WHERE source_type='primary_reservation' AND source_id=target)<>2
              OR (SELECT count(*) FROM ledger_lines WHERE entry_id=e.id)<>2
              OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id=l.account_id
                WHERE l.entry_id=e.id AND l.direction='debit' AND l.amount=r.principal AND a.kind='investor_held' AND a.wallet_id=d.wallet_id AND a.currency='RWF')
              OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id=l.account_id
                WHERE l.entry_id=e.id AND l.direction='credit' AND l.amount=r.principal AND a.kind='investor_available' AND a.wallet_id=d.wallet_id AND a.currency='RWF') THEN
              RAISE EXCEPTION 'Held claim retirement requires full original terminal held cash return' USING ERRCODE='23514';
             END IF;
             PERFORM check_primary_hold_binding(target::varchar);
             PERFORM check_primary_terminal_cash(target::varchar);
            END; $$;
            ALTER TABLE primary_held_claim_releases DROP CONSTRAINT primary_held_release_commit_pair,
                DROP COLUMN commit_sha256, DROP COLUMN commit_entry_id;
            SQL);
    }
};
