import type { BusinessRating, NoteStatus } from './business';
import type { InvestorNotice } from './investor';
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

export type StaffRole =
    | 'analyst'
    | 'approver'
    | 'compliance'
    | 'treasury'
    | 'superadmin';

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
    | 'complaints'
    | 'reports'
    | 'mail_testers'
    | AdminPendingSection;

/**
 * Design sections the console shows before their screens are wired: each opens the console frame
 * with its title and an empty state, so the sidebar matches the design while the work lands.
 */
export type AdminPendingSection =
    | 'notes'
    | 'primary_market'
    | 'secondary_market'
    | 'reports'
    | 'risk'
    | 'compliance'
    | 'payments'
    | 'ratings'
    | 'deferrals'
    | 'plus'
    | 'finance'
    | 'messaging'
    | 'academies'
    | 'app_control'
    | 'engines'
    | 'policies'
    | 'system_health';

export type AdminPendingSectionProps = AdminFrameShellProps & {
    /** A pending design section, or a designed screen whose live data is still being wired. */
    section:
        | AdminPendingSection
        | 'today'
        | 'businesses'
        | 'auditors'
        | 'staff'
        | 'events';
    /** Policies only: each rule the platform enforces, under its design tab. */
    rules?: PolicyRule[];
    /** A KPI tile's figure by its design key, where the server reads one (Finance). */
    figures?: Partial<Record<string, Money>>;
    /** Finance only, for whoever may read the ledger: each fee stream and who pays it. */
    streams?: FinanceStream[];
    /** Engines and Ratings, for underwriters: the engine's settings and the rating scorecard. */
    engine?: EngineRow[];
    /** A KPI tile's count by its design key, where the server reads one (Ratings). */
    counts?: Partial<Record<string, number>>;
    /** Ratings only, for whoever may view Businesses: every Business with a published rating. */
    book?: RatingBookRow[];
    /** App Control only: how much of each sandbox hard limit is used now (SBX-R01). */
    sandbox?: SandboxUsage[];
    /** App Control only: each app, online, with whether each of its features is built. */
    apps?: AppStatus[];
    /** App Control only, for whoever may view Businesses: the Businesses that raised most. */
    content?: AppContentRow[];
};

/** One rated Business in the rating book. */
export type RatingBookRow = {
    key: string;
    business: string;
    sector: string;
    rating: Rating;
};

/** One engine setting under its design tab, with the rule set version it comes from. */
export type EngineRow = {
    tab: string;
    key:
        | 'target_dscr'
        | 'minimum_dscr'
        | 'repeat_monthly_dscr'
        | 'min_principal'
        | 'max_principal'
        | 'issuance_unit'
        | 'first_issue_months'
        | 'repeat_issue_months'
        | 'tenors'
        | 'rate_band'
        | 'platform_fee'
        | 'cpa_share'
        | 'coverage'
        | 'cfads_margin'
        | 'nocf_stability'
        | 'positive_months'
        | 'history_depth'
        | 'conduct'
        | 'new_issuer_conduct'
        | 'instalment_conduct'
        | 'report_conduct'
        | 'adverse_conduct_cap'
        | 'default_cap'
        | 'strong'
        | 'stable'
        | 'weak'
        | 'distressed';
    value: string;
    source: string;
};

export type AppContentRow = {
    key: string;
    type: 'business';
    name: string;
    sector: string;
    rating: Rating | null;
    state: 'active' | 'pending';
};

export type AppStatus = {
    key: 'investor' | 'business' | 'auditor';
    features: {
        key:
            | 'sign_ups'
            | 'secondary_market'
            | 'academy'
            | 'note_creation'
            | 'monthly_reports'
            | 'auditor_sign_ups'
            | 'auto_dispatch'
            | 'flash_audits';
        state: 'live' | 'not_built';
    }[];
};

/** Whole numbers as strings: a count, or RWF for `principal`. */
export type SandboxUsage = {
    key: 'businesses' | 'investors' | 'principal';
    used: string;
    cap: string;
    used_pct: number;
};

