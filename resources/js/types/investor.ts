import type { Money } from './money';
import type { RouteAction, RouteLink } from './routing';
import type {
    C3PreviewOutcome,
    CampaignLifecycle,
    CampaignRestriction,
    Clock,
    CoarseInFlight,
    Ordinals,
    Pagination,
    ProviderOutcomeState,
    Receipt,
    UnitRights,
    Units,
} from './settlement';

/**
 * Investor app page contracts (Phase 1B, MVP-INVESTOR-SCR-01..05, 08, 10, 11 and the Investor
 * auth/verification flow). Every figure is a server fact — quantities, totals, fees, yields, payout
 * schedules, portfolio values and wallet balances all arrive already computed; the client formats
 * and arranges them and never does arithmetic that decides anything. The purchase unit is exactly
 * RWF 5,000 of principal per note unit (engineering contract §11.2), but even that arrives as
 * `unit_price` rather than being assumed.
 *
 * Secondary trading, "sell back to Rozine", Rozine Plus bands/automation, the watchlist, Investor
 * Academy and referral screens are outside the MVP and have no contract here.
 */

/* ------------------------------------------------------------------------------------------ */
/* Shared shapes                                                                               */
/* ------------------------------------------------------------------------------------------ */

/** The published rating bands; the word leads, the five-point score supports. */
export type InvestorRatingBand = 'strong' | 'stable' | 'weak' | 'distressed';

export type InvestorRating = {
    band: InvestorRatingBand;
    /** The published score on the five-point scale, formatted by the engine: "4.2". */
    score: string;
};

/**
 * The business's brand accent, from the design's industry palette. An enum rather than a hex so
 * the client can give each one a dark-mode pair.
 */
export type BusinessAccent =
    | 'green'
    | 'blue'
    | 'amber'
    | 'purple'
    | 'teal'
    | 'magenta'
    | 'ink';

/** A photo the business or its Audit Partner filed. `url` is null until the file is available. */
export type EvidencePhoto = {
    url: string | null;
    caption: string;
};

/** Where the Investor shell's tabs and launcher go. Market is secondary trading and is not built. */
export type InvestorAppLinks = {
    deals: RouteLink;
    portfolio: RouteLink;
    profile: RouteLink;
    wallet: RouteLink;
    notifications: RouteLink;
    launcher: RouteLink;
};

export type WalletSummary = {
    available: Money;
    /** The next scheduled payout to this investor, or null when nothing is scheduled. */
    next_payout: { amount: Money; due_on: string } | null;
};

/* ------------------------------------------------------------------------------------------ */
/* Deals (MVP-INVESTOR-SCR-01) and deal detail (SCR-02)                                        */
/* ------------------------------------------------------------------------------------------ */

/**
 * Where a listing stands. Only `open` may be bought; the others explain themselves (crosswalk
 * SCR-01-ST-02 sold out, SCR-02-ST-01..03 sold out mid-read / frozen / withdrawn).
 */
export type DealStatus = 'open' | 'sold_out' | 'frozen' | 'withdrawn';

export type DealCard = {
    id: string;
    name: string;
    accent: BusinessAccent;
    industry: string;
    district: string;
    rating: InvestorRating;
    /** A sealed Field Flash report is on file. */
    audited: boolean;
    just_listed: boolean;
    photos: EvidencePhoto[];
    raised: Money;
    target: Money;
    /** Share of the target funded, engine-rounded to one decimal: "78.1". */
    funded_pct: string;
    avg_monthly_revenue: Money;
    left_to_fill: Money;
    units_sold: number;
    units_total: number;
    units_left: number;
    investors: number;
    /** Campaign close, RFC 3339. The countdown renders against `server_time`. */
    closes_at: string;
    story: string;
    /** Flat total return over the whole term, one decimal: "13.5". Never annualised. */
    rate_pct: string;
    term_months: number;
    status: DealStatus;
    links: {
        /** Full deal page (phone) / the deal focused in the Deals panel (wide screen). */
        detail: RouteLink;
    };
};

export type DealSortKey = 'all' | 'top_interest' | 'top_rated' | 'top_picks';

export type DealSort = {
    key: DealSortKey;
    active: boolean;
    link: RouteLink;
};

export type IndustryFilter = {
    /** Null for "All". */
    industry: string | null;
    count: number;
    active: boolean;
    link: RouteLink;
};

/** What the investor may do right now, and why not (crosswalk SCR-01-ST-03 gated). */
export type InvestGate =
    | { status: 'eligible' }
    | { status: 'verification_required'; link: RouteLink }
    | { status: 'verification_pending' }
    | { status: 'restricted' };

export type UseOfFunds =
    | 'inventory'
    | 'equipment'
    | 'expansion'
    | 'hiring'
    | 'working_capital'
    | 'other';

export type DealFinancials = {
    avg_monthly_revenue: Money;
    ebitda: Money;
    existing_debt: Money;
    /** Rozine's appraised repayment capacity for the business. */
    capacity: Money;
};

