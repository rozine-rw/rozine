import { router } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { OperationNotice } from '@/components/rozine/operation-notice';
import {
    reloadPreservingState,
    useOperationCommand,
} from '@/hooks/use-operation-command';
import type { CommandNotice } from '@/hooks/use-operation-command';
import { useTranslation } from '@/hooks/use-translation';
import { refusalRefreshes } from '@/lib/rozine/operation';
import type { RouteAction, RouteLink } from '@/types';
import type {
    AuditorAllowedAction,
    AuditorCommand,
    AuditorCommandName,
    AuditorOperationResource,
    AuditorOutcome,
    AuditorPageContract,
    AuditorPreviewOutcome,
    ConflictReceipt,
} from '@/types/auditor';

/**
 * Refusals the partner cannot fix by refreshing: a denial, a record out of scope, or a step-up
 * that needs a new authenticator code rather than new facts.
 */
const FINAL_REFUSALS: ReadonlySet<string> = new Set([
    'ACTION_FORBIDDEN',
    'NOT_FOUND',
    'STEP_UP_INVALID',
    'STEP_UP_EXPIRED',
]);

/** The refusal codes with their own explanation; any other 403 reads as a changed access. */
const REFUSALS = [
    'VERSION_CONFLICT',
    'IDEMPOTENCY_CONFLICT',
    'DIGEST_STALE',
    'EVIDENCE_VERSION_STALE',
    'FINDINGS_VERSION_STALE',
    'PROCEDURE_VERSION_STALE',
    'MANDATE_STALE',
    'ACTION_FORBIDDEN',
    'NOT_FOUND',
    'STEP_UP_INVALID',
    'STEP_UP_EXPIRED',
] as const;

type Refusal = (typeof REFUSALS)[number];

const isRefusal = (code: string): code is Refusal =>
    (REFUSALS as readonly string[]).includes(code);

/** What a component that sent a command wants to hear first; `true` means it handled it. */
export type CommandCallbacks = {
    onCompleted?: (resource: AuditorOperationResource) => void;
    onRefused?: (code: string, status: number) => boolean;
};

/** A result card the partner dismisses before the page moves on to the server's `next`. */
type Result = { outcome: AuditorOutcome; next: RouteLink };

const initialFrom = (
    preview: AuditorPreviewOutcome | undefined,
): { held: AuditorCommand | null; notice: CommandNotice | null } => {
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
};

type Options = {
    page: AuditorPageContract;
    lookup: RouteLink;
    preview?: AuditorPreviewOutcome;
};

/** A command to send: which one, about which business, where it goes and its own facts. */
export type CommandRequest = {
    name: AuditorCommandName;
    business: string;
    route: RouteAction;
    /** The command's fields, with its target's `expected_revision`. */
    payload: Record<string, unknown>;
    /**
     * The target record's own `allowed_actions`, where a list page scopes each record; the
     * page's list otherwise.
     */
    scope?: AuditorAllowedAction[];
};

/** Which command lane a notice belongs to. */
export type CommandLane = 'ordinary' | 'conflict';

/**
 * The Auditor's JSON commands (auditor-filing-v1 points 1 and 7), through the shared operation
 * command. Each command is offered only when `allowed_actions` lists it, carries the page's
 * `identity_context_revision`, its target's `expected_revision` and a fresh `request_id`, and is
 * looked up — never blindly resent — when its answer is lost.
 *
 * A completed command either moves to the server's `next` page or, where the partner should hear
 * what happened, shows the result card first.
 *
 * A conflict declaration runs in its own lane (#96 point 6), with its own `request_id`, lookup and
 * notice: it can be declared while an ordinary command is in flight or its outcome unknown, and
 * that command stays held as it was. A recorded blocking conflict withdraws the private content at
 * once and follows the receipt's destination; nothing the earlier command answers later moves the
 * page again.
 */
