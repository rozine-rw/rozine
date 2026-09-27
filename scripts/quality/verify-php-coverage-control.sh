#!/usr/bin/env bash
# Exercise the installed Pest coverage gate with a known-green isolated suite.
# The normal PHP job still runs the entire application with --coverage --min=100.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PROBE="$(mktemp -d "${ROOT}/storage/framework/cache/coverage-control.XXXXXX")"
trap 'rm -rf "${PROBE}"' EXIT

php -d pcov.enabled=1 -r 'require $argv[1]."/vendor/autoload.php"; exit(\Pest\Support\Coverage::isAvailable() ? 0 : 1);' "${ROOT}" || {
  echo 'coverage control requires an active coverage driver' >&2
  exit 1
}

php /dev/stdin "${ROOT}" "${PROBE}" <<'PHP'
<?php

declare(strict_types=1);

$root = $argv[1];
$probe = $argv[2];
$original = new DOMDocument;
$original->load($root.'/phpunit.xml', LIBXML_NONET);
$xpath = new DOMXPath($original);
$source = $xpath->query('/phpunit/source')->item(0);
$directories = $xpath->query('/phpunit/source/include/directory');
if ($source === null || $directories->length !== 1 || trim($directories->item(0)->textContent) !== 'app'
    || $xpath->query('/phpunit/source/exclude/* | /phpunit/source/include/file')->length !== 0
    || in_array($xpath->evaluate('string(/phpunit/coverage/@includeUncoveredFiles)'), ['false', '0'], true)) {
    throw new RuntimeException('The authoritative coverage gate must include all app/ files, including unexecuted files.');
}
$composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$commands = $composer['scripts']['test:php:coverage'];
$gate = array_values(array_filter($commands, fn (string $command): bool => str_contains($command, 'vendor/bin/pest')));
if (count($gate) !== 1 || ! str_contains($gate[0], '--coverage --min=100') || ! str_contains($gate[0], '--no-tia')) {
    throw new RuntimeException('The authoritative Pest gate must retain full non-TIA coverage with --min=100.');
}
mkdir($probe.'/app/Support', 0700, true);
mkdir($probe.'/tests', 0700, true);
$config = new DOMDocument('1.0', 'UTF-8');
$phpunit = $config->appendChild($config->createElement('phpunit'));
$phpunit->setAttribute('bootstrap', $root.'/vendor/autoload.php');
$phpunit->setAttribute('cacheDirectory', $probe.'/cache');
$suites = $phpunit->appendChild($config->createElement('testsuites'));
$suite = $suites->appendChild($config->createElement('testsuite'));
$suite->setAttribute('name', 'coverage-control');
$suite->appendChild($config->createElement('directory', 'tests'));
// Preserve the real app/ inclusion rule, resolved inside this isolated probe.
$phpunit->appendChild($config->importNode($source, true));
$config->save($probe.'/phpunit.xml');
PHP

cat > "${PROBE}/app/Support/Covered.php" <<'PHP'
<?php

declare(strict_types=1);

namespace CoverageControl;

final class Covered
{
    public function value(): int
    {
        return 1;
    }
}
PHP
cat > "${PROBE}/tests/CoverageControlTest.php" <<'PHP'
<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/app/Support/Covered.php';

it('covers the baseline', function (): void {
    expect((new CoverageControl\Covered)->value())->toBe(1);
});
PHP

run_gate() {
  (cd "${PROBE}" && php -d pcov.enabled=1 "${ROOT}/vendor/bin/pest" --ci --no-tia \
    --configuration "${PROBE}/phpunit.xml" --test-directory "${PROBE#"${ROOT}/"}/tests" \
    --coverage --min=100 --coverage-clover="${PROBE}/clover.xml" \
    --log-junit="${PROBE}/junit.xml" --colors=never --compact) > "${PROBE}/$1.log" 2>&1
}

verify_report() {
  php /dev/stdin "${PROBE}" "$1" <<'PHP'
<?php

declare(strict_types=1);

$probe = $argv[1];
$uncovered = $argv[2] === 'uncovered';
$report = simplexml_load_file($probe.'/clover.xml');
$junit = simplexml_load_file($probe.'/junit.xml');
$tests = $junit->xpath('//testcase');
$metrics = $report->project->metrics;
if (count($tests) !== 1 || count($junit->xpath('//failure | //error | //skipped')) !== 0
    || (int) $metrics['statements'] < 1) {
    throw new RuntimeException('The coverage probe must execute its passing test and measure executable lines.');
}
$missing = $report->xpath('//file[contains(@name, "Uncovered.php")]/line[@type="stmt" and @count="0"]');
$complete = (int) $metrics['coveredstatements'] === (int) $metrics['statements'];
if (($uncovered && ($complete || count($missing) === 0)) || (! $uncovered && ! $complete)) {
    throw new RuntimeException('The coverage report did not measure the expected covered/uncovered sentinel.');
}
PHP
}

if ! run_gate baseline; then
  cat "${PROBE}/baseline.log"
  echo 'coverage control baseline was not green' >&2
  exit 1
fi
verify_report covered

cat > "${PROBE}/app/Support/Uncovered.php" <<'PHP'
<?php

declare(strict_types=1);

namespace CoverageControl;

final class Uncovered
{
    public function neverCalled(): int
    {
        return 2;
    }
}
PHP
status=0
run_gate uncovered || status=$?
if [ "${status}" -ne 1 ] || ! grep -qF 'Code coverage below expected' "${PROBE}/uncovered.log" \
  || ! grep -qF '100.0 %' "${PROBE}/uncovered.log"; then
  cat "${PROBE}/uncovered.log"
  echo 'the planted line did not produce the expected 100% coverage refusal' >&2
  exit 1
fi
verify_report uncovered
rm "${PROBE}/app/Support/Uncovered.php"
if ! run_gate recovered; then
  cat "${PROBE}/recovered.log"
  echo 'coverage control did not recover after removing the planted file' >&2
  exit 1
fi
verify_report covered
echo 'coverage control: green baseline, uncovered file rejected at 100%, green recovery'
