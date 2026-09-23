import type { Money } from '@/types';

/**
 * Display formatting for server money and dates, exactly as the design writes them. Presentation
 * only: nothing here rounds a value that is then used to decide anything.
 */
const francs = (money: Money): number => Number(money.amount);

const grouped = (value: number): string =>
    Math.round(value).toLocaleString('en-US');

/** "RWF 12,383,800" */
export const formatRwf = (money: Money): string =>
    `RWF ${grouped(francs(money))}`;

/** Digits only, grouped: "12,383,800" */
export const formatAmount = (money: Money): string => grouped(francs(money));

/** "RWF 850,000" below a million, "RWF 11.9M" / "RWF 18M" above it, "RWF 1.6B" from a billion. */
export const formatRwfShort = (money: Money): string => {
    const value = francs(money);

    if (value < 1e6) {
        return formatRwf(money);
    }

    const millions = Math.round(value / 1e5) / 10;

    if (millions >= 1000) {
        return `RWF ${(millions / 1000).toFixed(1)}B`;
    }

    return `RWF ${millions % 1 === 0 ? millions.toFixed(0) : millions.toFixed(1)}M`;
};

/** Stat-tile figure without the currency: "88M", "3.4M", "1.6B". */
export const formatMillions = (money: Money): string => {
    const millions = francs(money) / 1e6;

    if (millions >= 1000) {
        return `${(millions / 1000).toFixed(1)}B`;
    }

    return `${millions >= 10 ? Math.round(millions) : Math.round(millions * 10) / 10}M`;
};

/** The design writes day-first English dates ("7 Oct"), so English formats as en-GB. */
const intlLocale = (locale: string): string =>
    locale === 'en' ? 'en-GB' : locale;

/** A count with thousands separators: "1,657". */
export const formatCount = (value: number): string => grouped(value);

/** "Jun 2026" from an ISO date. */
export const formatMonthYear = (iso: string, locale: string): string =>
    new Intl.DateTimeFormat(intlLocale(locale), {
        month: 'short',
        year: 'numeric',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));

/** "September 2026" from an ISO date. */
export const formatMonthYearLong = (iso: string, locale: string): string =>
    new Intl.DateTimeFormat(intlLocale(locale), {
        month: 'long',
        year: 'numeric',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));

/** "7 Oct" from an ISO date. */
export const formatDayMonth = (iso: string, locale: string): string =>
    new Intl.DateTimeFormat(intlLocale(locale), {
        day: 'numeric',
        month: 'short',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));

/** "5 Oct 2026" from an ISO date. */
export const formatDate = (iso: string, locale: string): string =>
    new Intl.DateTimeFormat(intlLocale(locale), {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));