export type TrackRecord = {
    raises: number;
    /** Share of scheduled repayments made on time, or null before the first fell due. */
    on_time_pct: number | null;
    repaid: Money;
};

export type AboutBusiness = {
    /** RDB company code — never a tax identifier (BRS AC-9). */
    company_code: string;
    registered_year: number;
    years_operating: number;
    team_size: number;
    address: string;
};

/** The sealed Field Flash report (audit evidence), as the Audit Partner filed it. */
export type AuditEvidence = {
    standard: string;
    partner: string;
    licence: string;
    verified_on: string;
    cash_observed: Money;
    inventory_sample: number;
    /** Signed variance against telemetry, one decimal: "-2.0". */
    variance_pct: string;
    /** The engine's own tolerance verdict; the client never compares the number. */
    variance_within_tolerance: boolean;
    /** SHA-256 of the sealed report. */
    digest: string;
    photos: { label: string; geo: string; url: string | null }[];
    /** The signed report PDF. */
    report: RouteLink;
};

export type ProofPhoto = EvidencePhoto & {
    /** A required shot of the procedure, rather than one the auditor added. */
    required: boolean;
    gps: string;
    captured_at: string;
};

export type MonthlyUpdateStatus = 'healthy' | 'watch';

/** One verified monthly report (published after audit and co-signature). */
export type MonthlyUpdate = {
    id: string;
    /** First day of the reported month, RFC 3339. */
    month: string;
    status: MonthlyUpdateStatus;
    summary: string;
    /** Signed net for the month. */
    net: Money;
    inflow: Money;
    outflow: Money;
    business_note: string;
    auditor: { name: string; licence: string; verified_on: string };
    auditor_note: string;
    photos: ProofPhoto[];
};

export type DealDetail = DealCard & {
    use_of_funds: UseOfFunds[];
    financials: DealFinancials;
    /** Rozine's published rating basis, as the engine explains it. */
    rationale: string;
    track_record: TrackRecord | null;
    about: AboutBusiness;
    audit: AuditEvidence | null;
    updates: MonthlyUpdate[];
    /** A monthly report that missed the 7th, if one did. */
    overdue_report: { month: string } | null;
};

/** The server's quote for `units` notes of the focused deal. */
export type InvestQuote = {
    deal_id: string;
    units: number;
    unit_price: Money;
    amount: Money;
    rate_pct: string;
    expected_return: Money;
    /** The 1% investor payout fee over the whole schedule (CFG-04). */
    payout_fee: Money;
    /** What the investor gets back at maturity, after the investor payout fee. */
    get_back: Money;
    /** The most units this investor may take now (exposure caps and what is left). */
    max_units: number;
};

export type InvestorDealsProps = {
    server_time: string;
    wallet: WalletSummary;
    unread_notifications: number;
    gate: InvestGate;
    sorts: DealSort[];
    industries: IndustryFilter[];
    deals: DealCard[];
    /** The deal in front of the deck, in full — the wide-screen detail panel. Null when empty. */
    focus: DealDetail | null;
    quote: InvestQuote | null;
    links: InvestorAppLinks & {
        deposit: RouteLink;
        withdraw: RouteLink;
        /** Checkout for the focused deal; the client adds `units`. Null while it cannot be bought. */
        checkout: RouteLink | null;
    };
};

/**
 * The phone deal page. The quote reloads in place (`only: ['quote']`, with `units`). On a wide
 * screen the same deal shows in the Deals detail panel instead, so `home` is drawn there.
 */
export type InvestorDealProps = {
    server_time: string;
    deal: DealDetail;
    gate: InvestGate;
    quote: InvestQuote | null;
    home: InvestorDealsProps;
    links: {
        back: RouteLink;
        checkout: RouteLink | null;
    };
};

/* ------------------------------------------------------------------------------------------ */
/* Checkout (MVP-INVESTOR-SCR-03)                                                              */
/* ------------------------------------------------------------------------------------------ */

/** Stable refusal codes (engineering contract §4); the client maps each to localized text. */
export type CheckoutRefusalCode =
    | 'TRADE_BELOW_MINIMUM'
    | 'INSUFFICIENT_AVAILABLE_FUNDS'
    | 'EXPOSURE_LIMIT'
    | 'NOTE_INELIGIBLE'
    | 'RESERVATION_EXPIRED'
    | 'RESTRICTION_ACTIVE'
    | 'VERSION_CONFLICT';

export type CheckoutQuote = {
    units: number;
    unit_price: Money;
    amount: Money;
    rate_pct: string;
    term_months: number;
    expected_return: Money;
    /** The 1% investor payout fee over the whole schedule (CFG-04), exact. */
    payout_fee: Money;
    maturity_value: Money;
    maturity_date: string;
    /** The quote the confirmation commits to (idempotency and revision check). */
    revision: number;
};

