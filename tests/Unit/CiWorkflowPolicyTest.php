<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    $this->root = dirname(__DIR__, 2);
    $this->temporary = sys_get_temp_dir().'/rozine-ci-policy-'.bin2hex(random_bytes(8));
    mkdir($this->temporary.'/bin', 0700, true);
    $head = new Process(['git', 'rev-parse', 'HEAD'], $this->root);
    $head->mustRun();
    $this->candidate = trim($head->getOutput());
    $tree = new Process(['git', 'rev-parse', 'HEAD^{tree}'], $this->root);
    $tree->mustRun();
    $names = ['Board sync offline tests', 'PHP 8.5 quality gate', 'TypeScript/React quality gate', 'PostgreSQL concurrency lane',
        'PHP gate negative controls', 'Deployment admission negative controls', 'Record tested PR tree',
        'PHP negative controls (architecture)', 'PHP negative controls (business)',
        'PHP negative controls (auditor)', 'PHP negative controls (coverage)'];
    $this->fixtures = [
        'pulls' => [[], [['number' => 42, 'base' => ['ref' => 'dev'], 'merged_at' => '2026-09-27T08:00:00Z',
            'merge_commit_sha' => $this->candidate, 'head' => ['sha' => str_repeat('a', 40)]]]],
        'runs' => [['workflow_runs' => [['id' => 99, 'run_attempt' => 2, 'status' => 'completed',
            'conclusion' => 'success', 'event' => 'pull_request', 'head_sha' => str_repeat('a', 40)]]]],
        'jobs' => array_map(fn (array $chunk): array => ['jobs' => array_map(fn (string $name): array => ['name' => $name, 'conclusion' => 'success'], $chunk)], array_chunk($names, 3)),
        'commit' => ['sha' => str_repeat('b', 40), 'tree' => ['sha' => trim($tree->getOutput())],
            'parents' => [['sha' => str_repeat('d', 40)], ['sha' => str_repeat('a', 40)]]],
        'evidence' => ['schema' => 1, 'repository' => 'rozine-rw/rozine', 'pull_number' => '42',
            'head_sha' => str_repeat('a', 40), 'tested_sha' => str_repeat('b', 40),
            'tree_sha' => trim($tree->getOutput()), 'run_id' => '99', 'run_attempt' => '2'],
    ];
    file_put_contents($this->temporary.'/bin/gh', <<<'STUB'
#!/usr/bin/env python3
import json, os, sys
from pathlib import Path
fixtures = json.loads(Path(os.environ['CI_FIXTURES']).read_text())
args = sys.argv[1:]
if fixtures.get('api_error'):
    sys.exit(1)
if args[:2] == ['run', 'download']:
    if fixtures.get('missing_artifact'):
        sys.exit(1)
    assert args[2] == '99'
    assert args[args.index('--name') + 1] == 'tested-pr-tree-99-2'
    destination = Path(args[args.index('--dir') + 1]) / 'tested-tree.json'
    destination.write_text('invalid' if fixtures.get('malformed_artifact') else json.dumps(fixtures['evidence']))
else:
    endpoint = args[-1]
    if '/git/commits/' in endpoint:
        print(json.dumps(fixtures['commit']))
        sys.exit(0)
    assert '--paginate' in args and '--slurp' in args
    if '/commits/' in endpoint:
        key = 'pulls'
    elif '/actions/workflows/tests.yml/runs?' in endpoint:
        key = 'runs'
    elif '/actions/runs/99/attempts/2/jobs?' in endpoint:
        key = 'jobs'
    else:
        sys.exit(2)
    print(json.dumps(fixtures[key]))
STUB);
    chmod($this->temporary.'/bin/gh', 0700);
    $this->runPolicy = function (string $event = 'push', string $ref = 'refs/heads/dev'): string {
        file_put_contents($this->temporary.'/fixtures.json', json_encode($this->fixtures, JSON_THROW_ON_ERROR));
        $process = new Process(['python3', '.github/scripts/select-ci-mode.py'], $this->root, [
            'PATH' => $this->temporary.'/bin:'.getenv('PATH'),
            'CI_FIXTURES' => $this->temporary.'/fixtures.json',
            'GITHUB_EVENT_NAME' => $event, 'GITHUB_REF' => $ref,
            'GITHUB_SHA' => $this->candidate, 'GITHUB_REPOSITORY' => 'rozine-rw/rozine',
            'GITHUB_OUTPUT' => $this->temporary.'/output',
        ]);
        $process->mustRun();

        return (string) file_get_contents($this->temporary.'/output');
    };
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->temporary);
});

