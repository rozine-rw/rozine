import type { AdminFrameShellProps, StatValue } from '@/types/admin';
import type { RouteAction, RouteLink } from '@/types/routing';

/* ------------------------------------------------------------------------------------------ */
/* Audit Partner licence queues (ICPAR verification desk, partner change requests)            */
/* ------------------------------------------------------------------------------------------ */

/** A licence waiting for review, as the Audit Partner network's queues list it. */
export type LicenceQueueEntry = {
    id: string;
    name: string;
    licence: string;
    expires_on: string;
    submitted_at: string;
    /** Approve and reject open the 360's reason stage; absent when the viewer may not decide. */
    links: { review: RouteLink; approve?: RouteLink; reject?: RouteLink };
};

/** The Auditors directory's licence queues, sent only on the Audit Partners view. */
export type LicenceQueues = {
    /** First licences waiting for ICPAR verification, oldest first. */
    desk?: LicenceQueueEntry[];
    /** Licence renewals waiting for review, oldest first. */
    requests?: LicenceQueueEntry[];
};

/* ------------------------------------------------------------------------------------------ */
/* Audit report register (Reports oversight, `audit.reports.view`)                            */
/* ------------------------------------------------------------------------------------------ */

export type AuditRegisterStatus =
    | 'not_started'
    | 'in_progress'
    | 'overdue'
    | 'returned'
    | 'sealed'
    | 'awaiting_cosign'
    | 'published'
    | 'disputed'
    | 'escalated'
    | 'amended'
    | 'withdrawn';

export type AuditRegisterRow = {
    /** The audit assignment. */
    id: string;
    business: string;
    kind: 'flash' | 'monthly';
    /** The audited month's first day; null for a Flash Audit. */
    period: string | null;
    auditor: string | null;
    /** The licence the seal recorded; null before the seal. */
    licence: string | null;
    status: AuditRegisterStatus;
    due_at: string | null;
    /** The Business's co-sign or dispute deadline while it is owed. */
    cosign_by: string | null;
    sealed_at: string | null;
    /** The seal digest's first twelve characters. */
    digest: string | null;
    variance_pct: string | null;
    owes: 'auditor' | 'business' | null;
    reminded_at: string | null;
    links: { pdf: RouteLink | null; remind: RouteAction | null };
};

export type AuditRegisterChipKey =
    | 'all'
    | 'owed'
    | 'awaiting_cosign'
    | 'published'
    | 'disputed';

export type AdminAuditReportsProps = AdminFrameShellProps & {
    stats: {
        key: 'published' | 'awaiting_cosign' | 'overdue' | 'on_time';
        value: StatValue;
    }[];
    chips: {
        key: AuditRegisterChipKey;
        count: number;
        link: RouteLink;
        active: boolean;
    }[];
    rows: AuditRegisterRow[];
    /** The newest published reports, whichever view is chosen. */
    field: AuditRegisterRow[];
    published: AuditRegisterRow[];
};
