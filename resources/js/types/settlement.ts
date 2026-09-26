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
