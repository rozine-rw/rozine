import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';
import clientSourceManifest from './config/client-source-manifest.json';

const authoredExecutablePaths = clientSourceManifest.authoredExecutablePaths;

if (
    !Array.isArray(authoredExecutablePaths) ||
    authoredExecutablePaths.length === 0
) {
    throw new Error(
        'config/client-source-manifest.json must list authoredExecutablePaths.',
    );
}

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        clearMocks: true,
        environment: 'jsdom',
        execArgv: ['--experimental-require-module'],
        globals: true,
        include: ['tests/web/**/*.test.{ts,tsx}'],
        passWithNoTests: false,
        restoreMocks: true,
        setupFiles: ['./tests/web/setup.ts'],
        coverage: {
            provider: 'v8',
            include: authoredExecutablePaths,
            reporter: ['text', 'json', 'json-summary', 'lcov'],
            reportsDirectory: './coverage/web',
            reportOnFailure: true,
            thresholds: {
                branches: 0,
                functions: 0,
                lines: 0,
                statements: 0,
            },
        },
    },
});