it('reuses successful PR evidence for an identical merged dev tree across API pages', function (): void {
    expect(($this->runPolicy)())->toBe("full=false\nsource_run=99\n");
});

it('always runs full checks for PRs and deployment branches', function (string $event, string $ref): void {
    $this->fixtures['api_error'] = true;
    expect(($this->runPolicy)($event, $ref))->toBe("full=true\nsource_run=\n");
})->with([
    ['pull_request', 'refs/pull/42/merge'], ['push', 'refs/heads/uat'], ['push', 'refs/heads/main'],
]);

it('falls back to full checks when evidence is incomplete or does not match', function (string $case): void {
    match ($case) {
        'direct push' => $this->fixtures['pulls'] = [[]],
        'unmerged PR' => $this->fixtures['pulls'][1][0]['merged_at'] = null,
        'wrong branch' => $this->fixtures['pulls'][1][0]['base']['ref'] = 'uat',
        'wrong merge' => $this->fixtures['pulls'][1][0]['merge_commit_sha'] = str_repeat('c', 40),
        'missing run' => $this->fixtures['runs'][0]['workflow_runs'] = [],
        'failed run' => $this->fixtures['runs'][0]['workflow_runs'][0]['conclusion'] = 'failure',
        'running' => $this->fixtures['runs'][0]['workflow_runs'][0]['status'] = 'in_progress',
        'wrong run head' => $this->fixtures['runs'][0]['workflow_runs'][0]['head_sha'] = str_repeat('c', 40),
        'push run' => $this->fixtures['runs'][0]['workflow_runs'][0]['event'] = 'push',
        'missing job' => $this->fixtures['jobs'][0]['jobs'] = [],
        'skipped job' => $this->fixtures['jobs'][0]['jobs'][0]['conclusion'] = 'skipped',
        'cancelled group' => $this->fixtures['jobs'][3]['jobs'][0]['conclusion'] = 'cancelled',
        'different tree' => $this->fixtures['evidence']['tree_sha'] = str_repeat('c', 40),
        'wrong attempt' => $this->fixtures['evidence']['run_attempt'] = '1',
        'wrong PR' => $this->fixtures['evidence']['pull_number'] = '43',
        'wrong head' => $this->fixtures['evidence']['head_sha'] = str_repeat('c', 40),
        'wrong repo' => $this->fixtures['evidence']['repository'] = 'other/repo',
        'missing artifact' => $this->fixtures['missing_artifact'] = true,
        'malformed artifact' => $this->fixtures['malformed_artifact'] = true,
        'forged artifact tree' => $this->fixtures['commit']['tree']['sha'] = str_repeat('c', 40),
        'wrong merge parent' => $this->fixtures['commit']['parents'][1]['sha'] = str_repeat('c', 40),
        'not a merge' => $this->fixtures['commit']['parents'] = [],
        'non-object artifact' => $this->fixtures['evidence'] = [],
        'API unavailable' => $this->fixtures['api_error'] = true,
        default => throw new InvalidArgumentException('Unknown CI policy scenario: '.$case),
    };
    expect(($this->runPolicy)())->toBe("full=true\nsource_run=\n");
})->with(['direct push', 'unmerged PR', 'wrong branch', 'wrong merge', 'missing run', 'failed run',
    'running', 'wrong run head', 'push run', 'missing job', 'skipped job', 'cancelled group',
    'different tree', 'wrong attempt', 'wrong PR', 'wrong head', 'wrong repo', 'missing artifact',
    'malformed artifact', 'forged artifact tree', 'wrong merge parent', 'not a merge', 'non-object artifact', 'API unavailable']);

it('does not reuse an earlier successful run when the latest run failed', function (): void {
    $latest = $this->fixtures['runs'][0]['workflow_runs'][0];
    $latest['id'] = 100;
    $latest['conclusion'] = 'failure';
    $this->fixtures['runs'][] = ['workflow_runs' => [$latest]];
    expect(($this->runPolicy)())->toBe("full=true\nsource_run=\n");
});

