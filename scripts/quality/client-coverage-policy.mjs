import { createHash } from 'node:crypto';
import path from 'node:path';

export const COVERAGE_METRICS = [
    'lines',
    'statements',
    'functions',
    'branches',
];

export const CHANGED_BRANCH_POLICY = Object.freeze({
    status: 'implemented',
    policySatisfied: true,
    mapping:
        'immutable Git merge base plus Istanbul source maps cross-checked against LCOV',
});

const POLICY_MINIMUMS = Object.freeze({
    critical: {
        global: {
            lines: 100,
            statements: 100,
            functions: 100,
            branches: 100,
        },
        perFile: {
            lines: 100,
            statements: 100,
            functions: 100,
            branches: 100,
        },
    },
    nonCritical: {
        global: {
            lines: 100,
            statements: 100,
            functions: 100,
            branches: 95,
        },
        perFile: {
            lines: 100,
            statements: 100,
            functions: 100,
            branches: 90,
        },
    },
});

const GENERATED_SOURCE_ROOTS = Object.freeze([
    'resources/js/actions/',
    'resources/js/routes/',
]);

const GENERATED_SOURCE_PATHS = Object.freeze([
    'resources/js/wayfinder/index.ts',
]);

const DECLARATION_SOURCE_ROOTS = Object.freeze(['resources/js/types/']);

const comparePaths = (left, right) => {
    if (left < right) {
        return -1;
    }

    if (left > right) {
        return 1;
    }

    return 0;
};

const sorted = (values) => [...values].sort(comparePaths);

const sameArray = (left, right) =>
    left.length === right.length &&
    left.every((value, index) => value === right[index]);

const difference = (left, right) => {
    const rightSet = new Set(right);

    return left.filter((value) => !rightSet.has(value));
};

const duplicates = (values) => {
    const seen = new Set();
    const repeated = new Set();

    for (const value of values) {
        if (seen.has(value)) {
            repeated.add(value);
        }

        seen.add(value);
    }

    return sorted(repeated);
};

const addArrayErrors = ({ errors, label, paths, sourceRoot }) => {
    if (!Array.isArray(paths)) {
        errors.push(`${label} must be an array.`);

        return [];
    }

    const validPaths = [];

    for (const entry of paths) {
        if (typeof entry !== 'string') {
            errors.push(`${label} entries must be strings.`);
            continue;
        }

        if (
            entry.startsWith('/') ||
            entry.includes('\\') ||
            entry.split('/').includes('..') ||
            entry.split('/').includes('.') ||
            !entry.startsWith(`${sourceRoot}/`) ||
            (!entry.endsWith('.ts') && !entry.endsWith('.tsx'))
        ) {
            errors.push(
                `${label} contains a non-canonical source path: ${entry}`,
            );
            continue;
        }

        validPaths.push(entry);
    }

    for (const entry of duplicates(validPaths)) {
        errors.push(`${label} contains a duplicate path: ${entry}`);
    }

    if (!sameArray(validPaths, sorted(validPaths))) {
        errors.push(`${label} must be sorted by repository-relative path.`);
    }

    return validPaths;
};

const addGovernanceMetadataErrors = ({
    errors,
    label,
    metadata,
    requiredFields,
}) => {
    if (!metadata || typeof metadata !== 'object' || Array.isArray(metadata)) {
        errors.push(`${label} governance metadata must be an object.`);

        return;
    }

    for (const field of requiredFields) {
        if (
            typeof metadata[field] !== 'string' ||
            metadata[field].trim().length === 0
        ) {
            errors.push(
                `${label}.${field} must be a non-empty governance value.`,
            );
        }
    }
};

const assertPartition = ({ errors, actual, expected, label }) => {
    const missing = difference(expected, actual);
    const unexpected = difference(actual, expected);

    if (missing.length > 0) {
        errors.push(`${label} is missing: ${missing.join(', ')}`);
    }

    if (unexpected.length > 0) {
        errors.push(
            `${label} contains unexpected paths: ${unexpected.join(', ')}`,
        );
    }
};

export const sha256 = (content) =>
    createHash('sha256').update(content).digest('hex');

export const coverageSetSha256 = (paths) =>
    sha256(`${sorted(paths).join('\n')}\n`);

export const evaluateGeneratedClientStatus = (statusText) => {
    if (typeof statusText !== 'string') {
        return ['Generated-client Git status evidence must be text.'];
    }

    const drift = statusText.trim();

    return drift.length === 0
        ? []
        : [`Wayfinder output differs from the tested commit:\n${drift}`];
};

