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
RUNTIME_USER="${3:-}"

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
  if [ ! -d storage ] || [ -L storage ]; then
    echo "Deployment refused: storage/ is not provisioned on this host." >&2
    exit 1
  fi
  # Supplied by reviewed provisioning, never inferred from the deploy account.
  # The operator must verify this identity against the staging PHP-FPM pool.
  if [[ ! "${RUNTIME_USER}" =~ ^[a-z_][a-z0-9_-]*$ ]] ||
     ! runtime_uid="$(id -u "${RUNTIME_USER}")" ||
     [ "${runtime_uid}" = "0" ] || [ "${runtime_uid}" = "$(id -u)" ]; then
    echo "Deployment refused: an explicit distinct non-root UAT runtime identity is required." >&2
    exit 1
  fi
  command -v sudo >/dev/null || { echo "Deployment refused: runtime access cannot be checked." >&2; exit 1; }
  runtime_group="$(stat -L -c %g storage)"
  runtime_groups="$(id -G "${RUNTIME_USER}")"
  if [[ " ${runtime_groups} " != *" ${runtime_group} "* ]]; then
    echo "Deployment refused: runtime identity is outside the provisioned storage group." >&2
    exit 1
  fi
  # Probe under the actual runtime identity; this includes traversal through
  # every ancestor and honors ACLs. These read-only checks grant no privileges.
  for ancestor in . storage storage/isolated storage/isolated/uat; do
    if [ -L "${ancestor}" ] || { [ -e "${ancestor}" ] &&
       ! sudo -n -u "${RUNTIME_USER}" -- /usr/bin/test -x "${PWD}/${ancestor}"; }; then
      echo "Deployment refused: runtime cannot traverse the isolated storage ancestors." >&2
      exit 1
    fi
  done
  for directory in cache sessions private public logs views; do
    folder="storage/isolated/${EXPECTED_PROFILE}/${directory}"
    if [ -L "${folder}" ] || { [ -e "${folder}" ] && [ ! -d "${folder}" ]; }; then
      echo "Deployment refused: isolated storage is not a real directory." >&2
      exit 1
    fi
    if [ -d "${folder}" ]; then
      # Preserve existing ownership, group and mode; a provisioning defect is
      # a separate operator decision, not permission to relabel existing data.
      if [ "$(stat -c %g "${folder}")" != "${runtime_group}" ] ||
         [ $(( 8#$(stat -c %a "${folder}") & 02000 )) -eq 0 ]; then
        echo "Deployment refused: existing isolated storage needs approved group provisioning." >&2
        exit 1
      fi
    elif ! { (umask 0002; mkdir -p "${folder}") && chgrp "${runtime_group}" "${folder}" && chmod 2775 "${folder}"; }; then
      echo "Deployment refused: ${folder} cannot be given the web server group ${runtime_group} with mode 2775." >&2
      exit 1
    fi
    if ! /usr/bin/test -w "${folder}" || ! /usr/bin/test -x "${folder}" ||
       ! sudo -n -u "${RUNTIME_USER}" -- /usr/bin/test -x "${PWD}/${folder}" ||
       ! sudo -n -u "${RUNTIME_USER}" -- /usr/bin/test -w "${PWD}/${folder}"; then
      echo "Deployment refused: deploy and runtime identities need isolated storage write and traversal access." >&2
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
if [ "${EXPECTED_PROFILE}" = "uat" ]; then
  # Compiled views must stay readable by the other approved writer even when
  # the caller uses a private umask; limit this change to isolated view output.
  (umask 0007; "${PHP_BIN}" artisan view:cache)
else
  "${PHP_BIN}" artisan view:cache
fi
"${PHP_BIN}" artisan queue:restart

echo "Deployment complete under PHP ${php_version}."
