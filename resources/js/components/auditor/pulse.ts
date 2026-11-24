import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { PULSE_INTERVAL_MS } from '@/hooks/use-access-refresh';
import type { RouteLink } from '@/types';

/**
 * Keeps an open Auditor Home live (Erastus, 24 Nov 2026): while the tab is visible it asks the
 * server every few seconds for the fingerprint of what Home shows, and reloads the page's facts
 * when that differs from the fingerprint it was rendered with. An audit dispatched in one of the
 * partner's districts, an accepted job or a new notice then shows without a refresh. A reload waits
 * while a command is unsettled, so it never overtakes a command or its recorded outcome. A page
 * rendered without a fingerprint, such as a fixture preview, never asks.
 */
export function useAuditorPulse(
    live: RouteLink | undefined,
    shown: string | null | undefined,
    settled: boolean,
): void {
    const url =
        shown === null || shown === undefined ? null : (live?.url ?? null);
    const current = useRef(shown ?? null);
    const ready = useRef(settled);
    const inFlight = useRef(false);

    useEffect(() => {
        current.current = shown ?? null;
        ready.current = settled;
    });

    useEffect(() => {
        if (url === null) {
            return;
        }

        let stopped = false;
        const ask = async () => {
            if (document.hidden || inFlight.current || !ready.current) {
                return;
            }

            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const body = response.ok
                    ? ((await response.json()) as { version?: unknown })
                    : null;

                if (
                    stopped ||
                    typeof body?.version !== 'string' ||
                    body.version === current.current ||
                    !ready.current
                ) {
                    return;
                }

                inFlight.current = true;
                router.reload({
                    onFinish: () => {
                        inFlight.current = false;
                    },
                });
            } catch {
                // Offline or signed out: the next tick tries again, and focus reads the page anyway.
            }
        };
        const timer = window.setInterval(() => void ask(), PULSE_INTERVAL_MS);

        return () => {
            stopped = true;
            window.clearInterval(timer);
        };
    }, [url]);
}
