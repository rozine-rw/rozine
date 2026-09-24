# ADR-0001 — Laravel modular-monolith boundaries

**Status:** Accepted

**Date:** 2026-08-24

**Approved:** 2026-08-28 — Engineering, Aminu and Erastus

**Owners:** Engineering — Aminu and Erastus; Security — Aminu and Erastus; Product, Business, and internal Legal — Robert; Compliance, Audit Operations, and Finance/Risk — Kimani; independent test — a pool member who was not the sole author or an eligible external delegate; required external legal/regulatory authority remains separate

## Decision

Rozine remains one Laravel 13 deployment and one transactional data boundary. Inertia web routes and versioned mobile API routes are separate transports over the same application actions. They must not duplicate financial, authorization, underwriting, evidence, or secondary-market rules.

New bounded contexts use vertical modules with internal layers:

```text
App\Domain\<Context>          pure rules, values, states, and domain events
App\Application\<Context>     use cases, transactions, policies, and ports
App\Infrastructure\<Context>  Eloquent persistence and provider adapters
App\Http                       Inertia/API controllers, requests, and Resources
```

The transport contract is shared deliberately:

1. An Inertia controller and its `/api/v1` counterpart invoke the same application action.
2. The action returns an application result or authorized read model, never an Inertia response or vendor DTO.
3. One Eloquent API Resource shapes the result for both transports. The API returns that Resource directly; the Inertia adapter resolves the same Resource into props.
4. A Resource may shape deliberately loaded and authorized data, but may not calculate money, authorize, mutate state, call providers, or perform workflow transitions.
5. Provider implementations remain behind application-owned ports. SDK types do not enter Domain objects or Resource schemas.

Cross-context writes happen only through the target context's application action or declared port. Direct table/model writes across protected ledger, settlement, underwriting, marketplace, audit-evidence, or identity seams are prohibited.

## Initial executable rules

`tests/Architecture/ArchitectureTest.php` starts with rules that are true of the current code: PSR-4 casing, prohibited debug/termination helpers, transport naming/inheritance, Resource isolation from actions/infrastructure/calculators, and transport-independent support calculations. Module-specific rules are added with the first module rather than pretending absent namespaces are already enforced.

## Strict typing — D-76, approved 2026-09-10

