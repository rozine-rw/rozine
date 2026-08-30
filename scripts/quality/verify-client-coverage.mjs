#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import {
    existsSync,
    mkdirSync,
    readFileSync,
    readdirSync,
    statSync,
    writeFileSync,
} from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import {
    evaluateClientCoveragePolicy,
    evaluateRiskManifest,
    evaluateSourceManifest,
    parseChangedHeadLines,
    sha256,
} from './client-coverage-policy.mjs';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const defaultRepoRoot = path.resolve(scriptDirectory, '../..');

const usage = `Usage:
  node scripts/quality/verify-client-coverage.mjs --prepare [SHA options]
  node scripts/quality/verify-client-coverage.mjs [options]

SHA options (CLI takes precedence over environment):
  --base-sha <sha>         CLIENT_COVERAGE_BASE_SHA
  --head-sha <sha>         CLIENT_COVERAGE_HEAD_SHA (or GITHUB_SHA)
  --target-sha <sha>       CLIENT_COVERAGE_TARGET_SHA

Artifact options:
  --coverage-summary <path> coverage/web/coverage-summary.json
  --coverage-final <path>   coverage/web/coverage-final.json
  --lcov <path>             coverage/web/lcov.info
  --provenance <path>       .coverage-provenance/client.json
  --source-manifest <path>  config/client-source-manifest.json
  --risk-manifest <path>    config/client-risk-manifest.json
  --evidence <path>         coverage/web/policy-evidence.json
  --repo-root <path>        Repository root
  --prepare                 Record immutable pre-test SHA/source provenance
  --help                    Show this help
`;

const parseArguments = (arguments_) => {
    const options = {
        repoRoot: defaultRepoRoot,
        coverageSummary: 'coverage/web/coverage-summary.json',
        coverageFinal: 'coverage/web/coverage-final.json',
        lcov: 'coverage/web/lcov.info',
        provenance: '.coverage-provenance/client.json',
        sourceManifest: 'config/client-source-manifest.json',
        riskManifest: 'config/client-risk-manifest.json',
        evidence: 'coverage/web/policy-evidence.json',
        baseSha: process.env.CLIENT_COVERAGE_BASE_SHA,
        headSha: process.env.CLIENT_COVERAGE_HEAD_SHA ?? process.env.GITHUB_SHA,
        targetSha: process.env.CLIENT_COVERAGE_TARGET_SHA,
        prepare: false,
    };
    const optionNames = {
        '--coverage-summary': 'coverageSummary',
        '--coverage-final': 'coverageFinal',
        '--lcov': 'lcov',
        '--provenance': 'provenance',
        '--source-manifest': 'sourceManifest',
        '--risk-manifest': 'riskManifest',
        '--evidence': 'evidence',
        '--base-sha': 'baseSha',
        '--head-sha': 'headSha',
        '--target-sha': 'targetSha',
        '--repo-root': 'repoRoot',
    };

    for (let index = 0; index < arguments_.length; index += 1) {
        const argument = arguments_[index];

        if (argument === '--help') {
            process.stdout.write(usage);
            process.exit(0);
        }

        if (argument === '--prepare') {
            options.prepare = true;
            continue;
        }

        const optionName = optionNames[argument];

        if (!optionName) {
            throw new Error(`Unknown option: ${argument}`);
        }

        const value = arguments_[index + 1];

        if (!value || value.startsWith('--')) {
            throw new Error(`Missing value for ${argument}.`);
        }

        options[optionName] = value;
        index += 1;
    }

    return options;
};

const resolveFromRepo = (repoRoot, targetPath) =>
    path.isAbsolute(targetPath)
        ? targetPath
        : path.resolve(repoRoot, targetPath);

const readJson = (targetPath) => {
    const text = readFileSync(targetPath, 'utf8');

    return { text, value: JSON.parse(text) };
};

/**
 * Git output for a large promotion — a release branch carrying many merges —
 * runs well past Node's 1 MB default and aborts the check with ENOBUFS.
 */
const GIT_MAX_BUFFER = 64 * 1024 * 1024;

