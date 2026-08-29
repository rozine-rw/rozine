# Phase 0 kickoff evidence

**Status:** In progress

**Started:** 2026-08-24

**Branch:** `feat/phase-0-foundation`

**Baseline commit:** `e9c65ed`

## Goal of the foundation slices

Establish an honest, reproducible PHP and TypeScript/React quality baseline and safe decision boundary before new MVP domain work starts. These slices introduce no domain behavior, schema, money rule, provider, or brand asset; production edits are limited to removing unreachable branches, encoding existing invariants, and making the account-deletion cancel action explicitly non-submitting.

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
- [x] Configure PHP 8.4 minimum-compatibility and PHP 8.5 canonical deployment/coverage lanes in CI under D-73; hosted evidence remains pending.
- [x] Pin the clean-build Node runtime to 24.15.0 and build assets before feature tests that render the Vite manifest.
- [x] Add the initial Pest Architecture suite and remove starter placeholder helpers.
- [x] Prove the architecture rule with a temporary Resource-to-calculator violation that fails, then remove it and restore a green six-test architecture suite.
- [x] Make the local/CI Pint check deterministic without a parallel localhost-worker requirement.
- [x] Preserve the six superseded JPEG hashes as historical references, receive and hash all 31 replacement SVGs, and validate their XML syntax.
- [x] Add Pest's first-party PHPStan plugin, register it with PHPStan, and analyze `tests/`.
- [x] Add the standalone Vitest/React Testing Library/V8 foundation and fail-closed D-66/D-67 enforcement engine.
- [x] Backfill behavior tests until the complete D-67 metric/risk-tier baseline is green.
- [x] Prove clean Composer/npm installation, Laravel boot/routes, production build, and the full gate in a disposable copy with no prior `vendor/`, `node_modules/`, or build output.
- [ ] Add PostgreSQL locking/concurrency evidence for protected financial and secondary-market paths.

## Approved Engineering decisions

- [x] D-72 accepts ADR-0001 as written: the modular-monolith namespaces, one shared application/domain layer, `/api/v1`, shared Eloquent API Resources as the serialization boundary, and the three exact legacy dispositions are approved by Engineering on 2026-08-28.
- [x] D-73 sets PHP 8.4 as the minimum supported runtime, PHP 8.5 as the canonical deployment/coverage runtime, both versions in CI, and PostgreSQL as the authoritative locking/concurrency environment.
- [x] D-75 pins Node 24.15.0 across local metadata, both normal CI jobs, production builds, and the TIA baseline workflow while retaining npm 10.9.8.
- [x] D-74 assigns the Phase 1 server/API/domain/application/data/security lane and PHP evidence to Aminu, the Inertia React UI/UX/client/PWA/accessibility lane and web evidence to Erastus, and the contract freeze, five integration checkpoints, cross-review, and Alpha acceptance to both.
- [ ] Decision approval does not replace execution evidence: isolate or remove `PulseController`, finish the module architecture-rule catalog and negative controls, pin PHP 8.5 in deployment evidence, obtain hosted exact-SHA results for both runtime lanes, and prove PostgreSQL financial/secondary race behavior before Phase 0 exits.

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

## Authority and brand freeze

The governing plan's source order remains active. No unsigned PDF/prototype constant may change money, underwriting, authorization, evidence, secondary trading, or regulatory behavior.

The six superseded JPEGs remain hash-recorded as historical references in `brand-source-manifest.json`; their files have been replaced by 31 SHA-unique SVG sources in `docs/New Logo/`. On 29 August 2026, every SVG was hash-recorded and passed XML syntax validation. That proves receipt and file identity, not brand approval: PNG derivatives are still missing, and semantic mapping, duplicate/canonical selection, font/outline provenance, exact colors, usage rights, surface rules, and Robert's Brand approval remain pending. No runtime asset is replaced until D-51, D-52, D-56, D-57, and D-63 are approved.

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
- The 31 SVG sources are received, syntax-validated, and hash-recorded. The PNG derivatives, semantic/canonical mapping, font/outline provenance, exact colors, usage rights, and surface rules remain pending; Robert must provide the applicable Product/Brand and internal Legal sign-offs, while Accessibility approval and any required external rights/legal clearance remain separately pending for D-51, D-52, D-56, D-57, and D-63 and the rights-cleared masters.
- The Auditor PWA assurance spike remains open.
- ADR-0001 and the PHP/database runtime contract are approved; `PulseController` isolation/removal, the complete architecture-rule evidence, the PHP 8.5 deployment pin, hosted PHP 8.4/8.5 evidence, and PostgreSQL race tests remain open.
- Paid private-branch protection is intentionally out of scope; hosted promotion attestation, non-author latest-SHA review, and fail-closed exact-SHA deployment evidence remain open. Current deployment workflows still deploy directly on push.

Phase 0 remains `IN PROGRESS`; this evidence does not claim Phase 0 acceptance or permission to begin governed feature behavior.
