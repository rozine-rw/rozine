import { existsSync, readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

const ROOT = path.resolve(__dirname, '../../..');

/**
 * The previous mark: a wing beside the wordmark. The approved brand package in
 * docs/New Logo replaced it with the detached leading star.
 */
const RETIRED = [
    'rozine-wing-white.png',
    'rozine-wordmark-blue.png',
    'rozine-wordmark-white.png',
];

const sourcesUnder = (directory: string): string[] =>
    readdirSync(path.join(ROOT, directory), {
        recursive: true,
        withFileTypes: true,
    })
        .filter(
            (entry) => entry.isFile() && /\.(tsx?|php|css)$/u.test(entry.name),
        )
        .map((entry) => path.join(entry.parentPath, entry.name));

const pngSize = (file: string): { width: number; height: number } => {
    const header = readFileSync(path.join(ROOT, 'public/images', file));

    return { width: header.readUInt32BE(16), height: header.readUInt32BE(20) };
};

describe('Brand assets (approved star mark)', () => {
    it('ships no copy of the retired wing mark', () => {
        for (const file of RETIRED) {
            expect(existsSync(path.join(ROOT, 'public/images', file))).toBe(
                false,
            );
        }
    });

    it('never points a screen, email or stylesheet at the retired wing mark', () => {
        const offenders = [
            ...sourcesUnder('resources/js'),
            ...sourcesUnder('resources/views'),
            ...sourcesUnder('resources/css'),
        ].filter((file) => {
            const source = readFileSync(file, 'utf8');

            return RETIRED.some((asset) => source.includes(asset));
        });

        expect(offenders).toEqual([]);
    });

    it('ships the blue and white star lockups cut to the master frame', () => {
        for (const file of [
            'rozine-lockup-blue.png',
            'rozine-lockup-white.png',
        ]) {
            const { width, height } = pngSize(file);

            // Frame 83 crops to 5545 × 1903 around the star and wordmark.
            expect(width / height).toBeCloseTo(5545 / 1903, 1);
        }
    });
});
