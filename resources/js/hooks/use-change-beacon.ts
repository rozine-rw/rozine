import { http } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useAccessRefresh } from '@/hooks/use-access-refresh';
import { POLL_INTERVAL_MS } from '@/hooks/use-bounded-poll';
import { useOnline } from '@/hooks/use-online';
import type { RouteLink } from '@/types';
import type { ChangeFeed, ChangeTopicName } from '@/types/settlement';

/** The fastest and slowest the beacon reads, whatever the server asks (C4 proposal §4e). */
export const MIN_POLL_MS = 5_000;
export const MAX_POLL_MS = 60_000;

/** What one topic's change reloads: the page props to ask for, and optionally one subject only. */
export type BeaconReload = { only: string[]; subject?: string };

export type ChangeBeacon = {
    /** `links.changes`: the feed with the cursor it was rendered at. Absent in previews. */
    link: RouteLink | null | undefined;
    /** The props each topic's change reloads. A topic not named here is ignored. */
    reloads: Partial<Record<ChangeTopicName, BeaconReload>>;
    /**
     * False while the page has a command in flight or held for its lookup: a reload then waits
     * until the command settles, so it never overtakes a recorded outcome.
     */
    settled?: boolean;
};

const clamp = (ms: number): number =>
    Math.min(MAX_POLL_MS, Math.max(MIN_POLL_MS, ms));

const visible = (): boolean => !document.hidden;

/** The feed url without its cursor, and the cursor it was rendered with. */
const parse = (url: string): { base: URL; cursor: string | null } => {
    const base = new URL(url, window.location.origin);
    const cursor = base.searchParams.get('after');
    base.searchParams.delete('after');

    return { base, cursor };
};

/**
 * The online propagation beacon (S4-E). While the page is visible and online it reads which of
 * its topics changed and reloads only the matching props, through the page's own authorized
 * partial reload: the feed never says what anything now is. A `reset` reloads the page in full.
 *
 * - It polls at the server's pace, and reads at once when the page returns to view or the
 *   connection comes back; it pauses while hidden or offline. No socket is involved.
 * - A revision not newer than one already seen for that subject is ignored.
 * - With no `links.changes` (a fixture preview) nothing runs.
 * - A reconnect or refocus also refreshes the reloadable props directly (`useAccessRefresh`), so
 *   the page converges even when the feed has nothing new for it.
 */
export function useChangeBeacon(beacon: ChangeBeacon | undefined): void {
    const url = beacon?.link?.url ?? null;
    const settled = beacon?.settled ?? true;
    const current = beacon?.reloads ?? {};
    const reloads = useRef(current);
    const online = useOnline();
    const [shown, setShown] = useState(visible);
    const [round, setRound] = useState(0);
    const cursor = useRef<string | null>(null);
    const seen = useRef(new Map<string, number>());
    const delay = useRef(POLL_INTERVAL_MS);
    const immediate = useRef(false);
    const held = useRef<{ full: boolean; only: Set<string> }>({
        full: false,
        only: new Set(),
    });
    const ready = useRef(settled);
    const active = url !== null && online && shown;
    const everyProp = [
        ...new Set(Object.values(current).flatMap((reload) => reload.only)),
    ];

    useEffect(() => {
        reloads.current = current;
    });

    useAccessRefresh(everyProp, url !== null && everyProp.length > 0, settled);

    const flush = () => {
        if (!ready.current) {
            return;
        }

        const { full, only } = held.current;
        held.current = { full: false, only: new Set() };

        if (full) {
            router.reload();
        } else if (only.size > 0) {
            router.reload({ only: [...only] });
        }
    };

    const apply = (feed: ChangeFeed) => {
        if (feed.reset) {
            seen.current.clear();
            held.current.full = true;
        }

        for (const change of feed.changes) {
            const key = `${change.topic}|${change.subject}`;
            const reload = reloads.current[change.topic];

            if (change.revision <= (seen.current.get(key) ?? 0)) {
                continue;
            }

            seen.current.set(key, change.revision);

            if (
                reload !== undefined &&
                (reload.subject === undefined ||
                    reload.subject === change.subject)
            ) {
                reload.only.forEach((prop) => held.current.only.add(prop));
            }
        }

        flush();
    };

    useEffect(() => {
        ready.current = settled;
        flush();
    }, [settled]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const change = () => setShown(visible());
        document.addEventListener('visibilitychange', change);

        return () => document.removeEventListener('visibilitychange', change);
    }, []);

    useEffect(() => {
        cursor.current = url === null ? null : parse(url).cursor;
        seen.current.clear();
    }, [url]);

    useEffect(() => {
        if (!active) {
            immediate.current = true;

            return;
        }

        const controller = new AbortController();
        const read = async () => {
            const target = parse(url).base;

            if (cursor.current !== null) {
                target.searchParams.set('after', cursor.current);
            }

            try {
                const response = await http.getClient().request({
                    method: 'get',
                    url: target.pathname + target.search,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                });
                const feed = JSON.parse(response.data) as ChangeFeed;
                cursor.current = feed.next_cursor;
                delay.current = clamp(feed.poll_after_ms);
                apply(feed);
            } catch {
                if (controller.signal.aborted) {
                    return;
                }

                delay.current = clamp(delay.current * 2);
            }

            setRound((value) => value + 1);
        };
        const wait = immediate.current ? 0 : delay.current;
        immediate.current = false;
        const timer = window.setTimeout(() => void read(), wait);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [active, url, round]); // eslint-disable-line react-hooks/exhaustive-deps
}
