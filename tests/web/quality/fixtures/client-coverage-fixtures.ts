import { sha256 } from '../../../../scripts/quality/client-coverage-policy.mjs';

export const testRepoRoot = '/workspace/rozine';
export const testHeadSha = 'a'.repeat(40);
export const testBaseSha = 'b'.repeat(40);
export const testTargetSha = 'c'.repeat(40);

export const sourcePaths = {
    generated: ['resources/js/actions/App/Controller.ts'],
    declarationOnly: ['resources/js/types/global.d.ts'],
    authored: [
        'resources/js/components/critical-a.tsx',
        'resources/js/components/critical-b.tsx',
        'resources/js/components/presentation.tsx',
    ],
};

export const trackedPaths = [
    ...sourcePaths.generated,
    ...sourcePaths.declarationOnly,
    ...sourcePaths.authored,
].sort();

const pathSetSha = (paths: string[]) => {
    const sortedPaths = [...paths].sort();

    return sha256(`${sortedPaths.join('\n')}\n`);
};

const percentage = (covered: number, total: number) => {
    if (total === 0) {
        return 100;
    }

    return Math.floor((covered * 10000) / total) / 100;
};

export const metric = (total: number, covered = total) => ({
    total,
    covered,
    skipped: 0,
    pct: percentage(covered, total),
});

export const completeFileMetrics = () => ({
    lines: metric(2),
    statements: metric(2),
    functions: metric(1),
    branches: metric(2),
});

type FileMetrics = ReturnType<typeof completeFileMetrics>;

const aggregate = (files: Record<string, FileMetrics>) => {
    const totals = {
        lines: metric(0),
        statements: metric(0),
        functions: metric(0),
        branches: metric(0),
    };

    for (const file of Object.values(files)) {
        for (const name of [
            'lines',
            'statements',
            'functions',
            'branches',
        ] as const) {
            totals[name].total += file[name].total;
            totals[name].covered += file[name].covered;
            totals[name].pct = percentage(
                totals[name].covered,
                totals[name].total,
            );
        }
    }

    return totals;
};

export const makeSourceManifest = () => ({
    version: 1,
    sourceRoot: 'resources/js',
    extensions: ['.ts', '.tsx'],
    policy: 'D-66/D-67',
    exclusionMetadata: {
        generatedPaths: {
            provenance: 'Fixture generator output',
            generator: 'Fixture Wayfinder',
            owner: 'Engineering',
            rationale: 'Generated fixture',
            approver: 'Quality owner',
            reviewTrigger: 'Generator changes',
        },
        declarationOnlyPaths: {
            provenance: 'Fixture declarations',
            generator: 'TypeScript',
            owner: 'Engineering',
            rationale: 'No runtime output',
            approver: 'Quality owner',
            reviewTrigger: 'Runtime output appears',
        },
    },
    generatedPaths: [...sourcePaths.generated],
    declarationOnlyPaths: [...sourcePaths.declarationOnly],
    authoredExecutablePaths: [...sourcePaths.authored],
    counts: {
        totalTrackedSources: trackedPaths.length,
        generated: sourcePaths.generated.length,
        declarationOnly: sourcePaths.declarationOnly.length,
        authoredExecutable: sourcePaths.authored.length,
    },
    coverageSetSha256: pathSetSha(sourcePaths.authored),
});

const fullThresholds = {
    lines: 100,
    statements: 100,
    functions: 100,
    branches: 100,
};