export const evaluateSourceManifest = ({ manifest, trackedPaths }) => {
    const errors = [];

    if (!manifest || typeof manifest !== 'object' || Array.isArray(manifest)) {
        return {
            errors: ['Client source manifest must be a JSON object.'],
            authoredExecutablePaths: [],
        };
    }

    const sourceRoot = manifest.sourceRoot;

    if (manifest.version !== 1) {
        errors.push('Client source manifest version must be 1.');
    }

    if (typeof sourceRoot !== 'string' || sourceRoot.length === 0) {
        errors.push(
            'Client source manifest sourceRoot must be a non-empty string.',
        );
    }

    const usableRoot =
        typeof sourceRoot === 'string' && sourceRoot.length > 0
            ? sourceRoot
            : 'resources/js';
    const generatedPaths = addArrayErrors({
        errors,
        label: 'generatedPaths',
        paths: manifest.generatedPaths,
        sourceRoot: usableRoot,
    });
    const declarationOnlyPaths = addArrayErrors({
        errors,
        label: 'declarationOnlyPaths',
        paths: manifest.declarationOnlyPaths,
        sourceRoot: usableRoot,
    });
    const authoredExecutablePaths = addArrayErrors({
        errors,
        label: 'authoredExecutablePaths',
        paths: manifest.authoredExecutablePaths,
        sourceRoot: usableRoot,
    });

    for (const entry of generatedPaths) {
        if (
            !GENERATED_SOURCE_PATHS.includes(entry) &&
            !GENERATED_SOURCE_ROOTS.some((root) => entry.startsWith(root))
        ) {
            errors.push(
                `generatedPaths cannot exclude a path outside the verified Wayfinder roots: ${entry}`,
            );
        }
    }

    for (const entry of declarationOnlyPaths) {
        if (!DECLARATION_SOURCE_ROOTS.some((root) => entry.startsWith(root))) {
            errors.push(
                `declarationOnlyPaths cannot exclude a path outside the reviewed type roots: ${entry}`,
            );
        }
    }

    for (const group of ['generatedPaths', 'declarationOnlyPaths']) {
        addGovernanceMetadataErrors({
            errors,
            label: `exclusionMetadata.${group}`,
            metadata: manifest.exclusionMetadata?.[group],
            requiredFields: [
                'provenance',
                'generator',
                'owner',
                'rationale',
                'approver',
                'reviewTrigger',
            ],
        });
    }

    const inventory = [
        ...generatedPaths,
        ...declarationOnlyPaths,
        ...authoredExecutablePaths,
    ];

    for (const entry of duplicates(inventory)) {
        errors.push(`Source classifications overlap at: ${entry}`);
    }

    const canonicalTrackedPaths = sorted(trackedPaths ?? inventory);

    assertPartition({
        errors,
        actual: sorted(inventory),
        expected: canonicalTrackedPaths,
        label: 'Client source manifest inventory',
    });

    const expectedCounts = {
        totalTrackedSources: canonicalTrackedPaths.length,
        generated: generatedPaths.length,
        declarationOnly: declarationOnlyPaths.length,
        authoredExecutable: authoredExecutablePaths.length,
    };

    for (const [name, expected] of Object.entries(expectedCounts)) {
        if (manifest.counts?.[name] !== expected) {
            errors.push(
                `Client source manifest counts.${name} must be ${expected}; received ${String(manifest.counts?.[name])}.`,
            );
        }
    }

    const expectedCoverageSetSha256 = coverageSetSha256(
        authoredExecutablePaths,
    );

    if (manifest.coverageSetSha256 !== expectedCoverageSetSha256) {
        errors.push(
            `Client source manifest coverageSetSha256 must be ${expectedCoverageSetSha256}; received ${String(manifest.coverageSetSha256)}.`,
        );
    }

    return {
        errors,
        generatedPaths,
        declarationOnlyPaths,
        authoredExecutablePaths,
        coverageSetSha256: expectedCoverageSetSha256,
    };
};

const addThresholdErrors = (errors, thresholds) => {
    for (const risk of ['critical', 'nonCritical']) {
        for (const scope of ['global', 'perFile']) {
            for (const metric of COVERAGE_METRICS) {
                const threshold = thresholds?.[risk]?.[scope]?.[metric];
                const minimum = POLICY_MINIMUMS[risk][scope][metric];

                if (
                    typeof threshold !== 'number' ||
                    !Number.isFinite(threshold) ||
                    threshold < minimum ||
                    threshold > 100
                ) {
                    errors.push(
                        `thresholds.${risk}.${scope}.${metric} must be between ${minimum} and 100.`,
                    );
                }
            }
        }
    }
};

