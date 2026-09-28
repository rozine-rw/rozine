import type { BusinessRating, NoteStatus } from './business';
import type { Money } from './money';
import type { RouteAction, RouteLink } from './routing';
import type {
    AllocationKind,
    C3PreviewOutcome,
    ComponentAmounts,
    KigaliDate,
    Pagination,
    ProviderOutcome,
    ProviderOutcomeState,
    Receipt,
    ServicingState,
} from './settlement';

/**
 * Admin console page contracts (Phase 1B, crosswalk MVP-ADMIN-SCR-01..09). Every figure, count,
 * percentage, state and permission is a server fact; the console formats and arranges it and never
 * decides anything. `actions` carry only what the server allows this staff member to do right now
 * (contract §4 `allowed_actions`); the server rechecks every command.
 */

/** The console screens built for the MVP. Reconciliation, Book, Exceptions, Partners and Reports follow. */
export type AdminSection =
    | 'today'
    | 'applications'
    | 'disbursements'
    | 'repayments'
    | 'businesses'
    | 'investors'
    | 'auditors'
    | 'staff'
    | 'ledger'
    | 'events';

export type StaffRole = 'analyst' | 'approver' | 'superadmin';

/** The signed-in operator. Privileged staff accounts are separate from Party logins (CFG-01). */
export type StaffViewer = {
    id: string;
    name: string;
    email: string;
    initials: string;
    role: StaffRole;
};

/**
 * Phase 2 screens a server may add to the frame (MVP-ADMIN-SCR-04/05/06/07/10). They are optional
 * nav entries rather than `AdminSection` members, so no existing page has to send them: the sidebar
 * shows one only when its link is sent.
 */
export type AdminOptionalSection =
    | 'book'
    | 'exceptions'
    | 'reconciliation'
    | 'coverage'
    | 'reports';

/** Every section the frame can mark as current. */
export type AdminFrameSection = AdminSection | AdminOptionalSection;

/** What every console page receives for its frame. */
export type AdminShellProps = {
    viewer: StaffViewer;
    nav: Record<AdminSection, RouteLink> &
        Partial<Record<AdminOptionalSection, RouteLink | null>> & {
            launcher: RouteLink;
        };
    /** Live queue sizes for the sidebar badges; zero shows no badge. */
    badges: { applications: number; disbursements: number };
    /** The page-scoped top-bar search, as the server applied it. */
    search: string;
    /** Server clock, RFC3339; relative times count from here, never from the browser. */
    server_time: string;
};

/**
 * The frame as a live staff Resource may send it (#96): only the destinations the server serves
 * and only the queue sizes it knows. A null section hides that sidebar item and a null badge shows
 * none; the launcher is always sent. Every full `AdminShellProps` is also one of these.
 */
export type AdminFrameShellProps = Omit<AdminShellProps, 'nav' | 'badges'> & {
    nav: Record<AdminSection, RouteLink | null> &
        Partial<Record<AdminOptionalSection, RouteLink | null>> & {
            launcher: RouteLink;
        };
    badges: { applications: number | null; disbursements: number | null };
};

export type Rating = BusinessRating;

/** A figure as the server states it; the console only formats it. */
export type StatValue =
    | { kind: 'money'; value: Money }
    | { kind: 'count'; value: number }
    | { kind: 'percent'; value: string }
    | { kind: 'rating'; value: Rating | null }
    | { kind: 'date'; value: string }
    | { kind: 'text'; value: string };

/** Who did a privileged thing, when, and why (MVP-ADMIN-AC-01/AC-08). */
export type Attribution = {
    actor: string;
    at: string;
    reason: string | null;
};

export type Tone = 'blue' | 'green' | 'amber' | 'red' | 'purple' | 'grey';

/** A read-out from the policy registry, shown beside the queue it governs. */
export type PolicyItem = {
    key:
        | 'target_dscr'
        | 'listing_audit'
        | 'audit_routing'
        | 'rate_band'
        | 'min_revenue'
        | 'min_statement_years'
        | 'rdb_certificate'
        | 'max_notes'
        | 'dual_control_threshold';
    value: string;
};

