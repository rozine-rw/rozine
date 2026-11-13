import { useEffect, useRef } from 'react';
import type { ChangeEvent } from 'react';

/** How long typing pauses before the search runs. */
export const SEARCH_PAUSE_MS = 400;

/**
 * Runs a search once typing pauses, so a search box needs no Enter (#607). Enter still runs it at
 * once. A term equal to the one the server last applied runs nothing, and a pending run is dropped
 * when the box unmounts.
 */
export function useSearchAsYouType(
    applied: string,
    run: (term: string) => void,
): {
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    /** Drops a pending run, for when Enter has already searched. */
    cancel: () => void;
} {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latest = useRef({ applied, run });

    useEffect(() => {
        latest.current = { applied, run };
    });

    const cancel = () => {
        if (timer.current !== null) {
            clearTimeout(timer.current);
            timer.current = null;
        }
    };

    useEffect(() => cancel, []);

    return {
        cancel,
        onChange: (event) => {
            const term = event.currentTarget.value;

            cancel();
            timer.current = setTimeout(() => {
                timer.current = null;

                if (term.trim() !== latest.current.applied.trim()) {
                    latest.current.run(term);
                }
            }, SEARCH_PAUSE_MS);
        },
    };
}
