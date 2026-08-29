import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    ACCENTS,
    affordablePayment,
    businessNotes,
    digitsOnly,
    formatAbbrev,
    formatAverage,
    formatCompact,
    formatFull,
    formatNumber,
    isValidContact,
    isValidName,
    MAX_LOAN,
    monthlySurplus,
    profitMargin,
    qualifyFor,
    rate,
    ratingColor,
    registrationYears,
    scoreFor,
    sizeFor,
    sizingFor,
    surplusForMinimumLoan,
    yearsTrading,
    yieldFor,
} from '@/lib/pulse';
import type { Listing } from '@/lib/pulse';

afterEach(() => {
    vi.useRealTimers();
});

describe('Pulse amount presentation', () => {
    it.each([
        [0, 'RWF 0'],
        [999, 'RWF 999'],
        [1_000, 'RWF 1K'],
        [999_999, 'RWF 1000K'],
        [1_000_000, 'RWF 1M'],
        [1_250_000, 'RWF 1.3M'],
        [1_000_000_000, 'RWF 1B'],
        [1_250_000_000, 'RWF 1.3B'],
    ])('formats %d as a compact amount', (amount, expected) => {
        expect(formatCompact(amount)).toBe(expected);
    });

    it.each([
        [0, '0'],
        [999, '999'],
        [1_000, '1K'],
        [1_000_000, '1M'],
        [1_230_000_000, '1.23B'],
    ])('abbreviates %d without a currency prefix', (amount, expected) => {
        expect(formatAbbrev(amount)).toBe(expected);
    });

    it('formats full amounts, averages, and grouped numbers', () => {
        expect(formatFull(1_234_567.8)).toBe('RWF 1,234,568');
        expect(formatFull(0)).toBe('RWF 0');
        expect(formatAverage(null)).toBe('—');
        expect(formatAverage(12.6)).toBe('13');
        expect(formatAverage(12.64, 1)).toBe('12.6');
        expect(formatNumber(12_345.6)).toBe('12,346');
        expect(formatNumber(0)).toBe('0');
    });

    it('normalizes typed amounts using both default and explicit limits', () => {
        expect(digitsOnly('RWF 12,345')).toBe(12_345);
        expect(digitsOnly('abc')).toBe(0);
        expect(digitsOnly('', 4)).toBe(0);
        expect(digitsOnly('123456', 4)).toBe(1234);
        expect(digitsOnly(null as unknown as string)).toBe(0);
    });
});

