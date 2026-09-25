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
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports, audit_report_seals, audit_step_up_proofs, audit_report_publications, audit_report_signatures IN ACCESS EXCLUSIVE MODE');
            Schema::table('audit_step_up_proofs', function (Blueprint $table): void {
                $table->string('purpose', 40)->default('audit.seal');
            });
            Schema::table('audit_report_seals', function (Blueprint $table): void {
                $table->foreignUlid('step_up_proof_id')->nullable()->unique()->constrained('audit_step_up_proofs')->restrictOnDelete();
            });
            $matches = <<<'SQL'
                proof.audit_report_id = seal.audit_report_id AND proof.report_revision + 1 = seal.report_revision
                AND proof.actor_party_id = seal.author_party_id AND proof.actor_user_id = seal.actor_user_id
                AND proof.digest = seal.digest AND proof.consumed_at IS NOT NULL
                SQL;
            if (DB::selectOne("SELECT seal.id FROM audit_report_seals seal WHERE (SELECT count(*) FROM audit_step_up_proofs proof WHERE {$matches}) <> 1 LIMIT 1") !== null) {
                throw new RuntimeException('Existing audit seals require one unambiguous consumed proof before protection.');
            }
            DB::unprepared("ALTER TABLE audit_report_seals DISABLE TRIGGER audit_report_seals_immutable;
                UPDATE audit_report_seals seal SET step_up_proof_id = (SELECT proof.id FROM audit_step_up_proofs proof WHERE {$matches});
                ALTER TABLE audit_report_seals ENABLE TRIGGER audit_report_seals_immutable;
                ALTER TABLE audit_report_seals ALTER COLUMN step_up_proof_id SET NOT NULL;");
            DB::unprepared(<<<'SQL'
                ALTER TABLE audit_step_up_proofs ADD CONSTRAINT audit_step_up_purpose CHECK (purpose = 'audit.seal');
                CREATE OR REPLACE FUNCTION retain_audit_step_up_purpose() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF TG_OP = 'DELETE' OR NEW.purpose IS DISTINCT FROM OLD.purpose THEN
                        RAISE EXCEPTION 'Audit step-up purpose and history must be retained' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_step_up_retained BEFORE UPDATE OR DELETE ON audit_step_up_proofs
                    FOR EACH ROW EXECUTE FUNCTION retain_audit_step_up_purpose();
                CREATE OR REPLACE FUNCTION validate_audit_seal_proof() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE proof audit_step_up_proofs%ROWTYPE;
                BEGIN
                    SELECT * INTO proof FROM audit_step_up_proofs WHERE id = NEW.step_up_proof_id FOR SHARE;
                    IF NOT FOUND OR proof.purpose <> 'audit.seal' OR proof.consumed_at IS NULL
                        OR proof.audit_report_id IS DISTINCT FROM NEW.audit_report_id
                        OR proof.actor_party_id IS DISTINCT FROM NEW.author_party_id
                        OR proof.actor_user_id IS DISTINCT FROM NEW.actor_user_id
                        OR proof.report_revision + 1 <> NEW.report_revision OR proof.digest IS DISTINCT FROM NEW.digest THEN
                        RAISE EXCEPTION 'Audit seal requires its consumed purpose-bound proof' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_seal_proof_required BEFORE INSERT ON audit_report_seals
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_seal_proof();
                CREATE OR REPLACE FUNCTION lock_audit_amendment_parent() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM id FROM audit_reports WHERE id = NEW.amends_id FOR UPDATE;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_amendment_publication_lock BEFORE INSERT ON audit_reports
                    FOR EACH ROW WHEN (NEW.amends_id IS NOT NULL) EXECUTE FUNCTION lock_audit_amendment_parent();
                CREATE OR REPLACE FUNCTION require_unamended_audit_publication() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE report_id char(26);
                BEGIN
                    IF TG_TABLE_NAME = 'audit_report_signatures' THEN
                        SELECT audit_report_id INTO report_id FROM audit_report_publications WHERE id = NEW.audit_report_publication_id;
                    ELSE
                        report_id := NEW.audit_report_id;
                    END IF;
                    PERFORM id FROM audit_reports WHERE id = report_id FOR SHARE;
                    IF EXISTS (SELECT 1 FROM audit_reports WHERE amends_id = report_id) THEN
                        RAISE EXCEPTION 'An amended audit report cannot receive signatures or publish' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_signature_00_lineage BEFORE INSERT ON audit_report_signatures
                    FOR EACH ROW EXECUTE FUNCTION require_unamended_audit_publication();
                CREATE TRIGGER audit_publication_lineage BEFORE INSERT OR UPDATE ON audit_report_publications
                    FOR EACH ROW WHEN (NEW.status = 'published') EXECUTE FUNCTION require_unamended_audit_publication();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports, audit_report_seals, audit_step_up_proofs, audit_report_publications, audit_report_signatures IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_step_up_proofs')->exists() || DB::table('audit_report_publications')->exists()) {
                throw new RuntimeException('Existing audit proof and publication history requires a forward migration.');
            }
            DB::unprepared(<<<'SQL'
                DROP TRIGGER audit_publication_lineage ON audit_report_publications;
                DROP TRIGGER audit_signature_00_lineage ON audit_report_signatures;
                DROP FUNCTION require_unamended_audit_publication();
                DROP TRIGGER audit_amendment_publication_lock ON audit_reports;
                DROP FUNCTION lock_audit_amendment_parent();
                DROP TRIGGER audit_seal_proof_required ON audit_report_seals;
                DROP FUNCTION validate_audit_seal_proof();
                DROP TRIGGER audit_step_up_retained ON audit_step_up_proofs;
                DROP FUNCTION retain_audit_step_up_purpose();
                ALTER TABLE audit_step_up_proofs DROP CONSTRAINT audit_step_up_purpose;
                SQL);
            Schema::table('audit_report_seals', function (Blueprint $table): void {
                $table->dropForeign(['step_up_proof_id']);
                $table->dropColumn('step_up_proof_id');
            });
            Schema::table('audit_step_up_proofs', function (Blueprint $table): void {
                $table->dropColumn('purpose');
            });
        });
    }
};