export function useAuditorCommandCenter({ page, lookup, preview }: Options) {
    const initial = initialFrom(preview);
    const callbacks = useRef(new Map<string, CommandCallbacks>());
    const withdrawn = useRef(false);
    const [result, setResult] = useState<Result | null>(null);
    const [blocked, setBlocked] = useState<ConflictReceipt | null>(null);
    const [sheets, setSheets] = useState(0);

    const onCompleted = (
        sent: AuditorCommand,
        resource: AuditorOperationResource,
    ) => {
        const own = callbacks.current.get(sent.payload.request_id);

        callbacks.current.delete(sent.payload.request_id);
        own?.onCompleted?.(resource);

        /* After a blocking conflict, an earlier command's late answer moves nothing. */
        if (withdrawn.current) {
            return;
        }

        const { data } = resource;

        if (data === null) {
            router.reload();

            return;
        }

        const show = (outcome: AuditorOutcome) =>
            setResult({ outcome, next: data.next });

        switch (resource.code) {
            case 'ASSIGNMENT_DECLINED':
                show({ kind: 'job_declined', business: sent.business });

                return;
            case 'CONFLICT_RECORDED': {
                const receipt = data.conflict as ConflictReceipt;

                if (receipt.blocking) {
                    withdrawn.current = true;
                    setBlocked(receipt);
                    router.visit(data.next);

                    return;
                }

                show({
                    kind: 'conflict_declared',
                    business: sent.business,
                    resolution: data.outcome?.resolution ?? receipt.status,
                    blocking: receipt.blocking,
                });

                return;
            }
            case 'AUDIT_CHANGES_REQUESTED':
                show({
                    kind: 'changes_requested',
                    business: sent.business,
                });

                return;
            case 'AUDIT_REJECTED':
                show({ kind: 'report_rejected', business: sent.business });

                return;
            case 'AVAILABILITY_UPDATED':
                /* The switch belongs to the page it is on: redraw it there, in place. */
                router.reload();

                return;
            default:
                router.visit(data.next);
        }
    };

    const onRefused = (sent: AuditorCommand, code: string, status: number) => {
        const own = callbacks.current.get(sent.payload.request_id);

        callbacks.current.delete(sent.payload.request_id);

        if (own?.onRefused?.(code, status) === true) {
            return;
        }

        if (refusalNeedsFreshFacts(code, status)) {
            router.reload();
        }
    };

    const command = useOperationCommand<
        AuditorCommand,
        AuditorOperationResource
    >({
        actions: (sent) => sent.route,
        lookup,
        initial,
        refresh: reloadPreservingState,
        onCompleted,
        onRefused,
    });
    const conflict = useOperationCommand<
        AuditorCommand,
        AuditorOperationResource
    >({
        actions: (sent) => sent.route,
        lookup,
        refresh: reloadPreservingState,
        onCompleted,
        onRefused,
    });

    const allowed = (action: AuditorAllowedAction): boolean =>
        page.allowed_actions.includes(action);

    /**
     * Sends a command the page is allowed to issue, adding the identity context and a new
     * `request_id`; the target's `expected_revision` comes with the payload.
     */
    const send = (
        { name, business, route, payload, scope }: CommandRequest,
        own: CommandCallbacks = {},
    ): boolean => {
        if (!(scope ?? page.allowed_actions).includes(name)) {
            return false;
        }

        const request_id = crypto.randomUUID();
        const lane = name === 'conflict.declare' ? conflict : command;

        callbacks.current.set(request_id, own);

        return lane.send({
            name,
            business,
            route,
            payload: {
                ...payload,
                identity_context_revision: page.identity_context_revision,
                request_id,
            },
        });
    };

    const opened = useCallback(() => setSheets((count) => count + 1), []);
    const closed = useCallback(() => setSheets((count) => count - 1), []);

    return {
        busy: command.busy,
        notice: command.notice,
        unresolved: command.unresolved,
        /** Nothing is in flight or awaiting its outcome, so a new command may start. */
        idle: !command.busy && !command.unresolved,
        errors: command.errors,
        allowed,
        send,
        checkAgain: command.checkAgain,
        retry: command.retry,
        /** The conflict lane: its own state, so a declaration never waits on another command. */
        conflict: {
            busy: conflict.busy,
            notice: conflict.notice,
            idle: !conflict.busy && !conflict.unresolved,
            errors: conflict.errors,
            checkAgain: conflict.checkAgain,
            retry: conflict.retry,
        },
        result,
        /** Dismissing the result card moves on to the page the server named. */
        finish: () => {
            if (result !== null) {
                router.visit(result.next);
            }
        },
        blocked,
        /** Whether a sheet is open, so the notice shows inside it rather than beneath it. */
        sheetOpen: sheets > 0,
        opened,
        closed,
    };
}

