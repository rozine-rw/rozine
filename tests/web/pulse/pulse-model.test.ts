import { describe, expect, it } from 'vite-plus/test';
import {
    ACCENTS,
    decorateListings,
    decorateRating,
    digitsOnly,
    formatAbbrev,
    formatAverage,
    formatCompact,
    formatFull,
    formatNumber,
    isValidContact,
    isValidName,
    ratingColor,
} from '@/lib/pulse';
import type { Listing } from '@/lib/pulse';

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

describe('Pulse server-fact presentation', () => {
    it.each([
        ['Strong', 4.5, '4.5'],
        ['Stable', 3.5, '3.5'],
        ['Weak', 2.5, '2.5'],
        ['Distressed', 1, '1.0'],
    ])(
        'decorates the server-returned %s rating without deriving it',
        (band, score, display) => {
            expect(decorateRating(band, score)).toMatchObject({
                band,
                score: display,
            });
        },
    );
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
        projected_return: 113_000,
    });

    it('dresses listings across every rating and wraps avatar accents', () => {
        const notes = decorateListings([
            listing('Strong', 0),
            listing('Stable', 1),
            listing('Weak', 2),
            listing('Distressed', ACCENTS.length),
        ]);

        expect(notes.map((note) => note.ratingColor)).toEqual([
            ratingColor('Strong'),
            ratingColor('Stable'),
            ratingColor('Weak'),
            ratingColor('Distressed'),
        ]);
        expect(notes[3].avatar).toEqual(ACCENTS[0]);
        expect(notes[0].projected_return).toBe(113_000);
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