export const evaluateRiskManifest = ({
    manifest,
    sourceManifest,
    sourceManifestText,
}) => {
    const errors = [];

    if (!manifest || typeof manifest !== 'object' || Array.isArray(manifest)) {
        return {
            errors: ['Client risk manifest must be a JSON object.'],
            criticalPaths: [],
            nonCriticalPaths: [],
        };
    }

    if (manifest.version !== 1) {
        errors.push('Client risk manifest version must be 1.');
    }

    if (manifest.defaultClassification !== 'critical') {
        errors.push(
            'Client risk manifest must default unclassified files to critical.',
        );
    }

    if (typeof sourceManifestText !== 'string') {
        errors.push(
            'Raw client source manifest text is required for SHA-256 verification.',
        );
    } else {
        const expectedSourceManifestSha256 = sha256(sourceManifestText);

        if (manifest.sourceManifestSha256 !== expectedSourceManifestSha256) {
            errors.push(
                `Client risk manifest sourceManifestSha256 must be ${expectedSourceManifestSha256}; received ${String(manifest.sourceManifestSha256)}.`,
            );
        }
    }

    if (manifest.coverageSetSha256 !== sourceManifest?.coverageSetSha256) {
        errors.push(
            'Client risk manifest coverageSetSha256 does not match the source manifest.',
        );
    }

    addThresholdErrors(errors, manifest.thresholds);

    const sourceRoot = sourceManifest?.sourceRoot ?? 'resources/js';
    const criticalPaths = addArrayErrors({
        errors,
        label: 'criticalPaths',
        paths: manifest.criticalPaths,
        sourceRoot,
    });
    const nonCriticalPaths = addArrayErrors({
        errors,
        label: 'nonCriticalPaths',
        paths: manifest.nonCriticalPaths,
        sourceRoot,
    });
    const nonCriticalApprovals = manifest.nonCriticalApprovals;

    if (
        !nonCriticalApprovals ||
        typeof nonCriticalApprovals !== 'object' ||
        Array.isArray(nonCriticalApprovals)
    ) {
        errors.push(
            'nonCriticalApprovals must be an object keyed by exact path.',
        );
    } else {
        assertPartition({
            errors,
            actual: sorted(Object.keys(nonCriticalApprovals)),
            expected: sorted(nonCriticalPaths),
            label: 'Non-critical approval records',
        });

        for (const entry of nonCriticalPaths) {
            addGovernanceMetadataErrors({
                errors,
                label: `nonCriticalApprovals.${entry}`,
                metadata: nonCriticalApprovals[entry],
                requiredFields: [
                    'rationale',
                    'engineeringApprover',
                    'domainApprover',
                    'evidence',
                    'approvedAt',
                    'reviewTrigger',
                ],
            });
        }
    }

    for (const group of ['criticalPaths', 'nonCriticalPaths']) {
        addGovernanceMetadataErrors({
            errors,
            label: `classificationMetadata.${group}`,
            metadata: manifest.classificationMetadata?.[group],
            requiredFields: ['owner', 'approver', 'reviewTrigger'],
        });
    }

    const classifiedPaths = [...criticalPaths, ...nonCriticalPaths];

    for (const entry of duplicates(classifiedPaths)) {
        errors.push(`Risk classifications overlap at: ${entry}`);
    }

    assertPartition({
        errors,
        actual: sorted(classifiedPaths),
        expected: sorted(sourceManifest?.authoredExecutablePaths ?? []),
        label: 'Client risk manifest classification',
    });

    return {
        errors,
        criticalPaths,
        nonCriticalPaths,
        thresholds: manifest.thresholds,
    };
};

const normalizeCoveragePath = ({ coveragePath, repoRoot, errors }) => {
    const normalizedSeparators = coveragePath.replaceAll('\\', '/');
    const normalizedRoot = path.resolve(repoRoot).replaceAll('\\', '/');
    let relativePath = normalizedSeparators;

    if (path.isAbsolute(coveragePath)) {
        const absolutePath = path.resolve(coveragePath).replaceAll('\\', '/');

        if (
            absolutePath !== normalizedRoot &&
            !absolutePath.startsWith(`${normalizedRoot}/`)
        ) {
            errors.push(
                `Coverage report contains a path outside the repository: ${coveragePath}`,
            );

            return null;
        }

        relativePath = path
            .relative(normalizedRoot, absolutePath)
            .replaceAll('\\', '/');
    }

    if (
        relativePath.startsWith('/') ||
        relativePath.split('/').includes('..') ||
        relativePath.split('/').includes('.')
    ) {
        errors.push(
            `Coverage report contains a non-canonical path: ${coveragePath}`,
        );

        return null;
    }

    return relativePath;
};

const emptyMetrics = () =>
    Object.fromEntries(
        COVERAGE_METRICS.map((metric) => [
            metric,
            { total: 0, covered: 0, skipped: 0 },
        ]),
    );

const addMetrics = (target, source) => {
    for (const metric of COVERAGE_METRICS) {
        target[metric].total += source[metric].total;
        target[metric].covered += source[metric].covered;
        target[metric].skipped += source[metric].skipped;
    }
};

const percentage = ({ covered, total }) => {
    if (total === 0) {
        return 100;
    }

    return Math.floor((covered * 10000) / total) / 100;
};