export type AuditorCommandCenter = ReturnType<typeof useAuditorCommandCenter>;

const CommandContext = createContext<AuditorCommandCenter | null>(null);

export function AuditorCommandProvider({
    center,
    children,
}: {
    center: AuditorCommandCenter;
    children: ReactNode;
}) {
    return (
        <CommandContext.Provider value={center}>
            {children}
        </CommandContext.Provider>
    );
}

/** The page's command center; every page with Auditor commands provides one. */
export function useAuditorCommands(): AuditorCommandCenter {
    const center = useContext(CommandContext);

    if (center === null) {
        throw new Error('Auditor commands need an AuditorCommandProvider.');
    }

    return center;
}

/** Marks a sheet as open while it is mounted, when the page has commands. */
export function useSheetPresence(): void {
    const center = useContext(CommandContext);
    const opened = center?.opened;
    const closed = center?.closed;

    useEffect(() => {
        opened?.();

        return () => closed?.();
    }, [opened, closed]);
}

/** Why a command was refused, in the Auditor's words; any other 403 reads as a changed access. */
export function useRefusalText(): (code: string, status: number) => string {
    const { t } = useTranslation();

    return (code, status) =>
        isRefusal(code)
            ? t(`auditor.command.refused.${code}`)
            : status === 403
              ? t('auditor.command.refused.denied')
              : t('auditor.command.refused.failed');
}

/** Whether a refusal calls for fresh facts rather than a new code or a changed access. */
export const refusalNeedsFreshFacts = (code: string, status: number): boolean =>
    refusalRefreshes(code, status, FINAL_REFUSALS);

/**
 * What happened to the last command, in the Auditor's words. `placement` keeps one copy on screen:
 * the page's copy steps aside while a sheet shows its own.
 */
export function AuditorCommandNotice({
    placement,
    lane = 'ordinary',
    className,
}: {
    placement: 'page' | 'sheet';
    /** A sheet shows its own lane; the page shows both. */
    lane?: CommandLane;
    className?: string;
}) {
    const center = useAuditorCommands();

    if (placement === 'page') {
        return center.sheetOpen ? null : (
            <>
                <LaneNotice lane="ordinary" className={className} />
                <LaneNotice lane="conflict" className={className} />
            </>
        );
    }

    return <LaneNotice lane={lane} className={className} />;
}

/** One lane's notice, if it has one. */
function LaneNotice({
    lane,
    className,
}: {
    lane: CommandLane;
    className?: string;
}) {
    const { t } = useTranslation();
    const refused = useRefusalText();
    const center = useAuditorCommands();
    const state = lane === 'conflict' ? center.conflict : center;
    const { notice } = state;

    if (notice === null) {
        return null;
    }

    return (
        <OperationNotice
            notice={notice}
            busy={state.busy}
            onCheckAgain={state.checkAgain}
            onRetry={state.retry}
            className={className}
            copy={{
                refused,
                title: (kind) => t(`auditor.command.${kind}.title`),
                body: (kind) => t(`auditor.command.${kind}.body`),
                checkAgain: t('auditor.command.check_again'),
                tryAgain: t('auditor.command.try_again'),
            }}
        />
    );
}
