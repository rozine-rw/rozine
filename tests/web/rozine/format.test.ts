import { describe, expect, it } from 'vite-plus/test';
import {
    formatAmount,
    formatCount,
    formatDate,
    formatDayMonth,
    formatMillions,
    formatMonthShort,
    formatMonthYear,
    formatMonthYearLong,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';

const money = (amount: number) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

describe('Rozine display formatting', () => {
    it('writes full francs with thousands separators', () => {
        expect(formatRwf(money(12383800))).toBe('RWF 12,383,800');
        expect(formatAmount(money(5000))).toBe('5,000');
        expect(formatCount(1657)).toBe('1,657');
    });

    it('shortens large amounts exactly as the design does', () => {
        expect(formatRwfShort(money(850000))).toBe('RWF 850,000');
        expect(formatRwfShort(money(18000000))).toBe('RWF 18M');
        expect(formatRwfShort(money(11900000))).toBe('RWF 11.9M');
        expect(formatRwfShort(money(1600000000))).toBe('RWF 1.6B');
    });

    it('writes stat-tile millions without the currency', () => {
        expect(formatMillions(money(88000000))).toBe('88M');
        expect(formatMillions(money(3400000))).toBe('3.4M');
        expect(formatMillions(money(1600000000))).toBe('1.6B');
    });

    it('writes dates day-first in English and in the Kigali calendar', () => {
        expect(formatMonthYear('2026-06-02T09:00:00+02:00', 'en')).toBe(
            'Jun 2026',
        );
        expect(formatMonthYearLong('2026-09-01T00:00:00+02:00', 'en')).toBe(
            'September 2026',
        );
        expect(formatDayMonth('2026-10-07T00:00:00+02:00', 'en')).toBe('7 Oct');
        expect(formatMonthShort('2026-06-01', 'en')).toBe('Jun');
        expect(formatDate('2026-10-05T00:00:00+02:00', 'en')).toBe(
            '5 Oct 2026',
        );
        expect(formatDayMonth('2026-10-07T00:00:00+02:00', 'fr')).toBe(
            '7 oct.',
        );
    });
});
