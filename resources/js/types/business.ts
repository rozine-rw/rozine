import type {
    DepositIntent,
    DepositPolicy,
    DepositQuote,
    FundingMethod,
    WalletWithdrawal,
    WithdrawalTerms,
} from './investor';
import type { Money } from './money';
import type {
    OperationCommand,
    OperationResource as SharedOperationResource,
} from './operation';
import type { RouteAction, RouteLink } from './routing';
import type {
    Bps,
    BusinessCampaignLifecycle,
    C3PreviewOutcome,
    CampaignRestriction,
    Clock,
    CoarseInFlight,
    ComponentAmounts,
    InstalmentStatus,
    KigaliDate,
    LateFeeLadder,
    LateFeeStatus,
    LateFeeStep,
    Pagination,
    RaisingLifecycle,
    Receipt,
    ServicingState,
    Units,
} from './settlement';
import type { SupportCenter } from './support';

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
          /** The latest sealed audit report, waiting for the business's co-signatures. */
          kind: 'audit_cosign';
          report_kind: 'flash' | 'monthly';
          /** When the 24-hour window to co-sign or dispute closes; null when the report has none. */
          due_at: string | null;
          /** Whole hours left in that window, rounded up; null when there is none. */
          hours_left: number | null;
          link: RouteLink;
      }
    | {
          /** An open draft whose current offer still needs this person's signature. */
          kind: 'application_signature';
          title: string;
          /** The names of every required signatory still to sign, this person included. */
          pending: string[];
          link: RouteLink;
      }
    | {
          /** An open draft signed by part of the mandate, waiting for the people named. */
          kind: 'application_awaiting';
          title: string;
          pending: string[];
          link: RouteLink;
      }
    | {
          /** An open draft its evidence does not yet qualify, with the server's reason code. */
          kind: 'application_blocked';
          /** Null until the draft has a title. */
          title: string | null;
          code: string;
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
    /** A destination with no live route yet is null and hidden, never faked. */
    links: BusinessShellLinks & {
        wallet: RouteLink;
        deposit: RouteLink;
        withdraw: RouteLink | null;
        notifications: RouteLink | null;
        rating: RouteLink | null;
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

/**
 * Where a business's latest unamended sealed audit report stands for it (#124): `pending` — sealed
 * and waiting on the business; `published`; and, under the N6 policy (#126), `disputed` — the
 * business's dispute is with the CPA — and `escalated` — Rozine staff hold it.
 */
export type BusinessAuditReportStatus =
    | 'pending'
    | 'published'
    | 'disputed'
    | 'escalated';

/**
 * The business's way into its latest sealed audit report on the role landing page. The
 * destination rechecks publication authority, so this carries navigation only, no findings.
 */
export type BusinessAuditReportEntry = {
    /** Opaque; never parsed. */
    id: string;
    kind: 'flash' | 'monthly';
    status: BusinessAuditReportStatus;
    /** When its 24-hour review window closes; null for a report sealed before it had one. */
    due_at: string | null;
    link: RouteLink;
};

/** One business the current person may act for on the Business role landing page (#96). */
export type BusinessApplicationsEntry = {
    /** Opaque; never parsed. */
    business_id: string;
    name: string;
    /** This business's Home. */
    home: RouteLink;
    allowed_actions: 'application.create'[];
    application: BusinessApplicationSummary | null;
    actions: { create: RouteAction | null };
    /** The latest unamended sealed audit report; null when there is none, and nothing shows. */
    audit_report: BusinessAuditReportEntry | null;
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
    market: RouteLink;
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
    market: RouteLink | null;
    profile: RouteLink | null;
};

/**
 * Market (design L1263–1308): how the business's notes trade on the secondary market. There is no
 * secondary-market read yet, so the page carries its links only and measures nothing.
 */
export type BusinessMarketProps = {
    links: BusinessAppLinks;
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
    /**
     * How many verified statement months the applicable window needs (36 for a first raise, 12 for
     * a repeat) and how many are verified. Absent from applications submitted before it was sent.
     */
    requirement?: { months_required: number; months_verified: number };
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
    /** Absent on retained submissions made before C3 exposure reservation. */
    exposure_reservation_id?: string;
    note_id: string | null;
    timeline: TimelineStage[];
    /** Once staff approve (release) the application, the page that publishes it; null otherwise. */
    publish?: RouteLink | null;
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

/** How far Rozine has got reading one received statement; reading verifies nothing. */
export type StatementReading = 'reading' | 'read' | 'needs_review';

export type BusinessStatement = {
    id: string;
    filename: string;
    media_type: 'application/pdf' | 'text/csv';
    size_bytes: number;
    /** When it was received, ISO 8601. */
    received_at: string;
    reading: StatementReading;
    /** The original, as uploaded. */
    link: RouteLink;
};

/** The business's own bank and Mobile Money statements (BRS APP-B01). */
export type BusinessStatements = {
    /** Sent as `expected_revision` with the next upload. */
    revision: number;
    documents: BusinessStatement[];
    upload: RouteAction;
};

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
     * Home, drawn beneath the sheet on a wide screen. The web page always sends it; null (the
     * bearer read) opens the sheet over an empty backdrop with no stand-in balances.
     */
    home: BusinessHomeProps | null;
    /** The web page's statements, so step 1 can take the next upload; the bearer read leaves it out. */
    statements?: BusinessStatements;
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

export type ReportPeriod =
    | {
          kind: 'monthly' | 'annual';
          /** First day of the month or year, ISO date. */
          starts_on: string;
      }
    /** The pre-listing flash audit, which covers no single month. */
    | { kind: 'flash'; starts_on: null };

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
    /** Sealed and waiting for the business to co-sign or dispute it. */
    awaiting_signature: boolean;
    /** While it waits: when its co-sign window closes; null when the window has no end time. */
    cosign_by: string | null;
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
    /**
     * Audit-cycle policy the guide quotes: the day audits seal by. Null when no such day is
     * published: the guide then names no day. The co-sign window is always the BRS's 24 hours
     * after sealing (OD-07), so it is not a policy field.
     */
    policy: { seal_day: number } | null;
    links: BusinessAppLinks & { close: RouteLink };
};

/* ------------------------------------------------------------------------------------------ */
/* Profile (MVP-BUSINESS-SCR-09 company, signatories, documents; design L1460–1479, L1718–1891) */
/* ------------------------------------------------------------------------------------------ */

/** The design's Profile menu (Business.dc.html L5022–5028), in its order. */
export type ProfileSection =
    | 'company'
    | 'security'
    | 'permissions'
    | 'linked'
    | 'support'
    | 'terms'
    | 'privacy';

/** A mandate permission (`MandateAuthority::PERMISSIONS`). */
export type MandatePermission =
    | 'business.view'
    | 'application.create'
    | 'application.save'
    | 'application.evaluate'
    | 'application.sign'
    | 'report.cosign'
    | 'business.wallet.deposit'
    | 'business.wallet.withdraw'
    | 'repayment.pay'
    | 'business.profile.update';

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
    /** Null while no RDB certificate is held; the registration record shows instead. */
    certificate: {
        number: string;
        status: 'verified' | 'expired';
        expires_on: string | null;
    } | null;
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

/** A role a person holds on the business's verified mandate. */
export type MandateRole =
    | 'owner'
    | 'beneficial_owner'
    | 'controller'
    | 'director'
    | 'signatory'
    | 'representative';

/**
 * What the business's verified profile and current mandate hold, read-only. Live Profile shows
 * this while no contact, address or certificate record exists to edit.
 */
export type CompanyRegistration = {
    name: string;
    /** RDB company code; null for a verified sole trader, and no line is shown. */
    company_code: string | null;
    industry: string;
    district: string;
    established_year: number | null;
    /** Everyone on the mandate; `signatory` marks the people whose signature is required. */
    people: { name: string; roles: MandateRole[]; signatory: boolean }[];
    signatories_required: number;
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
        /** Null until a rating is published; no score is shown. */
        rating: BusinessRating | null;
    };
    section: ProfileSection;
    /** True at the bare Profile URL: a phone shows only the menu, a wide screen opens `section`. */
    landing: boolean;
    /** The editable contact record; null while none is held, and `registration` shows instead. */
    company: CompanyProfile | null;
    registration: CompanyRegistration | null;
    /** Provinces and the districts in each, for the address pickers. */
    provinces: {
        value: string;
        label: string;
        districts: { value: string; label: string }[];
    }[];
    /** The mandate's people, for Permissions & roles; nothing to invite, remove or transfer. */
    team: {
        name: string;
        roles: MandateRole[];
        permissions: MandatePermission[];
        signatory: boolean;
    }[];
    security: {
        two_factor: boolean;
        /** Whether a new device's sign-in is also e-mailed (the design's Login alerts). */
        login_alerts: boolean;
        update_login_alerts: { url: string; method: 'put' };
    };
    /**
     * Null while there is no Business read. `add` (with its `banks`) and each account's `unlink` are
     * offered only to a holder of `business.wallet.withdraw` where an account can be verified.
     */
    linked: {
        accounts: LinkedAccount[];
        add: RouteAction | null;
        banks: { code: string; name: string }[];
    } | null;
    /** Null while no legal document is published: the section shows its empty state. */
    legal: LegalDocument | null;
    /** The support center's chat over the Business's complaints (BRS v2.0 CMP-R03). */
    support: SupportCenter;
    links: BusinessAppLinks & {
        back: RouteLink;
        /** A section with no read open to the business is null and left out of the menu. */
        sections: Record<ProfileSection, RouteLink | null>;
        /** The account's security settings, where the password and two-factor sign-in are managed. */
        security_settings: RouteLink;
        sign_out: RouteAction;
    };
    /** Null while there is no profile command: nothing is editable. */
    actions: { save_company: RouteAction | null };
};

/* ------------------------------------------------------------------------------------------ */
/* Business servicing (C4 contract proposal v1 §4a, `business-servicing-v1`)                   */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable: nothing returns these shapes yet, and the pages are reviewed
 * through synthetic `preview/{fixture}` fixtures. The Business repays from its Rozine wallet,
 * funded through the C3 deposit adapter; `repayment.pay` is then an internal ledger movement with
 * no provider outcome. Every figure is the server's: the client never adds, subtracts or splits
 * money, and never derives DPD, a ladder step or a due date from the browser clock.
 */

export type BusinessServicingAllowedAction =
    | 'business.wallet.deposit'
    | 'business.wallet.withdraw'
    | 'repayment.pay';

export type BusinessServicingPageContract = {
    contract_version: 'business-servicing-v1';
    identity_context_revision: number;
    server_time: string;
    allowed_actions: BusinessServicingAllowedAction[];
};

/** A restriction never withholds deposits (§11.4). */
export type BusinessWalletBalances = {
    revision: number;
    status: 'active' | 'restricted';
    restriction: { code: 'RESTRICTION_ACTIVE'; since: string } | null;
    /** Spendable for repayments. */
    available: Money;
    /** Recorded but not credited, outside `available`. */
    pending_deposits: Money;
    /** Withdrawn from `available` and on its way to the Business's account. */
    pending_withdrawals: Money;
};

/** External cash apart from internal transfers (H5), as on the Investor wallet. */
export type BusinessWalletEntry =
    | {
          id: string;
          movement: 'external';
          kind: 'deposit';
          direction: 'in';
          amount: Money;
          /** The provider's fee on the deposit, passed through (#99 N11). */
          psp_fee: Money;
          /** The masked rail: "MTN MoMo +250 788 ···· 456". */
          counterparty: string;
          occurred_at: string;
          link: RouteLink;
      }
    | {
          id: string;
          movement: 'internal';
          kind: 'repayment';
          direction: 'out';
          amount: Money;
          note_title: string;
          instalment_indexes: number[];
          occurred_at: string;
          link: RouteLink;
      };

export type BusinessWalletMovement = BusinessWalletEntry['movement'];

/**
 * `business.wallet.show`. Mirrors the C3 Investor wallet (v2 §2a): deposit through a verified
 * registered rail under a versioned policy, withdrawal to the Business's own verified account
 * (APP-B01, LED-R04), recorded intents and withdrawals, and the history. A null `policy` or
 * `withdrawal` means that movement is not offered; a post would be refused `POLICY_INPUT_REQUIRED`.
 */
export type BusinessWalletProps = BusinessServicingPageContract & {
    business: { id: string; name: string };
    wallet: BusinessWalletBalances;
    funding: {
        kind: 'deposit' | 'withdraw' | null;
        policy: DepositPolicy | null;
        withdrawal: WithdrawalTerms | null;
        methods: FundingMethod[];
        picks: Money[];
        quote: DepositQuote | null;
    };
    /** Pending and unknown first. */
    deposits: DepositIntent[];
    /** Unsettled first. */
    withdrawals: WalletWithdrawal[];
    history: {
        movement: BusinessWalletMovement;
        filters: {
            key: BusinessWalletMovement;
            active: boolean;
            link: RouteLink;
        }[];
        items: BusinessWalletEntry[];
        pagination: Pagination;
        /**
         * The inclusive calendar days the history is narrowed to, and the unnarrowed view to apply
         * a new range to. Null where no range can be chosen (a preview state).
         */
        range: {
            from: string | null;
            to: string | null;
            base: RouteLink;
        } | null;
    };
    /** The statement downloads for the same range; null where none is served (a preview state). */
    exports: { pdf: RouteLink; csv: RouteLink } | null;
    /** The opened receipt: a history entry with its receipt, a deposit intent or a withdrawal. */
    receipt:
        | (BusinessWalletEntry & { receipt: Receipt })
        | DepositIntent
        | WalletWithdrawal
        | null;
    /** A headline figure without a basis renders without a drill-down. */
    bases: Partial<Record<'available', RouteLink>>;
    /** The shell's navigation for this page. */
    shell_links: BusinessShellLinks;
    links: {
        close: RouteLink;
        /** Opens the deposit panel. */
        deposit: RouteLink;
        /** Opens the withdrawal panel. */
        withdraw: RouteLink;
        /** The deposit operation lookup; its url holds the literal `{request_id}` token. */
        operation: RouteLink;
        /** The withdrawal operation lookup, with the same token. */
        withdrawal_operation: RouteLink;
        /** The note being repaid, when one is servicing. */
        repayments: RouteLink | null;
    };
    actions: { deposit: RouteAction; withdraw: RouteAction };
    preview_outcome?: C3PreviewOutcome<
        'business.wallet.deposit' | 'business.wallet.withdraw'
    >;
};

/* ------------------------------------------------------------------------------------------ */
/* Rating & financial health (MVP-BUSINESS-SCR-03, design L811–939)                           */
/* ------------------------------------------------------------------------------------------ */

/** One of the five limits on a raise; the engine marks the one that binds. */
export type CapacityLimit = { value: Money; binding: boolean } & (
    | { key: 'cash_flow' }
    | { key: 'exposure'; limit: Money; committed: Money }
    | { key: 'policy_max' }
);

/**
 * How the engine sized this business's capacity on the quote its rating came from: the cash it
 * keeps each month, the cover every instalment needs, and the three limits on one note (BRS §4).
 * Headroom is that capacity less the raises signed since it was sized.
 */
export type CapacitySizing = {
    cash_per_month: Money;
    /** The cover each instalment needs, already formatted: "1.50". */
    target_cover: string;
    term_months: number;
    rate_percent: string;
    limits: CapacityLimit[];
    approved: Money;
    headroom: Money;
};

/** The engine measures no growth; the fourth factor is the depth of the verified track record. */
export type RatingFactorKey =
    | 'financial_health'
    | 'repayment_history'
    | 'statement_consistency'
    | 'track_record';

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
    /** The Business's own factor scores out of 100, from the quote its rating came from. */
    factors: { key: RatingFactorKey; score: number }[] | null;
    sizing: CapacitySizing | null;
    /** Audited financials the rating rests on; null while none is published, and none is shown. */
    financials: {
        avg_monthly_revenue: Money;
        /** Net operating cash per month over the statement window; not accounting profit. */
        net_cash_month: Money;
        net_margin_percent: string;
        outstanding: Money;
    } | null;
    links: { close: RouteLink; raise: RouteLink | null };
};

