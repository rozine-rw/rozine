#!/usr/bin/env bash
#
# Remote build/migrate/reload step, shared by the staging and production
# deployments. Piped to the server over `ssh bash -s` so the text arrives
# verbatim: nothing here is expanded by a local or intermediate shell, and the
# old "no apostrophes anywhere in this block" hazard is gone.
#
# Usage: ssh <host> 'bash -s -- <app-root>' < deploy-remote.sh

set -euo pipefail

APP_ROOT="${1:?app root is required}"

# ---------------------------------------------------------------------------
# D-73 pins PHP 8.5 as the canonical deployment runtime. Resolve it explicitly
# and refuse to deploy under anything else, so a server-side runtime drift
# surfaces here instead of at the first request.
# ---------------------------------------------------------------------------
if command -v php8.5 >/dev/null 2>&1; then
  PHP_BIN="$(command -v php8.5)"
else
  PHP_BIN="$(command -v php)"
fi

php_version="$("${PHP_BIN}" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
if [ "${php_version}" != "8.5" ]; then
  echo "Deployment refused: D-73 pins PHP 8.5 as the deployment runtime." >&2
  echo "Resolved ${PHP_BIN} reports PHP ${php_version}." >&2
  exit 1
fi
echo "PHP runtime: ${PHP_BIN} ($("${PHP_BIN}" -r 'echo PHP_VERSION;'))"

COMPOSER_BIN="$(command -v composer)"

cd "${APP_ROOT}"

"${PHP_BIN}" "${COMPOSER_BIN}" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Wayfinder builds the typed client from the Laravel route list during the
# asset build, and Laravel answers that list from the route cache left by the
# previous run. Drop stale caches first, or a release that adds a route builds
# a client without it.
"${PHP_BIN}" artisan optimize:clear

npm ci --no-audit --no-fund || npm install --no-audit --no-fund
npm run build

"${PHP_BIN}" artisan migrate --force
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan queue:restart

echo "Deployment complete under PHP ${php_version}."