export type FinanceStream = {
    key:
        | 'platform_fee'
        | 'audit_partner_share'
        | 'deposit_fees'
        | 'earnings_fee'
        | 'resale_fee'
        | 'withdrawal_fee'
        | 'loss_reserve';
    /** The rule that sets it, as Policies shows it; null when no rate applies. */
    rate: PolicyItem | null;
    /** Null when nobody pays it: the fee or reserve does not exist. */
    paid_by: 'business' | 'investor' | 'seller' | null;
    /** Null when no ledger account takes it yet. */
    collected: Money | null;
};

/** One rule in effect, with the policy version it comes from when the rule carries one. */
export type PolicyRule = PolicyItem & {
    tab: string;
    version: string | null;
};

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
        | 'dual_control_threshold'
        | 'platform_fee'
        | 'cpa_share'
        | 'listing_fee'
        | 'repayment_fee'
        | 'minimum_age'
        | 'identity_evidence'
        | 'complaint_acknowledge'
        | 'complaint_resolve'
        | 'min_principal'
        | 'max_principal'
        | 'first_issue_months'
        | 'repeat_issue_months'
        | 'withdrawal_min'
        | 'withdrawal_max'
        | 'cpa_share_upcountry'
        | 'resale_fee'
        | 'earnings_fee'
        | 'cpa_earnings_share'
        | 'application_fee'
        | 'withdrawal_fee'
        | 'loss_reserve'
        | 'min_rating'
        | 'revenue_cap'
        | 'kyc_reverify'
        | 'late_charge'
        | 'miss_grace'
        | 'default_at'
        | 'auto_freeze'
        | 'min_investment'
        | 'concentration'
        | 'raise_days'
        | 'resale_listing_days'
        | 'resale_reservation_minutes'
        | 'cpa_radius'
        | 'sandbox_businesses'
        | 'sandbox_investors'
        | 'sandbox_principal'
        | 'residency'
        | 'risk_statements'
        | 'min_rate'
        | 'max_rate'
        | 'note_unit_price'
        | 'min_term'
        | 'max_term'
        | 'registration_countries'
        | 'kyc_investor_id'
        | 'kyc_investor_selfie'
        | 'kyc_investor_dob'
        | 'kyc_investor_address'
        | 'kyc_business_rdb';
    /** Empty when the rule is worded: `term` then names the wording. */
    value: string;
    term?: PolicyTerm;
};

/** A worded policy value, translated by the console. */
export type PolicyTerm =
    | 'every_deal'
    | 'required'
    | 'not_set'
    | 'none'
    | 'late_from_due'
    | 'day_31'
    | 'late_schedule'
    | 'resident_rwanda'
    | 'rwanda'
    | 'not_collected';

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
    /** A figure the platform does not record yet; the console shows it as not tracked. */
    | { key: TodayKpiKey; value: null }
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
    /** Null while the platform does not record this queue. */
    count: number | null;
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
    | 'platform_fee'
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

/**
 * The live Operations Center sends only the destinations the viewer may open, and null for every
 * panel or figure the platform does not record yet; the console shows those as not tracked.
 */
