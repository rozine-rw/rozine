# Phase 0 kickoff evidence

**Status:** In progress

**Started:** 2026-08-24

**Branch:** `feat/phase-0-foundation`

**Baseline commit:** `e9c65ed`

## Goal of the foundation slices

Establish an honest, reproducible PHP and TypeScript/React quality baseline and safe decision boundary before new MVP domain work starts. These slices introduce no domain behavior, schema, money rule, provider, or production brand activation; the new SVG/PNG files are governed source evidence only, while production edits are limited to removing unreachable branches, encoding existing invariants, and making the account-deletion cancel action explicitly non-submitting.

## Completed baseline checks

| Check | Result |
|---|---|
| Composer manifest/lock validation | Green |
| Clean locked Composer/npm installation | Green in a disposable checkout |
| Laravel boot | Laravel 13.23.0 on local PHP 8.5.8 |
| Non-vendor route discovery | Green, 11 routes |
| Platform requirements | Green |
| Full Pest run without TIA | Green, 101 tests / 396 assertions before the architecture suite |
| First-party PHP line coverage | Green, 100.0% over `app/` |
| Pest-aware PHPStan/Larastan | Green, zero errors across application and tests |
| TypeScript/React test foundation | Green, 226 Vitest behavior/policy tests on pinned Node 24.15.0 |
| Client source/risk inventory | Frozen: 101 authored executable, 66 generated, and 6 declaration-only files |
| D-67 policy engine | Green: 101/101 critical authored files at exact 100% lines, statements, functions, and branches |
| npm advisory audit | Green, zero vulnerabilities |
| Composer advisory audit | Green after `league/commonmark` 2.8.3 → 2.10.0; zero advisories |
| ESLint, Prettier, TypeScript | Green |
| Pint and PHPStan/Larastan | Green |
| Production Vite build and Wayfinder drift check | Green in a disposable exact-SHA checkout |

After the Phase 0B/C changes, `composer ci:check:php` is green with 107 tests, 417 assertions, zero Pest-aware PHPStan errors, and 100.0% first-party PHP line coverage. `composer test:php:tia` also records a fresh local graph and passes the same 107-test suite; its replay evidence is acceleration only, not release evidence. The complete client suite passes 226 tests and covers 1,154/1,154 statements, 727/727 branches, 487/487 functions, and 1,134/1,134 lines across all 101 critical authored files. TIA and focused runs remain acceleration evidence only.

`laravel/head` was required and locked but missing from the prior installed `vendor/` tree, which prevented Artisan from booting. `composer install` restored the exact locked package without changing either Composer manifest, and the complete locked dependency set was subsequently proven in a disposable clean checkout.

## Phase 0A implementation

- [x] Make one Composer command authoritative for full `--ci --no-tia --coverage --min=100` PHP evidence.
- [x] Remove TIA state restore/save and `--tia` from normal pull-request/promotion CI.
- [x] Keep the dedicated TIA baseline workflow and enable TIA only for local developer/agent loops.
- [x] Configure the PHP runtime lanes in CI under D-73. **Amended 2026-09-07:** the 8.4 minimum-compatibility lane is removed and CI runs PHP 8.5 alone; hosted evidence is recorded below.
- [x] Pin the clean-build Node runtime to 24.15.0 and build assets before feature tests that render the Vite manifest.
- [x] Add the initial Pest Architecture suite and remove starter placeholder helpers.
- [x] Prove the architecture rule with a temporary Resource-to-calculator violation that fails, then remove it and restore a green six-test architecture suite.
- [x] Make the local/CI Pint check deterministic without a parallel localhost-worker requirement.
- [x] Preserve the six superseded JPEG hashes as historical references; receive and hash Robert's 31 replacement SVGs and 31 matching PNGs; validate SVG XML plus PNG RGBA/alpha, basename, and dimension parity.
- [x] Add Pest's first-party PHPStan plugin, register it with PHPStan, and analyze `tests/`.
- [x] Add the standalone Vitest/React Testing Library/V8 foundation and fail-closed D-66/D-67 enforcement engine.
- [x] Backfill behavior tests until the complete D-67 metric/risk-tier baseline is green.
- [x] Prove clean Composer/npm installation, Laravel boot/routes, production build, and the full gate in a disposable copy with no prior `vendor/`, `node_modules/`, or build output.
- [ ] Add PostgreSQL locking/concurrency evidence for protected financial and secondary-market paths.

## Approved Engineering decisions

- [x] D-72 accepts ADR-0001 as written: the modular-monolith namespaces, one shared application/domain layer, `/api/v1`, shared Eloquent API Resources as the serialization boundary, and the three exact legacy dispositions are approved by Engineering on 2026-08-28.
- [x] D-73, as amended on 2026-09-07, sets PHP 8.5 as the sole supported runtime — minimum, canonical, coverage and deployment — with PostgreSQL as the authoritative locking/concurrency environment. The 8.4 lane is removed, so 8.4 is unsupported rather than untested.
- [x] D-75 pins Node 24.15.0 across local metadata, both normal CI jobs, production builds, and the TIA baseline workflow while retaining npm 10.9.8.
- [x] D-74 assigns the Phase 1 server/API/domain/application/data/security lane and PHP evidence to Aminu, the Inertia React UI/UX/client/PWA/accessibility lane and web evidence to Erastus, and the contract freeze, five integration checkpoints, cross-review, and Alpha acceptance to both.
- [ ] Decision approval does not replace execution evidence: the 2026-08-29 working tree has isolated `PulseController` behind tested Domain/Application/Infrastructure/HTTP boundaries, but the broader module architecture-rule catalog and negative controls, clean exact-SHA evidence, PHP 8.5 deployment pin, hosted results for both runtime lanes, and PostgreSQL financial/secondary race proof remain required before Phase 0 exits.

