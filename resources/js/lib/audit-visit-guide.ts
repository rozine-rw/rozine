import type { MessageCode } from '@/lib/i18n/types';
import type { AuditStepKey } from '@/types/auditor';

/** The parts a guided visit step states, in the order BRS v3 VIS-R01 lists them. */
export type VisitGuidePart = 'do' | 'enter' | 'upload' | 'done';

/** The parts a step states, in display order. */
export const VISIT_GUIDE_PARTS: readonly VisitGuidePart[] = [
    'do',
    'enter',
    'upload',
    'done',
];

/**
 * One guided visit step (BRS v3 VIS-R01): what to do, what to enter, what to upload where proof
 * is needed, and the rule checked before the next step opens, with a "How to" guide. A step
 * leaves out the parts it does not need. This is the single list of what each step asks for, so
 * the Business's visit-ready checklist (VIS-R03) can be generated from the same steps.
 */
export const VISIT_GUIDE: Record<
    AuditStepKey,
    Partial<Record<VisitGuidePart, MessageCode>> & { how: MessageCode }
> = {
    review: {
        do: 'auditor.audit.guide.review.do',
        done: 'auditor.audit.guide.review.done',
        how: 'auditor.audit.guide.review.how',
    },
    check_in: {
        do: 'auditor.audit.guide.check_in.do',
        enter: 'auditor.audit.guide.check_in.enter',
        done: 'auditor.audit.guide.check_in.done',
        how: 'auditor.audit.guide.check_in.how',
    },
    photos: {
        do: 'auditor.audit.guide.photos.do',
        upload: 'auditor.audit.guide.photos.upload',
        done: 'auditor.audit.guide.photos.done',
        how: 'auditor.audit.guide.photos.how',
    },
    ledger: {
        do: 'auditor.audit.guide.ledger.do',
        enter: 'auditor.audit.guide.ledger.enter',
        upload: 'auditor.audit.guide.ledger.upload',
        done: 'auditor.audit.guide.ledger.done',
        how: 'auditor.audit.guide.ledger.how',
    },
    statements: {
        do: 'auditor.audit.guide.statements.do',
        done: 'auditor.audit.guide.statements.done',
        how: 'auditor.audit.guide.statements.how',
    },
    count: {
        do: 'auditor.audit.guide.count.do',
        enter: 'auditor.audit.guide.count.enter',
        done: 'auditor.audit.guide.count.done',
        how: 'auditor.audit.guide.count.how',
    },
    cash_sales: {
        do: 'auditor.audit.guide.cash_sales.do',
        enter: 'auditor.audit.guide.cash_sales.enter',
        done: 'auditor.audit.guide.cash_sales.done',
        how: 'auditor.audit.guide.cash_sales.how',
    },
    costs: {
        do: 'auditor.audit.guide.costs.do',
        enter: 'auditor.audit.guide.costs.enter',
        done: 'auditor.audit.guide.costs.done',
        how: 'auditor.audit.guide.costs.how',
    },
    drawings: {
        do: 'auditor.audit.guide.drawings.do',
        enter: 'auditor.audit.guide.drawings.enter',
        done: 'auditor.audit.guide.drawings.done',
        how: 'auditor.audit.guide.drawings.how',
    },
    loans: {
        do: 'auditor.audit.guide.loans.do',
        enter: 'auditor.audit.guide.loans.enter',
        done: 'auditor.audit.guide.loans.done',
        how: 'auditor.audit.guide.loans.how',
    },
    differences: {
        do: 'auditor.audit.guide.differences.do',
        enter: 'auditor.audit.guide.differences.enter',
        done: 'auditor.audit.guide.differences.done',
        how: 'auditor.audit.guide.differences.how',
    },
    staff: {
        do: 'auditor.audit.guide.staff.do',
        enter: 'auditor.audit.guide.staff.enter',
        done: 'auditor.audit.guide.staff.done',
        how: 'auditor.audit.guide.staff.how',
    },
    expiries: {
        do: 'auditor.audit.guide.expiries.do',
        enter: 'auditor.audit.guide.expiries.enter',
        done: 'auditor.audit.guide.expiries.done',
        how: 'auditor.audit.guide.expiries.how',
    },
    loan_use: {
        do: 'auditor.audit.guide.loan_use.do',
        enter: 'auditor.audit.guide.loan_use.enter',
        done: 'auditor.audit.guide.loan_use.done',
        how: 'auditor.audit.guide.loan_use.how',
    },
    changes: {
        do: 'auditor.audit.guide.changes.do',
        enter: 'auditor.audit.guide.changes.enter',
        done: 'auditor.audit.guide.changes.done',
        how: 'auditor.audit.guide.changes.how',
    },
    seal: {
        do: 'auditor.audit.guide.seal.do',
        enter: 'auditor.audit.guide.seal.enter',
        done: 'auditor.audit.guide.seal.done',
        how: 'auditor.audit.guide.seal.how',
    },
};