const readMetric = ({ errors, metric, owner, summary }) => {
    const value = summary?.[metric];

    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        errors.push(`${owner}.${metric} is missing.`);

        return { total: 0, covered: 0, skipped: 0 };
    }

    const result = {};

    for (const name of ['total', 'covered', 'skipped']) {
        if (!Number.isSafeInteger(value[name]) || value[name] < 0) {
            errors.push(
                `${owner}.${metric}.${name} must be a non-negative integer.`,
            );
            result[name] = 0;
        } else {
            result[name] = value[name];
        }
    }

    if (result.covered > result.total) {
        errors.push(`${owner}.${metric}.covered cannot exceed total.`);
    }

    if (result.skipped !== 0) {
        errors.push(
            `${owner}.${metric}.skipped must be zero; skipped coverage is not accepted.`,
        );
    }

    if (typeof value.pct === 'number') {
        const expectedPercentage = percentage(result);

        if (value.pct !== expectedPercentage) {
            errors.push(
                `${owner}.${metric}.pct must be ${expectedPercentage}; received ${value.pct}.`,
            );
        }
    }

    return result;
};

const readMetrics = ({ errors, owner, summary }) =>
    Object.fromEntries(
        COVERAGE_METRICS.map((metric) => [
            metric,
            readMetric({ errors, metric, owner, summary }),
        ]),
    );

const thresholdSatisfied = (metric, threshold) => {
    if (metric.total === 0) {
        return true;
    }

    return metric.covered * 100 >= metric.total * threshold;
};

const addCoverageThresholdErrors = ({ errors, metrics, owner, thresholds }) => {
    for (const metric of COVERAGE_METRICS) {
        const threshold = thresholds[metric];
        const value = metrics[metric];

        if (!thresholdSatisfied(value, threshold)) {
            errors.push(
                `${owner}.${metric} coverage is ${percentage(value)}%; required ${threshold}%.`,
            );
        }
    }
};

const metricsEvidence = (metrics) =>
    Object.fromEntries(
        COVERAGE_METRICS.map((metric) => [
            metric,
            {
                ...metrics[metric],
                pct: percentage(metrics[metric]),
            },
        ]),
    );

const locationLines = (location) => {
    const start = location?.start?.line;

    if (!Number.isSafeInteger(start) || start < 1) {
        return null;
    }

    const candidateEnd = location?.end?.line;
    const end =
        Number.isSafeInteger(candidateEnd) && candidateEnd >= start
            ? candidateEnd
            : start;

    return { start, end };
};

const lineIntersects = (changedLines, location) => {
    const lines = locationLines(location);

    if (lines === null) {
        return false;
    }

    for (let line = lines.start; line <= lines.end; line += 1) {
        if (changedLines.has(line)) {
            return true;
        }
    }

    return false;
};

export const parseLcov = (lcovText) => {
    const errors = [];
    const files = new Map();
    let current = null;

    if (typeof lcovText !== 'string') {
        return { errors: ['LCOV evidence must be text.'], files };
    }

    for (const rawLine of lcovText.split(/\r?\n/u)) {
        const line = rawLine.trim();

        if (line.startsWith('SF:')) {
            if (current !== null) {
                errors.push(
                    `LCOV record for ${current.path} is missing end_of_record.`,
                );
            }

            const file = line.slice(3);
            current = { path: file, lines: new Map(), branches: [] };
            continue;
        }

        if (line === 'end_of_record') {
            if (current === null) {
                errors.push(
                    'LCOV contains end_of_record without an SF record.',
                );
                continue;
            }

            if (files.has(current.path)) {
                errors.push(
                    `LCOV contains a duplicate SF record: ${current.path}`,
                );
            } else {
                files.set(current.path, current);
            }

            current = null;
            continue;
        }

        if (current === null) {
            continue;
        }

        if (line.startsWith('DA:')) {
            const [lineNumberText, hitsText] = line.slice(3).split(',');
            const lineNumber = Number(lineNumberText);
            const hits = Number(hitsText);

            if (
                !Number.isSafeInteger(lineNumber) ||
                lineNumber < 1 ||
                !Number.isSafeInteger(hits) ||
                hits < 0
            ) {
                errors.push(
                    `LCOV contains an invalid DA record in ${current.path}: ${line}`,
                );
            } else if (current.lines.has(lineNumber)) {
                errors.push(
                    `LCOV contains duplicate DA line ${lineNumber} in ${current.path}.`,
                );
            } else {
                current.lines.set(lineNumber, hits);
            }
        }

        if (line.startsWith('BRDA:')) {
            const [lineText, blockText, branchText, hitsText] = line
                .slice(5)
                .split(',');
            const lineNumber = Number(lineText);
            const block = Number(blockText);
            const branch = Number(branchText);
            const hits = hitsText === '-' ? 0 : Number(hitsText);

            if (
                !Number.isSafeInteger(lineNumber) ||
                lineNumber < 1 ||
                !Number.isSafeInteger(block) ||
                block < 0 ||
                !Number.isSafeInteger(branch) ||
                branch < 0 ||
                !Number.isSafeInteger(hits) ||
                hits < 0
            ) {
                errors.push(
                    `LCOV contains an invalid BRDA record in ${current.path}: ${line}`,
                );
            } else {
                current.branches.push({
                    line: lineNumber,
                    block,
                    branch,
                    hits,
                });
            }
        }
    }

    if (current !== null) {
        errors.push(
            `LCOV record for ${current.path} is missing end_of_record.`,
        );
    }

    return { errors, files };
};

