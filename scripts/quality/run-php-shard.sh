#!/usr/bin/env bash
set -euo pipefail
case "${1:-}" in 1|2|3|4) ;; *) echo 'Expected a shard index from 1 to 4.' >&2; exit 64 ;; esac
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
OUTPUT="coverage/php/shard-$1"
mkdir -p "$OUTPUT"
php scripts/quality/php-shard-evidence.php record "$ROOT" "$OUTPUT" "$1"
export PHP_SHARD_TEST_IDS="$OUTPUT/executed-tests.jsonl"
php vendor/bin/pest --bootstrap=scripts/quality/php-shard-bootstrap.php --ci --no-tia --shard="$1/4" \
  --coverage-php="$OUTPUT/coverage.php" --log-junit="$OUTPUT/junit.xml" \
  --log-events-text="$OUTPUT/events.log" \
  --fail-on-empty-test-suite --fail-on-skipped --fail-on-incomplete --fail-on-risky --compact
