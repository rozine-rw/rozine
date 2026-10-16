<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

it('records passes only from the controlling runner while preserving fork failures', function (): void {
    $root = dirname(__DIR__, 2);
    $temporary = sys_get_temp_dir().'/rozine-shard-origin-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $files->makeDirectory($temporary, 0700, true);
    file_put_contents($temporary.'/ForkOriginTest.php', <<<'PHP'
<?php
class ForkOriginTest extends PHPUnit\Framework\TestCase
{
    public function testFork(): void
    {
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            if (getenv('SHARD_FIXTURE_CHILD_FAILURE') === '1') {
                self::fail('Planted child failure.');
            }
            exit(0);
        }
        pcntl_waitpid($pid, $status);
        self::assertTrue(pcntl_wifexited($status));
        self::assertSame(0, pcntl_wexitstatus($status), 'Parent retains child failure.');
    }
    public function testAfterOne(): void { self::assertTrue(true); }
    public function testAfterTwo(): void { self::assertTrue(true); }
}
PHP);
    try {
        foreach ([false, true] as $childFails) {
            $destination = $temporary.'/executed.jsonl';
            $process = new Process(['php', $root.'/vendor/bin/phpunit', '--no-configuration',
                '--bootstrap', $root.'/scripts/quality/php-shard-bootstrap.php', $temporary.'/ForkOriginTest.php'], $root,
                ['PHP_SHARD_TEST_IDS' => $destination, 'PHP_SHARD_CATALOG' => false, 'SHARD_FIXTURE_CHILD_FAILURE' => $childFails ? '1' : '0']);
            $process->setTimeout(30)->run();
            $records = file($destination, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($records === false) {
                throw new RuntimeException('Cannot read fork evidence.');
            }
            $ids = array_map(fn (string $line): string => base64_decode(json_decode($line, true, flags: JSON_THROW_ON_ERROR)['id'], true)
                ?: throw new RuntimeException('Invalid fork evidence identity.'), $records);
            expect($process->getExitCode())->toBe($childFails ? 1 : 0)
                ->and($ids)->toBe($childFails
                    ? ['ForkOriginTest::testAfterOne', 'ForkOriginTest::testAfterTwo']
                    : ['ForkOriginTest::testFork', 'ForkOriginTest::testAfterOne', 'ForkOriginTest::testAfterTwo']);
            if ($childFails) {
                expect($process->getOutput())->toContain('Parent retains child failure.');
            }
        }
    } finally {
        $files->deleteDirectory($temporary);
    }
});

