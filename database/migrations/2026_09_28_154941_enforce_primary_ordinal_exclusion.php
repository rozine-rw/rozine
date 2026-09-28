<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Primary\UnitOrdinals;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_reservations IN ACCESS EXCLUSIVE MODE');
            DB::statement('ALTER TABLE primary_reservations ADD COLUMN ordinal_ranges int8multirange');
            DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER primary_reservations_immutable');
            DB::statement('SET CONSTRAINTS primary_reservation_operation IMMEDIATE');
            $this->backfill();
            DB::statement('ALTER TABLE primary_reservations ENABLE TRIGGER primary_reservations_immutable');
            DB::statement('SET CONSTRAINTS primary_reservation_operation DEFERRED');
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION primary_ordinal_count(ranges int8multirange) RETURNS bigint LANGUAGE sql IMMUTABLE STRICT AS $$
                    SELECT COALESCE(SUM(upper(r) - lower(r)), 0)::bigint FROM unnest(ranges) r;
                $$;
                ALTER TABLE primary_reservations ALTER COLUMN ordinal_ranges SET NOT NULL;
                ALTER TABLE primary_reservations ADD CONSTRAINT primary_ordinal_quantity CHECK (
                    ordinal_ranges <@ int8multirange(int8range(1, 20001)) AND primary_ordinal_count(ordinal_ranges) = units);
                CREATE INDEX primary_ordinal_intersection ON primary_reservations USING gist (ordinal_ranges);
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations r JOIN business_campaigns c ON c.id = r.business_campaign_id
                        WHERE NOT r.ordinal_ranges <@ int8multirange(int8range(1, (c.principal / 5000)::bigint + 1)))
                        OR EXISTS (SELECT 1 FROM primary_reservations a JOIN primary_reservations b
                            ON a.business_campaign_id = b.business_campaign_id AND a.id < b.id AND a.ordinal_ranges && b.ordinal_ranges) THEN
                        RAISE EXCEPTION 'Existing Primary ordinal claims require verified nonoverlapping evidence' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                CREATE TABLE primary_ordinal_claims (
                    business_campaign_id char(26) NOT NULL,
                    ordinal integer NOT NULL CHECK (ordinal BETWEEN 1 AND 20000),
                    primary_reservation_id char(26) NOT NULL REFERENCES primary_reservations(id) ON DELETE RESTRICT,
                    PRIMARY KEY (business_campaign_id, ordinal)
                );
                CREATE INDEX primary_claim_reservation ON primary_ordinal_claims (primary_reservation_id);
                CREATE OR REPLACE FUNCTION validate_primary_ordinal_claim() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM primary_reservations WHERE id = NEW.primary_reservation_id
                        AND business_campaign_id = NEW.business_campaign_id AND ordinal_ranges @> NEW.ordinal::bigint) THEN
                        RAISE EXCEPTION 'Primary ordinal claim must bind its reservation' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER primary_claim_valid BEFORE INSERT ON primary_ordinal_claims
                    FOR EACH ROW EXECUTE FUNCTION validate_primary_ordinal_claim();
                CREATE TRIGGER primary_claim_immutable BEFORE UPDATE OR DELETE ON primary_ordinal_claims
                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();
                INSERT INTO primary_ordinal_claims (business_campaign_id, ordinal, primary_reservation_id)
                    SELECT p.business_campaign_id, n, p.id FROM primary_reservations p
                    CROSS JOIN LATERAL unnest(p.ordinal_ranges) r CROSS JOIN LATERAL generate_series(lower(r), upper(r) - 1) n;
                CREATE OR REPLACE FUNCTION retain_primary_ordinal_claims() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    INSERT INTO primary_ordinal_claims (business_campaign_id, ordinal, primary_reservation_id)
                        SELECT NEW.business_campaign_id, n, NEW.id FROM unnest(NEW.ordinal_ranges) r
                        CROSS JOIN LATERAL generate_series(lower(r), upper(r) - 1) n ORDER BY n;
                    RETURN NULL;
                END;
                $$;
                CREATE TRIGGER primary_ordinal_evidence AFTER INSERT ON primary_reservations
                    FOR EACH ROW EXECUTE FUNCTION retain_primary_ordinal_claims();
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
                CREATE TRIGGER primary_ordinals_unique BEFORE INSERT ON primary_reservations
                    FOR EACH ROW EXECUTE FUNCTION exclude_primary_ordinal_overlap();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_reservations) THEN
                        RAISE EXCEPTION 'Retained Primary ordinal claims require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TRIGGER primary_ordinal_evidence ON primary_reservations;
                DROP TABLE primary_ordinal_claims;
                DROP FUNCTION retain_primary_ordinal_claims(), validate_primary_ordinal_claim();
                DROP TRIGGER primary_ordinals_unique ON primary_reservations;
                DROP FUNCTION exclude_primary_ordinal_overlap();
                ALTER TABLE primary_reservations DROP COLUMN ordinal_ranges;
                DROP FUNCTION primary_ordinal_count(int8multirange);
                SQL);
        });
    }

    private function backfill(): void
    {
        foreach (DB::table('primary_reservations')->join('business_campaigns', 'business_campaigns.id', '=', 'primary_reservations.business_campaign_id')
            ->select('primary_reservations.*', 'business_campaigns.principal as campaign_principal')->orderBy('primary_reservations.id')->cursor() as $row) {
            try {
                $payload = json_decode(Crypt::decryptString($row->payload), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($payload) || ! is_array($payload['ordinals'] ?? null) || ! array_is_list($payload['ordinals'])
                    || ! hash_equals($row->sha256, hash('sha256', app(CanonicalJson::class)->encode($payload)))) {
                    throw new RuntimeException('Invalid allocation evidence.');
                }
                foreach (['contract' => 'primary-reservation-1', 'reservation_id' => $row->id, 'campaign_id' => $row->business_campaign_id,
                    'publication_sha256' => $row->publication_sha256, 'party_id' => $row->party_id, 'origin_operation_id' => $row->origin_operation_id,
                    'units' => (string) $row->units, 'principal' => $row->principal] as $key => $value) {
                    if (($payload[$key] ?? null) !== $value) {
                        throw new RuntimeException('Invalid allocation binding.');
                    }
                }
                $ordinals = UnitOrdinals::fromRanges((string) intdiv((int) $row->campaign_principal, 5000), $payload['ordinals']);
                if ((string) $ordinals->count !== (string) $row->units || $ordinals->ranges !== $payload['ordinals']) {
                    throw new RuntimeException('Invalid allocation quantity.');
                }
                $ranges = '{'.implode(',', array_map(fn (array $range): string => '['.$range['first'].','.((int) $range['last'] + 1).')', $ordinals->ranges)).'}';
            } catch (Throwable $exception) {
                throw new RuntimeException('Existing Primary ordinal claims require verified historical evidence.', previous: $exception);
            }
            DB::table('primary_reservations')->where('id', $row->id)->update(['ordinal_ranges' => $ranges]);
        }
    }
};
