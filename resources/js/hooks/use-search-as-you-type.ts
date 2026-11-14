import type { CancelToken } from '@inertiajs/core';
import { useEffect, useRef } from 'react';
import type { ChangeEvent } from 'react';

/** How long typing pauses before the search runs. */
export const SEARCH_PAUSE_MS = 400;

/**
 * Runs a search once typing pauses, so a search box needs no Enter (#607). `submit` (Enter) runs it
 * at once and drops the pending run. A term equal to the one last requested runs nothing; that is
 * the last term sent, not the one the server last applied, so clearing the box while a search is
 * still loading is sent too. Each search cancels the one still in flight, since `router.reload` is
 * asynchronous and an older response could otherwise land last. A pending run is dropped when the
 * box unmounts.
 */
export function useSearchAsYouType(
    applied: string,
    run: (term: string, onCancelToken: (token: CancelToken) => void) => void,
): {
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    submit: (term: string) => void;
} {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latest = useRef(run);
    const requested = useRef(applied);
    const inFlight = useRef<CancelToken | null>(null);

    useEffect(() => {
        latest.current = run;
    });

    useEffect(() => {
        requested.current = applied;
    }, [applied]);

    const cancel = () => {
        if (timer.current !== null) {
            clearTimeout(timer.current);
            timer.current = null;
        }
    };

    useEffect(() => cancel, []);

    const submit = (term: string) => {
        cancel();
        requested.current = term;
        inFlight.current?.cancel();
        inFlight.current = null;
        latest.current(term, (token) => {
            inFlight.current = token;
        });
    };

    return {
        submit,
        onChange: (event) => {
            const term = event.currentTarget.value;

            cancel();
            timer.current = setTimeout(() => {
                timer.current = null;

                if (term.trim() !== requested.current.trim()) {
                    submit(term);
                }
            }, SEARCH_PAUSE_MS);
        },
    };
}
