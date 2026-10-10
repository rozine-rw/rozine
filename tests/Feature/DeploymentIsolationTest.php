<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->deploymentDirectory = sys_get_temp_dir().'/rozine-deploy-'.Str::uuid();
    File::ensureDirectoryExists($this->deploymentDirectory.'/bin');
    File::ensureDirectoryExists($this->deploymentDirectory.'/app root/storage');
    File::put($this->deploymentDirectory.'/calls', '');

    $stub = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${1:-}" == "-r" ]]; then
  echo "8.5"
  exit 0
fi
printf '%s\n' "$*" >> "${DEPLOY_TEST_CALLS}"
if [[ "${1:-}" == "artisan" && "${2:-}" == "optimize:clear" ]]; then
  ls -1 storage/isolated/uat > "${DEPLOY_TEST_CALLS}.at-clear" 2>/dev/null || true
fi
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

    // Records each group hand-over, then really applies it, unless the test
    // stands in for a deploy account outside the web server's group.
    File::put($this->deploymentDirectory.'/bin/chgrp', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "${DEPLOY_TEST_CALLS}.chgrp"
if [[ "${DEPLOY_TEST_FAIL:-}" == "chgrp" ]]; then
  echo "chgrp: changing group of '$2': Operation not permitted" >&2
  exit 1
fi
exec /bin/chgrp "$@"
BASH);
    chmod($this->deploymentDirectory.'/bin/chgrp', 0700);

    // These legacy call-order tests use stubs. The Python deployment controls
    // additionally execute two real process identities on the CI runner.
    File::put($this->deploymentDirectory.'/bin/id', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${2:-}" == "uat-runtime" ]]; then
  case "$1" in
    -u) echo 65534 ;;
    -G) stat -c %g "${DEPLOY_TEST_STORAGE}" ;;
    *) exit 1 ;;
  esac
else exec /usr/bin/id "$@"; fi
BASH);
    File::put($this->deploymentDirectory.'/bin/sudo', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
[[ "$1 $2 $3 $4 $5" == '-n -u uat-runtime -- /usr/bin/test' ]] || exit 1
shift 4
exec "$@"
BASH);
    chmod($this->deploymentDirectory.'/bin/id', 0700);
    chmod($this->deploymentDirectory.'/bin/sudo', 0700);
});

afterEach(function () {
    File::deleteDirectory($this->deploymentDirectory);
});

function runIsolatedDeployment(string $directory, ?string $profile, string $fail = ''): Process
{
    $arguments = ['/bin/bash', base_path('.github/scripts/deploy-remote.sh'), $directory.'/app root'];

    if ($profile !== null) {
        $arguments[] = $profile;
        $arguments[] = 'uat-runtime';
    }

    $process = new Process($arguments, base_path(), [
        'PATH' => $directory.'/bin:/usr/bin:/bin',
        'DEPLOY_TEST_CALLS' => $directory.'/calls',
        'DEPLOY_TEST_FAIL' => $fail,
        'DEPLOY_TEST_STORAGE' => $directory.'/app root/storage',
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
        ...($profile === 'uat' ? ['artisan storage:link --no-interaction'] : []),
        'artisan route:cache',
        ...($profile === 'uat' ? [] : ['artisan view:cache']),
        'artisan queue:restart',
    ]);
})->with(['uat', 'production']);

test('a uat deployment leaves compiled views to the runtime identity, after clearing the stale ones', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');
    $calls = explode("\n", trim(File::get($this->deploymentDirectory.'/calls')));
    $position = array_flip($calls);

    // The runtime refreshes a stale compiled view by setting its timestamp,
    // which only the owner may do, so the deploy account must not own any.
    expect($process->isSuccessful())->toBeTrue()
        ->and($calls)->toContain('artisan optimize:clear', 'artisan migrate --force')
        ->and($calls)->not->toContain('artisan view:cache')
        ->and($position['artisan optimize:clear'])->toBeLessThan($position['artisan migrate --force']);
});

test('a uat deployment creates its isolated storage folders before clearing caches', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');
    $root = $this->deploymentDirectory.'/app root/storage/isolated/uat';
    $folders = ['cache', 'logs', 'private', 'public', 'sessions', 'views'];

    expect($process->isSuccessful())->toBeTrue()
        ->and(explode("\n", trim(File::get($this->deploymentDirectory.'/calls.at-clear'))))->toBe($folders);

    foreach ($folders as $folder) {
        expect(fileperms($root.'/'.$folder) & 07777)->toBe(02775, $folder.' is not group-writable and setgid');
    }
});

test('a uat deployment hands each isolated storage folder to the web server group of the provisioned storage', function () {
    // Stand storage/ in a group other than the deploy account's own whenever
    // this account has one, so mkdir creates each folder in the wrong group.
    $deployGroup = posix_getegid();
    $group = posix_geteuid() === 0 ? 65534 : (array_values(array_diff(posix_getgroups() ?: [], [$deployGroup]))[0] ?? $deployGroup);
    chgrp($this->deploymentDirectory.'/app root/storage', $group);

    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');
    $root = 'storage/isolated/uat/';

    expect($process->isSuccessful())->toBeTrue()
        ->and(explode("\n", trim(File::get($this->deploymentDirectory.'/calls.chgrp'))))->toBe(array_map(
            fn (string $folder): string => $group.' '.$root.$folder,
            ['cache', 'sessions', 'private', 'public', 'logs', 'views'],
        ));

    foreach (['cache', 'sessions', 'private', 'public', 'logs', 'views'] as $folder) {
        expect(filegroup($this->deploymentDirectory.'/app root/'.$root.$folder))->toBe($group);
    }
});

