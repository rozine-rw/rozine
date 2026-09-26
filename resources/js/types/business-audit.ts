import type { AuditDispute } from './audit-dispute';
import type { BusinessShellLinks } from './business';
import type {
    OperationCommand,
    OperationResource as SharedOperationResource,
} from './operation';
import type { RouteAction, RouteLink } from './routing';

/**
 * The Business audit co-sign contract (business-audit-report-v1, delivery 3), as handed over on
 * rozine-rw/rozine#96 ahead of its tested Resource. The sealed report is immutable: nothing on the
 * page edits it. Each required Party co-signs separately, attributed to the verified identity.
 */

/** One factual finding, exactly as sealed. There is no verdict, rating or score. */
export type AuditReportFinding = {
    code: string;
    title: string;
    body: string;
    evidence_ids: string[];
};

export type AuditReportSeal = {
    status: 'valid' | 'unavailable';
    signed_at: string | null;
    verification: RouteLink | null;
};

export type AuditReport = {
    id: string;
    /** The sealed report's revision; never the co-sign aggregate's. */
    revision: number;
    kind: 'flash' | 'monthly';
    /** YYYY-MM for a monthly report; null for Flash, which has no invented month. */
    period: string | null;
    digest: string;
    procedure_version: string;
    auditor: { name: string; licence: string };
    auditor_note: string;
    findings: AuditReportFinding[];
    seal: AuditReportSeal;
    published_at: string | null;
};

export type AuditCosigner = {
    name: string;
    state: 'pending' | 'signed';
    signed_at: string | null;
    is_you: boolean;
};

export type AuditCosignState =
    | 'pending'
    | 'partly_signed'
    | 'signed'
    | 'unavailable';

export type AuditCosign = {
    /** The mutable co-sign aggregate's revision: the `expected_revision` a co-signature sends. */
    revision: number;
    state: AuditCosignState;
    mandate_version: number;
    required_signatures: number;
    signed_count: number;
    signers: AuditCosigner[];
    /** At most 100 characters, retained with this Party's signature. */
    your_note: string;
    /**
     * When the window closes. Under `monthly-review-2026-09-26` it is `delivered_at` + 24 hours and
     * authoritative; a legacy monthly report keeps its by-the-7th date; null for Flash.
     */
    due_at: string | null;
    overdue: boolean;
    /**
     * The persisted co-sign policy (N6). `monthly-review-2026-09-26` is the 24-hour sign-off or
     * dispute window; any other value, or null, is a report sealed under an earlier policy, which
     * keeps its original wording, deadline and publication history.
     */
    policy_version: string | null;
    /** When the sealed report reached this Business's app: the 24-hour window counts from here. */
    delivered_at: string | null;
    /**
     * Why a published report was published: every required signature, automatic approval at the
     * end of the window (no signature is recorded for it) or a staff resolution of a dispute.
     */
    published_reason: AuditPublishedReason | null;
    /**
     * This Business's dispute of the report. While one exists nothing is signed or disputed, and
     * an open one pauses the window: the page never calls it overdue.
     */
    dispute: BusinessAuditDispute | null;
};

export type AuditPublishedReason =
    | 'signed'
    | 'auto_approved'
    | 'staff_resolved';

/** The Business projection of a dispute: the shared record and, once sealed, its amendment. */
export type BusinessAuditDispute = AuditDispute & {
    amendment: { report_id: string; link: RouteLink } | null;
};

export type AuditCosignAllowedAction = 'report.cosign' | 'report.dispute';

export type BusinessAuditCosignProps = {
    contract_version: 'business-audit-report-v1';
    identity_context_revision: number;
    server_time: string;
    business: { id: string; name: string };
    report: AuditReport;
    cosign: AuditCosign;
    allowed_actions: AuditCosignAllowedAction[];
    actions: {
        cosign: RouteAction | null;
        /** Null, no dispute is offered. */
        dispute: RouteAction | null;
    };
    links: { current: RouteLink; close: RouteLink; operation: RouteLink };
};

/**
 * The page's props as rendered. `shell_links` is not in the handed-over contract; the Business
 * shell needs it (as business-application-v1 sends it), and without it the page offers only its
 * own close link rather than inventing destinations.
 */
export type BusinessAuditCosignPageProps = BusinessAuditCosignProps & {
    shell_links?: BusinessShellLinks;
};

/**
 * A co-signature or dispute exactly as sent, with the route it went to, so a lookup or retry is
 * identical.
 *
 * A dispute (N6) sends `{request_id, identity_context_revision, expected_revision:
 * cosign.revision, report_revision, mandate_version, digest}` with `supporting_text` (plain text,
 * at most 1,000 characters) when written and `proof_files[]` (at most five PDF, JPEG or PNG files
 * of up to 10 MiB each) when attached: at least one of the two. Files go as multipart through the
 * same transport, as the Auditor ledger upload does.
 */
export type AuditCosignCommand = OperationCommand<AuditCosignAllowedAction> & {
    route: RouteAction;
};

/**
 * `REPORT_COSIGNATURE_RECORDED` (one required signature retained) or `REPORT_PUBLISHED` (every
 * required signature in, and published); `REPORT_DISPUTED` for a recorded dispute. `data.next` is
 * where the page continues.
 */
export type AuditCosignOperationResource = SharedOperationResource<{
    next: RouteLink;
}>;
