import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import type { CommandNotice, Completion } from '@/hooks/use-operation-command';
import type { RouteLink } from '@/types';
import type {
    ApplicationCommand,
    ApplyPreviewOutcome,
    BusinessApplyProps,
    OperationResource,
} from '@/types/business';

export type { CommandNotice };

const initialNotice = (
    preview: ApplyPreviewOutcome | undefined,
    refusal: { code: string; status: number } | null,
): CommandNotice | null => {
    if (preview === undefined) {
        return refusal === null ? null : { kind: 'refused', ...refusal };
    }

    return preview.kind === 'unconfirmed'
        ? { kind: 'unconfirmed' }
        : { kind: 'refused', code: preview.code, status: 409 };
};

type Options = {
    actions: BusinessApplyProps['actions'];
    lookup: RouteLink;
    /** Sent with every lookup as `identity_context_revision`, beside `command`. */
    identityContextRevision: number;
    preview?: ApplyPreviewOutcome;
    /** A refusal carried across the remount that read the page afresh, shown once again. */
    refusal?: { code: string; status: number } | null;
    onCompleted: (
        command: ApplicationCommand,
        resource: OperationResource,
        completion: Completion,
    ) => void;
    onRefused: (
        command: ApplicationCommand,
        code: string,
        status: number,
    ) => void;
};

/**
 * The application's JSON commands (business-application-v1 points 2 and 7), through the shared
 * operation command: one in flight at a time, an unknown outcome looked up (by `command` and
 * `identity_context_revision`) before any resend, and a new `request_id` after every definitive
 * answer. A synthetic preview may seed a held command.
 */
export function useApplicationCommand({
    actions,
    lookup,
    identityContextRevision,
    preview,
    refusal = null,
    onCompleted,
    onRefused,
}: Options) {
    return useOperationCommand<ApplicationCommand, OperationResource>({
        actions,
        lookup,
        lookupQuery: { identity_context_revision: identityContextRevision },
        initial: {
            held: preview?.kind === 'unconfirmed' ? preview.command : null,
            notice: initialNotice(preview, refusal),
        },
        refresh: reloadPreservingState,
        onCompleted,
        onRefused,
    });
}
