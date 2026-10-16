<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_report_publications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_id')->unique()->constrained()->restrictOnDelete();
            $table->ulid('business_id');
            $table->unsignedInteger('mandate_version');
            $table->unsignedInteger('report_revision');
            $table->char('digest', 64);
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->foreign(['business_id', 'mandate_version'])->references(['business_id', 'version'])->on('business_mandates')->restrictOnDelete();
        });
        Schema::create('audit_report_signatures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_publication_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('publication_revision');
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['audit_report_publication_id', 'actor_party_id'], 'audit_report_signature_party');
            $table->unique(['audit_report_publication_id', 'publication_revision'], 'audit_report_signature_revision');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_report_publications ADD CONSTRAINT audit_publication_state CHECK (
                revision > 0 AND ((status = 'pending' AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL AND revision > 1)));
            CREATE OR REPLACE FUNCTION protect_audit_publication() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    IF NEW.revision <> 1 OR NEW.status <> 'pending' OR NOT EXISTS (
                        SELECT 1 FROM audit_reports r JOIN audit_report_seals s ON s.audit_report_id = r.id
                        WHERE r.id = NEW.audit_report_id AND r.business_id = NEW.business_id AND r.status = 'sealed'
                        AND r.revision = NEW.report_revision AND s.report_revision = NEW.report_revision AND s.digest = NEW.digest) THEN
                        RAISE EXCEPTION 'Audit publication requires its retained sealed report' USING ERRCODE = '23514';
                    END IF;
                ELSIF TG_OP = 'DELETE' OR OLD.status = 'published' THEN
                    RAISE EXCEPTION 'Published audit reports cannot be changed' USING ERRCODE = '23514';
                ELSIF ROW(NEW.id, NEW.audit_report_id, NEW.business_id, NEW.mandate_version, NEW.report_revision, NEW.digest, NEW.created_at)
                    IS DISTINCT FROM ROW(OLD.id, OLD.audit_report_id, OLD.business_id, OLD.mandate_version, OLD.report_revision, OLD.digest, OLD.created_at)
                    OR NEW.revision <> OLD.revision + 1 THEN
                    RAISE EXCEPTION 'Audit publication binding is immutable and revisions advance once' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_publication_protected BEFORE INSERT OR UPDATE OR DELETE ON audit_report_publications
                FOR EACH ROW EXECUTE FUNCTION protect_audit_publication();
            CREATE OR REPLACE FUNCTION validate_audit_signature() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE publication audit_report_publications%ROWTYPE;
            BEGIN
                SELECT * INTO publication FROM audit_report_publications WHERE id = NEW.audit_report_publication_id FOR UPDATE;
                IF NOT FOUND OR publication.status <> 'pending' OR NEW.publication_revision <> publication.revision + 1
                    OR NOT EXISTS (SELECT 1 FROM business_mandates m, jsonb_array_elements_text(m.terms::jsonb->'required_signatories') signer
                        WHERE m.business_id = publication.business_id AND m.version = publication.mandate_version AND signer = NEW.actor_party_id::text) THEN
                    RAISE EXCEPTION 'Audit signature requires the pinned mandated Party and current publication revision' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_signature_authority BEFORE INSERT ON audit_report_signatures
                FOR EACH ROW EXECUTE FUNCTION validate_audit_signature();
            CREATE TRIGGER audit_report_signatures_immutable BEFORE UPDATE OR DELETE ON audit_report_signatures
                FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
            CREATE OR REPLACE FUNCTION validate_audit_actor_provenance() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE current_party varchar;
            BEGIN
                SELECT party_id INTO current_party FROM users WHERE id = NEW.actor_user_id FOR SHARE;
                IF NOT FOUND OR current_party IS DISTINCT FROM (to_jsonb(NEW)->>TG_ARGV[0]) THEN
                    RAISE EXCEPTION 'Audit actor account must belong to its attributed Party' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER zzz_audit_ledger_actor BEFORE INSERT ON audit_ledger_originals FOR EACH ROW EXECUTE FUNCTION validate_audit_actor_provenance('actor_party_id');
            CREATE TRIGGER audit_step_up_actor BEFORE INSERT ON audit_step_up_proofs FOR EACH ROW EXECUTE FUNCTION validate_audit_actor_provenance('actor_party_id');
            CREATE TRIGGER audit_seal_actor BEFORE INSERT ON audit_report_seals FOR EACH ROW EXECUTE FUNCTION validate_audit_actor_provenance('author_party_id');
            CREATE TRIGGER audit_signature_actor BEFORE INSERT ON audit_report_signatures FOR EACH ROW EXECUTE FUNCTION validate_audit_actor_provenance('actor_party_id');
            CREATE OR REPLACE FUNCTION require_audit_publication_signatures() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE required_count integer; signed_count integer; current_revision integer;
            BEGIN
                SELECT revision INTO current_revision FROM audit_report_publications WHERE id = NEW.id;
                SELECT count(*) INTO signed_count FROM audit_report_signatures WHERE audit_report_publication_id = NEW.id;
                SELECT jsonb_array_length(terms::jsonb->'required_signatories') INTO required_count FROM business_mandates
                    WHERE business_id = NEW.business_id AND version = NEW.mandate_version;
                IF current_revision <> signed_count + 1 OR (NEW.status = 'published' AND signed_count <> required_count) THEN
                    RAISE EXCEPTION 'Publication requires every retained mandated signature' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER audit_publication_signatures_required AFTER INSERT OR UPDATE ON audit_report_publications
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_audit_publication_signatures();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_report_publications, audit_report_signatures IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_report_publications')->exists() || DB::table('audit_report_signatures')->exists()) {
                throw new RuntimeException('Existing audit publication history requires a forward migration.');
            }
            DB::unprepared('DROP TRIGGER zzz_audit_ledger_actor ON audit_ledger_originals; DROP TRIGGER audit_step_up_actor ON audit_step_up_proofs; DROP TRIGGER audit_seal_actor ON audit_report_seals');
            Schema::drop('audit_report_signatures');
            Schema::drop('audit_report_publications');
            DB::unprepared('DROP FUNCTION protect_audit_publication(); DROP FUNCTION validate_audit_signature(); DROP FUNCTION validate_audit_actor_provenance(); DROP FUNCTION require_audit_publication_signatures()');
        });
    }
};
