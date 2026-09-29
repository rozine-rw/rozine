import type { Money } from './money';
import type { RouteAction, RouteLink } from './routing';

/**
 * Auditor app page contracts (Phase 1B, crosswalk MVP-AUDITOR-SCR-01…07). Every figure, deadline,
 * variance, tolerance and gate is a server fact; the client formats and arranges it and never
 * decides eligibility, money, credit or workflow. No screen asks the partner for a judgement
 * (MVP-AUDITOR-AC-02): the procedure records factual findings, and the engine rates.
 */

/* ------------------------------------------------------------------------------------------ */
/* Shell                                                                                        */
/* ------------------------------------------------------------------------------------------ */

/** Where the Auditor shell's tabs and launcher link go. */
export type AuditorAppLinks = {
    home: RouteLink;
    jobs: RouteLink;
    portfolio: RouteLink;
    profile: RouteLink;
    launcher: RouteLink;
};

/** Business sector, as a code so the file tile takes the design's sector colour. */
export type SectorCode =
    | 'agriculture'
    | 'logistics'
    | 'manufacturing'
    | 'retail'
    | 'energy'
    | 'technology'
    | 'services';

export type AuditorIdentity = {
    /** Display name with designation: "Diane Uwase, CPA". */
    name: string;
    firm: string;
    /** Professional body and designation, as the register shows it: "ICPAR · CPA". */
    accreditation: string;
    avatar_url: string | null;
    since_year: number;
};

/**
 * Dispatch availability. Radius and concurrency are policy values (CFG-05), shown, never edited
 * here; only the partner's own on/off choice is a command.
 */
export type AuditorAvailability = {
    accepting: boolean;
    radius_km: number;
    max_active: number;
    toggle: RouteAction;
};

export type AuditorStanding = {
    /** Share of jobs closed inside their clock, or null before the first closes. */
    on_time_pct: number | null;
    /** Average recorded variance, one decimal: "2.1". Null before any finding. */
    avg_variance_pct: string | null;
    /** The server's flag that the average sits outside the published tolerance. */
    variance_flagged: boolean;
    jobs_done: number;
    clock_expiries: number;
};

/** A countdown against a server deadline. `server_time` anchors the clock (contract §4). */
export type Deadline = {
    due_at: string;
};

/* ------------------------------------------------------------------------------------------ */
/* Home (design L86–203)                                                                        */
/* ------------------------------------------------------------------------------------------ */

export type AssignedJobStatus = 'in_progress' | 'overdue' | 'awaiting_cosign';

/** Accepted work still running its clock. */
export type AssignedJob = {
    id: string;
    kind: 'flash' | 'monthly';
    business: string;
    district: string;
    /** Distance from the registered office, already formatted by the server: "7.7". */
    distance_km: string;
    deadline: Deadline;
    step: number;
    steps: number;
    status: AssignedJobStatus;
    /** Set when the job came to this partner from another; the original deadline still runs. */
    reassigned_from: string | null;
    link: RouteLink;
};

/** Recent activity, newest first, as the server records it. */
export type AuditorActivity =
    | {
          kind: 'report_filed';
          at: string;
          business: string;
          /** Recorded variance, one decimal, or null when none was measured. */
          variance_pct: string | null;
      }
    | { kind: 'report_published'; at: string; business: string; month: string }
    | {
          kind: 'repayment_received';
          at: string;
          business: string;
          amount: Money;
      }
    | { kind: 'repayment_missed'; at: string; business: string }
    | { kind: 'payment_deferred'; at: string; business: string; cause: string }
    | { kind: 'payout'; at: string; amount: Money };

export type AuditorHomeProps = {
    server_time: string;
    auditor: AuditorIdentity;
    /** The published partner-quality score out of 100; null until the quality policy is live. */
    quality_score: number | null;
    /** Accrued service-fee share this month (C-23), or null before anything accrues. */
    earned_this_month: Money | null;
    active_deals: number;
    licence_expires_on: string;
    availability: AuditorAvailability;
    /** Eligible Flash Audits right now and the closest one's distance. */
    nearby: { count: number; closest_km: string | null };
    in_progress: AssignedJob[];
    standing: AuditorStanding;
    activity: AuditorActivity[];
    wallet: { available: Money };
    unread_notifications: number;
    links: AuditorAppLinks & {
        statement: RouteLink;
        withdraw: RouteLink;
        notifications: RouteLink;
    };
};

