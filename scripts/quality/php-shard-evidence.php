<?php

declare(strict_types=1);

use SebastianBergmann\CodeCoverage\Report\Facade;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** @return list<string> */
function shardSourceFiles(string $root): array
{
    $config = new DOMDocument;
    if (! $config->load($root.'/phpunit.xml', LIBXML_NONET)) {
        throw new RuntimeException('Cannot read the authoritative PHPUnit configuration.');
    }
    $xpath = new DOMXPath($config);
    if ($xpath->evaluate('count(/phpunit/source/include/*)') !== 1.0
        || trim($xpath->evaluate('string(/phpunit/source/include/directory)')) !== 'app'
        || $xpath->evaluate('count(/phpunit/source/exclude/*)') !== 0.0
        || in_array($xpath->evaluate('string(/phpunit/coverage/@includeUncoveredFiles)'), ['false', '0'], true)) {
        throw new RuntimeException('Coverage must include the entire app directory, including uncovered files.');
    }

    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app', FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = substr($file->getPathname(), strlen($root.'/app/'));
        }
    }
    sort($files);
    if ($files === []) {
        throw new RuntimeException('The source inventory is empty.');
    }

    return $files;
}

/** @return array<string, string> */
function shardSourceHashes(string $root): array
{
    $hashes = [];
    foreach (shardSourceFiles($root) as $file) {
        $hash = hash_file('sha256', $root.'/app/'.$file);
        if ($hash === false) {
            throw new RuntimeException('Cannot hash source file: '.$file);
        }
        $hashes[$file] = $hash;
    }

    return $hashes;
}

/** @return list<string> */
function shardExpectedTests(string $catalog): array
{
    $json = file_get_contents($catalog);
    if ($json === false) {
        throw new RuntimeException('Cannot read the complete test catalog.');
    }
    $encoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($encoded) || ! array_is_list($encoded)) {
        throw new RuntimeException('Invalid test catalog.');
    }
    $ids = [];
    foreach ($encoded as $id) {
        if (! is_string($id) || ($decoded = base64_decode($id, true)) === false || base64_encode($decoded) !== $id) {
            throw new RuntimeException('Invalid encoded test identity.');
        }
        $ids[] = $decoded;
    }
    sort($ids);
    if ($ids === [] || in_array('', $ids, true) || count(array_unique($ids)) !== count($ids)) {
        throw new RuntimeException('The complete test catalog must be nonempty and unique.');
    }

    return $ids;
}

