import type { Money } from './money';
import type {
    OperationCommand,
    OperationResource as SharedOperationResource,
} from './operation';
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
    /**
     * RDB company code — never a tax identifier (BRS AC-9). Null for a verified sole trader, who
     * has no RDB registration; the page then shows no company line at all, never a stand-in.
     */
    company_code: string | null;
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
        /** Resumes the business's open draft by GET; null when there is none. */
        apply: RouteLink | null;
    };
    /**
     * Starts a raise when there is no open draft and the server allows `application.create`
     * (option (a) on #96); null otherwise. One open draft per business is the server's rule.
     */
    create_application: CreateApplicationEntry | null;
};

/** The `application.create` command Home posts to start a raise (business-application-v1). */
export type CreateApplicationEntry = {
    action: RouteAction;
    /** The operation lookup: its url holds the literal `{request_id}` token. */
    operation: RouteLink;
    identity_context_revision: number;
    /** The revision the create expects for the business's applications (0 before the first). */
    expected_revision: number;
};

/** What a completed `application.create` returns: at least the page to continue to. */
export type CreateApplicationData = { next: RouteLink };

/** A business's open or submitted application, as the role landing page lists it. */
export type BusinessApplicationSummary = {
    /** Opaque; never parsed. */
    id: string;
    status: 'draft' | 'submitted';
    /** The saved resume pointer, e.g. `raise`. */
    step: string;
    revision: number;
    link: RouteLink;
};

/** One business the current person may act for on the Business role landing page (#96). */
export type BusinessApplicationsEntry = {
    /** Opaque; never parsed. */
    business_id: string;
    name: string;
    allowed_actions: 'application.create'[];
    application: BusinessApplicationSummary | null;
    actions: { create: RouteAction | null };
};

/**
 * The Business role landing page's way into Apply (`identity/role-home`, current authority only):
 * each business the person may act for, its application, and whether a raise may be started. No
 * financial facts are carried here.
 */
export type BusinessApplications = {
    identity_context_revision: number;
    entries: BusinessApplicationsEntry[];
    /** The create lookup; its url holds the literal `{request_id}` token. */
    operation: RouteLink;
    pagination: { next: RouteLink | null };
};

/** Where the Business shell's tabs and launcher link go. */
export type BusinessAppLinks = {
    home: RouteLink;
    reports: RouteLink;
    profile: RouteLink;
    launcher: RouteLink;
};

/**
 * The shell navigation a page may narrow: a destination the server does not open to the current
 * person is null and hidden, never faked.
 */
