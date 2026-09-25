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
};

export type AuditCosignAllowedAction = 'report.cosign';

export type BusinessAuditCosignProps = {
    contract_version: 'business-audit-report-v1';
    identity_context_revision: number;
    server_time: string;
    business: { id: string; name: string };
    report: AuditReport;
    cosign: AuditCosign;
    allowed_actions: AuditCosignAllowedAction[];
    actions: { cosign: RouteAction | null };
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

/** A co-signature exactly as sent, with the route it went to, so a lookup or retry is identical. */
export type AuditCosignCommand = OperationCommand<AuditCosignAllowedAction> & {
    route: RouteAction;
};

/**
 * `REPORT_COSIGNATURE_RECORDED` (one required signature retained) or `REPORT_PUBLISHED` (every
 * required signature in, and published). `data.next` is where the page continues.
 */
export type AuditCosignOperationResource = SharedOperationResource<{
    next: RouteLink;
}>;
