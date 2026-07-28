/**
 * The demand-simulation model behind the Pulse waitlist.
 *
 * These mirror the formulas the server persists with, so what a visitor sees
 * while they move the slider is what gets recorded when they claim a spot.
 */

export type ThemedColor = {
    light: string;
    dark: string;
};

export type Rating = {
    band: string;
    score: string;
    color: ThemedColor;
    background: ThemedColor;
};

export type Listing = {
    id: number;
    initial: string;
    name: string;
    district: string;
    term: string;
    yield: string;
    yield_rate: number;
    rating_band: string;
    rating_score: string;
    accent: number;
};

export type BusinessNote = Listing & {
    avatar: ThemedColor;
    ratingColor: ThemedColor;
    projectedReturn: string;
};

export type Traction = {
    pledged: number;
    investors: number;
    businesses: number;
    average_loan: number | null;
    average_yield: number | null;
    average_rating: number | null;
};

export type ContactMethod = 'phone' | 'email';

export const TERMS = [3, 6, 9, 12] as const;

export const DEFAULT_SCORE = 68;

export const BLENDED_YIELD = 12.5;

export const MIN_PLEDGE = 5000;

export const MAX_PLEDGE = 10000000;

const CAPACITY_MULTIPLE = 2.75;

/**
 * The avatar colours a listed business is dealt, by its accent index.
 */
export const ACCENTS: ThemedColor[] = [
    { light: '#12a150', dark: '#12a150' },
    { light: '#0a5cff', dark: '#0a5cff' },
    { light: '#b06d00', dark: '#dd8a00' },
    { light: '#8b5cf6', dark: '#8b5cf6' },
    { light: '#e5484d', dark: '#e5484d' },
    { light: '#0369a1', dark: '#0ea5e9' },
    { light: '#0f766e', dark: '#14b8a6' },
    { light: '#b06d00', dark: '#f59e0b' },
    { light: '#ec4899', dark: '#ec4899' },
    { light: '#6366f1', dark: '#6366f1' },
];

export const DOCUMENT_TYPES = [
    'MoMo statement',
    'Bank statement',
    'Audited cash transactions',
    'POS data',
];

export const STEPS = [
    {
        no: '01',
        title: 'Add your intent',
        sub: 'Businesses upload a statement. Investors set an amount.',
    },
    {
        no: '02',
        title: 'See the numbers',
        sub: 'Capacity, rating and yield on real cash flow, in ~60s.',
    },
    {
        no: '03',
        title: 'Lock your spot',
        sub: 'Claim a shareable pass and early access at launch.',
    },
];

export const TRUST_MARKERS = [
    { color: { light: '#12a150', dark: '#22c55e' }, label: 'Encrypted' },
    { color: { light: '#0a5cff', dark: '#0a5cff' }, label: 'OCR parsing' },
    { color: { light: '#e0a53a', dark: '#e0a53a' }, label: 'Bank-grade' },
];

/**
 * Abbreviate an amount the way the waitlist headlines it.
 */
export function formatCompact(amount: number): string {
    const value = Math.round(amount || 0);

    if (value >= 1e9) {
        return `RWF ${Math.round(value / 1e8) / 10}B`;
    }

    if (value >= 1e6) {
        return `RWF ${Math.round(value / 1e5) / 10}M`;
    }

    if (value >= 1e3) {
        return `RWF ${Math.round(value / 1e3)}K`;
    }

    return `RWF ${value}`;
}

/**
 * Write an amount out in full, grouped.
 */
export function formatFull(amount: number): string {
    return `RWF ${Math.round(amount || 0).toLocaleString('en-US')}`;
}

/**
 * Write out an average, or the dash that stands in for one nothing has been
 * measured for yet.
 */
export function formatAverage(value: number | null, precision = 0): string {
    if (value === null) {
        return '—';
    }

    return precision === 0 ? formatNumber(value) : value.toFixed(precision);
}

/**
 * Group a number without the currency prefix.
 */
export function formatNumber(value: number): string {
    return Math.round(value || 0).toLocaleString('en-US');
}

/**
 * Translate a strength score into a rating band and a score out of five.
 */
export function rate(score: number): Rating {
    const outOfFive = Math.round((score / 20) * 10) / 10;

    if (outOfFive >= 4) {
        return {
            band: 'Strong',
            score: outOfFive.toFixed(1),
            color: { light: '#0f7a3d', dark: '#4ade80' },
            background: {
                light: 'rgba(18,161,80,.12)',
                dark: 'rgba(16,161,80,.14)',
            },
        };
    }

    if (outOfFive >= 3) {
        return {
            band: 'Stable',
            score: outOfFive.toFixed(1),
            color: { light: '#0a5cff', dark: '#2dd4bf' },
            background: {
                light: 'rgba(10,92,255,.1)',
                dark: 'rgba(20,168,160,.14)',
            },
        };
    }

    if (outOfFive >= 2) {
        return {
            band: 'Weak',
            score: outOfFive.toFixed(1),
            color: { light: '#c47c00', dark: '#fbbf24' },
            background: {
                light: 'rgba(221,138,0,.12)',
                dark: 'rgba(221,138,0,.16)',
            },
        };
    }

    return {
        band: 'Distressed',
        score: outOfFive.toFixed(1),
        color: { light: '#c0392b', dark: '#f87171' },
        background: {
            light: 'rgba(229,72,77,.12)',
            dark: 'rgba(229,72,77,.16)',
        },
    };
}

/**
 * Get the flat rate, as a percentage, for a score over a given term.
 */
export function yieldFor(score: number, months: number): number {
    return Math.max(
        10,
        Math.min(15, 10 + (100 - score) * 0.08 + ((months - 3) / 9) * 1.5),
    );
}

/**
 * Get the amount a business pre-qualifies for over a given term.
 */
export function qualifyFor(
    capacity: number,
    score: number,
    months: number,
): number {
    return (capacity * months) / (24 * (1 + yieldFor(score, months) / 100));
}

/**
 * Get the borrowing capacity backed by a year of cash flow.
 */
export function capacityFor(annualInflow: number): number {
    return annualInflow ? CAPACITY_MULTIPLE * annualInflow : 0;
}

/**
 * Dress the businesses on the waitlist as the notes an investor sees, priced
 * against the amount they are pledging.
 */
export function businessNotes(
    listings: Listing[],
    pledge: number,
): BusinessNote[] {
    return listings.map((listing) => ({
        ...listing,
        avatar: ACCENTS[listing.accent % ACCENTS.length],
        ratingColor: ratingColor(listing.rating_band),
        projectedReturn: formatFull(pledge * (1 + listing.yield_rate / 100)),
    }));
}

/**
 * Get the colour a rating band is written in.
 */
export function ratingColor(band: string): ThemedColor {
    switch (band) {
        case 'Strong':
            return { light: '#0f7a3d', dark: '#4ade80' };
        case 'Stable':
            return { light: '#0a5cff', dark: '#2dd4bf' };
        case 'Weak':
            return { light: '#c47c00', dark: '#fbbf24' };
        default:
            return { light: '#c0392b', dark: '#f87171' };
    }
}

/**
 * Determine whether a contact detail is usable for the chosen method.
 */
export function isValidContact(value: string, method: ContactMethod): boolean {
    const contact = (value ?? '').trim();

    if (method === 'email') {
        return /.+@.+\..+/.test(contact);
    }

    return contact.replace(/[^0-9]/g, '').length >= 9;
}

/**
 * Determine whether a name is long enough to record.
 */
export function isValidName(value: string): boolean {
    return (value ?? '').trim().length >= 2;
}
