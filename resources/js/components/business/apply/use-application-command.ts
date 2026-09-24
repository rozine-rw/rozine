import { useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import {
    OPERATION_CODES,
    isUncertainStatus,
    readErrorCode,
} from '@/components/business/apply/operation-outcome';
import type { RouteAction, RouteLink } from '@/types';
import type {
    ApplicationCommand,
    ApplyPreviewOutcome,
    BusinessApplyProps,
    OperationResource,
} from '@/types/business';

/**
 * What the wizard tells the business about its last command, beyond "busy":
 * - `checking` — the answer was lost, so the recorded outcome is being looked up (an unknown
 *   outcome is client state only; the server never reports one);
 * - `unconfirmed` — the lookup could not be reached; nothing is resent until it answers;
 * - `pending` — the server recorded the command and is still working on it;
 * - `not_recorded` — the lookup found no result, so the identical command may be sent again;
 * - `refused` — a definitive answer: stale facts, a conflict, a denial or a scoped not-found.
 */
export type CommandNotice =
    | { kind: 'checking' }
    | { kind: 'unconfirmed' }
    | { kind: 'pending' }
    | { kind: 'not_recorded' }
    | { kind: 'refused'; code: string; status: number };

type Attempt =
    | { kind: 'resource'; resource: OperationResource }
    | { kind: 'invalid' }
    | { kind: 'failed'; status: number; code: string | null }
    | { kind: 'unreachable' };

/** A refusal without a body code still maps to the contract's generic codes. */
const STATUS_CODES: Record<number, string> = {
    403: 'ACTION_FORBIDDEN',
    404: 'NOT_FOUND',
    409: 'VERSION_CONFLICT',
};

const fallbackCode = (status: number): string =>
    STATUS_CODES[status] ?? 'REQUEST_FAILED';

const initialNotice = (
    preview: ApplyPreviewOutcome | undefined,
): CommandNotice | null => {
    if (preview === undefined) {
        return null;
    }

    return preview.kind === 'unconfirmed'
        ? { kind: 'unconfirmed' }
        : { kind: 'refused', code: preview.code, status: 409 };
};

type Options = {
    actions: BusinessApplyProps['actions'];
    lookup: RouteLink;
    preview?: ApplyPreviewOutcome;
    onCompleted: (
        command: ApplicationCommand,
        resource: OperationResource,
    ) => void;
    onRefused: (
        command: ApplicationCommand,
        code: string,
        status: number,
    ) => void;
};

/**
 * Sends the application's JSON commands through Inertia's `useHttp` (business-application-v1
 * points 2 and 7). One command is in flight at a time, and a command whose outcome is unknown
 * (no answer, a 5xx or a timeout) stays held — same payload, same `request_id` — until the
 * operation lookup settles it. It is resent only when the lookup reports no recorded result.
 * Any definitive answer, completed or rejected, releases it: rejections are journalled for good,
 * so the next command the page builds always carries a new `request_id`. An operation's
 * `server_time` describes when it ran, so a replayed result is never read as the current clock.
 */
export function useApplicationCommand({
    actions,
    lookup,
    preview,
    onCompleted,
    onRefused,
}: Options) {
    const http = useHttp<Record<string, never>, OperationResource>();
    const held = useRef<ApplicationCommand | null>(
        preview?.kind === 'unconfirmed' ? preview.command : null,
    );
    const inFlight = useRef(false);
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState<CommandNotice | null>(() =>
        initialNotice(preview),
    );

    const request = async (
        route: RouteAction | RouteLink,
        body: Record<string, unknown>,
    ): Promise<Attempt> => {
        let failure: Attempt = { kind: 'unreachable' };

        http.transform(() => body);

        try {
            const resource = (await http.submit(route, {
                onHttpException: (response) => {
                    failure = {
                        kind: 'failed',
                        status: response.status,
                        code: readErrorCode(response.data),
                    };
                },
            })) as OperationResource | undefined;

            /* A 422 resolves without a body: useHttp has put the field errors in `errors`. */
            return resource === undefined
                ? { kind: 'invalid' }
                : { kind: 'resource', resource };
        } catch {
            return failure;
        }
    };

    const refuse = (
        command: ApplicationCommand,
        code: string,
        status: number,
    ) => {
        held.current = null;
        setNotice({ kind: 'refused', code, status });
        onRefused(command, code, status);
    };

    const conclude = (
        command: ApplicationCommand,
        resource: OperationResource,
    ) => {
        if (resource.status === 'completed') {
            held.current = null;
            setNotice(null);
            onCompleted(command, resource);

            return;
        }

        if (resource.status === 'pending') {
            setNotice({ kind: 'pending' });

            return;
        }

        refuse(command, resource.code, 200);
    };

    const lookUp = async (command: ApplicationCommand) => {
        setNotice({ kind: 'checking' });

        const attempt = await request(
            {
                url: lookup.url.replace(
                    '{request_id}',
                    encodeURIComponent(command.payload.request_id),
                ),
                method: 'get',
            },
            { command: command.name },
        );

        if (attempt.kind === 'resource') {
            conclude(command, attempt.resource);

            return;
        }

        if (
            attempt.kind === 'failed' &&
            attempt.code === OPERATION_CODES.notFound
        ) {
            setNotice({ kind: 'not_recorded' });

            return;
        }

        if (attempt.kind === 'failed' && attempt.status === 403) {
            refuse(command, attempt.code ?? fallbackCode(403), 403);

            return;
        }

        setNotice({ kind: 'unconfirmed' });
    };

    const dispatch = async (command: ApplicationCommand) => {
        const attempt = await request(actions[command.name], command.payload);

        if (attempt.kind === 'resource') {
            conclude(command, attempt.resource);

            return;
        }

        if (attempt.kind === 'invalid') {
            held.current = null;

            return;
        }

        if (attempt.kind === 'failed' && !isUncertainStatus(attempt.status)) {
            refuse(
                command,
                attempt.code ?? fallbackCode(attempt.status),
                attempt.status,
            );

            return;
        }

        await lookUp(command);
    };

    const run = async (work: () => Promise<void>) => {
        inFlight.current = true;
        setBusy(true);

        try {
            await work();
        } finally {
            inFlight.current = false;
            setBusy(false);
        }
    };

    /** Sends a new command, unless another is in flight or still unresolved. */
    const send = (command: ApplicationCommand): boolean => {
        if (inFlight.current || held.current !== null) {
            return false;
        }

        held.current = command;
        http.clearErrors();
        setNotice(null);
        void run(() => dispatch(command));

        return true;
    };

    /** Asks the operation lookup again about the held command. */
    const checkAgain = () => {
        const command = held.current;

        if (!inFlight.current && command !== null) {
            void run(() => lookUp(command));
        }
    };

    /** Resends the held command unchanged, once the lookup has found no recorded result. */
    const retry = () => {
        const command = held.current;

        if (
            !inFlight.current &&
            command !== null &&
            notice?.kind === 'not_recorded'
        ) {
            setNotice(null);
            void run(() => dispatch(command));
        }
    };

    return {
        busy,
        notice,
        /** A command is held until its outcome is known; no other command may start meanwhile. */
        unresolved: notice !== null && notice.kind !== 'refused',
        errors: http.errors as Record<string, string | undefined>,
        send,
        checkAgain,
        retry,
    };
}
