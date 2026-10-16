<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->lock();
            Schema::table('audit_engagement_acceptances', function (Blueprint $table): void {
                $table->unique(['id', 'party_id'], 'audit_engagement_acceptance_party_key');
            });
            foreach (['audit_reports', 'statement_verifications'] as $table) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->ulid('engagement_acceptance_id')->nullable();
                });
            }

            /**
             * Backfill only the new relational projection of verified immutable originals.
             * The exclusive table locks and this transaction keep the temporary trigger
             * suspension, metadata fill and constraints atomic, including on failure.
             */
            DB::statement('ALTER TABLE audit_reports DISABLE TRIGGER audit_reports_protected');
            DB::statement('ALTER TABLE statement_verifications DISABLE TRIGGER statement_verifications_immutable');
            $this->backfill('audit_reports', 'binding', 'binding_sha256', 'author_party_id', 'engagement');
            $this->backfill('statement_verifications', 'payload', 'sha256', 'actor_party_id', 'assignment.engagement');
            DB::statement('ALTER TABLE audit_reports ENABLE TRIGGER audit_reports_protected');
            DB::statement('ALTER TABLE statement_verifications ENABLE TRIGGER statement_verifications_immutable');

            foreach (['audit_reports' => 'author_party_id', 'statement_verifications' => 'actor_party_id'] as $table => $partyColumn) {
                Schema::table($table, function (Blueprint $blueprint) use ($table, $partyColumn): void {
                    $blueprint->ulid('engagement_acceptance_id')->nullable(false)->change();
                    $blueprint->foreign(['engagement_acceptance_id', $partyColumn], $table.'_engagement_party')
                        ->references(['id', 'party_id'])->on('audit_engagement_acceptances')->restrictOnDelete();
                });
            }
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION serialize_audit_engagement_release() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM pg_advisory_xact_lock(hashtextextended('audit-engagement-terms', 0));
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_engagement_release_catalog BEFORE INSERT ON audit_engagement_releases
                    FOR EACH ROW EXECUTE FUNCTION serialize_audit_engagement_release();

                CREATE OR REPLACE FUNCTION validate_current_audit_engagement_acceptance() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    current_release audit_engagement_releases%ROWTYPE;
                BEGIN
                    PERFORM pg_advisory_xact_lock_shared(hashtextextended('audit-engagement-terms', 0));
                    SELECT * INTO current_release FROM audit_engagement_releases ORDER BY revision DESC LIMIT 1 FOR SHARE;
                    IF NOT FOUND OR current_release.status <> 'active'
                        OR current_release.id IS DISTINCT FROM NEW.audit_engagement_release_id THEN
                        RAISE EXCEPTION 'Audit engagement acceptance requires the current active release' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_engagement_acceptance_current BEFORE INSERT ON audit_engagement_acceptances
                    FOR EACH ROW EXECUTE FUNCTION validate_current_audit_engagement_acceptance();

                CREATE OR REPLACE FUNCTION validate_audit_engagement_source_pin() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE
                    current_release audit_engagement_releases%ROWTYPE;
                    acceptance audit_engagement_acceptances%ROWTYPE;
                    author_id character(26);
                BEGIN
                    PERFORM pg_advisory_xact_lock_shared(hashtextextended('audit-engagement-terms', 0));
                    author_id := CASE WHEN TG_TABLE_NAME = 'audit_reports' THEN to_jsonb(NEW)->>'author_party_id' ELSE to_jsonb(NEW)->>'actor_party_id' END;
                    SELECT * INTO acceptance FROM audit_engagement_acceptances
                        WHERE id = NEW.engagement_acceptance_id AND party_id = author_id FOR SHARE;
                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Audit source must bind the author engagement acceptance' USING ERRCODE = '23514';
                    END IF;
                    SELECT * INTO current_release FROM audit_engagement_releases ORDER BY revision DESC LIMIT 1 FOR SHARE;
                    IF NOT FOUND OR current_release.status <> 'active'
                        OR current_release.id IS DISTINCT FROM acceptance.audit_engagement_release_id THEN
                        RAISE EXCEPTION 'Audit source must bind current active engagement terms' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER z_audit_reports_engagement_binding BEFORE INSERT ON audit_reports
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_engagement_source_pin();
                CREATE TRIGGER statement_verifications_engagement_binding BEFORE INSERT ON statement_verifications
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_engagement_source_pin();

                CREATE OR REPLACE FUNCTION protect_audit_report_engagement_pin() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.engagement_acceptance_id IS DISTINCT FROM OLD.engagement_acceptance_id THEN
                        RAISE EXCEPTION 'Audit report source binding is immutable' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_reports_engagement_immutable BEFORE UPDATE ON audit_reports
                    FOR EACH ROW EXECUTE FUNCTION protect_audit_report_engagement_pin();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->lock();
            if (DB::table('audit_reports')->exists() || DB::table('statement_verifications')->exists()) {
                throw new RuntimeException('Existing audit engagement source pins require a forward migration.');
            }
            DB::unprepared(<<<'SQL'
                DROP TRIGGER audit_reports_engagement_immutable ON audit_reports;
                DROP TRIGGER z_audit_reports_engagement_binding ON audit_reports;
                DROP TRIGGER statement_verifications_engagement_binding ON statement_verifications;
                DROP TRIGGER audit_engagement_acceptance_current ON audit_engagement_acceptances;
                DROP TRIGGER audit_engagement_release_catalog ON audit_engagement_releases;
                DROP FUNCTION protect_audit_report_engagement_pin();
                DROP FUNCTION validate_audit_engagement_source_pin();
                DROP FUNCTION validate_current_audit_engagement_acceptance();
                DROP FUNCTION serialize_audit_engagement_release();
                SQL);
            foreach (['audit_reports', 'statement_verifications'] as $table) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->dropForeign($table.'_engagement_party');
                    $blueprint->dropColumn('engagement_acceptance_id');
                });
            }
            Schema::table('audit_engagement_acceptances', function (Blueprint $table): void {
                $table->dropUnique('audit_engagement_acceptance_party_key');
            });
        });
    }

    private function lock(): void
    {
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['audit-engagement-terms']);
        DB::statement('LOCK TABLE audit_engagement_releases, audit_engagement_acceptances, audit_reports, statement_verifications IN ACCESS EXCLUSIVE MODE');
    }

    private function backfill(string $table, string $payloadColumn, string $hashColumn, string $partyColumn, string $pinPath): void
    {
        $json = app(CanonicalJson::class);
        foreach (DB::table($table)->orderBy('id')->cursor() as $row) {
            try {
                $payload = json_decode(Crypt::decryptString($row->{$payloadColumn}), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|JsonException) {
                throw new RuntimeException('Existing audit engagement source pins require verified historical evidence.');
            }
            $pin = data_get($payload, $pinPath);
            if (! is_array($payload) || ! hash_equals($row->{$hashColumn}, hash('sha256', $json->encode($payload)))
                || ! is_array($pin) || ! is_string($pin['id'] ?? null)) {
                throw new RuntimeException('Existing audit engagement source pins require verified historical evidence.');
            }
            $acceptance = DB::table('audit_engagement_acceptances')->where('id', $pin['id'])->where('party_id', $row->{$partyColumn})->first();
            foreach (['id' => 'id', 'release_id' => 'audit_engagement_release_id', 'release_revision' => 'release_revision',
                'release_sha256' => 'release_sha256', 'sha256' => 'sha256'] as $key => $column) {
                if ($acceptance === null || ($pin[$key] ?? null) !== $acceptance->{$column}) {
                    throw new RuntimeException('Existing audit engagement source pins require verified historical evidence.');
                }
            }
            DB::table($table)->where('id', $row->id)->update(['engagement_acceptance_id' => $pin['id']]);
        }
    }
};