export const filesAtCommit = (repoRoot, commitSha, scopes) =>
    execFileSync(
        'git',
        ['ls-tree', '-r', '--name-only', commitSha, '--', ...scopes],
        {
            cwd: repoRoot,
            encoding: 'utf8',
            maxBuffer: GIT_MAX_BUFFER,
        },
    )
        .split('\n')
        .filter(Boolean)
        .sort();

export const trackedClientSources = (repoRoot, headSha) =>
    filesAtCommit(repoRoot, headSha, ['resources/js']).filter(
        (entry) => entry.endsWith('.ts') || entry.endsWith('.tsx'),
    );

const resolveFullCommit = ({ repoRoot, label, value }) => {
    if (
        typeof value !== 'string' ||
        !/^[0-9a-f]{40}([0-9a-f]{24})?$/u.test(value)
    ) {
        throw new Error(
            `${label} must be supplied as a full lowercase immutable Git SHA.`,
        );
    }

    const resolved = execFileSync(
        'git',
        ['rev-parse', '--verify', `${value}^{commit}`],
        { cwd: repoRoot, encoding: 'utf8', maxBuffer: GIT_MAX_BUFFER },
    ).trim();

    if (resolved !== value) {
        throw new Error(
            `${label} does not resolve to the supplied full commit SHA.`,
        );
    }

    return resolved;
};

const resolveShaContract = (repoRoot, options) => {
    const baseSha = resolveFullCommit({
        repoRoot,
        label: 'base SHA',
        value: options.baseSha,
    });
    const headSha = resolveFullCommit({
        repoRoot,
        label: 'head SHA',
        value: options.headSha,
    });
    const targetSha = resolveFullCommit({
        repoRoot,
        label: 'target SHA',
        value: options.targetSha,
    });
    const checkoutHeadSha = execFileSync('git', ['rev-parse', 'HEAD'], {
        cwd: repoRoot,
        encoding: 'utf8',
        maxBuffer: GIT_MAX_BUFFER,
    }).trim();

    if (checkoutHeadSha !== headSha) {
        throw new Error(
            `Checked-out HEAD ${checkoutHeadSha} does not equal supplied head SHA ${headSha}.`,
        );
    }

    const mergeBaseSha = execFileSync(
        'git',
        ['merge-base', targetSha, headSha],
        { cwd: repoRoot, encoding: 'utf8', maxBuffer: GIT_MAX_BUFFER },
    ).trim();

    if (mergeBaseSha !== baseSha) {
        throw new Error(
            `Supplied base SHA ${baseSha} does not equal git merge-base(${targetSha}, ${headSha}) ${mergeBaseSha}.`,
        );
    }

    return { baseSha, headSha, targetSha, mergeBaseSha };
};

const sourceHashes = (repoRoot, paths) =>
    Object.fromEntries(
        paths.map((file) => [
            file,
            sha256(readFileSync(path.resolve(repoRoot, file))),
        ]),
    );

export const assertPathsMatchHead = (repoRoot, paths, label) => {
    const dirty = execFileSync(
        'git',
        ['status', '--porcelain=v1', '--untracked-files=all', '--', ...paths],
        { cwd: repoRoot, encoding: 'utf8', maxBuffer: GIT_MAX_BUFFER },
    ).trim();

    if (dirty.length > 0) {
        throw new Error(
            `${label} differs from the supplied HEAD; exact-SHA coverage cannot run.`,
        );
    }
};

const filesRecursively = (repoRoot, directory) => {
    const absoluteDirectory = path.resolve(repoRoot, directory);

    if (!existsSync(absoluteDirectory)) {
        return [];
    }

    const files = [];
    const visit = (currentDirectory) => {
        for (const entry of readdirSync(currentDirectory, {
            withFileTypes: true,
        })) {
            const absolutePath = path.join(currentDirectory, entry.name);

            if (entry.isDirectory()) {
                visit(absolutePath);
            } else if (entry.isFile()) {
                files.push(
                    path.relative(repoRoot, absolutePath).replaceAll('\\', '/'),
                );
            }
        }
    };

    visit(absoluteDirectory);

    return files;
};