test('a uat deployment stops before clearing caches when this account cannot hand a folder to the web server group', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat', 'chgrp');

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getErrorOutput())->toContain('storage/isolated/uat/cache cannot be given the web server group')
        ->and(File::get($this->deploymentDirectory.'/calls'))->toContain('artisan isolation:check --expect=uat')
        ->not->toContain('optimize:clear', 'migrate --force', 'queue:restart', 'ci --no-audit')
        ->and($process->getOutput())->not->toContain('Deployment complete');
});

test('a uat deployment stops on a host whose storage was never provisioned', function () {
    File::deleteDirectory($this->deploymentDirectory.'/app root/storage');

    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getErrorOutput())->toContain('storage/ is not provisioned')
        ->and(File::exists($this->deploymentDirectory.'/app root/storage'))->toBeFalse()
        ->and(File::get($this->deploymentDirectory.'/calls'))->not->toContain('optimize:clear', 'migrate --force');
});

test('a production deployment leaves isolated storage and the public link alone', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'production');

    expect($process->isSuccessful())->toBeTrue()
        ->and(File::exists($this->deploymentDirectory.'/app root/storage/isolated'))->toBeFalse()
        ->and(File::exists($this->deploymentDirectory.'/calls.chgrp'))->toBeFalse()
        ->and(File::get($this->deploymentDirectory.'/calls'))->not->toContain('storage:link');
});

test('a uat deployment keeps an existing public storage link', function () {
    File::ensureDirectoryExists($this->deploymentDirectory.'/app root/public');
    symlink($this->deploymentDirectory.'/app root/storage/isolated/uat/public', $this->deploymentDirectory.'/app root/public/storage');

    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');

    expect($process->isSuccessful())->toBeTrue()
        ->and(File::get($this->deploymentDirectory.'/calls'))->not->toContain('storage:link');
});

test('an isolated storage folder that cannot be created stops the deployment before it clears caches', function () {
    File::ensureDirectoryExists($this->deploymentDirectory.'/app root/storage/isolated/uat');
    File::put($this->deploymentDirectory.'/app root/storage/isolated/uat/sessions', '');

    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat');

    expect($process->isSuccessful())->toBeFalse()
        ->and(File::get($this->deploymentDirectory.'/calls'))->toContain('artisan isolation:check --expect=uat')
        ->not->toContain('optimize:clear', 'migrate --force', 'queue:restart', 'ci --no-audit')
        ->and($process->getOutput())->not->toContain('Deployment complete');
});

test('a rejected deployment never clears shared caches migrates or restarts queues', function () {
    $process = runIsolatedDeployment($this->deploymentDirectory, 'uat', 'first');
    $calls = File::get($this->deploymentDirectory.'/calls');

    expect($process->getExitCode())->toBe(42)
        ->and($calls)->toContain('artisan isolation:check --expect=uat')
        ->not->toContain('optimize:clear', 'migrate --force', 'queue:restart', 'ci --no-audit')
        ->and(File::exists($this->deploymentDirectory.'/app root/storage/isolated'))->toBeFalse()
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
    expect(File::get(base_path('.github/workflows/deploy-uat.yml')))->toContain(' uat ${UAT_RUNTIME_USER}', 'vars.STAGING_RUNTIME_USER')
        ->and(File::get(base_path('.github/workflows/deploy-prod.yml')))->toContain(' production\' < .github/scripts/deploy-remote.sh');
});

test('each deployment copies nested vendor and storage folders and skips only the root ones', function (string $workflow) {
    preg_match_all("/--exclude '([^']+)'/", File::get(base_path('.github/workflows/'.$workflow)), $matches);
    $source = $this->deploymentDirectory.'/source';
    $target = $this->deploymentDirectory.'/target';
    $kept = ['resources/views/vendor/mail/html/code.blade.php', 'resources/views/vendor/mail/text/code.blade.php',
        'resources/js/routes/storage/index.ts', 'app/Http/Kernel.php'];
    $skipped = ['.git/HEAD', '.github/workflows/tests.yml', 'node_modules/vite/index.js', 'vendor/autoload.php', '.env',
        'storage/logs/laravel.log', 'bootstrap/cache/config.php', 'public/build/manifest.json', 'public/storage/file.txt'];
    foreach ([...$kept, ...$skipped] as $path) {
        File::ensureDirectoryExists(dirname($source.'/'.$path));
        File::put($source.'/'.$path, $path);
    }
    $excludes = array_merge(...array_map(fn (string $pattern): array => ['--exclude', $pattern], $matches[1]));

    $process = new Process(['rsync', '-a', '--delete', ...$excludes, $source.'/', $target.'/']);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    foreach ($kept as $path) {
        expect(File::exists($target.'/'.$path))->toBeTrue($path.' must be deployed');
    }
    foreach ($skipped as $path) {
        expect(File::exists($target.'/'.$path))->toBeFalse($path.' must not be deployed');
    }
    expect($matches[1])->toHaveCount(9)->each->toStartWith('/');
})->with(['deploy-uat.yml', 'deploy-prod.yml']);
