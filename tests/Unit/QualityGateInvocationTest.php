<?php

/*
 * A gate that cannot run is not a gate. PHPStan needs more memory than the
 * 128M a stock php.ini grants, and it dies with an unhelpful worker crash when
 * it runs out, so every invocation has to carry its own limit. CI never caught
 * this because setup-php runs with no limit at all.
 */

$root = dirname(__DIR__, 2);

test('the types:check script runs PHPStan with a memory limit', function () use ($root) {
    /** @var array{scripts: array<string, list<string>>} $composer */
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $commands = array_filter(
        $composer['scripts']['types:check'],
        fn (string $command): bool => str_contains($command, 'phpstan'),
    );

    expect($commands)->not->toBeEmpty();

    foreach ($commands as $command) {
        expect($command)->toContain('--memory-limit=');
    }
});

test('the negative control harness runs PHPStan with a memory limit', function () use ($root) {
    $lines = explode("\n", (string) file_get_contents($root.'/scripts/quality/verify-negative-controls.sh'));

    $invocations = array_filter(
        $lines,
        fn (string $line): bool => str_contains($line, 'vendor/bin/phpstan analyse'),
    );

    expect($invocations)->not->toBeEmpty();

    foreach ($invocations as $invocation) {
        expect($invocation)->toContain('--memory-limit=');
    }
});
