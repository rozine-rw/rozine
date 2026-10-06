import type { Money } from '@/types';

/**
 * The Auditor design's compact money figure (design `fmt`, L2062): "644K", "51.2M", "1.6B".
 * Presentation only — the exact franc amount stays the server's.
 */
export const compactAmount = (money: Money): string => {
    const value = Math.round(Number(money.amount));

    /* A net outflow keeps its sign: "−1.2M", never a bare negative integer. */
    if (value < 0) {
        return `−${compactAmount({ ...money, amount: String(-value) })}`;
    }

    if (value >= 1e9) {
        return `${Math.round(value / 1e8) / 10}B`;
    }

    if (value >= 1e6) {
        return `${Math.round(value / 1e5) / 10}M`;
    }

    if (value >= 1e3) {
        return `${Math.round(value / 1e3)}K`;
    }

    return String(value);
};

/** "RWF 51.2M". */
export const compactRwf = (money: Money): string =>
    `RWF ${compactAmount(money)}`;
