import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** How long between two polls of a pending or unknown record (C3 v2 §2 Recovery, H18). */
export const POLL_INTERVAL_MS = 10_000;

/** The most polls one round makes before it hands over to a manual refresh. */
export const POLL_LIMIT = 30;

/**
 * Bounded Inertia polling for records still `pending` or `unknown`: a partial reload of `only`
 * every ten seconds, at most thirty times, then a manual refresh. Each poll reads fresh authority
 * and actions from the server; nothing is inferred between polls. It is not C4 live propagation.
 * A manual refresh reloads at once and starts a new bounded round.
 */
export function useBoundedPoll(
    active: boolean,
    only: string[],
): { exhausted: boolean; refresh: () => void } {
    const [round, setRound] = useState(0);
    const [polls, setPolls] = useState(0);
    const exhausted = active && polls >= POLL_LIMIT;
    const scope = only.join(',');

    useEffect(() => {
        if (!active || polls >= POLL_LIMIT) {
            return;
        }

        const timer = window.setTimeout(() => {
            setPolls((count) => count + 1);
            router.reload({ only: scope.split(',') });
        }, POLL_INTERVAL_MS);

        return () => window.clearTimeout(timer);
    }, [active, polls, round, scope]);

    const refresh = () => {
        router.reload({ only: scope.split(',') });
        setPolls(0);
        setRound((value) => value + 1);
    };

    return { exhausted, refresh };
}