## Phase 0B quality-contract evidence

- [x] Lock `pestphp/pest-plugin-phpstan` 5.2.0, include `tests/` in analysis, and retain Larastan's Laravel awareness.
- [x] Lock Vitest 4.1.11, V8 coverage, jsdom, React Testing Library, user-event, accessible DOM matchers, and their ESLint rules.
- [x] Pin Node 24.15.0 and npm 10.9.8 in local metadata, both normal CI jobs, and the TIA baseline workflow; the clean pinned-runtime suite passes.
- [x] Freeze exact machine-readable source and risk manifests. Generated and declaration-only exclusions include provenance, owner, approver, rationale, and review triggers; all 101 authored files default to `critical`, with no non-critical downgrades.
- [x] Enforce complete report/manifests equality, global and per-file metrics, risk tiers, immutable base/target/head identity, source-map-backed changed-branch coverage, toolchain/source hashes, and fail-closed evidence output.
- [x] Prove 25 policy/component tests, including generated-source drift and unapproved risk-downgrade rejection.
- [x] Prove clean disposable exact-SHA runs: the initial fail-closed baseline rejected incomplete coverage; after the behavior-test backfill, the validator passes all 101 authored files and records complete evidence.
- [x] Remediate the approved `brace-expansion`, `js-yaml`, and `nanoid` npm findings; the live audit reports zero vulnerabilities.
- [x] Backfill behavior-first React tests across the shell, Inertia bootstrap/layouts/pages, auth/settings/security, Pulse, hooks, canvas/browser boundaries, and all UI primitives; all 101 authored files satisfy their critical D-67 thresholds.
- [x] Keep the React Compiler enabled in the production Vite build while measuring authored TS/TSX in Vitest without compiler-generated memoization scaffolding.
- [x] Upgrade `league/commonmark` from 2.8.3 to 2.10.0 (and compatible `nette/schema` 1.3.6), then prove zero Composer advisories and a green full PHP regression gate.

The original fail-closed D-67 baseline was 44/4,574 statements (0.96%), 31/3,642 branches (0.85%), 6/442 functions (1.35%), and 28/2,777 lines (1.00%). That run correctly failed, but its test transform also mapped React Compiler-generated memoization scaffolding back onto authored files. The corrected coverage transform measures human-authored TS/TSX while the production build retains the compiler. The resulting authored denominator is now completely covered: 1,154/1,154 statements, 727/727 branches, 487/487 functions, and 1,134/1,134 lines. No authored file, risk classification, or policy threshold was removed or weakened.

## Phase 0D — release gates, concurrency lane, and the Auditor platform decision (2026-09-06)

- [x] **D-68 deployment admission.** Both environments deployed on push with no gate of any kind:
  no check that the quality gates had run on the deployed SHA, that they passed, or that a second
  developer had seen it, and `workflow_dispatch` was equally open. Admission now refuses unless the
  candidate is a full 40-hex SHA, is still the tip of its target branch, has a successful `tests`
  run on that exact SHA with every required job individually successful, arrived through a merged
  pull request, and carries an APPROVED review of that pull request's final head SHA from someone
  other than its author. An in-progress run is waited out, never read as a pass. Eleven negative
  controls run in CI against a stubbed GitHub API, covering a failed run, a skipped job, a direct
  push, an author self-approval, and an approval naming a superseded commit. Verified against live
  data: the gate admits `dev` at `9af16b4` and names the non-author approver of pull request #67.
- [x] **D-73 PHP 8.5 deployment pin.** Both environments previously ran whatever bare `php`
  resolved to on the server. The shared remote script now resolves PHP 8.5 explicitly and aborts if
  the server answers with anything else.
- [x] **D-73 PostgreSQL concurrency lane.** `phpunit.pgsql.xml` runs the whole suite plus a
  Concurrency suite that exists only on PostgreSQL, wired into CI against `postgres:17` on the
  canonical 8.5 runtime with `pcntl` so the parallel writers can fork.
- [x] **Waitlist numbering concurrency defect closed.** Queue and loan numbers were `count() + 1`
  read outside any lock, so concurrent signups took the same number and a deleted row caused
  reuse. Numbering is now claimed from a counter row under `lockForUpdate`, never undercutting a
  number already issued, with unique indexes as a backstop. Proven by reverting the fix: the
  parallel test fails and the delete case raises the unique violation. Running the suite on
  PostgreSQL also exposed a latent factory defect — queue and loan numbers were drawn from 40-value
  ranges and had been issuing duplicates that no index existed to catch.
- [x] **Architecture rule catalog generalised.** Two rules named `PulseController` and the Pulse
  repository directly, making them assertions about one feature rather than about the architecture.
  All rules are now stated over namespaces. Added: configuration read through `config()` rather than
  `env()`; controllers reaching no further than the application layer; domain results depending only
  on their inputs, with no clock or random source; application code orchestrating through contracts
  rather than Eloquent; Resources touching no database; and infrastructure concretions named only by
  the provider that binds them.
- [x] **PHP gate negative controls.** Five controls plant the violation each gate exists to catch —
  a Domain class importing Illuminate, a controller querying a model, an application class naming a
  concrete adapter, an unreachable first-party line, and both a level-7 type error and an invalid
  `covers()` inside `tests/`. Each first requires its gate to be green on a clean tree, because a
  broken gate would otherwise "catch" every violation while catching nothing. CI fails the job if a
  planted file survives the run.
- [x] **D-04 decided: Option B.** The Auditor ships as a narrow thin-native secure-capture
  companion with every ordinary screen on Inertia web/PWA. Recorded in
  `d-04-auditor-capture-decision.md`.

