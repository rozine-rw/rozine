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
        Schema::table('audit_report_publications', function (Blueprint $table): void {
            $table->string('policy_version', 60)->default('audit-publication-legacy');
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->string('published_reason', 30)->nullable();
            $table->text('review')->nullable();
            $table->index(['policy_version', 'status', 'due_at'], 'audit_review_due');
        });
        Schema::create('audit_publication_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_publication_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('publication_revision');
            $table->string('command', 80);
            $table->string('actor_kind', 20);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignUlid('actor_party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->text('payload');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
            $table->unique(['audit_report_publication_id', 'publication_revision'], 'audit_publication_event_revision');
        });
        Schema::create('audit_dispute_proofs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('audit_report_publication_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('audit_publication_event_id')->constrained()->restrictOnDelete();
            $table->text('content');
            $table->text('filename');
            $table->string('mime_type', 80);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestampTz('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_report_publications DROP CONSTRAINT audit_publication_state;
            ALTER TABLE audit_report_publications ADD CONSTRAINT audit_publication_state CHECK (
                revision > 0 AND ((status = 'published' AND published_at IS NOT NULL AND revision > 1)
                OR (published_at IS NULL AND (status = 'pending' OR (policy_version = 'monthly-review-2026-09-26' AND status IN ('disputed', 'escalated', 'amended'))))));
            ALTER TABLE audit_report_publications ADD CONSTRAINT audit_review_policy CHECK (
                (policy_version = 'audit-publication-legacy' AND delivered_at IS NULL AND due_at IS NULL AND published_reason IS NULL AND review IS NULL)
                OR (policy_version = 'monthly-review-2026-09-26' AND delivered_at IS NOT NULL AND due_at IS NOT NULL AND due_at = delivered_at + interval '24 hours'
                AND ((status = 'published' AND published_reason IS NOT NULL AND published_reason IN ('signed', 'auto_approved', 'staff_resolved'))
                OR (status <> 'published' AND published_reason IS NULL))));
            CREATE OR REPLACE FUNCTION protect_monthly_audit_review() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'UPDATE' THEN
                    IF ROW(NEW.policy_version, NEW.delivered_at, NEW.due_at) IS DISTINCT FROM ROW(OLD.policy_version, OLD.delivered_at, OLD.due_at)
                        OR OLD.status = 'amended' THEN
                        RAISE EXCEPTION 'Monthly review policy, delivery and terminal history are retained' USING ERRCODE = '23514';
                    END IF;
                ELSIF NEW.policy_version = 'monthly-review-2026-09-26' AND NOT EXISTS (
                    SELECT 1 FROM audit_reports WHERE id = NEW.audit_report_id AND kind = 'monthly') THEN
                    RAISE EXCEPTION 'Monthly review policy requires a monthly report' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_review_policy_retained BEFORE INSERT OR UPDATE ON audit_report_publications
                FOR EACH ROW EXECUTE FUNCTION protect_monthly_audit_review();
            ALTER TABLE audit_publication_events ADD CONSTRAINT audit_publication_event_actor CHECK (
                (actor_kind = 'system' AND actor_user_id IS NULL AND actor_party_id IS NULL AND command IN ('report.delivered', 'report.auto_approve', 'report.amended'))
                OR (actor_kind = 'staff' AND actor_user_id IS NOT NULL AND actor_party_id IS NULL AND command IN ('audit.dispute.escalate', 'audit.dispute.resolve'))
                OR (actor_kind = 'party' AND actor_user_id IS NOT NULL AND actor_party_id IS NOT NULL AND command IN ('report.cosign', 'report.dispute', 'audit.dispute.uphold')));
            CREATE OR REPLACE FUNCTION validate_audit_publication_event() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE publication audit_report_publications%ROWTYPE;
            BEGIN
                SELECT * INTO publication FROM audit_report_publications WHERE id = NEW.audit_report_publication_id FOR UPDATE;
                IF NOT FOUND OR publication.policy_version <> 'monthly-review-2026-09-26'
                    OR NOT ((NEW.publication_revision = 1 AND publication.revision = 1 AND NEW.command = 'report.delivered')
                        OR (NEW.command <> 'report.delivered' AND NEW.publication_revision = publication.revision + 1 AND publication.status NOT IN ('published', 'amended'))) THEN
                    RAISE EXCEPTION 'Monthly review events advance the current retained publication' USING ERRCODE = '23514';
                END IF;
                IF NEW.actor_kind = 'party' AND NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.actor_user_id AND party_id = NEW.actor_party_id) THEN
                    RAISE EXCEPTION 'Review actor account must belong to its attributed Party' USING ERRCODE = '23514';
                END IF;
                IF NEW.command IN ('report.cosign', 'report.dispute') AND (publication.status <> 'pending' OR NEW.created_at >= publication.due_at
                    OR NOT EXISTS (SELECT 1 FROM business_mandates m, jsonb_array_elements_text(m.terms::jsonb->'required_signatories') signer
                        WHERE m.business_id = publication.business_id AND m.version = publication.mandate_version AND signer = NEW.actor_party_id::text)) THEN
                    RAISE EXCEPTION 'Business review requires a current pinned signer before expiry' USING ERRCODE = '23514';
                END IF;
                IF NEW.command = 'audit.dispute.uphold' AND (publication.status <> 'disputed' OR NOT EXISTS (
                    SELECT 1 FROM audit_reports WHERE id = publication.audit_report_id AND author_party_id = NEW.actor_party_id)) THEN
                    RAISE EXCEPTION 'Only the assigned CPA can uphold an open dispute' USING ERRCODE = '23514';
                END IF;
                IF (NEW.command = 'audit.dispute.escalate' AND publication.status <> 'disputed')
                    OR (NEW.command = 'audit.dispute.resolve' AND publication.status <> 'escalated') THEN
                    RAISE EXCEPTION 'Staff review requires an open dispute at the matching stage' USING ERRCODE = '23514';
                END IF;
                IF NEW.command = 'report.amended' AND NOT EXISTS (
                    SELECT 1 FROM audit_reports r JOIN audit_report_seals s ON s.audit_report_id = r.id
                    WHERE r.amends_id = publication.audit_report_id AND r.status = 'sealed') THEN
                    RAISE EXCEPTION 'Amended review requires its sealed replacement' USING ERRCODE = '23514';
                END IF;
                IF NEW.command = 'report.auto_approve' AND (publication.status <> 'pending' OR NEW.created_at < publication.due_at) THEN
                    RAISE EXCEPTION 'Only an expired undisputed window can auto approve' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_publication_event_authority BEFORE INSERT ON audit_publication_events
                FOR EACH ROW EXECUTE FUNCTION validate_audit_publication_event();
            CREATE OR REPLACE FUNCTION require_publication_event_commit() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM audit_report_publications WHERE id = NEW.audit_report_publication_id AND revision >= NEW.publication_revision) THEN
                    RAISE EXCEPTION 'A review event must commit with its publication revision' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER audit_publication_event_committed AFTER INSERT ON audit_publication_events
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_publication_event_commit();
            CREATE TRIGGER audit_publication_events_immutable BEFORE UPDATE OR DELETE ON audit_publication_events
                FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
            CREATE OR REPLACE FUNCTION validate_audit_dispute_proof() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE proof_event audit_publication_events%ROWTYPE; publication audit_report_publications%ROWTYPE;
            BEGIN
                SELECT * INTO proof_event FROM audit_publication_events WHERE id = NEW.audit_publication_event_id;
                SELECT * INTO publication FROM audit_report_publications WHERE id = NEW.audit_report_publication_id FOR UPDATE;
                IF proof_event.command IS DISTINCT FROM 'report.dispute' OR proof_event.audit_report_publication_id IS DISTINCT FROM publication.id
                    OR publication.status <> 'pending' OR publication.revision + 1 <> proof_event.publication_revision
                    OR NEW.size_bytes < 1 OR NEW.size_bytes > 10485760 OR NEW.mime_type NOT IN ('application/pdf', 'image/jpeg', 'image/png')
                    OR (SELECT count(*) FROM audit_dispute_proofs WHERE audit_report_publication_id = publication.id) >= 5 THEN
                    RAISE EXCEPTION 'Proof originals must belong to the current new dispute' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_dispute_proof_authority BEFORE INSERT ON audit_dispute_proofs
                FOR EACH ROW EXECUTE FUNCTION validate_audit_dispute_proof();
            CREATE TRIGGER audit_dispute_proofs_immutable BEFORE UPDATE OR DELETE ON audit_dispute_proofs
                FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
            SQL);
        $this->publicationConstraint(true);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_report_publications, audit_publication_events, audit_dispute_proofs IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_report_publications')->where('policy_version', 'monthly-review-2026-09-26')->exists()
                || DB::table('audit_publication_events')->exists() || DB::table('audit_dispute_proofs')->exists()) {
                throw new RuntimeException('Existing monthly review history requires a forward migration.');
            }
            $this->publicationConstraint(false);
            Schema::drop('audit_dispute_proofs');
            Schema::drop('audit_publication_events');
            DB::unprepared(<<<'SQL'
                DROP FUNCTION validate_audit_dispute_proof();
                DROP FUNCTION validate_audit_publication_event();
                DROP FUNCTION require_publication_event_commit();
                DROP TRIGGER audit_review_policy_retained ON audit_report_publications;
                DROP FUNCTION protect_monthly_audit_review();
                ALTER TABLE audit_report_publications DROP CONSTRAINT audit_review_policy;
                ALTER TABLE audit_report_publications DROP CONSTRAINT audit_publication_state;
                ALTER TABLE audit_report_publications ADD CONSTRAINT audit_publication_state CHECK (
                    revision > 0 AND ((status = 'pending' AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL AND revision > 1)));
                SQL);
            Schema::table('audit_report_publications', function (Blueprint $table): void {
                $table->dropIndex('audit_review_due');
                $table->dropColumn(['policy_version', 'delivered_at', 'due_at', 'published_reason', 'review']);
            });
        });
    }

    private function publicationConstraint(bool $monthly): void
    {
        $review = $monthly ? <<<'SQL'
            IF NEW.policy_version = 'monthly-review-2026-09-26' THEN
                SELECT * INTO publication FROM audit_report_publications WHERE id = NEW.id;
                SELECT count(*) INTO event_count FROM audit_publication_events WHERE audit_report_publication_id = NEW.id;
                SELECT command INTO latest_command FROM audit_publication_events WHERE audit_report_publication_id = NEW.id AND publication_revision = publication.revision;
                IF publication.revision <> event_count OR latest_command IS NULL
                    OR (publication.status <> 'published' AND signed_count <> 0)
                    OR (publication.status = 'pending' AND latest_command <> 'report.delivered')
                    OR (publication.status = 'disputed' AND latest_command <> 'report.dispute')
                    OR (publication.status = 'escalated' AND latest_command NOT IN ('audit.dispute.uphold', 'audit.dispute.escalate', 'audit.dispute.resolve'))
                    OR (publication.status = 'amended' AND latest_command <> 'report.amended')
                    OR (publication.status = 'published' AND publication.published_reason = 'signed' AND (signed_count <> 1 OR latest_command <> 'report.cosign'))
                    OR (publication.status = 'published' AND publication.published_reason = 'auto_approved' AND (signed_count <> 0 OR latest_command <> 'report.auto_approve'))
                    OR (publication.status = 'published' AND publication.published_reason = 'staff_resolved' AND (signed_count <> 0 OR latest_command <> 'audit.dispute.resolve')) THEN
                    RAISE EXCEPTION 'Monthly publication requires its retained decision history' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END IF;
            SQL : '';
        DB::unprepared("CREATE OR REPLACE FUNCTION require_audit_publication_signatures() RETURNS trigger LANGUAGE plpgsql AS \$\$
            DECLARE required_count integer; signed_count integer; current_revision integer;
                publication audit_report_publications%ROWTYPE; event_count integer; latest_command varchar;
            BEGIN
                SELECT revision INTO current_revision FROM audit_report_publications WHERE id = NEW.id;
                SELECT count(*) INTO signed_count FROM audit_report_signatures WHERE audit_report_publication_id = NEW.id;
                {$review}
                SELECT jsonb_array_length(terms::jsonb->'required_signatories') INTO required_count FROM business_mandates
                    WHERE business_id = NEW.business_id AND version = NEW.mandate_version;
                IF current_revision <> signed_count + 1 OR (NEW.status = 'published' AND signed_count <> required_count) THEN
                    RAISE EXCEPTION 'Publication requires every retained mandated signature' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            \$\$;");
    }
};