/* ------------------------------------------------------------------------------------------ */
/* Repayments (MVP-BUSINESS-SCR-05; C4 contract proposal v1 §4a, `business-servicing-v1`)       */
/* ------------------------------------------------------------------------------------------ */

/** One step of the late-fee ladder applied to an instalment. Provisional with `LateFeeStep`. */
export type BusinessLateFeeLine = {
    id: string;
    step: LateFeeStep;
    applies_on: KigaliDate;
    rate_bps: Bps;
    /** The amount the step applies to (#99 R1). */
    basis: Money;
    assessed: Money;
    collected: Money;
    outstanding: Money;
    status: LateFeeStatus;
};

export type BusinessInstalment = {
    index: number;
    due_on: KigaliDate;
    /** `late_fees` is "0" here. */
    scheduled: ComponentAmounts;
    paid: ComponentAmounts;
    outstanding: ComponentAmounts;
    status: InstalmentStatus;
    /** Days past due (MC-03); null when not due. */
    dpd: number | null;
    paid_on: KigaliDate | null;
    late_fees: BusinessLateFeeLine[];
};

/**
 * What the Business may pay now. `next_instalment` pays the next scheduled instalment early, at
 * its exact scheduled amount with no discount (C4 Q8, #99 R8).
 */
export type RepaymentPayOption = {
    key: 'due_now' | 'next_instalment';
    amounts: ComponentAmounts;
    instalment_indexes: number[];
};

