import { useEffect, useRef } from 'react';
import type { ChangeEvent } from 'react';

/** How long typing pauses before the search runs. */
export const SEARCH_PAUSE_MS = 400;

/**
 * Runs a search once typing pauses, so a search box needs no Enter (#607). `submit` (Enter) runs it
 * at once and drops the pending run. A term equal to the one last requested runs nothing; that is
 * the last term sent, not the one the server last applied, so clearing the box while a search is
 * still loading is sent too and supersedes it. A pending run is dropped when the box unmounts.
 */
export function useSearchAsYouType(
    applied: string,
    run: (term: string) => void,
): {
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    submit: (term: string) => void;
} {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latest = useRef(run);
    const requested = useRef(applied);

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
        latest.current(term);
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
