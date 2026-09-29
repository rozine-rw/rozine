---
name: local-test-tooling
description: "How to rebuild the local Rozine test tooling after the session scratchpad is wiped: playwright-cli wrapper, private Postgres cluster, manifest regen"
metadata:
  type: reference
---

Session scratchpads are wiped between sessions (2026-09-27 lost the playwright wrapper, regen script and watcher mid-work). Rebuild rather than hunt for old copies:

- **playwright-cli (browser journeys, `tests/Browser/*`)**: a wrapper script containing `exec npx -y @playwright/cli@0.1.21 "$@"`; run journeys with `PLAYWRIGHT_CLI=<wrapper> TMPDIR=/tmp/ DB_PORT=<port> PAO_DISABLE=1 php -d memory_limit=2G vendor/bin/pest -c phpunit.pgsql.xml --no-tia tests/Browser/<File>.php`. Needs `.env` copied from `~/Documents/projects/rozine/.env` and a fresh `npm run build`. Shots land in `output/playwright/<journey>/`.
- **Private Postgres**: `initdb -D <dir> -U postgres -A trust`; `pg_ctl -D <dir> -o "-p <port> -k ''" start`; seed each non-comment line of `.github/scripts/prepare-test-databases.sql` with its own `psql -h <redacted-ip> -p <port> -U postgres -c`. Never 5432/5433/5439 ([[shared-scratch-postgres]]).
- **Coverage manifests**: regenerate `config/client-{source,risk}-manifest.json` from `git ls-files resources/js` (.ts/.tsx): generated = routes/actions/wayfinder, declaration-only = previous list + `.d.ts` + types-only modules (e.g. `types/settlement.ts`), the rest authored; `coverageSetSha256` = sha256 of sorted authored paths joined by `\n` plus trailing `\n`; risk manifest carries sha256 of the source manifest text.
- **Preview server**: `.claude/launch.json` config `rozine-dev-local` = `php ~/Documents/projects/rozine/artisan serve --port=8126` (main checkout, which now tracks `dev`). Don't point configs at scratch worktrees.
- **pcov** isn't loaded by default in Herd PHP; load with `-d extension=.../pcov.so` for local coverage.
- **Run PHPStan on new PHP test files, browser journeys included, before pushing** (2026-09-28, #153): the coverage negative-control group runs PHPStan over `tests/` and fails on any error, even for a tests-only PR. `$this->artisan()` returns `PendingCommand|int`, so call `Artisan::call()` and assert its exit code instead. Use `TMPDIR=<scratch>/tmp PAO_DISABLE=1 vendor/bin/phpstan analyse --memory-limit=1G`.
- **Format/lint with Vite+, not Prettier/ESLint directly** (2026-09-28): `npx vp fmt <files>` and `npx vp lint <files>`. `npx prettier --write` rewrote whole files, and `npx eslint` has no config. Grep lint output for `error`: warnings bury errors, and `tail` hid a `no-node-access` error (`nextElementSibling` in a test) that failed #160's hosted gate.
- **Fast-lane merge helper**: `fastmerge.sh` must match the per-group `PHP negative controls (<group>)` jobs from #155. The aggregate `PHP gate negative controls` only reports late.
- **Don't symlink `vendor` into a new worktree** (2026-09-28, #169). Wayfinder writes each resolved vendor path into the `@see` lines of the generated routes, so `ci:web`'s generated-client check fails. A `node_modules` symlink is fine; `vendor` needs a real `composer install`.
- **Run `inventory:baseline` against a migrated PostgreSQL test DB, never the default local DB** (2026-09-28, #172). Otherwise it reports false drift and a regeneration deletes schema sections. Use `DB_CONNECTION=pgsql DB_HOST=<redacted-ip> DB_PORT=<private> DB_DATABASE=rozine_test DB_USERNAME=rozine_test DB_PASSWORD=rozine_test DB_URL= php artisan migrate:fresh --force` first, then `inventory:baseline --check`.
- **A PR that conflicts with its base gets no `pull_request` CI runs** ("no checks reported"). Merge the base in, resolve (for manifests, take the base side and regenerate), and push.
- **Never run `git checkout` (or pull/merge/reset) in a worktree a background agent is using** (2026-09-28). I detached S3-D's HEAD by accident; it was harmless only because it was the same commit. To inspect an agent's branch, use read-only commands: `git -C <wt> fetch`, `git show origin/<branch>:<path>`, `git log origin/<branch>`.