export type BusinessServicing = CampaignServicing & {
    note_id: string;
    revision: number;
    /** The oldest overdue instalment and today's, with assessed late fees; null when nothing is due. */
    due_now: ComponentAmounts | null;
    /** The next step's fee if nothing is paid first: a projection, labelled as one. */
    next_late_fee: {
        step: LateFeeStep;
        applies_on: KigaliDate;
        projected: Money;
    } | null;
    /**
     * Collection from the wallet on the due date (#99 R7: 23:59 on the due date, then daily during
     * recovery days 1–7). Null when the server runs no auto-collection.
     */
    autocollect: {
        mode: 'on_due_date' | 'off';
        next_attempt_on: KigaliDate | null;
    } | null;
};

/**
 * One repayment as the Business may see it. Allocation reads only `allocating` or `allocated`:
 * an exception stays with staff (H15), and only the number of Investors paid is shown (H16).
 */
export type RepaymentReceiptView = {
    id: string;
    revision: number;
    note_title: string;
    amount: Money;
    applied: ComponentAmounts;
    unapplied: Money;
    allocation: 'allocating' | 'allocated';
    /** Null until allocated. */
    investors_paid: number | null;
    /** REPAYMENT_RECEIVED, immutable. */
    receipt: Receipt;
    link: RouteLink;
};