export type CheckoutReceipt = {
    transaction_id: string;
    reference: string;
    amount: Money;
    units: number;
    expected_return: Money;
    maturity_value: Money;
    maturity_date: string;
    confirmed_at: string;
    link: RouteLink;
};

export type InvestorCheckoutProps = {
    server_time: string;
    deal: Pick<
        DealCard,
        | 'id'
        | 'name'
        | 'accent'
        | 'rate_pct'
        | 'term_months'
        | 'closes_at'
        | 'status'
    >;
    quote: CheckoutQuote;
    limits: { min_units: number; max_units: number };
    /** Quick picks with their server amounts (the design's 5K / 10K / 25K / 50K). */
    quick_picks: { units: number; amount: Money }[];
    /** Primary checkout is paid from the wallet only (CFG-03). */
    wallet: { available: Money; sufficient: boolean };
    /** The cost and risk disclosure the investor must acknowledge, at its version. */
    disclosure: { version: string; points: string[] };
    refusal: { code: CheckoutRefusalCode } | null;
    receipt: CheckoutReceipt | null;
    /** Deals, drawn beneath the sheet. */
    home: InvestorDealsProps;
    /**
     * Another quantity reloads `quote`, `wallet` and `refusal` in place (`units`). A confirmation
     * posts `deal`, `units`, the quote `revision` and the acknowledged disclosure version; the
     * server answers with `receipt` set, or a `refusal` code and a fresh quote.
     */
    links: {
        close: RouteLink;
        portfolio: RouteLink;
        deals: RouteLink;
        deposit: RouteLink;
    };
    actions: { confirm: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Portfolio (MVP-INVESTOR-SCR-04) and holding detail (SCR-05)                                 */
/* ------------------------------------------------------------------------------------------ */

/** Crosswalk SCR-04-ST-02 / SCR-05-ST-01..03: arrears, matured, frozen and defaulted show at once. */
export type HoldingHealth =
    | 'healthy'
    | 'watch'
    | 'arrears'
    | 'frozen'
    | 'defaulted'
    | 'matured';

export type HoldingSummary = {
    id: string;
    name: string;
    accent: BusinessAccent;
    industry: string;
    district: string;
    health: HoldingHealth;
    /** Principal outstanding plus return received so far — realised, not projected. */
    value: Money;
    invested: Money;
    /** Signed. */
    gain: Money;
    /** Signed, one decimal: "13.3". */
    gain_pct: string;
    /** Share of scheduled payments made, 0–100. */
    repaid_pct: number;
    matures_on: string;
    payments_made: number;
    payments_total: number;
    link: RouteLink;
};

export type PortfolioTotals = {
    businesses: number;
    value: Money;
    invested: Money;
    gain: Money;
    this_month: Money;
    /** Scheduled, not yet paid: labelled as a projection. */
    projected_3m: Money;
    next_payout: { amount: Money; month: string } | null;
    avg_monthly: Money;
};

export type PayoutMonth = {
    month: string;
    amount: Money;
    /** Bar height as a share of the tallest month, 0–100, from the server. */
    bar_pct: number;
    payers: { name: string; accent: BusinessAccent; amount: Money }[];
};

export type Concentration =
    | { status: 'concentrated'; business: string; share_pct: number }
    | { status: 'balanced' };

export type PortfolioTab = 'active' | 'matured';

export type InvestorPortfolioProps = {
    tab: PortfolioTab;
    tabs: { key: PortfolioTab; active: boolean; link: RouteLink }[];
    totals: PortfolioTotals;
    holdings: HoldingSummary[];
    payouts: PayoutMonth[];
    industries: {
        name: string;
        accent: BusinessAccent;
        amount: Money;
        share_pct: number;
    }[];
    risk: { band: InvestorRatingBand; share_pct: number }[];
    concentration: Concentration | null;
    /** Uninvested wallet cash, or null when there is none. */
    idle: Money | null;
    links: InvestorAppLinks;
};

export type RecoveryStepState = 'done' | 'current' | 'pending';

export type HoldingDetail = HoldingSummary & {
    rating: InvestorRating;
    rate_pct: string;
    expected_profit: Money;
    maturity_value: Money;
    received: Money;
    next_payment: { amount: Money; due_on: string } | null;
    /** Payments made so far and how many of them arrived on time. */
    on_time: { made: number; on_time: number; late: number };
    months_left: number;
    investors: number;
    rating_change: {
        from: InvestorRating;
        to: InvestorRating;
        reasons: { label: string; delta: string }[];
    } | null;
    /**
     * A declared, approved deferral. Penalties are platform revenue rather than an investor
     * entitlement (CFG-04), so the design's "Extra paid to you" row has no field here.
     */
    recovery_plan: {
        state: 'on_track' | 'off_track';
        reason: string;
        money_arrives: string;
        deferred: Money;
    } | null;
    arrears: {
        days_overdue: number;
        steps: {
            key: 'missed' | 'contacted' | 'plan' | 'resume';
            state: RecoveryStepState;
        }[];
    } | null;
    photos: EvidencePhoto[];
    updates: MonthlyUpdate[];
};

export type InvestorHoldingProps = {
    holding: HoldingDetail;
    links: InvestorAppLinks & { back: RouteLink };
};

/* ------------------------------------------------------------------------------------------ */
/* Wallet (MVP-INVESTOR-SCR-08) and receipts                                                   */
/* ------------------------------------------------------------------------------------------ */

export type FundingKind = 'deposit' | 'withdraw';

export type PayoutAccountKind = 'mtn' | 'airtel' | 'bank';

export type FundingMethod = {
    id: string;
    kind: PayoutAccountKind;
    /** "MTN MoMo", "Bank of Kigali". */
    label: string;
    /** Masked by the server: "+250 788 ···· 456". */
    masked: string;
};

/** The server's reading of an entered amount: its fee and what lands. */
export type FundingQuote = {
    kind: FundingKind;
    amount: Money;
    fee: Money;
    /** Deposit: the new available balance. Withdrawal: what reaches the account. */
    result: Money;
    refusal:
        | 'TRADE_BELOW_MINIMUM'
        | 'INSUFFICIENT_AVAILABLE_FUNDS'
        | 'DAILY_LIMIT'
        | 'RESTRICTION_ACTIVE'
        | null;
};

export type TransactionKind =
    | 'deposit'
    | 'withdrawal'
    | 'investment'
    | 'payout'
    | 'refund'
    | 'fee';

export type TransactionStatus = 'completed' | 'pending' | 'failed';

export type TransactionItem = {
    id: string;
    kind: TransactionKind;
    /** The counterparty: a business, a mobile-money account or a bank. */
    counterparty: string;
    occurred_at: string;
    /** Signed. */
    amount: Money;
    status: TransactionStatus;
    link: RouteLink;
};

export type TransactionReceipt = TransactionItem & {
    transaction_id: string;
    reference: string;
    balance_before: Money;
    balance_after: Money;
    /** Withdrawals only: gross, fee and what reached the account. */
    breakdown: { gross: Money; fee: Money; net: Money } | null;
    /** Payouts only: principal and return in this payout, and the investor fee. */
    payout: {
        principal: Money;
        return: Money;
        fee: Money;
    } | null;
};

export type EarningsRange = '3m' | '6m' | '12m' | 'ytd';

export type EarningsHistory = {
    from: string;
    to: string;
    ranges: { key: EarningsRange; active: boolean; link: RouteLink }[];
    totals: {
        interest: Money;
        received: Money;
        payouts: number;
        avg_monthly: Money;
    };
    months: {
        month: string;
        payouts: number;
        interest: Money;
        total: Money;
    }[];
};

export type TransactionRange = 'today' | '7d' | '30d' | 'all';

export type InvestorWalletProps = {
    wallet: {
        available: Money;
        status: 'active' | 'restricted';
        pending_withdrawal: Money | null;
    };
    funding: {
        /**
         * The open panel. Null on a phone means it is closed; a wide screen always shows one, with
         * deposit as the resting state (design L5417).
         */
        kind: FundingKind | null;
        methods: FundingMethod[];
        /** Quick amounts (50K / 100K / 500K / 1M). */
        picks: Money[];
        quote: FundingQuote | null;
    };
    earnings: EarningsHistory;
    transactions: {
        range: TransactionRange;
        ranges: { key: TransactionRange; active: boolean; link: RouteLink }[];
        items: TransactionItem[];
        exports: { pdf: RouteLink; csv: RouteLink };
    };
    /** The opened receipt, when the route names one. */
    receipt: TransactionReceipt | null;
    /**
     * An entered amount reloads `funding` in place (`kind`, `amount`); an earnings date range reloads
     * `earnings` (`from`, `to`). Deposit and withdrawal post `amount` and `method`.
     */
    links: InvestorAppLinks & {
        /** The wallet with no panel or receipt open. */
        close: RouteLink;
        deposit: RouteLink;
        withdraw: RouteLink;
        link_account: RouteLink;
    };
    actions: { deposit: RouteAction; withdraw: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Profile (MVP-INVESTOR-SCR-11) and statements (SCR-10)                                       */
/* ------------------------------------------------------------------------------------------ */

export type InvestorType = 'individual' | 'institution';

export type KycState = 'verified' | 'pending' | 'unverified' | 'expired';

export type ProfileSection = 'overview' | 'linked' | 'statements';

export type LinkedAccount = FundingMethod & {
    verified: boolean;
    unlink: RouteAction;
};

export type StatementFile = {
    /** First day of the month, or the financial year start for the annual summary. */
    period: string;
    kind: 'monthly' | 'annual';
    /** Still accruing: the period is not closed yet (crosswalk SCR-10-ST-02). */
    complete: boolean;
    link: RouteLink;
};

export type InvestorProfileProps = {
    section: ProfileSection;
    identity: {
        name: string;
        email: string;
        investor_type: InvestorType;
        kyc: KycState;
        member_since: string;
    };
    linked: {
        accounts: LinkedAccount[];
        banks: { code: string; name: string }[];
    } | null;
    statements: {
        annual: StatementFile | null;
        monthly: StatementFile[];
    } | null;
    links: InvestorAppLinks & {
        overview: RouteLink;
        linked: RouteLink;
        statements: RouteLink;
        verification: RouteLink;
        terms: RouteLink;
        privacy: RouteLink;
    };
    actions: { link_account: RouteAction; logout: RouteAction };
};

/* ------------------------------------------------------------------------------------------ */
/* Auth, sign-up and verification (the Investor app's own design)                              */
/* ------------------------------------------------------------------------------------------ */

/** Headline figures quoted by the auth copy, from policy rather than the design's literals. */
export type InvestorPitch = {
    rate_min_pct: string;
    rate_max_pct: string;
    term_min_months: number;
    term_max_months: number;
    unit_price: Money;
};

export type InvestorIntroProps = {
    pitch: InvestorPitch;
    links: { start: RouteLink; login: RouteLink };
};

export type InvestorAuthProps = {
    mode: 'login' | 'register';
    pitch: InvestorPitch;
    investor_type: InvestorType;
    status?: string;
    links: {
        login: RouteLink;
        register: RouteLink;
        /** Phone role cards go straight to sign-up with the chosen type. */
        individual: RouteLink;
        institution: RouteLink;
        /** Wide-screen type toggles keep the panel and switch its type. */
        pick_individual: RouteLink;
        pick_institution: RouteLink;
    };
    actions: { login: RouteAction; start: RouteAction };
};

export type SignUpStep =
    | 'identity'
    | 'address'
    | 'contact'
    | 'security'
    | 'payment'
    | 'agree';

export type IdDocument = 'national_id' | 'passport' | 'drivers_license';

export type SignUpDraft = {
    investor_type: InvestorType;
    first_name: string;
    last_name: string;
    address: string;
    id_type: IdDocument;
    id_number: string;
    /** Institutions: the RDB company code (never a tax identifier). */
    company_code: string;
    country: string;
    province: string;
    district: string;
    sector: string;
    cell: string;
    email: string;
    phone_country: string;
    phone: string;
    secret: 'pin' | 'password';
    payment_method: PayoutAccountKind | null;
    otp_sent: boolean;
    payment_verified: boolean;
};

export type InvestorSignUpProps = {
    step: SignUpStep;
    draft: SignUpDraft;
    /** Countries eligible under `eligibility.investor.allowedCountries`. */
    countries: string[];
    documents: { terms: RouteLink; privacy: RouteLink };
    links: { back: RouteLink };
    actions: { save: RouteAction; send_otp: RouteAction; finish: RouteAction };
};

export type KycStep = 'personal' | 'document' | 'liveness';

export type KycInstitutionStep = 'entity' | 'representative' | 'declarations';

export type UploadState = { status: 'missing' | 'uploaded' | 'verified' };

export type EntityType =
    | 'fund'
    | 'sacco'
    | 'treasury'
    | 'insurer'
    | 'pension'
    | 'other';

export type FundsSource =
    | 'operations'
    | 'member_savings'
    | 'investment_returns'
    | 'premiums'
    | 'contributions'
    | 'other';

export type InvestorVerificationProps =
    | {
          investor_type: 'individual';
          step: KycStep;
          country: string;
          date_of_birth: string;
          id_type: IdDocument;
          id_number: string;
          uploads: {
              front: UploadState;
              back: UploadState;
              selfie: UploadState;
          };
          links: { back: RouteLink };
          actions: {
              save: RouteAction;
              upload: RouteAction;
              submit: RouteAction;
          };
      }
    | {
          investor_type: 'institution';
          step: KycInstitutionStep;
          entity_type: EntityType | null;
          company_code: string;
          incorporated: string;
          representative: { name: string; role: string; id_number: string };
          funds_source: FundsSource | null;
          annual_commitment: string;
          uploads: {
              certificate: UploadState;
              resolution: UploadState;
              selfie: UploadState;
          };
          links: { back: RouteLink };
          actions: {
              save: RouteAction;
              upload: RouteAction;
              submit: RouteAction;
          };
      };

export type InvestorVerifiedProps = {
    wallet: { available: Money };
    open_deals: number;
    links: { deals: RouteLink };
};

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 3: investor-primary-v1 (C3 contract proposal v2 §2a–2d)                         */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable. The Phase 1B shapes above stay untouched; the Investor pages read
 * these instead, and are reviewed only through synthetic `preview/{fixture}` fixtures until the
 * server Resources exist. Names that would collide with a Phase 1B shape carry a `C3` prefix.
 */

/** What every C3 Investor page carries (v2 §2). `server_time` is fresh on every response. */
export type InvestorPageContract = {
    contract_version: 'investor-primary-v1';
    identity_context_revision: number;
    server_time: string;
    allowed_actions: InvestorAllowedAction[];
};

export type InvestorAllowedAction =
    | 'wallet.deposit'
    | 'primary.reserve'
    | 'primary.confirm'
    | 'primary.release'
    | 'primary.cancel';

/* Wallet (v2 §2a) ------------------------------------------------------------------------- */

export type WalletBucket = 'available' | 'held' | 'committed';

/**
 * Ledger-derived balances. `total` is available + held + unissued committed, each counted once
 * (H5); only `available` is spendable. Pending deposits sit outside `total`: they are not credited.
 */
export type WalletBalances = {
    revision: number;
    status: 'active' | 'restricted';
    restriction: { code: 'RESTRICTION_ACTIVE'; since: string } | null;
    total: Money;
    breakdown: { available: Money; held: Money; committed: Money };
    pending_deposits: Money;
};

/** Explicitly synthetic and versioned in fixtures; never frozen as live policy (H6). */
export type DepositPolicy = {
    version: string;
    synthetic: boolean;
    fee: Money;
    minimum: Money | null;
    maximum: Money | null;
};

export type DepositRefusal =
    | 'VALIDATION_FAILED'
    | 'DEPOSIT_METHOD_UNVERIFIED'
    | 'POLICY_INPUT_REQUIRED';

export type DepositQuote = {
    amount: Money;
    fee: Money;
    credited: Money;
    refusal: DepositRefusal | null;
};

/**
 * A recorded deposit intent. The Investor sees no provider reference: `pending` and `unknown` both
 * read "not yet confirmed", and nothing is credited until a verified success adds `credit_receipt`.
 */
export type DepositIntent = {
    id: string;
    request_id: string;
    amount: Money;
    method: FundingMethod;
    created_at: string;
    state: ProviderOutcomeState;
    /** DEPOSIT_INTENT_RECORDED, immutable. */
    intent_receipt: Receipt;
    /** DEPOSIT_CREDITED, only after a verified success. */
    credit_receipt: Receipt | null;
    link: RouteLink;
};

/** A live checkout reservation holding wallet cash. */
export type CheckoutHold = {
    reservation_id: string;
    deal_name: string;
    amount: Money;
    units: Units;
    clock: Clock;
    link: RouteLink;
};

/** Holds and internal transfers are kept apart from external cash movements (H5). */
export type WalletEntry =
    | {
          id: string;
          movement: 'external';
          kind: 'deposit';
          direction: 'in' | 'out';
          amount: Money;
          counterparty: string;
          occurred_at: string;
          link: RouteLink;
      }
    | {
          id: string;
          movement: 'internal';
          kind: 'hold' | 'hold_release' | 'commitment' | 'commitment_refund';
          from: WalletBucket;
          to: WalletBucket;
          amount: Money;
          deal_name: string;
          occurred_at: string;
          link: RouteLink;
      };

export type WalletMovement = WalletEntry['movement'];

export type C3InvestorWalletProps = InvestorPageContract & {
    wallet: WalletBalances;
    holds: CheckoutHold[];
    /**
     * Deposit only: withdrawal stays hidden until it has its own contract (H6). A null `policy`
     * means deposit is not offered; a post would be refused `POLICY_INPUT_REQUIRED`.
     */
    funding: {
        kind: 'deposit' | null;
        policy: DepositPolicy | null;
        methods: FundingMethod[];
        picks: Money[];
        quote: DepositQuote | null;
    };
    /** Pending and unknown first. */
    deposits: DepositIntent[];
    history: {
        movement: WalletMovement;
        filters: { key: WalletMovement; active: boolean; link: RouteLink }[];
        items: WalletEntry[];
        pagination: Pagination;
    };
    /** The opened receipt: a history entry with its receipt, or a deposit intent. */
    receipt: (WalletEntry & { receipt: Receipt }) | DepositIntent | null;
    /** C4 and Phase 2. */
    earnings: null;
    exports: null;
    links: InvestorAppLinks & {
        close: RouteLink;
        deposit: RouteLink;
        link_account: RouteLink;
        /** The operation lookup; its url holds the literal `{request_id}` token. */
        operation: RouteLink;
    };
    actions: { deposit: RouteAction };
    preview_outcome?: C3PreviewOutcome<'wallet.deposit'>;
};

/* Deals and deal detail (v2 §2b) ---------------------------------------------------------- */

export type CampaignUnits = {
    total: Units;
    available: Units;
    reserved: Units;
    committed: Units;
};

/**
 * A captioned image the business published. Optional: a listing may have none, and `url` stays
 * null while the file is unavailable. No coordinates, location text or assigned-Auditor originals.
 */
export type PublishedPhoto = { url: string | null; caption: string };

export type C3DealCard = {
    campaign_id: string;
    revision: number;
    name: string;
    accent: BusinessAccent;
    industry: string;
    district: string;
    rating: InvestorRating;
    audited: boolean;
    just_listed: boolean;
    photos: PublishedPhoto[];
    raised: Money;
    target: Money;
    /** Share of the target committed, engine-rounded to one decimal: "78.1". */
    funded_pct: string;
    left_to_fill: Money;
    avg_monthly_revenue: Money;
    units: CampaignUnits;
    unit_price: Money;
    investors: number;
    lifecycle: CampaignLifecycle;
    restriction: CampaignRestriction;
    /** `[live_at, live_at + 30 days)` (MC-02), rendered against `server_time`. */
    clock: Clock;
    /** "0": the MVP listing-fee waiver (§11.1). */
    listing_fee: Money;
    story: string;
    rate_pct: string;
    term_months: number;
    links: { detail: RouteLink };
};

/** Allowlisted aggregates only (H9). EBITDA is unavailable unless it was sourced as EBITDA. */
export type C3DealFinancials = {
    avg_monthly_revenue: Money;
    ebitda:
        | { value: Money }
        | { value: null; unavailable: 'NOT_SOURCED_AS_EBITDA' };
};

/** The coarse approved description (H9): no address, team size or company code. */
export type C3AboutBusiness = {
    description: string;
    industry: string;
    district: string;
};

/**
 * The sealed report as a factual summary (H9): no report export, photo location, cash observed
 * or tolerance verdict. The reconciliation statement may cite the governed tolerance.
 */
export type AuditSummary = {
    standard: string;
    partner: string;
    licence: string;
    verified_on: string;
    /** The sealed report's digest, as a reference. */
    digest: string;
    reconciliation_statement: string;
    tolerance: Money | null;
};

export type MonthlyUpdateSummary = Omit<MonthlyUpdate, 'photos'> & {
    photos: PublishedPhoto[];
};

export type C3DealDetail = C3DealCard & {
    use_of_funds: UseOfFunds[];
    financials: C3DealFinancials;
    rationale: string;
    track_record: TrackRecord | null;
    about: C3AboutBusiness;
    audit: AuditSummary | null;
    updates: MonthlyUpdateSummary[];
    overdue_report: { month: string } | null;
};

/** Which cap binds `max_units`, so a cap-hit state can name it. */
/**
 * What stops the investor taking more notes. Robert's #99 C3 answer replaced the per-transaction,
 * per-note, per-business and aggregate caps with one single-investor cap per raise (confirmed on
 * #96), so `raise_cap` is the only investor limit left.
 */
export type CapacityBinding =
    | 'raise_cap'
    | 'availability'
    | 'restriction'
    | 'connected_party';

export type InvestorCapacity = {
    max_units: Units;
    binding: CapacityBinding;
    /** What the Party may still commit to this raise under the single-investor cap. */
    remaining: { raise: Money };
};

/**
 * The server's quote. Indicative before a reservation; exact once ordinals are reserved, with the
 * reserved units' component rights (H3). There is no maturity date before issue (H4).
 */
export type PrimaryQuote = {
    basis: 'indicative' | 'reserved';
    units: Units;
    unit_price: Money;
    amount: Money;
    rate_pct: string;
    term_months: number;
    expected_return: Money;
    payout_fee: Money;
    maturity_value: Money;
    maturity_date: null;
    /** Set only when `basis` is `reserved`. */
    rights: UnitRights | null;
    revision: number;
    capacity: InvestorCapacity;
};

export type C3InvestorDealsProps = InvestorPageContract & {
    /** Unverified: the gate only, with `deals: []` and `focus: null` (H8). */
    gate: InvestGate;
    wallet: WalletSummary;
    unread_notifications: number;
    sorts: DealSort[];
    industries: IndustryFilter[];
    deals: C3DealCard[];
    focus: C3DealDetail | null;
    quote: PrimaryQuote | null;
    links: InvestorAppLinks & {
        deposit: RouteLink;
        checkout: RouteLink | null;
    };
};

/** The phone deal page; a wide screen shows the same deal in the Deals panel over `home`. */
export type C3InvestorDealProps = InvestorPageContract & {
    deal: C3DealDetail;
    gate: InvestGate;
    quote: PrimaryQuote | null;
    home: C3InvestorDealsProps;
    links: { back: RouteLink; checkout: RouteLink | null };
};

/* Reserve, confirm and commitment (v2 §2c) ------------------------------------------------ */

/** Five minutes, or the campaign's expiry if sooner (§11.3). */
export type Reservation = {
    id: string;
    revision: number;
    campaign_id: string;
    units: Units;
    ordinals: Ordinals;
    amount: Money;
    /** The exact component rights of these ordinals. */
    rights: UnitRights;
    state: 'held' | 'confirmed' | 'released' | 'expired';
    clock: Clock;
};

/** `confirmed` is raising and cancellable; `funded` has locked cancellation. Neither is a Holding (H2). */
export type CommitmentState =
    | 'confirmed'
    | 'funded'
    | 'issued'
    | 'cancelled'
    | 'expired'
    | 'failed_closing';

/** The coarse closing view of a funded commitment (H15): no provider or evidence reference. */
export type CommitmentClosing =
    | { stage: 'awaiting_disbursement' }
    | { stage: 'in_flight'; provider: CoarseInFlight };

export type Commitment = {
    id: string;
    revision: number;
    campaign_id: string;
    deal_name: string;
    units: Units;
    ordinals: Ordinals;
    principal: Money;
    /** Bound at confirmation; issue attaches dates and never recomputes them. */
    rights: UnitRights;
    state: CommitmentState;
    cancelled_by: 'investor' | 'business' | null;
    /** Funded only. */
    closing: CommitmentClosing | null;
    terms: {
        rate_pct: string;
        term_months: number;
        payout_fee: Money;
        policy_version: string;
        disclosure_version: string;
    };
    /** PRIMARY_COMMITTED, immutable. */
    confirmation: Receipt;
    refund: Receipt | null;
    holding: { id: string; link: RouteLink } | null;
    allowed_actions: 'primary.cancel'[];
    actions: { cancel: RouteAction | null };
    link: RouteLink;
};

export type C3InvestorCheckoutProps = InvestorPageContract & {
    deal: Pick<
        C3DealCard,
        | 'campaign_id'
        | 'revision'
        | 'name'
        | 'accent'
        | 'rate_pct'
        | 'term_months'
        | 'lifecycle'
        | 'restriction'
        | 'clock'
        | 'unit_price'
    >;
    quote: PrimaryQuote;
    quick_picks: { units: Units; amount: Money }[];
    /** Primary checkout is paid from the available bucket only. */
    wallet: { available: Money; sufficient: boolean; revision: number };
    disclosure: { version: string; sha256: string; points: string[] };
    reservation: Reservation | null;
    commitment: Commitment | null;
    refusal: { code: string; status: number } | null;
    home: C3InvestorDealsProps | null;
    /**
     * The app links travel with the page, not only with `home`, so the shell keeps its navigation
     * when there is no Deals home to draw beneath the sheet.
     */
    links: InvestorAppLinks & {
        close: RouteLink;
        deposit: RouteLink;
        operation: RouteLink;
    };
    actions: {
        reserve: RouteAction;
        confirm: RouteAction | null;
        release: RouteAction | null;
    };
    preview_outcome?: C3PreviewOutcome<InvestorAllowedAction>;
};

/**
 * One commitment, opened from the portfolio's "Awaiting issue" section (`investor.commitments.show`).
 * `commitment` is null with a scoped `refusal` when the Investor may no longer read it.
 */
export type C3InvestorCommitmentProps = InvestorPageContract & {
    commitment: Commitment | null;
    refusal: { code: string; status: number } | null;
    links: InvestorAppLinks & { close: RouteLink; operation: RouteLink };
    preview_outcome?: C3PreviewOutcome<'primary.cancel'>;
};

/* Settlement, issue and holdings (v2 §2d) ------------------------------------------------- */

/** One Holding per commitment, issued only on a verified and reconciled disbursement success. */
export type IssuedHolding = {
    id: string;
    revision: number;
    note_id: string;
    campaign_id: string;
    units: Units;
    ordinals: Ordinals;
    principal: Money;
    /** The same allocation as the commitment. */
    rights: UnitRights;
    /** The local recorded issue time (H17). */
    issued_at: string;
    /** The authenticated provider effective instant; never a callback's arrival or browser time. */
    disbursement_effective_at: string;
    /** Its Africa/Kigali date, which anchors the schedule (§11.4). */
    effective_date: string;
    terms: {
        rate_pct: string;
        term_months: number;
        total_return: Money;
        policy_version: string;
        disclosure_version: string;
    };
    schedule: {
        index: number;
        due_on: string;
        principal: Money;
        return: Money;
    }[];
    /** HOLDING_ISSUED. */
    issue_receipt: Receipt;
    source: { commitment_id: string; disbursement_operation_id: string };
};

export type CommitmentSummary = Pick<
    Commitment,
    'id' | 'deal_name' | 'units' | 'principal' | 'state' | 'closing' | 'link'
>;

/** Commitments are `confirmed` or `funded` only, and never counted as holdings. */
export type C3InvestorPortfolioProps = InvestorPortfolioProps &
    InvestorPageContract & { commitments: CommitmentSummary[] };

/** Servicing fields keep their C3 empty states; they are C4 facts. */
export type C3HoldingDetail = Omit<HoldingDetail, 'updates'> & {
    updates: MonthlyUpdateSummary[];
    issue: IssuedHolding;
};

export type C3InvestorHoldingProps = InvestorPageContract & {
    holding: C3HoldingDetail;
    links: InvestorAppLinks & { back: RouteLink };
};
