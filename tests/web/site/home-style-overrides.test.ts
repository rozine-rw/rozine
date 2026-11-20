import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vite-plus/test';

/**
 * The landing page keeps the design's inline styles; resources/css/site.css holds the few
 * rules that cannot be inline. These guard the ones we rely on.
 */
const css = readFileSync('resources/css/site.css', 'utf8').replace(/\s+/g, ' ');

describe('the landing page stylesheet', () => {
    it('keeps the header tabs inline with the waitlist button while they fit', () => {
        expect(css).toContain('.rz-site .rz-hdr-tabs { display: contents; }');
    });

    it('moves the tabs to a row of their own on phones so every tab stays reachable', () => {
        expect(css).toContain('@media (max-width: 640px)');
        expect(css).toContain(
            '.rz-site .rz-hdr { height: auto !important; flex-wrap: wrap;',
        );
        expect(css).toContain(
            '.rz-site .rz-hdr-tabs { order: 3; display: flex;',
        );
    });

    it('carries the design animations and pauses the money strip on hover', () => {
        expect(css).toContain('@keyframes rzb-roll');
        expect(css).toContain('@keyframes rzb-up');
        expect(css).toContain(
            '.rz-roll:hover { animation-play-state: paused !important; }',
        );
    });

    it('loads Inter up to the heaviest weight the design uses', () => {
        expect(css).toContain('font-weight: 800 900;');
    });
});