/**
 * `business.repayments.show`. Pays from the Business wallet only: the Phase 1B pay-ahead, bank
 * and mobile-money sources, deferral and manual review are Phase 2. `repayment.pay` carries the
 * total on screen as `quoted_total`; a different server total is refused `VERSION_CONFLICT`.
 */
export type BusinessRepaymentsProps = BusinessServicingPageContract & {
    note: {
        id: string;
        title: string;
        disbursement_effective_date: KigaliDate;
        /** The day of the month every instalment falls due. */
        due_day: number;
    };
    servicing: BusinessServicing;
    schedule: BusinessInstalment[];
    /** Null when the late-fee policy is unavailable: no ladder is shown or implied. */
    ladder: LateFeeLadder | null;
    pay: {
        /** Empty when nothing is payable. */
        options: RepaymentPayOption[];
        funding: {
            available: Money;
            sufficient: Record<RepaymentPayOption['key'], boolean>;
            revision: number;
        };
    };
    /** The opened repayment, or the one just recorded. */
    receipt: RepaymentReceiptView | null;
    /** Newest first. */
    recent: RepaymentReceiptView[];
    bases: Partial<Record<'remaining' | 'due_now' | 'repaid', RouteLink>>;
    /** Home, drawn beneath the sheet on a wide screen; null opens over an empty backdrop. */
    home: BusinessHomeProps | null;
    shell_links: BusinessShellLinks;
    links: {
        close: RouteLink;
        /** The wallet's deposit panel, offered instead of Pay when funds are short. */
        top_up: RouteLink;
        operation: RouteLink;
    };
    actions: { pay: RouteAction | null };
    preview_outcome?: C3PreviewOutcome<'repayment.pay'>;
};

