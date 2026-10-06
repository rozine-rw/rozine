import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { AuditorJobsProps } from '@/types/auditor';

/**
 * Which of the page's props a background read asks the server for: a partial reload. Undefined
 * reads every prop the page was rendered with.
 */
export type ReloadScope = { only?: string[]; except?: string[] };

/** The page's own reload, limited to its scope when it has one. */
export const reloadPage = (scope?: ReloadScope): void => {
    if (scope === undefined) {
        router.reload();
    } else {
        router.reload(scope);
    }
};

/**
 * How long a deadline read waits past the deadline itself, measured on the server's clock, so a
 * skewed or slow estimate of that clock still lands after the server has crossed it.
 */
export const DEADLINE_GRACE_MS = 5_000;

/**
 * Focus, visibility and reconnect arrive together when a partner returns to the tab: a recovery
 * signal this soon after the last read shares that read rather than starting another.
 */
export const RECOVERY_WINDOW_MS = 5_000;

/** The longest a browser timer can wait; a later deadline is checked again then, never read early. */
const MAX_TIMER_MS = 2 ** 31 - 1;

/**
 * The next deadline worth a read, in epoch milliseconds: the earliest one after both the server's
 * render time — the server already applied any deadline before it — and the last deadline this page
 * read for. Null when nothing is due.
 */
export function nextDeadline(
    deadlines: readonly string[],
    serverTime: string,
    after = Number.NEGATIVE_INFINITY,
): number | null {
    const floor = Math.max(Date.parse(serverTime), after);
    let next: number | null = null;

    for (const deadline of deadlines) {
        const at = Date.parse(deadline);

        if (at > floor && (next === null || at < next)) {
            next = at;
        }
    }

    return next;
}

/**
 * The deadlines that change what the Jobs list shows: an offer closes at its `accept_by`, and work
 * still in progress turns overdue at its `deadline.due_at`. Work already overdue or awaiting a
 * co-signature has no further crossing.
 */
export const jobsDeadlines = (jobs: AuditorJobsProps): string[] => [
    ...jobs.eligible.map((job) => job.accept_by),
    ...jobs.assigned
        .filter((job) => job.status === 'in_progress')
        .map((job) => job.deadline.due_at),
];

/** When a page reads again on its own: its scope, and the deadlines it shows with its clock. */
export type AuditorRefresh = {
    scope?: ReloadScope;
    deadlines?: { serverTime: string; at: readonly (string | null)[] };
};

type Options = AuditorRefresh & {
    /** False while a command is in flight or held for its lookup; a read then waits for it. */
    settled: boolean;
};

/** A read on a signal (focus, reconnect, a return to the tab) or for a deadline that passed. */
type ReadKind = 'recovery' | 'deadline';

/**
 * The server's clock on the page a read delivered. A page with no `server_time` is not this page's
 * facts — the server sent the partner elsewhere — so it leaves this page nothing to catch up.
 */
const deliveredAt = (props: Record<string, unknown>): number =>
    typeof props.server_time === 'string'
        ? Date.parse(props.server_time)
        : Number.POSITIVE_INFINITY;

/**
 * When an Auditor page reads its facts again in the background. Every read takes the server's
 * record locks, so the page reads only when something has changed or may have:
 *
 * - on focus, reconnect or a return to the tab — access may have been withdrawn meanwhile — as one
 *   read, however many of those signals arrive together;
 * - once, just after the next deadline the page shows passes on the server's clock (an offer
 *   closing, work turning overdue), then not again until the deadline after it.
 *
 * A hidden tab does not read: a signal or deadline while it is hidden waits, and the tab reads once
 * when it becomes visible again. A read answers for a deadline that passed only when the page it
 * delivers was rendered after that deadline; a read that started before it, or delivered no page,
 * is followed by exactly one catch-up read once it finishes.
 *
 * Nothing reads on a timer otherwise: the countdowns tick from `server_time` on their own. A read
 * waits while a command is unsettled, so it never overtakes a command or its recorded outcome, and
 * asks only for the page's `scope`. Commands still reload after they complete, and the server
 * reauthorizes each one whatever the page last read.
 */
