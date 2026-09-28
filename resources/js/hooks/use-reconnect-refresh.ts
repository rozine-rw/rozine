import { router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Reads the page's facts again when the connection returns (Phase 2: reconnect on every screen),
 * so nothing stale stays on screen after an outage. Only the `online` signal triggers it, never
 * focus, so a half-typed form or an open sheet is left alone; Inertia's reload keeps local state
 * and scroll. Screens with their own reconnect reads, like the Auditor app, turn it off.
 */
export function useReconnectRefresh(enabled = true): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const reconnect = () => router.reload();

        window.addEventListener('online', reconnect);

        return () => window.removeEventListener('online', reconnect);
    }, [enabled]);
}
