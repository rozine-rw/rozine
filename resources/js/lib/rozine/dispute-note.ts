import type { AuditDispute } from '@/types/audit-dispute';

/** Who recorded a dispute's `resolution_note`, as far as its status and outcome say. */
export type DisputeNoteAuthor = 'cpa' | 'staff' | 'unknown';

/**
 * Attributes the note by the decision it was recorded with, never by guesswork (N6, #96):
 * - a staff outcome (`amendment_required`, or `upheld` published by staff) carries staff's note;
 * - an escalated dispute with no outcome yet was upheld by the CPA, whose required reason it is;
 * - anything else — an `amended` resolution, which the CPA or staff may have closed — is unknown,
 *   and reads as a neutral review note.
 */
export const disputeNoteAuthor = (dispute: AuditDispute): DisputeNoteAuthor => {
    if (
        dispute.outcome === 'amendment_required' ||
        dispute.outcome === 'upheld'
    ) {
        return 'staff';
    }

    if (dispute.status === 'escalated' && dispute.outcome === null) {
        return 'cpa';
    }

    return 'unknown';
};
