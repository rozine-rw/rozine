#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import process from 'node:process';
import { evaluateGeneratedClientStatus } from './client-coverage-policy.mjs';

const generatedRoots = [
    'resources/js/actions',
    'resources/js/routes',
    'resources/js/wayfinder',
];

try {
    const status = execFileSync(
        'git',
        [
            'status',
            '--porcelain=v1',
            '--untracked-files=all',
            '--',
            ...generatedRoots,
        ],
        {
            encoding: 'utf8',
            // a large promotion lists far more than Node's 1 MB default allows
            maxBuffer: 64 * 1024 * 1024,
        },
    );
    const errors = evaluateGeneratedClientStatus(status);

    if (errors.length > 0) {
        throw new Error(errors.join('\n'));
    }

    process.stdout.write('Wayfinder output matches the tested commit.\n');
} catch (error) {
    const message = error instanceof Error ? error.message : String(error);

    process.stderr.write(`GENERATED CLIENT POLICY: ${message}\n`);
    process.exitCode = 1;
}
