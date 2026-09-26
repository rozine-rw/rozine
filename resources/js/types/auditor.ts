import type { AuditDispute } from './audit-dispute';
import type { Money } from './money';
import type { OperationCommand, OperationResource } from './operation';
import type { RouteAction, RouteLink } from './routing';

/**
 * Auditor app page contracts (Phase 1B, crosswalk MVP-AUDITOR-SCR-01…07, auditor-filing-v1 as
 * confirmed on rozine-rw/rozine#96). Every figure, deadline, variance, tolerance and gate is a
 * server fact; the client formats and arranges it and never decides eligibility, money, credit or
 * workflow. No screen asks the partner for a judgement (MVP-AUDITOR-AC-02): the procedure records
 * factual findings, and the engine rates.
 */

/* ------------------------------------------------------------------------------------------ */
/* auditor-filing-v1: identity, scoped actions, commands and operations                         */
/* ------------------------------------------------------------------------------------------ */

/**
 * The commands a page may issue for the current partner, scoped by the server (point 1). A
 * command that is not listed is never offered; the page never infers one from a licence status or
 * an assignment. Record IDs in action links grant nothing on their own.
 */
export type AuditorAllowedAction =
    | 'assignment.accept'
    | 'assignment.decline'
    | 'conflict.declare'
    | 'audit.start'
    | 'audit.save_step'
    | 'audit.seal'
    | 'audit.request_changes'
    | 'audit.reject'
    | 'audit.amend'
    | 'audit.dispute.uphold'
    | 'accreditation.submit'
    | 'accreditation.renew'
    | 'accreditation.withdraw'
    | 'availability.update';

/** What every Auditor page carries (point 1). */
export type AuditorPageContract = {
    contract_version: 'auditor-filing-v1';
    /** The identity context every command sends back unchanged. */
    identity_context_revision: number;
    /** When the server rendered these facts, ISO 8601; every countdown anchors to it. */
    server_time: string;
    allowed_actions: AuditorAllowedAction[];
};

/**
 * A reason the server lists and labels: the page shows `label` as sent and never decides which
 * code needs words — `requires_explanation` says so.
 */
export type ServerOption<Code extends string = string> = {
    code: Code;
    label: string;
    requires_explanation: boolean;
};

/** Why a partner declines an offered file (point 3). */
export type DeclineReason = 'unavailable' | 'capacity' | 'location' | 'other';

/** Why a monthly filing goes back to the business (confirmation on #96). */
export type RequestChangesReason =
    | 'missing_originals'
    | 'reconciliation_difference'
    | 'classification_unresolved'
    | 'debt_evidence_missing'
    | 'capture_unverified'
    | 'other';

/** Why a filing version cannot be verified. It concerns that filing, never creditworthiness. */
export type RejectReason =
    | 'evidence_unverifiable'
    | 'procedure_incomplete'
    | 'other';

/**
 * One piece of evidence as the server identifies it (point 4). A fact the capture could not supply
 * is null and reads "Unavailable". The server alone sets `source` and `device_attestation`; online
 * Alpha fixtures carry `unavailable` and do not pass the D-04 physical-device gate.
 * `isolated_synthetic` is synthetic test evidence from an isolated environment: never a native
 * capture or proof, and its attestation is always `unavailable`.
 */
export type EvidenceItem = {
    evidence_id: string;
    kind: 'photo' | 'check_in' | 'ledger' | 'statement' | 'licence_certificate';
    sha256: string;
    captured_at: string | null;
    source: 'companion_device' | 'web_upload' | 'isolated_synthetic';
    device_attestation: 'verified' | 'unverified' | 'unavailable';
    /** Formatted coordinates: "-1.9441, 30.0619". */
    position: string | null;
    accuracy_m: number | null;
};

/**
 * Where an assignment stands after a conflict: never who has it next (confirmation on #96).
 * `closed` means Audit Operations closed the assignment; it is never described as reassigned.
 */
export type ConflictAssignmentStatus =
    | 'reassignment_pending'
    | 'reassigned'
    | 'recorded'
    | 'closed';

/**
 * The partner's own receipt for a declared conflict. A blocking conflict removes access to the
 * file, evidence, step saves and sealing at once; only this receipt and the minimal status remain.
 * The receipt shows the declarant's own kind, note, date and coarse status, and nothing else: no
 * replacement identity and no private case detail read after the conflict.
 */
export type ConflictReceipt = {
    conflict_id: string;
    kind: ConflictKind;
    declared_at: string;
    /** The factual explanation the partner gave. */
    note: string;
    blocking: boolean;
    status: ConflictAssignmentStatus;
};

/** The reference to the record a command targets and the revision it was read at. */
export type RecordRef = {
    id: string;
    revision: number;
};

/** The operation lookup's command names: each command is named after the action that scopes it. */
export type AuditorCommandName = AuditorAllowedAction;