export function useAuditorRefresh({ settled, scope, deadlines }: Options) {
    const alive = useRef(true);
    const inFlight = useRef(false);
    const waiting = useRef(false);
    const ready = useRef(settled);
    const lastRead = useRef(Number.NEGATIVE_INFINITY);
    const handled = useRef(Number.NEGATIVE_INFINITY);
    const missed = useRef<number | null>(null);
    const scoped = useRef(scope);
    const [read] = useState(() => {
        /*
         * Once a read finishes, a deadline that passed is answered or gets its catch-up read. It is
         * cleared first, so the catch-up cannot queue another for the same deadline.
         */
        const settle = (delivered: number): void => {
            const due = missed.current;

            if (due === null || !alive.current) {
                return;
            }

            missed.current = null;
            handled.current = Math.max(handled.current, due);

            if (delivered < due) {
                run('deadline');
            }
        };

        const run = (kind: ReadKind): void => {
            /* Becoming visible reads anyway, so a signal while hidden leaves the read to it. */
            if (
                document.hidden ||
                inFlight.current ||
                (kind === 'recovery' &&
                    Date.now() - lastRead.current < RECOVERY_WINDOW_MS)
            ) {
                return;
            }

            if (!ready.current) {
                waiting.current = true;

                return;
            }

            let delivered = Number.NEGATIVE_INFINITY;

            inFlight.current = true;
            lastRead.current = Date.now();
            router.reload({
                ...scoped.current,
                onSuccess: (page) => {
                    delivered = deliveredAt(page.props);
                },
                onFinish: () => {
                    inFlight.current = false;
                    settle(delivered);
                },
            });
        };

        return run;
    });

    useEffect(() => {
        alive.current = true;

        return () => {
            alive.current = false;
        };
    }, []);

    useEffect(() => {
        scoped.current = scope;
    }, [scope]);

    useEffect(() => {
        ready.current = settled;

        if (settled && waiting.current) {
            waiting.current = false;
            read('deadline');
        }
    }, [settled, read]);

    useEffect(() => {
        const signal = () => read('recovery');
        /* A deadline that passed while the tab was hidden is read for at once, whatever the window. */
        const visibility = () =>
            read(missed.current === null ? 'recovery' : 'deadline');
        window.addEventListener('focus', signal);
        window.addEventListener('online', signal);
        document.addEventListener('visibilitychange', visibility);

        return () => {
            window.removeEventListener('focus', signal);
            window.removeEventListener('online', signal);
            document.removeEventListener('visibilitychange', visibility);
        };
    }, [read]);

    const serverTime = deadlines?.serverTime ?? null;
    const shown = (deadlines?.at ?? []).filter(
        (deadline): deadline is string => deadline !== null,
    );
    const key = shown.join(' ');

    useEffect(() => {
        if (serverTime === null) {
            return;
        }

        const list = key === '' ? [] : key.split(' ');
        /* The server's clock, as of when these props arrived; the device clock only measures time since. */
        const offset = Date.parse(serverTime) - Date.now();
        let timer = 0;
        /* Waits for the next deadline after `after`; once it passes, for the one after that. */
        const schedule = (after: number): void => {
            const at = nextDeadline(list, serverTime, after);

            if (at === null) {
                return;
            }

            const arm = () => {
                const wait = at + DEADLINE_GRACE_MS - (Date.now() + offset);

                if (wait > 0) {
                    timer = window.setTimeout(
                        arm,
                        Math.min(wait, MAX_TIMER_MS),
                    );

                    return;
                }

                missed.current = at;
                read('deadline');
                schedule(at);
            };

            arm();
        };

        schedule(handled.current);

        return () => window.clearTimeout(timer);
    }, [key, serverTime, read]);
}