/* ------------------------------------------------------------------------------------------ */
/* Jobs (MVP-AUDITOR-SCR-01, design L205–323)                                                   */
/* ------------------------------------------------------------------------------------------ */

/** Commands every offered or assigned file carries; a conflict can always be declared (AC-08). */
export type JobActions = {
    accept: RouteAction;
    decline: RouteAction;
    conflict: RouteAction;
};

/** A Flash Audit this partner is eligible for, offered by dispatch. */
export type EligibleJob = {
    id: string;
    business: string;
    sector: SectorCode;
    district: string;
    offered_at: string;
    distance_km: string;
    requested: Money;
    /** The engine's DSCR for the case, formatted: "1.12". Null while unrated. */
    dscr: string | null;
    term_months: number;
    /** Where to draw the pin on the radius map, relative to the registered office. */
    map: { east_km: number; north_km: number };
    link: RouteLink;
    actions: JobActions;
};

/** A monthly window the partner opens for a note they steward. */
export type MonthlyWindow = {
    id: string;
    note_title: string;
    note_id: string;
    business: string;
    /** First day of the audited month, ISO 8601. */
    month: string;
    open: RouteAction;
};

export type MonthlyReportStatus =
    | 'in_progress'
    | 'overdue'
    | 'changes_requested'
    | 'awaiting_cosign';

export type MonthlyReportCard = {
    id: string;
    business: string;
    sector: SectorCode;
    note_title: string;
    due_on: string;
    status: MonthlyReportStatus;
    inflow: Money;
    outflow: Money;
    /** Liquidity cover from the statements, formatted: "1.3". */
    cover: string | null;
    link: RouteLink;
};

/** Conflict kinds the policy recognises (CFG-05 rotation/conflicts). */
export type ConflictKind =
    | 'financial_interest'
    | 'role_tie'
    | 'family_or_business'
    | 'other';

/** What the server did after a command, shown once in the design's result card. */
export type AuditorOutcome =
    | {
          kind: 'conflict_declared';
          business: string;
          /** Reassigned to a named partner, queued for operations, or on the record only. */
          resolution: 'reassigned' | 'queued' | 'recorded';
          reassigned_to: string | null;
      }
    | { kind: 'job_declined'; business: string }
    | {
          kind: 'report_sealed';
          business: string;
          /** The audited month for a monthly report; null for a Flash Audit. */
          month: string | null;
          cosign_due_on: string;
      }
    | { kind: 'changes_requested'; business: string }
    | { kind: 'report_rejected'; business: string };

export type AuditorJobsProps = {
    server_time: string;
    radius_km: number;
    flash_hours: number;
    eligible: EligibleJob[];
    assigned: AssignedJob[];
    monthly: { windows: MonthlyWindow[]; reports: MonthlyReportCard[] };
    outcome: AuditorOutcome | null;
    links: AuditorAppLinks;
};

/* ------------------------------------------------------------------------------------------ */
/* Business file (MVP-AUDITOR-SCR-02, design step 0 L1038–1093)                                 */
/* ------------------------------------------------------------------------------------------ */

export type FileDocument = {
    name: string;
    detail: string;
    status: 'parsed' | 'verified' | 'present' | 'missing';
    kind: 'statement' | 'registry' | 'identity' | 'pitch' | 'photos';
};

export type BusinessFile = {
    raise: {
        requested: Money;
        term_months: number;
        /** Flat total return over the whole term, one decimal: "10.0". Not an APR. */
        return_pct: string;
        sector: SectorCode;
        use_of_funds: string;
    };
    documents: FileDocument[];
    /** The engine's automated checks, read-only: a factual met or flagged per rule. */
    prescreen: { label: string; detail: string; result: 'met' | 'flag' }[];
    /** Why the engine sent this case to the field. */
    reason: string;
    /** The procedure version's agreed steps for the visit, in order. */
    mandate: string[];
    history: {
        last_audit: {
            on: string;
            kind: 'flash' | 'monthly';
            by: string;
            summary: string;
        } | null;
        flags: string[];
    };
};

export type FileJob = {
    id: string;
    business: string;
    district: string;
    distance_km: string;
    /** Offered: the clock has not started. Assigned: it runs from `deadline`. */
    state: 'offered' | 'assigned';
    deadline: Deadline | null;
    reassigned_from: string | null;
};