export type BusinessShellLinks = {
    home: RouteLink;
    launcher: RouteLink;
    reports: RouteLink | null;
    profile: RouteLink | null;
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

/**
 * An exact fraction as the server publishes it, as integer decimal strings (e.g. 1/200). The
 * client shows it as written and never turns it into a rounded percentage.
 */
export type Ratio = {
    numerator: string;
    denominator: string;
};

/**
 * The commands this page may issue for the current person, scoped by the server
 * (business-application-v1 point 1). A command that is not listed is never offered.
 */
export type ApplicationAllowedAction =
    | 'application.save'
    | 'application.evaluate'
    | 'application.submit';

/** The saved draft. It resumes from the server, so a cold reload never loses accepted input. */
export type ApplicationDraft = {
    id: string;
    /** The application revision every command sends back as `expected_revision`. */
    revision: number;
    title: string;
    target: Money | null;
    term_months: TermMonths | null;
    use_of_funds: UseOfFunds[];
    story: string;
};

/**
 * A verified year. A fact the evidence does not hold is null and shows as unavailable, never 0.
 * Every figure is the server's total for the months of this year inside the evidence window.
 */
export type FinancialYear = {
    year: number;
    /** How many months of this year the evidence covers (1–12); a partial year says so. */
    months: number;
    revenue: Money | null;
    costs: Money | null;
    /** Net operating cash for those months — not an accounting profit. */
    net_profit: Money | null;
};

/** The evidence window the totals cover, as the server states it (`YYYY-MM` months). */
export type EvidencePeriod = {
    from_month: string;
    through_month: string;
    months: number;
};

/**
 * Whether the verified record lets this business raise now (crosswalk MVP-BUSINESS-SCR-02-ST-02).
 * The server owns the rule (first raise: 36 complete consecutive verified months; a repeat raise
 * needs the passed original baseline, a fully settled on-time note, audited gaps and the automated
 * collection mandate before 12 months apply) and explains a refusal in its own `message`.
 */
export type ApplicationEligibility =
    | { status: 'eligible' }
    | { status: 'ineligible'; code: string; message: string };

/**
 * An officer on the verified entity mandate. `role` is the server's label for the mandate role;
 * no label, on its own, says the person may sign for the business.
 */
export type MandateOfficer = {
    name: string;
    role: string;
};

/** Step 1: what verified evidence says about the business. Never edited here. */
export type ApplicationEvidence = {
    /** The evidence version an evaluation expects (`evidence_version`). */
    version: string;
    eligibility: ApplicationEligibility;
    business: BusinessIdentity & {
        established_year: number | null;
        officers: MandateOfficer[];
    };
    verified: { registry: boolean; statements: boolean };
    rating: BusinessRating | null;
    totals: {
        revenue: Money | null;
        costs: Money | null;
        net_profit: Money | null;
    };
    /** The window the totals and years cover; null when the server cannot state one. */
    period: EvidencePeriod | null;
    years: FinancialYear[];
    existing_debt: Money | null;
    debt_verified: boolean;
    capacity: Money | null;
};

/** Why an evaluation made no offer (business-application-v1 point 4). */
export type QuoteDenialCode =
    | 'UNDERWRITING_EVIDENCE_REQUIRED'
    | 'DSCR_BELOW_CUTOFF'
    | 'CAPACITY_BELOW_MINIMUM'
    | 'EXPOSURE_LIMIT'
    | 'POLICY_INPUT_REQUIRED'
    | 'RESTRICTION_ACTIVE';

/** One instalment of the repayment schedule; the last one carries any residual. */
export type ScheduleInstalment = {
    instalment: number;
    amount: Money;
};

/**
 * The server's immutable quote, created by `application.evaluate` and only read on a page load.
 * Every figure is the engine's; the page shows it, never recomputes it. A refusal carries a
 * stable code and the server's own explanation, and no numeric offer at all.
 */
export type ApplicationQuote =
    | {
          status: 'ready';
          quote_id: string;
          quote_revision: number;
          policy_version: string;
          evidence_version: string;
          calculation_version: string;
          mandate_version: string;
          /** What the business asked for, as saved in the draft. */
          requested_principal: Money;
          /**
           * The principal this quote is for: the offer, or a lower amount the business chose to
           * accept (evaluated again with `accepted_principal`). On the RWF 5,000 note grid.
           */
          principal: Money;
          /** The most the evaluation offers: the request resized and quantized to the note grid. */
          offered_principal: Money;
          term_months: TermMonths;
          /** Flat total return over the whole term, one decimal: "12.1". Not an APR. */
          rate_pct: string;
          interest: Money;
          total: Money;
          /** Whole notes as a decimal integer string: "6783". */
          units: string;
          unit_price: Money;
          reserve: Money | null;
          schedule: ScheduleInstalment[];
          /** Stable codes for how the offer was reached (e.g. resized); support and audit only. */
          reason_codes: string[];
          rate_basis: {
              band: RatingBand | null;
              floor_pct: string;
              cap_pct: string;
              /** The exact term premium, never a rounded coefficient. */
              term_premium: Ratio;
          };
      }
    | {
          status: 'refused';
          code: QuoteDenialCode;
          message: string;
      };

export type AcceptanceDocument = {
    kind: 'terms' | 'privacy';
    version: string;
    /** Hash of the exact text shown, echoed back on acceptance. */
    sha256: string;
    /** The key clauses, as the legal owner summarises them for this version. */
    summary: { heading: string; body: string }[];
    /**
     * The complete immutable text `sha256` hashes, shown in full as plain text (line breaks kept)
     * beside the summary before acceptance, so the hash binds text the signer can read.
     */
    body: string;
};

/** A risk disclosure in the server's words, at an immutable version. */
export type AcceptanceDisclosure = {
    key: string;
    version: string;
    sha256: string;
    text: string;
};

export type AcceptanceSigner = {
    party_id: string;
    name: string;
    /** The server's label for the signer's mandate role. */
    role: string;
    state: 'signed' | 'pending';
    /** When the signature was recorded, ISO 8601; null while pending. */
    signed_at: string | null;
};

/**
 * Step 3: what the business signs, who must sign it, and what falls due on approval. The signers
 * come from the effective entity mandate: every one of them accepts the same financial, document
 * and mandate versions, one signature never stands in for another, and a signature against an
 * older version does not count on the current one. `allowed_actions` alone decides whether the
 * current person may sign.
 */
export type ApplicationAcceptance = {
    documents: AcceptanceDocument[];
    disclosures: AcceptanceDisclosure[];
    fee_on_approval: Money;
    /** The mandate version a signature pins. */
    mandate_version: string;
    signers: AcceptanceSigner[];
    /** How many signatures the mandate requires; never assumed to be two. */
    required_signatures: number;
    /** Server-derived: every required signature is recorded on the current versions. */
    signatures_complete: boolean;
};

export type TimelineStage = {
    stage: 'submitted' | 'under_review' | 'approved' | 'published';
    state: 'done' | 'current' | 'pending';
};

/** A submitted application. Submitting does not issue a note, so `note_id` stays null until one exists. */
export type ApplicationSubmission = {
    application_id: string;
    note_id: string | null;
    timeline: TimelineStage[];
};

export type ApplicationCommandName = 'save' | 'evaluate' | 'submit';

/**
 * The authorized slice of the application a command returns in `data`, so the page can show the
 * result at once and then refresh its remaining props. `next` is the page to continue to.
 */
export type ApplicationSnapshot = {
    application: ApplicationDraft;
    quote: ApplicationQuote | null;
    acceptance: ApplicationAcceptance;
    submission: ApplicationSubmission | null;
    next: RouteLink;
};

/**
 * The shared operation Resource (business-application-v1 points 2 and 7). `code` is the specific
 * outcome — `APPLICATION_SAVED`, `APPLICATION_EVALUATED`, `APPLICATION_SIGNATURE_RECORDED`,
 * `APPLICATION_SUBMITTED`, `OPERATION_PENDING`, or a persisted denial's own domain code. A
 * completed evaluation may still hold a refused quote.
 */
export type OperationResource = SharedOperationResource<ApplicationSnapshot>;

/** A command exactly as sent, kept whole so an uncertain outcome is looked up and retried unchanged. */
export type ApplicationCommand = OperationCommand<ApplicationCommandName> & {
    /**
     * A save that asks to advance the resume pointer (its `step` names the next step); the page
     * moves on to the authorized `next` once the server confirms. Autosaves never advance.
     */
    advance: boolean;
};

/**
 * Supplied only by local/testing synthetic fixture previews: seeds the outcome the page otherwise
 * reaches only after a live command, so it can be reviewed. The server never sends it.
 */
export type ApplyPreviewOutcome =
    | { kind: 'unconfirmed'; command: ApplicationCommand }
    | { kind: 'refused'; code: string };

export type BusinessApplyProps = {
    contract_version: 'business-application-v1';
    business_id: string;
    identity_context_revision: number;
    /** When the server rendered these facts, ISO 8601. */
    server_time: string;
    allowed_actions: ApplicationAllowedAction[];
    /** The server's resume pointer. */
    step: ApplyStep;
    application: ApplicationDraft;
    evidence: ApplicationEvidence;
    quote: ApplicationQuote | null;
    acceptance: ApplicationAcceptance;
    submission: ApplicationSubmission | null;
    /**
     * Home, drawn beneath the sheet on a wide screen; null when the server sends no Home, in
     * which case the sheet opens over an empty backdrop with no stand-in balances.
     */
    home: BusinessHomeProps | null;
    /** The shell's navigation for this page, read instead of `home.links`. */
    shell_links: BusinessShellLinks;
    links: {
        close: RouteLink;
        /** Back to an earlier step as a view-step query; it never moves the stored pointer. */
        back: RouteLink;
        /**
         * The operation lookup. Its url holds the literal `{request_id}` token, which the page
         * replaces; the command name and `identity_context_revision` go as its query.
         */
        operation: RouteLink;
    };
    actions: {
        save: RouteAction;
        evaluate: RouteAction;
        submit: RouteAction;
    };
    preview_outcome?: ApplyPreviewOutcome;
    /**
     * Another submitted application of this business still under review, which blocks this
     * draft's evaluation and submission (one at a time in C2); null otherwise.
     */
    pending_application: { id: string; link: RouteLink } | null;
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
    /**
     * Publishing stays closed until an approved, fully signed application exists and the listing
     * transaction lands in checkpoint 3; until the server lists `application.publish`, the sheet
     * explains why instead of offering the command.
     */
    allowed_actions: 'application.publish'[];
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

/* ------------------------------------------------------------------------------------------ */
/* Reports (MVP-BUSINESS-SCR-06, design L942–1002, report sheet L1897–1939)                    */
/* ------------------------------------------------------------------------------------------ */

export type ReportStatus = 'verified' | 'in_audit' | 'archived';

/** The month's health as the audit recorded it. */
export type ReportHealth = 'healthy' | 'watch' | 'at_risk';

export type ReportPeriod = {
    kind: 'monthly' | 'annual';
    /** First day of the month or year, ISO date. */
    starts_on: string;
};

export type ReportRow = {
    id: string;
    period: ReportPeriod;
    status: ReportStatus;
    /** Filed figures; absent while the audit is open. */
    inflow: Money | null;
    health: ReportHealth | null;
    auditor: string;
    /** While in audit: the day the CPA must seal it by. */
    seal_by: string | null;
    /** The report sheet, or audit prep while the audit is open. */
    link: RouteLink;
};

/** One filed figure. The engine owns every value; the client only formats it. */
export type ReportFigure =
    | { key: 'cash_inflow' | 'cash_outflow' | 'net_position'; value: Money }
    | { key: 'net_margin'; percent: string }
    | { key: 'days_cash_on_hand'; count: number }
    | {
          key: 'quarters_above_floor';
          count: number;
          of: number;
          floor_percent: string;
      };

export type ReportDetail = {
    id: string;
    period: ReportPeriod;
    archived: boolean;
    /** Published (monthly) or filed (annual), ISO date. */
    published_on: string;
    /** Investors who opened it; null for an archived filing. */
    seen_by: number | null;
    inflow: Money;
    outflow: Money;
    health: ReportHealth;
    recap: string;
    figures: ReportFigure[];
    auditor: { name: string; initials: string; note: string };
};

export type BusinessReportsProps = {
    reports: Record<ReportStatus, ReportRow[]>;
    /** The report open in the sheet, addressed by URL. */
    report: ReportDetail | null;
    /** Audit-cycle policy the guide quotes: the day audits seal by and the co-sign window. */
    policy: { seal_day: number; cosign_day: number };
    links: BusinessAppLinks & { close: RouteLink };
};

/* ------------------------------------------------------------------------------------------ */
/* Profile (MVP-BUSINESS-SCR-09 company, signatories, documents; design L1460–1479, L1718–1891) */
/* ------------------------------------------------------------------------------------------ */

export type ProfileSection = 'company' | 'linked' | 'terms' | 'privacy';

export type CompanyProfile = {
    /** The RDB-registered name: shown, never edited here. */
    name: string;
    email: string;
    /** Ten digits, as the business typed it: "0788123456". */
    phone: string;
    address: {
        province: string;
        district: string;
        sector: string;
        cell: string;
        street: string;
    };
    certificate: {
        number: string;
        status: 'verified' | 'expired';
        expires_on: string | null;
    };
    signatories: { name: string; role: string }[];
    /** From the verified mandate (D-64); never assumed to be two. */
    signatories_required: number;
};

export type LinkedAccount = {
    id: string;
    kind: 'wallet' | 'bank' | 'mobile_money';
    name: string;
    /** Masked by the server: "Business account ····2231". */
    detail: string;
    /** Null where only Rozine support can change it, as for the payout bank. */
    unlink: RouteAction | null;
};

/** A versioned legal document, as the business accepted it. */
export type LegalDocument = {
    version: string;
    updated_on: string;
    sections: { heading: string; body: string }[];
};

export type BusinessProfileProps = {
    business: {
        name: string;
        address_line: string;
        verified: boolean;
        rating: BusinessRating;
    };
    section: ProfileSection;
    /** True at the bare Profile URL: a phone shows only the menu, a wide screen opens `section`. */
    landing: boolean;
    company: CompanyProfile;
    /** Provinces and the districts in each, for the address pickers. */
    provinces: {
        value: string;
        label: string;
        districts: { value: string; label: string }[];
    }[];
    linked: { accounts: LinkedAccount[]; add: RouteLink | null } | null;
    legal: LegalDocument | null;
    links: BusinessAppLinks & {
        back: RouteLink;
        sections: Record<ProfileSection, RouteLink>;
        sign_out: RouteAction;
    };
    actions: { save_company: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Wallet (MVP-BUSINESS-SCR-08, design L1315–1457, transaction detail L2378–2413)              */
/* ------------------------------------------------------------------------------------------ */

export type WalletFlow = 'deposit' | 'withdraw';

export type WalletMethod = {
    key: string;
    kind: 'mtn' | 'airtel' | 'bank';
    name: string;
};

/**
 * The server's price for the amount and method on screen: the fee, and either what reaches the
 * account (withdraw) or the balance after (deposit). A refusal carries the reason instead.
 */
export type WalletQuote =
    | {
          status: 'ready';
          flow: WalletFlow;
          fee: Money;
          receive: Money;
          new_balance: Money;
      }
    | { status: 'refused'; flow: WalletFlow; message: string };

export type WalletTransactionKind =
    | 'deposit'
    | 'withdrawal'
    | 'disbursement'
    | 'repayment'
    | 'services_fee'
    | 'application_fee';

export type WalletTransaction = {
    id: string;
    kind: WalletTransactionKind;
    direction: 'in' | 'out';
    /** The bank, network or note the money moved through: "Bank of Kigali", "Fleet Expansion". */
    via: string;
    amount: Money;
    occurred_at: string;
    status: 'completed' | 'pending' | 'failed';
    /** Why a transfer failed, so it can be inspected before any retry. */
    failure_reason: string | null;
    reference: string;
    balance_before: Money;
    balance_after: Money;
    /** Withdrawals only: the fee and what reached the account. */
    charges: { gross: Money; fee: Money; net: Money } | null;
};

export type BusinessWalletProps = {
    wallet: { available: Money; status: 'active' | 'frozen' };
    methods: Record<WalletFlow, WalletMethod[]>;
    quick_amounts: Money[];
    /** Quoted for the `flow`, `amount` and `method` the page last asked for. */
    quote: WalletQuote | null;
    /** Today in Kigali, ISO date, for the quick date ranges. */
    today: string;
    transactions: {
        items: WalletTransaction[];
        from: string | null;
        to: string | null;
    };
    links: BusinessAppLinks & {
        back: RouteLink;
        wallet: RouteLink;
        export_pdf: RouteLink;
        export_csv: RouteLink;
    };
    actions: Record<WalletFlow, RouteAction>;
};

/* ------------------------------------------------------------------------------------------ */
/* Rating & financial health (MVP-BUSINESS-SCR-03, design L811–939)                           */
/* ------------------------------------------------------------------------------------------ */

/** One of the five limits on a raise; the engine marks the one that binds. */
export type CapacityLimit = { value: Money; binding: boolean } & (
    | { key: 'capacity'; ebitda: Money; multiplier: string }
    | { key: 'revenue_share'; percent: string; revenue: Money }
    | { key: 'book_share'; percent: string; book: Money }
    | { key: 'phase_cap'; phase: string; book_under: Money }
    | { key: 'policy_max' }
);

/** How the engine sized this business's capacity, exactly as it computed it. */
export type CapacitySizing = {
    cash_per_month: Money;
    net_margin_percent: string;
    depreciation_percent: string;
    multiplier: string;
    cover_tier: 'none' | 'cover1x' | 'cover2x';
    carry: Money;
    stock: { state: 'verified' | 'indicative' | 'none'; value: Money | null };
    limits: CapacityLimit[];
    approved: Money;
    headroom: Money;
    tiers: {
        multiplier: string;
        principal: Money;
        applied: boolean;
        needs_cover: string | null;
        your_cover: string;
    }[];
};

export type RatingFactorKey =
    | 'financial_health'
    | 'repayment_history'
    | 'statement_consistency'
    | 'growth_outlook';

export type BusinessRatingProps = {
    home: BusinessHomeProps;
    /** Null until the first audited report is rated. */
    rating: BusinessRating | null;
    /** Why the engine declined to rate or raise capacity, in its own words. */
    refusal: { reasons: string[] } | null;
    /** The rating at the last audit and what has moved it since, when it has drifted. */
    drift: {
        audited: BusinessRating;
        moves: { reason: string; delta: string }[];
    } | null;
    /** Published factor scores out of 100, when the engine publishes them. */
    factors: { key: RatingFactorKey; score: number }[] | null;
    sizing: CapacitySizing | null;
    financials: {
        avg_monthly_revenue: Money;
        ebitda_month: Money;
        net_margin_percent: string;
        outstanding: Money;
    };
    links: { close: RouteLink; raise: RouteLink | null };
};

/* ------------------------------------------------------------------------------------------ */
/* Repayments (MVP-BUSINESS-SCR-05, design L1060–1262)                                         */
/* ------------------------------------------------------------------------------------------ */

export type RepaymentSource = {
    key: string;
    kind: 'wallet' | 'bank' | 'mobile_money';
    name: string;
    /** "Balance RWF 12,383,800", "Bank of Kigali ····2231", masked by the server. */
    detail: string;
};

export type BusinessRepaymentsProps = {
    home: BusinessHomeProps;
    note: { id: string; title: string };
    progress: {
        repaid_pct: number;
        repaid: Money;
        payments_made: number;
        payments_total: number;
        remaining: Money;
        remaining_months: number;
        total: Money;
    };
    /**
     * This month's instalment. `days` counts to the due date, or since it when overdue. After a
     * payment ahead there is nothing due this month; a defaulted note owes the whole balance.
     */
    this_month: {
        state: 'due' | 'overdue' | 'paid' | 'defaulted';
        amount: Money;
        due_on: string;
        days: number;
        /** The day of the month every instalment falls due: the disbursement day. */
        due_day: number;
    };
    sources: RepaymentSource[];
    pay_ahead: {
        options: (
            | { key: 'next'; months: number; amount: Money }
            | { key: 'full'; amount: Money; last: boolean }
        )[];
        /** The most the business can pay: what is left on the note. */
        max: Money;
    } | null;
    schedule: {
        due_on: string;
        amount: Money;
        status: 'paid' | 'due' | 'overdue' | 'upcoming';
    }[];
    /** The approved late-fee ladder, with the total owed at each step for this instalment. */
    late_ladder: {
        step: 'due_day' | 'day_7' | 'day_30';
        fee_percent: string;
        total: Money;
    }[];
    /** Set after a payment is processed: the design's "Payment processed" screen. */
    receipt: {
        amount: Money;
        investors: number;
        outstanding: Money;
        payments_made: number;
        payments_total: number;
    } | null;
    links: {
        close: RouteLink;
        defer: RouteLink | null;
        review: RouteLink | null;
    };
    actions: { pay: RouteAction; pay_ahead: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Audit prep (MVP-BUSINESS-SCR-07, design L1006–1057)                                         */
/* ------------------------------------------------------------------------------------------ */

export type BusinessAuditPrepProps = {
    home: BusinessHomeProps;
    audit: {
        /** First day of the month being audited, ISO date. */
        period: string;
        /** The prep window runs from the 20th to month-end; before that it is the next audit. */
        window_open: boolean;
        days_left: number;
        seal_by: string;
        /** The day co-signing closes for this month's report, ISO date. */
        cosign_by: string;
        /** True until the business has had its first audit. */
        first: boolean;
        /** Set when a new Audit Partner took over the file. */
        reassigned: { from: string; to: string } | null;
    };
    links: { close: RouteLink };
};