export const makeRiskManifest = ({
    sourceManifest,
    sourceManifestText,
    criticalPaths = sourcePaths.authored,
    nonCriticalPaths = [],
}: {
    sourceManifest: ReturnType<typeof makeSourceManifest>;
    sourceManifestText: string;
    criticalPaths?: string[];
    nonCriticalPaths?: string[];
}) => ({
    version: 1,
    policy: 'D-67',
    defaultClassification: 'critical',
    classificationMetadata: {
        criticalPaths: {
            owner: 'Engineering',
            approver: 'Quality owner',
            reviewTrigger: 'Critical path changes',
        },
        nonCriticalPaths: {
            owner: 'Engineering',
            approver: 'Quality owner',
            reviewTrigger: 'Risk changes',
        },
    },
    nonCriticalApprovals: Object.fromEntries(
        nonCriticalPaths.map((file) => [
            file,
            {
                rationale: 'Presentation-only fixture',
                engineeringApprover: 'Second developer',
                domainApprover: 'Fixture domain owner',
                evidence: 'TEST-APPROVAL',
                approvedAt: '2026-08-24',
                reviewTrigger: 'Behavior or dependency changes',
            },
        ]),
    ),
    sourceManifestSha256: sha256(sourceManifestText),
    coverageSetSha256: sourceManifest.coverageSetSha256,
    thresholds: {
        critical: {
            global: { ...fullThresholds },
            perFile: { ...fullThresholds },
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
    },
    criticalPaths: [...criticalPaths].sort(),
    nonCriticalPaths: [...nonCriticalPaths].sort(),
});

export const makeCoverageSummary = (
    replacements: Partial<Record<string, FileMetrics>> = {},
) => {
    const files = Object.fromEntries(
        sourcePaths.authored.map((file) => [
            `${testRepoRoot}/${file}`,
            replacements[file] ?? completeFileMetrics(),
        ]),
    );

    return {
        total: aggregate(files),
        ...files,
    };
};

export const makeCoverageFinal = (
    branchHits: Partial<Record<string, number[]>> = {},
) =>
    Object.fromEntries(
        sourcePaths.authored.map((file) => {
            const absolutePath = `${testRepoRoot}/${file}`;

            return [
                absolutePath,
                {
                    path: absolutePath,
                    statementMap: {
                        0: {
                            start: { line: 1, column: 0 },
                            end: { line: 1, column: 1 },
                        },
                        1: {
                            start: { line: 2, column: 0 },
                            end: { line: 2, column: 10 },
                        },
                    },
                    fnMap: {
                        0: {
                            name: 'fixture',
                            decl: {
                                start: { line: 1, column: 0 },
                                end: { line: 1, column: 7 },
                            },
                            loc: {
                                start: { line: 1, column: 0 },
                                end: { line: 2, column: 10 },
                            },
                            line: 1,
                        },
                    },
                    branchMap: {
                        0: {
                            loc: {
                                start: { line: 2, column: 0 },
                                end: { line: 2, column: 10 },
                            },
                            type: 'if',
                            locations: [
                                {
                                    start: { line: 2, column: 0 },
                                    end: { line: 2, column: 5 },
                                },
                                {
                                    start: { line: 2, column: 5 },
                                    end: { line: 2, column: 10 },
                                },
                            ],
                            line: 2,
                        },
                    },
                    s: { 0: 1, 1: 1 },
                    f: { 0: 1 },
                    b: { 0: branchHits[file] ?? [1, 1] },
                },
            ];
        }),
    );

export const makeLcov = (branchHits: Partial<Record<string, number[]>> = {}) =>
    sourcePaths.authored
        .map((file) => {
            const hits = branchHits[file] ?? [1, 1];

            return [
                'TN:',
                `SF:${file}`,
                'DA:1,1',
                'DA:2,1',
                `BRDA:2,0,0,${hits[0]}`,
                `BRDA:2,0,1,${hits[1]}`,
                'end_of_record',
            ].join('\n');
        })
        .join('\n');

export const makePolicyFixture = ({
    criticalPaths = sourcePaths.authored,
    nonCriticalPaths = [],
    coverageSummary = makeCoverageSummary(),
    coverageFinal = makeCoverageFinal(),
    lcovText = makeLcov(),
}: {
    criticalPaths?: string[];
    nonCriticalPaths?: string[];
    coverageSummary?: ReturnType<typeof makeCoverageSummary>;
    coverageFinal?: ReturnType<typeof makeCoverageFinal>;
    lcovText?: string;
} = {}) => {
    const sourceManifest = makeSourceManifest();
    const sourceManifestText = `${JSON.stringify(sourceManifest, null, 2)}\n`;
    const riskManifest = makeRiskManifest({
        sourceManifest,
        sourceManifestText,
        criticalPaths,
        nonCriticalPaths,
    });
    const sourceHashes = Object.fromEntries(
        sourcePaths.authored.map((file) => [file, sha256(`source:${file}`)]),
    );
    const toolchainHashes = {
        'package.json': sha256('fixture package'),
        'vitest.config.ts': sha256('fixture vitest config'),
    };
    const provenance = {
        version: 1,
        baseSha: testBaseSha,
        targetSha: testTargetSha,
        headSha: testHeadSha,
        coverageSetSha256: sourceManifest.coverageSetSha256,
        preparedAtMs: 1_000,
        sourceHashes: { ...sourceHashes },
        toolchainHashes: { ...toolchainHashes },
    };

    return {
        sourceManifest,
        sourceManifestText,
        riskManifest,
        coverageSummary,
        coverageFinal,
        lcovText,
        trackedPaths,
        repoRoot: testRepoRoot,
        baseSha: testBaseSha,
        targetSha: testTargetSha,
        headSha: testHeadSha,
        expectedHeadSha: testHeadSha,
        mergeBaseSha: testBaseSha,
        changedLines: new Map<string, Set<number>>(),
        provenance,
        sourceHashes,
        toolchainHashes,
        oldestArtifactMtimeMs: 1_001,
    };
};