export type AdminTodayProps = AdminFrameShellProps & {
    kpis: TodayKpi[];
    attention: AttentionTile[];
    /** Null for a viewer who may not view reconciliation; empty when no break is open. */
    breaks: ReconciliationBreak[] | null;
    capital_raised: {
        from: string | null;
        to: string | null;
        grain: 'year' | 'month' | 'day' | 'hour';
        bars: { label: string; amount: Money; height_pct: number }[];
    };
    /** Issued notes by their newest monthly report; distress is null until servicing records it. */
    portfolio_health: {
        total: number;
        healthy: { count: number; pct: number };
        watch: { count: number; pct: number };
        distressed: { count: number; pct: number } | null;
    } | null;
    /** Null while the console has no ledger feed. */
    activity: ActivityItem[] | null;
    /** A stage the platform does not record yet has a null count. */
    funnel: { stage: FunnelStage; count: number | null; width_pct: number }[];
    pending_applications: {
        id: string;
        business: string;
        requested: Money;
        rating: Rating | null;
        /** Null for a viewer who may not review applications. */
        link: RouteLink | null;
    }[];
    sector_exposure:
        | {
              sector: string;
              outstanding: Money;
              notes: number;
              at_risk: boolean;
          }[]
        | null;
    treasury: {
        invested: Money | null;
        disbursed: Money | null;
        platform_net: Money | null;
        paid_to_investors: Money | null;
    };
    collections: {
        in_repayment: number;
        outstanding: Money;
        next_due: Money;
        on_track: number;
        late: number;
        default_risk: number;
    } | null;
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
    /** Flat total return over the term, one decimal: "12.1". Not an APR. Null when the engine refused it. */
    rate_pct: string | null;
    /** Null while the server does not project capacity usage; shown as unavailable, never 0. */
    capacity_used_pct: number | null;
    rating: Rating | null;
    submitted_at: string;
    state: ApplicationState;
    decision: EngineDecision;
    /** Null for an engine refusal: the draft opens no staff review. */
    link: RouteLink | null;
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
    /** Null until the platform reads a Business's capacity; the cell shows a dash. */
    capacity_used_pct: number | null;
    /** `not_tracked` until the platform reads arrears and freezes; the cell says so. */
    health: 'healthy' | 'watch' | 'distressed' | 'not_tracked';
    /** Null while no freeze read exists. */
    frozen: boolean | null;
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
    /** Null where the platform records no firm, licence or district for the partner. */
    firm: string | null;
    licence: string | null;
    district: string | null;
    active_engagements: number;
    on_time_pct: number | null;
    /** Null until audit fees are paid out and read. */
    share_mtd: Money | null;
    standing: 'active' | 'pending' | 'licence_expired' | 'suspended';
    frozen: boolean;
    link: RouteLink;
};

export type StaffPartyRow = {
    id: string;
    name: string;
    email: string;
    initials: string;
    /** The operator's most privileged role; null when they hold none. */
    role: StaffRole | null;
    /** Every role the operator holds, most privileged first. */
    roles: StaffRole[];
    frozen: boolean;
    you: boolean;
    link: RouteLink;
};

/** A capability a staff role can grant (`StaffPermission`); every operator may open the console. */
export type StaffPermissionKey =
    | 'admin.open'
    | 'businesses.view'
    | 'businesses.verify'
    | 'investors.verify'
    | 'applications.review'
    | 'audit.partners.verify'
    | 'audit.assignments.manage'
    | 'audit.reports.view'
    | 'consent.documents.record'
    | 'ledger.view'
    | 'disbursements.view'
    | 'disbursements.hold'
    | 'disbursements.requery'
    | 'staging.mail.testers.manage'
    | 'reconciliation.view'
    | 'exceptions.view'
    | 'messaging.view'
    | 'primary.view'
    | 'payments.view'
    | 'complaints.answer'
    | 'health.view'
    | 'staff.manage'
    | 'accounts.freeze'
    | 'capacity.adjust'
    | 'records.edit'
    | 'ledger.correct'
    | 'policies.change'
    | 'records.delete';

/**
 * One row of the Staff & Roles permission matrix: a capability and the roles that grant it. A design
 * capability no role holds says why: not built yet, or barred for every role by rule.
 */
export type PermissionMatrixRow = {
    key: StaffPermissionKey;
    roles: StaffRole[];
    withheld?: 'not_built' | 'by_rule';
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
        | { kind: 'text'; value: string }
        | { kind: 'holding'; value: Money; notes: number };
};