/**
 * A command exactly as sent. Every payload carries one `expected_revision` for its target
 * aggregate, the page's `identity_context_revision` and its own `request_id` (point 1). A seal's
 * payload carries the step-up proof, never the authenticator code.
 */
export type AuditorCommand = OperationCommand<AuditorCommandName> & {
    /** Where it goes: the record's own action route. */
    route: RouteAction;
    /** The business the command concerns, for the result card. */
    business: string;
    /**
     * The operation lookup this command recovers through, when it is not the page's own: a report
     * start is looked up at `links.start_operation`, never the assignment's lookup.
     */
    lookup?: RouteLink;
};

/** The record a sealed report returns (point 6). Algorithm and key custody are the server's. */
export type SealedRecord = {
    sealed_at: string;
    digest: string;
    licence: string;
    signature_ref: string;
    /** Opaque identifiers, shown as sent. */
    report_id: string;
    key_id: string;
};

/**
 * The authorized updated record(s) a completed command returns, and the page to continue to
 * (point 7). Which fields are present depends on the operation's `code`.
 */
export type AuditorOperationData = {
    next: RouteLink;
    /** `CONFLICT_RECORDED`. */
    conflict?: ConflictReceipt;
    outcome?: { resolution: ConflictAssignmentStatus };
    /** `AUDIT_SEALED`. */
    sealed?: SealedRecord;
};

/**
 * The shared operation Resource. Completed codes: `ACCREDITATION_SUBMITTED`,
 * `ACCREDITATION_WITHDRAWN`, `ASSIGNMENT_ACCEPTED`, `ASSIGNMENT_DECLINED`, `CONFLICT_RECORDED`,
 * `AUDIT_STEP_SAVED`, `AUDIT_SEALED`, `AUDIT_CHANGES_REQUESTED`, `AUDIT_REJECTED`,
 * `AUDIT_AMENDMENT_CREATED`, `REPORT_DISPUTE_ESCALATED` (an upheld dispute, now with Rozine
 * staff); a denial keeps its own code.
 */
export type AuditorOperationResource = OperationResource<AuditorOperationData>;

/** What the seal's step-up returns: an opaque, single-use proof that expires (point 2). */
export type StepUpProof = {
    proof: string;
    expires_at: string;
};

/**
 * Supplied only by local/testing synthetic fixture previews: seeds a state the page otherwise
 * reaches only after a live command, so it can be reviewed. The server never sends it.
 * - `unconfirmed` / `not_recorded` — a command whose answer was lost, before and after the lookup;
 * - `refused` — a definitive refusal, such as `DIGEST_STALE`;
 * - `step_up` — the seal's authenticator entry, fresh or after a wrong code, an expired proof or
 *   throttling (`retry_after` seconds);
 * - `sheet` — a reason sheet opened with a reason chosen.
 */
export type AuditorPreviewOutcome =
    | { kind: 'unconfirmed'; command: AuditorCommand }
    | { kind: 'not_recorded'; command: AuditorCommand }
    | { kind: 'refused'; code: string; status: number }
    | {
          kind: 'step_up';
          state: 'required' | 'wrong_code' | 'expired' | 'throttled';
          retry_after?: number;
      }
    | {
          kind: 'sheet';
          sheet: 'decline' | 'request_changes' | 'reject';
          reason: string | null;
      };

/* ------------------------------------------------------------------------------------------ */
/* Shell                                                                                        */
/* ------------------------------------------------------------------------------------------ */

/**
 * Where the Auditor shell's tabs and launcher link go. A tab whose route the server has not
 * published yet is null, and the shell leaves it out rather than linking to nothing.
 */
export type AuditorAppLinks = {
    home: RouteLink;
    jobs: RouteLink | null;
    portfolio: RouteLink | null;
    profile: RouteLink;
    launcher: RouteLink;
    /** The partner's own conflict receipts (GET `/auditor/conflicts`), where the server sends it. */
    conflicts?: RouteLink | null;
};

/**
 * Whether this partner may take new work under the engagement terms, as Jobs, the file page (and
 * the Jobs beneath it), Profile and the Auditor role home carry it (#96): `current` — the current terms are
 * accepted; `required` — current terms are available but not yet accepted; `unavailable` — no
 * usable terms are published. `link` is the agreement page. It is informational only: every
 * control still comes from its record's own `allowed_actions`, never from this status.
 */
export type EngagementSummary = {
    status: 'current' | 'required' | 'unavailable';
    link: RouteLink;
};

/**
 * The engagement summary on a page that carries it. The live Auditor pages always send
 * `{status, link}`; null is the synthetic fixtures' placeholder, and the page then shows nothing.
 */