it('keeps native time-balanced partitions deterministic and complete as classes change', function (): void {
    $root = dirname(__DIR__, 2);
    $temporary = sys_get_temp_dir().'/rozine-shard-balance-'.bin2hex(random_bytes(8));
    $testDirectory = str_repeat('../', substr_count(trim($root, '/'), '/') + 1).ltrim($temporary.'/tests', '/');
    $files = new Filesystem;
    $files->makeDirectory($temporary.'/tests/.pest', 0700, true);
    file_put_contents($temporary.'/phpunit.xml', '<phpunit><testsuites><testsuite name="fixture"><directory>tests</directory></testsuite></testsuites></phpunit>');
    $addClass = function (int $index) use ($temporary): void {
        file_put_contents($temporary.'/tests/Balance'.$index.'Test.php', '<?php
namespace ShardFixtures;
class Balance'.$index.'Test extends \\PHPUnit\\Framework\\TestCase {
    public function testFirst(): void { self::assertTrue(true); }
    public function testSecond(): void { self::assertTrue(true); }
}');
    };
    foreach (range(1, 6) as $index) {
        $addClass($index);
    }
    $timings = ['ShardFixtures\\Balance1Test' => 40.0, 'ShardFixtures\\Balance2Test' => 20.0,
        'ShardFixtures\\Balance3Test' => 10.0, 'ShardFixtures\\Balance4Test' => 8.0, 'ShardFixtures\\Balance5Test' => 5.0];
    $catalog = function (?int $shard = null) use ($root, $temporary, $testDirectory): array {
        $destination = $temporary.'/catalog.json';
        $process = new Process(['php', $root.'/vendor/bin/pest', '--configuration', $temporary.'/phpunit.xml',
            '--test-directory='.$testDirectory,
            '--bootstrap', $root.'/scripts/quality/php-shard-bootstrap.php', '--ci', '--no-tia',
            ...($shard === null ? ['--list-tests'] : ['--shard='.$shard.'/4'])], $root,
            ['PHP_SHARD_TEST_IDS' => $shard === null ? false : $destination, 'PHP_SHARD_CATALOG' => $shard === null ? $destination : false]);
        $process->setTimeout(30)->mustRun();

        if ($shard === null) {
            $contents = file_get_contents($destination);
            if ($contents === false) {
                throw new RuntimeException('Cannot read the fixture catalog.');
            }

            return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        }
        $records = file($destination, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($records === false) {
            throw new RuntimeException('Cannot read the fixture execution.');
        }

        return array_map(fn (string $line): string => json_decode($line, true, flags: JSON_THROW_ON_ERROR)['id'], $records);
    };
    try {
        foreach ([false, true] as $changedClasses) {
            if ($changedClasses) {
                $addClass(7);
                unset($timings['ShardFixtures\\Balance2Test']);
            }
            file_put_contents($temporary.'/tests/.pest/shards.json', json_encode(['timings' => $timings], JSON_THROW_ON_ERROR));
            $expected = $catalog();
            expect($expected)->toHaveCount($changedClasses ? 14 : 12);
            $previous = null;
            foreach (range(1, 2) as $attempt) {
                $partitions = array_map(fn (int $shard): array => $catalog($shard), range(1, 4));
                foreach ($partitions as $partition) {
                    expect($partition)->toBe(array_values(array_intersect($expected, $partition)));
                }
                $actual = array_merge(...$partitions);
                expect(count(array_unique($actual)))->toBe(count($expected));
                sort($actual);
                $sortedExpected = $expected;
                sort($sortedExpected);
                expect($actual)->toBe($sortedExpected);
                if ($previous !== null) {
                    expect($partitions)->toBe($previous);
                }
                $previous = $partitions;
            }
        }
    } finally {
        $files->deleteDirectory($temporary);
    }
});

it('merges native coverage only after every test and shard is accounted for', function (): void {
    $root = dirname(__DIR__, 2);
    $temporary = sys_get_temp_dir().'/rozine-shards-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $files->makeDirectory($temporary.'/app', 0700, true);
    $files->makeDirectory($temporary.'/tests', 0700, true);
    $source = <<<'PHP'
<?php
class ShardSubject
{
    public static function pick(int $value): string
    {
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        $value += 0;
        return match ($value) {
            1 => 'one',
            2 => 'two',
            3 => 'three',
            default => 'four',
        };
    }
}
PHP;
    file_put_contents($temporary.'/app/ShardSubject.php', $source);
    file_put_contents($temporary.'/phpunit.xml', '<phpunit><testsuites><testsuite name="fixture"><directory>tests</directory></testsuite></testsuites><source><include><directory>app</directory></include></source></phpunit>');
    $environment = ['GITHUB_SHA' => str_repeat('a', 40), 'GITHUB_RUN_ID' => '123', 'GITHUB_RUN_ATTEMPT' => '2'];
    $identity = [
        'sha' => $environment['GITHUB_SHA'], 'run_id' => '123', 'run_attempt' => '2',
        'php' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
        'config_hash' => hash_file('sha256', $temporary.'/phpunit.xml'),
        'source_hashes' => ['ShardSubject.php' => hash('sha256', $source)], 'total' => 4,
    ];
    $driver = new Process(['php', '-d', 'pcov.enabled=1', '-r',
        'require "vendor/autoload.php"; exit(\\Pest\\Support\\Coverage::isAvailable() ? 0 : 1);'], $root);
    $driver->run();
    try {
        foreach (range(1, 4) as $index) {
            file_put_contents($temporary.'/tests/Shard'.$index.'Test.php', '<?php
require_once __DIR__."/../app/ShardSubject.php";
class Shard'.$index.'Test extends PHPUnit\\Framework\\TestCase {
    #[PHPUnit\\Framework\\Attributes\\DataProvider("values")]
    public function testBranch(int $value): void { self::assertIsString(ShardSubject::pick($value)); }
    public static function values(): array { return ["raw-\\xff" => ['.$index.']]; }
}');
            $output = $temporary.'/evidence/shard-'.$index;
            $files->makeDirectory($output, 0700, true);
            $process = new Process(['php', '-d', 'pcov.enabled=1', '-d', 'pcov.directory='.$temporary.'/app', $root.'/vendor/bin/phpunit',
                '--configuration', $temporary.'/phpunit.xml', '--bootstrap', $root.'/scripts/quality/php-shard-bootstrap.php',
                ...($driver->isSuccessful() ? ['--coverage-php='.$output.'/coverage.php'] : []),
                '--log-junit='.$output.'/junit.xml',
                $temporary.'/tests/Shard'.$index.'Test.php'], $temporary, ['PHP_SHARD_TEST_IDS' => $output.'/executed-tests.jsonl']);
            $process->setTimeout(30)->mustRun();
            file_put_contents($output.'/identity.json', json_encode([...$identity, 'shard' => $index], JSON_THROW_ON_ERROR));
        }
        $catalog = new Process(['php', $root.'/vendor/bin/phpunit', '--configuration', $temporary.'/phpunit.xml',
            '--bootstrap', $root.'/scripts/quality/php-shard-bootstrap.php', '--list-tests'], $temporary,
            ['PHP_SHARD_TEST_IDS' => false, 'PHP_SHARD_CATALOG' => $temporary.'/all.json']);
        $catalog->mustRun();
        $merge = function (string $result = 'success') use ($root, $temporary, $environment): Process {
            $process = new Process(['php', $root.'/scripts/quality/php-shard-evidence.php', 'merge', $temporary,
                $temporary.'/evidence', $temporary.'/all.json', $result], $root, $environment);
            $process->run();

            return $process;
        };
        if (! $driver->isSuccessful()) {
            $refused = $merge();
            expect($refused->getExitCode())->toBe(1)
                ->and($refused->getErrorOutput())->toContain('Cannot open file:');

            return;
        }
        $green = $merge();
        expect($green->getOutput().$green->getErrorOutput())->toContain('All 4 tests accounted for exactly once')
            ->and($green->getExitCode())->toBe(0)
            ->and(file_get_contents($temporary.'/evidence/tested-sha.txt'))->toBe(str_repeat('a', 40)."\n");

        // Binary-input dataset names can make diagnostic JUnit XML invalid UTF-8.
        file_put_contents($temporary.'/evidence/shard-1/junit.xml', "invalid XML \xff");
        expect($merge()->getExitCode())->toBe(0);

        foreach (['failure', 'cancelled', 'skipped', '', 'unknown'] as $result) {
            expect($merge($result)->getExitCode())->toBe(1);
        }
        $baseline = $temporary.'/baseline';
        $files->copyDirectory($temporary.'/evidence', $baseline);
        foreach (['missing shard', 'stale run', 'wrong revision', 'wrong source', 'wrong shard', 'corrupt coverage',
            'missing coverage', 'duplicate test', 'missing test', 'unexpected test', 'failed test', 'skipped test',
            'uncovered line', 'missing source file', 'empty catalog', 'extra shard'] as $scenario) {
            $files->deleteDirectory($temporary.'/evidence');
            $files->copyDirectory($baseline, $temporary.'/evidence');
            $shard = $temporary.'/evidence/shard-4';
            $metadata = [...$identity, 'shard' => 4];
            if ($scenario === 'missing shard') {
                $files->deleteDirectory($shard);
            } elseif (in_array($scenario, ['stale run', 'wrong revision', 'wrong source', 'wrong shard'], true)) {
                $key = match ($scenario) {
                    'stale run' => 'run_attempt', 'wrong revision' => 'sha', 'wrong source' => 'source_hashes', default => 'shard',
                };
                $metadata[$key] = 'wrong';
                file_put_contents($shard.'/identity.json', json_encode($metadata, JSON_THROW_ON_ERROR));
            } elseif ($scenario === 'missing coverage') {
                unlink($shard.'/coverage.php');
            } elseif ($scenario === 'corrupt coverage') {
                file_put_contents($shard.'/coverage.php', 'invalid');
            } elseif ($scenario === 'duplicate test') {
                copy($temporary.'/evidence/shard-1/executed-tests.jsonl', $shard.'/executed-tests.jsonl');
            } elseif ($scenario === 'missing test') {
                file_put_contents($shard.'/executed-tests.jsonl', '');
            } elseif ($scenario === 'unexpected test') {
                file_put_contents($shard.'/executed-tests.jsonl', '"unknown"'."\n");
            } elseif ($scenario === 'failed test' || $scenario === 'skipped test') {
                $event = json_decode(trim((string) file_get_contents($shard.'/executed-tests.jsonl')), true, flags: JSON_THROW_ON_ERROR);
                $event['status'] = $scenario === 'failed test' ? 'failed' : 'skipped';
                file_put_contents($shard.'/executed-tests.jsonl', json_encode($event, JSON_THROW_ON_ERROR)."\n");
            } elseif ($scenario === 'empty catalog') {
                file_put_contents($temporary.'/all.json', '[]');
            } elseif ($scenario === 'extra shard') {
                $files->copyDirectory($shard, $temporary.'/evidence/shard-5');
            } else {
                foreach (range(1, 4) as $index) {
                    $coverageFile = $temporary.'/evidence/shard-'.$index.'/coverage.php';
                    $coverage = (new Unserializer)->unserialize($coverageFile);
                    $lines = $coverage['codeCoverage']->lineCoverage();
                    if ($scenario === 'missing source file') {
                        $lines = [];
                    } else {
                        foreach ($lines['ShardSubject.php'] as $line => $hits) {
                            if ($hits !== null) {
                                $lines['ShardSubject.php'][$line] = [];
                                break;
                            }
                        }
                    }
                    $coverage['codeCoverage']->setLineCoverage($lines);
                    file_put_contents($coverageFile, "<?php // phpunit/php-code-coverage serialization format 1\nreturn unserialize(".var_export(serialize($coverage), true).");\n");
                }
            }
            $refused = $merge();
            expect($refused->getExitCode())->toBe(1, $scenario.': '.$refused->getOutput().$refused->getErrorOutput());
            if ($scenario === 'uncovered line') {
                expect($refused->getErrorOutput())->toContain('Combined coverage below 100%:');
            }
            $catalog->mustRun();
        }
        $files->deleteDirectory($temporary.'/evidence');
        $files->copyDirectory($baseline, $temporary.'/evidence');
        expect($merge()->getExitCode())->toBe(0);
    } finally {
        $files->deleteDirectory($temporary);
    }
});

it('retains the stable required gate and waits for all four isolated jobs', function (): void {
    $root = dirname(__DIR__, 2);
    $jobs = Yaml::parseFile($root.'/.github/workflows/tests.yml')['jobs'];
    expect($jobs['php-shards']['strategy']['matrix']['shard'])->toBe([1, 2, 3, 4])
        ->and($jobs['php-shards']['strategy']['fail-fast'])->toBeFalse()
        ->and($jobs['ci']['name'])->toBe('PHP ${{ matrix.php-version }} quality gate')
        ->and($jobs['ci']['strategy']['matrix']['php-version'])->toBe(['8.5'])
        ->and($jobs['ci']['needs'])->toBe(['plan', 'php-shards'])
        ->and($jobs['ci']['if'])->toBe("\${{ always() && needs.plan.outputs.full == 'true' }}")
        ->and($jobs['ci']['steps'][0]['env']['SHARD_RESULT'])->toBe('${{ needs.php-shards.result }}');
    $steps = array_column($jobs['ci']['steps'], null, 'name');
    expect($steps['Run authoritative PHP static gates']['run'])->toBe('composer ci:check:php:static')
        ->and($steps['Download this run\'s four shard artifacts']['with']['pattern'])->toBe('php-shard-*-${{ github.run_id }}-${{ github.run_attempt }}')
        ->and($steps['Require complete test execution and 100% combined coverage']['env']['SHARD_RESULT'])->toBe('${{ needs.php-shards.result }}')
        ->and($steps['Require complete test execution and 100% combined coverage']['run'])->toBe('php scripts/quality/php-shard-evidence.php merge "$PWD" coverage/php coverage/php/expected-tests.json "$SHARD_RESULT"')
        ->and($steps['Enumerate the complete non-TIA test catalog']['env']['PHP_SHARD_CATALOG'])->toBe('coverage/php/expected-tests.json')
        ->and($steps['Enumerate the complete non-TIA test catalog']['run'])->toContain('--bootstrap=scripts/quality/php-shard-bootstrap.php --ci --no-tia --list-tests')
        ->and($steps["Download this run's four shard artifacts"]['uses'])->toStartWith('actions/download-artifact@')
        ->and($steps["Download this run's four shard artifacts"]['with']['path'])->toBe('coverage/shards')
        ->and($steps['Collect shard directories']['run'])->toContain('for shard in 1 2 3 4;', 'mv "coverage/shards/php-shard-$shard-$GITHUB_RUN_ID-$GITHUB_RUN_ATTEMPT" "coverage/php/shard-$shard"');
    $order = array_flip(array_keys($steps));
    expect($order["Download this run's four shard artifacts"])->toBeLessThan($order['Collect shard directories'])
        ->and($order['Collect shard directories'])->toBeLessThan($order['Require complete test execution and 100% combined coverage']);
    $controls = array_column($jobs['negative-control-groups']['steps'], null, 'name');
    expect($controls['Prove the sharded coverage gate fails closed']['if'])->toBe("\${{ matrix.group == 'coverage' }}")
        ->and($controls['Prove the sharded coverage gate fails closed']['run'])->toBe('php vendor/bin/pest --ci --no-tia tests/Unit/PhpShardGateTest.php --compact');
    $command = file_get_contents($root.'/scripts/quality/run-php-shard.sh');
    expect($command)->toContain('--no-tia', '--shard="$1/4"', '--coverage-php=', '--fail-on-empty-test-suite', '--fail-on-skipped', '--fail-on-incomplete', '--fail-on-risky');
});
