import type { BusinessRating, NoteStatus } from './business';
import type { Money } from './money';
import type { RouteAction, RouteLink } from './routing';
import type {
    C3PreviewOutcome,
    Pagination,
    ProviderOutcome,
    ProviderOutcomeState,
    Receipt,
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

/** What every console page receives for its frame. */
export type AdminShellProps = {
    viewer: StaffViewer;
    nav: Record<AdminSection, RouteLink> & { launcher: RouteLink };
    /** Live queue sizes for the sidebar badges; zero shows no badge. */
    badges: { applications: number; disbursements: number };
    /** The page-scoped top-bar search, as the server applied it. */
    search: string;
    /** Server clock, RFC3339; relative times count from here, never from the browser. */
    server_time: string;
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
    capacity_used_pct: number;
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
    capacity: {
        used_pct: number;
        approved: Money;
        active_notes: number;
        outstanding: Money;
    };
    /** The engine's evidence, each on 0–100. Read-only. */
    factors: { key: EvidenceFactorKey; value: number }[];
    /** Listing audit evidence; anything but sealed blocks approval (SCR-02-ST-02). */
    audit: {
        state: 'sealed' | 'pending' | 'missing';
        sealed_at: string | null;
    };
    use_of_funds: string;
    submitted_at: string;
    reviewer: string | null;
    trail: TrailEntry[];
    links: { close: RouteLink; business: RouteLink };
    actions: Partial<Record<ReviewAction, RouteAction>>;
};

export type AdminApplicationsProps = AdminShellProps & {
    policy: PolicyItem[];
    tabs: { key: ApplicationTab; count: number; link: RouteLink }[];
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
 * The staff step-up an approval needs. It has its own staff route and purpose, not the Auditor
 * seal's; that route and its codes are still to be settled, so `route` is null until they exist.
 */
export type DisbursementStepUp = {
    purpose: 'disbursement.approve';
    route: RouteAction | null;
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
    preview_outcome?: C3PreviewOutcome<'application.release'>;
};