### Hosted evidence

All six jobs green on `477956a8d2363a63c9db7023cbe48f2f3b5354df`, workflow run `34036806259`:

| Job | Result |
|---|---|
| PHP 8.5 quality gate | success |
| PostgreSQL concurrency lane | success |
| TypeScript/React quality gate | success |
| Deployment admission negative controls | success — 11 cases |
| PHP gate negative controls | success — 6 caught, 0 not caught, 0 skipped |

Every negative control fired on the hosted runtime, including the two that cannot run locally: an
unreachable first-party line was rejected by the 100% coverage gate, and an invalid construct inside
`tests/` was rejected by static analysis.

### What this section does not claim

Local verification used PHP 8.4.23 against a throwaway PostgreSQL instance, before D-73 was amended to require 8.5; the hosted lane is the authority for the supported runtime. Two gates cannot run on
that machine at all: no coverage driver is installed, and PHPStan exits 1 with no output on any
input. The hosted lanes are the authority for both. The negative-control harness reports itself
skipped rather than passing when a gate is unavailable, and fails outright if a selected control
does not report at all — a defect found when the hosted job reported success having executed no
control.

This is exact-SHA evidence for a feature branch. Promotion to `uat` and `main` regenerates it on the
resulting target-branch SHA, which is what the admission gate now requires before either environment
will accept a deployment.


## Phase 0E — recording what the gates already prove (2026-09-09)

No new engineering. The plan understated the position by five items because nothing had been ticked
since the gates started passing, so each claim was re-verified against a run rather than assumed and
the record was corrected.

**Now proven and recorded:**

| Item | Evidence |
|---|---|
| Reversible negative checks across every quality control | 20 client policy tests plus five PHP controls; hosted run records 6 caught, 0 not caught, 0 skipped |
| Boot, route discovery, focused tests, static/type checks, production build green from a clean baseline | Hosted CI installs from lock files, migrates, builds, and runs the gates on the exact commit; `generated:check` regenerates the typed client from the live route list |
| 100.0% PHP line coverage with no risky test, warning or deprecation | 177 tests, 725 assertions, `Total: 100.0 %`, no deprecation/risky/incomplete marker |
| PHPStan across `app/`, config, database, routes and `tests/` at zero errors; TIA local; CI without TIA | Zero errors; `composer test:php:tia` builds a fresh graph and passes 177 tests; hosted job runs `--no-tia` |
| Brand lockup, colours, role naming and reconstruction authority | D-51, D-52, D-57, D-63 closed; wordmark reconstructed in Inter |

**Corrected rather than ticked.** Three items were partly done and are now stated precisely instead
of left as bare unchecked boxes:

- At the 2026-09-09 review, strict typing was not approved or enforced. **Superseded by D-76 and Phase 0G below:** the current-source convention is now approved and enforced. Protected-module rules accompany each module's first implementation PR under ADR-0001; absent future modules are not counted as tested.
- Favicon, PWA and Apple-touch outputs ship; monochrome, dark and responsive-header outputs wait on
  the Inter wordmark.
- Rights-cleared masters do not exist yet. The 19 wordmark-bearing files need their Inter re-export
  before any wordmark master is cut. The 12 star-only files were never blocked, which is why the
  icons could ship.

The promotion control remains deliberately unexercised: `uat` and `main` sit behind `dev`, and
running a real deployment is held until MVP development begins.
## Phase 0F — baseline inventory (2026-09-09)

- [x] **Implementation-state inventory captured.** `php artisan inventory:baseline` writes
  `docs/phase-0/baseline-inventory.md` from the code: runtime contract, full schema with columns and
  indexes, migration files, first-party routes with middleware, authentication configuration, the
  ADR-0001 layer inventory, CI workflows and jobs, and both deployment targets. `--check` regenerates
  and fails on any difference, and runs in CI, so the document cannot drift silently — proven by
  planting a phantom table row and watching the check reject it.

  Structure only. Row counts and migration run-state are per-environment and excluded by design;
  including them would make the document differ between machines and render the drift check useless.
  Deployment secrets appear by name, never by value.

  One thing the capture surfaced: **production and staging deploy with the same `STAGING_SSH_*`
  credentials**. That was known informally; it is now visible in a governance record.

## Phase 0G — strict typing and protected-module delivery gate (2026-09-10)

**Authority:** Aminu approved the strict-types recommendation in this task. D-76 and ADR-0001 record the exact scope and the previously agreed rule-delivery timing for protected modules. Erastus's review of the implementation is still required before promotion.

