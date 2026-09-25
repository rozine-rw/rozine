import { http as transport } from '@inertiajs/core';
import { router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import {
    OPERATION_NOT_FOUND,
    fallbackCode,
    isUncertainStatus,
    readErrorCode,
} from '@/lib/rozine/operation';
import type { RouteAction, RouteLink } from '@/types';
import type { OperationCommand, OperationResource } from '@/types/operation';

/**
 * What a page tells its person about their last command, beyond "busy":
 * - `checking` — the answer was lost, so the recorded outcome is being looked up (an unknown
 *   outcome is client state only; the server never reports one);
 * - `unconfirmed` — the lookup could not be reached; nothing is resent until it answers;
 * - `pending` — the server recorded the command and is still working on it;
 * - `not_recorded` — the lookup found no result and the page's facts have been refreshed, so the
 *   identical command may be sent again;
 * - `refused` — a definitive answer: stale facts, a conflict, a denial or a scoped not-found.
 */
export type CommandNotice =
    | { kind: 'checking' }
    | { kind: 'unconfirmed' }
    | { kind: 'pending' }
    | { kind: 'not_recorded' }
    | { kind: 'refused'; code: string; status: number };

/**
 * How a completion became known. `recovered` is true when the operation lookup reported it after an
 * unknown outcome: its recorded snapshot is a historical receipt, which a later revision may have
 * overtaken, so a page reads its facts afresh rather than showing that snapshot as current.
 */
export type Completion = { recovered: boolean };

type Attempt<R> =
    | { kind: 'resource'; resource: R }
    | { kind: 'invalid'; errors: Record<string, unknown>; code: string | null }
    | { kind: 'failed'; status: number; code: string | null }
    | { kind: 'unreachable' };

type Options<C extends OperationCommand, R> = {
    /**
     * Where each command goes: by command name, or read from the command itself when each record
     * carries its own command routes.
     */
    actions: Record<C['name'], RouteAction> | ((command: C) => RouteAction);
    /**
     * The operation lookup. Its url holds the literal `{request_id}` token, which is replaced; the
     * command name goes as the `command` query.
     */
    lookup: RouteLink;
    /**
     * Further query parameters the lookup needs beside `command`, e.g. the Business contract's
     * `identity_context_revision`. Read when the lookup is sent.
     */
    lookupQuery?: Record<string, string | number>;
    /**
     * Recorded 422 codes that mean a refusal of the whole command rather than a field to correct
     * (e.g. `APPLICATION_STEP_INVALID`): the page is told, as for any refusal, instead of only
     * showing field errors. Direct answers and lookup replays alike.
     */
    refusals422?: ReadonlySet<string>;
    /** A command a synthetic preview seeds as already sent, with what is known about it. */
    initial?: { held: C | null; notice: CommandNotice | null };
    /**
     * Reloads the page's authorized facts, keeping its state, before a retry is offered after the
     * lookup found no recorded result. It must not touch the held command.
     */
    refresh: () => Promise<void>;
    onCompleted: (command: C, resource: R, completion: Completion) => void;
    onRefused: (command: C, code: string, status: number) => void;
};

/**
 * Sends a page's JSON commands through Inertia's `useHttp` and returns the shared operation
 * Resource. One command is in flight at a time, and a command whose outcome is unknown (no answer,
 * a 5xx or a timeout) stays held — same payload, same `request_id` — until the operation lookup
 * settles it. It is resent only when the lookup reports no recorded result. Any definitive answer,
 * completed or rejected, releases it: rejections are journalled for good, so the next command the
 * page builds always carries a new `request_id`. An operation's `server_time` describes its
 * response, so it is never read as the page's clock.
 */
export function useOperationCommand<
    C extends OperationCommand,
    R extends Pick<OperationResource<unknown>, 'status' | 'code'>,
>({
    actions,
    lookup,
    lookupQuery,
    refusals422,
    initial,
    refresh,
    onCompleted,
    onRefused,
}: Options<C, R>) {
    const http = useHttp<Record<string, never>, R>();
    const held = useRef<C | null>(initial?.held ?? null);
    const inFlight = useRef(false);
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState<CommandNotice | null>(
        initial?.notice ?? null,
    );

    const request = async (
        route: RouteAction | RouteLink,
        body: Record<string, unknown>,
    ): Promise<Attempt<R>> => {
        let failure: Attempt<R> = { kind: 'unreachable' };
        let fieldErrors: Record<string, unknown> = {};
        let validationCode: string | null = null;
        /*
         * useHttp hands a 422 over as field errors only, so the recorded code is read from the
         * response itself while this command's request is out.
         */
        const stopReading =
            refusals422 === undefined
                ? null
                : transport.onResponse((response) => {
                      if (response.status === 422) {
                          validationCode = readErrorCode(response.data);
                      }

                      return response;
                  });

        http.transform(() => body);

        try {
            const resource = (await http.submit(route, {
                onError: (errors) => {
                    fieldErrors = errors;
                },
                onHttpException: (response) => {
                    failure = {
                        kind: 'failed',
                        status: response.status,
                        code: readErrorCode(response.data),
                    };
                },
            })) as R | undefined;

            /* A 422 resolves without a body: useHttp has put the field errors in `errors`. */
            return resource === undefined
                ? { kind: 'invalid', errors: fieldErrors, code: validationCode }
                : { kind: 'resource', resource };
        } catch {
            return failure;
        } finally {
            stopReading?.();
        }
    };

    const refuse = (command: C, code: string, status: number) => {
        held.current = null;
        setNotice({ kind: 'refused', code, status });
        onRefused(command, code, status);
    };

    const conclude = (command: C, resource: R, recovered: boolean) => {
        if (resource.status === 'completed') {
            held.current = null;
            setNotice(null);
            onCompleted(command, resource, { recovered });

            return;
        }

        if (resource.status === 'pending') {
            setNotice({ kind: 'pending' });

            return;
        }

        refuse(command, resource.code, 200);
    };

    /**
     * A validation refusal (HTTP 422) is definitive: useHttp has put its field errors on the page
     * for a correction, which goes as a new command with a new `request_id` — never the same one
     * again. A 422 with no field errors to show still says the command was refused, and a 422
     * whose code the page names in `refusals422` is a refusal of the command as a whole.
     */
    const invalid = (
        command: C,
        errors: Record<string, unknown>,
        code: string | null,
    ) => {
        if (code !== null && refusals422?.has(code)) {
            refuse(command, code, 422);

            return;
        }

        held.current = null;
        setNotice(
            Object.keys(errors).length === 0
                ? { kind: 'refused', code: fallbackCode(422), status: 422 }
                : null,
        );
    };

    const lookUp = async (command: C) => {
        setNotice({ kind: 'checking' });

        const attempt = await request(
            {
                url: lookup.url.replace(
                    '{request_id}',
                    encodeURIComponent(command.payload.request_id),
                ),
                method: 'get',
            },
            { ...lookupQuery, command: command.name },
        );

        if (attempt.kind === 'resource') {
            conclude(command, attempt.resource, true);

            return;
        }

        /* The lookup replayed a recorded 422: the command was refused, not lost. */
        if (attempt.kind === 'invalid') {
            invalid(command, attempt.errors, attempt.code);

            return;
        }

        if (attempt.kind === 'failed' && attempt.code === OPERATION_NOT_FOUND) {
            /*
             * No recorded result is not a failure. The page's authorized facts are refreshed first,
             * still under "checking"; the held command — its original body and `request_id` — is
             * kept exactly as sent, never rebuilt from the newer props.
             */
            await refresh();
            setNotice({ kind: 'not_recorded' });

            return;
        }

        /*
         * The lookup replays a recorded refusal with its own status (a 409 conflict, a scoped
         * 404): that is a definitive answer, not an unknown one. A denial is final too.
         */
        if (
            attempt.kind === 'failed' &&
            !isUncertainStatus(attempt.status) &&
            (attempt.code !== null || attempt.status === 403)
        ) {
            refuse(
                command,
                attempt.code ?? fallbackCode(attempt.status),
                attempt.status,
            );

            return;
        }

        setNotice({ kind: 'unconfirmed' });
    };

    const dispatch = async (command: C) => {
        const name: C['name'] = command.name;
        const route =
            typeof actions === 'function' ? actions(command) : actions[name];
        const attempt = await request(route, command.payload);

        if (attempt.kind === 'resource') {
            conclude(command, attempt.resource, false);

            return;
        }

        if (attempt.kind === 'invalid') {
            invalid(command, attempt.errors, attempt.code);

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
    const send = (command: C): boolean => {
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

/**
 * The page's own reload — Inertia keeps component state and scroll on a reload — resolved once it
 * has finished: the `refresh` both apps pass to `useOperationCommand`. `scope` limits it to the
 * props the page asks for (a partial reload).
 */
export const reloadPreservingState = (
    scope: { only?: string[]; except?: string[] } = {},
): Promise<void> =>
    new Promise((resolve) => {
        router.reload({ ...scope, onFinish: () => resolve() });
    });