export const governedToolchainPaths = (repoRoot, headSha) => {
    const staticPaths = [
        '.npmrc',
        '.nvmrc',
        'eslint.config.js',
        'package-lock.json',
        'package.json',
        'tsconfig.json',
        'tsconfig.test.json',
        'vite.config.ts',
        'vitest.config.ts',
        'config/client-risk-manifest.json',
        'config/client-source-manifest.json',
    ];

    return [
        ...new Set([
            ...staticPaths,
            ...filesAtCommit(repoRoot, headSha, [
                'scripts/quality',
                'tests/web',
            ]),
            ...filesRecursively(repoRoot, 'scripts/quality'),
            ...filesRecursively(repoRoot, 'tests/web'),
        ]),
    ].sort();
};

const changedAuthoredLines = ({
    repoRoot,
    baseSha,
    headSha,
    authoredPaths,
}) => {
    const diff = execFileSync(
        'git',
        [
            'diff',
            '--unified=0',
            '--no-color',
            '--find-renames',
            baseSha,
            headSha,
            '--',
            'resources/js',
        ],
        { cwd: repoRoot, encoding: 'utf8', maxBuffer: GIT_MAX_BUFFER },
    );
    const authoredSet = new Set(authoredPaths);

    return new Map(
        [...parseChangedHeadLines(diff)].filter(([file]) =>
            authoredSet.has(file),
        ),
    );
};

const loadManifestContract = ({ repoRoot, options, headSha }) => {
    const sourceManifestPath = resolveFromRepo(
        repoRoot,
        options.sourceManifest,
    );
    const riskManifestPath = resolveFromRepo(repoRoot, options.riskManifest);
    const sourceManifest = readJson(sourceManifestPath);
    const riskManifest = readJson(riskManifestPath);
    const trackedPaths = trackedClientSources(repoRoot, headSha);
    const sourceResult = evaluateSourceManifest({
        manifest: sourceManifest.value,
        trackedPaths,
    });
    const riskResult = evaluateRiskManifest({
        manifest: riskManifest.value,
        sourceManifest: sourceManifest.value,
        sourceManifestText: sourceManifest.text,
    });
    const errors = [...sourceResult.errors, ...riskResult.errors];

    if (errors.length > 0) {
        throw new Error(errors.join('\n'));
    }

    return {
        sourceManifest,
        riskManifest,
        trackedPaths,
        authoredPaths: sourceResult.authoredExecutablePaths,
    };
};

const prepare = ({ repoRoot, options, shaContract, manifestContract }) => {
    const toolchainPaths = governedToolchainPaths(
        repoRoot,
        shaContract.headSha,
    );
    assertPathsMatchHead(repoRoot, ['resources/js'], 'Governed client source');
    assertPathsMatchHead(
        repoRoot,
        [
            '.npmrc',
            '.nvmrc',
            'eslint.config.js',
            'package-lock.json',
            'package.json',
            'tsconfig.json',
            'tsconfig.test.json',
            'vite.config.ts',
            'vitest.config.ts',
            'config/client-risk-manifest.json',
            'config/client-source-manifest.json',
            'scripts/quality',
            'tests/web',
        ],
        'Coverage toolchain',
    );
    const provenancePath = resolveFromRepo(repoRoot, options.provenance);
    const provenance = {
        version: 1,
        baseSha: shaContract.baseSha,
        targetSha: shaContract.targetSha,
        headSha: shaContract.headSha,
        coverageSetSha256:
            manifestContract.sourceManifest.value.coverageSetSha256,
        preparedAtMs: Date.now(),
        sourceHashes: sourceHashes(repoRoot, manifestContract.trackedPaths),
        toolchainHashes: sourceHashes(repoRoot, toolchainPaths),
    };

    mkdirSync(path.dirname(provenancePath), { recursive: true });
    writeFileSync(provenancePath, `${JSON.stringify(provenance, null, 2)}\n`);
    process.stdout.write(
        `Prepared exact-SHA client coverage run at ${shaContract.headSha}.\n`,
    );
    process.stdout.write(
        `Provenance: ${path.relative(repoRoot, provenancePath)}\n`,
    );
};