try {
    if (! isset($argv) || count($argv) < 5) {
        throw new RuntimeException('Expected record/merge, project root, evidence directory and shard/catalog.');
    }
    [$script, $mode, $root, $directory] = $argv;
    $root = realpath($root) ?: throw new RuntimeException('Invalid project root.');
    foreach (['GITHUB_SHA', 'GITHUB_RUN_ID', 'GITHUB_RUN_ATTEMPT'] as $name) {
        if (! getenv($name)) {
            throw new RuntimeException('Missing evidence identity: '.$name);
        }
    }
    $identity = [
        'sha' => getenv('GITHUB_SHA'),
        'run_id' => getenv('GITHUB_RUN_ID'),
        'run_attempt' => getenv('GITHUB_RUN_ATTEMPT'),
        'php' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
        'config_hash' => hash_file('sha256', $root.'/phpunit.xml'),
        'source_hashes' => shardSourceHashes($root),
        'total' => 4,
    ];

    if ($mode === 'record') {
        $shard = filter_var($argv[4], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4]]);
        $head = new Process(['git', 'rev-parse', 'HEAD'], $root);
        $head->mustRun();
        if ($shard === false || trim($head->getOutput()) !== $identity['sha']) {
            throw new RuntimeException('Invalid shard or checkout identity.');
        }
        file_put_contents($directory.'/identity.json', json_encode([...$identity, 'shard' => $shard], JSON_THROW_ON_ERROR));
        exit(0);
    }
    if ($mode !== 'merge' || ($argv[5] ?? '') !== 'success') {
        throw new RuntimeException('PHP shards did not all succeed; refusing aggregate approval.');
    }

    $expected = shardExpectedTests($argv[4]);
    $directories = glob($directory.'/shard-*', GLOB_ONLYDIR);
    if ($directories === false || count($directories) !== 4) {
        throw new RuntimeException('Exactly four shard artifacts are required.');
    }
    $merged = null;
    $seen = [];
    for ($shard = 1; $shard <= 4; $shard++) {
        $path = $directory.'/shard-'.$shard;
        $metadataJson = file_get_contents($path.'/identity.json');
        if ($metadataJson === false) {
            throw new RuntimeException('Missing shard identity.');
        }
        $metadata = json_decode($metadataJson, true, flags: JSON_THROW_ON_ERROR);
        if ($metadata !== [...$identity, 'shard' => $shard]) {
            throw new RuntimeException('Shard '.$shard.' has mismatched revision, run, configuration or source evidence.');
        }
        $data = (new Unserializer)->unserialize($path.'/coverage.php');
        $files = $data['codeCoverage']->coveredFiles();
        sort($files);
        if ($files !== array_keys($identity['source_hashes'])) {
            throw new RuntimeException('Shard '.$shard.' does not cover the complete source inventory.');
        }
        $executed = file($path.'/executed-tests.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($executed === false || $executed === []) {
            throw new RuntimeException('Shard '.$shard.' has no executed tests.');
        }
        $shardIds = [];
        foreach ($executed as $record) {
            $event = json_decode($record, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($event) || ($event['status'] ?? '') !== 'passed') {
                throw new RuntimeException('A test did not pass.');
            }
            $encodedId = $event['id'] ?? null;
            $id = is_string($encodedId) ? base64_decode($encodedId, true) : false;
            if ($id === false || $id === '' || base64_encode($id) !== $encodedId || isset($seen[$id])) {
                throw new RuntimeException('Duplicate or invalid executed test identity.');
            }
            $seen[$id] = true;
            $shardIds[$id] = true;
        }
        foreach ($data['testResults'] as $id => $result) {
            if (! isset($shardIds[$id]) || $result['status'] !== 'success') {
                throw new RuntimeException('Coverage contains a test that did not succeed in this shard.');
            }
        }
        if ($merged === null) {
            $merged = $data;
        } else {
            $merged['codeCoverage']->merge($data['codeCoverage']);
            $merged['testResults'] += $data['testResults'];
        }
    }
    $actual = array_keys($seen);
    sort($actual);
    if ($actual !== $expected) {
        throw new RuntimeException('Executed tests do not match the complete catalog: '.count(array_diff($expected, $actual)).' missing, '.count(array_diff($actual, $expected)).' unexpected.');
    }

    // Rebase serialized relative paths onto this checkout before rendering.
    foreach ($merged['codeCoverage']->coveredFiles() as $file) {
        $merged['codeCoverage']->renameFile($file, $root.'/app/'.$file);
    }
    $merged['basePath'] = '';
    $report = Facade::fromSerializedData($merged);
    $report->renderClover($directory.'/clover.xml');
    $report->renderText($directory.'/coverage.txt', null, true, true);
    $summary = $report->summary();
    if ($summary->numberOfExecutableLines() === 0
        || $summary->numberOfExecutedLines() !== $summary->numberOfExecutableLines()) {
        throw new RuntimeException(sprintf('Combined coverage below 100%%: %d/%d executable lines.', $summary->numberOfExecutedLines(), $summary->numberOfExecutableLines()));
    }
    file_put_contents($directory.'/tested-sha.txt', $identity['sha']."\n");
    file_put_contents($directory.'/runtime.txt', PHP_VERSION."\n");
    file_put_contents($directory.'/summary.json', json_encode([
        ...$identity,
        'tests' => count($actual),
        'executable_lines' => $summary->numberOfExecutableLines(),
        'covered_lines' => $summary->numberOfExecutedLines(),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    printf("All %d tests accounted for exactly once across four shards. Combined coverage: 100%% (%d/%d lines).\n", count($actual), $summary->numberOfExecutedLines(), $summary->numberOfExecutableLines());
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
