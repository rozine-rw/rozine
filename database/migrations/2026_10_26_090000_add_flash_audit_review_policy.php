<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives a Flash (listing) report the same 24-hour sign-off or dispute window as a monthly one
 * (BRS OD-07, #363): one signatory signs off or disputes, and silence approves at the deadline.
 * Only reports sealed from now on carry `flash-review-2026-10-26`; a publication keeps the policy
 * it was opened under, so every earlier Flash report stays under the legacy all-signers rule.
 */
return new class extends Migration
{
    private const MONTHLY = "'monthly-review-2026-09-26'";

    private const REVIEWED = "'monthly-review-2026-09-26', 'flash-review-2026-10-26'";

    public function up(): void
    {
        $this->install(self::REVIEWED);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_report_publications IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_report_publications')->where('policy_version', 'flash-review-2026-10-26')->exists()) {
                throw new RuntimeException('Existing Flash review history requires a forward migration.');
            }
            $this->install(self::MONTHLY);
        });
    }

    /** Every review rule, for the policies listed (a SQL list of quoted policy versions). */
    private function install(string $policies): void
    {
        $flash = $policies === self::REVIEWED ? <<<'SQL'
            ELSIF NEW.policy_version = 'flash-review-2026-10-26' AND NOT EXISTS (
                SELECT 1 FROM audit_reports WHERE id = NEW.audit_report_id AND kind = 'flash') THEN
                RAISE EXCEPTION 'Flash review policy requires a Flash report' USING ERRCODE = '23514';
            SQL : '';
        DB::unprepared(<<<SQL
            ALTER TABLE audit_report_publications DROP CONSTRAINT audit_publication_state;
            ALTER TABLE audit_report_publications ADD CONSTRAINT audit_publication_state CHECK (
                revision > 0 AND ((status = 'published' AND published_at IS NOT NULL AND revision > 1)
                OR (published_at IS NULL AND (status = 'pending' OR (policy_version IN ({$policies}) AND status IN ('disputed', 'escalated', 'amended'))))));
            ALTER TABLE audit_report_publications DROP CONSTRAINT audit_review_policy;
            ALTER TABLE audit_report_publications ADD CONSTRAINT audit_review_policy CHECK (
                (policy_version = 'audit-publication-legacy' AND delivered_at IS NULL AND due_at IS NULL AND published_reason IS NULL AND review IS NULL)
                OR (policy_version IN ({$policies}) AND delivered_at IS NOT NULL AND due_at IS NOT NULL AND due_at = delivered_at + interval '24 hours'
                AND ((status = 'published' AND published_reason IS NOT NULL AND published_reason IN ('signed', 'auto_approved', 'staff_resolved'))
                OR (status <> 'published' AND published_reason IS NULL))));
            CREATE OR REPLACE FUNCTION protect_monthly_audit_review() RETURNS trigger LANGUAGE plpgsql AS \$\$
            BEGIN
                IF TG_OP = 'UPDATE' THEN
                    IF ROW(NEW.policy_version, NEW.delivered_at, NEW.due_at) IS DISTINCT FROM ROW(OLD.policy_version, OLD.delivered_at, OLD.due_at)
                        OR OLD.status = 'amended' THEN
                        RAISE EXCEPTION 'Monthly review policy, delivery and terminal history are retained' USING ERRCODE = '23514';
                    END IF;
                ELSIF NEW.policy_version = 'monthly-review-2026-09-26' AND NOT EXISTS (
                    SELECT 1 FROM audit_reports WHERE id = NEW.audit_report_id AND kind = 'monthly') THEN
                    RAISE EXCEPTION 'Monthly review policy requires a monthly report' USING ERRCODE = '23514';
                {$flash}
                END IF;
                RETURN NEW;
            END;
            \$\$;
            CREATE OR REPLACE FUNCTION validate_audit_publication_event() RETURNS trigger LANGUAGE plpgsql AS \$\$
            DECLARE publication audit_report_publications%ROWTYPE;
            BEGIN
                SELECT * INTO publication FROM audit_report_publications WHERE id = NEW.audit_report_publication_id FOR UPDATE;
                IF NOT FOUND OR publication.policy_version NOT IN ({$policies})
                    OR NOT ((NEW.publication_revision = 1 AND publication.revision = 1 AND NEW.command = 'report.delivered')
                        OR (NEW.command <> 'report.delivered' AND NEW.publication_revision = publication.revision + 1 AND publication.status NOT IN ('published', 'amended'))) THEN
                    RAISE EXCEPTION 'Monthly review events advance the current retained publication' USING ERRCODE = '23514';
                END IF;
                IF NEW.previous_sha256 IS DISTINCT FROM (SELECT sha256 FROM audit_publication_events
                    WHERE audit_report_publication_id = NEW.audit_report_publication_id AND publication_revision = NEW.publication_revision - 1) THEN
                    RAISE EXCEPTION 'Review events must extend their retained predecessor' USING ERRCODE = '23514';
                END IF;
                IF NEW.command = 'audit.dispute.resolve' AND EXISTS (SELECT 1 FROM audit_publication_events
                    WHERE audit_report_publication_id = publication.id AND publication_revision = publication.revision AND command = 'audit.dispute.resolve') THEN
                    RAISE EXCEPTION 'A required amendment remains frozen until its replacement is sealed' USING ERRCODE = '23514';
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
            \$\$;
            CREATE OR REPLACE FUNCTION require_audit_publication_signatures() RETURNS trigger LANGUAGE plpgsql AS \$\$
            DECLARE required_count integer; signed_count integer; current_revision integer;
                publication audit_report_publications%ROWTYPE; event_count integer; latest_command varchar;
            BEGIN
                SELECT revision INTO current_revision FROM audit_report_publications WHERE id = NEW.id;
                SELECT count(*) INTO signed_count FROM audit_report_signatures WHERE audit_report_publication_id = NEW.id;
                IF NEW.policy_version IN ({$policies}) THEN
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
                SELECT jsonb_array_length(terms::jsonb->'required_signatories') INTO required_count FROM business_mandates
                    WHERE business_id = NEW.business_id AND version = NEW.mandate_version;
                IF current_revision <> signed_count + 1 OR (NEW.status = 'published' AND signed_count <> required_count) THEN
                    RAISE EXCEPTION 'Publication requires every retained mandated signature' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            \$\$;
            SQL);
    }
};
