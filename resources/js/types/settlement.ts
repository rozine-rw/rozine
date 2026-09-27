import type { Money } from './money';
import type { OperationCommand } from './operation';
import type { RouteLink } from './routing';

/**
 * Shared checkpoint 3 primitives (C3 contract proposal v2 §3): wallet, primary commitment,
 * settlement/issue and disbursement. They are additive: the existing `Money`, `RouteLink` /
 * `RouteAction`, `OperationResource` / `OperationCommand` and the Admin `Attribution` /
 * `TrailEntry` are unchanged, and nothing here migrates an Auditor or Phase 1B type.
 *
 * Non-activatable: no live route, controller or Wayfinder helper returns these shapes yet. The
 * pages that read them are reviewed through synthetic `preview/{fixture}` fixtures only.
 */

/** A whole number of note units, as a nonnegative integer string (engineering contract §4). */
export type Units = string;

/**
 * An immutable record of what one command recorded. Its amount, units, revision, outcome and
 * `recorded_at` never change across lookups or replays, even after a provider advances; the fresh
 * state of the record lives beside it under `current`.
 */
export type Receipt = {
    receipt_id: string;
    operation_id: string;
    request_id: string;
    /**
     * The outcome recorded, e.g. `PRIMARY_RESERVED`, `PRIMARY_COMMITTED`, `DEPOSIT_INTENT_RECORDED`,
     * `DEPOSIT_CREDITED`, `COMMITMENT_REFUNDED`, `LISTING_PUBLISHED`, `DISBURSEMENT_INTENT_RECORDED`
     * or `HOLDING_ISSUED`. An `*_INTENT_RECORDED` code means the intent was durably recorded, not
     * that any payment completed.
     */
    code: string;
    recorded_at: string;
    amount: Money;
    units: Units | null;
    reference: string;
    revision: number;
    policy_version: string;
    disclosure_version: string | null;
    link: RouteLink;
};

/** `failed` is a verified final failure only (§10.8); an unanswered call stays `unknown`. */
export type ProviderOutcomeState =
    | 'pending'
    | 'succeeded'
    | 'failed'
    | 'unknown';

/**
 * A payment provider's outcome for one durable operation. Staff audience only: Investors and
 * Businesses see `CoarseInFlight` instead, with no provider, error or evidence reference (H15).
 */
export type ProviderOutcome = {
    state: ProviderOutcomeState;
    operation_id: string;
    provider_reference: string | null;
    error_code: string | null;
    /** Authenticated provider times; never a callback's arrival or the browser's clock. */
    observed_at: string | null;
    effective_at: string | null;
    /** An exception stays blocked and is never shown as reconciled (H13). */
    reconciliation: 'unreconciled' | 'matched' | 'exception';
    reconciled_at: string | null;
};

/** The in-flight state an Investor or Business may see: "not yet confirmed", never paid or failed. */
export type CoarseInFlight = 'pending' | 'unknown';

/** A validity window `[starts_at, expires_at)`, rendered against the page's `server_time`. */
export type Clock = { starts_at: string; expires_at: string };

/** Newest first; an empty page may still carry `next`. */
export type Pagination = { next: RouteLink | null };

/** A C3 command's `OperationResource.data`: the immutable receipt beside a fresh projection. */
export type C3OperationData<R, C> = {
    receipt: R;
    /** Freshly authorized; null when the actor may no longer read the record. */
    current: C | null;
    next: RouteLink;
};

/**
 * A command state a synthetic preview seeds so it can be reviewed without a live command. Local and
 * testing fixtures only; the server never sends it.
 */
export type C3PreviewOutcome<Name extends string> =
    | { kind: 'unconfirmed'; command: OperationCommand<Name> }
    | { kind: 'not_recorded'; command: OperationCommand<Name> }
    | { kind: 'refused'; code: string; status: number };

/**
 * Where a campaign stands. A restriction is carried separately (`CampaignRestriction`) and never
 * replaces the lifecycle, so a restricted campaign still says whether it was raising, funded or in
 * flight.
 */
export type CampaignLifecycle =
    | 'live'
    | 'fully_reserved'
    | 'funded'
    | 'disbursing'
    | 'issued'
    | 'expired'
    | 'cancelled'
    | 'failed_closing';

