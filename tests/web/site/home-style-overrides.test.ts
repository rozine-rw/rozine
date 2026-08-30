import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

/**
 * resources/css/site.css is a mechanical extraction of the design export, so a
 * re-port would overwrite it. These guard the departures we make on purpose.
 */
const css = readFileSync('resources/css/site.css', 'utf8');

describe('the departures we make from the design export', () => {
    it('lets the how-it-works heading scroll away once the columns stack', () => {
        expect(css).toContain('Deliberate departures from the design export');
        expect(css.replace(/\s+/g, ' ')).toContain(
            '@media (max-width: 760px) { .rz-site .rz-how-grid > :first-child { position: static !important; } }',
        );
    });

    it('keeps the heading pinned while the grid still has two columns', () => {
        // the override is scoped to the same breakpoint that collapses the grid
        const collapse = css.indexOf('.rz-how-grid');

        expect(collapse).toBeGreaterThan(-1);
        expect(css).toContain('grid-template-columns: 1fr !important');
    });
});
