import {
    OPERATION_NOT_FOUND,
    refusalRefreshes as refreshesUnless,
} from '@/lib/rozine/operation';

export { isUncertainStatus, readErrorCode } from '@/lib/rozine/operation';

/**
 * The operation codes the apply wizard acts on (business-application-v1, confirmed on
 * rozine-rw/rozine#96). The Resource's `status` says whether an operation completed, is pending or
 * was rejected; these codes pick out the outcomes that change what the page does next.
 */
export const OPERATION_CODES = {
    /** Every required signature is in: the application is submitted. */
    submitted: 'APPLICATION_SUBMITTED',
    /** The lookup has no recorded outcome; the identical command may be resent. */
    notFound: OPERATION_NOT_FOUND,
} as const;

/** Refusals the current person cannot fix by refreshing: a denial or a record out of scope. */
const FINAL_REFUSALS: ReadonlySet<string> = new Set([
    'ACTION_FORBIDDEN',
    'MANDATE_REQUIRED',
    'NOT_FOUND',
]);

/** Whether a refusal calls for fresh facts: stale versions and conflicts do, denials do not. */
export const refusalRefreshes = (code: string, status: number): boolean =>
    refreshesUnless(code, status, FINAL_REFUSALS);
