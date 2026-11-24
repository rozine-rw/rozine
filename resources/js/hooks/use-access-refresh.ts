import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Reconcile access after another tab or a disconnected session may have changed it: on focus, on
 * reconnect and when the document becomes visible again, the page asks the server for its facts.
 *
 * - `properties` names the props to reload; null reloads the page's full current facts, so a
 *   withdrawn authority lands on whatever the server now renders, such as the access-denied page.
 * - `enabled` is false where there is no live session to reconcile against, such as a fixture
 *   preview.
 * - `settled` is false while the page has a command in flight or held for its lookup. A signal
 *   then waits, and the refresh runs once the command settles, so a reload never overtakes a
 *   command or its recorded outcome.
 * - `pulse` is a URL answering `{ version }`, a fingerprint of the page's facts. While the page is
 *   visible it is asked every few seconds and the props reload when the fingerprint changes, so a
 *   decision taken elsewhere, such as Compliance approving a KYC, shows without a refresh.
 */
export const PULSE_INTERVAL_MS = 5000;

export function useAccessRefresh(
    properties: string[] | null,
    enabled = true,
    settled = true,
    pulse: string | null = null,
): boolean {
    const keys = properties === null ? null : properties.join(',');
    const inFlight = useRef(false);
    const waiting = useRef(false);
    const ready = useRef(settled);
    const [refreshing, setRefreshing] = useState(false);
    const [refresh] = useState(() => (only: string[] | null) => {
        if (inFlight.current) {
            return;
        }

        if (!ready.current) {
            waiting.current = true;

            return;
        }

        inFlight.current = true;
        setRefreshing(true);
        router.reload({
            ...(only === null ? {} : { only }),
            onFinish: () => {
                inFlight.current = false;
                setRefreshing(false);
            },
        });
    });

    useEffect(() => {
        ready.current = settled;

        if (settled && waiting.current) {
            waiting.current = false;
            refresh(keys === null ? null : keys.split(','));
        }
    }, [settled, keys, refresh]);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        const only = keys === null ? null : keys.split(',');
        const signal = () => refresh(only);
        const visibility = () => {
            if (!document.hidden) {
                signal();
            }
        };
        window.addEventListener('focus', signal);
        window.addEventListener('online', signal);
        document.addEventListener('visibilitychange', visibility);

        return () => {
            window.removeEventListener('focus', signal);
            window.removeEventListener('online', signal);
            document.removeEventListener('visibilitychange', visibility);
        };
    }, [enabled, keys, refresh]);

    useEffect(() => {
        if (!enabled || pulse === null) {
            return;
        }

        const only = keys === null ? null : keys.split(',');
        let last: string | null = null;
        let stopped = false;
        const ask = async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(pulse, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const body = response.ok
                    ? ((await response.json()) as { version?: unknown })
                    : null;
                const version =
                    typeof body?.version === 'string' ? body.version : null;

                if (stopped || version === null) {
                    return;
                }

                if (last !== null && version !== last) {
                    refresh(only);
                }

                last = version;
            } catch {
                // Offline or signed out: the next tick or the focus signal tries again.
            }
        };
        void ask();
        const timer = window.setInterval(() => void ask(), PULSE_INTERVAL_MS);

        return () => {
            stopped = true;
            window.clearInterval(timer);
        };
    }, [enabled, keys, pulse, refresh]);

    return refreshing;
}
