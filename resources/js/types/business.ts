import type { Money } from './money';
import type { RouteAction, RouteLink } from './routing';

/**
 * Business app page contracts (Phase 1B). Every figure is a server fact; the client only formats
 * and arranges it. Proposed for the shared Resource schemas in rozine-rw/rozine#96.
 */

/** The four published rating bands (brand system 03); the word leads, the number supports. */
export type RatingBand = 'strong' | 'stable' | 'weak' | 'distressed';

export type BusinessRating = {
    band: RatingBand;
    /** The published score on the five-point scale, already formatted by the engine: "4.8". */
    score: string;
};

export type BusinessIdentity = {
    name: string;
    /** RDB company code — never a tax identifier (BRS AC-9). */
    company_code: string;
    industry: string;
    district: string;
};

export type NoteStatus =
    | 'draft'
    | 'active'
    | 'funded'
    | 'repaying'
    | 'completed'
    | 'failed';

export type BusinessNoteSummary = {
    id: string;
    title: string;
    status: NoteStatus;
    /** Creation or publication time, ISO 8601. Absent for drafts. */
    created_at: string | null;
    funded_pct: number;
    investors: number;
    raised: Money;
    target: Money;
    link: RouteLink;
    /** Drafts resume where they were left. */
    resume?: RouteLink;
};

export type LiveRaise = {
    title: string;
    funded_pct: number;
    investors: number;
    raised: Money;
    target: Money;
    link: RouteLink;
};

/** What needs the business now, in the order the server ranks it. */
export type BusinessTodo =
    | {
          kind: 'application_approved';
          title: string;
          fee: Money;
          link: RouteLink;
      }
    | {
          kind: 'application_declined';
          title: string;
          reason: string | null;
          link: RouteLink;
      }
    | {
          kind: 'disbursement_ready';
          gross: Money;
          note_title: string;
          link: RouteLink;
      }
    | {
          kind: 'audit_window';
          /** First day of the audited month, ISO 8601. */
          month: string;
          window_open: boolean;
          days_left: number;
          /** When the report is due sealed, ISO 8601. */
          sealed_by: string;
          link: RouteLink;
      }
    | {
          kind: 'repayment_due';
          amount: Money;
          note_title: string;
          due_on: string;
          link: RouteLink;
      };

export type BusinessCapital = {
    raised: Money;
    investors: number;
    repaid: Money;
    /** Share of scheduled repayments made on time, or null before the first is due. */
    on_time_pct: number | null;
    active_notes: number;
};

export type BusinessHomeProps = {
    business: BusinessIdentity;
    rating: BusinessRating | null;
    wallet: { available: Money };
    unread_notifications: number;
    live_raise: LiveRaise | null;
    today: BusinessTodo[];
    capital: BusinessCapital;
    notes: BusinessNoteSummary[];
    headroom: Money | null;
    links: BusinessAppLinks & {
        wallet: RouteLink;
        deposit: RouteLink;
        withdraw: RouteLink;
        notifications: RouteLink;
        rating: RouteLink;
        apply: RouteLink;
    };
};

/** Where the Business shell's tabs and launcher link go. */
export type BusinessAppLinks = {
    home: RouteLink;
    reports: RouteLink;
    profile: RouteLink;
    launcher: RouteLink;
};

/* ------------------------------------------------------------------------------------------ */
/* Raise application (MVP-BUSINESS-SCR-02, design "RNP" wizard L349–629)                       */
/* ------------------------------------------------------------------------------------------ */

export type ApplyStep = 'business' | 'raise' | 'review' | 'submitted';

export type UseOfFunds =
    | 'inventory'
    | 'expansion'
    | 'equipment'
    | 'hiring'
    | 'working_capital'
    | 'other';

export type TermMonths = 3 | 4 | 5 | 6;

/** The saved draft. It resumes from the server, so a cold reload never loses accepted input. */
export type ApplicationDraft = {
    id: string;
    revision: number;
    title: string;
    target: Money | null;
    term_months: TermMonths | null;
    use_of_funds: UseOfFunds[];
    story: string;
};

export type FinancialYear = {
    year: number;
    revenue: Money;
    costs: Money;
    net_profit: Money;
};

/** Step 1: what verified evidence says about the business. Never edited here. */
export type ApplicationEvidence = {
    business: BusinessIdentity & {
        established_year: number | null;
        officers: { role: 'ceo' | 'board_chair'; name: string }[];
    };
    verified: { registry: boolean; statements: boolean };
    rating: BusinessRating | null;
    totals: { revenue: Money; costs: Money; net_profit: Money };
    years: FinancialYear[];
    existing_debt: Money;
    debt_verified: boolean;
    capacity: Money | null;
};

/**
 * The server's quote for the requested principal and term (contract AC-01). Every figure is the
 * engine's; the page shows it, never recomputes it. A refusal carries a stable code and the
 * server's own explanation.
 */
export type ApplicationQuote =
    | {
          status: 'ready';
          policy_version: string;
          principal: Money;
          term_months: TermMonths;
          /** Flat total return over the whole term, one decimal: "12.1". Not an APR. */
          rate_pct: string;
          interest: Money;
          total: Money;
          monthly: Money;
          units: number;
          unit_price: Money;
          reserve: Money | null;
          rate_basis: {
              band: RatingBand | null;
              floor_pct: string;
              cap_pct: string;
              term_premium_pct: string;
          };
      }
    | {
          status: 'refused';
          code:
              | 'CAPACITY_EXCEEDED'
              | 'CAPACITY_BELOW_MINIMUM'
              | 'DSCR_BELOW_CUTOFF'
              | 'POLICY_INPUT_REQUIRED';
          message: string;
      };

export type AcceptanceDocument = {
    kind: 'terms' | 'privacy';
    version: string;
    /** The key clauses, as the legal owner summarises them for this version. */
    summary: { heading: string; body: string }[];
};

/** Step 3: what the business signs, and what falls due on approval. */
export type ApplicationAcceptance = {
    documents: AcceptanceDocument[];
    fee_on_approval: Money;
};

export type TimelineStage = {
    stage: 'submitted' | 'under_review' | 'approved' | 'published';
    state: 'done' | 'current' | 'pending';
};

export type ApplicationSubmission = {
    note_id: string;
    timeline: TimelineStage[];
};

export type BusinessApplyProps = {
    step: ApplyStep;
    application: ApplicationDraft;
    evidence: ApplicationEvidence;
    quote: ApplicationQuote | null;
    acceptance: ApplicationAcceptance;
    submission: ApplicationSubmission | null;
    /** Home, drawn beneath the sheet on a wide screen. */
    home: BusinessHomeProps;
    links: { close: RouteLink; back: RouteLink; next: RouteLink | null };
    actions: { save: RouteAction; submit: RouteAction };
};
