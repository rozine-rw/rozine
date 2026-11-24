import { describe, expect, it } from 'vite-plus/test';
import {
    BIZ_FAQS,
    BIZ_STEPS,
    HELP_TABS,
    JOURNEY,
    LANDING_PAGES,
    businessEstimate,
    contactProblem,
    formatPosition,
    investorExample,
    pageFromHash,
    rateFor,
    rwf,
} from '@/lib/landing';

describe('the landing page arithmetic', () => {
    it('adds half a point to the band rate for every three extra months', () => {
        expect(rateFor('Strong', 3)).toBe(12.5);
        expect(rateFor('Stable', 5)).toBe(13.8);
        expect(rateFor('Distressed', 6)).toBe(15);
    });

    it('shows the design example for RWF 100,000 over 5 months in a Stable business', () => {
        expect(investorExample(100000, 5, 'Stable')).toEqual({
            amount: 'RWF 100,000',
            rate: '13.8%',
            earn: '+RWF 11,730',
            back: 'RWF 111,730',
            month: 'RWF 22,346',
        });
    });

    it('caps a raise at 35% of yearly revenue and RWF 100M', () => {
        expect(businessEstimate(120000000)).toEqual({
            revenue: 'RWF 120M',
            max: 'RWF 42M',
            cap: '35% of your yearly revenue',
        });
        expect(businessEstimate(305000000).max).toBe('RWF 100M');
        expect(businessEstimate(305000000).cap).toBe(
            'The RWF 100M maximum per raise',
        );
        expect(businessEstimate(35000000).max).toBe('RWF 12.2M');
    });

    it('writes francs and queue places with thousands separators', () => {
        expect(rwf(1234567.4)).toBe('RWF 1,234,567');
        expect(formatPosition(1204)).toBe('1,204');
    });
});

describe('the landing page addresses', () => {
    it('gives each view its own fragment and keeps Invest at the bare address', () => {
        expect(pageFromHash('')).toBe('home');
        expect(pageFromHash('#')).toBe('home');
        expect(pageFromHash('#businesses')).toBe('biz');
        expect(pageFromHash('#how-it-works')).toBe('how');
        expect(pageFromHash('#help')).toBe('help');
    });

    it("still honours the earlier site's investor address", () => {
        expect(pageFromHash('#investors')).toBe('home');
    });

    it('leaves the view alone for a fragment that names none, such as the waitlist', () => {
        expect(pageFromHash('#join')).toBeNull();
    });

    it('lists the four tabs in the order of the design', () => {
        expect(LANDING_PAGES.map(([, label]) => label)).toEqual([
            'Invest',
            'Businesses',
            'How it works',
            'Help',
        ]);
    });
});

describe('the waitlist checks before sending', () => {
    it('asks each audience for its own kind of name', () => {
        expect(contactProblem('investor', ' ', '0788123456')).toBe(
            'Add your name.',
        );
        expect(contactProblem('business', 'K', '0788123456')).toBe(
            'Add your business name.',
        );
    });

    it('accepts a phone number as people type it, or an email', () => {
        expect(contactProblem('investor', 'Di', '+250 788 123 456')).toBe('');
        expect(contactProblem('investor', 'Di', 'di@example.rw')).toBe('');
    });

    it('turns away anything else', () => {
        for (const contact of ['', '12345', 'di@', 'di example.rw']) {
            expect(contactProblem('investor', 'Di', contact)).toBe(
                'Add a valid phone number or email.',
            );
        }
    });
});

describe('the landing page content', () => {
    it('carries the design copy for every list', () => {
        expect(BIZ_STEPS).toHaveLength(7);
        expect(JOURNEY).toHaveLength(6);
        expect(BIZ_FAQS).toHaveLength(6);
        expect(
            HELP_TABS.map(([, label, faqs]) => [label, faqs.length]),
        ).toEqual([
            ['Investing', 5],
            ['Your money', 5],
            ['For businesses', 6],
            ['Account', 4],
        ]);
    });
});