it('tests immutable proposed merges and preserves deployment gates', function (): void {
    $workflow = Yaml::parseFile($this->root.'/.github/workflows/tests.yml');
    expect($workflow['on']['push']['branches'])->toBe(['dev', 'uat', 'main'])
        ->and($workflow['concurrency']['group'])->toBe('tests-${{ github.event.pull_request.number || github.run_id }}')
        ->and($workflow['jobs']['plan']['steps'][1]['env']['GH_TOKEN'])->toBe('${{ github.token }}');
    foreach ($workflow['jobs'] as $job) {
        foreach ($job['steps'] as $step) {
            if (str_starts_with($step['uses'] ?? '', 'actions/checkout@')) {
                expect($step['with']['ref'])->toBe('${{ github.sha }}');
            }
        }
    }
    foreach (['php-shards', 'web', 'concurrency', 'negative-control-groups', 'admission'] as $name) {
        expect($workflow['jobs'][$name]['needs'])->toBe('plan')
            ->and($workflow['jobs'][$name]['if'])->toBe("\${{ needs.plan.outputs.full == 'true' }}");
    }
    expect($workflow['jobs']['tested-tree']['needs'])->toBe(['ci', 'web', 'concurrency', 'negative-controls', 'admission', 'board-sync'])
        ->and($workflow['jobs']['tested-tree']['if'])->toBe("\${{ github.event_name == 'pull_request' && needs.ci.result == 'success' && needs.web.result == 'success' }}")
        ->and($workflow['jobs']['dev-smoke']['if'])->toBe("\${{ needs.plan.outputs.reused == 'true' && needs.plan.outputs.scope == 'full' && github.ref == 'refs/heads/dev' }}");
});

it('requires board sync evidence before reusing the tested tree', function (): void {
    $this->fixtures['jobs'][0]['jobs'][0]['conclusion'] = 'skipped';

    expect(($this->runPolicy)())->toBe("full=true\nsource_run=\n");
});

it('keeps full suites executable while naming POC evidence separately', function (): void {
    $workflow = Yaml::parseFile($this->root.'/.github/workflows/tests.yml');
    expect($workflow['on']['workflow_dispatch']['inputs']['validation']['options'])->toBe(['auto', 'poc', 'full'])
        ->and($workflow['on']['workflow_dispatch']['inputs']['validation']['default'])->toBe('auto')
        ->and($workflow['jobs']['plan']['steps'][1]['run'])->toBe('python3 .github/scripts/select-validation-scope.py');
    foreach (['php-shards', 'web', 'concurrency', 'negative-control-groups', 'admission'] as $name) {
        expect($workflow['jobs'][$name]['if'])->toBe("\${{ needs.plan.outputs.full == 'true' }}");
    }
    foreach (['poc-php', 'poc-web'] as $name) {
        expect($workflow['jobs'][$name]['if'])->toBe("\${{ needs.plan.outputs.poc == 'true' }}")
            ->and($workflow['jobs'][$name]['timeout-minutes'])->toBe(9);
    }
    expect(array_column($workflow['jobs']['poc-php']['steps'], 'name'))->toContain('Record POC validation tree', 'Publish POC validation tree', 'Require combined POC execution within ten minutes')
        ->and($workflow['jobs']['validation-tree']['needs'])->not->toContain('poc-php', 'poc-web')
        ->and($workflow['jobs']['reuse-poc']['name'])->toBe('Reuse POC validation evidence');
});

it('pins original POC provenance and budgets reuse before staging admission', function (): void {
    $workflow = Yaml::parseFile($this->root.'/.github/workflows/tests.yml');
    $steps = $workflow['jobs']['reuse-poc']['steps'];
    expect($steps[1]['env']['SOURCE_RUN'])->toBe('${{ needs.plan.outputs.source_run }}')
        ->and($steps[1]['env']['SOURCE_ATTEMPT'])->toBe('${{ needs.plan.outputs.source_attempt }}')
        ->and($steps[2]['env']['SOURCE_ATTEMPT'])->toBe('${{ needs.plan.outputs.source_attempt }}')
        ->and($steps[2]['env']['SOURCE_RUN'])->toBe('${{ needs.plan.outputs.source_run }}')
        ->and($steps[3]['with']['name'])->toBe('poc-reuse-${{ github.run_id }}-${{ github.run_attempt }}')
        ->and($steps[4]['run'])->toBe('python3 .github/scripts/check-poc-budget.py');
});
