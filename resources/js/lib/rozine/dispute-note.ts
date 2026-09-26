import type { AuditDispute } from '@/types/audit-dispute';

/** Who recorded a dispute's `resolution_note`: `unknown` reads as a neutral review note. */
export type DisputeNoteAuthor = 'cpa' | 'staff' | 'unknown';

/**
 * Attributes the note only by the server's `resolution_note_by` (N6, #96), never by the dispute's
 * status or outcome: staff can escalate an unacted-on case with a reason of their own, which the
 * outcome alone could not tell apart from a CPA's uphold. A note sent with no author is neutral.
 */
export const disputeNoteAuthor = (dispute: AuditDispute): DisputeNoteAuthor =>
    dispute.resolution_note_by ?? 'unknown';
