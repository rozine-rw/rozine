import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

const root = path.resolve(__dirname, '../../../resources/js');
const stylesheets = ['app.css', 'rozine.css', 'pulse.css', 'site.css'].map(
    (file) =>
        readFileSync(
            path.resolve(__dirname, '../../../resources/css', file),
            'utf8',
        ),
);

const sources = [...readdirSync(root, { recursive: true, encoding: 'utf8' })]
    .filter((file) => file.endsWith('.tsx'))
    .map((file) => ({
        file: file.split(path.sep).join('/'),
        text: readFileSync(path.join(root, file), 'utf8'),
    }));

/** Every string literal on a line, so a class list is judged with the dark: utilities beside it. */
const classLists = (text: string) =>
    text.split('\n').flatMap((line, index) =>
        [...line.matchAll(/'([^'\n]*)'|"([^"\n]*)"|`([^`]*)`/gu)].map(
            (match) => ({
                line: index + 1,
                classes: (match[1] ?? match[2] ?? match[3]).split(/\s+/u),
            }),
        ),
    );

const luminance = (hex: string) => {
    const full =
        hex.length === 4
            ? `#${[...hex.slice(1)].map((digit) => digit + digit).join('')}`
            : hex;
    const [r, g, b] = [1, 3, 5].map((index) => {
        const channel = parseInt(full.slice(index, index + 2), 16) / 255;

        return channel <= 0.03928
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

describe('Dark theme tokens', () => {
    it('uses only rz colour utilities that the theme defines', () => {
        const defined = new Set(
            stylesheets.flatMap((css) =>
                [...css.matchAll(/--color-(rz-[\w-]+):/gu)].map(
                    ([, name]) => name,
                ),
            ),
        );
        const undefinedTokens = sources.flatMap(({ file, text }) =>
            [
                ...text.matchAll(
                    /(?:^|[\s'"`:])(?:bg|text|border|ring|fill|stroke|from|via|to|outline|divide|decoration|placeholder|caret)-(rz-[a-z0-9-]+)/gu,
                ),
            ]
                .map(([, token]) => token)
                .filter((token) => !defined.has(token))
                .map((token) => `${file} ${token}`),
        );

        expect(undefinedTokens).toEqual([]);
    });
});

describe('Light-theme colours carry a dark-theme counterpart', () => {
    /**
     * Light literals that are meant to stay light in both themes: white logo tiles and
     * pagination dots over photos, toggle knobs, spinners and chips on coloured fills, the
     * wallet's brand payment chips, the two-factor QR code's quiet zone and the unused
     * starter pages.
     */
    const INTENTIONAL = new Set([
        'components/auditor/jobs/radius-map.tsx border-white',
        'components/auditor/ui.tsx bg-white',
        'components/business/profile/account-sections.tsx bg-white',
        'components/business/wallet/balance-card.tsx bg-[#6ae8bf]',
        'components/business/wallet/balance-card.tsx bg-[#ffc44f]',
        'components/investor/deals/checkout-sheet.tsx border-t-white',
        'components/investor/deals/deal-bits.tsx bg-white',
        'components/investor/deals/deal-detail.tsx bg-white/95',
        'components/investor/deals/desk-card.tsx bg-white',
        'components/investor/deals/swipe-card.tsx bg-white',
        'components/investor/floating-hold.tsx bg-[#fdeaea]',
        'components/investor/floating-hold.tsx bg-white',
        'components/investor/floating-hold.tsx text-[#0b1c3a]',
        'components/investor/portfolio/holding-detail.tsx bg-white/95',
        'components/investor/portfolio/portfolio-insights.tsx bg-white',
        'components/investor/profile/profile-parts.tsx bg-white',
        'components/investor/wallet/funding.tsx bg-[#6ae8bf]',
        'components/investor/wallet/funding.tsx bg-[#ffc44f]',
        'components/investor/wallet/funding.tsx bg-[#ffcb05]',
        'components/investor/wallet/funding.tsx text-[#0c1830]',
        'components/rozine/login-alerts.tsx bg-white',
        'components/two-factor-setup-modal.tsx bg-white',
        'layouts/auth/auth-split-layout.tsx text-black',
        'pages/business/publish.tsx border-t-white',
        'pages/investor/lot.tsx bg-white/95',
        'pages/welcome.tsx text-[#1b1b18]',
    ]);

    const COLOUR =
        /^((?:[a-z0-9-]+:)*)(bg|text|border(?:-[trblxy])?|from|via|to|ring|divide|outline)-(\[#[0-9a-f]{3,6}\]|white|black)(?:\/(\d+|\[[^\]]+\]))?$/iu;

    it('never leaves a light surface or a near-black text colour without a dark: utility', () => {
        const leaks = sources.flatMap(({ file, text }) =>
            classLists(text).flatMap(({ line, classes }) => {
                const darkProperties = new Set(
                    classes
                        .filter((name) => name.includes('dark:'))
                        .map(
                            (name) =>
                                name.split(':').at(-1)?.split('-')[0] ?? '',
                        ),
                );

                return classes.flatMap((name) => {
                    const match = COLOUR.exec(name);

                    if (!match || match[1].includes('dark:')) {
                        return [];
                    }

                    const [, , property, value, opacity] = match;

                    /* A translucent white over a coloured fill stays legible in both themes. */
                    const alpha = opacity?.startsWith('[')
                        ? parseFloat(opacity.slice(1, -1)) * 100
                        : parseInt(opacity ?? '100', 10);

                    if (alpha < 60) {
                        return [];
                    }

                    const hex =
                        value === 'white'
                            ? '#ffffff'
                            : value === 'black'
                              ? '#000000'
                              : value.slice(1, -1);
                    const level = luminance(hex);
                    const base = property.split('-')[0];
                    const leaksInDark =
                        (['bg', 'from', 'via', 'to'].includes(base) &&
                            level > 0.6) ||
                        (base === 'text' && level < 0.06) ||
                        (['border', 'ring', 'divide', 'outline'].includes(
                            base,
                        ) &&
                            level > 0.5);
                    const key = `${file} ${name.replace(/^((?:[a-z0-9-]+:)*)/u, '')}`;

                    return leaksInDark &&
                        !darkProperties.has(base) &&
                        !INTENTIONAL.has(key)
                        ? [`${file}:${line} ${name}`]
                        : [];
                });
            }),
        );

        expect(leaks).toEqual([]);
    });
});

describe('Starter-kit screens share the Rozine dark palette', () => {
    const block = (css: string, selector: string) => {
        const start = css.indexOf(`\n${selector} {`);

        return Object.fromEntries(
            [
                ...css
                    .slice(start, css.indexOf('\n}', start))
                    .matchAll(/--([\w-]+):\s*([^;]+);/gu),
            ].map(([, name, value]) => [name, value.trim()]),
        );
    };
    const [app, rozine] = stylesheets;
    const shadcn = block(app, '.dark');
    const brand = block(rozine, '.dark');

    it('paints Settings and other shadcn screens in the navy surfaces, not neutral black', () => {
        expect(shadcn.background).toBe(brand['rz-page']);
        expect(shadcn.card).toBe(brand['rz-surface']);
        expect(shadcn.popover).toBe(brand['rz-surface']);
        expect(shadcn.foreground).toBe(brand['rz-ink']);
        expect(shadcn.primary).toBe(brand['rz-investor']);
    });

    it('keeps the primary button label readable on its fill', () => {
        const contrast =
            (luminance(shadcn['primary-foreground']) + 0.05) /
            (luminance(shadcn.primary) + 0.05);

        expect(contrast).toBeGreaterThanOrEqual(4.5);
    });
});

describe('White labels on green fills', () => {
    it('darkens the Academy "Got it" fill in dark mode so its white label reads at 4.5:1', () => {
        const source = readFileSync(
            path.join(root, 'pages/business/academy-module.tsx'),
            'utf8',
        );
        const button =
            /className="([^"]*bg-rz-positive[^"]*text-white[^"]*)"/u.exec(
                source,
            )?.[1];
        const darkFill = /dark:bg-\[(#[0-9a-f]{6})\]/u.exec(button ?? '')?.[1];

        expect(darkFill).toBeDefined();
        expect(
            1.05 / (luminance(darkFill ?? '#ffffff') + 0.05),
        ).toBeGreaterThanOrEqual(4.5);
    });
});

describe('Auditor calendar today marker', () => {
    it('rings today in the auditor accent in dark mode, not near-white ink', () => {
        const source = readFileSync(
            path.join(root, 'components/auditor/portfolio/audit-calendar.tsx'),
            'utf8',
        );
        const today = /: isToday\s*\?\s*'([^']*)'/u.exec(source)?.[1] ?? '';

        expect(today).toContain('dark:border-[#f0a060]');
        expect(today).not.toContain('dark:border-rz-ink');
    });
});

describe('Starter-kit primary action (QA #721)', () => {
    it('is the Rozine blue in the light theme too, not near-black', () => {
        const app = stylesheets[0];
        const light = app.slice(app.indexOf(':root {'));
        const block = light.slice(0, light.indexOf('}'));

        expect(block).toMatch(/--primary: #1e3aff;/u);
        expect(block).toMatch(/--primary-foreground: #ffffff;/u);
    });
});
