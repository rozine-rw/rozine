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
    seal: {
        do: 'auditor.audit.guide.seal.do',
        enter: 'auditor.audit.guide.seal.enter',
        done: 'auditor.audit.guide.seal.done',
        how: 'auditor.audit.guide.seal.how',
    },
};