const finalCoverageMetrics = ({ errors, file, coverage }) => {
    const statementMap = coverage?.statementMap;
    const statements = coverage?.s;
    const functions = coverage?.f;
    const branches = coverage?.b;

    if (
        !statementMap ||
        !statements ||
        !coverage?.fnMap ||
        !functions ||
        !coverage?.branchMap ||
        !branches
    ) {
        errors.push(
            `coverage-final.json has an incomplete Istanbul map for ${file}.`,
        );

        return null;
    }

    const lineHits = new Map();
    const statementHits = [];

    for (const [identifier, location] of Object.entries(statementMap)) {
        const lines = locationLines(location);
        const hits = statements[identifier];

        if (lines === null || !Number.isSafeInteger(hits) || hits < 0) {
            errors.push(
                `coverage-final.json has an unmappable statement ${identifier} in ${file}.`,
            );
            continue;
        }

        statementHits.push(hits);
        lineHits.set(
            lines.start,
            Math.max(lineHits.get(lines.start) ?? 0, hits),
        );
    }

    const functionHits = Object.values(functions);
    const branchHits = [];
    const branchRecords = [];

    for (const [identifier, branch] of Object.entries(coverage.branchMap)) {
        const lines = locationLines(branch.loc);
        const locations = branch.locations;
        const hits = branches[identifier];

        if (
            lines === null ||
            !Array.isArray(locations) ||
            !Array.isArray(hits) ||
            locations.length !== hits.length ||
            hits.some((hit) => !Number.isSafeInteger(hit) || hit < 0)
        ) {
            errors.push(
                `coverage-final.json has an unmappable branch ${identifier} in ${file}.`,
            );
            continue;
        }

        branchHits.push(...hits);
        branchRecords.push({
            identifier,
            line: lines.start,
            location: branch.loc,
            locations,
            type: branch.type,
            hits,
        });
    }

    if (functionHits.some((hit) => !Number.isSafeInteger(hit) || hit < 0)) {
        errors.push(
            `coverage-final.json has invalid function counters in ${file}.`,
        );
    }

    const counterMetric = (hits) => ({
        total: hits.length,
        covered: hits.filter((hit) => hit > 0).length,
        skipped: 0,
    });

    return {
        metrics: {
            lines: counterMetric([...lineHits.values()]),
            statements: counterMetric(statementHits),
            functions: counterMetric(functionHits),
            branches: counterMetric(branchHits),
        },
        lineHits,
        branchRecords,
    };
};

export const evaluateCoverageArtifacts = ({
    coverageFinal,
    lcovText,
    coverageSummary,
    expectedPaths,
    repoRoot,
}) => {
    const errors = [];
    const finalFiles = new Map();

    if (
        !coverageFinal ||
        typeof coverageFinal !== 'object' ||
        Array.isArray(coverageFinal)
    ) {
        return {
            errors: ['coverage-final.json must be a JSON object.'],
            files: finalFiles,
        };
    }

    for (const [coveragePath, coverage] of Object.entries(coverageFinal)) {
        const normalizedPath = normalizeCoveragePath({
            coveragePath,
            repoRoot,
            errors,
        });

        if (normalizedPath === null) {
            continue;
        }

        const declaredPath = normalizeCoveragePath({
            coveragePath: coverage?.path ?? '',
            repoRoot,
            errors,
        });

        if (declaredPath !== normalizedPath) {
            errors.push(
                `coverage-final.json path mismatch for ${normalizedPath}.`,
            );
        }

        if (finalFiles.has(normalizedPath)) {
            errors.push(
                `coverage-final.json contains a duplicate path: ${normalizedPath}`,
            );
            continue;
        }

        const finalMetrics = finalCoverageMetrics({
            errors,
            file: normalizedPath,
            coverage,
        });

        if (finalMetrics !== null) {
            finalFiles.set(normalizedPath, finalMetrics);
        }
    }

    assertPartition({
        errors,
        actual: sorted(finalFiles.keys()),
        expected: sorted(expectedPaths),
        label: 'coverage-final.json file set',
    });

    const parsedLcov = parseLcov(lcovText);
    errors.push(...parsedLcov.errors);
    const lcovFiles = new Map();

    for (const [lcovPath, lcov] of parsedLcov.files) {
        const normalizedPath = normalizeCoveragePath({
            coveragePath: lcovPath,
            repoRoot,
            errors,
        });

        if (normalizedPath !== null) {
            lcovFiles.set(normalizedPath, lcov);
        }
    }

    assertPartition({
        errors,
        actual: sorted(lcovFiles.keys()),
        expected: sorted(expectedPaths),
        label: 'LCOV file set',
    });

    for (const file of expectedPaths) {
        const finalFile = finalFiles.get(file);
        const lcovFile = lcovFiles.get(file);
        const summary =
            coverageSummary?.[file] ??
            coverageSummary?.[path.resolve(repoRoot, file)];

        if (!finalFile || !lcovFile || !summary) {
            continue;
        }

        const summaryMetrics = readMetrics({ errors, owner: file, summary });

        for (const metric of COVERAGE_METRICS) {
            for (const field of ['total', 'covered', 'skipped']) {
                if (
                    summaryMetrics[metric][field] !==
                    finalFile.metrics[metric][field]
                ) {
                    errors.push(
                        `${file}.${metric}.${field} differs between coverage-summary.json and coverage-final.json.`,
                    );
                }
            }
        }

        const finalLineEntries = [...finalFile.lineHits.entries()].sort(
            ([left], [right]) => left - right,
        );
        const lcovLineEntries = [...lcovFile.lines.entries()].sort(
            ([left], [right]) => left - right,
        );

        if (
            JSON.stringify(finalLineEntries) !== JSON.stringify(lcovLineEntries)
        ) {
            errors.push(
                `${file} line source-map counters do not match LCOV DA evidence.`,
            );
        }

        const finalBranches = finalFile.branchRecords.flatMap((branch, block) =>
            branch.hits.map((hits, branchIndex) => ({
                line: branch.line,
                block,
                branch: branchIndex,
                hits,
            })),
        );

        if (
            JSON.stringify(finalBranches) !== JSON.stringify(lcovFile.branches)
        ) {
            errors.push(
                `${file} branch source-map counters do not match LCOV BRDA evidence.`,
            );
        }
    }

    return { errors, files: finalFiles };
};

