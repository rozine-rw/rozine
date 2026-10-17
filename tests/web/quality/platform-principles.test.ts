import { describe, expect, it } from 'vite-plus/test';
import { SECTION_DESIGNS } from '@/components/admin/section-designs';
import en from '@/lib/i18n/catalogs/en';
import fr from '@/lib/i18n/catalogs/fr';
import rw from '@/lib/i18n/catalogs/rw';

/**
 * BRS v2.0 GEN-R01: Rozine never buys or sells notes, makes a market, sets resale prices or
 * promises a return, and the plan's Phase 3 prohibits principal inventory and any sale "to
 * Rozine". The design still shows a "sell back to Rozine" promise and a RAMP book of
 * Rozine-held notes; the principle wins, so no locale may carry either.
 */
const PROHIBITED: Record<string, RegExp[]> = {
    en: [
        /\bsell\b[^.]*\bback to Rozine\b/iu,
        /\bRozine (?:buys|will buy|repurchases)\b/iu,
        /\bmarket[- ]mak/iu,
        /\bguaranteed (?:returns?|income|exit|liquidity|payouts?)\b/iu,
        /\bRAMP\b/u,
    ],
    fr: [
        /\brevend\w*\s+à\s+Rozine\b/iu,
        /\brendements?\s+garantis?\b/iu,
        /\bRAMP\b/u,
    ],
    rw: [/\bgurisha kuri Rozine\b/iu, /\bRAMP\b/u],
};

const CATALOGS: Record<string, Record<string, unknown>> = { en, fr, rw };

/** A message's copy, with every plural form of a plural message. */
const copyOf = (message: unknown): string =>
    typeof message === 'string'
        ? message
        : Object.values(message as Record<string, string>).join('\n');

describe('Rozine is a platform, never a principal (BRS GEN-R01)', () => {
    it.each(Object.keys(PROHIBITED))(
        'keeps every %s message free of buy-back, market-making and guaranteed-return claims',
        (locale) => {
            const offending = Object.entries(CATALOGS[locale]).filter(
                ([, message]) =>
                    PROHIBITED[locale].some((pattern) =>
                        pattern.test(copyOf(message)),
                    ),
            );

            expect(offending).toEqual([]);
        },
    );

    it('catches the claims it exists to catch', () => {
        expect(
            PROHIBITED.en.some((pattern) =>
                pattern.test('Sell back to Rozine, or on the market, any time'),
            ),
        ).toBe(true);
        expect(
            PROHIBITED.fr.some((pattern) =>
                pattern.test('Revendez à Rozine, ou sur le marché'),
            ),
        ).toBe(true);
        expect(
            PROHIBITED.rw.some((pattern) =>
                pattern.test('Gurisha kuri Rozine, cyangwa ku isoko'),
            ),
        ).toBe(true);
    });

    it('offers resale only to other investors', () => {
        expect(en['investor.auth.brand_point_3']).toBe(
            'Sell your units to other investors on the market',
        );
    });

    it('gives the Finance dashboard no book of Rozine-held notes', () => {
        const finance = SECTION_DESIGNS.finance;

        expect(finance?.tabs?.map((tab) => tab.key)).toEqual([
            'streams',
            'ledger',
        ]);
        expect(finance?.blocks.map((block) => block.key)).not.toContain('ramp');
    });
});
