import { readFileSync } from 'node:fs';
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
});
