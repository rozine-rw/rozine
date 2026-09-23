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

/* ------------------------------------------------------------------------------------------ */
/* Listing (design "Publish to the Investor feed" sheet L2090–2120)                             */
/* ------------------------------------------------------------------------------------------ */

export type PaymentSourceKey = 'wallet' | 'mtn' | 'airtel' | 'card';

export type BusinessPublishProps = {
    home: BusinessHomeProps;
    application: { id: string; title: string; target: Money };
    /** The listing fee the server will charge. RWF 0 while CFG-01 waives it for the MVP. */
    fee: Money;
    /** How the fee can be paid; empty when there is nothing to pay. */
    sources: { key: PaymentSourceKey; detail: string }[];
    links: { close: RouteLink };
    actions: { publish: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Registration onboarding (MVP-BUSINESS-SCR-09 profile set-up, design L1569–1715)              */
/* ------------------------------------------------------------------------------------------ */

export type OnboardingStep = 'confirm' | 'documents' | 'bank' | 'finish';

/** What the Rwanda Development Board holds for this company, as the server fetched it. */
export type RegistryRecord = {
    name: string;
    company_code: string;
    legal_form: string;
    registered_on: string;
    status: 'active' | 'dormant' | 'deregistered';
    staff: number | null;
    address: string;
    /** The registry's own activity description, e.g. "Logistics & Freight Transport". */
    category: string;
    management: { name: string; role: string }[];
    shareholders: { name: string; share_pct: number }[];
};

export type ShowcaseSlot =
    | 'products'
    | 'facilities'
    | 'team'
    | 'operations'
    | 'customers'
    | 'impact'
    | 'brand';

export type OnboardingDocuments = {
    certificate: {
        status: 'required' | 'uploaded' | 'verified';
        number: string | null;
        file_name: string | null;
    };
    logo_url: string | null;
    photos: { slot: ShowcaseSlot; url: string | null }[];
};

export type LinkedBankAccount = {
    bank_name: string;
    /** Masked by the server: "Business account ····2231". */
    masked_number: string;
};

export type BusinessOnboardingProps = {
    step: OnboardingStep;
    registry: RegistryRecord;
    industry: string;
    industries: { value: string; label: string }[];
    documents: OnboardingDocuments;
    banks: { code: string; name: string }[];
    bank_account: LinkedBankAccount | null;
    signatories: { id: string; name: string; role: string }[];
    /** From the company's verified mandate (D-64); never assumed to be two. */
    signatories_required: number;
    contact_masked: string;
    /** `next` is the following step; the documents and bank steps move on only once their record is on file. */
    links: { back: RouteLink; next: RouteLink };
    actions: {
        confirm: RouteAction;
        certificate: RouteAction;
        upload: RouteAction;
        bank: RouteAction;
        finish: RouteAction;
    };
};

/* ------------------------------------------------------------------------------------------ */
/* Note progress (MVP-BUSINESS-SCR-04 raise progress, design L632–709)                          */
/* ------------------------------------------------------------------------------------------ */

/**
 * Where a published note stands, as the server records it. The design draws one dashboard for
 * every note; its tiles and tracker follow the phase.
 */
export type NoteProgress =
    | {
          phase: 'raising';
          raised: Money;
          target: Money;
          /** Still to raise, from the server's ledger — the client never subtracts. */
          remaining: Money;
          funded_pct: number;
          investors: number;
          /** When the listing closes if it has not filled, ISO date. */
          closes_on: string;
          days_left: number;
      }
    | {
          phase: 'funded';
          raised: Money;
          investors: number;
          funded_on: string;
      }
    | {
          phase: 'repaying';
          outstanding: Money;
          payments_made: number;
          payments_total: number;
          repaid_pct: number;
          repaid: Money;
          remaining: Money;
          remaining_months: number;
          investors: number;
          health: 'on_time' | 'late';
          next_payment: {
              amount: Money;
              due_on: string;
              days_until: number;
          } | null;
      }
    | {
          /** The listing did not fill in time: every committed franc went back, without fee. */
          phase: 'expired';
          raised: Money;
          target: Money;
          investors: number;
          closed_on: string;
      };

export type NotePhoto = {
    /** Null until the business adds the photo; the design's placeholder tile shows instead. */
    url: string | null;
    caption: string;
};

export type NotePerformanceMonth = {
    /** First day of the month, ISO date. */
    month: string;
    /** Audited revenue for the month. */
    revenue: Money;
    paid_on_time: boolean;
};

/** One range of the trend, with the high and low the server read off it. */
export type NotePerformanceRange = {
    months: NotePerformanceMonth[];
    high: Money;
    low: Money;
};

export type NoteInvestor = {
    initials: string;
    name: string;
    kind: 'individual' | 'institution' | 'sacco';
    amount: Money;
};

export type BusinessNoteProps = {
    home: BusinessHomeProps;
    note: {
        id: string;
        title: string;
        status: NoteStatus;
        progress: NoteProgress;
        photos: NotePhoto[];
        /** Audited history, present once the note has any (design `perfHasData`). */
        performance: {
            six_months: NotePerformanceRange;
            twelve_months: NotePerformanceRange;
        } | null;
        recent_investors: NoteInvestor[];
    };
    /** `pay` and `investors` stay null until those screens are open to the business. */
    links: {
        close: RouteLink;
        pay: RouteLink | null;
        investors: RouteLink | null;
    };
};
