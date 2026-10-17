import { readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

const root = path.resolve(__dirname, '../../..');
const read = (file: string) => readFileSync(path.join(root, file), 'utf8');

/*
 * The plan's throttled-3G first-interaction budget (Phase 3): every preloaded font competes with
 * the app's own script for the first seconds of a slow connection.
 */
describe('Fonts on the critical path', () => {
    it('loads Inter once, as the variable face the product styles declare', () => {
        expect(read('vite.config.ts')).not.toContain("bunny('Inter'");
        expect(read('resources/css/rozine.css')).toMatch(
            /@font-face \{\s*font-family: 'Inter';\s*font-style: normal;\s*font-weight: 100 900;\s*font-display: swap;/u,
        );
    });

    it('fetches the starter screens’ Instrument Sans only where it is used', () => {
        expect(read('vite.config.ts')).toMatch(
            /bunny\('Instrument Sans', \{\s*weights: \[400, 500, 600\],\s*preload: false,\s*\}\)/u,
        );
    });
});