describe('Pulse business scoring and sizing', () => {
    it('calculates surplus, margin, and years without allowing invalid negatives', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-01T00:00:00Z'));

        expect(monthlySurplus(24_000_000, 12_000_000)).toBe(1_000_000);
        expect(profitMargin(0, 0)).toBe(0);
        expect(profitMargin(10, 10)).toBe(0);
        expect(profitMargin(10, 12)).toBe(0);
        expect(profitMargin(100, 70)).toBe(0.3);
        expect(yearsTrading(2020)).toBe(6);
        expect(yearsTrading(2030)).toBe(0);

        const years = registrationYears();

        expect(years[0]).toBe(2026);
        expect(years.at(-1)).toBe(1996);
        expect(years).toHaveLength(31);
    });

    it('scores unknown, ordinary, capped, minimum, and maximum businesses', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-01T00:00:00Z'));

        expect(scoreFor(0, 0, 'Unknown', 2030)).toBe(50);
        expect(scoreFor(100, 70, 'Services', 2024)).toBeCloseTo(70.6);
        expect(scoreFor(100, 0, 'Retail & trade', 1996)).toBeCloseTo(89.9);
        expect(scoreFor(-1, 0, 'Unknown', 2100)).toBe(50);
        expect(scoreFor(100, -100, 'Retail & trade', 1990)).toBeCloseTo(89.9);
    });

    it.each([
        [90, 'Strong', '4.5'],
        [70, 'Stable', '3.5'],
        [50, 'Weak', '2.5'],
        [20, 'Distressed', '1.0'],
    ])('maps score %d to the %s rating', (score, band, display) => {
        expect(rate(score)).toMatchObject({ band, score: display });
    });

    it('clamps yields at both limits and calculates affordability', () => {
        expect(yieldFor(100, 3)).toBe(10.5);
        expect(yieldFor(70, 6)).toBeCloseTo(13.3833333333);
        expect(yieldFor(0, 12)).toBe(15);
        expect(affordablePayment(12_000_000, 0)).toBe(800_000);
        expect(affordablePayment(0, 12_000_000)).toBe(0);
    });

    it('sizes against affordability, revenue ceiling, zero, and the maximum band', () => {
        expect(sizeFor(0, 1, 50, 3)).toBe(0);

        const affordabilityBound = sizeFor(24_000_000, 18_000_000, 70, 3);
        const revenueBound = sizeFor(100_000_000, 0, 70, 12);

        expect(affordabilityBound).toBeGreaterThan(0);
        expect(affordabilityBound).toBeLessThan(8_400_000);
        expect(revenueBound).toBe(35_000_000);
        expect(qualifyFor(500_000_000, 0, 90, 12)).toBe(MAX_LOAN);
        expect(qualifyFor(24_000_000, 18_000_000, 70, 3)).toBe(
            affordabilityBound,
        );
        expect(surplusForMinimumLoan(70, 6)).toBeGreaterThan(0);
    });

    it('returns all sizing states including no repayment, waitlist, and cap', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-01T00:00:00Z'));

        const empty = sizingFor(0, 0, '', 0, 0);
        const waitlist = sizingFor(15_000_000, 14_000_000, 'Other', 2026, 3);
        const qualified = sizingFor(
            50_000_000,
            25_000_000,
            'Services',
            2020,
            12,
        );
        const capped = sizingFor(500_000_000, 0, 'Retail & trade', 1996, 12);

        expect(empty).toMatchObject({
            monthlyRepayment: 0,
            coverRatio: 0,
            belowMinimum: false,
            atMaximum: false,
        });
        expect(waitlist.belowMinimum).toBe(true);
        expect(qualified.coverRatio).toBeGreaterThan(0);
        expect(qualified.belowMinimum).toBe(false);
        expect(capped.qualifiedAmount).toBe(MAX_LOAN);
        expect(capped.atMaximum).toBe(true);
    });
});

describe('Pulse listing and signup helpers', () => {
    const listing = (ratingBand: string, accent: number): Listing => ({
        id: accent + 1,
        initial: 'A',
        name: `Business ${accent}`,
        district: 'Gasabo',
        term: '6mo',
        yield: '13%',
        yield_rate: 13,
        rating_band: ratingBand,
        rating_score: '3.5',
        accent,
    });

    it('dresses listings across every rating and wraps avatar accents', () => {
        const notes = businessNotes(
            [
                listing('Strong', 0),
                listing('Stable', 1),
                listing('Weak', 2),
                listing('Distressed', ACCENTS.length),
            ],
            100_000,
        );

        expect(notes.map((note) => note.ratingColor)).toEqual([
            ratingColor('Strong'),
            ratingColor('Stable'),
            ratingColor('Weak'),
            ratingColor('Distressed'),
        ]);
        expect(notes[3].avatar).toEqual(ACCENTS[0]);
        expect(notes[0].projectedReturn).toBe('RWF 113,000');
    });

    it.each([
        ['person@example.com', 'email', true],
        ['bad-email', 'email', false],
        ['+250 788 123 456', 'phone', true],
        ['123', 'phone', false],
        [null, 'phone', false],
    ] as const)(
        'validates %s as %s contact = %s',
        (value, method, expected) => {
            expect(isValidContact(value as unknown as string, method)).toBe(
                expected,
            );
        },
    );

    it('requires a meaningful trimmed name', () => {
        expect(isValidName('Ada')).toBe(true);
        expect(isValidName(' A ')).toBe(false);
        expect(isValidName(null as unknown as string)).toBe(false);
    });
});
