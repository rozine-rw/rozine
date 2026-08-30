/**
 * Presentation types and helpers for the Pulse waitlist.
 *
 * Financial, scoring, eligibility and policy facts are deliberately absent
 * from this module. Laravel returns those facts through API Resources; React
 * only formats and decorates them for display.
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
    projected_return: number;
};

export type BusinessNote = Listing & {
    avatar: ThemedColor;
    ratingColor: ThemedColor;
};

export type Traction = {
    pledged: number;
    investors: number;
    businesses: number;
    average_loan: number | null;
    average_yield: number | null;
    average_rating: number | null;
    average_term: number | null;
};

export type ContactMethod = 'phone' | 'email';

export type Sizing = {
    rating: {
        band: string;
        score: number;
    };
    flat_rate: number;
    sized_amount: number;
    qualified_amount: number;
    monthly_repayment: number;
    monthly_surplus: number;
    cover_ratio: number;
    below_minimum: boolean;
    at_maximum: boolean;
    required_surplus: number;
    status: 'pre_qualified' | 'waitlisted';
};

export type PulsePolicy = {
    terms: number[];
    sectors: string[];
    registration_years: number[];
    minimum_revenue: number;
    minimum_loan: number;
    maximum_loan: number;
    pledge: {
        minimum: number;
        maximum: number;
        step: number;
        default: number;
    };
};

export type InvestorPreview = {
    pledge_amount: number;
    projected_return: number;
    blended_yield: number;
    listings: Listing[];
};

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

/** Static, non-policy context for the two waitlist panels. */
export const INVESTOR_CONTEXT = [
    '10–15% total return over 3 to 12 months.',
    'Paid back monthly, automatically, into your Rozine account.',
    'You pick businesses you want to invest in. Each one is audited by an ICPAR-certified CPA.',
];

export const TRUST_MARKERS = [
    { color: { light: '#12a150', dark: '#22c55e' }, label: 'Encrypted' },
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
 * Shorten a figure to the abbreviation the traction tiles carry, without the
 * currency in front of it.
 */
export function formatAbbrev(amount: number): string {
    const value = Math.round(amount || 0);

    if (value >= 1e9) {
        return `${Math.round(value / 1e7) / 100}B`;
    }

    if (value >= 1e6) {
        return `${Math.round(value / 1e5) / 10}M`;
    }

    if (value >= 1e3) {
        return `${Math.round(value / 1e3)}K`;
    }

    return String(value);
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
 * Keep only the digits a visitor typed into an amount.
 */
export function digitsOnly(value: string, maxLength = 12): number {
    const digits = (value ?? '').replace(/[^0-9]/g, '').slice(0, maxLength);

    return digits === '' ? 0 : parseInt(digits, 10);
}

/** Decorate a server-returned rating without deriving its band or score. */
export function decorateRating(band: string, score: number): Rating {
    const display = score.toFixed(1);

    if (band === 'Strong') {
        return {
            band,
            score: display,
            color: { light: '#0f7a3d', dark: '#4ade80' },
            background: {
                light: 'rgba(18,161,80,.12)',
                dark: 'rgba(16,161,80,.14)',
            },
        };
    }

    if (band === 'Stable') {
        return {
            band,
            score: display,
            color: { light: '#0a5cff', dark: '#2dd4bf' },
            background: {
                light: 'rgba(10,92,255,.1)',
                dark: 'rgba(20,168,160,.14)',
            },
        };
    }

    if (band === 'Weak') {
        return {
            band,
            score: display,
            color: { light: '#c47c00', dark: '#fbbf24' },
            background: {
                light: 'rgba(221,138,0,.12)',
                dark: 'rgba(221,138,0,.16)',
            },
        };
    }

    return {
        band,
        score: display,
        color: { light: '#c0392b', dark: '#f87171' },
        background: {
            light: 'rgba(229,72,77,.12)',
            dark: 'rgba(229,72,77,.16)',
        },
    };
}

/** Dress server-returned listing facts with presentation-only colors. */
export function decorateListings(listings: Listing[]): BusinessNote[] {
    return listings.map((listing) => ({
        ...listing,
        avatar: ACCENTS[listing.accent % ACCENTS.length],
        ratingColor: ratingColor(listing.rating_band),
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
