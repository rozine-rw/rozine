import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

const css = (file: string) =>
    readFileSync(
        path.resolve(__dirname, '../../../resources/css', file),
        'utf8',
    );

describe('Reduced motion (WCAG 2.2 SC 2.3.3)', () => {
    it('loads the shared stylesheet, and every animated stylesheet, on every app page', () => {
        const app = css('app.css');

        expect(app).toContain("@import './rozine.css';");
        expect(app).toContain("@import './pulse.css';");
    });

    it('stops every animation and transition when the system asks for reduced motion', () => {
        const block =
            /@media \(prefers-reduced-motion: reduce\) \{([\s\S]*?)\n\}/u.exec(
                css('rozine.css'),
            )?.[1];

        expect(block).toBeDefined();
        expect(block).toMatch(/\*,\s*\*::before,\s*\*::after \{/u);
        expect(block).toContain('animation-duration: 0.01ms !important;');
        expect(block).toContain('animation-iteration-count: 1 !important;');
        expect(block).toContain('transition-duration: 0.01ms !important;');
        expect(block).toContain('scroll-behavior: auto !important;');
    });
});

describe('Visible keyboard focus (WCAG 2.2 SC 2.4.7)', () => {
    it('rings whatever has keyboard focus, outside every layer, in the audience accent', () => {
        const shared = css('rozine.css');
        const rule = /^:focus-visible \{([^}]*)\}/mu.exec(shared)?.[1];

        expect(rule).toContain(
            'outline: 2px solid var(--rz-accent-fill, var(--rz-admin-fill));',
        );
        expect(rule).toContain('outline-offset: 2px;');
        expect(shared.slice(0, shared.indexOf(':focus-visible {'))).not.toMatch(
            /@layer[^;{]*\{[^}]*$/u,
        );
    });

    it('rings only the controls inside a modal dialog, not the dialog holding focus itself', () => {
        expect(css('rozine.css')).toMatch(
            /\[role='dialog'\]\[tabindex='-1'\]:focus-visible,\s*\[role='alertdialog'\]\[tabindex='-1'\]:focus-visible \{\s*outline: none;\s*\}/u,
        );
    });
});

const tokens = (stylesheet: string, selector: string) => {
    const start = stylesheet.indexOf(`${selector} {`);
    const body = stylesheet.slice(start, stylesheet.indexOf('\n}', start));

    return Object.fromEntries(
        [...body.matchAll(/--(rz-[\w-]+):\s*(#[0-9a-f]{6});/giu)].map(
            ([, name, value]) => [name, value],
        ),
    );
};

const luminance = (hex: string) => {
    const [r, g, b] = [1, 3, 5].map((index) => {
        const channel = parseInt(hex.slice(index, index + 2), 16) / 255;

        return channel <= 0.03928
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const contrast = (foreground: string, background: string) => {
    const [light, dark] = [luminance(foreground), luminance(background)].sort(
        (a, b) => b - a,
    );

    return (light + 0.05) / (dark + 0.05);
};

describe('Text contrast (WCAG 2.2 SC 1.4.3)', () => {
    const shared = css('rozine.css');
    const light = tokens(shared, ':root');
    const dark = { ...light, ...tokens(shared, '.dark') };

    it.each([
        ['light', light, 'rz-muted'],
        ['light', light, 'rz-body'],
        ['light', light, 'rz-faint'],
        ['light', light, 'rz-group'],
        ['light', light, 'rz-danger-text'],
        ['light', light, 'rz-business-text'],
        ['light', light, 'rz-business-app-text'],
        ['light', light, 'rz-auditor-text'],
        ['dark', dark, 'rz-muted'],
        ['dark', dark, 'rz-faint'],
        ['light', light, 'rz-placeholder'],
        ['dark', dark, 'rz-placeholder'],
    ])('keeps %s %s at 4.5:1 or more on every surface', (_, theme, token) => {
        for (const surface of ['rz-surface', 'rz-page', 'rz-field']) {
            expect(
                contrast(theme[token], theme[surface]),
            ).toBeGreaterThanOrEqual(4.5);
        }
    });
});

describe('Literal text colours (WCAG 2.2 SC 1.4.3)', () => {
    /**
     * Files whose literal colours sit on a dark or tinted fill of their own, or are not text:
     * the starter welcome page no route serves, the notice tiles' icon colour and the review
     * drawer's kicker on its navy header.
     */
    const ALLOWED = new Set([
        'pages/welcome.tsx',
        'components/investor/notices/notices-panel.tsx',
        'components/admin/applications/review-drawer.tsx',
    ]);

    it('draws every light-theme literal text colour at 4.5:1 or more on white', () => {
        const root = path.resolve(__dirname, '../../../resources/js');
        const failures = [
            ...readdirSync(root, { recursive: true, encoding: 'utf8' }),
        ]
            .filter(
                (file) =>
                    file.endsWith('.tsx') &&
                    !ALLOWED.has(file.split(path.sep).join('/')),
            )
            .flatMap((file) =>
                readFileSync(path.join(root, file), 'utf8')
                    .split('\n')
                    /* A line that sets its own navy or translucent-white fill draws light text on dark. */
                    .filter(
                        (line) =>
                            !/bg-\[(#0c1830|rgba\(255,255,255)/u.test(line),
                    )
                    .flatMap((line) => [
                        ...line.matchAll(
                            /(^|[\s'"`])text-\[(#[0-9a-f]{6})\]/giu,
                        ),
                    ])
                    .filter(([, , colour]) => contrast(colour, '#ffffff') < 4.5)
                    .map(([, , colour]) => `${file} ${colour}`),
            );

        expect(failures).toEqual([]);
    });
});

describe('Audit oversight contrast in both themes (WCAG 2.2 SC 1.4.3)', () => {
    const light = tokens(css('rozine.css'), ':root');
    const dark = { ...light, ...tokens(css('rozine.css'), '.dark') };

    it.each([
        [
            'licence rejection',
            'components/admin/parties/review-queue.tsx',
            '#b3352a',
            'rz-surface',
            'rz-surface',
        ],
        [
            'licence metadata',
            'components/admin/parties/review-queue.tsx',
            '#7a5d33',
            '#faf7f1',
            'rz-surface-muted',
        ],
        [
            'sealed report download',
            'pages/admin/audit-reports.tsx',
            '#7a5d33',
            'rz-surface',
            'rz-surface',
        ],
    ])(
        'keeps %s readable on its actual light and dark fills',
        (_, file, foreground, lightFill, darkFill) => {
            const source = readFileSync(
                path.resolve(__dirname, '../../../resources/js', file),
                'utf8',
            );
            const classes = [...source.matchAll(/className="([^"]*)"/gu)]
                .map((match) => match[1])
                .find((className) =>
                    className.split(/\s/u).includes(`text-[${foreground}]`),
                );

            if (classes === undefined) {
                throw new Error(`Missing audited text colour in ${file}`);
            }

            const darkForeground =
                /(?:^|\s)dark:text-\[(#[0-9a-f]{6})\]/u.exec(classes)?.[1] ??
                foreground;
            expect(source).toContain(
                lightFill.startsWith('#')
                    ? `bg-[${lightFill}]`
                    : `bg-${lightFill}`,
            );
            expect(source).toContain(
                lightFill === darkFill
                    ? `bg-${darkFill}`
                    : `dark:bg-${darkFill}`,
            );
            expect(
                contrast(
                    foreground,
                    lightFill.startsWith('#') ? lightFill : light[lightFill],
                ),
            ).toBeGreaterThanOrEqual(4.5);
            expect(
                contrast(darkForeground, dark[darkFill]),
            ).toBeGreaterThanOrEqual(4.5);
        },
    );
});

describe('Placeholder contrast (WCAG 2.2 SC 1.4.3)', () => {
    it('keeps the Pulse input placeholder at 4.5:1 or more on its input and page', () => {
        const pulse = css('pulse.css');
        const light = tokens(pulse, '.rz-pulse');
        const dark = { ...light, ...tokens(pulse, '.dark .rz-pulse') };

        for (const background of ['#ffffff', '#f7f9fd']) {
            expect(
                contrast(light['rz-placeholder'], background),
            ).toBeGreaterThanOrEqual(4.5);
        }

        for (const background of [dark['rz-page'], '#0c0d13', '#12131a']) {
            expect(
                contrast(dark['rz-placeholder'], background),
            ).toBeGreaterThanOrEqual(4.5);
        }
    });

    it('never draws form placeholders in the decorative faint grey', () => {
        const sources = [
            ...readdirSync(path.resolve(__dirname, '../../../resources/js'), {
                recursive: true,
                encoding: 'utf8',
            }),
        ].filter((file) => file.endsWith('.tsx'));

        for (const file of sources) {
            expect(
                readFileSync(
                    path.resolve(__dirname, '../../../resources/js', file),
                    'utf8',
                ),
                file,
            ).not.toContain('placeholder:text-rz-faint');
        }
    });
});