/** One entry in a record's trail: an action taken on it, by whom and when. */
export type TrailEntry = {
    id: string;
    at: string;
    actor: string;
    action: { code: string; label: string; tone: Tone };
    reason: string | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Today (MVP-ADMIN-SCR-01)                                                                    */
/* ------------------------------------------------------------------------------------------ */

export type TodayKpiKey =
    | 'capital_raised'
    | 'active_businesses'
    | 'verified_investors'
    | 'outstanding_notes'
    | 'treasury_position'
    | 'default_rate'
    | 'secondary_volume';

export type TodayKpi =
    | {
          key: Exclude<TodayKpiKey, 'treasury_position' | 'default_rate'>;
          value: StatValue;
      }
    | {
          key: 'treasury_position';
          value: StatValue;
          /** The platform has paid out more than it collected. */
          negative: boolean;
      }
    | {
          key: 'default_rate';
          value: StatValue;
          failed: number;
          total: number;
          /** Above the policy's tolerated rate. */
          breach: boolean;
      };

export type AttentionKey =
    | 'applications_pending'
    | 'kyc_awaiting'
    | 'notes_late'
    | 'notes_default_risk'
    | 'frozen_accounts';

export type AttentionTile = {
    key: AttentionKey;
    count: number;
    /** Null while the screen that works this queue is not built yet. */
    link: RouteLink | null;
};

/** An unexplained reconciliation difference. Breaks are aged and assigned, never hidden (AC-05). */
export type ReconciliationBreak = {
    id: string;
    reference: string;
    description: string;
    gap: Money;
    opened_at: string;
    age_days: number;
    owner: Attribution | null;
    actions: { assign?: RouteAction; escalate?: RouteAction };
};

export type LedgerKind =
    | 'investment'
    | 'disbursement'
    | 'repayment'
    | 'service_fee'
    | 'repayment_fee'
    | 'auditor_share'
    | 'distribution'
    | 'recovery'
    | 'deposit'
    | 'withdrawal'
    | 'secondary'
    | 'secondary_fee'
    | 'contra';

export type ActivityItem = {
    id: string;
    kind: LedgerKind;
    summary: string;
    amount: Money;
    at: string;
    link: RouteLink;
};

export type FunnelStage =
    | 'submitted'
    | 'live'
    | 'funded'
    | 'repaying'
    | 'matured'
    | 'failed';

export type AdminTodayProps = AdminShellProps & {
    kpis: TodayKpi[];
    attention: AttentionTile[];
    breaks: ReconciliationBreak[];
    capital_raised: {
        from: string | null;
        to: string | null;
        grain: 'year' | 'month' | 'day' | 'hour';
        bars: { label: string; amount: Money; height_pct: number }[];
    };
    portfolio_health: {
        total: number;
        healthy: { count: number; pct: number };
        watch: { count: number; pct: number };
        distressed: { count: number; pct: number };
    };
    activity: ActivityItem[];
    funnel: { stage: FunnelStage; count: number; width_pct: number }[];
    pending_applications: {
        id: string;
        business: string;
        requested: Money;
        rating: Rating | null;
        link: RouteLink;
    }[];
    sector_exposure: {
        sector: string;
        outstanding: Money;
        notes: number;
        at_risk: boolean;
    }[];
    treasury: {
        invested: Money;
        disbursed: Money;
        platform_net: Money;
        paid_to_investors: Money;
    };
    collections: {
        in_repayment: number;
        outstanding: Money;
        next_due: Money;
        on_track: number;
        late: number;
        default_risk: number;
    };
};

/* ------------------------------------------------------------------------------------------ */
/* Applications (MVP-ADMIN-SCR-02)                                                             */
/* ------------------------------------------------------------------------------------------ */

export type ApplicationState =
    | 'submitted'
    | 'under_review'
    | 'info_requested'
    | 'escalated'
    | 'approved'
    | 'rejected';

export type ApplicationTab =
    | 'pending'
    | 'under_review'
    | 'escalated'
    | 'approved'
    | 'rejected';

/** The underwriting engine's recommendation. Staff never score or rate (AC-04). */
export type EngineDecision = {
    code: 'approve' | 'reject' | 'audit' | 'review';
    reason: string;
    /**
     * A stable code for the reason, localized in place of `reason` when present, e.g.
     * `CURRENT_RELEASE_REVIEW_REQUIRED`: a retained quote is never shown as current approval.
     */
    reason_code?: string | null;
};

export type ApplicationRow = {
    id: string;
    business: string;
    note_title: string;
    sector: string;
    requested: Money;
    term_months: number;
    /** Flat total return over the term, one decimal: "12.1". Not an APR. */
    rate_pct: string;
    /** Null while the server does not project capacity usage; shown as unavailable, never 0. */
    capacity_used_pct: number | null;
    rating: Rating | null;
    submitted_at: string;
    state: ApplicationState;
    decision: EngineDecision;
    link: RouteLink;
    /** Opens the review with the approval stage ready, when the server allows approving. */
    approve_link: RouteLink | null;
};

export type ReviewAction = 'take' | 'approve' | 'reject' | 'info' | 'escalate';

export type EvidenceFactorKey =
    | 'repayment_history'
    | 'revenue_consistency'
    | 'statement_record'
    | 'capacity_headroom'
    | 'sector_risk';

export type ApplicationReview = {
    id: string;
    business: string;
    note_title: string;
    sector: string;
    state: ApplicationState;
    decision: EngineDecision;
    requested: Money;
    rating: Rating | null;
    term_months: number;
    rate_pct: string;
    /** Only `approved` is always sourced; the others are null until the server projects them. */
    capacity: {
        used_pct: number | null;
        approved: Money;
        active_notes: number | null;
        outstanding: Money | null;
    };
    /** The engine's evidence, each on 0–100. Read-only. */
    factors: { key: EvidenceFactorKey; value: number }[];
    /** Listing audit evidence; anything but sealed blocks approval (SCR-02-ST-02). */
    audit: {
        /** Null when the release gates are the authority; no audit banner is inferred then. */
        state: 'sealed' | 'pending' | 'missing' | null;
        sealed_at: string | null;
    };
    use_of_funds: string;
    submitted_at: string;
    reviewer: string | null;
    trail: TrailEntry[];
    /** `business` is null until a live staff Business page exists. */
    links: { close: RouteLink; business: RouteLink | null };
    actions: Partial<Record<ReviewAction, RouteAction>>;
};

export type AdminApplicationsProps = AdminFrameShellProps & {
    policy: PolicyItem[];
    /** A null count is one the server does not state; the tab shows its label alone. */
    tabs: { key: ApplicationTab; count: number | null; link: RouteLink }[];
    active_tab: ApplicationTab;
    applications: ApplicationRow[];
    review: ApplicationReview | null;
    /** The stage to open the review on, e.g. the row's Approve button. */
    stage: ReviewAction | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Disbursements with maker-checker (MVP-ADMIN-SCR-03, AC-03)                                  */
/* ------------------------------------------------------------------------------------------ */

export type DisbursementState =
    | 'ready'
    | 'awaiting_second_approver'
    | 'on_hold'
    | 'dispatched'
    | 'paid'
    | 'failed';

export type DisbursementRow = {
    id: string;
    reference: string;
    business: string;
    note_title: string;
    /** Masked by the server; raw bank identifiers never reach the console. */
    destination: string;
    amount: Money;
    due_on: string;
    state: DisbursementState;
    link: RouteLink;
};

export type DisbursementDetail = DisbursementRow & {
    /** Releases at or above this need two distinct staff. */
    threshold: Money;
    requires_second_approver: boolean;
    maker: Attribution | null;
    checker: Attribution | null;
    /** True when the viewer is the maker: the server will not let them check their own release. */
    viewer_is_maker: boolean;
    failure: {
        code: string;
        message: string;
        provider_reference: string;
        failed_at: string;
        attempts: number;
    } | null;
    trail: TrailEntry[];
    links: { close: RouteLink };
    actions: {
        authorize?: RouteAction;
        approve?: RouteAction;
        reject?: RouteAction;
        hold?: RouteAction;
        retry?: RouteAction;
    };
};

export type AdminDisbursementsProps = AdminShellProps & {
    policy: PolicyItem[];
    disbursements: DisbursementRow[];
    /** Releases waiting on a second, different approver. */
    awaiting_second_approver: number;
    disbursement: DisbursementDetail | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Parties (MVP-ADMIN-SCR-08)                                                                  */
/* ------------------------------------------------------------------------------------------ */

export type PartyKind = 'business' | 'investor' | 'auditor' | 'staff';

export type KycState = 'verified' | 'pending' | 'overdue' | 'rejected';

export type BusinessPartyRow = {
    id: string;
    name: string;
    sector: string;
    rating: Rating | null;
    active_notes: number;
    investors: number;
    raised: Money;
    capacity_used_pct: number;
    health: 'healthy' | 'watch' | 'distressed';
    frozen: boolean;
    kyc: KycState;
    link: RouteLink;
};

export type InvestorPartyRow = {
    id: string;
    name: string;
    country: string;
    kyc: KycState;
    portfolio: Money;
    wallet: Money;
    holdings: number;
    businesses: number;
    frozen: boolean;
    restricted: boolean;
    link: RouteLink;
};

export type AuditorPartyRow = {
    id: string;
    name: string;
    firm: string;
    licence: string;
    district: string;
    active_engagements: number;
    on_time_pct: number | null;
    share_mtd: Money;
    standing: 'active' | 'pending' | 'licence_expired' | 'suspended';
    frozen: boolean;
    link: RouteLink;
};

export type StaffPartyRow = {
    id: string;
    name: string;
    email: string;
    initials: string;
    role: StaffRole;
    frozen: boolean;
    you: boolean;
    link: RouteLink;
};

export type PartyDirectory =
    | { kind: 'business'; rows: BusinessPartyRow[] }
    | { kind: 'investor'; rows: InvestorPartyRow[] }
    | { kind: 'auditor'; rows: AuditorPartyRow[] }
    | { kind: 'staff'; rows: StaffPartyRow[] };

export type PartyStatKey =
    | 'active_notes'
    | 'investors'
    | 'raised'
    | 'rating'
    | 'capacity'
    | 'capacity_used'
    | 'portfolio'
    | 'wallet'
    | 'holdings'
    | 'businesses'
    | 'kyc_due'
    | 'engagements'
    | 'on_time'
    | 'share_mtd'
    | 'role'
    | 'last_sign_in';

export type PartyListRow = {
    id: string;
    title: string;
    tone: Tone;
    detail:
        | { kind: 'note_status'; value: NoteStatus }
        | { kind: 'money'; value: Money }
        | { kind: 'text'; value: string };
};

export type PartyDetail = {
    id: string;
    kind: PartyKind;
    name: string;
    subtitle: string;
    health: 'active' | 'healthy' | 'watch' | 'distressed' | 'kyc_pending';
    stats: { key: PartyStatKey; value: StatValue }[];
    list: {
        key: 'active_notes' | 'holdings' | 'engagements';
        rows: PartyListRow[];
    } | null;
    history: TrailEntry[];
    kyc: { state: KycState; due_on: string | null } | null;
    licence: {
        member_id: string;
        licence: string;
        expires_on: string;
        district: string;
        state: 'verified' | 'pending' | 'expired';
    } | null;
    /** The current restriction, attributed; null when the account is open. */
    freeze: Attribution | null;
    /** Why a release cannot happen here (e.g. a legal hold), or null. */
    release_blocked: 'LEGAL_HOLD' | null;
    /** Every freeze and release on this account, newest first. */
    restrictions: (TrailEntry & { kind: 'freeze' | 'release' })[];
    links: { close: RouteLink };
    actions: {
        freeze?: RouteAction;
        release?: RouteAction;
        verify_kyc?: RouteAction;
        reject_kyc?: RouteAction;
        verify_licence?: RouteAction;
        reject_licence?: RouteAction;
    };
};

export type DirectoryChipKey =
    | 'all'
    | 'healthy'
    | 'watch'
    | 'distressed'
    | 'frozen'
    | 'verified'
    | 'pending'
    | 'kyc_overdue'
    | 'restricted'
    | 'active'
    | 'licence_expired'
    | 'suspended';

export type DirectoryStatKey =
    | 'total_businesses'
    | 'avg_rating'
    | 'active_notes'
    | 'capital_raised'
    | 'total_investors'
    | 'kyc_verified'
    | 'awaiting_kyc'
    | 'total_aum'
    | 'partners'
    | 'active_partners'
    | 'pending_partners'
    | 'licences_expiring'
    | 'operators'
    | 'approvers'
    | 'frozen_accounts';

export type DirectoryChip = {
    key: DirectoryChipKey;
    count: number;
    link: RouteLink;
    active: boolean;
};

export type DirectoryFilter = {
    key: 'sector' | 'country' | 'sort';
    value: string;
    options: { value: string; label: string }[];
};

export type AdminPartiesProps = AdminShellProps & {
    kind: PartyKind;
    policy: PolicyItem[];
    stats: { key: DirectoryStatKey; value: StatValue }[];
    chips: DirectoryChip[];
    filters: DirectoryFilter[];
    /** Rows shown and rows matching, e.g. "Showing first 60 of 524". */
    shown: number;
    total: number;
    directory: PartyDirectory;
    party: PartyDetail | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Ledger (MVP-ADMIN ledger drill-down) and Event log (MVP-ADMIN-SCR-09)                       */
/* ------------------------------------------------------------------------------------------ */

export type LedgerRow = {
    id: string;
    at: string;
    kind: LedgerKind;
    from: string;
    to: string;
    reference: string;
    amount: Money;
    link: RouteLink;
};

export type Posting = {
    account: string;
    account_code: string;
    debit: Money | null;
    credit: Money | null;
};

export type LedgerEntryDetail = LedgerRow & {
    operation_id: string;
    posted_by: Attribution;
    postings: Posting[];
    totals: { debit: Money; credit: Money };
    /** Debits equal credits, as the core verified on posting. */
    balanced: boolean;
    /** A correction is a contra entry, never an edit (AC-02). */
    contra_of: { id: string; link: RouteLink } | null;
    contra_by: { id: string; link: RouteLink } | null;
    links: { close: RouteLink; events: RouteLink };
};

export type AdminLedgerProps = AdminShellProps & {
    entries: LedgerRow[];
    total: number;
    more: RouteLink | null;
    entry: LedgerEntryDetail | null;
};

export type EventRow = {
    id: string;
    at: string;
    actor: string;
    action: { code: string; label: string; tone: Tone };
    target: string;
    source: string;
    reason: string | null;
    changes: { field: string; before: string | null; after: string | null }[];
};

export type EventExport =
    | { state: 'idle' }
    | { state: 'running'; requested: Attribution }
    | { state: 'ready'; requested: Attribution; download: RouteLink };

export type AdminEventsProps = AdminShellProps & {
    events: EventRow[];
    total: number;
    filters: {
        q: string;
        preset: 'today' | '7d' | '30d' | null;
        from: string | null;
        to: string | null;
    };
    more: RouteLink | null;
    export: EventExport;
    actions: { export?: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 3: staff-disbursement-v1 and the minimal staff release (C3 proposal v2 §2e)      */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable. The Phase 1B disbursement and application shapes above stay
 * untouched; the console pages read these, reviewed only through synthetic fixtures. Staff pages
 * sit under the separately versioned staff-access contract: no `identity_context_revision`, no
 * Party key, and permission only ever from the staff permission map, never a role label.
 */

/** New `StaffPermission` entries, to be added and tested in the C3 server slice (H11). */
export type DisbursementPermission =
    | 'disbursements.view'
    | 'disbursements.authorize'
    | 'disbursements.approve'
    | 'disbursements.hold'
    | 'disbursements.requery';

/**
 * From current permissions and segregation: `approve` needs a checker other than the maker and a
 * fresh bound step-up; `reject` voids the maker's authorization only; `release_hold` needs someone
 * other than the staff member who placed the hold; `requery` asks about the same operation and
 * never resends. There is no `retry` or `reauthorize` (H12).
 */
export type DisbursementAllowedAction =
    | 'disbursement.authorize'
    | 'disbursement.approve'
    | 'disbursement.reject'
    | 'disbursement.hold'
    | 'disbursement.release_hold'
    | 'disbursement.requery';

export type DisbursementCommandKey =
    | 'authorize'
    | 'approve'
    | 'reject'
    | 'hold'
    | 'release_hold'
    | 'requery';

export type StaffDisbursementPageContract = {
    contract_version: 'staff-disbursement-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: DisbursementAllowedAction[];
};

/**
 * `queued`: the intent is recorded (DISBURSEMENT_INTENT_RECORDED) and the worker has not sent it.
 * `dispatched`: the worker sent it after its own recheck; the outcome is in `provider`, and a
 * verified failure stays here until it is reconciled. `succeeded` is verified and reconciled, with
 * issue committed. `failed_closing` is a failed recheck or a verified, reconciled final failure,
 * refunded (§11.3).
 */
export type C3DisbursementState =
    | 'ready'
    | 'awaiting_second_approver'
    | 'on_hold'
    | 'queued'
    | 'dispatched'
    | 'succeeded'
    | 'failed_closing';

export type C3DisbursementRow = {
    id: string;
    reference: string;
    business: string;
    note_title: string;
    /** Masked by the server. */
    destination: string;
    amount: Money;
    state: C3DisbursementState;
    provider_state: ProviderOutcomeState | null;
    link: RouteLink;
};

/**
 * The staff step-up an approval needs (#96 answer 5). It has its own staff route and purpose, not
 * the Auditor seal's. The server lists `route` only once S3-D publishes that exchange; while it is
 * null, approval is withheld. With a route, the drawer exchanges an authenticator code for a
 * `DisbursementStepUpProof` and sends it as `step_up_proof` on approve.
 */
export type DisbursementStepUp = {
    purpose: 'disbursement.approve';
    route: RouteAction | null;
};

/**
 * PROVISIONAL until S3-D publishes the staff step-up DTO. The body the drawer sends to
 * `step_up.route`, mirroring the Auditor seal exchange: the route-bound disbursement is the
 * record, so no disbursement id is sent, and the server binds the proof to the staff principal,
 * revision, exact amount, verified destination and intent digest.
 */
export type DisbursementStepUpRequest = {
    request_id: string;
    expected_revision: number;
    intent_digest: string;
    code: string;
};

/**
 * PROVISIONAL until S3-D publishes the staff step-up DTO. What the exchange answers, in the seal
 * exchange's shape: an opaque, single-use proof and when it expires. It lives only in the
 * drawer's transient state, never in the operation journal, the lookup or storage.
 */
export type DisbursementStepUpProof = {
    proof: string;
    expires_at: string;
};

export type C3DisbursementDetail = C3DisbursementRow & {
    campaign_id: string;
    revision: number;
    /** No sourced deadline after full funding exists; rendered as unavailable (H12). */
    due_on: null;
    precheck: {
        state: 'not_run' | 'passed' | 'failed';
        checked_at: string | null;
        policy_version: string | null;
        causes: string[];
    };
    maker: Attribution | null;
    checker: Attribution | null;
    viewer_is_maker: boolean;
    /** What the approval's step-up proof binds (H11). */
    approval_binding: {
        revision: number;
        amount: Money;
        /** Masked. */
        destination: string;
        intent_digest: string;
    } | null;
    step_up: DisbursementStepUp;
    intent: {
        operation_id: string;
        recorded_at: string;
        receipt: Receipt;
    } | null;
    dispatch: {
        sent_at: string;
        recheck: {
            state: 'passed' | 'failed';
            checked_at: string;
            causes: string[];
        };
    } | null;
    /** Staff audience: references and error codes are allowed here. */
    provider: ProviderOutcome | null;
    /** Releasing needs a staff member other than the one who placed it. */
    hold: { placed_by: Attribution; reason: string } | null;
    viewer_placed_hold: boolean;
    issue: {
        holdings: number;
        issued_at: string;
        effective_date: string;
    } | null;
    refund: { commitments: number; total: Money; receipt: Receipt } | null;
    trail: TrailEntry[];
    allowed_actions: DisbursementAllowedAction[];
    actions: Partial<Record<DisbursementCommandKey, RouteAction>>;
    links: {
        close: RouteLink;
        ledger: RouteLink | null;
        operation: RouteLink;
    };
};

export type C3AdminDisbursementsProps = AdminShellProps &
    StaffDisbursementPageContract & {
        disbursements: C3DisbursementRow[];
        pagination: Pagination;
        awaiting_second_approver: number;
        disbursement: C3DisbursementDetail | null;
        /**
         * Null with a scoped refusal when the viewer may no longer read the opened disbursement:
         * a lookup's `current: null` still needs lookup authorization.
         */
        refusal: { code: string; status: number } | null;
        preview_outcome?: C3PreviewOutcome<DisbursementAllowedAction>;
    };

/**
 * The minimal staff release of a reviewed application into a campaign (AC-02/AC-03, H1). It uses
 * the existing `applications.review` permission, offered only through `allowed_actions`, and can
 * never override a failed gate: a failed gate shows as blocking.
 */
export type StaffReleaseGate = {
    key: 'engine' | 'authority' | 'report';
    state: 'passed' | 'failed';
    /** A stable cause code when the gate failed. */
    cause: string | null;
};

export type StaffRelease = {
    revision: number;
    state: 'awaiting_staff_review' | 'released' | 'refused';
    gates: StaffReleaseGate[];
    allowed_actions: 'application.release'[];
    actions: { release: RouteAction | null };
    /** Set once released: the release receipt. */
    receipt: Receipt | null;
};

export type C3ApplicationReview = ApplicationReview & {
    release: StaffRelease | null;
};

export type C3AdminApplicationsProps = Omit<
    AdminApplicationsProps,
    'review'
> & {
    review: C3ApplicationReview | null;
    links: { operation: RouteLink };
    /** The live index pages by cursor (`staff-applications-v1`); previews may omit it. */
    pagination?: Pagination;
    preview_outcome?: C3PreviewOutcome<'application.release'>;
};

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 4: staff-servicing-v1 repayments (C4 contract proposal v1 §4b, AC-13)            */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable, under `staff-access-v1` like the C3 staff pages. This scaffold is
 * the read side of AC-13 plus requery: the queue, one repayment's reconciliation at the policy
 * tolerance and its allocation. Attributing inbound receipts (maker/checker) waits on Q3, so no
 * attribute, approve or reject command exists here yet. There is no balance editing and no amount
 * field anywhere.
 */

/** New `StaffPermission` entries for the read and requery slice. */
export type RepaymentPermission = 'repayments.view' | 'repayments.requery';

/** Asks the provider about the same operation again; never a resend (H13). */
export type RepaymentAllowedAction = 'repayment.requery';

export type StaffServicingPageContract = {
    contract_version: 'staff-servicing-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: RepaymentAllowedAction[];
};

/** `exception` is blocked and is never shown as reconciled; resolving it is Phase 2. */
export type RepaymentState = 'received' | 'allocated' | 'exception';

export type ReconciliationCheck = {
    state: 'unreconciled' | 'matched' | 'exception';
    /** "0" (#99 N7): a policy value from the server, never a UI constant. */
    tolerance: Money;
    expected: Money;
    observed: Money;
    difference: Money;
    /** Stable codes, e.g. AMOUNT_MISMATCH, IDENTITY_MISMATCH, DUPLICATE, CONFLICTING_FINAL. */
    causes: string[];
    checked_at: string | null;
    policy_version: string;
};

export type AllocationLine = {
    kind: AllocationKind;
    instalment_index: number | null;
    amount: Money;
    /** The ledger account; the drill-down is through `Allocation.ledger`. */
    account_code: string;
    /** Entitlement rows affected, on Investor lines only. */
    holders: number | null;
};

/**
 * Where a receipt went. `balanced` is the core's own check that what was paid, the receipt and
 * what was distributed agree; the console never adds the lines up itself.
 */
export type Allocation = {
    receipt_amount: Money;
    /** Principal, then return, then late fees (§11.4), the service fee, and anything unapplied. */
    paid: AllocationLine[];
    /** Service fee, steward share, Investor lines and the PSP fee. */
    distributed: AllocationLine[];
    balanced: boolean;
    entitlements: {
        holdings: number;
        record_date: KigaliDate;
        rule: 'largest_remainder_by_ordinal';
    };
    posted_at: string | null;
    ledger: RouteLink | null;
};

export type RepaymentRow = {
    id: string;
    reference: string;
    business: string;
    note_title: string;
    source: 'wallet' | 'inbound_receipt';
    amount: Money;
    state: RepaymentState;
    dpd_at_receipt: number | null;
    received_at: string;
    link: RouteLink;
};

export type ServicingSnapshot = {
    state: ServicingState;
    dpd: number | null;
    outstanding: ComponentAmounts;
};

export type RepaymentDetail = RepaymentRow & {
    revision: number;
    note_id: string;
    servicing_before: ServicingSnapshot;
    /** Null until the allocation posts. */
    servicing_after: ServicingSnapshot | null;
    source_detail:
        | { kind: 'wallet'; wallet_entry_id: string }
        | {
              kind: 'inbound_receipt';
              receipt_id: string;
              /** Staff audience: references and error codes are allowed here. */
              provider: ProviderOutcome;
          };
    reconciliation: ReconciliationCheck;
    /** Null while `received`. */
    allocation: Allocation | null;
    /** REPAYMENT_RECEIVED, REPAYMENT_ALLOCATED, …, each immutable. */
    receipts: Receipt[];
    trail: TrailEntry[];
    allowed_actions: RepaymentAllowedAction[];
    actions: Partial<Record<'requery', RouteAction>>;
    links: {
        close: RouteLink;
        ledger: RouteLink | null;
        operation: RouteLink;
        business: RouteLink | null;
    };
};

export type AdminRepaymentsProps = AdminShellProps &
    StaffServicingPageContract & {
        counts: { due_today: number; overdue: number; exceptions: number };
        repayments: RepaymentRow[];
        pagination: Pagination;
        repayment: RepaymentDetail | null;
        preview_outcome?: C3PreviewOutcome<RepaymentAllowedAction>;
    };

/* ------------------------------------------------------------------------------------------ */
/* Phase 2: Book (MVP-ADMIN-SCR-05) and Exceptions (MVP-ADMIN-SCR-06), read-only proposal      */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable, under `staff-access-v1` like the C3/C4 staff pages. Nothing returns
 * these shapes yet: both pages are reviewed through synthetic `preview/{fixture}` fixtures only.
 * Both screens are read-only with links: assigning, escalating, halting or remedying waits on the
 * unsigned D-25/28/54 escalation policy and its approvals, so no command and no allowed action
 * exists here. There is no book-level exposure or concentration policy either (the #99 50%
 * single-investor per-raise cap is enforced at purchase), so no breach or window state is sent.
 */

/** No command exists on either screen yet; `allowed_actions` is always empty. */
export type StaffBookPageContract = {
    contract_version: 'staff-book-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type StaffExceptionsPageContract = {
    contract_version: 'staff-exceptions-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type BookStatKey =
    | 'live_notes'
    | 'principal_outstanding'
    | 'due_today'
    | 'overdue';

/** A server-counted filter over the book; `all` is every live note. */
export type BookChip = {
    key: 'all' | ServicingState;
    count: number;
    link: RouteLink;
    active: boolean;
};

/**
 * One live note and its health: its servicing state and DPD as the core records them (MC-03).
 * `next_due` is the oldest unpaid instalment, null when nothing is scheduled.
 */
export type BookNoteRow = {
    id: string;
    note_id: string;
    business: string;
    note_title: string;
    principal_outstanding: Money;
    next_due: { on: KigaliDate; amount: Money } | null;
    servicing: ServicingState;
    /**
     * Days past due, counted by the server against Kigali contractual dates: null while no
     * instalment is due or overdue (so `next_due` can be set with a null DPD), 0 on its due
     * date, positive when overdue.
     */
    dpd: number | null;
    link: RouteLink;
};

export type AdminBookProps = AdminFrameShellProps &
    StaffBookPageContract & {
        stats: { key: BookStatKey; value: StatValue }[];
        chips: BookChip[];
        notes: BookNoteRow[];
        pagination: Pagination;
    };

export type ExceptionKind = 'arrears' | 'halt' | 'variance';

/** A server-counted filter over the open exceptions; `all` is every open one. */
export type ExceptionChip = {
    key: 'all' | ExceptionKind;
    count: number;
    link: RouteLink;
    active: boolean;
};

/**
 * An open exception, shaped like a reconciliation break: aged and owned, never hidden (AC-05).
 * `amount` is the arrears overdue or the variance gap, null for a halt; `dpd` is arrears only.
 */
export type ExceptionItem = {
    id: string;
    kind: ExceptionKind;
    reference: string;
    /** What the exception is about; an account subject (a statement variance) has no note. */
    subject: { kind: 'business' | 'account'; label: string };
    note_title: string | null;
    description: string;
    amount: Money | null;
    dpd: number | null;
    opened_at: string;
    age_days: number;
    owner: Attribution | null;
    link: RouteLink;
};

export type AdminExceptionsProps = AdminFrameShellProps &
    StaffExceptionsPageContract & {
        counts: { open: number; unassigned: number };
        chips: ExceptionChip[];
        exceptions: ExceptionItem[];
        pagination: Pagination;
    };

/* ------------------------------------------------------------------------------------------ */
/* Phase 2: Reconciliation (MVP-ADMIN-SCR-04), Partner coverage (MVP-ADMIN-SCR-07) and Reports */
/* (MVP-ADMIN-SCR-10), read-only proposal                                                      */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable, under `staff-access-v1` like the Book and Exceptions proposals.
 * Nothing returns these shapes yet: the pages are reviewed through synthetic `preview/{fixture}`
 * fixtures only, and none offers a command.
 *
 * The account topology is undecided (plan D-21), so Reconciliation knows no account kinds, no
 * reserve bucket and no float: the server names every account it reconciles. Partner coverage
 * carries no radius or eligibility rule: the server decides whether a district is covered. Reports
 * lists packs and their readiness only; no pack's contents are defined here.
 */

/** No command exists on these screens yet; `allowed_actions` is always empty. */
export type StaffReconciliationPageContract = {
    contract_version: 'staff-reconciliation-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type StaffCoveragePageContract = {
    contract_version: 'staff-coverage-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type StaffReportsPageContract = {
    contract_version: 'staff-reports-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

/** Whether the provider or bank statement feed for an account is reaching the core. */
export type StatementFeed =
    | { state: 'available' }
    | { state: 'unavailable'; since: string };

/**
 * One account the core reconciles, as the server names it. The three balances sit side by side as
 * of `as_of`: the ledger's, the provider or bank statement's, and their difference (ledger minus
 * statement), each stated by the server. While the feed is unavailable no statement balance is
 * known, so `statement` and `difference` are null.
 */
export type ReconciliationAccount = {
    id: string;
    label: string;
    as_of: string;
    ledger: Money;
    statement: Money | null;
    difference: Money | null;
    feed: StatementFeed;
};

/**
 * The close of the last business day (MVP-ADMIN-AC-05: yesterday reconciles before today opens).
 * A day with an unexplained break cannot close, so `open_break` stays until every break is
 * explained; `not_reconciled` is a day the core has not been able to reconcile yet.
 */
export type ReconciliationDayClose = { business_date: KigaliDate } & (
    | { state: 'reconciled'; reconciled_at: string }
    | { state: 'open_break' }
    | { state: 'not_reconciled' }
);

/**
 * An open break, in the Today shape without its commands, tied to the account it sits on and
 * linking to its record. Assigning and escalating wait on the unsigned D-25/28/54 policy.
 */
export type ReconciliationBreakRecord = Omit<ReconciliationBreak, 'actions'> & {
    account_id: string;
    link: RouteLink;
};

export type AdminReconciliationProps = AdminFrameShellProps &
    StaffReconciliationPageContract & {
        day_close: ReconciliationDayClose;
        accounts: ReconciliationAccount[];
        breaks: ReconciliationBreakRecord[];
    };

export type CoverageStatKey = 'districts' | 'uncovered' | 'audits_open';

/**
 * One district and the server's counts: Audit Partners active there and audits open there
 * (including any still waiting for a partner). `capacity` is the server's verdict; the console
 * applies no radius, capacity or eligibility rule of its own. `link` opens the district's Audit
 * Partners, null when none is active.
 */
export type CoverageDistrict = {
    id: string;
    district: string;
    province: string;
    active_partners: number;
    audits_open: number;
    capacity: 'covered' | 'uncovered';
    link: RouteLink | null;
};

export type AdminCoverageProps = AdminFrameShellProps &
    StaffCoveragePageContract & {
        stats: { key: CoverageStatKey; value: StatValue }[];
        districts: CoverageDistrict[];
    };

export type ReportPackKind = 'regulator' | 'board' | 'export';

/**
 * A pack for one period, as the server names it. `complete` is final as of `as_of`; `incomplete`
 * is a period that has not closed, so nothing can be downloaded yet; `moving` is a snapshot as of
 * `as_of` whose figures can still change, with the server's note on what is pending. `link`
 * retrieves exactly the pack or snapshot stated at `as_of` (never a fresh recomputation), and is
 * always null while the period is incomplete; a complete or moving pack may also have none.
 */
export type ReportPack = {
    id: string;
    kind: ReportPackKind;
    label: string;
    period: { start: KigaliDate; end: KigaliDate };
} & (
    | { status: 'complete'; as_of: string; link: RouteLink | null }
    | { status: 'incomplete'; as_of: null; link: null }
    | {
          status: 'moving';
          as_of: string;
          pending: string;
          link: RouteLink | null;
      }
);

export type AdminReportsProps = AdminFrameShellProps &
    StaffReportsPageContract & {
        packs: ReportPack[];
    };
