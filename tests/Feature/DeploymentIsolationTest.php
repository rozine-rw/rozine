<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->deploymentDirectory = sys_get_temp_dir().'/rozine-deploy-'.Str::uuid();
    File::ensureDirectoryExists($this->deploymentDirectory.'/bin');
    File::ensureDirectoryExists($this->deploymentDirectory.'/app root');
    File::put($this->deploymentDirectory.'/calls', '');

    $stub = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${1:-}" == "-r" ]]; then
  echo "8.5"
  exit 0
fi
printf '%s\n' "$*" >> "${DEPLOY_TEST_CALLS}"
if [[ "${1:-}" == "artisan" && "${2:-}" == "isolation:check" ]]; then
  if [[ "${DEPLOY_TEST_FAIL:-}" == "first" ]]; then
    exit 42
  fi
  if [[ "${DEPLOY_TEST_FAIL:-}" == "cached" ]] && grep -q '^artisan config:cache$' "${DEPLOY_TEST_CALLS}"; then
    exit 43
  fi
fi
BASH;

    foreach (['php8.5', 'php', 'composer', 'npm'] as $binary) {
        $path = $this->deploymentDirectory.'/bin/'.$binary;
        File::put($path, $stub);
        chmod($path, 0700);
    }
});

afterEach(function () {
    File::deleteDirectory($this->deploymentDirectory);
});

function runIsolatedDeployment(string $directory, ?string $profile, string $fail = ''): Process
{
    $arguments = ['/bin/bash', base_path('.github/scripts/deploy-remote.sh'), $directory.'/app root'];

    if ($profile !== null) {
        $arguments[] = $profile;
    }

    $process = new Process($arguments, base_path(), [
        'PATH' => $directory.'/bin:/usr/bin:/bin',
        'DEPLOY_TEST_CALLS' => $directory.'/calls',
        'DEPLOY_TEST_FAIL' => $fail,
    ]);
    $process->run();

    return $process;
}

test('deployment rejects missing or unsupported targets before invoking any runtime', function (?string $profile) {
    $process = runIsolatedDeployment($this->deploymentDirectory, $profile);

    expect($process->isSuccessful())->toBeFalse()
        ->and(File::get($this->deploymentDirectory.'/calls'))->toBe('');
})->with([null, 'demo', 'staging', 'local']);

test('deployment checks the target before mutations and again after caching configuration', function (string $profile) {
    $process = runIsolatedDeployment($this->deploymentDirectory, $profile);
    $calls = explode("\n", trim(File::get($this->deploymentDirectory.'/calls')));
    $check = 'artisan isolation:check --expect='.$profile.' --no-interaction';

    expect($process->isSuccessful())->toBeTrue()
        ->and($process->getOutput())->toContain('Deployment complete under PHP 8.5.')
        ->and(array_values(array_filter($calls, fn (string $call): bool => $call === $check)))->toHaveCount(2);

    expect($calls)->toBe([
        $this->deploymentDirectory.'/bin/composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist',
        'artisan config:clear',
        $check,
        'artisan optimize:clear',
        'ci --no-audit --no-fund',
        'run build',
        'artisan migrate --force',
        'artisan config:cache',
        $check,
        'artisan route:cache',
        'artisan view:cache',
        'artisan queue:restart',
    ]);
})->with(['uat', 'production']);

test('a rejected deployment never clears shared caches migrates or restarts queues', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat', 'first');
    $calls = File::get($this->deploymentDirectory.'/calls');

    expect($process->getExitCode())->toBe(42)
        ->and($calls)->toContain('artisan isolation:check --expect=uat')
        ->not->toContain('optimize:clear', 'migrate --force', 'queue:restart', 'ci --no-audit')
        ->and($process->getOutput())->not->toContain('Deployment complete');
});

test('a rejected cached configuration prevents publishing route caches or restarting queues', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'production', 'cached');

    expect($process->getExitCode())->toBe(43)
        ->and(File::get($this->deploymentDirectory.'/calls'))->toContain('artisan config:cache')
        ->not->toContain('route:cache', 'view:cache', 'queue:restart')
        ->and($process->getOutput())->not->toContain('Deployment complete');
});

test('each deployment workflow passes its fixed environment profile', function () {
    expect(File::get(base_path('.github/workflows/deploy-uat.yml')))->toContain(' uat\' < .github/scripts/deploy-remote.sh')
        ->and(File::get(base_path('.github/workflows/deploy-prod.yml')))->toContain(' production\' < .github/scripts/deploy-remote.sh');
});