export type PartyDetail = {
    id: string;
    kind: PartyKind;
    name: string;
    subtitle: string;
    health:
        | 'active'
        | 'healthy'
        | 'watch'
        | 'distressed'
        | 'kyc_pending'
        | 'frozen'
        | 'not_tracked';
    stats: { key: PartyStatKey; value: StatValue }[];
    list: {
        key: 'active_notes' | 'holdings' | 'engagements';
        rows: PartyListRow[];
    } | null;
    history: TrailEntry[];
    kyc: { state: KycState; due_on: string | null } | null;
    licence: {
        /** Null where the platform records no ICPAR member ID or district; the field shows a dash. */
        member_id: string | null;
        licence: string;
        expires_on: string;
        district: string | null;
        state: 'verified' | 'pending' | 'expired';
        /** The accreditation revision and waiting submission a licence decision answers. */
        review?: { revision: number; submission_id: string } | null;
    } | null;
    /** The current restriction, attributed; null when the account is open. */
    freeze: Attribution | null;
    /** Why a release cannot happen here (e.g. a legal hold), or null. */
    release_blocked: 'LEGAL_HOLD' | null;
    /** Every freeze and release on this account, newest first. */
    restrictions: (TrailEntry & { kind: 'freeze' | 'release' })[];
    /** An Investor's identity submission, reviewed in the 360; absent for other parties. */
    verification?: InvestorVerificationReview | null;
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

export type AdminPartiesProps = AdminFrameShellProps & {
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
    /** Staff & Roles only: what each role may do. */
    permission_matrix?: PermissionMatrixRow[];
    /** Staff & Roles only: the latest staff access changes, newest first. */
    action_log?: StaffActionLogEntry[];
    /** Staff only, for a viewer who may manage staff: adds an operator by email with one role. */
    grant?: RouteAction | null;
    pending_approvals?: StaffApproval[];
    /** Staff only: how many of the capabilities a role can hold the viewer holds. */
    access?: { granted: number; total: number };
};

export type StaffApproval = {
    id: string;
    recipient: string;
    email: string;
    requester: string;
    operation: 'grant' | 'reactivate';
    reason: string;
    approve: RouteAction | null;
};

export type StaffActionLogEntry = {
    id: string;
    at: string;
    actor: string;
    target: string;
    action: 'staff_suspended' | 'staff_enabled' | 'staff_granted';
    /** The role an invite gave; null for a freeze or a reactivation. */
    role: StaffRole | null;
    reason: string;
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
    /**
     * Who started this entry's money flow and when, by reference. A later movement in the same
     * flow keeps it, so it is not who posted the entry; null when no operation is recorded.
     */
    origin: { actor: string; at: string } | null;
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
    /** The next older page, after the last row shown; null on the last page. */
    older: RouteLink | null;
    /** Back to the newest page; null while on it. */
    newest: RouteLink | null;
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

export type AdminEventsProps = AdminFrameShellProps & {
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
/* Checkpoint 3: staff-disbursement-v2 and automatic disbursement (BRS v2.0 SVC-R01, ADM-R02)  */
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
    | 'disbursements.hold'
    | 'disbursements.requery';

/**
 * From current permissions only. A funded disbursement pays automatically, so there is nothing to
 * approve: one authorised staff member may `hold` it while it waits and `release_hold` it, the
 * hold's placer included (ADM-R02); `requery` asks about the same operation and never resends.
 * There is no `retry` or `reauthorize` (H12).
 */
export type DisbursementAllowedAction =
    | 'disbursement.hold'
    | 'disbursement.release_hold'
    | 'disbursement.requery';

export type DisbursementCommandKey = 'hold' | 'release_hold' | 'requery';

export type StaffDisbursementPageContract = {
    contract_version: 'staff-disbursement-v2';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: DisbursementAllowedAction[];
};

/**
 * `ready`: funded, waiting for automatic payment because something blocks it (no verified payout
 * destination yet, or the next run has not reached it). `awaiting_second_approver` only appears
 * on a disbursement recorded under the retired two-person release; automatic payment pays it too.
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
    /**
     * The BRS v2.0 platform fee taken from the raise (SVC-R01): 3% of the amount, 40% of it the
     * Audit Partner's. The payout is what the Business receives. Null for a disbursement opened
     * before the fee rule.
     */
    platform_fee: {
        fee: Money;
        audit_partner_share: Money;
        rozine_share: Money;
        payout: Money;
        version: string;
    } | null;
    state: C3DisbursementState;
    provider_state: ProviderOutcomeState | null;
    link: RouteLink;
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
    /**
     * Automatic payment (SVC-R01): the rule it runs under, what the Business receives, and what a
     * `ready` disbursement waits for. `waiting_for` is null once it is held or in flight.
     */
    automation: {
        rule_version: string;
        payout: Money;
        waiting_for: 'verified_payout_destination' | 'next_run' | null;
    };
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
    /** Any authorised staff member may release it, including the one who placed it. */
    hold: { placed_by: Attribution; reason: string } | null;
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

/**
 * The live staff Resource sends the frame as `AdminFrameShellProps` (#96): only the sections the
 * server serves, and a null badge where it knows no count. Every full fixture shell is one too.
 */
export type C3AdminDisbursementsProps = AdminFrameShellProps &
    StaffDisbursementPageContract & {
        disbursements: C3DisbursementRow[];
        pagination: Pagination;
        /** Waiting for payment or on hold: the ones staff may need to look at. */
        needs_attention: number;
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
    links: {
        operation: RouteLink;
        /** The change beacon (S4-E), with its render-time cursor; null in previews. */
        changes?: RouteLink | null;
    };
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
    /** Principal, then return, then late fees (§11.4), and anything unapplied. */
    paid: AllocationLine[];
    /** Steward share, Investor lines and the PSP fee. */
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

/** `audit` is a Flash Audit unfinished after its 24-hour clock (BRS AUD-R07). */
export type ExceptionKind =
    | 'arrears'
    | 'halt'
    | 'variance'
    | 'audit'
    | 'complaint';

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
    /**
     * What the exception is about; an account subject (a statement variance), an Investor
     * subject (a halt on their account) and a Business subject of an overdue audit have no note.
     */
    subject: { kind: 'business' | 'account' | 'investor'; label: string };
    note_title: string | null;
    description: string;
    amount: Money | null;
    dpd: number | null;
    opened_at: string;
    age_days: number;
    owner: Attribution | null;
    /** The record it sits on, null when the viewer may not open it. */
    link: RouteLink | null;
};

export type AdminExceptionsProps = AdminFrameShellProps &
    StaffExceptionsPageContract & {
        counts: { open: number; unassigned: number };
        chips: ExceptionChip[];
        exceptions: ExceptionItem[];
        pagination: Pagination;
    };

/** The Primary Market Monitor offers no moderation command; `allowed_actions` is always empty. */
export type StaffPrimaryMarketPageContract = {
    contract_version: 'staff-primary-market-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type PrimaryMarketStatKey =
    | 'active'
    | 'in_flight'
    | 'fill'
    | 'completion';

/** Where a raise that is still filling stands, as the Investor projection names it. */
export type RaiseLifecycle =
    | 'live'
    | 'fully_reserved'
    | 'inventory_unavailable'
    | 'sold_out_pending_settlement'
    | 'closing_pending_settlement';

/** One raise still filling: its fill, investors and Kigali days left on its own clock. */
export type PrimaryMarketRaise = {
    id: string;
    reference: string;
    business: string;
    sector: string;
    district: string;
    rating: Rating;
    rate_pct: string;
    term_months: number;
    funded_pct: string;
    raised: Money;
    target: Money;
    investors: number;
    lifecycle: RaiseLifecycle;
    opened_at: string;
    closes_at: string;
    days_left: number;
};

/** The Notes register offers no command; `allowed_actions` is always empty. */
export type StaffNotesPageContract = {
    contract_version: 'staff-notes-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type NotesStatKey = 'total' | 'live' | 'repaying' | 'raised';

/** Newest first, or largest first by one of the design's sorts. */
export type NotesSort =
    | 'newest'
    | 'raised'
    | 'yield'
    | 'funded'
    | 'investors'
    | 'term';

/**
 * Where a note sits now, as the dashboard counts it: `live` while it raises, `funded` once full and
 * before its payout closes, `repaying` once its notes issued, `failed` when it closed unfunded or
 * its payout failed. `matured` is a tab only: servicing records no final repayment yet.
 */
export type NoteState = 'live' | 'funded' | 'repaying' | 'failed';

export type NotesTabKey = 'all' | 'live' | 'repaying' | 'matured' | 'failed';

export type NoteRegisterRow = {
    id: string;
    reference: string;
    business: string;
    sector: string;
    state: NoteState;
    rate_pct: string;
    term_months: number;
    funded_pct: string;
    raised: Money;
    target: Money;
    investors: number;
    listed_at: string;
};

export type AdminNotesProps = AdminFrameShellProps &
    StaffNotesPageContract & {
        stats: { key: NotesStatKey; value: StatValue }[];
        tabs: {
            key: NotesTabKey;
            count: number;
            link: RouteLink;
            active: boolean;
        }[];
        notes: NoteRegisterRow[];
        pagination: Pagination;
        sort: NotesSort;
    };

export type AdminPrimaryMarketProps = AdminFrameShellProps &
    StaffPrimaryMarketPageContract & {
        stats: { key: PrimaryMarketStatKey; value: StatValue }[];
        /**
         * Cash committed per Kigali day over the last fourteen days, or over the chosen window of
         * dates (`to` set, both ends included; by month over a long window). Buckets with none are
         * left out.
         */
        trend: TrendWindow & {
            bars: { label: string; amount: Money; height_pct: number }[];
        };
        raises: PrimaryMarketRaise[];
        pagination: Pagination;
    };

/** The Payment Center offers no release, hold or reverse command; `allowed_actions` is always empty. */
export type StaffPaymentsPageContract = {
    contract_version: 'staff-payments-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type PaymentsStatKey = 'deposits' | 'withdrawals' | 'disbursed';

/** A withdrawal whose cash is held while its payout waits on the provider's verified outcome. */
export type PayoutInFlight = {
    id: string;
    reference: string;
    owner: 'investor' | 'business';
    /** Null when the account has no name on record. */
    name: string | null;
    amount: Money;
    received: Money;
    account: string;
    state: 'pending' | 'unknown';
    requested_at: string;
};

/** A chart's window as the server read it: no `to` means the last fourteen days. */
export type TrendWindow = {
    from: string;
    to: string | null;
    grain: 'day' | 'month';
    /** Whether the viewer chose either end; otherwise it is the last fourteen days. */
    custom: boolean;
};

export type AdminPaymentsProps = AdminFrameShellProps &
    StaffPaymentsPageContract & {
        /** The first Kigali day of the fourteen the figures cover. */
        from: string;
        /** The bars' window: the last fourteen days, or the chosen dates (per month over a long one). */
        trend: TrendWindow;
        stats: { key: PaymentsStatKey; value: Money; count: number }[];
        in_flight: number;
        /** The fee schedule money moves under. */
        policy: PolicyItem[];
        /** Credited deposits plus paid-out withdrawals per Kigali bucket; buckets with none are left out. */
        bars: { label: string; amount: Money; height_pct: number }[];
        payouts: PayoutInFlight[];
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
 * linking to its record, or null when the viewer may not open that record. Assigning and
 * escalating wait on the unsigned D-25/28/54 policy.
 */
export type ReconciliationBreakRecord = Omit<ReconciliationBreak, 'actions'> & {
    account_id: string;
    link: RouteLink | null;
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

/* ------------------------------------------------------------------------------------------ */
/* Phase 2: Complaints (BRS v2.0 CMP-R03), `staff-complaints-v1`                                */
/* ------------------------------------------------------------------------------------------ */

export type ComplaintStatusKey = 'awaiting' | 'answered' | 'resolved';

/** Which clock a complaint is past: its 4-hour acknowledgement or its 48-hour resolution. */
export type ComplaintLate = 'acknowledge' | 'resolve' | null;

export type ComplaintStatKey = 'unresolved' | 'awaiting' | 'late';

/** One complaint as the list shows it; `link` opens it in the drawer. */
export type ComplaintRow = {
    id: string;
    reference: string;
    customer: string;
    role: 'investor' | 'business';
    status: ComplaintStatusKey;
    late: ComplaintLate;
    opened_at: string;
    acknowledge_by: string;
    resolve_by: string;
    messages: number;
    link: RouteLink;
};

/** The complaint open in the drawer, with its whole conversation; `answer` is null once resolved. */
export type ComplaintDetail = Omit<ComplaintRow, 'messages' | 'link'> & {
    messages: {
        id: string;
        author: 'customer' | 'staff';
        body: string;
        resolves: boolean;
        at: string;
    }[];
    close: RouteLink;
    answer: { url: string; method: 'post' } | null;
};

export type AdminComplaintsProps = AdminFrameShellProps & {
    contract_version: 'staff-complaints-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
    stats: { key: ComplaintStatKey; value: StatValue }[];
    complaints: ComplaintRow[];
    complaint: ComplaintDetail | null;
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

/* ------------------------------------------------------------------------------------------ */
/* Investor identity review (Compliance, `investors.verify`)                                   */
/* ------------------------------------------------------------------------------------------ */

export type KycIdType = 'national_id' | 'passport' | 'drivers_license';

export type KycCaseStatus = 'draft' | 'submitted' | 'approved' | 'rejected';

export type InvestorVerificationEntry = {
    id: string;
    revision: number;
    status: KycCaseStatus;
    submitted_at: string;
    decided_at: string | null;
    name: string;
    email: string;
    id_type: KycIdType;
    /** The automated checks the case passed before it could be submitted. */
    checks: KycCheck[];
    selected: boolean;
    link: RouteLink;
};

export type KycCheck = 'age' | 'id_number' | 'documents' | 'selfie';

/** A private upload: read only through its link, never inlined in the props. */
export type InvestorVerificationDocument = {
    id: string;
    slot: 'front' | 'back' | 'selfie';
    filename: string;
    media_type: string;
    size_bytes: number;
    sha256: string;
    uploaded_at: string;
    /** Whether the submission still points at this upload rather than a replaced one. */
    current: boolean;
    /** The private file as a download. */
    link: RouteLink;
    /** The same private file, served for display in place. */
    view: RouteLink;
};

export type InvestorVerificationReview = {
    id: string;
    revision: number;
    status: KycCaseStatus;
    submitted_at: string | null;
    account: { name: string; email: string };
    date_of_birth: string;
    id_type: KycIdType;
    id_number: string;
    decision: {
        outcome: 'approved' | 'rejected';
        reason: string;
        decided_at: string;
    } | null;
    documents: InvestorVerificationDocument[];
    history: {
        revision: number;
        status: KycCaseStatus;
        command:
            | 'verification.save'
            | 'verification.upload'
            | 'verification.submit'
            | 'verification.approve'
            | 'verification.reject';
        reason: string | null;
        at: string;
    }[];
    links: { close: RouteLink };
    /** Present only while the case waits for review. */
    actions: { approve?: RouteAction; reject?: RouteAction };
};

export type AdminInvestorVerificationsProps = AdminFrameShellProps & {
    contract_version: 'staff-investor-verifications-v1';
    active_tab: 'submitted' | 'decided';
    /** The compliance rules the queue is decided under. */
    policy: PolicyItem[];
    tabs: {
        key: 'submitted' | 'decided';
        count: number;
        link: RouteLink;
    }[];
    entries: InvestorVerificationEntry[];
    /** Every case: verified of all started; the last 30 days' decisions and their mean time from submission. */
    stats: {
        completion_pct: string | null;
        verified: number;
        total: number;
        turnaround_hours: string | null;
        approved_30d: number;
        rejected_30d: number;
        /** Null until AML and sanctions screening (KYC-R04) is connected. */
        aml_alerts: number | null;
        sanctions_hits: number | null;
    };
    pagination: { next: RouteLink | null };
    review: InvestorVerificationReview | null;
};

/** A named tester a superadmin lets staging email; the server's own recipients are separate. */
export type StagingMailTester = {
    id: string;
    email: string;
    added_by: string;
    added_at: string;
    remove: RouteAction;
};

/** Superadmin's staging mail testers (`staging.mail.testers.manage`), on staging only. */
export type AdminStagingMailTestersProps = AdminFrameShellProps & {
    contract_version: 'staff-staging-mail-testers-v1';
    server_recipients: string[];
    testers: StagingMailTester[];
    add: RouteAction;
};

/* ------------------------------------------------------------------------------------------ */
/* Phase 2: Messaging History (staff-messaging-v1, BRS v2.0 NTF-R01)                           */
/* ------------------------------------------------------------------------------------------ */

/** The category a notice is filed under, after the design's message categories. */
export type MessageCategory =
    | 'onboarding'
    | 'wallet'
    | 'fundraising'
    | 'portfolio'
    | 'reporting'
    | 'security'
    | 'underwriting'
    | 'payments'
    | 'audits'
    | 'support'
    | 'general';

/**
 * One notice receipt: who it reached, the notice's kind (worded from the participant's catalog),
 * the automation that sent it, the channel and whether it arrived. A mail can fail; an in-app
 * notice is delivered when it is recorded.
 */
export type MessageHistoryRow = {
    id: string;
    recipient: string;
    recipient_kind: 'person' | 'business';
    kind: InvestorNotice['kind'];
    category: MessageCategory;
    source: 'automation';
    channel: 'in_app' | 'mail';
    status: 'delivered' | 'sent' | 'failed';
    at: string;
};

/** Who an automation or email template reaches. */
export type MessageAudience = 'investor' | 'business' | 'auditor' | 'everyone';

/** One notice the platform sends as its event happens, on these channels. */
export type MessageAutomation = {
    kind:
        | InvestorNotice['kind']
        | 'security.payout_account_linked'
        | 'security.payout_account_unlinked'
        | 'support.complaint_resolved';
    category: MessageCategory;
    audience: MessageAudience;
    channels: ('in_app' | 'mail')[];
};

/** One transactional email template, by folder and name. */
export type MessageTemplate = {
    key:
        | 'account.new_device_sign_in'
        | 'account.one_time_code'
        | 'account.reset_password'
        | 'account.verify_email'
        | 'auditor.accreditation_decided'
        | 'auditor.job_offered'
        | 'business.application_decided'
        | 'business.disbursement_sent'
        | 'business.raise_expired'
        | 'business.repayment_reminder'
        | 'business.reporting_window_open'
        | 'investor.investment_confirmed'
        | 'investor.payment_delayed'
        | 'investor.payout_received'
        | 'investor.raise_expired'
        | 'investor.report_published'
        | 'investor.verification_decided'
        | 'investor.wallet_transfer_completed';
    audience: MessageAudience;
};

export type AdminMessagingProps = AdminFrameShellProps & {
    contract_version: 'staff-messaging-v1';
    server_time: string;
    history: MessageHistoryRow[];
    automations: MessageAutomation[];
    templates: MessageTemplate[];
    /** The next page of older receipts, or null when this page holds the oldest. */
    older: RouteLink | null;
    /** Back to the newest receipts, sent only on an older page. */
    newest: RouteLink | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Phase 2: System Health, `staff-system-health-v1`                                             */
/* ------------------------------------------------------------------------------------------ */

export type StaffSystemHealthPageContract = {
    contract_version: 'staff-system-health-v1';
    staff_access_version: 'staff-access-v1';
    server_time: string;
    allowed_actions: never[];
};

export type HealthStatus = 'critical' | 'watch' | 'healthy' | 'untracked';

/** Each signal's readings, as the server records them; untracked signals carry none. Only a signal that needs a look links to the page that resolves it. */
export type HealthSignal =
    | {
          key: 'ledger';
          status: 'critical' | 'watch' | 'healthy';
          facts: { accounts: number; unmatched: number; breaks: number };
          action: RouteLink | null;
      }
    | {
          key: 'capital';
          status: 'healthy';
          facts: { amount: string; raises: number };
          action: null;
      }
    | {
          key: 'queue';
          status: 'watch' | 'healthy';
          facts: { pending: number };
          action: RouteLink | null;
      }
    | {
          key: 'events';
          status: 'watch' | 'healthy';
          facts: { last_hour: number; total: number };
          action: null;
      }
    | {
          key: 'frozen';
          status: 'watch' | 'healthy';
          facts: { accounts: number };
          action: RouteLink | null;
      }
    | {
          key: 'default_rate' | 'arrears' | 'outstanding' | 'treasury';
          status: 'untracked';
          facts: never[];
          action: null;
      };

export type AdminSystemHealthProps = AdminFrameShellProps &
    StaffSystemHealthPageContract & {
        /** How many tracked signals are critical or on watch. */
        overall: { critical: number; watch: number };
        /** Worst first: critical, watch, healthy, then the untracked signals. */
        signals: HealthSignal[];
    };
