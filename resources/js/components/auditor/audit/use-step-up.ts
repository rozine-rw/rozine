import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { readErrorCode, readRetryAfter } from '@/lib/rozine/operation';
import type { RouteAction } from '@/types';
import type { StepUpProof } from '@/types/auditor';

/** What the seal's step-up said about one authenticator code. */
export type StepUpResult =
    | { kind: 'proof'; proof: StepUpProof }
    /** The code did not match (HTTP 422); the server's own words, when it sent any. */
    | { kind: 'invalid'; message: string | null }
    | {
          kind: 'refused';
          status: number;
          code: string | null;
          /** Seconds to wait before another code, from `Retry-After` on a 429. */
          retryAfter: number | null;
      }
    | { kind: 'unreachable' };

/** The facts a step-up binds the proof to, plus the one-time code. */
export type StepUpRequest = {
    audit_id: string;
    expected_revision: number;
    digest: string;
    identity_context_revision: number;
    request_id: string;
    code: string;
};

/**
 * The seal's fresh-user confirmation (auditor-filing-v1 point 2): the partner's confirmed
 * authenticator code is exchanged for an opaque, single-use proof bound to the account, context,
 * report revision and digest. The code travels in this one request and nowhere else — not the
 * command journal, a retry, storage or the URL — and the request body is dropped as soon as the
 * answer arrives. It confirms who is sealing; it says nothing about the capture devices. The
 * route comes with each request, from a stage that enables the step-up.
 */
export function useStepUp() {
    const http = useHttp<Record<string, never>, StepUpProof>();
    const [checking, setChecking] = useState(false);

    const verify = async (
        route: RouteAction,
        request: StepUpRequest,
    ): Promise<StepUpResult> => {
        let failure: StepUpResult = { kind: 'unreachable' };
        let message: string | null = null;
        /* Dropped once the answer arrives, so nothing keeps the code after its request. */
        let pending: StepUpRequest | null = request;

        setChecking(true);
        http.transform(() => ({ ...pending }));

        try {
            const proof = await http.submit(route, {
                onError: (errors) => {
                    message = errors.code ?? null;
                },
                onHttpException: (response) => {
                    failure = {
                        kind: 'refused',
                        status: response.status,
                        code: readErrorCode(response.data),
                        retryAfter: readRetryAfter(response.headers),
                    };
                },
            });

            /* A 422 resolves without a body, after `onError` has the code's field error. */
            return proof === undefined
                ? { kind: 'invalid', message }
                : { kind: 'proof', proof };
        } catch {
            return failure;
        } finally {
            pending = null;
            setChecking(false);
        }
    };

    return { checking, verify };
}
