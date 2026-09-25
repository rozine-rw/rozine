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
        Schema::table('audit_assignments', function (Blueprint $table): void {
            $table->unique(['id', 'business_id'], 'audit_assignment_business_key');
        });
        Schema::table('business_applications', function (Blueprint $table): void {
            $table->unique(['id', 'business_id'], 'application_business_key');
        });
        Schema::table('business_application_versions', function (Blueprint $table): void {
            $table->unique(['id', 'business_application_id', 'revision'], 'application_version_binding_key');
        });
        Schema::create('audit_reports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('assignment_id');
            $table->unsignedInteger('assignment_revision');
            $table->foreignUlid('author_party_id')->constrained('parties')->restrictOnDelete();
            $table->ulid('business_id');
            $table->ulid('application_id');
            $table->unsignedInteger('application_revision');
            $table->ulid('application_version_id');
            $table->ulid('submission_id');
            $table->ulid('quote_id');
            $table->ulid('amends_id')->nullable()->unique();
            $table->unsignedInteger('revision');
            $table->string('kind', 20);
            $table->string('status', 30);
            $table->string('step', 30);
            $table->text('binding');
            $table->char('binding_sha256', 64);
            $table->text('draft');
            $table->timestampsTz();
            $table->unique(['id', 'assignment_id', 'assignment_revision', 'author_party_id'], 'audit_report_assignment_key');
            $table->foreign(['assignment_id', 'business_id'], 'audit_report_assignment_business')
                ->references(['id', 'business_id'])->on('audit_assignments')->restrictOnDelete();
            $table->foreign(['application_id', 'business_id'], 'audit_report_application_business')
                ->references(['id', 'business_id'])->on('business_applications')->restrictOnDelete();
            $table->foreign(['application_version_id', 'application_id', 'application_revision'], 'audit_report_application_version')
                ->references(['id', 'business_application_id', 'revision'])->on('business_application_versions')->restrictOnDelete();
            $table->foreign(['application_id', 'submission_id'], 'audit_report_submission_owner')
                ->references(['business_application_id', 'id'])->on('business_application_submissions')->restrictOnDelete();
            $table->foreign(['application_id', 'quote_id'], 'audit_report_quote_owner')
                ->references(['business_application_id', 'id'])->on('business_application_quotes')->restrictOnDelete();
            $table->foreign(['amends_id', 'assignment_id', 'assignment_revision', 'author_party_id'], 'audit_report_amendment_assignment')
                ->references(['id', 'assignment_id', 'assignment_revision', 'author_party_id'])->on('audit_reports')->restrictOnDelete();
        });
        Schema::create('audit_report_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 30);
            $table->string('step', 30);
            $table->text('snapshot');
            $table->char('sha256', 64);
            $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 80);
            $table->timestampTz('created_at');
            $table->unique(['audit_report_id', 'revision']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_revision CHECK (revision > 0 AND application_revision > 0 AND assignment_revision > 0);
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_status CHECK (status IN ('draft', 'sealed', 'changes_requested', 'rejected', 'withdrawn'));
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_step CHECK (
                (kind = 'flash' AND step IN ('review', 'check_in', 'photos', 'ledger', 'seal')) OR
                (kind = 'monthly' AND step IN ('statements', 'count', 'photos', 'seal'))
            );
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_seal_step CHECK (status <> 'sealed' OR step = 'seal');
            ALTER TABLE audit_reports ADD CONSTRAINT audit_report_distinct_amendment CHECK (amends_id IS NULL OR amends_id <> id);
            ALTER TABLE audit_report_versions ADD CONSTRAINT audit_report_version_revision CHECK (revision > 0);
            CREATE UNIQUE INDEX audit_report_root_assignment ON audit_reports (assignment_id, assignment_revision) WHERE amends_id IS NULL;
            CREATE OR REPLACE FUNCTION validate_audit_report_binding() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                application business_applications%ROWTYPE;
                assignment audit_assignments%ROWTYPE;
            BEGIN
                SELECT * INTO application FROM business_applications WHERE id = NEW.application_id FOR SHARE;
                IF NOT FOUND OR application.status <> 'submitted' OR application.revision <> NEW.application_revision
                    OR application.current_submission_id IS DISTINCT FROM NEW.submission_id
                    OR application.current_quote_id IS DISTINCT FROM NEW.quote_id THEN
                    RAISE EXCEPTION 'Audit report must bind the current submitted application' USING ERRCODE = '23514';
                END IF;
                SELECT * INTO assignment FROM audit_assignments WHERE id = NEW.assignment_id FOR SHARE;
                IF NOT FOUND OR assignment.status <> 'accepted' OR assignment.revision <> NEW.assignment_revision
                    OR assignment.party_id IS DISTINCT FROM NEW.author_party_id THEN
                    RAISE EXCEPTION 'Audit report must bind the current accepted Auditor' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_reports_source_binding BEFORE INSERT ON audit_reports FOR EACH ROW EXECUTE FUNCTION validate_audit_report_binding();
            CREATE OR REPLACE FUNCTION protect_audit_report() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' OR OLD.status <> 'draft' THEN
                    RAISE EXCEPTION 'Final audit reports and report history are immutable' USING ERRCODE = '23514';
                END IF;
                IF ROW(NEW.id, NEW.assignment_id, NEW.assignment_revision, NEW.author_party_id, NEW.business_id, NEW.application_id, NEW.application_revision,
                    NEW.application_version_id, NEW.submission_id, NEW.quote_id, NEW.amends_id, NEW.kind,
                    NEW.binding, NEW.binding_sha256, NEW.created_at)
                    IS DISTINCT FROM ROW(OLD.id, OLD.assignment_id, OLD.assignment_revision, OLD.author_party_id, OLD.business_id, OLD.application_id, OLD.application_revision,
                    OLD.application_version_id, OLD.submission_id, OLD.quote_id, OLD.amends_id, OLD.kind,
                    OLD.binding, OLD.binding_sha256, OLD.created_at) THEN
                    RAISE EXCEPTION 'Audit report source binding is immutable' USING ERRCODE = '23514';
                END IF;
                IF NEW.revision <> OLD.revision + 1 THEN
                    RAISE EXCEPTION 'Audit report revision must advance once' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_reports_protected BEFORE UPDATE OR DELETE ON audit_reports FOR EACH ROW EXECUTE FUNCTION protect_audit_report();
            CREATE OR REPLACE FUNCTION reject_audit_report_version_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit report version history is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_report_versions_immutable BEFORE UPDATE OR DELETE ON audit_report_versions FOR EACH ROW EXECUTE FUNCTION reject_audit_report_version_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports, audit_report_versions IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_reports')->exists() || DB::table('audit_report_versions')->exists()) {
                throw new RuntimeException('Existing audit report history requires a forward migration.');
            }
            Schema::dropIfExists('audit_report_versions');
            Schema::dropIfExists('audit_reports');
            DB::unprepared('DROP FUNCTION IF EXISTS reject_audit_report_version_mutation(); DROP FUNCTION IF EXISTS protect_audit_report(); DROP FUNCTION IF EXISTS validate_audit_report_binding()');
            Schema::table('business_application_versions', function (Blueprint $table): void {
                $table->dropUnique('application_version_binding_key');
            });
            Schema::table('business_applications', function (Blueprint $table): void {
                $table->dropUnique('application_business_key');
            });
            Schema::table('audit_assignments', function (Blueprint $table): void {
                $table->dropUnique('audit_assignment_business_key');
            });
        });
    }
};
