import { router } from '@inertiajs/react';
import { refusalRefreshes } from '@/components/business/apply/operation-outcome';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import type {
    CreateApplicationData,
    CreateApplicationEntry,
} from '@/types/business';
import type { OperationCommand, OperationResource } from '@/types/operation';

/**
 * Starts a raise with `application.create` (#96, option (a)): the common command fields with
 * `expected_revision` from the entry, an unknown outcome looked up (by `command` and
 * `identity_context_revision`) before any resend, then the server's `next`. A concurrent create
 * completes as `APPLICATION_RESUMED` with the existing draft; both completed codes, direct or
 * recovered, simply follow `next` — a fresh read, never a recorded snapshot.
 */
export function useCreateApplication(entry: CreateApplicationEntry) {
    const command = useOperationCommand<
        OperationCommand<'create'>,
        OperationResource<CreateApplicationData>
    >({
        actions: { create: entry.action },
        lookup: entry.operation,
        lookupQuery: {
            identity_context_revision: entry.identity_context_revision,
        },
        refresh: reloadPreservingState,
        onCompleted: (_sent, resource) => {
            if (resource.data === null) {
                router.reload();

                return;
            }

            router.visit(resource.data.next);
        },
        onRefused: (_sent, code, status) => {
            if (refusalRefreshes(code, status)) {
                router.reload();
            }
        },
    });

    return {
        ...command,
        /** Sends a new create, under a new `request_id`. */
        start: () =>
            command.send({
                name: 'create',
                payload: {
                    identity_context_revision: entry.identity_context_revision,
                    expected_revision: entry.expected_revision,
                    request_id: crypto.randomUUID(),
                },
            }),
    };
}