/**
 * An issued Note's schedule as it was issued: each instalment's principal and return, the equal
 * monthly amount the Business pays (BRS glossary). No paid amount, late fee or current claim.
 */
export type BusinessIssuedSchedule = {
    note_id: string;
    title: string;
    /** The disbursement's Kigali effective date, which anchors the due dates. */
    effective_date: KigaliDate | null;
    principal: Money;
    return: Money;
    total: Money;
    instalments: {
        index: number;
        due_on: KigaliDate;
        principal: Money;
        return: Money;
        total: Money;
    }[];
};

/**
 * `business.repayments.show` while no note of the business is servicing: no note, servicing or
 * pay panel, and no Pay. The sheet shows its empty state over Home, or each issued Note's
 * schedule as it was issued.
 */
export type BusinessRepaymentsEmptyProps = Omit<
    BusinessRepaymentsProps,
    'note' | 'servicing' | 'pay'
> & {
    note: null;
    servicing: null;
    pay: null;
    issued: BusinessIssuedSchedule[];
};

/** What the Repayments page receives: a servicing note's sheet, or its empty state. */
export type BusinessRepaymentsPageProps =
    | BusinessRepaymentsProps
    | BusinessRepaymentsEmptyProps;

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

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 3: business-campaign-v1 (C3 contract proposal v2 §2f)                            */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable. The Phase 1B listing and note shapes above stay untouched; the
 * Publish and campaign pages read these, reviewed only through synthetic fixtures. The Business
 * sees aggregate funding progress only: no Investor names, identity kinds or per-Investor amounts
 * (H16).
 */

