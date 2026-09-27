<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

it('assigns every PHP control to exactly one isolated workflow group', function (): void {
    $root = dirname(__DIR__, 2);
    $workflow = Yaml::parseFile($root.'/.github/workflows/tests.yml');
    $groups = $workflow['jobs']['negative-control-groups'];
    $all = new Process(['bash', 'scripts/quality/verify-negative-controls.sh', '--list-controls'], $root);
    $all->mustRun();
    $expected = explode("\n", trim($all->getOutput()));
    $selected = [];
    foreach ($groups['strategy']['matrix']['group'] as $group) {
        $process = new Process(['bash', 'scripts/quality/verify-negative-controls.sh', '--list-group', $group], $root);
        $process->mustRun();
        $selected = [...$selected, ...explode("\n", trim($process->getOutput()))];
    }
    sort($expected);
    sort($selected);
    expect($selected)->toBe($expected)
        ->and($groups['strategy']['fail-fast'])->toBeFalse();
    $required = $workflow['jobs']['negative-controls'];
    expect($required['name'])->toBe('PHP gate negative controls')
        ->and($required['needs'])->toBe(['plan', 'negative-control-groups'])
        ->and($required['if'])->toBe("\${{ always() && needs.plan.outputs.full == 'true' }}")
        ->and($required['steps'][1]['env']['GROUP_RESULT'])->toBe('${{ needs.negative-control-groups.result }}')
        ->and($required['steps'][1]['run'])->toBe('bash scripts/quality/require-php-controls.sh "$GROUP_RESULT"');
});

it('refuses unsuccessful or absent control-group results', function (string $result): void {
    $process = new Process(['bash', 'scripts/quality/require-php-controls.sh', $result], dirname(__DIR__, 2));
    $process->run();
    expect($process->getExitCode())->toBe(1)->and($process->getOutput())->toContain('did not all succeed');
})->with(['failure', 'cancelled', 'skipped', '', 'unknown']);

it('accepts the required control check only when every matrix group succeeded', function (): void {
    $process = new Process(['bash', 'scripts/quality/require-php-controls.sh', 'success'], dirname(__DIR__, 2));
    $process->mustRun();
    expect($process->getOutput())->toContain('All PHP negative-control groups succeeded.');
});

it('rejects unknown control groups instead of reporting an empty success', function (): void {
    $process = new Process(['bash', 'scripts/quality/verify-negative-controls.sh', '--group', 'missing'], dirname(__DIR__, 2));
    $process->run();
    expect($process->getExitCode())->toBe(64)->and($process->getErrorOutput())->toContain('unknown control group');
});

it('proves the coverage control or fails closed when a driver is unavailable', function (): void {
    $root = dirname(__DIR__, 2);
    $driver = new Process(['php', '-d', 'pcov.enabled=1', '-r',
        'require "vendor/autoload.php"; exit(\\Pest\\Support\\Coverage::isAvailable() ? 0 : 1);'], $root);
    $driver->run();
    $process = new Process(['bash', 'scripts/quality/verify-php-coverage-control.sh'], $root);
    $process->setTimeout(60)->run();
    if ($driver->isSuccessful()) {
        expect($process->getExitCode())->toBe(0)
            ->and($process->getOutput())->toContain('green baseline, uncovered file rejected at 100%, green recovery');
    } else {
        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('coverage control requires an active coverage driver');
    }
});
