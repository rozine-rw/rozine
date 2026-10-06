import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/** How long a quantity must settle before the server re-quotes it. */
export const QUOTE_DEBOUNCE_MS = 250;

/**
 * The investor's chosen quantity and the server's quote for it. Every change re-asks the server
 * (`only`, the quote by default) — the client never multiplies a unit price itself. `extra` carries
 * the other inputs the server needs (the focused deal).
 */
export function useQuotedUnits(
    initial: number,
    extra: Record<string, string>,
    only: string[] = ['quote'],
): {
    units: number;
    quoting: boolean;
    setUnits: (units: number) => void;
    /** Back to one note without a re-quote, for when the page reloads the quote itself. */
    resetUnits: () => void;
} {
    const [units, setUnitsState] = useState(initial);
    const [quoting, setQuoting] = useState(false);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const setUnits = (next: number) => {
        setUnitsState(next);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => {
            router.reload({
                only,
                data: { ...extra, units: next },
                onStart: () => setQuoting(true),
                onFinish: () => setQuoting(false),
            });
        }, QUOTE_DEBOUNCE_MS);
    };

    const resetUnits = () => {
        window.clearTimeout(timer.current);
        setUnitsState(1);
    };

    return { units, quoting, setUnits, resetUnits };
}