export type BusinessCampaignAllowedAction =
    | 'application.publish'
    | 'campaign.cancel';

/**
 * What Publish needs, stated explicitly. Publish reuses the retained Review signatures rather than
 * asking for a second acceptance; if the quote or terms changed since signing it routes back to
 * Review to sign again. Every prerequisite is a server fact.
 */
export type PublishPrerequisite = {
    key:
        | 'staff_release'
        | 'signatures_retained'
        | 'quote_current'
        | 'terms_current';
    met: boolean;
};

export type C3BusinessPublishProps = {
    contract_version: 'business-campaign-v1';
    identity_context_revision: number;
    server_time: string;
    application: {
        id: string;
        title: string;
        target: Money;
        revision: number;
    };
    /** Gates stay server facts; causes are stable codes. */
    release: {
        state: 'awaiting_staff_review' | 'released' | 'refused';
        causes: string[];
    };
    prerequisites: PublishPrerequisite[];
    /** "0": an explicit MVP waiver, with a zero-fee receipt (§11.1). */
    listing_fee: Money;
    fee_disclosure: { version: string; text: string };
    /** The published listing: its zero-fee receipt and the campaign it opened. */
    listing: { receipt: Receipt; campaign: RouteLink } | null;
    allowed_actions: 'application.publish'[];
    actions: { publish: RouteAction };
    links: {
        close: RouteLink;
        operation: RouteLink;
        /** Back to Review to sign again, when the quote or terms changed since signing. */
        review: RouteLink | null;
    };
    /**
     * Home, drawn beneath the sheet on a wide screen. The web page always sends it; null (the
     * bearer read) opens the sheet over an empty backdrop with no stand-in balances.
     */
    home: BusinessHomeProps | null;
    /** The shell's navigation for this page, read instead of `home.links`. */
    shell_links: BusinessShellLinks;
    preview_outcome?: C3PreviewOutcome<'application.publish'>;
};

/** The coarse closing view (H15): no provider or evidence reference. */
export type BusinessClosing =
    | { stage: 'awaiting_disbursement' }
    | { stage: 'in_flight'; provider: CoarseInFlight };

/** A campaign's aggregate funding progress. Every figure is the server's; the client never subtracts. */
export type CampaignProgress =
    | {
          phase: 'raising';
          lifecycle: RaisingLifecycle;
          restriction: CampaignRestriction;
          committed: Money;
          reserved: Money;
          remaining: Money;
          units: {
              total: Units;
              available: Units;
              reserved: Units;
              committed: Units;
              /** Occupied by a hold or commitment that has ended; not on sale (no recycling yet). */
              unavailable: Units;
          };
          investors: number;
          /** One decimal: "78.1". */
          funded_pct: string;
          clock: Clock;
      }
    | {
          phase: 'funded';
          /** Sent with the durable funding lock; when present it matches the campaign's lifecycle. */
          lifecycle?: 'funded_pending_disbursement';
          restriction: CampaignRestriction;
          committed: Money;
          investors: number;
          funded_at: string;
          closing: BusinessClosing;
      }
    | {
          phase: 'disbursed';
          amount: Money;
          receipt: Receipt;
          disbursement_effective_at: string;
          effective_date: string;
          /** Masked. */
          destination: string;
      }
    | {
          phase: 'expired' | 'cancelled' | 'failed_closing';
          committed_refunded: Money;
          investors: number;
          closed_at: string;
      };

