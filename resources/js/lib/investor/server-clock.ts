import { useEffect, useState } from 'react';

const DAY = 86_400_000;

/**
 * The server's "now", estimated from the `server_time` the page was rendered with plus the time
 * elapsed since (engineering contract §4: countdowns render against the server clock, never the
 * browser clock alone). `live` ticks it every second for an on-screen countdown. The server
 * still decides expiry; this only draws it.
 */
export function useServerNow(serverTime: string, live = false): number {
    const [offset] = useState(() => Date.parse(serverTime) - Date.now());
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (!live) {
            return;
        }

        const timer = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timer);
    }, [live]);

    return now + offset;
}

export type TimeLeft =
    | { kind: 'closed' }
    | { kind: 'clock'; label: string }
    | { kind: 'days'; days: number };

const pad = (value: number): string => String(value).padStart(2, '0');

/**
 * How long until `closesAt`: whole days ahead of the last day, then an HH:MM:SS clock inside the
 * final 24 hours (the design's `clockInfo`).
 */
export function timeLeft(closesAt: string, now: number): TimeLeft {
    const remaining = Date.parse(closesAt) - now;

    if (remaining <= 0) {
        return { kind: 'closed' };
    }

    if (remaining < DAY) {
        const seconds = Math.floor(remaining / 1000);

        return {
            kind: 'clock',
            label: `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`,
        };
    }

    return { kind: 'days', days: Math.ceil(remaining / DAY) };
}