export type CampaignRestriction = {
    code: 'NOTE_INELIGIBLE' | 'RESTRICTION_ACTIVE';
    since: string;
} | null;

/** Unit ordinal ranges, reserved at checkout and kept through issue (H3). */
export type Ordinals = { first: Units; last: Units }[];

/**
 * The undated component rights of a set of units, from the campaign's fixed economics (§11.2).
 * Instalments are relative only: dates are attached at issue, never before (H3, H4).
 */
export type UnitRights = {
    total_return: Money;
    instalments: { index: number; principal: Money; return: Money }[];
};

/**
 * A Rozine Plus tier, from the Investor's currently active deployed capital (Robert's #99 policy).
 * Bands are lower-inclusive and upper-exclusive, in RWF: Standard below 1M; Bronze [1M, 5M);
 * Silver [5M, 30M); Gold [30M, 100M); Platinum [100M, 250M); Diamond from 250M. The server picks
 * the tier; the client never derives it from a balance.
 */
export type PlusTier =
    | 'standard'
    | 'bronze'
    | 'silver'
    | 'gold'
    | 'platinum'
    | 'diamond';

/**
 * The Plus fee on earnings, which replaces the 1% investor repayment fee. It applies to the return
 * portion of each payout only, never to principal, and its rate is locked at commitment for the
 * note's life. A tier or rate that changes between reserve and confirm is refused with
 * `DISCLOSURE_STALE` (409) and must be reconfirmed.
 *
 * Provisional: the shape follows Hussain's acceptance on #96; the policy is Robert's #99 answer.
 */
export type EarningsFee = {
    tier: PlusTier;
    /** The locked rate: `1000` is 10.0%. */
    rate_bps: Bps;
    basis: 'return_only';
    policy_version: string;
};

/* ------------------------------------------------------------------------------------------ */
/* Checkpoint 4: servicing primitives (C4 contract proposal v1 §3)                             */
/* ------------------------------------------------------------------------------------------ */

/*
 * Additive and non-activatable, like the C3 primitives above. Nothing returns these shapes yet:
 * the pages that read them are reviewed through synthetic `preview/{fixture}` fixtures only, and
 * every figure stays a server fact. The client never derives DPD, a ladder step or "days left"
 * from the browser clock, and never adds, subtracts or splits money.
 */

/** An Africa/Kigali calendar date, `YYYY-MM-DD`, with no time part (contractual dates only). */
export type KigaliDate = string;

/** Integer basis points (engineering contract §4): `500` is 5%. */
export type Bps = number;

/** `processing`: a receipt is recorded and its allocation has not posted yet. */
export type InstalmentStatus =
    | 'upcoming'
    | 'due'
    | 'overdue'
    | 'processing'
    | 'partially_paid'
    | 'paid';

/**
 * Where a note's servicing stands. `due_today` is DPD 0 on the oldest unpaid instalment and
 * `overdue` is DPD 1 or more (MC-03). `defaulted` is reserved: nothing emits it before Phase 2.
 */
export type ServicingState =
    | 'current'
    | 'due_today'
    | 'overdue'
    | 'repaid'
    | 'defaulted';

/** Server-summed components; the client never adds them up. */
export type ComponentAmounts = {
    principal: Money;
    return: Money;
    late_fees: Money;
    service_fee: Money;
    total: Money;
};

/**
 * A late-fee ladder step. Provisional pending #99 R1/R2 and the §8.5 amendment: the steps follow
 * Robert's adopted #99 N4 (+5% at the due date, day 7 and day 30), but the base each step applies
 * to, its DPD mapping and whether it replaces §8.5 are still open. The server sends all of them.
 */
export type LateFeeStep = 'due_date' | 'day_7' | 'day_30';

export type LateFeeStatus =
    | 'projected'
    | 'assessed'
    | 'partially_collected'
    | 'collected'
    | 'waived';

/**
 * One line of an allocation: what a receipt paid, in §11.4 order, then where it went. The
 * `investor_late_fee` line is provisional pending the #99 C7 amendment (late-fee pass-through).
 */
export type AllocationKind =
    | 'principal'
    | 'return'
    | 'late_fee'
    | 'service_fee'
    | 'steward_share'
    | 'investor_gross'
    | 'investor_fee'
    | 'investor_net'
    | 'investor_late_fee'
    | 'psp_fee'
    | 'unapplied';