export type InvestorKind = 'person' | 'organization';

/** A funded raise's holders for its own Business. No Investor is named (Privacy Note §5). */
export type CampaignInvestors = {
    raised: Money;
    holders: number;
    average: Money;
    mix: { kind: InvestorKind; holders: number; percent: string }[];
    /** Largest holding first. */
    list: { kind: InvestorKind; units: string; principal: Money }[];
};

export type BusinessCampaignProps = Omit<
    BusinessNoteProps,
    'home' | 'note' | 'links'
> & {
    contract_version: 'business-campaign-v1';
    identity_context_revision: number;
    server_time: string;
    allowed_actions: 'campaign.cancel'[];
    /** The campaign lifecycle (#96): never the 1B `NoteStatus`, so `fully_reserved` never reads as funded. */
    campaign: {
        id: string;
        revision: number;
        lifecycle: BusinessCampaignLifecycle;
    };
    note: Omit<
        BusinessNoteProps['note'],
        'recent_investors' | 'progress' | 'status'
    > & {
        progress: CampaignProgress;
    };
    links: {
        close: RouteLink;
        operation: RouteLink;
        /** The change beacon (S4-E), with its render-time cursor; null in previews. */
        changes?: RouteLink | null;
    };
    /**
     * Home, drawn beneath the sheet on a wide screen. The web page always sends it; null (the
     * bearer read) opens the sheet over an empty backdrop with no stand-in balances.
     */
    home: BusinessHomeProps | null;
    /** Who holds the funded notes, by kind and amount only; null until the raise is funded. */
    investors?: CampaignInvestors | null;
    /** The shell's navigation for this page, read instead of `home.links`. */
    shell_links: BusinessShellLinks;
    actions: { cancel: RouteAction | null };
    preview_outcome?: C3PreviewOutcome<'campaign.cancel'>;
};

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 4: business-campaign-v2 (C4 contract proposal v1 §4a)                            */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable. v2 only adds the servicing phases to a campaign's progress: an
 * issued note that is repaying, and one that is fully repaid. It still shows aggregate figures
 * only, with the number of Investors and never who they are (H16).
 */

/** Where a note's repayment stands, as the campaign page shows it. Every figure is the server's. */
export type CampaignServicing = {
    state: ServicingState;
    /** Days past due on the oldest unpaid instalment (MC-03); null when nothing is due. */
    dpd: number | null;
    progress: {
        repaid: Money;
        total: Money;
        remaining: Money;
        /** One decimal: "40.0". */
        repaid_pct: string;
        payments_made: number;
        payments_total: number;
        remaining_instalments: number;
    };
    next: {
        index: number;
        due_on: KigaliDate;
        amounts: ComponentAmounts;
    } | null;
    /** `ARREARS` appears from DPD 1 and freezes secondary (§8.3); it never replaces the state. */
    restriction: {
        code: 'ARREARS' | 'RESTRICTION_ACTIVE';
        since: string;
    } | null;
};

export type CampaignProgressV2 =
    | CampaignProgress
    | {
          phase: 'repaying';
          servicing: CampaignServicing;
          investors: number;
          /** The note's repayments page. */
          link: RouteLink;
      }
    | {
          phase: 'repaid';
          total_repaid: Money;
          completed_on: KigaliDate;
          investors: number;
      };

export type BusinessCampaignV2Props = Omit<
    BusinessCampaignProps,
    'contract_version' | 'note'
> & {
    contract_version: 'business-campaign-v2';
    note: Omit<BusinessCampaignProps['note'], 'progress'> & {
        progress: CampaignProgressV2;
    };
};
