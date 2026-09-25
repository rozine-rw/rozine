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
    /** The monthly by-the-7th deadline of the original reporting cycle; null for Flash. */
    due_at: string | null;
    overdue: boolean;
    /**
     * Pending the delivery 3 contract and the N6 amendment decision (not sent today). Why a
     * published report was published: every required signature, or automatic approval once the
     * co-sign window lapsed. Absent, the page reads publication from `report.published_at` alone.
     */
    published_reason?: 'signed' | 'auto_approved' | null;
    /**
     * Pending the delivery 3 contract and the N6 amendment decision (not sent today). A dispute
     * this business submitted: while it is `under_review` (the CPA reviews the proof, then amends
     * or upholds the report) or `escalated` (upheld or not acted on, so Rozine staff review it),
     * the 24-hour review timer is paused and nothing is signed or disputed. An amended report
     * comes back `resolved` with a fresh `due_at`, and the page reads as usual.
     */
    dispute?: AuditCosignDispute | null;
};

/** Pending the delivery 3 contract: where a submitted dispute stands. */
export type AuditCosignDispute = {
    status: 'under_review' | 'escalated' | 'resolved';
    submitted_at: string;
};

/**
 * `report.dispute` is pending the delivery 3 contract: it is offered only alongside
 * `actions.dispute`, and the current contract never sends it.
 */
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
        /** Pending the delivery 3 contract: absent or null, no dispute is offered. */
        dispute?: RouteAction | null;
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
 * The dispute is pending the delivery 3 contract. Its body is `{request_id,
 * identity_context_revision, expected_revision: cosign.revision, report_revision, digest, reason}`
 * with a required factual `reason` (at most 2,000 characters). Proof is optional text, files or
 * both (#99): `supporting_text` (at most 1,000 characters) only when written, and `proof_files`
 * only when attached, in which case the command goes as multipart through the same transport, as
 * the Auditor ledger upload does. The field names, the upload transport (multipart or a separate
 * upload) and the accepted file types and sizes are pending that contract.
 */
export type AuditCosignCommand = OperationCommand<AuditCosignAllowedAction> & {
    route: RouteAction;
};

/**
 * `REPORT_COSIGNATURE_RECORDED` (one required signature retained) or `REPORT_PUBLISHED` (every
 * required signature in, and published); `REPORT_DISPUTED` for a dispute, pending the delivery 3
 * contract. `data.next` is where the page continues.
 */
export type AuditCosignOperationResource = SharedOperationResource<{
    next: RouteLink;
}>;
