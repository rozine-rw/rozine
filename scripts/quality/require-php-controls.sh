#!/usr/bin/env bash
# Keep the stable required check red for failed, cancelled, missing or skipped groups.
set -euo pipefail
if [ "${1:-}" != success ]; then
  echo "::error::PHP negative-control groups did not all succeed (${1:-missing})"
  exit 1
fi
echo 'All PHP negative-control groups succeeded.'