Aminu approved the strict-types recommendation in this task. Every human-authored PHP file under `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, and `tests/` must begin with `declare(strict_types=1);` after the PHP opening tag and optional comments. A later declaration must not disable or replace it.

The scope includes standalone configuration and route files, anonymous migrations, factories, seeders, Pest test files, and test support code as well as named classes. Blade templates (`*.blade.php`) and generated `bootstrap/cache/**` files are excluded. Vendor, build, and generated artifacts outside these six source roots are outside this convention. No human-authored file is exempt; any future exception must identify the exact file, reason, owner, approver, and expiry before the enforcement scope changes. A generated marker or directory name alone does not grant an exclusion.

`tests/Architecture/StrictTypesTest.php` combines Pest's namespace expectation with a recursive file scan and PHP syntax-tree inspection. It checks every approved directory, including hidden and newly added/untracked PHP files, and rejects missing/disabled declarations, comment or string imitations, late declarations, and redeclarations. These tests run in the normal non-TIA suite and the architecture command. The negative-control harness plants missing and disabled declarations plus hidden-file violations in every source root and requires the corresponding file diagnostic before removing each fixture.

Strict typing does not validate transport input, prohibit an explicit cast, or replace authorization and financial rules. Those remain application responsibilities, and the existing PHPStan and 100% coverage gates continue to apply. Existing behavior must pass before this conversion is accepted.

## Protected-module rule delivery gate

The timing is part of the existing accepted decision: the **first implementation PR** for each ledger, settlement, underwriting-publication, seal, or immutable-evidence module must freeze its actual namespaces and allowed entry points here, add architecture rules over those existing symbols, and demonstrate a controlled failure followed by a passing run in that same PR. The test must assert that its target symbols exist so an empty namespace cannot pass as protection. Renaming or moving a module updates its rule and evidence in the same change.

Aminu owns the server-side implementation and Erastus supplies the non-author review (reversed if Erastus authors that change). These are enforceable module-entry requirements, not claims that future modules are already implemented or tested. Phase 0 establishes the current-source rules and this delivery gate; it does not require building later-phase protected modules early. Their behavior, authorization, immutable-evidence and PostgreSQL concurrency tests remain required with their implementation.

## Exact legacy exceptions

### Phase 1 identity entry points — 2026-09-23

`App\Domain\Identity\RoleAccess`, `MembershipTransitions` and `ActiveRolePolicy` own role availability, the membership lifecycle and active-role decisions. The application entry points are `RegisterIdentity`, `GetIdentityContext`, `ResolveVerifiedPerson`, `ChangeMembership`, `SelectActiveRole`, `AuthorizeActiveRole` and `ConfigureIdentityOperator` under `App\Application\Identity`. `Contracts\IdentityRepository` and `Contracts\IdentityAccessStore` bind only in `AppServiceProvider` to the two `App\Infrastructure\Identity\EloquentIdentity*` adapters. Registration remains atomic and grants no verification or membership.

`Party`, `RoleMembership`, `VerifiedPersonIdentity`, `IdentityOperator` and `IdentityAuditEvent` may be referenced only by the identity adapters, model relationships and factories. The architecture suite asserts concrete identity targets and enforces that boundary. The `identity-boundary` negative control plants a forbidden application-layer Party write, requires that rule to fail, removes it and verifies a clean suite.

Identity administration requires a dedicated staff account with verified email, confirmed MFA and a current enabled operator record. A trusted deployment operator uses `php artisan identity:operator <user-id> --reason="..." --no-interaction` to grant this narrow capability, or adds `--revoke` to withdraw it. It cannot be granted through HTTP or registration. Granting refuses an account with verified identity, membership history, an organization Party or a shared Party; an unused registration Party can be detached with an audit record. Revoking access remains possible without valid MFA. This is the identity operator bootstrap, not the full Admin permission matrix or a live staff onboarding approval.

`ResolveVerifiedPerson` records the operator's attestation against reviewed external evidence: the namespace-qualified provider reference identifies the same person across logins, and a separate evidence reference and reason are mandatory. The provider reference is hashed for matching and is never exposed in public Resources. A PostgreSQL advisory lock plus unique identity/Party keys serialize resolution. Existing verified identity/membership histories are rejected for explicit reconciliation instead of being moved or merged. This action does not fetch documents, run an external KYC/KYB provider, validate corporate mandates or grant transaction eligibility.

Membership commands lock the operator and Party, recheck current authority, enforce allowed state transitions and Auditor exclusivity, compare the membership revision, and write the mutation and immutable audit receipt in the same transaction. `pending` and `suspended` roles retain the exclusivity constraint; `revoked` is terminal. Actor-scoped request UUIDs and canonical permitted-input hashes give one effect for retries and reject changed payloads. Audit entries retain actor, target, reason, before/after, request identity, policy version and server time. PostgreSQL rejects updates/deletes of audit entries. The actor ID is historical data without a cascading user foreign key, so account deletion cannot rewrite the audit event.

Selection stores a membership ID and revision plus a monotonically increasing context revision on the authenticated account. `AuthorizeActiveRole::handle` holds the user and Party locks through its callback; future role-scoped mutations must supply the record's server-resolved Party and the expected context revision there. Missing/revoked verification, an inactive or revised membership, the wrong role/owner, a stale context or missing Auditor MFA fails closed. A suspended membership's reinstatement does not restore a previously selected role. GET role context uses the same authorization boundary. The identity Resource currently exposes only `identity.select_role` and `identity.view_role`, never financial, company-mandate or audit-filing permissions.

| Operation | Named web route | Named API route | Shared application entry point |
|---|---|---|---|
| Read identity/launcher facts | `dashboard` | `api.v1.identity.show` | `GetIdentityContext` / `IdentityContextResource` (`identity-v2`) |
| Resolve reviewed person identity | `identity.people.resolve` | `api.v1.identity.people.resolve` | `ResolveVerifiedPerson` |
| Provision/change a membership | `identity.memberships.update` | `api.v1.identity.memberships.update` | `ChangeMembership` |
| Select active role | `identity.active-role.store` | `api.v1.identity.active-role.store` | `SelectActiveRole` |
| Read authorized role context | `identity.roles.show` | `api.v1.identity.roles.show` | `AuthorizeActiveRole` |

Web commands use authenticated, CSRF-protected Laravel routes; API commands use Sanctum and require the relevant `identity:manage`, `identity:select-role` or `identity:access` ability for bearer tokens as well as current server-side authority. Both adapters serialize the same shared Resources; the Inertia dashboard consumes the shared result directly. `resources/js/types/identity.ts` defines the type-only client contract and named Wayfinder routes expose the matching transports.

Fortify remains the single authentication entry point. `CreateNewUser` keeps framework validation and adapts the ID returned by `RegisterIdentity` to Fortify's User return type. `ResetUserPassword` remains the existing framework adapter; neither is duplicated for the API. Consent, recovery policy, complete staff permissions, entity authority, Auditor biometric binding and transactional authorization remain unfinished Phase 1 work. The local regressions cover web/API parity, cross-login restrictions, stale/replayed commands, immutable auditing and four PostgreSQL races; they do not replace latest-commit non-author review or checkpoint acceptance.

### Checkpoint 1 entry and navigation adapters — 2026-09-24

PR #97 merged to `dev` at `2774c03` after its five hosted gates passed. Aminu explicitly waived Erastus's review for that PR; this is a scoped exception, not a claim that a review occurred or a waiver for later checkpoints.

`RoleHomeController` publishes `investor.home`, `business.home` and `auditor.home` through `AuthorizeActiveRole`; GET requests never select a membership. These entry pages expose verified identity facts only. Denied browser requests render `identity/access-denied` without stale role props; JSON clients retain stable error codes. Financial dashboard data remains outside checkpoint 1.

`GetStaffAccess` and `ConfigureStaffAccess` use `IdentityAccessStore` and its existing transactional adapter. `staff_accounts` grants only `admin.open`, separately from `identity_operators` and marketplace roles. The trusted deployment command `identity:staff <user-id> --reason="..." --no-interaction` grants this entry capability, with `--revoke` to withdraw it. Provisioning enforces a separate account, verified email and effective confirmed MFA, records immutable evidence and refuses established/shared participant identities. Revocation remains available without those prerequisites. A disabled staff account cannot be resolved into a participant identity. `staff-access-v1` exposes only `contract_version`, `can_open_admin` and scoped `allowed_actions`, with the same Resource on the dashboard, authorized `admin.home` and `/api/v1/staff-access`. This does not grant financial, directory, identity-management or maker-checker operations; the full staff matrix remains open. No real staff grant is created by the migration or a seeder.

`SaveRoleBookmark` and `GetRoleBookmark` authorize and lock the current user and Party through the existing access port. `role_bookmarks` has one position per account and role, bound to the selected membership ID/revision. The shared `BookmarkDestination` allowlist currently accepts only the three implemented role homes, no parameters, and an optional `section=overview|access` query. New screens must add their own validation and record-level authorization before becoming restorable. Revoked or changed memberships cannot restore old positions; obsolete destinations fall back to the authorized role home. Bookmarks contain no financial/evidence props, raw URLs or scroll state. Saves require the current context revision and request UUID, and are audited atomically. Same-command replay returns the current position without overwriting a newer save. The `role-bookmark-v1` Resource serves web and `/api/v1` endpoints; `identity.roles.resume` resolves a fresh authorized redirect for the launcher. API navigation additionally requires `identity:access`; the staff projection requires `staff:access`, which alone grants no authority.

Architecture rules now cover `StaffAccount` and `RoleBookmark` along with the other protected identity records. Local verification for this adapter slice: 857 non-TIA PostgreSQL tests / 4,444 assertions with 100% application line coverage; 337 web tests with 100% lines, statements, functions and branches; zero PHPStan errors; TypeScript and production build pass. PostgreSQL includes concurrent duplicate bookmark saves and saves racing role switches. This adapter slice is committed as `9484b2b` before integration.

The checkpoint 1 integration merges Erastus's `67c4528` snapshot and adapts the launcher to `identity-v2`. Role buttons use the shared JSON selection action with the expected revision and a request UUID; only a matching selected role plus `identity.view_role` navigates to the authorized resume route. Network retries preserve the request UUID, while stale/revoked/MFA/validation failures require refreshed authority. Current selected roles still reauthorize through resume. Staff entry uses only `staff_access`, not marketplace roles or identity-operator status. Focus, visibility and reconnect events reload the applicable access Resources and coalesce duplicate refreshes. The four richer role surfaces remain synthetic previews behind the existing local/testing-only preview route; no financial workflow is enabled.

Combined local verification at `04e191c568bc8e9634d3aed0eb64fb5f858803c7`, published in [PR #98](https://github.com/rozine-rw/rozine/pull/98), passes 1,023 non-TIA PostgreSQL tests / 5,412 assertions with 100% application line coverage, 727 web tests with all four metrics at 100% across 304 authored executable files and exact-SHA D-67 admission, zero PHPStan errors, TypeScript, build, i18n and inventory checks. `vp check` reports zero errors and 41 warnings in the combined branch. `tests/Browser/CheckpointOneBrowserTest.php` runs Playwright CLI against factory accounts in the isolated `rozine_test` database and a temporary application server. It proves real login/CSRF, Investor/Business switching, saved-position restoration and cold reload, denial after another tab changes roles, logout, a separate login's independent selection/position, Auditor MFA gating and staff MFA challenge/entry. Desktop and 390×844 screenshots were inspected under ignored `output/playwright/checkpoint-one/`. Run it explicitly after building assets with `PLAYWRIGHT_CLI=/path/to/playwright-cli php vendor/bin/pest tests/Browser/CheckpointOneBrowserTest.php --no-tia --compact`; it is separate from the default PHP suites and requires the installed CLI/browser. Hosted exact-commit checks and non-author checkpoint review remain separate acceptance evidence.

### Checkpoint 2 identity authority and exact arithmetic — 2026-09-24

`AuthorizeStaffPermission` holds the existing dedicated staff/MFA authority locks through the protected callback. Explicit `StaffPermission` roles are analyst, approver, treasury, compliance and superadmin; none grants wildcard access or an underwriting override. `identity:staff --role=...` provisions these roles through the existing audited operator boundary. Registration and HTTP cannot grant them.

`ResolveVerifiedOrganization` requires `businesses.verify`, records reviewed RDB evidence and resolves one organization Party under an advisory lock and unique registry digest. It accepts no tax-identifier namespace and grants no participant membership or entity mandate. `WithVerifiedParties` holds sorted Party locks and verifies the entity plus every required person; it is a verification boundary, not actor authorization. A Business action must additionally check the active role and current effective mandate. `VerifiedOrganizationIdentity` joins the existing protected identity model set, accessible only to identity adapters, model relationships and factories. Concrete-target assertions prevent an empty rule; the identity negative control probes both Party and organization-identity writes.

`App\Domain\Underwriting` contains pure exact-rational calculation only: `CashFlowEvidence`, `EngineScorecard`, `FlatReturnPricing`, `LoanSchedule` and `LoanCapacity`, with `ExactFinancialValue` and `UnderwritingViolation`. Inputs include the complete observation/repayment calendar; domain code does not read clocks, external evidence or persistence. Whole-RWF half-up rules, original-request cutoff, final DSCR, repeat weak-month limits, exposure room and final 5,000-franc quantization follow engineering-2026-09-23.4. Internal score diagnostics are not a public Resource. Evidence validation and immutable calculation publication are separate modules and remain subject to the first-PR boundary gate above.

Local focused evidence: 66 underwriting tests / 250 assertions, 36 staff/organization feature tests / 210 assertions and 10 PostgreSQL identity concurrency tests / 35 assertions. Concurrency includes duplicate registry requests, simultaneous canonical organization resolution and blocking verification/permission revocation until protected work commits. These are local foundation checks, not full checkpoint, hosted CI or browser acceptance.

### Checkpoint 2 command outcomes and Business mandates — 2026-09-24

`App\Application\Operations\Contracts\OperationJournal` is the only entry to immutable `CommandOperation` records through `App\Infrastructure\Operations\EloquentOperationJournal`. Its authorizer runs within the transaction before execution and every replay; callers supply server-resolved Party/staff keys and lock current record authority. PostgreSQL serializes each Party/command/UUID, binds canonical permitted input and target identity, and records the effect plus final outcome atomically. A rejected operation rolls back its inner effects while recording its denial. Unexpected failures leave no committed outcome. Scoped lookup returning 404 does not prove an in-flight command failed. Outcomes cannot be updated or deleted, including after their eight-day minimum lookup-retention date; no purge policy is added. `OperationResource` emits only the agreed public envelope, preserving the recorded HTTP result without disclosing actor keys or request hashes.

`CanonicalJson` binds to the approved JCS package through `JcsCanonicalJson`. This governed profile permits UTF-8 strings, booleans, null, arrays/objects and exactly representable integer JSON numbers. Money, ratios and large counts are strings. It rejects floats, integers beyond 53-bit precision, malformed UTF-8 and U+2028/U+2029 before invoking the package, so unsupported serialization cannot silently produce a purported canonical digest. This is an intentionally bounded input profile, not a claim that the dependency handles every JSON numeric/string value. Tests cover exact large money strings, UTF-16 key ordering and rejection boundaries.

`ConfigureBusinessAuthority` and `WithBusinessAuthority` are the Business authority entry points through `BusinessAuthorityStore` and `App\Infrastructure\Business\EloquentBusinessAuthorityStore`. Only this adapter may reference `BusinessProfile` and `BusinessMandate`. Every version preserves the reviewed profile, complete declared-person set, required signatories, explicit permissions, effective/expiry timestamps, actor, evidence and reason. PostgreSQL rejects mandate updates/deletes. Staff may revoke a mandate after a party loses verification, but cannot activate unverified authority or change a company code away from the verified RDB identity. Sole traders have one verified owner/signatory; companies use their actual mandate without a two-director assumption.

Business commands first lock their aggregate, then the actor and all affected identity Parties in stable order through `AuthorizeEntityRole`. Current Business role/context, the verified entity and every declared person, effective mandate, exact permission and optional mandate version are rechecked through the protected callback. People without view authority receive scoped 404; visible but ungranted operations receive an explicit denial. These actions do not issue a note, publish an underwriting result or approve legal documents. Architecture tests name concrete targets for the journal and Business models; their negative controls reject bypass writes. Full application, evidence, signing and transport integration remain in progress. The foundation passes 1,196 full non-TIA PostgreSQL PHP tests / 6,101 assertions at 100% application line coverage, zero PHPStan errors and all 28 negative-control scenarios. The web foundation chain and `vp check` pass (41 existing warnings). Exact-commit publication and complete checkpoint acceptance remain separate.

| Class | Exception | Owner | Removal gate |
|---|---|---|---|
| `App\Http\Controllers\PulseController` | Directly queries `PulseSignup` and calls `PulseUnderwriting`; it predates the application-action boundary. | Aminu and Erastus — Engineering | Isolate or remove before Phase 1 begins; Pulse product scope remains deferred to Phase 6. |
| `App\Actions\Fortify\CreateNewUser` | Implements a Fortify framework contract inside the starter-kit authentication seam. | Aminu and Erastus — Engineering/Security | Reassess with the Phase 1 identity module; never duplicate it for the mobile API. |
| `App\Actions\Fortify\ResetUserPassword` | Implements a Fortify framework contract inside the starter-kit authentication seam. | Aminu and Erastus — Engineering/Security | Reassess with the Phase 1 identity module; never duplicate it for the mobile API. |

These are exact, temporary exceptions—not namespace-wide ignores or permission for new code to repeat the pattern.

### Checkpoint 2 versioned consent and review decisions — 2026-09-24

`RecordConsentRelease` and `WithCurrentConsent` use `ConsentCatalog` through `App\Infrastructure\Identity\EloquentConsentCatalog`. `ConsentRelease` is an existing-symbol target of the protected identity rule, with a direct-write negative control. The adapter records externally approved text and evidence; it does not itself grant legal approval. Only explicit compliance/superadmin permission `consent.documents.record` can record a release, under current verified staff/MFA authority. A release preserves complete Terms/Privacy text, owner summaries, mandatory disclosures, versions, computed SHA-256 hashes, provenance, actor and reason. Existing kind/key versions cannot be reused for changed content, summary or synthetic provenance. A withdrawal appends a new release and never falls back to an old one. No actual approved legal document is seeded.

PostgreSQL holds an exclusive catalog advisory lock for publication and a shared lock through protected reads/acceptance, so a concurrent release cannot change what a signer accepts mid-transaction. Missing, withdrawn or environment-ineligible documents are unavailable. Synthetic releases are accepted only where `EnvironmentIsolation::canSeed()` allows fixtures; they are unavailable in UAT/production. `ConsentDocuments` matches every exact kind/key/version/hash without numeric-string coercion; application signatures will retain the selected release and receipt. Publication/revocation races pass with the combined 17-test / 58-assertion PostgreSQL suite. The complete PHP suite passes 1,250 tests / 6,262 assertions at 100% application line coverage, with zero PHPStan errors; all three identity boundary probes fail as required and the clean architecture suite passes afterwards.

Erastus reviewed PR #101 at `1740ea9f` with no blockers. The journal's eight days remain a **minimum lookup interval**, not a TTL or deletion permission (MC-01). There is no prune command. Future CFG-05 purge/anonymisation must respect the relevant retention/hold rule and preserve effect uniqueness plus minimal replay tombstones; expiry must never make a committed command executable again. The HTTP `OperationResource` now adds immutable `recorded_at` from the stored outcome, emits fresh response `server_time`, and mirrors `field_errors` as Laravel's `errors` bag. The immutable outcome itself does not change on replay. Business mandates currently represent an explicit **all-of** verified signer set. Arbitrary quorum mandates are not inferred from counts; unsupported/conflicting authority remains unavailable until accurately represented. `MANDATE_STALE` requires refreshed authority and a fresh acceptance attempt.

### Checkpoint 2 application drafts — 2026-09-24

`CreateBusinessApplication`, `SaveBusinessApplication`, `GetBusinessApplication` and `FindBusinessOperation` use `BusinessApplicationStore` through `App\Infrastructure\Business\EloquentBusinessApplicationStore`. Only the Business adapter, model relationships and factories can reference `BusinessApplication` or `BusinessApplicationVersion`. They join the existing concrete-target architecture assertion and direct-write negative controls. Each committed draft version records the exact whole-franc request, resume pointer, actor account/Party, mandate version and policy. PostgreSQL protects the historical snapshots against updates/deletes. Drafts may be incomplete; no calculation, approval, review-step transition or signature is implied by saving one.

The adapter locks current Business authority before the application, then resolves the actor on the server and serializes the journal request. A repeated UUID cannot create another application or version, a changed body conflicts, and a stale version cannot overwrite a newer edit. A second login for the same Party sees the same receipt only after current authority is checked. Operation lookup verifies target scope and the locked Party identity; it refuses a receipt if account identity changes between initial lookup and authorization. Reads preserve the saved version. Draft save cannot bypass the separately required quote/signature workflow or edit a submitted application. Local proof: 1,289 full non-TIA PHP tests / 6,423 assertions at 100% application line coverage; zero PHPStan errors; the combined PostgreSQL concurrency suite passes 19 tests / 65 assertions. Live page/API contracts and financial publication remain in progress.

## Consequences

- Laravel owns both web and mobile business logic without creating a second backend.
- Inertia remains a server-driven web transport; native clients consume `/api/v1` later.
- Shared Resources make schema parity testable while shared actions preserve behavioral parity.
- Financial/evidence transactions stay inside one database boundary and can use PostgreSQL locking and idempotency controls.
- The architecture suite grows alongside modules and cannot replace behavior, authorization, concurrency, or Resource contract tests.

## Approved Phase 0 contract

- [x] Use the documented Domain/Application/Infrastructure/HTTP namespace boundaries.
- [x] Start post-MVP native API versioning at `/api/v1`.
- [x] Use Eloquent API Resources as the shared serialization boundary, not the business-logic boundary.
- [x] Preserve one shared application/domain layer for Inertia web and mobile API transports.
- [x] Accept the three exact legacy dispositions. `PulseController` must be isolated or removed before Phase 1 starts; the two Fortify actions remain controlled framework exceptions and must be reassessed with the Phase 1 identity module.

Acceptance closes the architecture decision. It does not close the remaining implementation evidence: `PulseController` isolation/removal, the expanded module rule catalog, controlled architecture-test failures, and exact-SHA hosted results must still pass before Phase 1 governed behavior begins.
