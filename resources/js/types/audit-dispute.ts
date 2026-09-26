import type { RouteLink } from './routing';

/**
 * A Business dispute of a sealed monthly report under N6 (#96, agreed with the server lane). The
 * Business and the Auditor read the same record; only the Business projection adds `amendment`.
 */

/**
 * The persisted publication policy a sealed report carries. Only `monthly-review-2026-09-26` is
 * the 24-hour sign-off or dispute window; `audit-publication-legacy` is a Flash report or a monthly
 * report sealed before it, which keeps its original deadline and signature rules.
 */
export type AuditPublicationPolicy =
    | 'audit-publication-legacy'
    | 'monthly-review-2026-09-26';

/**
 * Why a published report was published: every required signature, automatic approval at the end
 * of the 24-hour window, or a staff resolution of a dispute. Only `signed` records a signature.
 */
export type AuditPublishedReason =
    | 'signed'
    | 'auto_approved'
    | 'staff_resolved';

/**
 * `under_review` — the assigned CPA reads the proof first; `escalated` — the CPA upheld the
 * findings or staff took an unacted-on case, so Rozine staff review it; `resolved` — staff upheld
 * and published it, or a sealed amendment replaced it. The review window stays paused until then.
 */
export type AuditDisputeStatus = 'under_review' | 'escalated' | 'resolved';

/**
 * `amendment_required` stays `escalated` until the amendment is sealed; `amended` and `upheld` are
 * `resolved`. Neither staff resolution nor automatic approval adds a signature.
 */
export type AuditDisputeOutcome = 'amendment_required' | 'amended' | 'upheld';

/**
 * One retained proof file. `download` is the server's authorized link with attachment disposition:
 * a plain download, never an Inertia visit, and never a path built on the client.
 */
export type AuditDisputeProofFile = {
    id: string;
    name: string;
    mime_type: string;
    size_bytes: number;
    sha256: string;
    download: RouteLink;
};

export type AuditDispute = {
    id: string;
    /** The dispute aggregate's revision: the `expected_revision` an uphold sends. */
    revision: number;
    status: AuditDisputeStatus;
    submitted_at: string;
    /** The Business's plain-text proof, at most 1,000 characters; null when it sent files only. */
    supporting_text: string | null;
    proof_files: AuditDisputeProofFile[];
    outcome: AuditDisputeOutcome | null;
    /** The latest attributed review note — the CPA's uphold reason or staff's — shown as sent. */
    resolution_note: string | null;
    /**
     * Who recorded `resolution_note`: null on submission, `cpa` after the CPA upholds, `staff`
     * after staff escalate or resolve. An amendment keeps the existing author.
     */
    resolution_note_by: 'cpa' | 'staff' | null;
};