export type EngagementSummaryProp = {
    engagement: EngagementSummary | null;
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
    /**
     * The partner's saved preference, not whether work is arriving: dispatch also needs
     * `standing.current`.
     */
    accepting: boolean;
    radius_km: number;
    max_active: number;
    /** The availability record's revision, sent back as `expected_revision`. */
    revision: number;
    /** Where `availability.update` goes; `allowed_actions` decides whether it is offered. */
    update: RouteAction;
};

/** Why a partner's standing does not let dispatch offer them work right now. */
export type StandingReason =
    | 'ACCREDITATION_REQUIRED'
    | 'ACCREDITATION_EXPIRED'
    | 'ACCREDITATION_SUSPENDED'
    | 'STANDING_CHECK_REQUIRED';

/**
 * Whether dispatch may offer this partner work right now, evaluated fresh by the server from the
 * persisted record (#96). It is separate from `availability.accepting`, the partner's saved
 * preference: work arrives only when both hold. The standing-check cadence is server policy.
 */
export type DispatchStanding =
    | { current: true; reason: null }
    | { current: false; reason: StandingReason };

export type AuditorStanding = DispatchStanding & {
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

/**
 * Accepted work still running its clock. `deadline.due_at` is the offer's `complete_by` for a flash
 * audit — 24 hours from dispatch — and the server's `visit_by` for a monthly one; a reassigned job
 * keeps its original deadline. A monthly report's seal and co-sign date (the 7th) is separate: it
 * comes from the report calendar, not from this clock.
 */
export type AssignedJob = {
    id: string;
    /** The assignment's revision, sent back as `expected_revision` by its commands. */
    revision: number;
    /** Accepted work; the server splits each Jobs page on this. */
    state: 'assigned';
    kind: 'flash' | 'monthly';
    business: string;
    district: string;
    /**
     * Distance from the registered office, already formatted by the server: "7.7". Null when it
     * cannot be stated; the card then gives the district alone.
     */
    distance_km: string | null;
    deadline: Deadline;
    /** Accepted: no offer window is left. */
    accept_by: null;
    /** The flash deadline from the original dispatch; null for monthly. */
    complete_by: string | null;
    /**
     * Where the procedure stands. Both are null until the procedure publishes its steps (S-D);
     * the card then shows no step progress rather than a stand-in "Step 0 of 0".
     */
    step: number | null;
    steps: number | null;
    /** Server-sent: `in_progress` or `overdue` against `deadline`, `awaiting_cosign` from S-D. */
    status: AssignedJobStatus;
    /**
     * Set when the job came to this partner from another; the original deadline still runs. The
     * page only says the job was reassigned and never shows this value: no other partner is named.
     */
    reassigned_from: string | null;
    /**
     * Opens the job's file summary (GET `/auditor/jobs/{assignment}`), never the procedure itself;
     * this record's commands are scoped by its own list.
     */
    link: RouteLink;
    allowed_actions: AuditorAllowedAction[];
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

/**
 * Auditor Home as designed. The live Auditor landing page is still `identity/role-home`, which
 * carries the engagement summary; this synthetic page may carry one too.
 */
export type AuditorHomeProps = AuditorPageContract &
    Partial<EngagementSummaryProp> & {
        auditor: AuditorIdentity;
        /** The published partner-quality score out of 100; null until the quality policy is live. */
        quality_score: number | null;
        /** Accrued service-fee share this month (C-23), or null before anything accrues. */
        earned_this_month: Money | null;
        active_deals: number;
        /** Null when no licence is on record or the fact is unavailable; never a stand-in date. */
        licence_expires_on: string | null;
        availability: AuditorAvailability;
        /** Eligible Flash Audits right now and the closest one's distance. */
        nearby: { count: number; closest_km: string | null };
        in_progress: AssignedJob[];
        standing: AuditorStanding;
        activity: AuditorActivity[];
        /** The wallet balance, or null when the server cannot state it; never a stand-in amount. */
        wallet: { available: Money | null };
        unread_notifications: number;
        /** Each destination is null until it exists for this partner; its control is then hidden. */
        links: AuditorAppLinks &
            OperationLookupLinks & {
                statement: RouteLink | null;
                withdraw: RouteLink | null;
                notifications: RouteLink | null;
            };
        preview_outcome?: AuditorPreviewOutcome;
    };

/* ------------------------------------------------------------------------------------------ */
/* Jobs (MVP-AUDITOR-SCR-01, design L205–323)                                                   */
/* ------------------------------------------------------------------------------------------ */

/**
 * Where an offered or assigned file's commands go. Whether each is offered is `allowed_actions`'
 * call alone; a conflict stays declarable during an accepted procedure (AC-08).
 */
export type JobActions = {
    accept: RouteAction;
    decline: RouteAction;
    conflict: RouteAction;
};

/**
 * A Flash Audit or monthly visit this partner is eligible for, offered by dispatch. A persisted
 * draft can lack any of its figures: each missing one is null and reads "—" or "Unavailable",
 * never 0.
 */
export type EligibleJob = {
    /** The assignment offer's ID; its commands send `revision` as `expected_revision`. */
    id: string;
    revision: number;
    /** An open offer; the server splits each Jobs page on this. */
    state: 'offered';
    /** A Flash Audit, or a monthly (routine) visit whose deadline the report calendar owns. */
    kind: 'flash' | 'monthly';
    business: string;
    /** Null when the Business industry matches no sector. */
    sector: SectorCode | null;
    district: string;
    offered_at: string;
    /** Distance from the registered office, formatted: "11.2". Null when it cannot be stated. */
    distance_km: string | null;
    /** Null while the draft carries no amount. */
    requested: Money | null;
    /** The engine's DSCR for the case, formatted: "1.12". Null until an evaluation is published. */
    dscr: string | null;
    /** Null while the draft carries no term. */
    term_months: number | null;
    /**
     * Where to draw the pin on the radius map, relative to the registered office: approximate,
     * server-rounded to 0.1 km for presentation only. Dispatch never uses these offsets. Null until
     * a truthful relative position exists: the map then draws no pin for this job, never one at
     * the centre.
     */
    map: { east_km: number; north_km: number } | null;
    /** When this offer closes (ISO): an hour for a flash audit, four for monthly, never past `complete_by`. */
    accept_by: string;
    /**
     * When a flash audit is due (ISO): 24 hours from its original dispatch, whoever accepts it and
     * however often it is reoffered. Null for a monthly offer — the monthly calendar owns that
     * deadline (inputs by the 3rd, report and co-signatures by the 7th).
     */
    complete_by: string | null;
    /** An offer has no running deadline of its own yet: always null. */
    deadline: null;
    /** Offers are never shown as reassigned: always null. */
    reassigned_from: null;
    link: RouteLink;
    actions: JobActions;
    /**
     * What the partner may do with this offer (#96 point 3): deadlines, capacity and conflicts
     * differ per record, so each card is gated by its own list, not the page's.
     */
    allowed_actions: AuditorAllowedAction[];
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
          /** Where the assignment stands; the next partner is never named. */
          resolution: ConflictAssignmentStatus;
          /** Whether the conflict stopped this partner's work on the file. */
          blocking: boolean;
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

/** The operation lookup: its url holds the literal `{request_id}` token (point 7). */
export type OperationLookupLinks = { operation: RouteLink };

/**
 * A bounded page, newest assignment first (default 25, at most 50). `next` asks for the page
 * before it (`?before=`); null means there is nothing further back. A page can be empty and still
 * carry `next` — records found for it may have lost this partner's authority while being read — so
 * the page keeps offering "Show more" whenever `next` is set, and never treats an empty page as the
 * end of the history. Counts on a page are that page's, never global totals.
 */
export type AuditorPagination = {
    next: RouteLink | null;
};

/**
 * Jobs (GET `/auditor/jobs`, `auditor.jobs.index`). Offers' and assigned jobs' commands POST to
 * `/auditor/jobs/{assignment}/accept`, `/decline` and `/conflict`, and the lookup is GET
 * `/auditor/assignment-operations/{request_id}?command=`; the page follows the links it is sent.
 */
export type AuditorJobsProps = AuditorPageContract &
    EngagementSummaryProp & {
        radius_km: number;
        flash_hours: number;
        /**
         * Offers to this partner and their accepted work: the server splits one bounded page into
         * these two lists, and `pagination` covers the page as a whole.
         */
        eligible: EligibleJob[];
        assigned: AssignedJob[];
        /**
         * The monthly windows and report cards (S-D and the report calendar). Null until they are
         * served: the page then leaves the Monthly section out rather than reading "no reports".
         */
        monthly: {
            windows: MonthlyWindow[];
            reports: MonthlyReportCard[];
        } | null;
        /**
         * The server's labelled reasons for declining an offer. The page shows each `label` as sent and
         * relies on no code of its own.
         */
        decline_options: ServerOption<DeclineReason>[];
        /**
         * The page as a whole (GET `/auditor/jobs?before=`). Live pages always send it, so their counts
         * read as this page's, never as totals, even when `next` is null; only a synthetic preview
         * without it presents its offers as the complete list.
         */
        pagination?: AuditorPagination;
        outcome: AuditorOutcome | null;
        links: AuditorAppLinks & OperationLookupLinks;
        preview_outcome?: AuditorPreviewOutcome;
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

/**
 * What the file shows. Nothing is invented: a raise figure the draft lacks is null and reads "—",
 * and a list the server cannot yet state truthfully is empty.
 */
export type BusinessFile = {
    raise: {
        requested: Money | null;
        term_months: number | null;
        /** Flat total return over the whole term, one decimal: "10.0". Not an APR. */
        return_pct: string | null;
        /** Null when the Business industry matches no sector. */
        sector: SectorCode | null;
        use_of_funds: string;
    };
    /** What the business submitted; empty when no document assertion is on record. */
    documents: FileDocument[];
    /**
     * The engine's automated checks, read-only: a factual met or flagged per rule. Empty until a
     * pre-screen result is published.
     */
    prescreen: { label: string; detail: string; result: 'met' | 'flag' }[];
    /** Why the engine sent this case to the field; null until a public reason is persisted. */
    reason: string | null;
    /** The procedure version's agreed steps for the visit, in order; empty until published (S-D). */
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
    /** The assignment's ID; its commands send `revision` as `expected_revision`. */
    id: string;
    revision: number;
    /** A Flash Audit or a monthly (routine) visit. */
    kind: 'flash' | 'monthly';
    business: string;
    district: string;
    /** Formatted: "11.2". Null when it cannot be stated; the header then gives the district alone. */
    distance_km: string | null;
    /**
     * Offered or assigned. A flash audit's clock runs from its original dispatch either way;
     * accepting does not start it.
     */
    state: 'offered' | 'assigned';
    /**
     * Once assigned: `due_at` is `complete_by` for a flash audit and `visit_by` for a routine one.
     */
    deadline: Deadline | null;
    /** While offered: when the offer closes. Null once assigned, which is what `state` also says. */
    accept_by: string | null;
    /** The flash deadline from dispatch; null for monthly. */
    complete_by: string | null;
    /** Set when the file was reassigned to this partner; never shown, so no partner is named. */
    reassigned_from: string | null;
    /** This record's own actions; the page's `allowed_actions` repeats them. */
    allowed_actions: AuditorAllowedAction[];
};

/**
 * The file summary (GET `/auditor/jobs/{assignment}`, `auditor.jobs.show`). After a blocking
 * conflict the full file route denies access, and the partner's own receipt is what remains.
 */
export type AuditorFileProps = AuditorPageContract &
    EngagementSummaryProp & {
        job: FileJob;
        actions: JobActions & {
            /**
             * `audit.start` (#96): present once assigned and while no report exists, null
             * otherwise. Starting sends the assignment revision with the `application` pins and
             * follows `data.next`; the file GET creates nothing, and once a report exists
             * `links.procedure` resumes it instead.
             */
            start: RouteAction | null;
        };
        /**
         * The exact application ID and revision a started report binds, sent with `actions.start`.
         * Null when no application exists: the file then says there is nothing to start.
         */
        application: RecordRef | null;
        decline_options: ServerOption<DeclineReason>[];
        /** `operation` recovers assignment and conflict commands; a start has its own lookup. */
        links: OperationLookupLinks & {
            close: RouteLink;
            /** The started report's procedure; null until a report exists. */
            procedure: RouteLink | null;
            /** The report start's operation lookup, with the literal `{request_id}` token. */
            start_operation: RouteLink;
        };
        /** Jobs, drawn beneath the sheet on a wide screen. */
        jobs: AuditorJobsProps;
        preview_outcome?: AuditorPreviewOutcome;
    } & (
        | { file: BusinessFile; blocked: null }
        /** A blocking conflict: the file is withheld and only the partner's receipt remains. */
        | { file: null; blocked: ConflictReceipt }
    );

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
    /**
     * Opens the capture companion on this assignment, as the server supplies it. Null while no
     * companion handoff exists: the page says capture is unavailable and offers no web capture.
     */
    handoff: { url: string } | null;
    /**
     * Where the package comes from. `isolated_synthetic` is synthetic test capture from an isolated
     * environment and is labelled so, never as native proof; absent reads as the companion.
     */
    source?: 'isolated_synthetic' | 'companion_device';
};

export type CheckInStage = {
    step: 'check_in';
    package: CapturePackage;
    check_in:
        | { state: 'pending' }
        | {
              state: 'recorded';
              at: string;
              /** The check-in's evidence; a position the capture could not supply is null. */
              evidence: EvidenceItem;
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
    /**
     * The ingestion outcome: a received document is never an approval or a seal (point 4). Null
     * before the server has taken it in.
     */
    ingestion: 'INGESTED_NOT_AUDIT_APPROVED' | null;
    evidence: EvidenceItem | null;
    /**
     * The retained original's protected download, opened as an ordinary link. Null or absent
     * while the server offers none: the card then shows no download.
     */
    link?: RouteLink | null;
};

export type LedgerStage = {
    step: 'ledger';
    /**
     * Where a ledger original is uploaded: present only on the current, editable Flash ledger step
     * while the command is allowed; null for a previous-step read, a terminal report or a
     * read-only token, which then offers no upload at all.
     */
    upload: RouteAction | null;
    /**
     * The stock value the business declared. Null when no Business stock declaration exists (the
     * application and statement facts do not supply one, #96 S-D): it reads "Not declared", never
     * zero, and reconciliation stays blocked.
     */
    reported_stock: Money | null;
    observed_stock: Money | null;
    /** The policy tolerance, as the server words it: "RWF 0". */
    tolerance: string;
    variance: Variance | null;
    documents: LedgerDocument[];
    /** A document has parsed, so the reconciliation fact can be recorded. */
    ledger_ready: boolean;
    reconciled: boolean;
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
              /**
               * The factual cover, `inflow / (outflow + debt_service)` to two decimals: "1.84".
               * Null when the denominator is zero, and it reads "Unavailable". No monitoring
               * thresholds are approved, so there is no band and the figure is shown neutrally.
               */
              cover: { value: string; band: null } | null;
              /**
               * Every original document behind the month, each a protected download (web or API)
               * opened as an ordinary link. None stands in for the others.
               */
              documents: { name: string; link: RouteLink }[];
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
    /** The statement period; null for a retained record with no pinned period. */
    period: { from: string; to: string } | null;
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
    /** Factual findings from the server's finding codes, each citing its evidence. */
    findings: {
        code: string;
        no: string;
        title: string;
        body: string;
        evidence_ids: string[];
    }[];
    /** Every piece of evidence the seal covers. */
    evidence: EvidenceItem[];
    /** The versions this preview pins; a change needs a new preview and confirmation. */
    procedure_version: string;
    findings_version: string;
    evidence_version: string;
    /** The digest of the package that will be sealed. */
    digest: string;
    licence: string;
    /**
     * The fresh-user confirmation the seal needs: the partner's confirmed authenticator. When it
     * is not confirmed, the existing security settings are where to set it up.
     */
    mfa: { confirmed: boolean; settings: RouteLink };
    /** Monthly filings only: the server's labelled reasons to request changes or reject. */
    reason_options: ReasonOptions | null;
};

/** The server's labelled reasons for returning a monthly filing. */
export type ReasonOptions = {
    request_changes: ServerOption<RequestChangesReason>[];
    reject: ServerOption<RejectReason>[];
};

/**
 * A monthly filing returned to the business or rejected (delivery 2, #96): an immutable terminal
 * report version holding its last draft and the factual decision. Nothing can be saved, sealed or
 * stepped up; the partner may start one linked amendment, or open the one already created.
 */
export type ReturnedStage = {
    step: 'returned';
    status: 'changes_requested' | 'rejected';
    /** The retained reason, as the server labels it, with the partner's factual explanation. */
    reason: {
        code: RequestChangesReason | RejectReason;
        label: string;
        explanation: string;
    };
    recorded_at: string;
    /** The linked amendment, once one exists; this report stays as it is. */
    amended_by: { report_id: string; link: RouteLink } | null;
};

export type CosignState = 'pending' | 'signed' | 'declined' | 'overdue';

export type SealedStage = SealedRecord & {
    step: 'sealed';
    cosign: {
        party: string;
        state: CosignState;
        /**
         * A monthly report and its co-signature are due by the 7th; the server sets the date.
         * Null for a Flash Audit, which has no monthly deadline: no date is shown.
         */
        due_on: string | null;
        signed_at: string | null;
    };
    published_at: string | null;
    /**
     * Whether the seal verifies now. `unavailable` (e.g. its signing key was revoked) keeps the
     * sealed record, signature, digest and history as they are, and says the seal cannot be
     * verified rather than implying it is currently valid.
     */
    seal_status: 'valid' | 'unavailable';
    /** A linked amendment of this report, if one exists; this report stays unchanged. */
    amended_by: { report_id: string; link: RouteLink } | null;
    /**
     * The public seal check for this report, as the Business co-sign page's `seal.verification`
     * links it. Optional until the server sends it on the sealed stage; no link shows without it.
     */
    verification?: RouteLink | null;
    /**
     * The Business's dispute of this report (N6), for the assigned CPA: its status, the proof as
     * sent and the outcome. The CPA reviews it first and either starts a linked amendment or
     * upholds the findings, which escalates it to Rozine staff and never publishes. Null when the
     * report is not disputed.
     */
    dispute: AuditDispute | null;
};

/** A blocking conflict: work stopped, only the partner's receipt shown. */
export type BlockedStage = {
    step: 'blocked';
    conflict: ConflictReceipt;
};

export type AuditStage =
    | ReviewStage
    | CheckInStage
    | PhotosStage
    | LedgerStage
    | StatementsStage
    | CountStage
    | SealStage
    | SealedStage
    | ReturnedStage
    | BlockedStage;

export type AuditProcedureProps = AuditorPageContract & {
    audit: {
        /** The audit report's ID; its commands send `revision` as `expected_revision`. */
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
        /** Set on a linked amendment: the published report it amends, which stays unchanged. */
        amends: { report_id: string; link: RouteLink } | null;
    };
    /** The assignment a conflict declaration targets. */
    assignment: RecordRef;
    steps: { key: AuditStepKey; state: 'done' | 'current' | 'todo' }[];
    stage: AuditStage;
    /** The server's gate: may this step be committed and the next opened? */
    can_continue: boolean;
    /** What still blocks the step, or what sealing will do, as the server words it. */
    hint: string | null;
    /**
     * The labelled reasons for returning a monthly draft at any step (delivery 2, #96), the same
     * shape as the seal stage's; null when unavailable — a Flash Audit or a terminal report.
     */
    reason_options: ReasonOptions | null;
    links: OperationLookupLinks & { close: RouteLink; back: RouteLink | null };
    actions: {
        save: RouteAction;
        conflict: RouteAction;
        /**
         * The commands below are null while the delivered stage does not enable them (they are
         * then also absent from `allowed_actions`): the page offers no button for a null one.
         */
        /** Returns a `StepUpProof` for the authenticator code; not an operation. */
        step_up: RouteAction | null;
        seal: RouteAction | null;
        request_changes: RouteAction | null;
        reject: RouteAction | null;
        amend: RouteAction | null;
        /**
         * Upholds the findings against the Business's dispute (`audit.dispute.uphold`): the
         * dispute escalates to Rozine staff with the CPA's reason, and nothing is published.
         */
        dispute_uphold: RouteAction | null;
    };
    outcome: AuditorOutcome | null;
    /** Jobs, drawn beneath the sheet on a wide screen. */
    jobs: AuditorJobsProps;
    preview_outcome?: AuditorPreviewOutcome;
    /**
     * Local/testing previews only: opens the conflict sheet over whatever `preview_outcome`
     * seeds, so a declaration during another command can be reviewed.
     */
    preview_conflict_open?: boolean;
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
    conflict_id: string;
    business: string;
    note_id: string;
    kind: ConflictKind;
    declared_on: string;
};

/** An assigned file a conflict can be declared on; the declaration targets its `revision`. */
export type AssignedFile = {
    id: string;
    revision: number;
    business: string;
    note_id: string;
    /** Whether a conflict can be declared on this file is this record's own call. */
    allowed_actions: AuditorAllowedAction[];
};

export type AuditorPortfolioProps = AuditorPageContract & {
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
    links: AuditorAppLinks & OperationLookupLinks;
    preview_outcome?: AuditorPreviewOutcome;
};

/* ------------------------------------------------------------------------------------------ */
/* Conflicts: the partner's own conflict receipts (AC-08, S-C)                                  */
/* ------------------------------------------------------------------------------------------ */

/**
 * One of the partner's own declarations. The private read carries no Business display name, no
 * note ID, no case detail and no replacement identity: only the assignment and Business it
 * concerns, by ID, and the declarant's own receipt. The page names it "Business on record" with a
 * short form of `assignment_id`.
 */
export type OwnConflict = {
    assignment_id: string;
    business_id: string;
    /** Always blocking; its status is coarse, including `closed` by Audit Operations. */
    conflict: ConflictReceipt;
};

/**
 * The partner's own conflict receipts (GET `/auditor/conflicts`, `auditor.conflicts.index`), a
 * bounded page with its own `pagination`. The single receipt (GET `/auditor/jobs/{assignment}/conflict`,
 * `auditor.conflicts.show`) is the same shape with one entry, and is where a completed
 * declaration's `data.next` leads. It is read-only: no command is offered here.
 */
export type AuditorConflictsProps = AuditorPageContract & {
    conflicts: OwnConflict[];
    links: AuditorAppLinks & OperationLookupLinks;
    pagination: AuditorPagination;
};

/* ------------------------------------------------------------------------------------------ */
/* Profile: accreditation and availability (design L593–1013)                                  */
/* ------------------------------------------------------------------------------------------ */

/**
 * The partner's latest accreditation submission, first-time or renewal. A pending submission
 * keeps the identity of the certificate it uploaded (point 3).
 */
export type AccreditationSubmission =
    | { status: 'none' }
    | {
          status: 'pending';
          id: string;
          licence: string;
          expires_on: string;
          submitted_on: string;
          evidence: EvidenceItem;
      }
    | { status: 'rejected'; id: string; reason: string };

/**
 * The server's record of the partner's accreditation. `none` is a first-time partner with no
 * licence on record: a submission confers no standing until an authorized staff member records
 * the ICPAR check and its dates.
 */
export type Accreditation =
    | {
          status: 'none';
          revision: number;
          licence: null;
          expires_on: null;
          days_left: null;
          submission: AccreditationSubmission;
      }
    | {
          status: 'active' | 'expired' | 'suspended';
          revision: number;
          licence: string;
          expires_on: string;
          /** Days until expiry; negative once expired. The server decides standing. */
          days_left: number;
          submission: AccreditationSubmission;
      };

export type ProfileSection = 'accreditation' | 'availability';

/**
 * The partner as the Profile shows them. A fact Rozine holds no record of yet — the firm, the
 * professional body and designation, the year they started — is null and is left out.
 */
export type AuditorProfileIdentity = Pick<
    AuditorIdentity,
    'name' | 'avatar_url'
> & {
    firm: string | null;
    accreditation: string | null;
    since_year: number | null;
};

/** Where the partner's own certificates are read: the one on record and the one under review. */
export type AccreditationCertificateLinks = {
    certificate: RouteLink | null;
    submitted_certificate: RouteLink | null;
};

export type AccreditationActions = {
    submit: RouteAction;
    renew: RouteAction;
    withdraw: RouteAction;
};

export type AuditorProfileProps = AuditorPageContract &
    EngagementSummaryProp & {
        section: ProfileSection;
        auditor: AuditorProfileIdentity;
        quality_score: number | null;
        on_time_pct: number | null;
        jobs_done: number;
        accreditation: Accreditation;
        /** Whether dispatch may offer work now; read with `availability.accepting`. */
        standing: DispatchStanding;
        availability: AuditorAvailability;
        /**
         * Where the accreditation commands go: a first-time submission, a renewal and a withdrawal.
         * `allowed_actions` decides which are offered.
         */
        actions: AccreditationActions;
        /** Eligible Flash Audits right now, for the Jobs tab badge. */
        open_jobs: number;
        links: AuditorAppLinks &
            OperationLookupLinks &
            AccreditationCertificateLinks & {
                sections: Record<ProfileSection, RouteLink>;
            };
        preview_outcome?: AuditorPreviewOutcome;
    };

/* ------------------------------------------------------------------------------------------ */
/* Engagement terms: the platform MSA and agreed procedures (auditor-engagement-v1, #96)        */
/* ------------------------------------------------------------------------------------------ */

/** One retained document: its title and complete original text, with the body's SHA-256. */
export type EngagementDocument = {
    title: string;
    body: string;
    sha256: string;
};

/**
 * The current release of the terms. `revision` is what an acceptance sends back as
 * `expected_revision`; `sha256` covers the release as a whole. `synthetic` marks isolated test
 * terms that stand for no real engagement.
 */
export type EngagementRelease = {
    id: string;
    revision: number;
    version: string;
    procedure_version: string;
    documents: {
        master_services: EngagementDocument;
        agreed_procedures: EngagementDocument;
    };
    sha256: string;
    synthetic: boolean;
};

/** This partner's retained acceptance of the current release, as its receipt records it. */
export type EngagementAcceptance = {
    id: string;
    release_id: string;
    release_revision: number;
    release_sha256: string;
    accepted_at: string;
    sha256: string;
};

export type EngagementAllowedAction = 'audit.engagement.accept';

/** Accept's body: every field is the release as read, and `accepted` is a strict boolean. */
export type EngagementAcceptPayload = {
    identity_context_revision: number;
    expected_revision: number;
    release_id: string;
    sha256: string;
    accepted: true;
    request_id: string;
};

/** An acceptance exactly as sent, with the route it goes to. */
export type EngagementCommand = OperationCommand<EngagementAllowedAction> & {
    payload: EngagementAcceptPayload;
    route: RouteAction;
};

export type EngagementOperationResource = OperationResource<{
    acceptance: EngagementAcceptance;
}>;

/**
 * Supplied only by synthetic fixture previews: a refusal the page otherwise reaches only after a
 * live acceptance, such as a version conflict. The server never sends it.
 */
export type EngagementPreviewOutcome = {
    kind: 'refused';
    code: string;
    status: number;
};

/**
 * The agreement page (GET `/auditor/engagement`, `auditor.engagement.show`): the current terms in
 * full, the partner's own acceptance, and Accept where `allowed_actions` offers it. Accept POSTs to
 * `actions.accept`; its lookup is `links.operation` with the same `request_id` and no `command`
 * query. After an acceptance the page reads `links.current` afresh.
 */
export type AuditorEngagementProps = {
    contract_version: 'auditor-engagement-v1';
    identity_context_revision: number;
    server_time: string;
    /** Null while no usable terms are published. */
    release: EngagementRelease | null;
    acceptance: EngagementAcceptance | null;
    allowed_actions: EngagementAllowedAction[];
    actions: { accept: RouteAction | null };
    /** Eligible Flash Audits right now, for the Jobs tab badge; the web page sends none. */
    open_jobs: number;
    links: AuditorAppLinks & OperationLookupLinks & { current: RouteLink };
    preview_outcome?: EngagementPreviewOutcome;
};