export const parseChangedHeadLines = (diffText) => {
    const changedLines = new Map();
    let currentFile = null;

    if (typeof diffText !== 'string') {
        throw new TypeError('Git diff evidence must be text.');
    }

    for (const line of diffText.split(/\r?\n/u)) {
        if (line.startsWith('+++ ')) {
            const target = line.slice(4);
            currentFile =
                target === '/dev/null' ? null : target.replace(/^b\//u, '');
            continue;
        }

        if (!line.startsWith('@@ ') || currentFile === null) {
            continue;
        }

        const match = line.match(/\+(\d+)(?:,(\d+))?\s/u);

        if (!match) {
            throw new Error(`Unable to map changed hunk: ${line}`);
        }

        const start = Number(match[1]);
        const count = match[2] === undefined ? 1 : Number(match[2]);

        if (count === 0) {
            continue;
        }

        const lines = changedLines.get(currentFile) ?? new Set();

        for (let offset = 0; offset < count; offset += 1) {
            lines.add(start + offset);
        }

        changedLines.set(currentFile, lines);
    }

    return changedLines;
};

export const evaluateChangedBranchCoverage = ({
    artifactFiles,
    changedLines,
    baseSha,
    headSha,
    mergeBaseSha,
}) => {
    const errors = [];
    const changedBranches = [];

    if (!validCommitSha(baseSha)) {
        errors.push('baseSha must be a lowercase 40- or 64-character Git SHA.');
    }

    if (mergeBaseSha !== baseSha) {
        errors.push(
            `Recorded base SHA ${baseSha} is not the immutable merge base ${mergeBaseSha}.`,
        );
    }

    if (!validCommitSha(headSha)) {
        errors.push(
            'Changed-branch headSha must be a lowercase 40- or 64-character Git SHA.',
        );
    }

    for (const [file, lines] of changedLines) {
        const artifact = artifactFiles.get(file);

        if (!artifact) {
            errors.push(
                `Changed file has no source-mapped coverage artifact: ${file}`,
            );
            continue;
        }

        for (const branch of artifact.branchRecords) {
            if (!lineIntersects(lines, branch.location)) {
                continue;
            }

            const uncoveredIndexes = branch.hits
                .map((hits, index) => (hits > 0 ? null : index))
                .filter((index) => index !== null);
            const record = {
                file,
                branchId: branch.identifier,
                type: branch.type,
                startLine: branch.location.start.line,
                endLine: branch.location.end.line,
                hits: branch.hits,
            };
            changedBranches.push(record);

            if (uncoveredIndexes.length > 0) {
                errors.push(
                    `${file} changed branch ${branch.identifier} has uncovered outcomes: ${uncoveredIndexes.join(', ')}.`,
                );
            }
        }
    }

    return {
        errors,
        evidence: {
            status: errors.length === 0 ? 'passed' : 'failed',
            policySatisfied: errors.length === 0,
            baseSha,
            headSha,
            mergeBaseSha,
            changedFileCount: changedLines.size,
            changedLineCount: [...changedLines.values()].reduce(
                (total, lines) => total + lines.size,
                0,
            ),
            changedBranchCount: changedBranches.length,
            changedBranches,
        },
    };
};

const validCommitSha = (value) => /^[0-9a-f]{40}([0-9a-f]{24})?$/.test(value);

export const evaluateCoverageProvenance = ({
    provenance,
    baseSha,
    targetSha,
    headSha,
    coverageSetSha256: expectedCoverageSetSha256,
    sourceHashes,
    toolchainHashes,
    oldestArtifactMtimeMs,
}) => {
    const errors = [];

    if (
        !provenance ||
        typeof provenance !== 'object' ||
        Array.isArray(provenance)
    ) {
        return { errors: ['Coverage run provenance must be a JSON object.'] };
    }

    if (provenance.version !== 1) {
        errors.push('Coverage run provenance version must be 1.');
    }

    for (const [name, expected] of Object.entries({
        baseSha,
        targetSha,
        headSha,
        coverageSetSha256: expectedCoverageSetSha256,
    })) {
        if (provenance[name] !== expected) {
            errors.push(
                `Coverage run provenance ${name} must be ${String(expected)}; received ${String(provenance[name])}.`,
            );
        }
    }

    if (
        !Number.isSafeInteger(provenance.preparedAtMs) ||
        provenance.preparedAtMs < 1
    ) {
        errors.push(
            'Coverage run provenance preparedAtMs must be a positive integer.',
        );
    } else if (
        !Number.isFinite(oldestArtifactMtimeMs) ||
        oldestArtifactMtimeMs < provenance.preparedAtMs
    ) {
        errors.push(
            'Coverage artifacts are stale relative to the prepared run provenance.',
        );
    }

    if (
        !provenance.sourceHashes ||
        typeof provenance.sourceHashes !== 'object' ||
        Array.isArray(provenance.sourceHashes)
    ) {
        errors.push('Coverage run provenance sourceHashes must be an object.');
    } else {
        const expectedPaths = sorted(Object.keys(sourceHashes));
        const actualPaths = sorted(Object.keys(provenance.sourceHashes));

        assertPartition({
            errors,
            actual: actualPaths,
            expected: expectedPaths,
            label: 'Coverage run provenance source hash set',
        });

        for (const file of expectedPaths) {
            if (provenance.sourceHashes[file] !== sourceHashes[file]) {
                errors.push(
                    `Coverage run provenance has a stale source hash for ${file}.`,
                );
            }
        }
    }

    if (
        !provenance.toolchainHashes ||
        typeof provenance.toolchainHashes !== 'object' ||
        Array.isArray(provenance.toolchainHashes)
    ) {
        errors.push(
            'Coverage run provenance toolchainHashes must be an object.',
        );
    } else {
        const expectedPaths = sorted(Object.keys(toolchainHashes));
        const actualPaths = sorted(Object.keys(provenance.toolchainHashes));

        assertPartition({
            errors,
            actual: actualPaths,
            expected: expectedPaths,
            label: 'Coverage run provenance toolchain hash set',
        });

        for (const file of expectedPaths) {
            if (provenance.toolchainHashes[file] !== toolchainHashes[file]) {
                errors.push(
                    `Coverage run provenance has a stale toolchain hash for ${file}.`,
                );
            }
        }
    }

    return { errors };
};

export const evaluateClientCoveragePolicy = ({
    sourceManifest,
    sourceManifestText,
    riskManifest,
    coverageSummary,
    coverageFinal,
    lcovText,
    trackedPaths,
    repoRoot,
    baseSha,
    targetSha,
    headSha,
    expectedHeadSha,
    mergeBaseSha,
    changedLines = new Map(),
    provenance,
    sourceHashes = {},
    toolchainHashes = {},
    oldestArtifactMtimeMs,
}) => {
    const sourceResult = evaluateSourceManifest({
        manifest: sourceManifest,
        trackedPaths,
    });
    const riskResult = evaluateRiskManifest({
        manifest: riskManifest,
        sourceManifest,
        sourceManifestText,
    });
    const errors = [...sourceResult.errors, ...riskResult.errors];

    if (typeof headSha !== 'string' || !validCommitSha(headSha)) {
        errors.push('headSha must be a lowercase 40- or 64-character Git SHA.');
    }

    if (
        typeof expectedHeadSha !== 'string' ||
        !validCommitSha(expectedHeadSha)
    ) {
        errors.push(
            'expectedHeadSha must be a lowercase 40- or 64-character Git SHA.',
        );
    } else if (headSha !== expectedHeadSha) {
        errors.push(
            `Coverage SHA mismatch: expected ${expectedHeadSha}; received ${headSha}.`,
        );
    }

    if (typeof targetSha !== 'string' || !validCommitSha(targetSha)) {
        errors.push(
            'targetSha must be a lowercase 40- or 64-character Git SHA.',
        );
    }

    if (typeof mergeBaseSha !== 'string' || !validCommitSha(mergeBaseSha)) {
        errors.push(
            'mergeBaseSha must be a lowercase 40- or 64-character Git SHA.',
        );
    }

    if (
        !coverageSummary ||
        typeof coverageSummary !== 'object' ||
        Array.isArray(coverageSummary)
    ) {
        errors.push('Coverage summary must be a JSON object.');
    }

    const fileSummaries = new Map();

    for (const [coveragePath, summary] of Object.entries(
        coverageSummary ?? {},
    )) {
        if (coveragePath === 'total') {
            continue;
        }

        const normalizedPath = normalizeCoveragePath({
            coveragePath,
            repoRoot,
            errors,
        });

        if (normalizedPath === null) {
            continue;
        }

        if (fileSummaries.has(normalizedPath)) {
            errors.push(
                `Coverage report contains a duplicate normalized path: ${normalizedPath}`,
            );
            continue;
        }

        fileSummaries.set(
            normalizedPath,
            readMetrics({
                errors,
                owner: normalizedPath,
                summary,
            }),
        );
    }

    const coveredPaths = sorted(fileSummaries.keys());
    const expectedPaths = sorted(sourceResult.authoredExecutablePaths);

    assertPartition({
        errors,
        actual: coveredPaths,
        expected: expectedPaths,
        label: 'Coverage report file set',
    });

    const actualCoverageSetSha256 = coverageSetSha256(coveredPaths);

    if (actualCoverageSetSha256 !== sourceManifest?.coverageSetSha256) {
        errors.push(
            `Coverage report set SHA-256 is ${actualCoverageSetSha256}; expected ${String(sourceManifest?.coverageSetSha256)}.`,
        );
    }

    const allMetrics = emptyMetrics();
    const criticalMetrics = emptyMetrics();
    const nonCriticalMetrics = emptyMetrics();
    const criticalSet = new Set(riskResult.criticalPaths);
    const nonCriticalSet = new Set(riskResult.nonCriticalPaths);

    for (const [file, metrics] of fileSummaries) {
        addMetrics(allMetrics, metrics);

        if (criticalSet.has(file)) {
            addMetrics(criticalMetrics, metrics);
            addCoverageThresholdErrors({
                errors,
                metrics,
                owner: file,
                thresholds: riskResult.thresholds?.critical?.perFile ?? {},
            });
        } else if (nonCriticalSet.has(file)) {
            addMetrics(nonCriticalMetrics, metrics);
            addCoverageThresholdErrors({
                errors,
                metrics,
                owner: file,
                thresholds: riskResult.thresholds?.nonCritical?.perFile ?? {},
            });
        }
    }

    if (riskResult.thresholds?.critical?.global) {
        addCoverageThresholdErrors({
            errors,
            metrics: criticalMetrics,
            owner: 'critical.global',
            thresholds: riskResult.thresholds.critical.global,
        });
    }

    if (riskResult.thresholds?.nonCritical?.global) {
        addCoverageThresholdErrors({
            errors,
            metrics: nonCriticalMetrics,
            owner: 'nonCritical.global',
            thresholds: riskResult.thresholds.nonCritical.global,
        });
    }

    const reportedTotal = readMetrics({
        errors,
        owner: 'total',
        summary: coverageSummary?.total,
    });

    for (const metric of COVERAGE_METRICS) {
        for (const field of ['total', 'covered', 'skipped']) {
            if (reportedTotal[metric][field] !== allMetrics[metric][field]) {
                errors.push(
                    `total.${metric}.${field} must equal the exact per-file aggregate ${allMetrics[metric][field]}; received ${reportedTotal[metric][field]}.`,
                );
            }
        }
    }

    const artifactResult = evaluateCoverageArtifacts({
        coverageFinal,
        lcovText,
        coverageSummary,
        expectedPaths,
        repoRoot,
    });
    errors.push(...artifactResult.errors);
    const changedBranchResult = evaluateChangedBranchCoverage({
        artifactFiles: artifactResult.files,
        changedLines,
        baseSha,
        headSha,
        mergeBaseSha,
    });
    errors.push(...changedBranchResult.errors);
    const provenanceResult = evaluateCoverageProvenance({
        provenance,
        baseSha,
        targetSha,
        headSha,
        coverageSetSha256: sourceManifest?.coverageSetSha256,
        sourceHashes,
        toolchainHashes,
        oldestArtifactMtimeMs,
    });
    errors.push(...provenanceResult.errors);

    return {
        passed: errors.length === 0,
        errors,
        evidence: {
            policy: 'D-67',
            enforcementComplete: true,
            policySatisfied: errors.length === 0,
            baseSha,
            targetSha,
            headSha,
            expectedHeadSha,
            sourceManifestSha256:
                typeof sourceManifestText === 'string'
                    ? sha256(sourceManifestText)
                    : null,
            coverageSetSha256: actualCoverageSetSha256,
            fileCount: coveredPaths.length,
            metrics: {
                total: metricsEvidence(allMetrics),
                critical: metricsEvidence(criticalMetrics),
                nonCritical: metricsEvidence(nonCriticalMetrics),
            },
            changedBranchCoverage: changedBranchResult.evidence,
            errors,
        },
    };
};
