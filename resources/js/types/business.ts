import type { Money } from './money';
import type { RouteLink } from './routing';

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
