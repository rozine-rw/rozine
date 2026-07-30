/**
 * The demand-simulation model behind the Pulse waitlist.
 *
 * A business sizes itself on five figures it reports: revenue and costs over
 * the last twelve months, what it does, the year it registered and the term it
 * wants. These mirror the formulas the server persists with, so what a visitor
 * sees while they type is what gets recorded when they claim a spot.
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
    average_term: number | null;
};

export type ContactMethod = 'phone' | 'email';

export type Sizing = {
    score: number;
    rating: Rating;
    flatRate: number;
    /** What the figures size to, before the loan band is applied. */
    sizedAmount: number;
    qualifiedAmount: number;
    monthlyRepayment: number;
    coverRatio: number;
    belowMinimum: boolean;
    atMaximum: boolean;
    /** The monthly surplus the smallest loan would need over this term. */
    requiredSurplus: number;
};

export const TERMS = [3, 6, 9, 12] as const;

export const BLENDED_YIELD = 13.0;

export const MIN_PLEDGE = 5000;

export const MAX_PLEDGE = 50000000;

/** The smallest revenue a business can be sized on. */
export const MINIMUM_REVENUE = 15000000;

/** The smallest loan Rozine writes. */
export const MIN_LOAN = 5000000;

/** The largest loan Rozine writes. */
export const MAX_LOAN = 50000000;

/** The earliest year of registration the waitlist offers. */
export const EARLIEST_REGISTRATION_YEAR = 1996;

/**
 * The cover a monthly repayment must leave on top of itself, so a business
 * never commits every franc of its surplus.
 */
const COVER = 1.25;

/** The share of a year's revenue a loan may never exceed. */
const REVENUE_CEILING = 0.35;

/** The step a pre-qualified amount is rounded down to. */
const ROUNDING_STEP = 100000;

/** The years of trading beyond which more history adds nothing. */
const TRADING_YEARS_CAP = 10;

/** The profit margin beyond which a fatter margin adds nothing. */
const MARGIN_CAP = 0.45;

/**
 * What a business does, and the points that earns it. These mirror the
 * `PulseSector` enum the server scores with.
 */
export const SECTORS: { value: string; score: number }[] = [
    { value: 'Agriculture', score: 4 },
    { value: 'Retail & trade', score: 6 },
    { value: 'Logistics', score: 5 },
    { value: 'Manufacturing', score: 4 },
    { value: 'Services', score: 5 },
    { value: 'Energy', score: 3 },
    { value: 'Other', score: 2 },
];

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

/** What an investor gets, listed beside the amount they set. */
export const INVESTOR_CRITERIA = [
    '10–15% total return over 3 to 12 months.',
    'Paid back monthly, automatically, into your Rozine account.',
    'You pick businesses you want to invest in. Each one is audited by an ICPAR-certified CPA.',
];

/** What a business needs before it can be sized. */
export const BUSINESS_CRITERIA = [
    'Registered with RDB',
    'RWF 15M+ revenue in the last 12 months',
    'Showing clear profit',
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

/**
 * Get the money a business has left each month to service debt with.
 */
export function monthlySurplus(
    annualRevenue: number,
    annualCosts: number,
): number {
    return (annualRevenue - annualCosts) / 12;
}

/**
 * Get the share of revenue a business keeps.
 */
export function profitMargin(
    annualRevenue: number,
    annualCosts: number,
): number {
    if (annualRevenue <= 0 || annualCosts >= annualRevenue) {
        return 0;
    }

    return (annualRevenue - annualCosts) / annualRevenue;
}

/**
 * Get the years a business has been trading.
 */
export function yearsTrading(registeredYear: number): number {
    return Math.max(0, new Date().getFullYear() - registeredYear);
}

/**
 * The years of registration a business may pick from, newest first.
 */
export function registrationYears(): number[] {
    const years: number[] = [];

    for (
        let year = new Date().getFullYear();
        year >= EARLIEST_REGISTRATION_YEAR;
        year--
    ) {
        years.push(year);
    }

    return years;
}

/**
 * Score a business' strength out of a hundred, held between forty and
 * ninety-two so neither a long history nor a fat margin runs away with it.
 */
export function scoreFor(
    annualRevenue: number,
    annualCosts: number,
    sector: string,
    registeredYear: number,
): number {
    const sectorScore =
        SECTORS.find((entry) => entry.value === sector)?.score ?? 0;

    const score =
        50 +
        Math.min(yearsTrading(registeredYear), TRADING_YEARS_CAP) * 1.5 +
        sectorScore +
        Math.min(profitMargin(annualRevenue, annualCosts), MARGIN_CAP) * 42;

    return Math.max(40, Math.min(92, score));
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
 * Get the flat rate, as a percentage, for a score over a given term. A weaker
 * score and a longer term both raise it.
 */
export function yieldFor(score: number, months: number): number {
    return Math.max(
        10.5,
        Math.min(15, 10 + (100 - score) * 0.085 + ((months - 3) / 9) * 2.5),
    );
}

/**
 * Get the repayment a business could carry each month, which is its surplus
 * less the cover the model insists on.
 */
export function affordablePayment(
    annualRevenue: number,
    annualCosts: number,
): number {
    return Math.max(0, monthlySurplus(annualRevenue, annualCosts)) / COVER;
}

/**
 * Get the amount a business' figures size to, before the loan band applies.
 *
 * Affordability binds first; the share-of-revenue ceiling is the backstop.
 */
export function sizeFor(
    annualRevenue: number,
    annualCosts: number,
    score: number,
    months: number,
): number {
    const affordable =
        (affordablePayment(annualRevenue, annualCosts) * months) /
        (1 + yieldFor(score, months) / 100);

    const sized = Math.min(affordable, annualRevenue * REVENUE_CEILING);

    return Math.floor(Math.max(0, sized) / ROUNDING_STEP) * ROUNDING_STEP;
}

/**
 * Get the amount a business pre-qualifies for, held to the loan band Rozine
 * writes within.
 */
export function qualifyFor(
    annualRevenue: number,
    annualCosts: number,
    score: number,
    months: number,
): number {
    return Math.min(
        sizeFor(annualRevenue, annualCosts, score, months),
        MAX_LOAN,
    );
}

/**
 * Get the monthly surplus the smallest loan Rozine writes would need over a
 * given term, which is what a business short of it has to close.
 */
export function surplusForMinimumLoan(score: number, months: number): number {
    return (MIN_LOAN * COVER * (1 + yieldFor(score, months) / 100)) / months;
}

/**
 * Size a business against the figures it reported, over the term it picked.
 */
export function sizingFor(
    annualRevenue: number,
    annualCosts: number,
    sector: string,
    registeredYear: number,
    months: number,
): Sizing {
    const score = scoreFor(annualRevenue, annualCosts, sector, registeredYear);
    const flatRate = yieldFor(score, months);
    const sizedAmount = sizeFor(annualRevenue, annualCosts, score, months);
    const qualifiedAmount = Math.min(sizedAmount, MAX_LOAN);
    const monthlyRepayment =
        (qualifiedAmount * (1 + flatRate / 100)) / Math.max(1, months);

    return {
        score,
        rating: rate(score),
        flatRate,
        sizedAmount,
        qualifiedAmount,
        monthlyRepayment,
        coverRatio:
            monthlyRepayment > 0
                ? monthlySurplus(annualRevenue, annualCosts) / monthlyRepayment
                : 0,
        belowMinimum: annualRevenue > 0 && sizedAmount < MIN_LOAN,
        atMaximum: sizedAmount > MAX_LOAN,
        requiredSurplus: surplusForMinimumLoan(score, months),
    };
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
