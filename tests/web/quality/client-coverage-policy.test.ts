import { execFileSync } from 'node:child_process';
import {
    mkdirSync,
    mkdtempSync,
    renameSync,
    rmSync,
    writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import {
    evaluateChangedBranchCoverage,
    evaluateClientCoveragePolicy,
    evaluateCoverageArtifacts,
    evaluateCoverageProvenance,
    evaluateGeneratedClientStatus,
    evaluateRiskManifest,
    evaluateSourceManifest,
    parseChangedHeadLines,
    sha256,
} from '../../../scripts/quality/client-coverage-policy.mjs';
import {
    assertPathsMatchHead,
    trackedClientSources,
} from '../../../scripts/quality/verify-client-coverage.mjs';
import {
    completeFileMetrics,
    makeCoverageFinal,
    makeCoverageSummary,
    makeLcov,
    makePolicyFixture,
    makeRiskManifest,
    makeSourceManifest,
    metric,
    sourcePaths,
    testBaseSha,
    testHeadSha,
    testRepoRoot,
    trackedPaths,
} from './fixtures/client-coverage-fixtures';

describe('client source and risk manifests', () => {
    it('rejects generated Wayfinder drift after regeneration', () => {
        expect(evaluateGeneratedClientStatus('')).toEqual([]);
        expect(
            evaluateGeneratedClientStatus(
                ' M resources/js/routes/index.ts\n?? resources/js/routes/new/index.ts',
            ),
        ).toEqual([expect.stringContaining('Wayfinder output differs')]);
    });

    it('accepts only an exact, non-overlapping tracked source inventory', () => {
        const manifest = makeSourceManifest();

        expect(
            evaluateSourceManifest({ manifest, trackedPaths }).errors,
        ).toEqual([]);

        manifest.authoredExecutablePaths =
            manifest.authoredExecutablePaths.slice(1);

        expect(
            evaluateSourceManifest({ manifest, trackedPaths }).errors,
        ).toEqual(
            expect.arrayContaining([
                expect.stringContaining('inventory is missing'),
                expect.stringContaining('counts.authoredExecutable'),
            ]),
        );
    });

    it('fails closed when exact exclusions drift or lose governance metadata', () => {
        const manifest = makeSourceManifest();
        manifest.generatedPaths.push(manifest.authoredExecutablePaths[0]);
        manifest.exclusionMetadata.generatedPaths.approver = '';

        const result = evaluateSourceManifest({ manifest, trackedPaths });

        expect(result.errors).toEqual(
            expect.arrayContaining([
                expect.stringContaining('overlap'),
                expect.stringContaining('approver'),
                expect.stringContaining('verified Wayfinder roots'),
            ]),
        );
    });

    it('requires exact source SHA, complete classification, and risk governance', () => {
        const sourceManifest = makeSourceManifest();
        const sourceManifestText = `${JSON.stringify(sourceManifest, null, 2)}\n`;
        const riskManifest = makeRiskManifest({
            sourceManifest,
            sourceManifestText,
        });
        riskManifest.sourceManifestSha256 = '0'.repeat(64);
        riskManifest.criticalPaths = riskManifest.criticalPaths.slice(1);
        riskManifest.classificationMetadata.criticalPaths.owner = '';

        const result = evaluateRiskManifest({
            manifest: riskManifest,
            sourceManifest,
            sourceManifestText,
        });

        expect(result.errors).toEqual(
            expect.arrayContaining([
                expect.stringContaining('sourceManifestSha256'),
                expect.stringContaining('classification is missing'),
                expect.stringContaining('owner'),
            ]),
        );
    });

    it('rejects a non-critical downgrade without path-specific approvals', () => {
        const sourceManifest = makeSourceManifest();
        const sourceManifestText = `${JSON.stringify(sourceManifest, null, 2)}\n`;
        const [downgradedPath, ...criticalPaths] =
            sourceManifest.authoredExecutablePaths;
        const riskManifest = makeRiskManifest({
            sourceManifest,
            sourceManifestText,
            criticalPaths,
            nonCriticalPaths: [downgradedPath],
        });
        delete riskManifest.nonCriticalApprovals[downgradedPath];

        const result = evaluateRiskManifest({
            manifest: riskManifest,
            sourceManifest,
            sourceManifestText,
        });

        expect(result.errors).toContain(
            `Non-critical approval records is missing: ${downgradedPath}`,
        );
    });
});

describe('exact client metric enforcement', () => {
    it('passes exact global and per-file critical metrics', () => {
        const result = evaluateClientCoveragePolicy(makePolicyFixture());

        expect(result.errors).toEqual([]);
        expect(result.passed).toBe(true);
        expect(result.evidence.policy).toBe('D-67');
        expect(result.evidence.enforcementComplete).toBe(true);
    });

    it('uses raw counters rather than rounded percentages', () => {
        const file = sourcePaths.authored[0];
        const metrics = completeFileMetrics();
        metrics.lines = metric(10_001, 10_000);
        const coverageSummary = makeCoverageSummary({ [file]: metrics });

        const result = evaluateClientCoveragePolicy(
            makePolicyFixture({ coverageSummary }),
        );

        expect(result.passed).toBe(false);
        expect(result.errors).toContain(
            `${file}.lines coverage is 99.99%; required 100%.`,
        );
    });

    it('rejects totals or percentages that do not exactly match per-file evidence', () => {
        const fixture = makePolicyFixture();
        fixture.coverageSummary.total.lines.total += 1;
        fixture.coverageSummary.total.statements.pct = 99;

        const result = evaluateClientCoveragePolicy(fixture);

        expect(result.errors).toEqual(
            expect.arrayContaining([
                expect.stringContaining('total.lines.total must equal'),
                expect.stringContaining('total.statements.pct must be 100'),
            ]),
        );
    });

    it('applies 90% per-file and 95% global noncritical branch thresholds', () => {
        const [critical, presentationA, presentationB] = sourcePaths.authored;
        const aMetrics = completeFileMetrics();
        const bMetrics = completeFileMetrics();
        aMetrics.branches = metric(10, 9);
        bMetrics.branches = metric(10, 9);
        const coverageSummary = makeCoverageSummary({
            [presentationA]: aMetrics,
            [presentationB]: bMetrics,
        });
        const result = evaluateClientCoveragePolicy(
            makePolicyFixture({
                criticalPaths: [critical],
                nonCriticalPaths: [presentationA, presentationB],
                coverageSummary,
            }),
        );

        expect(result.errors).toContain(
            'nonCritical.global.branches coverage is 90%; required 95%.',
        );
        expect(result.errors).not.toContain(
            `${presentationA}.branches coverage is 90%; required 90%.`,
        );
    });

    it('treats zero denominators as fully covered without hiding skipped items', () => {
        const file = sourcePaths.authored[0];
        const metrics = completeFileMetrics();
        metrics.functions = metric(0);
        metrics.branches = metric(0);
        const coverageSummary = makeCoverageSummary({ [file]: metrics });
        const coverageFinal = makeCoverageFinal();
        const finalFile = coverageFinal[
            `${testRepoRoot}/${file}`
        ] as unknown as {
            fnMap: Record<string, unknown>;
            f: Record<string, number>;
            branchMap: Record<string, unknown>;
            b: Record<string, number[]>;
        };
        finalFile.fnMap = {};
        finalFile.f = {};
        finalFile.branchMap = {};
        finalFile.b = {};
        const lcovText = makeLcov().replace(
            `SF:${file}\nDA:1,1\nDA:2,1\nBRDA:2,0,0,1\nBRDA:2,0,1,1`,
            `SF:${file}\nDA:1,1\nDA:2,1`,
        );

        const result = evaluateClientCoveragePolicy(
            makePolicyFixture({ coverageSummary, coverageFinal, lcovText }),
        );

        expect(result.errors).toEqual([]);
    });
});

describe('coverage set, source-map, LCOV, and SHA evidence', () => {
    it('rejects a missing coverage file and changed coverage-set SHA', () => {
        const fixture = makePolicyFixture();
        const mutableCoverageSummary =
            fixture.coverageSummary as unknown as Record<string, unknown>;
        delete mutableCoverageSummary[
            `${testRepoRoot}/${sourcePaths.authored[0]}`
        ];

        const result = evaluateClientCoveragePolicy(fixture);

        expect(result.errors).toEqual(
            expect.arrayContaining([
                expect.stringContaining('Coverage report file set is missing'),
                expect.stringContaining('Coverage report set SHA-256'),
            ]),
        );
    });

    it('cross-checks Istanbul source maps against LCOV line and branch counters', () => {
        const coverageFinal = makeCoverageFinal();
        const file = `${testRepoRoot}/${sourcePaths.authored[0]}`;
        coverageFinal[file].b[0][1] = 0;

        const result = evaluateCoverageArtifacts({
            coverageFinal,
            lcovText: makeLcov(),
            coverageSummary: makeCoverageSummary(),
            expectedPaths: sourcePaths.authored,
            repoRoot: testRepoRoot,
        });

        expect(result.errors).toContain(
            `${sourcePaths.authored[0]} branch source-map counters do not match LCOV BRDA evidence.`,
        );
    });

    it('fails unmappable branch evidence closed', () => {
        const coverageFinal = makeCoverageFinal();
        const file = `${testRepoRoot}/${sourcePaths.authored[0]}`;
        coverageFinal[file].branchMap[0].locations.pop();

        const result = evaluateCoverageArtifacts({
            coverageFinal,
            lcovText: makeLcov(),
            coverageSummary: makeCoverageSummary(),
            expectedPaths: sourcePaths.authored,
            repoRoot: testRepoRoot,
        });

        expect(result.errors).toEqual(
            expect.arrayContaining([
                expect.stringContaining('unmappable branch 0'),
                expect.stringContaining('branch source-map counters'),
            ]),
        );
    });

    it('rejects stale source hashes and stale coverage timestamps', () => {
        const fixture = makePolicyFixture();
        fixture.provenance.sourceHashes[sourcePaths.authored[0]] =
            sha256('stale');
        fixture.provenance.toolchainHashes['vitest.config.ts'] =
            sha256('stale');

        const result = evaluateCoverageProvenance({
            provenance: fixture.provenance,
            baseSha: fixture.baseSha,
            targetSha: fixture.targetSha,
            headSha: fixture.headSha,
            coverageSetSha256: fixture.sourceManifest.coverageSetSha256,
            sourceHashes: fixture.sourceHashes,
            toolchainHashes: fixture.toolchainHashes,
            oldestArtifactMtimeMs: 999,
        });

        expect(result.errors).toEqual(
            expect.arrayContaining([
                'Coverage artifacts are stale relative to the prepared run provenance.',
                expect.stringContaining('stale source hash'),
                expect.stringContaining('stale toolchain hash'),
            ]),
        );
    });

    it('rejects a checkout SHA that differs from the supplied immutable head', () => {
        const fixture = makePolicyFixture();
        fixture.expectedHeadSha = 'd'.repeat(40);

        const result = evaluateClientCoveragePolicy(fixture);

        expect(result.errors).toContain(
            `Coverage SHA mismatch: expected ${'d'.repeat(40)}; received ${testHeadSha}.`,
        );
    });
});

describe('changed-branch enforcement', () => {
    it('maps only added/modified head lines from zero-context Git hunks', () => {
        const changed = parseChangedHeadLines(
            [
                'diff --git a/resources/js/a.ts b/resources/js/a.ts',
                '--- a/resources/js/a.ts',
                '+++ b/resources/js/a.ts',
                '@@ -3,2 +3,3 @@',
                '@@ -10,1 +11 @@',
                '@@ -20,2 +21,0 @@',
                'diff --git a/resources/js/removed.ts b/resources/js/removed.ts',
                '--- a/resources/js/removed.ts',
                '+++ /dev/null',
                '@@ -1,2 +0,0 @@',
            ].join('\n'),
        );

        expect([...changed]).toEqual([
            ['resources/js/a.ts', new Set([3, 4, 5, 11])],
        ]);
    });

    it('maps changed head lines to source-mapped branches and requires every outcome', () => {
        const file = sourcePaths.authored[0];
        const artifactResult = evaluateCoverageArtifacts({
            coverageFinal: makeCoverageFinal({ [file]: [1, 0] }),
            lcovText: makeLcov({ [file]: [1, 0] }),
            coverageSummary: makeCoverageSummary(),
            expectedPaths: sourcePaths.authored,
            repoRoot: testRepoRoot,
        });
        const result = evaluateChangedBranchCoverage({
            artifactFiles: artifactResult.files,
            changedLines: new Map([[file, new Set([2])]]),
            baseSha: testBaseSha,
            headSha: testHeadSha,
            mergeBaseSha: testBaseSha,
        });

        expect(result.errors).toContain(
            `${file} changed branch 0 has uncovered outcomes: 1.`,
        );
        expect(result.evidence.changedBranchCount).toBe(1);
    });

    it('fails when the recorded base is not the target/head merge base', () => {
        const result = evaluateChangedBranchCoverage({
            artifactFiles: new Map(),
            changedLines: new Map(),
            baseSha: testBaseSha,
            headSha: testHeadSha,
            mergeBaseSha: 'd'.repeat(40),
        });

        expect(result.errors).toContain(
            `Recorded base SHA ${testBaseSha} is not the immutable merge base ${'d'.repeat(40)}.`,
        );
    });
});

describe('exact checkout inventory', () => {
    it('derives source inventory from supplied HEAD and rejects untracked or renamed escapes', () => {
        const repository = mkdtempSync(
            path.join(tmpdir(), 'rozine-coverage-policy-'),
        );

        try {
            mkdirSync(path.join(repository, 'resources/js'), {
                recursive: true,
            });
            writeFileSync(
                path.join(repository, 'resources/js/existing.ts'),
                'export const existing = true;\n',
            );
            execFileSync('git', ['init', '--quiet'], { cwd: repository });
            execFileSync('git', ['add', 'resources/js/existing.ts'], {
                cwd: repository,
            });
            execFileSync(
                'git',
                [
                    '-c',
                    'user.name=Coverage Test',
                    '-c',
                    'user.email=coverage@example.test',
                    'commit',
                    '--quiet',
                    '-m',
                    'fixture',
                ],
                { cwd: repository },
            );
            const headSha = execFileSync('git', ['rev-parse', 'HEAD'], {
                cwd: repository,
                encoding: 'utf8',
            }).trim();

            writeFileSync(
                path.join(repository, 'resources/js/untracked.ts'),
                'export const escaped = true;\n',
            );

            expect(trackedClientSources(repository, headSha)).toEqual([
                'resources/js/existing.ts',
            ]);
            expect(() =>
                assertPathsMatchHead(
                    repository,
                    ['resources/js'],
                    'Governed client source',
                ),
            ).toThrow('differs from the supplied HEAD');

            rmSync(path.join(repository, 'resources/js/untracked.ts'));
            renameSync(
                path.join(repository, 'resources/js/existing.ts'),
                path.join(repository, 'resources/js/renamed.ts'),
            );

            expect(() =>
                assertPathsMatchHead(
                    repository,
                    ['resources/js'],
                    'Governed client source',
                ),
            ).toThrow('differs from the supplied HEAD');
        } finally {
            rmSync(repository, { recursive: true, force: true });
        }
    });
});
