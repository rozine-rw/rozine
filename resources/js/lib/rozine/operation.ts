/**
 * Shared reading of operation outcomes for the JSON command contracts (business-application-v1
 * and auditor-filing-v1, rozine-rw/rozine#96). The Resource's `status` says whether an operation
 * completed, is pending or was rejected; these helpers classify the HTTP failures around it.
 */

/**
 * The lookup has no recorded outcome (HTTP 404). It does not prove the attempt failed: a retry
 * resends the identical body and `request_id`, which the server serializes against the first.
 */
export const OPERATION_NOT_FOUND = 'OPERATION_NOT_FOUND';

/** A refusal without a body code still maps to the contract's generic codes. */
const STATUS_CODES: Record<number, string> = {
    403: 'ACTION_FORBIDDEN',
    404: 'NOT_FOUND',
    409: 'VERSION_CONFLICT',
};

export const fallbackCode = (status: number): string =>
    STATUS_CODES[status] ?? 'REQUEST_FAILED';

/**
 * Whether a refusal calls for fresh facts: stale versions and conflicts do; denials, scoped
 * not-founds and the contract's `final` codes do not.
 */
export const refusalRefreshes = (
    code: string,
    status: number,
    final: ReadonlySet<string>,
): boolean => status !== 403 && status !== 404 && !final.has(code);

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

/**
 * Seconds to wait from a `Retry-After` header given in seconds, or null when it is absent or not a
 * whole number. The wait is a duration from the response, never read against a clock.
 */
export const readRetryAfter = (
    headers: Record<string, string> | undefined,
): number | null => {
    const value = headers?.['retry-after'] ?? headers?.['Retry-After'];

    return value !== undefined && /^\d+$/u.test(value.trim())
        ? Number(value.trim())
        : null;
};