export type AuditorFileProps = {
    server_time: string;
    flash_hours: number;
    job: FileJob;
    file: BusinessFile;
    actions: JobActions;
    links: { close: RouteLink; procedure: RouteLink | null };
    /** Jobs, drawn beneath the sheet on a wide screen. */
    jobs: AuditorJobsProps;
};

/* ------------------------------------------------------------------------------------------ */
/* Audit procedure (MVP-AUDITOR-SCR-03…06, design sheets L1017–1498)                             */
/* ------------------------------------------------------------------------------------------ */

export type FlashStep = 'review' | 'check_in' | 'photos' | 'ledger' | 'seal';
export type MonthlyStep = 'statements' | 'count' | 'photos' | 'seal';
export type AuditStepKey = FlashStep | MonthlyStep;

/**
 * The narrow native capture companion's package, as the server last heard it (D-04). Photos and
 * the on-site check-in are captured there, never on the web.
 */
export type CapturePackage = {
    status:
        | 'not_started'
        | 'capturing'
        | 'syncing'
        | 'needs_attention'
        | 'complete';
    attention: 'no_signal' | 'storage_full' | 'upload_failed' | null;
    received: number;
    expected: number;
    last_sync_at: string | null;
    /** Opens the capture companion on this assignment. */
    handoff: { url: string };
};

export type CheckInStage = {
    step: 'check_in';
    package: CapturePackage;
    check_in:
        | { state: 'pending' }
        | {
              state: 'recorded';
              at: string;
              /** Formatted coordinates: "-1.9441, 30.0619". */
              position: string;
              accuracy_m: number;
              /** True when accuracy or distance from the premises sends it to review. */
              review: boolean;
          };
};

export type PhotoSlot = {
    key: string;
    label: string;
    required: boolean;
    captured_at: string | null;
    position: string | null;
    thumbnail_url: string | null;
    /** Extra photos carry the partner's own title. */
    extra: boolean;
    title: string;
};

export type PhotosStage = {
    step: 'photos';
    package: CapturePackage;
    required: number;
    slots: PhotoSlot[];
    title_max: number;
};

export type Variance = {
    /** Signed percentage, one decimal: "-12.4". */
    pct: string;
    within: boolean;
};

export type LedgerDocument = {
    id: string;
    name: string;
    /** File type and size, as the server describes it: "PDF · 2.4 MB". */
    detail: string;
    state: 'scanning' | 'parsed' | 'failed';
    fields: { label: string; value: string }[];
    failure: string | null;
};

export type LedgerStage = {
    step: 'ledger';
    reported_stock: Money;
    observed_stock: Money | null;
    /** The policy tolerance, as the server words it: "RWF 0". */
    tolerance: string;
    variance: Variance | null;
    documents: LedgerDocument[];
    /** A document has parsed, so the reconciliation fact can be recorded. */
    ledger_ready: boolean;
    reconciled: boolean;
    upload: RouteAction;
};

export type ReviewStage = {
    step: 'review';
    file: BusinessFile;
};

export type StatementsStage = {
    step: 'statements';
    statements:
        | {
              status: 'available';
              inflow: Money;
              outflow: Money;
              net: Money;
              cover: { value: string; band: 'healthy' | 'watch' | 'below' };
              document: { name: string; link: RouteLink };
          }
        | { status: 'unavailable'; reason: string };
};

export type ProofItem = {
    key: string;
    label: string;
    hint: string;
    seen: boolean;
};

export type CountStage = {
    step: 'count';
    financial_proofs: ProofItem[];
    cash: {
        observed: Money | null;
        statement: Money | null;
        variance: Variance | null;
    };
    tolerance: string;
    period: { from: string; to: string };
    account_ref: string;
    sector: { label: string; definition: string };
    inventory_proofs: ProofItem[];
    stock: {
        observed_units: string | null;
        reported_units: string | null;
        variance: Variance | null;
    };
    operational_status: 'active' | 'suspended' | 'restricted' | null;
};

export type SealStage = {
    step: 'seal';
    summary: {
        label: string;
        value: string;
        tone: 'ok' | 'flag' | 'neutral';
    }[];
    note: {
        required: boolean;
        /** Why a note is or isn't required, from the server's flags. */
        why: string;
        value: string;
        min: number;
        max: number;
    };
    findings: { no: string; title: string; body: string }[];
    procedure_version: string;
    /** The digest of the package that will be sealed. */
    digest: string;
    licence: string;
    seal: RouteAction;
    /** Monthly reports only: send back or escalate the business's own submission. */
    suggest: RouteAction | null;
    reject: RouteAction | null;
};

