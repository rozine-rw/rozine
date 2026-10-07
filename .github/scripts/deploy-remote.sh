#!/usr/bin/env bash
#
# Remote build/migrate/reload step, shared by the staging and production
# deployments. Piped to the server over `ssh bash -s` so the text arrives
# verbatim: nothing here is expanded by a local or intermediate shell, and the
# old "no apostrophes anywhere in this block" hazard is gone.
#
# Usage: ssh <host> 'bash -s -- <app-root> <uat|production>' < deploy-remote.sh

set -euo pipefail

APP_ROOT="${1:?app root is required}"
EXPECTED_PROFILE="${2:?expected environment profile is required}"

case "${EXPECTED_PROFILE}" in
  uat|production) ;;
  *) echo "Deployment refused: expected profile must be uat or production." >&2; exit 1 ;;
esac

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

# Read the server's actual environment, not a previous release's config cache.
# Check before cache/database/queue operations can touch external state.
"${PHP_BIN}" artisan config:clear
"${PHP_BIN}" artisan isolation:check --expect="${EXPECTED_PROFILE}" --no-interaction

# The uat profile keeps its cache, sessions, uploads, logs and compiled views
# under storage/isolated/uat, and the runtime never creates those folders, so a
# fresh host answers every page with a 500. Create them once the target is
# confirmed. The web server's group comes from storage/, which the host's
# provisioning owns (deploy:www-data on staging); each folder takes that group,
# group-writable and setgid, so files keep it whichever account writes them.
# When storage/ is missing or this account cannot hand a folder to that group,
# the deploy stops here rather than leave PHP-FPM unable to write.
if [ "${EXPECTED_PROFILE}" = "uat" ]; then
  if [ ! -d storage ]; then
    echo "Deployment refused: storage/ is not provisioned on this host." >&2
    exit 1
  fi
  runtime_group="$(stat -L -c %g storage)"
  for directory in cache sessions private public logs views; do
    folder="storage/isolated/${EXPECTED_PROFILE}/${directory}"
    if ! { mkdir -p "${folder}" && chgrp "${runtime_group}" "${folder}" && chmod 2775 "${folder}"; }; then
      echo "Deployment refused: ${folder} cannot be given the web server group ${runtime_group} with mode 2775." >&2
      exit 1
    fi
  done
fi

# Wayfinder builds the typed client from the Laravel route list during the
# asset build, and Laravel answers that list from the route cache left by the
# previous run. Drop stale caches first, or a release that adds a route builds
# a client without it.
"${PHP_BIN}" artisan optimize:clear

npm ci --no-audit --no-fund || npm install --no-audit --no-fund
npm run build

"${PHP_BIN}" artisan migrate --force
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan isolation:check --expect="${EXPECTED_PROFILE}" --no-interaction

# The isolation layer points public/storage at storage/isolated/uat/public, the
# only target isolation:check accepts, so an existing link is already right.
if [ "${EXPECTED_PROFILE}" = "uat" ] && [ ! -L public/storage ]; then
  "${PHP_BIN}" artisan storage:link --no-interaction
fi

"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan queue:restart

echo "Deployment complete under PHP ${php_version}."
