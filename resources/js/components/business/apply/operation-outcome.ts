/**
 * The operation codes the apply wizard acts on (business-application-v1, confirmed on
 * rozine-rw/rozine#96). The Resource's `status` says whether an operation completed, is pending or
 * was rejected; these codes pick out the outcomes that change what the page does next.
 */
export const OPERATION_CODES = {
    /** Every required signature is in: the application is submitted. */
    submitted: 'APPLICATION_SUBMITTED',
    /**
     * The lookup has no recorded outcome (HTTP 404). It does not prove the attempt failed: a
     * retry resends the identical body and `request_id`, which the server serializes against
     * the first attempt.
     */
    notFound: 'OPERATION_NOT_FOUND',
} as const;

/** Refusals the current person cannot fix by refreshing: a denial or a record out of scope. */
const FINAL_REFUSALS = new Set([
    'ACTION_FORBIDDEN',
    'MANDATE_REQUIRED',
    'NOT_FOUND',
]);

/** Whether a refusal calls for fresh facts: stale versions and conflicts do, denials do not. */
export const refusalRefreshes = (code: string, status: number): boolean =>
    status !== 403 && status !== 404 && !FINAL_REFUSALS.has(code);

/**
 * Whether an HTTP failure leaves the command's outcome unknown: the request may have been
 * recorded even though no answer came back, so it is looked up rather than resent.
 */
export const isUncertainStatus = (status: number): boolean =>
    status === 408 || status >= 500;

/** The stable code in an error body, if the server sent one. */
export const readErrorCode = (body: unknown): string | null => {
    let parsed: unknown = body;

    if (typeof body === 'string') {
        try {
            parsed = JSON.parse(body);
        } catch {
            return null;
        }
    }

    if (
        typeof parsed === 'object' &&
        parsed !== null &&
        'code' in parsed &&
        typeof parsed.code === 'string'
    ) {
        return parsed.code;
    }

    return null;
};
