import { formatCount, formatDate, formatRwfShort } from '@/lib/rozine/format';

/**
 * Console-only display helpers. Presentation only: nothing here decides anything, and every
 * value it reads is a server fact.
 */

const KIGALI = 'Africa/Kigali';

/** The ledger and audit-trail clock: "2026-09-23 19:37:23", Kigali time, as the design writes it. */
export const formatTimestamp = (iso: string): string => {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
            timeZone: KIGALI,
        })
            .formatToParts(new Date(iso))
            .map((part) => [part.type, part.value]),
    );

    return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}:${parts.second}`;
};

export type RelativeTime =
    | { unit: 'now' }
    | { unit: 'minutes' | 'hours' | 'days'; count: number };

/** How long before the server's clock something happened, in the largest whole unit. */
export const relativeTime = (iso: string, serverTime: string): RelativeTime => {
    const elapsed = Date.parse(serverTime) - Date.parse(iso);
    const minutes = Math.floor(elapsed / 60000);
    const hours = Math.floor(elapsed / 3600000);
    const days = Math.floor(elapsed / 86400000);

    if (days > 0) {
        return { unit: 'days', count: days };
    }

    if (hours > 0) {
        return { unit: 'hours', count: hours };
    }

    if (minutes > 0) {
        return { unit: 'minutes', count: minutes };
    }

    return { unit: 'now' };
};

/** The design's avatar palette, chosen by a stable hash of the record id. */
const AVATARS = [
    '#1e3aff',
    '#1d9e75',
    '#7c3aed',
    '#c2661f',
    '#0891b2',
    '#1428a8',
    '#e5484d',
] as const;

export const avatarColor = (id: string): string => {
    let hash = 0;

    for (const char of id) {
        hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
    }

    return AVATARS[hash % AVATARS.length];
};

export const initialOf = (name: string): string =>
    name.trim().charAt(0).toUpperCase();

export { formatCount, formatDate, formatRwfShort };
