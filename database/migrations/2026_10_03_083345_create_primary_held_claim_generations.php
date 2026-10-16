<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Infrastructure\Primary\RetainedHeldClaimRelease;
use App\Infrastructure\Primary\RetainedPrimaryReservation;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Forward-only after retirement evidence; historical claims and cash are never rewritten. */
return new class extends Migration
{
    public function up(): void
    {
        $this->withBusinessGate(function (): void {
            DB::statement('LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments, primary_ordinal_claims, primary_campaign_fundings, primary_funding_commitments, investor_wallets, ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE');
            $inputs = [];
            foreach (PrimaryReservationRecord::query()->orderBy('id')->cursor() as $root) {
                $inputs[$root->business_campaign_id] ??= app(PrimaryCampaignSource::class)->lockRetained($root->business_campaign_id);
                [$reservation] = app(RetainedPrimaryReservation::class)->read($root, $inputs[$root->business_campaign_id]);
                if (in_array($reservation->state, ['released', 'expired'], true)) {
                    app(RetainedHeldClaimRelease::class)->binding($root, $inputs[$root->business_campaign_id]);
                }
                DB::select('SELECT check_primary_hold_binding(?), check_primary_terminal_cash(?)', [$root->id, $root->id]);
            }
            DB::unprepared(<<<'SQL'
                                DO $$ BEGIN
                                  IF EXISTS (SELECT 1 FROM primary_reservations r JOIN business_campaigns c ON c.id=r.business_campaign_id
                                     GROUP BY c.id,c.principal HAVING SUM(r.principal)>c.principal)
                                    OR EXISTS (SELECT 1 FROM primary_reservations a JOIN primary_reservations b
                                     ON a.business_campaign_id=b.business_campaign_id AND a.id<b.id AND a.ordinal_ranges && b.ordinal_ranges)
                                    OR EXISTS (SELECT 1 FROM primary_ordinal_claims c JOIN primary_reservations r ON r.id=c.primary_reservation_id
                     WHERE c.business_campaign_id<>r.business_campaign_id OR NOT r.ordinal_ranges @> c.ordinal::bigint)
                    OR EXISTS (SELECT 1 FROM primary_reservations r WHERE
                                     (SELECT count(*) FROM primary_ordinal_claims c WHERE c.primary_reservation_id=r.id AND c.business_campaign_id=r.business_campaign_id AND r.ordinal_ranges @> c.ordinal::bigint)<>r.units)
                                    THEN RAISE EXCEPTION 'Existing held claim evidence requires verified immutable inventory' USING ERRCODE='23514'; END IF;
                                END; $$;
                CREATE TABLE primary_held_claim_releases (
                 primary_reservation_id char(26) PRIMARY KEY REFERENCES primary_reservations(id) ON DELETE RESTRICT,
                 root_sha256 char(64) NOT NULL, version_id char(26) NOT NULL REFERENCES primary_reservation_versions(id) ON DELETE RESTRICT,
                 version_sha256 char(64) NOT NULL, hold_entry_id char(26) NOT NULL REFERENCES ledger_entries(id) ON DELETE RESTRICT,
                 hold_sha256 char(64) NOT NULL, return_entry_id char(26) NOT NULL REFERENCES ledger_entries(id) ON DELETE RESTRICT,
                 return_sha256 char(64) NOT NULL, wallet_id char(26) NOT NULL REFERENCES investor_wallets(id) ON DELETE RESTRICT,
                 sha256 char(64) NOT NULL CHECK (sha256 ~ '^[0-9a-f]{64}$'), created_at timestamptz NOT NULL
                );
                CREATE TABLE primary_held_claim_generations (
                 business_campaign_id char(26) NOT NULL REFERENCES business_campaigns(id) ON DELETE RESTRICT,
                 ordinal integer NOT NULL CHECK (ordinal BETWEEN 1 AND 20000), generation integer NOT NULL CHECK (generation > 0),
                 previous_reservation_id char(26) NOT NULL REFERENCES primary_held_claim_releases(primary_reservation_id) ON DELETE RESTRICT,
                 primary_reservation_id char(26) NOT NULL REFERENCES primary_reservations(id) ON DELETE RESTRICT,
                 PRIMARY KEY (business_campaign_id, ordinal, generation),
                 UNIQUE (business_campaign_id, ordinal, previous_reservation_id),
                 UNIQUE (business_campaign_id, ordinal, primary_reservation_id),
                 FOREIGN KEY (business_campaign_id, ordinal) REFERENCES primary_ordinal_claims(business_campaign_id, ordinal) ON DELETE RESTRICT,
                 CHECK (previous_reservation_id <> primary_reservation_id)
                );
                CREATE INDEX primary_generation_root ON primary_held_claim_generations(primary_reservation_id);
                CREATE VIEW primary_live_claim_roots AS
                 SELECT r.* FROM primary_reservations r WHERE NOT EXISTS (
                  SELECT 1 FROM primary_held_claim_releases d WHERE d.primary_reservation_id = r.id);
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
                  OR h.created_at < r.created_at OR e.created_at < v.created_at
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
                CREATE OR REPLACE FUNCTION validate_primary_held_release() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                 PERFORM 1 FROM business_profiles WHERE id=(SELECT c.business_id FROM business_campaigns c JOIN primary_reservations r ON r.business_campaign_id=c.id WHERE r.id=NEW.primary_reservation_id) FOR UPDATE;
                 PERFORM 1 FROM business_campaigns WHERE id=(SELECT business_campaign_id FROM primary_reservations WHERE id=NEW.primary_reservation_id) FOR UPDATE;
                 PERFORM 1 FROM primary_reservations WHERE id=NEW.primary_reservation_id FOR UPDATE;
                 IF EXISTS (SELECT 1 FROM primary_campaign_fundings WHERE business_campaign_id=(SELECT business_campaign_id FROM primary_reservations WHERE id=NEW.primary_reservation_id)) THEN
                  RAISE EXCEPTION 'Funded claim retirement is prohibited' USING ERRCODE='23514'; END IF;
                 PERFORM check_primary_held_release(NEW.primary_reservation_id); RETURN NULL; END; $$;
                CREATE TRIGGER primary_held_release_valid AFTER INSERT ON primary_held_claim_releases FOR EACH ROW EXECUTE FUNCTION validate_primary_held_release();
                CREATE TRIGGER primary_held_release_immutable BEFORE UPDATE OR DELETE ON primary_held_claim_releases FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                CREATE TRIGGER primary_held_generation_immutable BEFORE UPDATE OR DELETE ON primary_held_claim_generations FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                CREATE OR REPLACE FUNCTION validate_primary_held_generation() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE prior_id char(26); prior_generation integer;
                BEGIN
                 SELECT primary_reservation_id, generation INTO prior_id, prior_generation FROM primary_held_claim_generations
                  WHERE business_campaign_id=NEW.business_campaign_id AND ordinal=NEW.ordinal ORDER BY generation DESC LIMIT 1;
                 IF NOT FOUND THEN
                  SELECT primary_reservation_id, 0 INTO prior_id, prior_generation FROM primary_ordinal_claims
                   WHERE business_campaign_id=NEW.business_campaign_id AND ordinal=NEW.ordinal;
                 END IF;
                 IF NEW.generation IS DISTINCT FROM prior_generation+1 OR NEW.previous_reservation_id IS DISTINCT FROM prior_id
                  OR EXISTS (SELECT 1 FROM primary_ordinal_claims WHERE business_campaign_id=NEW.business_campaign_id AND ordinal=NEW.ordinal AND primary_reservation_id=NEW.primary_reservation_id)
                  OR EXISTS (SELECT 1 FROM primary_held_claim_releases WHERE primary_reservation_id=NEW.primary_reservation_id)
                  OR (SELECT created_at FROM primary_reservations WHERE id=NEW.primary_reservation_id) <
                     (SELECT v.created_at FROM primary_held_claim_releases d JOIN primary_reservation_versions v ON v.id=d.version_id WHERE d.primary_reservation_id=prior_id)
                  OR NOT EXISTS (SELECT 1 FROM primary_reservations WHERE id=NEW.primary_reservation_id
                   AND business_campaign_id=NEW.business_campaign_id AND ordinal_ranges @> NEW.ordinal::bigint)
                  OR NOT EXISTS (SELECT 1 FROM primary_reservations WHERE id=prior_id
                   AND business_campaign_id=NEW.business_campaign_id AND ordinal_ranges @> NEW.ordinal::bigint) THEN
                  RAISE EXCEPTION 'Held ordinal generation must extend its exact previous root' USING ERRCODE='23514';
                 END IF;
                 PERFORM check_primary_held_release(prior_id);
                 RETURN NEW;
                END; $$;
                CREATE TRIGGER primary_held_generation_valid BEFORE INSERT ON primary_held_claim_generations FOR EACH ROW EXECUTE FUNCTION validate_primary_held_generation();
                CREATE OR REPLACE FUNCTION check_primary_claim_generations(target char(26)) RETURNS void LANGUAGE plpgsql AS $$
                DECLARE retired_id char(26);
                BEGIN
                 FOR retired_id IN SELECT d.primary_reservation_id FROM primary_held_claim_releases d
                  JOIN primary_reservations r ON r.id=d.primary_reservation_id WHERE r.business_campaign_id=target LOOP
                  PERFORM check_primary_held_release(retired_id);
                 END LOOP;
                 IF EXISTS (SELECT 1 FROM primary_ordinal_claims c JOIN primary_reservations r ON r.id=c.primary_reservation_id
                  WHERE c.business_campaign_id=target AND (r.business_campaign_id<>target OR NOT r.ordinal_ranges @> c.ordinal::bigint))
                 OR EXISTS (SELECT 1 FROM primary_held_claim_generations g JOIN primary_ordinal_claims first_claim
                   ON first_claim.business_campaign_id=g.business_campaign_id AND first_claim.ordinal=g.ordinal
                   LEFT JOIN primary_held_claim_generations p ON p.business_campaign_id=g.business_campaign_id AND p.ordinal=g.ordinal AND p.generation=g.generation-1
                   JOIN primary_reservations r ON r.id=g.primary_reservation_id
                   JOIN primary_reservations previous_root ON previous_root.id=g.previous_reservation_id
                   WHERE g.business_campaign_id=target AND (g.primary_reservation_id=first_claim.primary_reservation_id OR g.previous_reservation_id IS DISTINCT FROM
                    CASE WHEN g.generation=1 THEN first_claim.primary_reservation_id ELSE p.primary_reservation_id END
                    OR r.business_campaign_id<>target OR previous_root.business_campaign_id<>target
                    OR NOT r.ordinal_ranges @> g.ordinal::bigint OR NOT previous_root.ordinal_ranges @> g.ordinal::bigint))
                  OR EXISTS (SELECT 1 FROM primary_reservations r CROSS JOIN LATERAL unnest(r.ordinal_ranges) ranges
                    CROSS JOIN LATERAL generate_series(lower(ranges),upper(ranges)-1) n
                    WHERE r.business_campaign_id=target AND NOT EXISTS (SELECT 1 FROM primary_ordinal_claims c
                      WHERE c.business_campaign_id=target AND c.ordinal=n AND (c.primary_reservation_id=r.id OR EXISTS (
                       SELECT 1 FROM primary_held_claim_generations g WHERE g.business_campaign_id=target AND g.ordinal=n AND g.primary_reservation_id=r.id))))
                  OR EXISTS (SELECT 1 FROM primary_live_claim_roots a JOIN primary_live_claim_roots b
                    ON a.business_campaign_id=b.business_campaign_id AND a.id<b.id AND a.ordinal_ranges && b.ordinal_ranges WHERE a.business_campaign_id=target) THEN
                  RAISE EXCEPTION 'Held ordinal generations require complete immutable ancestry and disjoint live roots' USING ERRCODE='23514';
                 END IF;
                END; $$;
                CREATE OR REPLACE FUNCTION retain_primary_ordinal_claims() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE n integer; prior_id char(26); prior_generation integer;
                BEGIN
                 FOR n IN SELECT generate_series(lower(r),upper(r)-1)::integer FROM unnest(NEW.ordinal_ranges) r ORDER BY 1 LOOP
                  INSERT INTO primary_ordinal_claims(business_campaign_id,ordinal,primary_reservation_id)
                   VALUES (NEW.business_campaign_id,n,NEW.id) ON CONFLICT DO NOTHING;
                  SELECT primary_reservation_id,generation INTO prior_id,prior_generation FROM primary_held_claim_generations
                   WHERE business_campaign_id=NEW.business_campaign_id AND ordinal=n ORDER BY generation DESC LIMIT 1;
                  IF NOT FOUND THEN SELECT primary_reservation_id,0 INTO prior_id,prior_generation FROM primary_ordinal_claims
                   WHERE business_campaign_id=NEW.business_campaign_id AND ordinal=n; END IF;
                  IF prior_id<>NEW.id THEN INSERT INTO primary_held_claim_generations(business_campaign_id,ordinal,generation,previous_reservation_id,primary_reservation_id)
                   VALUES (NEW.business_campaign_id,n,prior_generation+1,prior_id,NEW.id); END IF;
                 END LOOP;
                 RETURN NULL;
                END; $$;
                CREATE OR REPLACE FUNCTION bound_primary_campaign_inventory() RETURNS trigger LANGUAGE plpgsql AS $$
                            DECLARE campaign business_campaigns%ROWTYPE;
                            DECLARE allocated numeric;
                            BEGIN
                                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                                SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                                SELECT COALESCE(SUM(principal), 0) INTO allocated FROM primary_live_claim_roots WHERE business_campaign_id = NEW.business_campaign_id;
                                IF allocated + NEW.principal > campaign.principal THEN
                                    RAISE EXCEPTION 'Primary allocations exceed the published campaign capacity' USING ERRCODE = '23514';
                                END IF;
                                RETURN NEW;
                            END;
                            $$;
                CREATE OR REPLACE FUNCTION exclude_primary_ordinal_overlap() RETURNS trigger LANGUAGE plpgsql AS $$
                                DECLARE campaign business_campaigns%ROWTYPE;
                                BEGIN
                                    PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                                    SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                                    IF NEW.ordinal_ranges IS NULL OR NOT NEW.ordinal_ranges <@ int8multirange(int8range(1, (campaign.principal / 5000)::bigint + 1)) THEN
                                        RAISE EXCEPTION 'Primary ordinal claims must fit the published campaign' USING ERRCODE = '23514';
                                    END IF;
                                    IF EXISTS (SELECT 1 FROM primary_live_claim_roots WHERE business_campaign_id = NEW.business_campaign_id AND ordinal_ranges && NEW.ordinal_ranges) THEN
                                        RAISE EXCEPTION 'Primary ordinal claims cannot overlap retained allocations' USING ERRCODE = '23514';
                                    END IF;
                                    RETURN NEW;
                                END;
                                $$;
                CREATE OR REPLACE FUNCTION require_complete_primary_funding() RETURNS trigger LANGUAGE plpgsql AS $$
                                DECLARE funding primary_campaign_fundings%ROWTYPE;
                                DECLARE target_id char(26);
                                BEGIN
                                    IF TG_TABLE_NAME = 'primary_campaign_fundings' THEN target_id := NEW.id;
                                    ELSE target_id := NEW.funding_id; END IF;
                                    SELECT * INTO funding FROM primary_campaign_fundings WHERE id = target_id;
                                    PERFORM check_primary_claim_generations(funding.business_campaign_id);
                                    IF NOT EXISTS (SELECT 1 FROM primary_funding_commitments WHERE funding_id = funding.id)
                                        OR (SELECT COALESCE(SUM(r.principal), 0) FROM primary_funding_commitments m
                                            JOIN primary_reservations r ON r.id = m.reservation_id WHERE m.funding_id = funding.id) <> funding.principal
                                        OR EXISTS (SELECT 1 FROM primary_live_claim_roots r WHERE r.business_campaign_id = funding.business_campaign_id
                                            AND NOT EXISTS (SELECT 1 FROM primary_funding_commitments m WHERE m.funding_id = funding.id AND m.reservation_id = r.id))
                                        OR EXISTS (SELECT 1 FROM primary_funding_commitments m JOIN primary_commitments c ON c.id = m.commitment_id
                                            JOIN primary_reservations r ON r.id = m.reservation_id
                                            WHERE m.funding_id = funding.id AND ((SELECT state FROM primary_reservation_versions
                                                WHERE primary_reservation_id = r.id ORDER BY revision DESC LIMIT 1) IS DISTINCT FROM 'confirmed'
                                                OR EXISTS (SELECT 1 FROM ledger_entries e WHERE e.source_type = 'primary_reservation'
                                                    AND e.source_id = r.id AND e.kind NOT IN ('primary_hold', 'primary_commit'))
                                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'credit' AND a.kind = 'investor_committed'
                                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal)
                                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'debit' AND a.kind = 'investor_held'
                                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal))) THEN
                                        RAISE EXCEPTION 'Funding must retain the complete confirmed principal and unreturned committed cash' USING ERRCODE = '23514';
                                    END IF;
                                    RETURN NEW;
                                END;
                                $$;
                SQL);
        });
    }

    /** Retain every FK while allowing a campaign writer queued behind the Business gate to finish. */
    private function withBusinessGate(Closure $migration): void
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                DB::transaction(function () use ($migration): void {
                    DB::statement('LOCK TABLE business_profiles IN ACCESS EXCLUSIVE MODE');
                    // FK installation needs this stronger mode. Never wait while holding Business.
                    DB::statement('LOCK TABLE business_campaigns IN SHARE ROW EXCLUSIVE MODE NOWAIT');
                    $migration();
                });

                return;
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '55P03' || $attempt >= 49) {
                    throw $exception;
                }
                // The transaction/savepoint released its gates before this bounded retry.
                usleep(10000);
            }
        }
    }

    public function down(): void
    {
        $this->withBusinessGate(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_held_claim_releases, primary_held_claim_generations IN ACCESS EXCLUSIVE MODE;
                                DO $$ BEGIN
                                    IF EXISTS (SELECT 1 FROM primary_held_claim_releases) OR EXISTS (SELECT 1 FROM primary_held_claim_generations) THEN
                                        RAISE EXCEPTION 'Retained held claim generations require a forward migration' USING ERRCODE='23514';
                                    END IF;
                                END; $$;
                CREATE OR REPLACE FUNCTION bound_primary_campaign_inventory() RETURNS trigger LANGUAGE plpgsql AS $$
                            DECLARE campaign business_campaigns%ROWTYPE;
                            DECLARE allocated numeric;
                            BEGIN
                                PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                                SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                                SELECT COALESCE(SUM(principal), 0) INTO allocated FROM primary_reservations WHERE business_campaign_id = NEW.business_campaign_id;
                                IF allocated + NEW.principal > campaign.principal THEN
                                    RAISE EXCEPTION 'Primary allocations exceed the published campaign capacity' USING ERRCODE = '23514';
                                END IF;
                                RETURN NEW;
                            END;
                            $$;
                CREATE OR REPLACE FUNCTION exclude_primary_ordinal_overlap() RETURNS trigger LANGUAGE plpgsql AS $$
                                DECLARE campaign business_campaigns%ROWTYPE;
                                BEGIN
                                    PERFORM 1 FROM business_profiles WHERE id = (SELECT business_id FROM business_campaigns WHERE id = NEW.business_campaign_id) FOR UPDATE;
                                    SELECT * INTO campaign FROM business_campaigns WHERE id = NEW.business_campaign_id FOR UPDATE;
                                    IF NEW.ordinal_ranges IS NULL OR NOT NEW.ordinal_ranges <@ int8multirange(int8range(1, (campaign.principal / 5000)::bigint + 1)) THEN
                                        RAISE EXCEPTION 'Primary ordinal claims must fit the published campaign' USING ERRCODE = '23514';
                                    END IF;
                                    IF EXISTS (SELECT 1 FROM primary_reservations WHERE business_campaign_id = NEW.business_campaign_id AND ordinal_ranges && NEW.ordinal_ranges) THEN
                                        RAISE EXCEPTION 'Primary ordinal claims cannot overlap retained allocations' USING ERRCODE = '23514';
                                    END IF;
                                    RETURN NEW;
                                END;
                                $$;
                CREATE OR REPLACE FUNCTION require_complete_primary_funding() RETURNS trigger LANGUAGE plpgsql AS $$
                                DECLARE funding primary_campaign_fundings%ROWTYPE;
                                DECLARE target_id char(26);
                                BEGIN
                                    IF TG_TABLE_NAME = 'primary_campaign_fundings' THEN target_id := NEW.id;
                                    ELSE target_id := NEW.funding_id; END IF;
                                    SELECT * INTO funding FROM primary_campaign_fundings WHERE id = target_id;
                                    IF NOT EXISTS (SELECT 1 FROM primary_funding_commitments WHERE funding_id = funding.id)
                                        OR (SELECT COALESCE(SUM(r.principal), 0) FROM primary_funding_commitments m
                                            JOIN primary_reservations r ON r.id = m.reservation_id WHERE m.funding_id = funding.id) <> funding.principal
                                        OR EXISTS (SELECT 1 FROM primary_reservations r WHERE r.business_campaign_id = funding.business_campaign_id
                                            AND NOT EXISTS (SELECT 1 FROM primary_funding_commitments m WHERE m.funding_id = funding.id AND m.reservation_id = r.id))
                                        OR EXISTS (SELECT 1 FROM primary_funding_commitments m JOIN primary_commitments c ON c.id = m.commitment_id
                                            JOIN primary_reservations r ON r.id = m.reservation_id
                                            WHERE m.funding_id = funding.id AND ((SELECT state FROM primary_reservation_versions
                                                WHERE primary_reservation_id = r.id ORDER BY revision DESC LIMIT 1) IS DISTINCT FROM 'confirmed'
                                                OR EXISTS (SELECT 1 FROM ledger_entries e WHERE e.source_type = 'primary_reservation'
                                                    AND e.source_id = r.id AND e.kind NOT IN ('primary_hold', 'primary_commit'))
                                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'credit' AND a.kind = 'investor_committed'
                                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal)
                                                OR NOT EXISTS (SELECT 1 FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id
                                                    WHERE l.entry_id = m.commit_entry_id AND l.direction = 'debit' AND a.kind = 'investor_held'
                                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal))) THEN
                                        RAISE EXCEPTION 'Funding must retain the complete confirmed principal and unreturned committed cash' USING ERRCODE = '23514';
                                    END IF;
                                    RETURN NEW;
                                END;
                                $$;
                DROP VIEW primary_live_claim_roots;
                DROP TABLE primary_held_claim_generations;
                DROP TABLE primary_held_claim_releases;
                DROP FUNCTION validate_primary_held_release(), validate_primary_held_generation(), check_primary_claim_generations(char), check_primary_held_release(char);

                SQL);
        });
    }
};
