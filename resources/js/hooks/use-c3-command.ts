import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import type { CommandNotice } from '@/hooks/use-operation-command';
import { refusalRefreshes } from '@/lib/rozine/operation';
import type { RouteAction, RouteLink } from '@/types';
import type { OperationCommand, OperationResource } from '@/types/operation';
import type { C3OperationData, C3PreviewOutcome } from '@/types/settlement';

/**
 * Recorded 422s that refuse a C3 command as a whole rather than a field to correct (v2 §2
 * Recovery): the page is told, as for any refusal.
 */
export const C3_REFUSALS_422: ReadonlySet<string> = new Set([
    'EXPOSURE_LIMIT',
    'INSUFFICIENT_AVAILABLE_FUNDS',
]);

/** Refusals that fresh facts cannot change. Denials and scoped not-founds never refresh either. */
const FINAL: ReadonlySet<string> = new Set(['IDEMPOTENCY_CONFLICT']);

export type C3Resource = OperationResource<C3OperationData<unknown, unknown>>;

/** The held command and notice a synthetic preview seeds. */
export function seedFromPreview<Name extends string>(
    preview: C3PreviewOutcome<Name> | undefined,
): { held: OperationCommand<Name> | null; notice: CommandNotice | null } {
    switch (preview?.kind) {
        case 'unconfirmed':
        case 'not_recorded':
            return { held: preview.command, notice: { kind: preview.kind } };
        case 'refused':
            return {
                held: null,
                notice: {
                    kind: 'refused',
                    code: preview.code,
                    status: preview.status,
                },
            };
        default:
            return { held: null, notice: null };
    }
}

type Options<Name extends string> = {
    /** Where each command goes; a command whose route is null is never sent. */
    actions: Partial<Record<Name, RouteAction | null>>;
    /** The operation lookup, whose url holds the literal `{request_id}` token. */
    lookup: RouteLink;
    /** Extra lookup query, e.g. a marketplace page's `identity_context_revision`. */
    lookupQuery?: Record<string, string | number>;
    /** The page's current `allowed_actions`; a retry is offered only while they still permit it. */
    allowed: readonly string[];
    preview?: C3PreviewOutcome<Name>;
    /** The props a refresh reloads; undefined reloads them all. */
    only?: string[];
};

/**
 * A C3 command through the shared operation command (v2 §2). No optimistic state: a completion
 * follows the server's `next` page, whose facts are read afresh, so no balance, commitment or
 * Holding is ever drawn before the server says so. An uncertain answer (a 5xx such as 503
 * `RETRYABLE_CONTENTION`, a timeout, a lost connection) is looked up with the same `request_id`
 * and never resent on its own; `OPERATION_NOT_FOUND` refreshes the page's facts and then offers an
 * explicit same-key retry only while `allowed` still lists the command.
 */
export function useC3Command<Name extends string>({
    actions,
    lookup,
    lookupQuery,
    allowed,
    preview,
    only,
}: Options<Name>) {
    const scope = only === undefined ? {} : { only };
    const [last, setLast] = useState<Name | null>(
        preview !== undefined && preview.kind !== 'refused'
            ? preview.command.name
            : null,
    );
    const command = useOperationCommand<OperationCommand<Name>, C3Resource>({
        actions: (sent) => actions[sent.name as Name] as RouteAction,
        lookup,
        lookupQuery,
        refusals422: C3_REFUSALS_422,
        initial: seedFromPreview(preview),
        refresh: () => reloadPreservingState(scope),
        onCompleted: (_sent, resource) => {
            if (resource.data === null) {
                void reloadPreservingState(scope);

                return;
            }

            router.visit(resource.data.next);
        },
        onRefused: (_sent, code, status) => {
            if (refusalRefreshes(code, status, FINAL)) {
                void reloadPreservingState(scope);
            }
        },
    });

    /** Sends a new command with a fresh `request_id`, if its route exists and nothing is held. */
    const send = (name: Name, payload: Record<string, unknown>): boolean => {
        if (!actions[name]) {
            return false;
        }

        const sent = command.send({
            name,
            payload: { request_id: crypto.randomUUID(), ...payload },
        });

        if (sent) {
            setLast(name);
        }

        return sent;
    };

    return {
        ...command,
        send,
        /** Whether "Try again" may resend the held command under the page's current actions. */
        retryAllowed: last !== null && allowed.includes(last),
    };
}
