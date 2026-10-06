import { useSyncExternalStore } from 'react';

/** Tailwind `lg`: the width at which the Investor app switches from phone to desktop layout. */
export const WIDE_QUERY = '(min-width: 1024px)';

const query = (): MediaQueryList | undefined =>
    typeof window.matchMedia === 'function'
        ? window.matchMedia(WIDE_QUERY)
        : undefined;

function subscribe(callback: () => void): () => void {
    const list = query();

    list?.addEventListener('change', callback);

    return () => list?.removeEventListener('change', callback);
}

const snapshot = (): boolean => query()?.matches ?? false;

/**
 * Whether the desktop layout is showing. The Investor screens draw genuinely different markup for
 * each environment (the design's own desktop deck, invest bar and panels), so a page renders one
 * or the other rather than both hidden by CSS — screen readers meet one landmark set, and the
 * server is only asked for one quote.
 */
export function useWide(): boolean {
    return useSyncExternalStore(subscribe, snapshot, () => false);
}