- Added `declare(strict_types=1);` to 108 existing PHP files. Together with the existing strict file and the new architecture test, all 110 human-authored PHP files in `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, and `tests/` are covered. Blade templates and generated `bootstrap/cache/**` remain excluded; no human-authored exception was introduced.
- Added a Pest namespace rule plus a recursive, syntax-tree-based file rule. Configuration, routes, anonymous migrations, tests, support files, and newly added/untracked PHP files cannot escape through the absence of a named class. Regression cases reject comment/string imitations, missing/disabled/late/scoped declarations, redeclarations, and invalid source.
- Extended the existing CI negative-control harness with missing and disabled standalone declarations plus hidden-file violations in each of the six source roots. These controls exposed that a glob filename filter could omit dotfiles even with hidden-directory scanning enabled; the corrected suffix matcher catches them. All other planted PHP fixtures now enable strict types so their failures still exercise the intended dependency, coverage or static-analysis rule. The harness refuses to overwrite an existing file or symlink.
- Corrected the `ProfileValidationRules` PHPDoc to describe the actual returned `Unique` rule object and precise lists/array shape. This fixes the strict-mode static-analysis finding without changing validation behavior or adding casts/ignores.
- Regenerated Wayfinder outputs because source-line references moved. No dependency versions, business rules, authentication flows or coverage thresholds changed.

### Local verification

| Check | Result |
|---|---|
| Strict-types rule before conversion | Failed on missing declarations in all six roots; 16 syntax regression cases passed |
| Strict-types suite after conversion | 23 tests, 135 assertions, all passing |
| PHP 8.5.8 full non-TIA gate | 206 tests, 889 assertions, 100.0% first-party line coverage |
| Pint and Pest-aware PHPStan | Passing; zero static-analysis errors |
| Complete architecture command | 35 tests, 225 assertions, all passing |
| PHP negative controls | 24 caught, 0 not caught, 0 skipped; includes 18 strict-types cases and successful teardown |
| Full PostgreSQL suite, isolated local PostgreSQL 18.4 | 210 tests, 901 assertions, including real concurrent writers; the hosted PostgreSQL 17 lane remains the candidate-runtime proof |
| Clean frontend snapshot | 33 files / 322 tests; D-67 passes all 112 authored files: 1,470/1,470 statements, 1,098/1,098 branches, 605/605 functions and 1,434/1,434 lines |
| Frontend static/build checks | `vp check`, configured lint/format/TypeScript/i18n gates, production build and Wayfinder drift check pass |
| Clean snapshot PHP and inventory checks | Full non-TIA PHP gate repeats 206 tests / 889 assertions / 100.0% coverage with zero static-analysis errors; all 11 migrations and `inventory:baseline --check` pass |

Clean-checkout evidence was generated in a disposable checkout at `c10441bf57e5b10e47018cd5b973c95f950e19f9`, based on `aeb85f595f0022d6645a8870b2aaefd95b62ba3e`, with freshly installed locked Composer/npm dependencies and explicit base/target/head identities. The temporary commit contains the implementation and generated outputs; this evidence section was completed afterward. All 123 changed or added non-documentation files match that tested snapshot, and its final working tree is clean. It is not a commit or push on the working feature branch. The frontend reports 32 existing lint warnings, two jsdom canvas notices, and the existing optional-font-fallback warning; none is a new strict-types failure, and no dependency was added to silence them.

These are local implementation checks. The candidate commit still needs hosted CI and the non-author developer's review; this record does not mark Phase 0 accepted or claim future protected modules have been tested.

## Phase 0H — MVP crosswalk and deferred scope (2026-09-10)

**Result:** source enumeration and owned delivery/evidence mapping are complete. This closes the two
Phase 0 document-creation checklist items and the stable-ID/owned-secondary-path mapping criteria;
it does not close implementation, policy approval or Phase 0 acceptance.

The PDF was already accessible in Downloads. Its absence from the repository had incorrectly been
reported as a source blocker. A byte-identical copy is now archived as
[Rozine MVP Spec.pdf](../Rozine%20MVP%20Spec.pdf), and its 14 rendered pages have been visually
reviewed. The [MVP crosswalk](mvp-crosswalk.md) resolves PDF requirements against the BRS and active
decisions; the [deferred-scope register](deferred-scope-register.md) preserves explicit exclusions
and every post-MVP destination without approving them.

| Local verification | Result |
|---|---|
| PDF provenance | 14 pages, 759,501 bytes; source/copy byte equality; SHA-256 `5019b5f6a53e44d5f42b539c77429113c1eb5c18c6392b0a0916b0e9dc18642a` |
| Acceptance rows | 46 unique ordered IDs: 12 spine, 8 Business, 8 Auditor, 10 Investor, 8 Admin; each source title matched to the correct PDF page |
| Screen/state rows | 38 role screens plus launcher; 92 named states, each matched to its exact PDF screen row; stable generic-state suffixes and explicit profile/filter applicability |
| Suite/demo and spine coverage | 24 launcher/demo fields, five visitor steps, all nine access rows, three build waves, source feature/journey rows and nine owned secondary checkpoints |
| Deferred coverage | All 20 PDF exclusion bullets, including repeats/compound scopes; all 43 Phase 5–8 checklist items; former-work-package moved portions routed to 24 owned tranches |
| Structural checks | Inline Node/pypdf assertions pass ID sequence/uniqueness, source-title/state membership, table widths/non-empty required fields, slice/tranche/decision references, checklist counts, local links and source hash; zero errors |
| Dependency setup | `vp install` succeeds; its root-package-name-only lockfile normalization was undone, leaving no dependency or lockfile change |
| Frontend check | `vp check` exits 0: 173 formatted files, zero errors, 32 existing lint warnings |
| Frontend regression | `vp test run --config vitest.config.ts`: 33 files / 322 tests pass; two existing jsdom canvas notices remain |
| Diff hygiene | `git diff --check` passes; changes are documentation/source archival only |

Local authoring is based on `dev` commit `7a0ac6286ef596b96d3d8376fecbe6f00a72c2f8` on
`feat/phase-0-mvp-traceability`. No application code, database, dependencies or financial policy
changed; PHP/coverage/build/live-provider acceptance was not rerun or claimed by this documentation
work. The crosswalk's automated and witnessed evidence columns are **requirements for future
delivery**, not fabricated existing test results. Non-author review, commit/push/PR and hosted
exact-SHA acceptance are not asserted here.

Important retained gates: Appendix A activation; D-05/D-64 identity/mandates; D-09/D-10/D-15
fees/units/limits; D-26/D-27 mandatory secondary lifecycle/pricing; D-04 companion implementation
allocation, estimate/rebaseline and device proof; D-61/D-62 scope signatures; and provider/legal/
regulatory approvals. G-OPS-01 and G-AUD-01 in the crosswalk expose unbaselined operational
approval-threshold and audit-quality/tolerance details without inventing a policy value or decision
signature. Full native clients remain post-MVP, but required secure capture, supervisor access,
internal APIs, language foundations, notices and secondary cash settlement remain in the MVP.

## Phase 0I — underwriting and secondary decision preparation (2026-09-10)

**Status:** review preparation complete; owner decisions, implementation evidence and Phase 1 contract freeze remain open.

The [underwriting decision review](underwriting-decision-review.md) proposes seven owned decisions for OwnerDraw, debt service, volatility trim, exact Coverage precision, prototype bounds, material-event invalidation and the risk/action/cure matrix. The [secondary contract review](secondary-contract-review.md) expands D-26/D-27 Option A into ten candidate rules, explicit state/command and atomicity contracts, holder-of-record behavior, disclosures, recovery and twelve required implementation scenarios. Neither document is a signature or activated financial policy.

The secondary review exposes a real compatibility decision: an RWF 3,000 remaining-cashflow cap cannot coexist with an RWF 5,000 purchase minimum for that order. The proposed current-BRS outcome is an explained undersized-order denial/hold-to-maturity path, or aggregation of the seller's own eligible same-Note units. Any residual-ticket exception needs an explicit D-10/BRS/legal disposition; no platform buyback or liquidity promise is introduced.

| Local verification | Result |
|---|---|
| Source inventory | [Vector register](underwriting-vector-review.json) preserves all 48 Appendix A.5 rows, exact source-row SHA-256 and dispositions: 34 READY-TO-BASELINE, 7 BLOCKED, 6 REJECTED, 1 QUARANTINED; zero BASELINED |
| Arithmetic evidence | [Fixture pack](policy-review-fixtures.json) has 34 synthetic examples with input hashes; 22 source vectors have partial examples, 26 have none, and secondary arithmetic is additionally covered; no complete vector/layer coverage claimed |
| Focused review checks | `php artisan test --compact tests/Unit/PhaseZeroPolicyReviewTest.php --no-tia`: 42 tests / 732 assertions pass, including six negative controls for false activation/baseline/signature, changed input, unknown vector and duplicate fixture |
| Full PHP regression/coverage | `composer test:php:coverage`: 248 tests / 1,622 assertions pass, TIA disabled, 100.0% configured `app/` coverage; this is not coverage of an unimplemented production underwriting or secondary engine |
| PHP style/static analysis | `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` pass; zero PHPStan errors |
| Frontend check/regression | `vp check` exits 0 with 32 existing warnings; `vp test run --config vitest.config.ts`: 33 files / 322 tests pass with the two existing jsdom canvas notices |
| Dependency/diff hygiene | `vp install` succeeds; root-name-only lockfile normalization restored; no dependency changes; `git diff --check` passes |

Changes are documentation, machine-readable review artifacts and a **test-only** arithmetic/integrity oracle. No `app/` code, schema, live record, provider or existing Pulse formula changes. The full PHP gate verifies the current application baseline, not SEC-T01–SEC-T12, PostgreSQL settlement races, provider evidence or a deployed new calculator. The required independent approver remains unnamed, all review approvals are null, and the plan's two Phase 0 blocker checkboxes remain unchecked. Existing source-policy statuses and approval-header placeholders are preserved.

Work remains local on `feat/phase-0-mvp-traceability` based on `dev` SHA `7a0ac6286ef596b96d3d8376fecbe6f00a72c2f8`; no commit, push, PR, hosted exact-SHA acceptance or deployment is claimed. Next authority is K/R disposition of UW-R01–UW-R07 and SEC-R01–SEC-R10, A/E contract review, a named eligible non-author reviewer and the separately required external approvals. Actual corpus validation and implementation-layer evidence must follow before baselining/activation.

## Phase 0J — demo/UAT isolation groundwork (2026-09-10)

**Status:** backend/deployment configuration safeguards are implemented and locally verified. The Phase 0 isolation checkbox remains open; no demo journey, hosted isolation, financial activation or Phase 1 acceptance is asserted.

`EnvironmentIsolation` derives its profile from the deployment's `APP_ENV`, never from a request parameter. Unknown environments and malformed flags fail closed. `staging` and `uat` share the `uat` profile; production and local/testing retain their existing resource configuration. Non-live deployments require PostgreSQL database **and** username `rozine_demo` or `rozine_uat`, without URL/read/write overrides; demo alone may use its exact nonsymlinked `database/isolated/rozine_demo.sqlite` file. Production rejects those reserved non-live database identities. These are configuration checks, not proof of PostgreSQL grants or physical separation.

Demo/UAT are restricted to the selected database connection, namespaced database queues, local file cache/sessions, separate compiled views/private/public storage/logs, array-only mail and explicitly faked Laravel HTTP-client requests. Remote/fallback drivers are removed from their runtime configuration. Provider, SMTP, cloud-storage, SQS, Slack-webhook and Redis credentials are rejected without echoing their values. Unsafe storage symlinks and an incorrect existing `public/storage` target are refused. Dedicated filesystem permissions and network egress controls are still required: application configuration does not constrain raw SDK/cURL calls or a host administrator.

Both demo flags default to false. Local/testing retain ordinary fixture/reset behavior; demo seeding requires `ROZINE_DEMO_ENABLED=true`, and destructive Artisan reset operations additionally require `ROZINE_DEMO_RESET_ENABLED=true`. Production/UAT deny seeding and destructive migration commands even with `--force`. Command-event guards, Laravel's built-in command prohibitions and the existing seeder's own guard cover separate invocation paths. There is no new web reset endpoint or synthetic financial book. `live_money_enabled` is fixed false, not an environment switch that can activate an unimplemented money module.

The shared deployment script now requires a fixed `uat` or `production` target, clears the prior config cache and runs `isolation:check --expect=<target>` before `optimize:clear`, migrations or queue restart; it repeats the check after config caching. Both workflows pass their literal target. Existing D-68 admission/review gates are preserved. Hermetic process tests use fake PHP/Composer/npm executables and never SSH or deploy.

| Local verification | Result |
|---|---|
| Full PHP regression/coverage | `composer test:php:coverage`: 361 tests / 1,973 assertions pass, TIA disabled, 100.0% configured `app/` line coverage, including the architecture suite |
| Isolation proof | Invalid flags, database aliases/overrides, credentials, URLs, debug mode and symlinks are rejected; real forced Artisan calls and nested commands preserve test sentinels; fresh-process application boot resolves isolated resources without creating/querying the demo database |
| Deployment proof | Missing/wrong targets fail; first configuration failure prevents cache clearing/migration/queue restart; cached-configuration failure prevents route/view caching and queue restart; both workflows pass the correct fixed target |
| PHP quality | Dirty-file Pint passes; Pest-aware PHPStan reports zero errors; deployment Bash syntax passes |
| Frontend regression/build | `vp check` passes with the 32 existing warnings; configured Vitest run passes 33 files / 322 tests, with two existing jsdom canvas notices; both TypeScript projects and the production build pass |
| As-built inventory | Generated and checked against a freshly migrated disposable SQLite database; schema is unchanged, and only new classes/commands plus deployment-profile metadata differ. The existing local application database was not migrated or reset |
| Dependency/diff hygiene | `vp install` succeeds; root-name-only lockfile normalization restored; no dependency changes; `git diff --check` passes |

**Before any UAT promotion:** provision and verify the dedicated least-privilege database/role, use a separate checkout and isolated writable directories, remove external credentials, set `APP_DEBUG=false` and a non-live HTTPS `APP_URL`, and ensure any existing `public/storage` link has the required isolated target. A currently nonconforming deployment will fail at boot/check until its operator prepares these prerequisites; this change does not rename a database, copy live data, rewrite `.env`, repoint a symlink or provision infrastructure automatically. Preserve existing data and backup/recovery access during that preparation.

Remaining closure evidence: real DB grant/host/egress separation, synthetic fixture provenance, an authorized deterministic reset with unaffected-environment sentinels, visible demo/UAT labels and no-live-claims browser proof, candidate-SHA hosted checks and non-author review. Required provider-specific sandbox adapters and any future live activation need their own approved contracts. Work remains local on `feat/phase-0-mvp-traceability`; no commit, push, PR, deployment or production/provider action is claimed.

## Phase 0K — Auditor sizing and external-track preparation (2026-09-10)

**Status:** planning artifacts prepared and locally checked; native implementation, device evidence, owner acceptance and actual provider kickoff remain open.

The [Auditor capture delivery plan](auditor-capture-delivery-plan.md) preserves approved D-04 Option B, assigns the proposed Aminu server / Erastus companion-handoff split, and separates **15–25 incremental developer-days** from already-budgeted shared-core and ordinary web work. The longer Erastus lane gives a **proposed 2–3-focused-week portfolio addition**; Section 9.1 now distinguishes the historical Week 8/9/10 targets from candidate Weeks 10–11/11–12/12–13 windows. These are low-confidence author estimates, not accepted staffing or dates. The first native proof must confirm platform, distribution and source/coverage feasibility; unsupported integrity, offline revocation and time claims are not silently downgraded.

The execution pack identifies the three required physical-device rows and two iOS dwell observations of at least 168 hours each. All are unrun/unstarted. Creating the record did not start a timer, host a harness, touch a device, schedule a reminder or obtain an attestation result. The [provider dependency register](provider-dependency-register.md) prepares 13 tracks covering every IR-1–IR-8 plus AML, parsing, storage, legal and native integrity, with proposed leads and a proposed first review on 14 September 2026. No owner acknowledgement, contact, provider selection, credentials, certification, legal opinion or regulatory authorization is asserted.

| Local verification | Result |
|---|---|
| Machine-readable preparation | [delivery-readiness.json](delivery-readiness.json) reconciles six native work packages, A/E/total effort, longer-lane capacity, candidate RC windows, three device runs, two dwell observations and 13 provider tracks |
| Focused readiness tests | `php artisan test --compact tests/Unit/PhaseZeroDeliveryReadinessTest.php --no-tia`: 16 tests / 68 assertions pass, including 12 negative controls for false approval/contact/device evidence, shortened dwell, estimate/capacity drift, circular dependency, missing integration, unknown gate and duplicate track |
| Full PHP regression/coverage | `composer test:php:coverage`: 377 tests / 2,042 assertions, TIA disabled, 100.0% configured `app/` line coverage |
| Static/style | Dirty-file Pint and Pest-aware PHPStan pass; zero static-analysis errors; Markdown tables/IDs validated; `git diff --check` passes |
| Frontend regression | `vp check` and configured Vitest run pass; 33 files / 322 tests, with the same 32 lint warnings and two jsdom canvas notices |
| Dependency/state hygiene | `vp install` passes; root-name-only lockfile normalization restored; no dependency, application-code, database or provider changes from this preparation |

The remaining demo/UAT **visual** safeguards were not implemented in this preparation pass. The Impeccable UI skill requires product context before visual edits; permission to add a source-derived `PRODUCT.md` was requested and is pending. Existing backend isolation work in Phase 0J remains intact. No native source or financial fixtures were added, and neither the provider-start checkbox nor the environment-isolation checkbox is closed. No commit, push, PR, hosted acceptance or deployment is claimed.

## Phase 0L — upstream environment policy and provider tracks (2026-09-10; reconciled 2026-09-14)

This section retains the evidence brought in by `origin/dev` PR #85 (`a08158c`, merged as `10e085f`) alongside the local Phase 0H–K work. The source called this Phase 0H; it is relabelled here to avoid replacing the MVP crosswalk evidence. Definition is complete, but the broader isolation acceptance item remains open as recorded in Phase 0J. Server observations below belong to the upstream 10 September investigation and were not repeated during conflict resolution.

- [x] **Environment isolation defined.** `environment-isolation-policy.md` fixes each environment's
  purpose and permitted data — real participant data in production and nowhere else — together with
  seeded-demo boundaries, per-environment reset controls and feature-flag rules. The upstream record
  reports `APP_DEBUG=false` on both servers and staging on `APP_ENV=staging`.
- [x] **Production refuses to seed.** `DB::prohibitDestructiveCommands` covers five commands and not
  `db:seed`, although `SeedCommand` supports the same prohibition. `DatabaseSeeder` creates
  `test@example.com` with the factory password `password`, already verified, so one seed on the
  production box would have planted a known-credential account beside the live waitlist. Tests assert
  production refuses seeding and wiping, that local/testing can still seed, and reset the static
  prohibition flags after each test so one production-mode test cannot leak into the rest. The merged
  guard uses `EnvironmentIsolation::canSeed()` so provider boot order cannot re-enable the framework
  seed command in UAT/staging or a disabled demo. Nested-command regression tests cover those profiles.
- [ ] **Provider and regulatory tracks owned, not started.** `provider-regulatory-tracks.md` names an
  owner, decision, first action and certification gate for each of the ten required tracks and four
  adjacent ones. External engagement is not claimed; the item closes when each owner confirms first
  contact. The prepared 13-track dependency register and its required inputs/fake-case evidence remain
  intact. Different groupings and named leads require owner reconciliation, not an inferred signature.

**Upstream deployment blocker reported 2026-09-10:** the Contabo box ran PHP 8.4.23 with only 8.4 FPM
pools, while D-73 pins deployment to 8.5 and `deploy-remote.sh` rejects other versions. That reported
state blocks deployment until operators upgrade and verify it. Deployment remains deferred by decision;
this merge neither checks nor changes the server, FPM pools, nginx, secrets or live data.

**Merged-state precedence:** the policy's observed `rozine_staging`/`rozine` database identity,
permitted staging reset and shared outbound credentials do not satisfy the stricter implemented UAT
profile in Phase 0J. Operators must prepare and verify the isolated database/role, paths and disabled
outbound credentials before promotion; do not weaken the guard to accommodate the old host. The local
demo seed/reset switches now exist, but a complete demo, general feature-flag system and hosted proof
do not. The new documents are retained as dated policy/owner records, not deployment acceptance.

## Phase 0M — demo/UAT presentation and synthetic reset safeguards (2026-09-14)

**Status: locally implemented and verified; not deployed or independently accepted.** User-authorized follow-through on the demo/UAT safeguards; the source-derived root [PRODUCT.md](../../PRODUCT.md) supplies the previously missing product context. The existing visual system and assets are preserved; no new brand master, dependency, policy activation or financial module is introduced.

- `HandleInertiaRequests` supplies `nonLiveEnvironment` as an always-included, server-owned prop, including partial reloads. `demo` and `uat`/`staging` show distinct, non-dismissible notices on public, authentication, sidebar and header application layouts. Production gets no non-live notice. Request parameters cannot change the profile. Copy says to use synthetic data and that no real-money transactions occur; it does not certify the origin of arbitrary user-entered records.
- English, French and Kinyarwanda notice strings are cataloged, pseudo-localized and included in the hard-coded-string guard. French/Kinyarwanda catalog presence is still engineering preparation, not D-07 linguistic sign-off.
- Existing `config/isolation.php` switches remain default-off and use the existing accessor. Their shared Aminu/Erastus ownership, purpose and 2026-10-14 00:00 UTC expiry are recorded. Enabled switches expire fail-closed; production with switches off remains unaffected. `live_money_enabled` remains fixed false.
- `demo:reset --confirm=pulse-foundation-v1` restores two reserved, authored synthetic Pulse fixtures only after dedicated-demo configuration and both opt-ins pass. A contract separates application orchestration from database persistence. The complete batch is transactional; writes match contact **and** synthetic provenance, including when a contact appears concurrently. The selected connection is purged and reopened after validation so a cached connection cannot defeat a changed target configuration. Collisions and database failures refuse safely without exposing contacts or SQL.
- The fixture-set reset preserves unrelated data, accounts, files, counters, queues and other environments. Tests use fresh disposable databases and original-connection sentinels, never the user's local database. No user/schema migration, deployed reset, SMTP, provider, real-money or Erastus device operation was performed.

| Local evidence | Result |
|---|---|
| Full PHP regression/architecture/coverage | 408 tests, 2,143 assertions, TIA disabled, 100.0% configured `app/` line coverage |
| Client behavior/coverage | 329 tests in 34 files; all 114 authored executable files included; 100% lines/statements/functions/branches (1,441/1,477/607/1,104 covered units) |
| Negative controls | Confirmation, all non-demo environments, both flag-off cases, invalid flag combination, expiry, wrong database, provenance collision, queue collision and transactional failure are exercised. Removing the write-time provenance predicate makes the unowned-contact test fail; restoring it returns green |
| Static/build | Pint, PHPStan, TypeScript, `vp check`, i18n and Wayfinder drift checks, and production build pass. Existing 32 client lint warnings and two jsdom canvas notices remain |
| Browser | Disposable loopback demo preview: desktop 1,280px and mobile 390px; public page and login notice visible; Inertia login-to-recovery navigation retains notice with no console errors. French notice visually inspected at 320px without clipping. This is desktop responsive evidence, **not physical-device or dwell evidence**, and not a deployed UAT observation |

At this local-verification checkpoint, the work was uncommitted on `feat/phase-0-demo-safeguards`, retaining the isolation work already merged through PR #86 into `aminu-dev` and subsequently PR #87 into `dev`. The raw complete local coverage collection and manifest checks passed; the review package must attach a fresh immutable-SHA D-67 report, hosted checks and eligible non-author review to the committed candidate. The local results above do not claim changed-branch/exact-SHA promotion acceptance for that dirty checkout.

Remaining: host/DB-grant/egress verification and remediation by the authorized operator; candidate-SHA checks and non-author review; Phase 3 financial-demo fixtures/journeys and provider-specific adapters behind their approvals. Erastus's three physical runs and two 168-hour dwell observations remain unstarted in the authoritative register until he records actual device/start evidence using the existing D-04 handoff. The overall Phase 0 isolation checkbox stays open.

## Authority and brand freeze

The governing plan's source order remains active. No unsigned PDF/prototype constant may change money, underwriting, authorization, evidence, secondary trading, or regulatory behavior.

The six superseded JPEGs remain hash-recorded as historical references in `brand-source-manifest.json`; their files have been replaced by 31 SHA-unique SVG sources and 31 matching PNG sources in `docs/New Logo/`. The user confirmed that Robert supplied both packages. On 29 August 2026, every SVG was hash-recorded and passed XML syntax validation, while every PNG was hash-recorded, validated as RGBA with alpha, and matched one-to-one to its SVG basename and `viewBox` dimensions. The source archive `PNGs.zip` is recorded at SHA-256 `de04dac504360a6e05866707e55677b18253f7bc4b1d7293303aca0cbd1675bc`. Receipt and file identity do not constitute brand approval: semantic mapping, duplicate/canonical selection, exact colors, vector/font/outline provenance, usage rights, required variants and surface rules, accessibility approval, and Robert's explicit Brand approval remain pending. No runtime asset is replaced until D-51, D-52, D-56, D-57, and D-63 are approved.

The confirmed internal project approval pool is Aminu, Erastus, Robert, and Kimani. Under D-71, Robert owns Product, Design, Brand, Business, and internal Legal; Aminu and Erastus jointly own Engineering and Security; Kimani owns Audit Operations, Compliance, and Finance/Risk. Each applicable record must still capture every required named-owner signature, conflict, or abstention. Any pool member who was not the sole author may supply the independent-test approval, or the record may name an eligible external delegate. Required external legal/regulatory/CMA, ICPAR/Audit Partner, provider-certification, penetration-test, and independent-assurance approvals remain separate.

D-68 intentionally removes a paid GitHub plan and private-branch-protection feature from the Phase 0 dependency list for the current two-developer team. It does not remove pull requests, hosted exact-SHA gates, review by the non-author developer, or fail-closed deployment admission, and it does not claim that GitHub technically blocks direct pushes.

Current runtime brand references remain deliberately unchanged:

| Surface | Current legacy reference |
|---|---|
| Application shell | `resources/js/components/app-logo.tsx` and `app-logo-icon.tsx` use `/images/rozine-wing-white.png` |
| Pulse pass/card export | `resources/js/components/pulse/pass-card.tsx` and `resources/js/lib/pass-card-image.ts` use the white wing |
| Pulse wordmark | `resources/js/components/pulse/pulse-wordmark.tsx` uses the legacy wordmark family |
| Browser/PWA identity | `AppServiceProvider`, `site.webmanifest`, favicons, Apple-touch, PWA icons, and `og-image.png` use legacy derivatives |

## Exit blockers carried forward

- The Aminu/Erastus/Robert/Kimani ownership map, including Robert as Business/internal Legal owner and Kimani as Finance/Risk owner, is recorded; internal ownership assignment is no longer a Phase 0 blocker. The remaining internal governance work is to name an eligible non-author independent-test approver on each applicable record and capture all required owner signatures for the Phase 1 red decisions—including the Party model and secondary-market contract.
- Robert's 31 SVG and 31 matching PNG sources are received, validated, and hash-recorded; PNG receipt is no longer a blocker. Semantic/canonical mapping, exact colors, vector/font/outline provenance, usage rights, required variants and surface rules, accessibility approval, and Robert's explicit Product/Brand and internal Legal sign-offs remain pending, alongside any required external rights/legal clearance for D-51, D-52, D-56, D-57, and D-63 and the rights-cleared masters.
- The Auditor PWA assurance spike is closed. D-04 resolved to Option B on 2026-09-06: a narrow thin-native secure-capture companion, with every ordinary Auditor screen on Inertia web/PWA. Option A is disproven on the web platform rather than untested. The exception still needs its estimate and owner entered in the schedule, and the plan treats a Phase 0 native exception as requiring a schedule rebaseline.
- ADR-0001 and the PHP/database runtime contract are approved. The 2026-08-29 working tree closes the specific `PulseController` legacy exception and removes authoritative Pulse calculations from React and Resources, with 100% local PHP and web coverage plus green static/build gates. Clean committed exact-SHA evidence, the complete architecture-rule catalog, the PHP 8.5 deployment pin, hosted 8.5 evidence, and PostgreSQL race tests remain open; the public-waitlist `count()+1` numbering is not concurrency-certified.
- Paid private-branch protection is intentionally out of scope. Fail-closed exact-SHA deployment admission, non-author latest-SHA review, and the PHP 8.5 deployment pin are implemented as of 2026-09-06; deployment workflows no longer deploy on push without evidence. Hosted promotion attestation on a promoted SHA remains to be recorded.

Phase 0 remains `IN PROGRESS`; this evidence does not claim Phase 0 acceptance or permission to begin governed feature behavior.