export type CosignState = 'pending' | 'signed' | 'declined' | 'overdue';

export type SealedStage = {
    step: 'sealed';
    sealed_at: string;
    digest: string;
    licence: string;
    cosign: {
        party: string;
        state: CosignState;
        due_on: string;
        signed_at: string | null;
    };
    published_at: string | null;
    amend: RouteLink | null;
};

export type AuditStage =
    | ReviewStage
    | CheckInStage
    | PhotosStage
    | LedgerStage
    | StatementsStage
    | CountStage
    | SealStage
    | SealedStage;

export type AuditProcedureProps = {
    server_time: string;
    audit: {
        id: string;
        kind: 'flash' | 'monthly';
        business: string;
        district: string;
        distance_km: string;
        /** The audited month for a monthly report. */
        month: string | null;
        deadline: Deadline;
        revision: number;
        reassigned_from: string | null;
    };
    steps: { key: AuditStepKey; state: 'done' | 'current' | 'todo' }[];
    stage: AuditStage;
    /** The server's gate: may this step be committed and the next opened? */
    can_continue: boolean;
    /** What still blocks the step, or what sealing will do, as the server words it. */
    hint: string | null;
    links: { close: RouteLink; back: RouteLink | null };
    actions: { save: RouteAction; conflict: RouteAction };
    outcome: AuditorOutcome | null;
    /** Jobs, drawn beneath the sheet on a wide screen. */
    jobs: AuditorJobsProps;
};

/* ------------------------------------------------------------------------------------------ */
/* Portfolio: reports and the conflict register (MVP-AUDITOR-SCR-07, AC-08)                     */
/* ------------------------------------------------------------------------------------------ */

export type FiledReportStatus =
    | 'awaiting_cosign'
    | 'published'
    | 'late'
    | 'rejected';

export type FiledReport = {
    id: string;
    business: string;
    kind: 'flash' | 'monthly';
    /** The audited month for a monthly report, or null for a Flash Audit. */
    month: string | null;
    district: string;
    filed_on: string;
    due_on: string;
    status: FiledReportStatus;
    late_days: number | null;
    rejection: { reason: string; amend: RouteLink } | null;
    link: RouteLink;
};

export type ReportFilter = 'all' | FiledReportStatus;

export type ConflictEntry = {
    id: string;
    business: string;
    note_id: string;
    kind: ConflictKind;
    declared_on: string;
};

export type AssignedFile = {
    id: string;
    business: string;
    note_id: string;
};

export type AuditorPortfolioProps = {
    server_time: string;
    reports: FiledReport[];
    filter: ReportFilter;
    filters: { key: ReportFilter; count: number; link: RouteLink }[];
    conflicts: {
        files: AssignedFile[];
        record: ConflictEntry[];
        declare: RouteAction;
    };
    outcome: AuditorOutcome | null;
    /** Eligible Flash Audits right now, for the Jobs tab badge. */
    open_jobs: number;
    links: AuditorAppLinks;
};

/* ------------------------------------------------------------------------------------------ */
/* Profile: accreditation and availability (design L593–1013)                                  */
/* ------------------------------------------------------------------------------------------ */

export type AccreditationRenewal =
    | { status: 'none'; submit: RouteAction }
    | {
          status: 'pending';
          id: string;
          licence: string;
          expires_on: string;
          submitted_on: string;
          withdraw: RouteAction;
      }
    | { status: 'rejected'; id: string; reason: string; submit: RouteAction };

export type Accreditation = {
    licence: string;
    expires_on: string;
    /** Days until expiry; negative once expired. The server decides standing. */
    days_left: number;
    status: 'active' | 'expired' | 'suspended';
    renewal: AccreditationRenewal;
};

export type ProfileSection = 'accreditation' | 'availability';

export type AuditorProfileProps = {
    server_time: string;
    section: ProfileSection;
    auditor: AuditorIdentity;
    quality_score: number | null;
    on_time_pct: number | null;
    jobs_done: number;
    accreditation: Accreditation;
    availability: AuditorAvailability;
    /** Eligible Flash Audits right now, for the Jobs tab badge. */
    open_jobs: number;
    links: AuditorAppLinks & { sections: Record<ProfileSection, RouteLink> };
};
