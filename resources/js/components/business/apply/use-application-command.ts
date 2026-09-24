import { useOperationCommand } from '@/hooks/use-operation-command';
import type { CommandNotice } from '@/hooks/use-operation-command';
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
 * The application's JSON commands (business-application-v1 points 2 and 7), through the shared
 * operation command: one in flight at a time, an unknown outcome looked up before any resend, and
 * a new `request_id` after every definitive answer. A synthetic preview may seed a held command.
 */
export function useApplicationCommand({
    actions,
    lookup,
    preview,
    onCompleted,
    onRefused,
}: Options) {
    return useOperationCommand<ApplicationCommand, OperationResource>({
        actions,
        lookup,
        initial: {
            held: preview?.kind === 'unconfirmed' ? preview.command : null,
            notice: initialNotice(preview),
        },
        onCompleted,
        onRefused,
    });
}