const verify = ({ repoRoot, options, shaContract, manifestContract }) => {
    const toolchainPaths = governedToolchainPaths(
        repoRoot,
        shaContract.headSha,
    );
    assertPathsMatchHead(repoRoot, ['resources/js'], 'Governed client source');
    assertPathsMatchHead(
        repoRoot,
        [
            '.npmrc',
            '.nvmrc',
            'eslint.config.js',
            'package-lock.json',
            'package.json',
            'tsconfig.json',
            'tsconfig.test.json',
            'vite.config.ts',
            'vitest.config.ts',
            'config/client-risk-manifest.json',
            'config/client-source-manifest.json',
            'scripts/quality',
            'tests/web',
        ],
        'Coverage toolchain',
    );
    const artifactPaths = {
        summary: resolveFromRepo(repoRoot, options.coverageSummary),
        final: resolveFromRepo(repoRoot, options.coverageFinal),
        lcov: resolveFromRepo(repoRoot, options.lcov),
        provenance: resolveFromRepo(repoRoot, options.provenance),
    };
    const coverageSummary = readJson(artifactPaths.summary);
    const coverageFinal = readJson(artifactPaths.final);
    const lcovText = readFileSync(artifactPaths.lcov, 'utf8');
    const provenance = readJson(artifactPaths.provenance);
    const currentSourceHashes = sourceHashes(
        repoRoot,
        manifestContract.trackedPaths,
    );
    const currentToolchainHashes = sourceHashes(repoRoot, toolchainPaths);
    const oldestArtifactMtimeMs = Math.min(
        statSync(artifactPaths.summary).mtimeMs,
        statSync(artifactPaths.final).mtimeMs,
        statSync(artifactPaths.lcov).mtimeMs,
    );
    const result = evaluateClientCoveragePolicy({
        sourceManifest: manifestContract.sourceManifest.value,
        sourceManifestText: manifestContract.sourceManifest.text,
        riskManifest: manifestContract.riskManifest.value,
        coverageSummary: coverageSummary.value,
        coverageFinal: coverageFinal.value,
        lcovText,
        trackedPaths: manifestContract.trackedPaths,
        repoRoot,
        baseSha: shaContract.baseSha,
        targetSha: shaContract.targetSha,
        headSha: shaContract.headSha,
        expectedHeadSha: options.headSha,
        mergeBaseSha: shaContract.mergeBaseSha,
        changedLines: changedAuthoredLines({
            repoRoot,
            baseSha: shaContract.baseSha,
            headSha: shaContract.headSha,
            authoredPaths: manifestContract.authoredPaths,
        }),
        provenance: provenance.value,
        sourceHashes: currentSourceHashes,
        toolchainHashes: currentToolchainHashes,
        oldestArtifactMtimeMs,
    });
    const evidencePath = resolveFromRepo(repoRoot, options.evidence);
    result.evidence.artifacts = {
        coverageSummarySha256: sha256(coverageSummary.text),
        coverageFinalSha256: sha256(coverageFinal.text),
        lcovSha256: sha256(lcovText),
        provenanceSha256: sha256(provenance.text),
    };
    mkdirSync(path.dirname(evidencePath), { recursive: true });
    writeFileSync(
        evidencePath,
        `${JSON.stringify(result.evidence, null, 2)}\n`,
    );

    if (!result.passed) {
        for (const error of result.errors) {
            process.stderr.write(`CLIENT COVERAGE POLICY: ${error}\n`);
        }

        process.stderr.write(
            `Evidence: ${path.relative(repoRoot, evidencePath)}\n`,
        );
        process.exitCode = 1;

        return;
    }

    process.stdout.write(
        `D-67 client coverage policy passed for ${result.evidence.fileCount} files at ${shaContract.headSha}.\n`,
    );
    process.stdout.write(
        `Evidence: ${path.relative(repoRoot, evidencePath)}\n`,
    );
};

const main = () => {
    const options = parseArguments(process.argv.slice(2));
    const repoRoot = path.resolve(options.repoRoot);
    const shaContract = resolveShaContract(repoRoot, options);
    const manifestContract = loadManifestContract({
        repoRoot,
        options,
        headSha: shaContract.headSha,
    });

    if (options.prepare) {
        prepare({ repoRoot, options, shaContract, manifestContract });

        return;
    }

    verify({ repoRoot, options, shaContract, manifestContract });
};

if (path.resolve(process.argv[1] ?? '') === fileURLToPath(import.meta.url)) {
    try {
        main();
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);

        process.stderr.write(`CLIENT COVERAGE POLICY: ${message}\n`);
        process.exitCode = 1;
    }
}
