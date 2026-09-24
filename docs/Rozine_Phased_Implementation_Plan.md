# Rozine MVP-First Phased Implementation Plan

**Document status:** Draft; underwriting/Pulse decisions D-11, D-11A–C, D-13, D-14, and D-19A are product-approved candidates, not activated; remaining product, operational, technical, and regulatory gates stay open

**Current MVP specification override — 23 September 2026:** [engineering-2026-09-23.4](phase-0/engineering-contract-draft-2026-09-20.md) section 11 is the controlling baseline for the confirmed underwriting/secondary amendments and the five formerly open CFG default groups. Aminu delegated default selection and waived Erastus's review of this specification. Older pending-default and joint-freeze statements below are history where superseded by that record; they are not another internal approval round. Future implementation tests and real operational/external evidence remain separate. Phase 1 application development has not started in this defaults-only update.

**Prepared:** 18 August 2026 · **MVP-first refactor:** 19 August 2026 · **Agent-native secondary-market schedule:** 20 August 2026 · **Cross-platform risk-tiered coverage revision:** 24 August 2026 · **Phase 0 governance and brand-input update:** 27 August 2026 · **Named internal-role update:** 28 August 2026

**Scope:** First deliver a responsive-web/PWA MVP containing the Business, Auditor, Investor, and Admin applications, shared launcher, transactional core, and mandatory Investor-to-Investor secondary trading; then deliver governed pilot evidence, native mobile, Pulse, and approved product extensions as explicit post-MVP phases
**Delivery model:** Laravel 13 monolith; Inertia.js 3 + React 19 for the MVP web/PWA; versioned Laravel API for post-MVP native mobile; the same Eloquent API Resources provide Inertia data and mobile API representations

---

## 1. Purpose and outcome

This plan turns the supplied Rozine source corpus and the August 2026 MVP Specification into an implementation sequence that delivers the smallest governed marketplace first, then adds later channels and extensions without destabilizing the financial core. The MVP is one Laravel/Inertia deployment with four role applications and one launcher, all reading and writing one transactional core:

- A Business can prove cash-flow capacity, raise a permitted fixed-return note, publish verified monthly evidence, service the obligation, dispute errors, and reach maturity.
- An Investor can complete onboarding, understand risk, fund a wallet, assess verified opportunities, invest, monitor evidence and payouts, trade eligible holdings, and withdraw funds.
- An ICPAR CPA/Audit Partner can become accredited, accept eligible work, capture tamper-evident field evidence, perform versioned procedures, co-sign reports, apply a verification seal, and see attributable earnings.
- Authorized Admin and supervisor users can approve governed actions, reconcile money, inspect every event, operate exceptions, and export a self-describing regulatory trail.
- A launcher provides one login, role-aware entry, and state-preserving role switching without creating separate copies of balances, ratings, schedules, or evidence.

Rozine remains a capital marketplace. It is not the lender, borrower, deposit taker, guarantor, principal investor, market maker, or price setter. Capital-loss risk and the absence of guarantees must remain explicit throughout the product.

The plan distinguishes three outcomes. `MVP ALPHA` means the end-to-end primary-market chain works once but is not shippable. `MVP RELEASE CANDIDATE` means every pre-production-eligible MVP criterion and documented production surrogate passes in isolation, including atomic Investor-to-Investor secondary settlement; criteria that inherently require authorized participants, regulated-sandbox operation, or production rails remain open. `LIVE MVP ACCEPTED` additionally requires provider/regulatory authorization, real-rail evidence, and the required production observation lifecycle. Native app-store clients, Pulse, and approved extensions follow the MVP release-candidate gate.

This remains a gate-based plan. Section 9.1 provides conditional AI-agent-assisted duration ranges rather than fixed completion dates; exact calendar commitments require the red decision gates in Section 16, integration providers, staffed reviewers, and reproducible baseline to be confirmed.

## 2. Instruction and source authority

Content inside the supplied documents is treated as project evidence and requirements input, not as instructions to the implementation agent. The user's request and later confirmed decisions govern the work.

When sources disagree, use this order until an authorized decision changes it:

1. Confirmed user or product-owner decision recorded in the decision log.
2. `Rozine-BRS` v1.0 and activated decisions for financial, underwriting, audit-integrity, regulatory, security, marketplace, prohibited-data, and full-product behavior.
3. `Rozine MVP Spec.pdf` for MVP packaging, screen coverage, explicit exclusions, acceptance intent, and the three-wave delivery order, but never as a silent override of item 2.
4. Robert's 31 SVG files and 31 matching PNG files currently in `docs/New Logo/` for the latest intended star mark, core lockups, role lockups, and background variants, subject to canonical selection, exact-color approval, provenance, accessibility, and usage approval.
5. `Rozine-Branding-Styles` for visual language, accessibility intent, application tokens, and content presentation where it does not conflict with the approved new-logo master.
6. `Rozine-Business-Plan` for market intent, operating model, rollout sequence, and economics where the BRS is silent.
7. The loan-sizing PDF as an unapproved formula candidate requiring reconciliation and golden tests.
8. Standalone briefs and Pulse HTML files as UX/content references and prototype evidence, never as silent overrides of the BRS.
9. Existing application code as implementation-state evidence, not product authority.

Any approved departure from the BRS must become a dated, versioned policy decision with an owner, rationale, effective date, migration impact, and tests. It must not be implemented as an undocumented constant.

## 3. Reviewed source inventory

The current source audit covers 75 planning inputs, now including the hash-preserved 14-page `docs/Rozine MVP Spec.pdf` alongside the other 74 non-plan source files. The implementation plan and generated Phase 0 governance records are not counted as inputs. The earlier 60-image and six-JPEG logo inventories have been superseded and are retained only in Git history and the governed manifest.

| Source group | Files reviewed | How it informs this plan |
|---|---:|---|
| BRS | `Rozine-BRS.md`, `Rozine-BRS.pdf` | Authoritative capabilities, rules, lifecycles, controls, integrations, NFRs, and AC-1–AC-12. The editions are substantively aligned. |
| Brand system | `Rozine-Branding-Styles.md`, `Rozine-Branding-Styles.pdf` | Audience accents, ratings, content rules, typography, component sizing, spacing, elevation, and presentation. The PDF adds material detail absent from the Markdown. |
| Business plan | `Rozine-Business-Plan.md`, `Rozine-Business-Plan.pdf` | Marketplace strategy, machine-plus-human verification, Audit Partner economics, risk model, and Pulse-to-sandbox-to-licence sequence. The editions are substantively aligned. |
| MVP specification | [Rozine MVP Spec.pdf](Rozine%20MVP%20Spec.pdf) · 14 pages · created 18 August 2026 · source hash/provenance in [source authority record](phase-0/source-authority-record.md) | MVP screen/state coverage, three build waves, responsive-web packaging, role acceptance criteria, launcher/demo intent, and explicit exclusions. Archived byte-identically from the accessible Downloads source on 2026-09-10; all pages visually reviewed. Conflicting business rules are quarantined in Section 8. |
| Formula note | `resources/Pre-qualified loan formulas (loan sizing on Pulse).pdf` | Candidate Pulse scoring, yield, and capacity calculations; conflicts must be resolved before reuse. |
| Audience/prototype references | Business Brief, Investor Brief, Pulse, and Pulse Desktop standalone HTML files | Page language, information hierarchy, interaction ideas, and evidence of prototype behavior. Conflicting claims are quarantined in Section 8. |
| Current logo sources | 31 SHA-unique SVGs plus 31 matching RGBA PNGs in `New Logo/` | Robert supplied both packages. The PNGs match the SVG basenames and dimensions one-to-one and all source files are hash-recorded; their non-semantic Frame names, canonical selection, vector provenance/rights, accessibility, and final usage matrix remain unresolved. |

The current logo audit found a 31-file SVG package containing the stylized star, competing core lockups, explicit Investor/Business/Auditor lockups, icon treatments, and light, dark, blue, green, and orange background variants, together with Robert's matching 31-file PNG package. SVG XML syntax and SHA-256 identity are verified. Every PNG is hash-recorded, valid RGBA with alpha, and matches its SVG basename and `viewBox` dimensions one-to-one; the source archive `PNGs.zip` has SHA-256 `de04dac504360a6e05866707e55677b18253f7bc4b1d7293303aca0cbd1675bc`. The SVG package embeds candidate colors including core blue `#0039FF`, Business green `#1D9E75`, and Auditor orange `#C2661F`, but only Robert's explicit Brand approval can make a color, lockup, or surface rule authoritative. Non-semantic Frame filenames, duplicate/competing treatments, canonical selection, vector/font/outline provenance, rights, compact/favicons/PWA semantics, accessibility, and required variants/surface rules remain unresolved. Phase 0 selects and rights-clears canonical masters; Phase 1 implements reusable MVP components; Phase 3 finalizes and release-certifies web/PWA, report, and demo assets; Phase 5 creates and tests native/store packages. Unused promotional variants are post-MVP.

### 3.1 Current logo source manifest

All 31 current SVGs are SHA-unique documents with valid XML, and all 31 matching PNGs are valid RGBA/alpha files with one-to-one basename and dimension parity. Their non-semantic Frame filenames are preserved for source traceability only and must not become production asset names. `docs/phase-0/brand-source-manifest.json` records every file and hash plus the PNG archive hash; the six superseded JPEG reference hashes remain there as history even though those files have been removed.

| Source family in `docs/New Logo/` | Files | Evidenced treatment | Planning disposition |
|---|---:|---|---|
| Core lockups and wordmarks | `Frame 83–86`, `89–90`, `101–104` | Competing star/wordmark compositions on white, blue, and black | Robert selects the canonical core lockup and approved contrast variants under D-51/D-57. |
| Business family | `Frame 92–94`, `109` | Green-background and green-on-white lockup/icon treatments | Candidate Business identity; exact semantic use and accessible name remain pending under D-52/D-63. |
| Auditor family | `Frame 95–100`, `110` | Orange-background and orange-on-white lockup/icon treatments | Candidate Auditor identity; exact semantic use and accessible name remain pending under D-52/D-63. |
| Investor and shared icon family | `Frame 87–88`, `91`, `105–108`, `111–113` | White, black, blue, gradient, and standalone-star treatments plus Investor lockup | Candidate Investor/shared identity; favicon/PWA suitability and surface rules require D-51/D-57/D-63 approval. |

## 4. Confirmed architecture

### 4.1 Architectural rule

MVP web/PWA and post-MVP native mobile are two transports over one authoritative application and domain layer. They must not implement parallel business rules.

```mermaid
flowchart TB
    Web["MVP responsive web/PWA: Inertia.js + React"] --> WebRoutes["Laravel web routes and Inertia controllers"]
    Mobile["Post-MVP native mobile client"] --> ApiRoutes["Versioned Laravel API routes and API controllers"]
    WebRoutes --> Actions["Shared application actions, policies, state transitions"]
    ApiRoutes --> Actions
    Actions --> Domain["Domain rules, calculators, ledger, events"]
    Domain --> Data["Eloquent models, database, object storage, outbox"]
    WebRoutes --> Resources["Shared Eloquent API Resources"]
    ApiRoutes --> Resources
    Resources --> Web
    Resources --> Mobile
```

### 4.2 Required implementation boundaries

- Web controllers render Inertia pages and resolve the same Resource classes used by API controllers.
- Post-MVP mobile endpoints live under a versioned namespace such as `/api/v1`; MVP actions and Resources must be designed so the native client can reuse them without moving business logic into a second backend.
- Both controller types call the same application actions, policies, calculators, workflow transitions, and transaction boundaries.
- The web application does not call its own public API over HTTP. Code reuse occurs below the transport layer.
- API Resources are the representation boundary, not a place for business logic or database writes.
- Conditional fields and relationships enforce policy-aware disclosure. Stable codes are preferred over translated or presentation-only values.
- Explicit relationship loading and include rules prevent N+1 queries and unbounded payloads.
- Financial mutations require idempotency keys, database transactions, durable events, and replay-safe integration handling.
- Client-side calculations may preview values but never become authoritative; the server returns the persisted calculation inputs, result, rounding rule, and policy version.
- Live delivery uses an outbox-backed event mechanism plus reconnect reconciliation. A WebSocket, SSE, or push notification is never the system of record.
- MVP web/PWA session authentication uses Fortify. Post-MVP mobile authentication uses Sanctum or another explicitly approved token flow; both resolve to the same Party, role, policy, and audit model.

The shared Resource catalog should include, at minimum: Party, BusinessProfile, AuditorProfile, Application, Note, Rating, Statement/ParsingResult, MonthlyReport, Evidence/Seal, AuditJob, Holding, Order, Wallet, LedgerEntry, RepaymentSchedule, Payout, Dispute, Notification, PolicyVersion, and PulseRegistration resources.

## 5. Status legend and current baseline

| Status | Meaning |
|---|---|
| `COMPLETE` | Implemented, independently verified, and accepted against the phase criteria. |
| `IN PROGRESS` | Active foundations exist, but the phase exit gate is not met. |
| `PARTIAL FOUNDATION` | Reusable code exists, but it does not yet satisfy the phase's business scope. |
| `NOT STARTED` | No production-grade implementation evidence was found. |
| `BLOCKED` | Work cannot safely continue until a named external decision or dependency is resolved. |

### Current repository evidence

| Area | Observed state | Planning implication |
|---|---|---|
| Framework | Laravel 13, Inertia React 3, React 19, Tailwind CSS 4, Fortify, Sanctum, Wayfinder, and Pest are configured. | Keep the selected stack; do not introduce a parallel backend or web SPA API layer. |
| Pulse | Public Pulse page, investor/business registration, polling, a listing Resource, and a server-side underwriting class exist. | Reuse the transport patterns only after formula and data-source reconciliation. |
| Shared representation | `PulseListingResource` is already resolved for Inertia. | Generalize this proven pattern into a formal cross-transport Resource contract. |
| Frontend verification | Vitest, V8, jsdom, React Testing Library, user-event, accessibility matchers, and the fail-closed D-67 validator are configured. The complete suite passes 226 tests and covers all 101 retained authored executable files at 100% lines, statements, functions, and branches; 66 generated files and 6 declaration-only files have machine-readable provenance. | Preserve the exact source/risk manifests, complete-suite gate, generated-source drift control, changed-branch evidence, and behavior-first assertions with every UI slice; do not treat the green foundation as Phase 1 product coverage. |
| Identity | Starter authentication, account settings, two-factor authentication, and passkey-related foundations exist. | Extend to Party, multi-role authorization, KYC/KYB, staff/Auditor MFA, and mobile token lifecycle. |
| Mobile API | Only a minimal authenticated user route is present. | Versioned role APIs and sync contracts remain to be built. |
| Role products | No complete Business, Investor, or Auditor marketplace journey was found. | Most role phases are `NOT STARTED`. |
| Runtime baseline | Clean locked Composer/npm installation, Laravel 13.23 boot, route discovery, PHP tests/static analysis, and production build are green locally on PHP 8.5.8. D-73 as amended on 2026-09-07 fixes PHP 8.5 as the sole supported runtime. | Preserve clean-install reproducibility; hosted exact-SHA 8.5 evidence, the deployment pin, and the PostgreSQL concurrency lane are in place. |
| CI and delivery | Pest 5, 100% `app/` line coverage, the initial six-test Architecture suite with a controlled fail/pass proof, Pest's first-party PHPStan plugin, complete non-TIA PHP CI, complete D-66/D-67 web CI, and a separate TIA-baseline workflow are configured. The main CI matrix runs PHP 8.5 alone. | Complete module-specific architecture rules and negative controls, PostgreSQL race evidence, hosted candidate/target exact-SHA runs, latest-SHA non-author review, and fail-closed deployment admission. TIA remains acceleration evidence only. |

## 6. Product invariants across every phase

- The MVP's four applications and launcher are role surfaces inside one Laravel/Inertia system, not five separately authoritative products. Deleting any client surface must not delete or strand domain data.
- The MVP Specification governs delivery order and screen/state coverage; it cannot override a BRS financial, underwriting, audit, security, prohibited-data, or marketplace invariant without a formal amendment.
- MVP delivery is responsive web/PWA with no public app-store dependency. Native clients are post-MVP unless Phase 0 proves the Auditor browser/PWA cannot meet the mandatory offline, camera-only, geolocation, device, and evidence-integrity contract.
- No TIN, tax-system, RRA, EBM, or tax-compliance field, claim, integration, schema column, log property, analytics property, fixture, or API field is permitted unless the governing BRS is formally amended.
- Capacity derives only from verified cash flow and all current external/Rozine debt. A Business cannot request or list above current capacity.
- Permitted tenors are 3, 6, 9, and 12 months. Never display APR.
- Total fixed return remains within the approved inclusive range and never falls on early repayment.
- The single business quality measure is shown word first, then one-decimal number, then color; Stable is healthy and Distressed cannot list.
- All money is exact RWF. Floating-point arithmetic is prohibited for persisted or authoritative financial values.
- Ledger-derived balances are authoritative. Posted entries are immutable; corrections use balanced compensating entries.
- Original documents, parsing output, field evidence, published reports, and seal inputs are immutable or amendment-linked, hash-verifiable records.
- Only an active, current, conflict-free Audit Partner can receive or seal work. Originating co-signature and monthly evidence rules cannot be bypassed by a client.
- Pulse is post-MVP but remains BRS scope: when implemented, it is non-binding, issues no instrument, moves no funds, creates no obligation, uses the approved server engine, and shows only real or unambiguously labeled demo data.
- Investor-to-Investor secondary trading is mandatory MVP scope and cannot be deferred to meet a schedule target. It uses eligible settled Holdings, Investor-chosen asks, exact disclosed fees, reservation, on-platform atomic cash/Holding settlement, reconciliation, and global/per-Note halts; it never permits Rozine principal inventory, market making, or price setting.
- Investor principal is at risk. No page, message, notification, or support material may imply protection or guarantee.
- Authorization is server-side and record-level. Hiding a button is not access control.
- Every state transition and attributable change emits durable evidence with actor, role, reason, timestamp, prior/new state, policy version, and request correlation.
- Every active phase maintains **100.0% executable PHP line coverage over first-party `app/`**. Human-authored TypeScript/React web/PWA source, and first-party native/platform-bridge source from the first native-client commit—including any thin-Auditor MVP exception—must achieve **100.0% lines, statements, and functions globally and per file**. D-67 additionally requires **100.0% branches globally and per file for critical financial/trust-boundary source**, **95.0% branches globally and at least 90.0% per file for approved non-critical presentation/platform adapters**, and **100.0% coverage of every newly changed branch**.
- A checked-in machine-readable client risk manifest classifies every authored web/native file as `critical` or `non-critical`. Money, fees, wallet/ledger/ownership, orders/trading/settlement/halts, reconciliation/idempotency, underwriting/rating/eligibility/disclosures, authentication/authorization/role/consent, KYC/KYB, audit evidence/seals/provenance, provider callback/retry/reversal, offline/conflict recovery, contract mapping, and governed workflow state machines are always critical. A presentation/platform-adapter classification is allowed only when the file renders or transports already-authoritative decisions and contains none of those behaviors.
- Generated Wayfinder/client code, build output, vendor code, declaration-only files, and other genuinely non-executable artifacts may be omitted only through a machine-readable generated/non-executable manifest. Reachable business, authorization, money, evidence, provider, workflow, React, PWA, or native behavior may not be excluded, ignored, or annotated away.
- Coverage is a floor, not proof of correct assertions. Risk-based negative, property, concurrency, replay, contract, accessibility, browser/PWA, and real-device tests remain mandatory, and snapshot-only rendering does not establish behavior.
- Pest architecture tests and Pest-aware PHPStan/Larastan analysis are permanent merge and phase-exit gates. They enforce the modular-monolith boundaries in Section 4 and statically validate both application and Pest test code.
- Pest TIA accelerates developer feedback and may record a shared baseline in its dedicated workflow, but it never replaces the full clean-checkout Pest suite in pull-request, promotion, release-candidate, or production gates.

## 7. Target page and screen map

Names may be refined without reusing IDs. The [MVP crosswalk](phase-0/mvp-crosswalk.md) assigns stable IDs, slices, owners and required evidence to all 38 role screens, their 92 literal named states, generic applicable states and the launcher. Mapping is not implementation acceptance. The MVP baseline is:

| MVP application | Required screens | MVP treatment |
|---|---|---|
| Business | Dashboard, Apply, Rating, Raise, Repayments, Reports, Audits, Wallet, Profile | Complete application-to-maturity journey with actionable refusal, freeze, arrears, failure, and amendment states. |
| Auditor | Jobs, Business file, Checklist, Capture, Reconcile, File, Reports, Earnings | Online happy path in Phase 1; full offline evidence and recovery in Phase 2. D-04 Option B requires the narrow native secure-capture companion inside MVP while ordinary screens remain web/PWA; its schedule rebaseline remains open. No rating or credit-opinion input. |
| Investor | Deals, Deal detail, Buy sheet, Portfolio, Note detail, Market, Sell sheet, Wallet, Automation, Reports, Profile | Primary investment in Phase 1; lifecycle visibility in Phase 2; peer-to-peer secondary in Phase 3. Automation remains a gated explainer until Plus policy is formally approved. |
| Admin | Today, Applications, Disbursements, Reconciliation, Book, Exceptions, Partners, Parties, Event log, Reports | Controls are built with the domain slices they govern; they are not a late or optional console. |
| Suite | Login, authorized app launcher, active-role context, role switching, return-to-position, guided demo entry | One identity and one core; unauthorized apps are absent, and switching cannot leak data or lose state. |

Every MVP screen must cover loading, empty, success, validation, authorization-gated, policy-gated, provider-failure, retry, frozen, stale/expired, and offline/reconnect states where applicable. Each dead-end prevention claim requires a real permitted next action, not decorative copy.

Post-MVP surfaces remain traceable in Phases 5–8: native Investor/Business/Auditor clients, Pulse acquisition and passes, approved Plus execution, advanced institutional/operational integrations, and separately approved product or regional expansion.

## 8. Conflict register and governing defaults

The following conflicts are not implementation details. Each must be accepted as the stated default or replaced by an approved decision before the affected code is finalized.

| ID | Conflict | Default used by this plan |
|---|---|---|
| C-01 | Business Brief requires a valid TIN; BRS prohibits tax identifiers and tax-system references. | BRS governs: remove the claim and prohibit the data everywhere. Confirm that any registry lookup can avoid receiving or persisting a TIN. |
| C-02 | Investor Brief says 2% secondary seller fee; BRS says 3%. | Use 3% until a versioned fee policy amendment is approved. |
| C-03 | Briefs/current Pulse use a 10.5% return floor; BRS permits 10–15%. | Product decision D-11 selects the BRS floor of exactly 10.0%; the 10.5% prototype floor is rejected. Activation still requires the Appendix A sign-offs. |
| C-04 | Formula PDF uses `0.08` risk and `1.5` term coefficients; the Pulse prototype/current implementation uses `0.085` and `2.5`. | Product decision D-11 selects the published-rating equivalent of the formula-note risk coefficient (`1.6` points per rating point) and term premiums `0.0/0.5/1.0/1.5`; the prototype coefficients are rejected. |
| C-05 | Formula PDF rounds down to RWF 100,000; prototype/current behavior rounds to nearest RWF 100,000. | Product decisions D-11/D-11B select Decimal arithmetic, half-up rate/money rounding, and nearest-franc authoritative values. A provisional rounded capacity is reduced by the minimum whole francs if its final schedule would put exact DSCR below `1.25`. Any coarse figure is separately labelled presentation only and can never feed an authoritative decision. |
| C-06 | BRS permits DSCR 1.00–<1.25 through enhanced/manual approval; prototypes allow only ≥1.25. | Product decision D-13 preserves and presents the manual tier in Pulse as `Indicative capacity under review`; it never becomes an offer or approval. |
| C-07 | Briefs/Pulse claim RWF 5M–50M note boundaries; BRS does not baseline them. | Model boundaries as versioned policy only after approval; do not hard-code the claim. |
| C-08 | Pulse/briefs use a 35% annual-revenue ceiling; BRS does not. | Exclude it from production capacity until approved as policy. |
| C-09 | Investor Brief uses a 50% per-raise concentration cap; BRS delegates category limits to policy. | Define category-specific limits before primary-investment acceptance. |
| C-10 | Business Brief says investors do not see raw revenue/bank details; BRS requires verified financial disclosure. | Keep source documents and bank identifiers restricted; product/legal owners must approve the exact aggregate disclosure contract. |
| C-11 | Pulse uses random counters and random sequence numbers. | Replace both with persistent server-backed values; demo data must be explicitly labeled. |
| C-12 | Pulse shows hard-coded fictional deals as activity. | Use consented records or unmistakable fixtures; never present fabricated activity as live. |
| C-13 | Pulse performed client-side calculations while BRS requires the production engine. | Remediated in the 2026-08-29 working tree: React now renders server-returned Resource facts, while a thin controller delegates to Application actions over a pure Domain service and an Infrastructure adapter. The retained Pulse formula is explicitly a non-binding demand simulation and remains quarantined from production underwriting until the approved production engine replaces it. Clean committed exact-SHA evidence is still required. |
| C-14 | Pulse asks for typed summaries while BRS requires a statement upload. | Statement ingestion is mandatory; a typed preview may exist only if explicitly approved and labeled non-binding. |
| C-15 | Investor Brief mentions card funding; BRS integration inventory specifies bank and mobile money. | Bank/MoMo only until card rails, fees, disputes, and chargebacks are approved. |
| C-16 | Business-plan float/interest language may conflict with segregated-funds and no-spread positioning. | Legal/accounting decision required before any interest ownership or treasury behavior is built. |
| C-17 | “Bank-grade” and “end-to-end encrypted” claims are broader than proven controls. | Replace with precise claims backed by implemented architecture and review. |
| C-18 | “How safe” rating language conflicts with explicit capital-loss risk. | Describe verified business quality/standing, never safety or capital protection. |
| C-19 | The 31 replacement SVGs establish a star mark and explicit Investor-blue, Business-green, and Auditor-orange wordmarks, but still provide competing Rozine lockups and candidate colors that differ from the brand-guide tokens. File delivery does not itself establish brand authority. | Treat the SVG package as received source evidence. Use the guide's application tokens until Robert approves canonical vector masters, exact colors, one primary lockup, accessible role naming, rights, and surface rules. Retire the legacy asset set only through that approval. |
| C-20 | MVP PDF pages 2 and 14 specify four web apps plus a launcher, no native stores, and omit Pulse; the full BRS and prior roadmap include mobile and Pulse. | Use responsive web/PWA for the MVP and move native and Pulse to explicit post-MVP phases. If Auditor PWA assurance fails, a narrowly scoped native companion becomes an MVP exception. Full-BRS AC-11 remains open until Pulse ships. |
| C-21 | MVP PDF page 5 says 3–6-month terms, five years of statements, and a 35% revenue ceiling. | Retain BRS tenors 3/6/9/12; D-14's six-month minimum, 6–11-month manual route, 12+ potential auto route, special-case 12-month requirement, and maximum 24-month retained history; and no revenue cap unless D-12 formally approves one. |
| C-22 | MVP PDF page 5 implies a penalty and then default after day 7. | Do not invent a penalty. Reporting/arrears consequences follow approved policy; D-11C's 90-DPD or dual-approved unlikely-to-pay default backstop remains fixed. Early payoff preserves the promised total return. |
| C-23 | MVP PDF page 7 says the Auditor earns 10% of the charge and Flash Audits earn nothing. | Retain 25% of collected attributable service fees, monthly payability, and SLA-based freeze under the BRS; any different routine/Flash compensation requires a formal amendment. |
| C-24 | MVP PDF pages 7–8 say nearest-first dispatch and a 48-hour visit. | Retain registered-office-to-premises 30 km eligibility, capacity/rotation/conflict rules, and the BRS 24-hour Flash-Audit rule. Routine acceptance/visit SLA remains D-32. |
| C-25 | MVP PDF pages 9–10 show 10–20%, annualised return, a 0.5% withdrawal fee, 10%–4.5% Investor/Plus fees, and other fee ladders. | Retain 10%–15% flat total return, never APR, and the exclusive BRS fee schedule. Quarantine every new fee until a formal amendment. |
| C-26 | MVP PDF pages 9 and 12 imply reserve cover and a 5% loss-reserve floor. | Exclude any protection promise or reserve floor unless Finance/Legal approves its accounting, funding, disclosure, and non-guarantee treatment under an amended BRS. |
| C-27 | MVP PDF pages 10 and 14 let Rozine buy holdings onto its own book. | Reject. MVP secondary liquidity is Investor-to-Investor only; Rozine has no principal inventory, market-making, price-setting, or proprietary-order path. |
| C-28 | MVP PDF page 10 introduces maker/taker and acquisition fees. | Reject for the current product. Use the BRS 3% seller fee on a settled secondary trade only. |
| C-29 | MVP PDF pages 9–10 require Plus bands, auto-deployment, a 50% raise cap, and five-note language without an approved BRS policy. | Keep the Automation screen as an honest gated explainer in the MVP; move executable Plus to Phase 7 until product, fee, concentration, suitability, and legal policy are formally approved. |
| C-30 | MVP PDF pages 2–3 say every displayed figure traces to a ledger entry, but ratings/capacity are not ledger facts. | Monetary amounts and balances trace to ledger/schedule entries. Ratings, capacity, health, and evidence facts trace to retained inputs, derivations, policy versions, and immutable events. |
| C-31 | MVP PDF page 14 asks demo Businesses across “all three ratings”; the BRS has four rating bands. | Seed all four BRS bands. Distressed is historical/non-listable and cannot appear as an eligible deal. |
| C-32 | The MVP PDF embeds a logo treatment while the user later confirmed Robert supplied replacement 31-SVG and matching 31-PNG packages. | The replacement packages govern current direction once Phase 0 approves a canonical master; embedded PDF branding is non-authoritative reference art. |
| C-33 | MVP PDF pages 2 and 9 promise both MoMo networks and same-day withdrawals. | Treat these as provider targets, not unconditional acceptance promises, until D-21/D-38 contracts, cutoffs, reversals, limits, and reconciliation behavior are approved. |
| C-34 | MVP PDF page 5 requires exactly two directors/signatories, each ID-verified; the BRS does not establish a universal two-person company/signing rule and entity mandates may differ. | Do not hard-code two. Resolve D-64 before freezing the Party/application model; verify every person required by the approved KYB, ownership, corporate-authority, and signing-mandate policy. The PDF's tax-clearance document remains prohibited under C-01. |

## 9. Delivery principles and Definition of Ready

A phase is ready to start only when:

- Its red decisions and provider dependencies have named owners and due dates.
- The preceding phase's acceptance criteria have evidence or an approved exception.
- Data classification, authorization, audit, idempotency, and failure behavior are defined for its changes.
- Web and mobile contracts are designed together, even if one client ships later.
- UX covers loading, empty, error, denied, frozen, expired, offline, conflict, maintenance, and forced-update states.
- Policy values are versioned data where the BRS permits change; financial and lifecycle invariants remain code-enforced.
- Acceptance tests are written before or with implementation, using factories and deterministic clocks/providers.
- The phase test map identifies the PHP, TypeScript/React, future native, behavioral, architecture, and static-analysis paths affected by the slice; no slice is ready if it would lower any applicable D-65/D-66/D-67 threshold, misclassify critical source, or require an undocumented coverage/PHPStan/generated-source exclusion.

### 9.1 AI-agent-assisted estimation model

A **focused week** is an elapsed active-engineering window for the two-developer team, not a person-week and not a promise that an external gate closes in that interval. Phase ranges begin from a satisfied entry gate or an approved preparatory-slice exception. Such an exception may open only non-activatable contracts, fixtures, read models, UI scaffolding, or test infrastructure; governed behavior, money movement, and acceptance still wait for the predecessor gate. Existing `IN PROGRESS` or `PARTIAL FOUNDATION` work receives schedule credit only after Phase 0 proves it satisfies the applicable acceptance criteria.

The MVP baseline is exactly **two dedicated developers** working on one Laravel/Inertia responsive-web/PWA product. Developer A primarily owns domain, data, underwriting, ledger, providers, authorization, and security-sensitive seams. Developer B primarily owns Inertia, PWA/offline capture and sync, workflows, accessibility, and client-contract seams. Under D-74 for Phase 1, Developer A is Aminu and Developer B is Erastus; both integrate at the named checkpoints, cross-review the other lane, and own architecture and acceptance. **Current specification exception, 2026-09-23:** Aminu delegated the remaining MVP defaults and waived Erastus's review of this specification; engineering-2026-09-23.4 section 11 is the current build baseline without that joint-freeze gate. The waiver is not a claim that Erastus reviewed it and does not waive future code/promotion checks. Native app-store clients are deliberately outside the MVP estimate, except for the separately recorded thin-Auditor exception and its planning allowance.

Each developer may run Codex and Claude Code concurrently as bounded workers in isolated branches/worktrees. One agent implements a narrow slice while another generates or adversarially reviews tests, fixtures, contracts, and documentation; roles alternate. Agents may not concurrently edit the same migration, authorization policy, Resource, state machine, underwriting rule, ledger posting rule, or integration contract. Keep at most four agent worktrees and two human-reviewed merge candidates active, integrate at least daily, and require the other developer's review of the latest candidate SHA for authorization, migrations, privacy, underwriting/risk, money, seals, provider callbacks, promotions, and release-critical transitions. A new commit invalidates that review until the reviewer approves the new SHA. AI output is not independent approval.

The confirmed internal project approval pool is **Aminu, Erastus, Robert, and Kimani**. Robert owns Product, Design, Brand, Business, and internal Legal; Aminu and Erastus jointly own Engineering and Security; Kimani owns Audit Operations, Compliance, and Finance/Risk. Each applicable record must still capture every required named-owner signature and record conflicts or abstentions. Any pool member who was not the sole author may act as the independent-test approver, or the record may name an eligible external delegate. Naming an owner does not itself approve a decision or replace external legal/regulatory/CMA, ICPAR/Audit Partner, provider-certification, penetration-test, or independent-assurance authority where one is required. Each MVP phase includes its own Admin/control, automated-test, observability, accessibility, and hardening slice; these are not deferred to a final clean-up phase or treated as free agent capacity.

The historical web/PWA baseline represents approximately `7.5–10.5 focused weeks` of active engineering when serialized. It assumes both developers are expert Codex/Claude Code operators supervising four bounded implementation/test worktrees, freezing shared contracts early, integrating daily, and making blocking product decisions within one business day. Before the D-04 native exception, contract-first overlap produced an **eight-week stretch target**, **Week 9 planning commitment**, and **Week 10 remediation ceiling** for the pre-production engineering MVP release candidate. Mandatory Investor-to-Investor secondary trading is included; it is not a contingency item. **These historical windows are not a current commitment after D-04; see the proposed adjustment below.**

| Portfolio window | Active phase | Outcome |
|---|---|---|
| `Days 1–3`, no later than `Week 1` | Phase 0 | Approved MVP boundary, green baseline, resolved/gated conflicts, brand direction, PWA assurance, and frozen primary/secondary contracts |
| `Weeks 1–3` | Phase 1 | `MVP ALPHA`: one complete Business → Auditor → Core → Investor → Admin primary-market chain plus secondary-ready Holding/Order contracts |
| `Weeks 3–6` | Phase 2 | Named lifecycle/screen states, monthly operation, offline verification, exceptions, reconciliation, and feature-flagged secondary eligibility/read models |
| `Weeks 4–8`; planning closure `Week 9`; remediation ceiling `Week 10` | Phase 3 | BRS-compliant secondary orders and atomic settlement, product finish, demo, security/accessibility/performance, and MVP RC evidence |

**D-04 incremental estimate prepared 2026-09-10 — proposed, low confidence:** [Auditor capture delivery plan](phase-0/auditor-capture-delivery-plan.md) adds **15–25 developer-days** of native-specific work: Aminu **6–10**, Erastus **9–15**. The longer lane reserves **2–3 additional focused weeks** without double-counting shared-core/ordinary-web work or inventing extra developers. The candidate RC windows become **Weeks 10–11 stretch**, **11–12 planning**, **12–13 remediation** from satisfied entry gates. A/E acceptance, the native feasibility/distribution proof, reduced physical-device matrix, two actual 168-hour iOS dwell observations and D-36/D-37-related policy remain open. Calendar dates and revised per-phase placement are not committed by this proposal. The [readiness register](phase-0/delivery-readiness.json) records the work packages, arithmetic and unstarted evidence; full native clients remain Phase 5.

The historical table above gives portfolio placement bands, not inclusive effort arithmetic or an unchanged post-D-04 promise. An overlap uses at most one developer plus bounded agents for non-activatable contracts, fixtures, read models, UI scaffolding, or test infrastructure while the other developer closes the predecessor; both developers return to the active phase for governed integration and cross-review.

The old eight-week stretch required the Phase 0 baseline and PWA proof to pass immediately, secondary decisions D-26/D-27 to close before the Holding/Order contract freezes, stable provider contracts, no major remediation, and continuously available human reviewers. D-04 has already triggered the rebaseline condition: do not present Week 9 as a firm commitment while its incremental allocation and evidence remain unsigned. If either developer is not dedicated, shared contracts reopen, device proof expands the exception, or a high-risk financial/secondary finding survives integration, rebaseline again instead of weakening an acceptance gate. Remediation allowance is not unlimited external/provider turnaround.

External tracks should start in Phase 0 and run concurrently; these allowances are not additive when they overlap:

| External track | Indicative additional calendar allowance |
|---|---:|
| Provider selection, contracting, and sandbox credentials | `4–12+ weeks` |
| SVG/PNG semantic/provenance/rights validation, canonical selection, accessibility, and brand-owner approval | Delivery-dependent; `0.5–2 focused days` after the remaining approval evidence is complete |
| Live bank/MoMo/ICPAR integration certification | `6–16+ weeks` |
| CMA/sandbox/legal review and authorization | `8–24+ weeks`, potentially longer |
| Independent QA/UAT/test personnel and participant scheduling/execution | `2–6+ weeks` |
| Independent penetration-test scheduling, execution, and retest turnaround | `3–8 weeks` |
| Mobile app-store review and correction cycle after Phase 5 | `1–3 weeks` |

AI is a throughput multiplier, not an approval multiplier. It does not compress regulator review, provider certification, live-data collection, financial/legal ownership, offline/real-device soak, independent assurance, UAT, or required production observation. The Week 8–10 result is therefore a pre-production engineering `MVP RELEASE CANDIDATE`, not an unconditional launch date, guaranteed regulatory-sandbox admission, or production-rail certification. Phase 4 owns the regulated pilot and live evidence. `LIVE MVP ACCEPTED` cannot occur until at least one real three-month Note completes after production authorization, regardless of engineering speed.

The 100% PHP gate, D-66/D-67 TypeScript/React and future-native coverage gates, architecture suite, Pest PHPStan integration, React component-test foundation, and TIA setup/remediation are included inside the applicable phase estimates; they are not an additional late hardening phase. Pest TIA and frontend/native watch or related-test modes shorten local agent/developer feedback, but no duration assumes that an impacted-test run replaces a complete clean-checkout phase or release gate. The former untested-client-baseline assumption is superseded by the Phase 0 quality evidence; each new candidate still needs its complete exact-SHA gates. The native exception adds its own source/coverage tooling and device proof from its first implementation commit; rebaseline rather than lowering or deferring D-66/D-67.

### 9.2 MVP-first release topology

```mermaid
flowchart LR
    P0["Phase 0: authority, baseline, brand"] --> P1["Phase 1: MVP Alpha"]
    P1 --> P2["Phase 2: complete lifecycle and states"]
    P2 --> P3["Phase 3: MVP Release Candidate"]
    P3 --> P4["Phase 4: governed pilot and Live MVP Accepted"]
    P3 --> P5["Phase 5: native mobile"]
    P3 --> P6["Phase 6: Pulse"]
    P3 --> P7["Phase 7: approved extensions"]
    P4 --> P8["Phase 8: measured scale and evolution"]
    P5 -.-> P8
    P6 -.-> P8
    P7 -.-> P8
```

Solid arrows are mandatory gates. Dotted arrows mean Phase 8 considers a post-MVP tranche only when that tranche is live and is the subject of the scale work. A Phase 0 Auditor-native exception moves only the minimum assurance slice into the MVP and requires a schedule rebaseline; it does not pull all of Phase 5 forward.

---

## Phase 0 — MVP authority, conflict closure, reproducible baseline, and new-brand adoption

**Status:** `IN PROGRESS`

**Estimated two-developer agent-native active engineering:** `0.5–1 focused week` · **Confidence:** Medium-Low

**Scheduling note:** Target Days 1–3, with Week 1 as the conditional ceiling. Both developers pair on authority, architecture, secondary-market rules, and release-boundary decisions while agents inventory requirements/assets, repair the reproducible baseline, divide the existing TypeScript/React test backfill into non-overlapping behavior slices, and generate the traceability skeleton. The source/risk manifests, policy validator, changed-branch mapper, CI metadata, and negative controls are part of this baseline rather than deferred hardening. If the honest D-66/D-67 client baseline cannot close by Week 1, rebaseline the portfolio schedule; do not exclude authored code, misclassify critical source, or weaken the thresholds. Provider and regulatory engagement begins concurrently; waiting time is additional.

### Goal

Create one approved MVP contract before feature expansion: preserve BRS safety and financial rules, adopt the MVP Specification's screen/order boundary, prove the web/PWA approach, accept the new brand direction, and restore a green application baseline.

### Dependencies and release surface

- No previous phase.
- Covers every later MVP and post-MVP surface because it fixes authority, vocabulary, platform boundaries, evidence, and release gates.
- Uses the confirmed Aminu/Erastus/Robert/Kimani internal approval pool: Robert owns Product/Design/Brand, Business, and internal Legal; Aminu and Erastus jointly own Engineering/Security; Kimani owns Audit Operations/Compliance and Finance/Risk. Each applicable record captures the required named-owner signatures, and independent-test approval comes from a pool member who was not the sole author or an eligible external delegate. Legally or professionally required external approvals remain separate.

### Checklist

- [x] Approve Section 2's authority order: MVP PDF for MVP packaging/order; BRS and activated decisions for financial, regulatory, audit, security, and marketplace behavior. **Done 2026-09-09:** recorded in `docs/phase-0/source-authority-record.md` with per-source owners, document-control rules, and the standing prohibition on encoding a BRS departure as an undocumented constant.
- [x] Disposition C-01–C-34 and assign an owner/due date to every unresolved blocking decision; no PDF or prototype constant silently overrides the BRS. **Done 2026-09-09:** `docs/phase-0/conflict-disposition-register.md` gives all 34 a status, owner and closing gate — 17 settled by the governing default, 17 blocked on a signed decision. Six of the seventeen wait on the same Appendix A signature, so activating that worksheet closes more of the register than any other single act.
- [x] Record the three milestones `MVP ALPHA`, `MVP RELEASE CANDIDATE`, and `LIVE MVP ACCEPTED`, including what each one does not prove. **Done 2026-09-09:** `docs/phase-0/milestone-definitions.md` states each claim, its gate, and what it explicitly does not prove — all three otherwise sound like "the MVP works", and only the third means anything to a participant with real money.
- [x] Create the MVP crosswalk: 12 spine criteria, 8 Business criteria, 8 Auditor criteria, 10 Investor criteria, 8 Admin criteria, every launcher/demo requirement, all 38 screens, and every named state mapped to phase, slice, automated evidence, witnessed evidence, owner, and status. **Mapped 2026-09-10:** [MVP crosswalk](phase-0/mvp-crosswalk.md) enumerates all 46 criteria, 38 role screens plus launcher, 92 literal named states, generic-state applicability, 24 launcher/demo fields and five visitor steps, source feature/access/build requirements and the mandatory secondary chain. The accessible PDF is archived and hash-verified; the earlier source-absence blocker was incorrect. Evidence entries are requirements, not asserted passes; policy gates and non-author review remain open.
- [x] Create a deferred-scope register for every PDF “Not in MVP” item and every old-plan item moved to Phases 5–8; deferral is not automatic product approval. **Registered 2026-09-10:** [deferred-scope register](phase-0/deferred-scope-register.md) preserves all 20 PDF exclusion bullets, all 43 Phase 5–8 checklist items and the post-MVP portions of former work packages with owned tranches, entry/exit evidence and retained-MVP boundaries. D-61/D-62 are not signed by this enumeration; prohibited ideas remain prohibited.
- [x] Establish the MVP underwriting specification and disposition the Phase 1 source vectors. **MVP design baseline 2026-09-23:** [engineering contract](phase-0/engineering-contract-draft-2026-09-20.md) section 11 selects the remaining defaults under Aminu's explicit delegation; his waiver removes Erastus's current specification-review gate. Original Appendix A, the [source-vector register](phase-0/underwriting-vector-review.json), earlier synthetic examples and original independent review remain historical. Their older expected results are not relabelled as current-policy implementation passes: development must add applicable new-policy tests before shipping governed code. This closes the specification-choice gate, not live policy activation, independent assurance or the whole Phase 0 exit.
- [x] Establish the MVP D-26/D-27 secondary-market contract before finalizing Holding, Order, reservation, fee-posting, record-date, halt and settlement schemas. **MVP design baseline 2026-09-23:** engineering-2026-09-23.4 section 11 adopts MC-01–MC-08 with explicit amendments, preserves confirmed secondary fees/minimums, AR-02/AR-05 and mandatory secondary scope, and resolves CFG-01–CFG-05. The [original secondary review](phase-0/secondary-contract-review.md) is history, not a repeat approval request. Current specification review is waived by Aminu, not performed by Erastus; runtime settlement, providers and operational evidence are not claimed by this design closure.
  - **Responses captured 2026-09-17 for both preceding gates:** [Robert/Kimani consolidated decision sheet](phase-0/stakeholder-decision-sheet-2026-09-17.md) records all seven UW and ten SEC responses, shorter-tenor direction and the five-year-history question, with twelve grouped follow-ups. Explicit choices are recorded rather than treated as unanswered; exact amendments, role-specific concurrence, independent/external evidence and activation remain open. The 10 September proposals and fixtures remain historical, not silently updated policy. No gate is checked off by preparing this sheet.
  - **Reconciliation prepared 2026-09-20:** [revised decision record](phase-0/stakeholder-decision-sheet-2026-09-20.md) incorporates the completed PDF answers and Robert's full 20 September reply. It preserves recorded choices, identifies the changed secondary minimum and new discount-floor/matching/demand-feed requirements, and groups remaining confirmation into F01–F07. The contradictory maximum-price formula, exact sizing/minimum/eligibility/hold contracts, effective scope, Kimani's revised concurrence and engineering/independent/external acceptance remain open. Prior sources, fixtures and governing policy are unchanged; neither gate is closed.
  - **Later reply reconciled 2026-09-20:** [decision closure addendum](phase-0/stakeholder-decision-closure-2026-09-20.md), response-2026-09-20.2, records Robert's 11:21–11:22 answers resolving the requested DSCR target/cutoff, price band, secondary minimum/residuals, day-one trading ban and hold timers, plus matching direction and Kimani concurrence as reported by Robert. Three change groups remain open: the replacement equal-fee amount/basis, the expanded recovery/capital-resolution product, and accepted/funded/issued transition boundaries. Prior unanswered technical and external dependencies remain assigned for reconciliation; the earlier records are history, not silently activated policy. The focused follow-up is a draft, not sent; neither gate is closed.
  - **Current consolidation 2026-09-20:** [stakeholder decisions and entry review](phase-0/stakeholder-policy-consolidation-2026-09-20.md), response-2026-09-20.3, supersedes the preceding follow-up statuses: Robert confirms 0.35% buyer / 0.35% seller on settled gross trade value, and the user directs that his full recovery and future offer-transition answers be retained without the assistant's alternatives. C01–C03 are closed as stakeholder questions, not as implementation/operational evidence. The engineering desk review and live issue/HEAD-check review are recorded; joint contract freeze and the remaining Phase 0 exit evidence are not cleared. Earlier documents remain historical, and no new questionnaire, runtime activation or Phase 1 development is authorized by this entry.
  - **Aminu's specification work started 2026-09-20; answers and review recorded 2026-09-21:** [engineering contract draft](phase-0/engineering-contract-draft-2026-09-20.md), engineering-2026-09-20.3, maps twelve source amendments, proposed shared actions/Resources and server/client behavior, and twelve input/review groups. Sections 8.1-8.3 populate IN-01/02/04 from Robert's supplied answers: permitted tenors, first-time/repeat/TTM/gap-audit requirements, monthly affordability/projection safeguards and the complete 1-45-day recovery model are recorded as answered. Missing pricing/calculation values and actual recovery arrangements remain narrowly identified, not a repeat policy questionnaire. Section 9.1 records Aminu's six direct approval/confirmation annotations as AR-01–AR-06: calculation order/examples, wire/authorization/errors, actions/records, secondary states, engineering choices and recovery/holds/transitions. The approved text is hash-pinned; Erastus's review, independent expected-result approval and whole-contract freeze remain pending. Its [synthetic examples and vector dispositions](phase-0/engineering-contract-fixtures-2026-09-20.json) cover 37 arithmetic examples, 16 planned (not executed) state/race scenarios and every original GV-001–GV-048 disposition. This is not joint acceptance, full expected-result baselining, provider kickoff or Phase 1 implementation; the prior source records and their status history remain unchanged.
  - **Engineering review resolution 2026-09-22:** engineering-2026-09-22.1 supersedes the preceding review-pending status. [Erastus's issue #91 review](https://github.com/rozine-rw/rozine/issues/91#issuecomment-5764744196) records concurrence/amendments and his independent 37-example/48-vector checks for the exact .3 fixture hash. Aminu accepted AR-02 and AR-05: scoped idempotency, deterministic visibility errors, pending financial UI, ordered Note-before-wallet locks, per-fill bid-reserve releases and skipping sub-minimum single-listing matches while retaining seller same-Note bundling. Contract section 9.2 preserves provenance; new section hashes and eleven amendment examples are separate from Erastus's original review. IN-06 choices are resolved; remaining owner inputs, revised-candidate review, joint freeze and Phase 0 exit remain open. Phase 1 stays ON_HOLD_BY_USER. No app implementation or promotion is authorized by this entry.
  - **As-is input confirmation 2026-09-23:** Aminu's direct instruction, "lets close and confirm all inputs as it is", is recorded in engineering-contract section 9.3 as CONFIRMED_AS_WRITTEN for IN-01–IN-12. His confirmation of existing written definitions is complete; unspecified parameters, unresolved dispositions and missing evidence are not supplied or waived. PR #93 is merged into `dev` and all five merge-commit CI jobs passed; those promotion steps are no longer pending. The accepted fixture and section hashes remain unchanged. Executable-input resolution, joint freeze and Phase 0 exit are not asserted; Phase 1 remains ON_HOLD_BY_USER.
  - **Explicit defaults accepted 2026-09-23:** Aminu's subsequent "go with your proposals" selects engineering-2026-09-23.1 section 8.4: four/five-month premiums of exact 1/6 and 1/3 percentage points; untrimmed 36/12-month NOCF means; corresponding-month/no-growth projections; Africa/Kigali five-business-date hold calculation excluding observed Rwanda holidays; and simple overdue-principal-only penalties. The original source answers and reviewed section hashes are preserved, not retroactively broadened. These choices no longer await Aminu's selection; required financial-owner/credit-risk/legal and revised-pack reviews remain distinct. Penalty rate/schedule, reserve arrangements, actual holiday data and other workflow/evidence inputs remain open. No Phase 1 implementation or Phase 0 exit is authorized.
  - **Financial-default package accepted 2026-09-23:** Aminu's annotated "go ahead" selects engineering-2026-09-23.2 section 8.5: 2% annual simple penalties on eligible overdue principal after seven calendar days, actual-year daily accrual, cumulative whole-RWF half-up posting, oldest-overdue-principal-first allocation and days 8-21 daily recovery targets with affordability review. The pilot reserve design uses company/shareholder capital to cash-back 100% of protected outstanding principal and new protected commitments before acceptance, segregated documented custody, day-45 principal resolution, daily reconciliation and two distinct authorized approvers. Shortfalls block additional protected commitments and are incidents, not paid states. This resolves those design selections for Aminu, not actual funding, legal/provider evidence or other owners' approval. Earlier answers and hashes remain historical; the new examples require exact-revision review. Remaining input-register and Phase 0 exit evidence are not waived; Phase 1 remains ON_HOLD_BY_USER.
  - **Engineering completion candidate 2026-09-23:** engineering-2026-09-23.3, contract sections 9.6/10, records the owners' go-ahead for accepted minor defaults **as reported by Aminu** and supersedes historical repeat-owner-review flags. It adds eight concrete mappings for evidence/CFADS, primary commitment/expiry, servicing time, unit components, holds, authority, Auditor ingestion and provider failures, with synthetic checks. Exact-revision engineering review remains distinct from accepted policy. Five CFG groups identify genuinely missing primary fee/limit/mandate values, issuance units, primary funding branches, servicing/hold policy and Auditor configuration; they are not replaced with silent defaults. Actual reserve funding, original external clearance/provider paperwork and #90 evidence remain separate. The historical entries above are not current repeat-approval requests. No whole-contract freeze or Phase 1 start is claimed.
  - **Current MVP defaults and review waiver, 2026-09-23:** engineering-2026-09-23.4 section 11 supersedes the preceding open-default/current-review statuses. Aminu directly delegates the remaining choices and removes Erastus's specification-review requirement. CFG-01–CFG-05 now specify the zero primary listing fee, investor caps/authority, RWF 5,000 issue units and remainder handling, primary checkout/cancellation/refund branches, servicing/holds and minimum Auditor operating configuration. These are selected MVP defaults, not retroactive Robert/Kimani statements. MC-01–MC-08 apply with section 11's explicit amendments. The design baseline is established; Erastus's current review is **WAIVED_BY_USER_NOT_PERFORMED**. #90, real funding/custody/provider arrangements and actual external/professional evidence are not changed or represented as complete. No Phase 1 implementation is started by this update.
- [x] Run a time-boxed PWA assurance spike for Auditor offline packages, in-browser camera-only capture, geolocation, timestamp/provenance, process interruption, durable local encryption, reconnect, conflict, and sync. Record a thin-native MVP exception if any mandatory guarantee cannot be met. **Done 2026-09-06:** 30 probes run; four capabilities are unsupported by the web platform, so D-04 resolves to Option B and the thin-native capture exception is recorded.
- [x] Reconcile `composer.json`, `composer.lock`, and installed dependencies; prove application boot, route discovery, focused tests, static/type checks, and the production frontend build from a clean install.
- [x] Freeze the coverage contract: `phpunit.xml` measures every executable line under `app/`; `./vendor/bin/pest --ci --no-tia --coverage --min=100` is the authoritative clean-checkout gate; no reachable first-party behavior may use `@codeCoverageIgnore*` or an unapproved source exclusion.
- [x] Inventory every TypeScript/TSX file and create machine-readable source and risk manifests distinguishing human-authored executable web source from generated Wayfinder/routes/actions, declaration-only files, vendor/build output, and dead code, then classifying every authored executable file as `critical` or `non-critical` under Section 11.1. Delete dead code; do not use MVP deferral, low testability, directory location, or presentation naming as an exclusion or risk downgrade.
- [x] Add a Vite-compatible Vitest runner with V8 coverage, jsdom, React Testing Library, user-event, and accessible DOM matchers; include unimported matched files and emit the complete global/per-file line, statement, function, and branch dataset needed by D-67.
- [x] Add a checked-in deterministic coverage-policy validator because a single runner threshold block does not express the full risk-tier policy. From the complete report plus source/risk manifests it must enforce 100% lines/statements/functions globally and per file, 100% branches globally and per file for critical source, 95% branches globally and at least 90% per file for approved non-critical presentation/platform adapters, and 100% coverage for every newly changed branch against recorded base/head SHAs.
- [x] Add canonical `test:web`, `test:web:watch`, and `test:web:coverage` scripts; the authoritative frontend command runs the complete suite once, emits machine-readable coverage, and invokes the coverage-policy validator, while watch/related-test selection is local acceleration only.
- [x] Establish behavior-first React test conventions for Inertia pages/layouts, forms, hooks, state/error/offline branches, Resources/prop contracts, active-role authorization presentation, and accessibility queries; prohibit shallow implementation-detail tests and snapshot-only coverage claims.
- [x] Approve ADR-0001: the Domain/Application/Infrastructure/HTTP namespace boundaries, shared application/domain layer, `/api/v1`, shared Eloquent Resource serialization boundary, and three exact legacy dispositions.
- [x] Complete the current-source architecture rule catalog covering the Section 4 dependency rules, naming/inheritance, strict types, prohibited debug calls, and existing Domain/Application/HTTP/Resource/Integration boundaries. **Implemented and locally verified 2026-09-10:** D-76 requires strict typing across all six approved PHP source roots; `tests/Architecture/StrictTypesTest.php` covers named symbols and standalone files, with 23 passing tests and 18 planted missing/disabled/hidden-file cases. The existing catalog enforces dependency direction, configuration access, Domain purity, application contracts, Resource isolation, and container-bound adapters. ADR-0001 records the non-empty target assertions, rule implementation, controlled fail/pass proof and non-author review required in the first PR introducing each protected ledger, settlement, underwriting-publication, seal or immutable-evidence module. Future modules are not claimed as implemented; this change still requires candidate-SHA hosted checks and Erastus's review before promotion. See the Phase 0G evidence record.
- [x] Add the Pest 5 first-party `pestphp/pest-plugin-phpstan`, register its extension in `phpstan.neon`, include `tests/` in analysis, retain Larastan for Laravel awareness, and make zero-error Pest-aware analysis part of `composer ci:check:static`.
- [x] Correct the TIA topology: configure local TIA with a coverage driver and optional fetched shared baseline; keep `--tia --fresh` only in the dedicated baseline workflow; remove `--tia` and TIA result-cache restoration from the authoritative pull-request/promotion CI test job so every clean checkout executes every test.
- [x] Centralize non-drifting Composer/npm commands for the local impacted-test loops, full PHP and D-66/D-67 web coverage gates, architecture suite, Pest-aware static analysis, frontend type/lint/build gates, changed-branch calculation, and future native hooks; make `composer ci:check` invoke the authoritative applicable gates rather than leaving thresholds only inside workflow YAML.
- [x] Approve PHP 8.5 as the sole supported runtime — minimum, canonical, coverage and deployment — exercise it in CI, and use PostgreSQL rather than SQLite as the authoritative concurrency/locking environment. **Amended 2026-09-07:** the PHP 8.4 minimum-compatibility lane is removed; 8.4 is unsupported rather than untested.
- [x] Approve and pin Node 24.15.0 for local development, both normal CI jobs, production asset builds, and the TIA baseline workflow; retain npm 10.9.8 as the deterministic package-manager contract.
- [x] Prove the approved runtime contract through the hosted PHP 8.5 gate, pin PHP 8.5 in deployment evidence, and add PostgreSQL-backed locking/concurrency tests. **Done 2026-09-07:** the deployment script resolves 8.5 explicitly and aborts otherwise, and the PostgreSQL lane includes real locking/concurrency tests. **Update 2026-09-23:** Aminu selected PostgreSQL for the application and all database tests; both PHPUnit configurations now include the Concurrency suite.
- [ ] Operate the approved two-developer promotion control at each `feat/* -> dev -> uat -> main` hop: attach complete clean-checkout evidence for the exact candidate SHA, record the non-author developer's approval of that SHA, rerun the applicable hosted gates on the resulting target-branch SHA, and make deployment workflows—including manual dispatch—refuse missing, failed, stale, or mismatched evidence. Paid private-branch protection is intentionally not required and must not be claimed as enabled.
- [x] Prove the quality controls with reversible negative checks: an uncovered PHP line fails; an uncovered client line/statement/function fails globally and per file; an entirely unimported in-scope file fails; missing/drifting source or risk manifest entries fail; an uncovered critical branch fails; non-critical branch results below either 95% global or 90% per file fail using exact counts; one uncovered newly changed branch fails; a risk-manifest downgrade of governed behavior fails; stale/unmappable base-head or mismatched tested-SHA evidence fails; a forbidden dependency fails the architecture suite; and an invalid Pest construct fails PHPStan. Remove every temporary violation after evidence is captured. **Done 2026-09-09:** the client half is 20 policy tests in `tests/web/quality/client-coverage-policy.test.ts`; the PHP half is `scripts/quality/verify-negative-controls.sh`, which plants each violation, requires its gate to have been green beforehand, and fails if a planted file survives. The hosted run records 6 caught, 0 not caught, 0 skipped.
- [x] Capture the baseline schema, routes, authentication, existing Pulse boundary, CI gates, deployment environments, and current migration/data state. **Done 2026-09-09:** `php artisan inventory:baseline` generates `docs/phase-0/baseline-inventory.md` from the code itself, and `--check` runs in CI so the document fails the build rather than rotting. It captures structure only — row counts and which migrations have run are per-environment facts that would differ between machines, so they are excluded deliberately and the drift check stays meaningful. Deployment secrets are recorded by **name** only.
- [x] Approve one of the two supplied Rozine lockups, the star mark, the three role lockups, exact source colors, font/outline ownership, role-accessible names, and whether reconstruction is authorized. **Done 2026-09-07:** primary is the detached leading star, secondary is the star on the `i`; colours follow the logo package; role wordmarks pair with Rozine by default with the accessible name always paired; the wordmark is reconstructed in Inter, which is the authorised reconstruction and settles font ownership under the SIL Open Font License.
- [x] Replace WhatsApp filenames with a semantic asset manifest without deleting source evidence; record the six current JPEGs as references and retire the legacy 60-image inventory. **Done 2026-09-07:** `docs/phase-0/brand-asset-manifest.json` maps every one of the 31 non-semantic Figma exports to the artwork it depicts and its colourway, so a brand decision can name an artwork rather than a filename. Source files and their hashes are untouched.
- [x] Receive and hash Robert's 31 SVG sources and validate their XML syntax.
- [ ] Complete semantic mapping, duplicate/canonical selection, font/outline provenance, usage-rights validation, authoritative-color approval, and Robert's Brand sign-off before any release asset is derived. **Mostly done 2026-09-07:** semantic mapping is machine-readable in `docs/phase-0/brand-asset-manifest.json` (31 files resolve to 8 artworks, so there is no duplicate problem); canonical selection, authoritative colours and Brand sign-off are recorded; font provenance is settled by reconstructing the wordmark in Inter. **Remaining:** the Inter re-export of the 19 wordmark-bearing files has not been produced, and ownership of the vector artwork itself is still unrecorded — the answer given described marketing imagery rather than the mark.
- [x] Receive and hash Robert's 31 matching PNG derivatives/references; validate RGBA/alpha plus one-to-one basename and dimension parity with the SVG package. PNG files do not replace the approved vector master.
- [ ] Define canonical monochrome, dark, favicon/PWA, Apple-touch, and responsive-header outputs from those masters; treat the rounded-square JPEG only as a visual reference. **Partly done 2026-09-07:** favicon, PWA and Apple-touch outputs are derived from the star and shipping, with tests asserting the maskable safe circle and the absence of alpha where a platform composites. They were unblocked because the star carries no font licence. Monochrome, dark and responsive-header outputs wait on the Inter wordmark.
- [ ] Define environment isolation, feature flags, seeded-demo boundaries, and reset controls so demo/UAT facts cannot look live or touch real records. **Definition and local groundwork recorded 2026-09-10; reconciled 2026-09-14:** the [environment isolation policy](phase-0/environment-isolation-policy.md) preserves environment/data rules, feature-flag requirements and owned infrastructure gaps. Phase 0J in [kickoff evidence](phase-0/kickoff-evidence.md) records implemented separate demo/UAT database identities and resource namespaces, opt-in demo seed/reset flags, production/UAT command prohibitions, non-live outbound restrictions and target-specific deployment checks with negative tests. The merged runtime retains these stricter controls, including denying UAT resets/seeding despite the earlier policy's permissive staging row. The reported PHP 8.4/required 8.5 mismatch and shared deploy user, SSH secrets, mail key and host remain dated upstream observations requiring operator verification/remediation, not newly verified live facts. **Local safeguards added 2026-09-14:** Phase 0M records server-owned non-live notices, owned/expiring default-off switches, and a transactional synthetic Pulse fixture-set reset with provenance and unaffected-record tests. The root PRODUCT.md prerequisite is supplied for this scoped UI work. This item remains open pending host/DB-role/network proof and non-author/exact-SHA acceptance; Phase 3 financial-demo journeys and provider adapters remain separate. No hosted isolation, general flag library or complete demo is claimed.
- [ ] Start KYC/KYB, registry, ICPAR, parsing, bank, MoMo, storage, maps, legal, and CMA tracks with named owners. **Preparation and owner routing recorded 2026-09-10; not yet started:** the [provider dependency register](phase-0/provider-dependency-register.md) covers 13 prepared tracks, all IR-1–IR-8, proposed leads/review dates, required inputs, fake cases and external gates. The [provider and regulatory tracks](phase-0/provider-regulatory-tracks.md) supplies ten required plus four adjacent owner/decision/first-action/certification entries, including CMA admission and independent penetration testing. Their groupings and lead assignments are not identical; retain both and reconcile them with the named owners before kickoff, without treating a proposed lead or recorded assignment as owner acknowledgement. Machine-readable records remain `PREPARED_NOT_STARTED`; no first contact, provider selection, contract, regulator engagement, sandbox or certification is claimed. This item closes only with recorded actual owner kickoff/contact evidence for every required track.

**Phase 0B/C evidence (2026-08-24):** the approved Pest-aware PHPStan, Vitest/React/V8, exact source/risk manifests, D-67 validator, immutable-SHA evidence, generated-source drift check, canonical PHP/web commands, and dependency remediation are implemented. The PHP gate passes 107 tests/417 assertions at 100% first-party line coverage with zero PHPStan errors. The complete client suite passes 226 tests and covers all 101 critical authored files at exact 100%: 1,154/1,154 statements, 727/727 branches, 487/487 functions, and 1,134/1,134 lines. Vitest measures authored source without React Compiler-generated scaffolding; the production Vite build retains the compiler. Composer and npm audits report zero advisories after upgrading `league/commonmark` 2.8.3 → 2.10.0 and remediating the approved npm findings.

**Phase 0 governance and Engineering update (2026-08-29):** Aminu, Erastus, Robert, and Kimani are the confirmed internal approval pool. Robert owns Product/Design/Brand, Business, and internal Legal; Aminu and Erastus jointly own Engineering/Security; Kimani owns Audit Operations/Compliance and Finance/Risk. Each applicable record must still capture every required named-owner signature. Independent-test approval may come from any pool member who was not the sole author or from an eligible external delegate. D-72 accepts ADR-0001 as written, and D-73 fixed PHP 8.4 minimum compatibility and PHP 8.5 canonical deployment/coverage, and was amended on 2026-09-07 to make 8.5 the sole supported runtime with PostgreSQL-authoritative concurrency evidence; their remaining implementation and exact-SHA evidence gates stay open. The two-developer team will not make a paid GitHub plan or private-branch-protection feature a Phase 0 dependency; D-68 preserves checked pull requests, immutable exact-SHA hosted evidence, non-author review, and fail-closed deployment admission without claiming that GitHub technically blocks direct pushes. The user confirmed Robert supplied both the 31-file SVG package and its 31 matching PNG package. SVG XML and all source hashes are verified; every PNG is valid RGBA with alpha and matches its SVG basename and dimensions one-to-one. PNG receipt is resolved. Semantic/canonical selection, exact colors, vector/font/outline provenance, rights, required variants/surface rules, accessibility, and Robert's explicit Brand approval remain pending, so D-51, D-52, D-56, D-57, and D-63 remain open. External legal/regulatory/CMA, ICPAR/Audit Partner, provider-certification, penetration-test, and independent-assurance authority remains separate.

**Pulse boundary remediation evidence (2026-08-29, uncommitted working tree):** authoritative browser-side Pulse scoring, sizing, eligibility-like, yield, and projected-return calculations have been removed. Inertia React requests non-persisting previews through generated Wayfinder routes and renders shared Resource facts; registration actions independently recompute and persist server-authoritative results. Laravel now separates a pure `App\Domain\Pulse` simulation, `App\Application\Pulse` actions and repository port, an `App\Infrastructure\Pulse` Eloquent adapter, thin HTTP requests/controller, and calculation-free Resources. Fresh non-TIA PHP verification passes 132 tests/567 assertions at 100.0% first-party line coverage with zero PHPStan errors; the complete web suite passes 220 tests at 100% statements (1,154/1,154), branches (716/716), functions (481/481), and lines (1,135/1,135), and lint, formatting, TypeScript, and production build checks pass. This closes the local `PulseController` exception only. It is not clean exact-SHA/hosted evidence, it does not activate the rejected prototype formula as production underwriting, and its legacy `count()+1` public-waitlist numbering remains excluded from concurrency claims until PostgreSQL-backed allocation and constraints replace it.

### Deliverables

- Signed MVP scope/authority decision and C-01–C-34 disposition register.
- Per-ID MVP/BRS/phase/evidence crosswalk and deferred-scope register.
- Green reproducible-baseline evidence pack and implementation-state inventory.
- Versioned cross-platform quality contract: PHP and web/native coverage scopes, generated/non-executable manifest, critical/non-critical risk manifest, D-67 metric/branch thresholds, changed-branch checker, architecture rule catalog, Pest-aware PHPStan configuration, Vitest/React test foundation, local impacted-test instructions, dedicated TIA baseline workflow, and full-suite CI evidence.
- Auditor PWA assurance report and, if required, scoped native-exception decision.
- Approved architecture decision records, error/state vocabulary, and release definitions.
- New-logo approval record, governed 31-SVG/31-PNG source manifest and archive record, rights/licensing record, approved canonical vector/transparent and outlined-wordmark masters, derived-asset brief, and legacy retirement record.
- Provider/regulatory dependency plan with owners, due dates, fakes, and certification gates.

### Acceptance Criteria

- [ ] No unresolved source conflict can silently alter money, underwriting, evidence, marketplace, access-control, or regulatory behavior.
- [ ] Every red decision needed by the Phase 1 chain has a signed executable disposition; a named owner or future due date alone is not sufficient to encode provisional money, evidence, authorization, or underwriting behavior.
- [x] Every MVP screen, state, and acceptance criterion has a stable ID and an implementation/test/evidence owner. **Mapped 2026-09-10:** [MVP crosswalk](phase-0/mvp-crosswalk.md); source states and generic applicability inherit explicit slice/evidence ownership. This closes identification and ownership, not implementation or witnessed acceptance.
- [x] `MVP ALPHA`, `MVP RELEASE CANDIDATE`, and `LIVE MVP ACCEPTED` have distinct, approved gates. Recorded 2026-09-09 in `docs/phase-0/milestone-definitions.md`, each gate-based rather than date-based and each requiring archived exact-SHA evidence rather than a replayed or partial run.
- [x] The MVP crosswalk contains an unbroken, owned path from an eligible settled Holding through ask, reservation, fill/cancel/expiry, exact fee disclosure, cash/Holding settlement, reconciliation, and Admin halt; no Rozine-principal route exists. **Mapped 2026-09-10:** crosswalk checkpoints SEC-01–SEC-09 allocate A/E/K/Robert and automated/witnessed evidence across Phases 1–4. D-26/D-27 remain open; this is an owned delivery path, not an approved executable lifecycle or a working market.
- [x] The web/PWA Auditor route is either proven capable in principle or replaced by a documented, estimated thin-native exception. Met by the Option B route: the web route is disproven on the platform and replaced by the documented exception in `docs/phase-0/d-04-auditor-capture-decision.md`. **Estimate prepared 2026-09-10:** [incremental capture plan](phase-0/auditor-capture-delivery-plan.md) records 15–25 developer-days and a proposed 2–3-focused-week schedule adjustment in Section 9.1. A/E acceptance, native feasibility and physical-device/dwell evidence remain open; the estimate is not a delivery commitment or working companion.
- [x] Application boot, route discovery, focused tests, static/type checks, and production asset build are green from the approved clean baseline. Hosted CI installs from the lock files, migrates, builds production assets and runs the authoritative gates on the exact commit; `generated:check` regenerates the typed client from the live route list, so route discovery is proven rather than assumed.
- [x] The full non-TIA clean-checkout Pest suite reports `100.0%` line coverage for all in-scope `app/` code; there is no unexplained coverage exclusion, ignored error, risky test, warning, or deprecation. Verified 2026-09-09: 177 tests, 725 assertions, `Total: 100.0 %`, with no deprecation, risky or incomplete marker in the run.
- [x] The complete clean-checkout Vitest suite reports 100.0% lines/statements/functions globally and per file for all retained human-authored TypeScript/React code; 100.0% critical branches globally and per file; at least 95.0% non-critical branches globally and 90.0% per file; and 100.0% newly changed branches. Every negative control fails as expected, and no behavioral file is hidden or misclassified by either manifest.
- [ ] Architecture tests fail on a controlled forbidden-dependency example and pass after its removal; the rule catalog covers shared Resources, thin transports, application actions, Domain isolation, provider ports/adapters, and protected financial/evidence seams. **First half met 2026-09-06** and automated rather than performed once: three planted violations are caught on every hosted run and the tree is checked clean afterwards. The catalog covers Resources, transports, application actions, Domain isolation and provider ports. **Protected financial and evidence seams are not covered because those namespaces do not exist yet**, so this criterion cannot close before the module that introduces them.
- [x] PHPStan/Larastan analyses `app/`, configured first-party paths, and `tests/` with the Pest extension and zero errors; TIA works locally from a fresh or downloaded baseline, while the normal CI job demonstrably runs the complete suite without TIA. Verified 2026-09-09: zero errors across `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` and `tests/`; `composer test:php:tia` builds a fresh graph and passes all 177 tests; the hosted job runs `--no-tia`.
- [ ] Canonical Composer/npm and CI commands agree; PHP coverage and complete web metric/risk-tier/changed-branch reports, manifest/report-set equality, frontend type/lint/build checks, the supported/deployed PHP matrix, PostgreSQL financial-concurrency lane, documented latest-SHA second-developer review, and fail-closed exact-SHA deployment admission are green from a clean install.
- [ ] All 31 current SVG sources and 31 matching PNG references are accounted for; one primary lockup/mark direction and the role naming contract are approved; rights-cleared vector/transparent masters exist; no unapproved source is treated as a production master. **Three of four met 2026-09-07:** all 62 files are accounted for and mapped to 8 artworks, the primary lockup and role naming contract are approved, and no unapproved source is in production — the shipping icons derive from the star, which carries no font licence. **Rights-cleared masters do not yet exist:** the wordmark must be reconstructed in Inter before any wordmark-bearing master is cut.
- [ ] Native mobile, Pulse, Plus execution, and every other deferred item have an explicit destination phase and cannot leak into the MVP critical path without change control.

### Verification and exit gate

Archive the source/risk manifests, rendered-PDF review, conflict register, PWA spike evidence, brand approval, dependency outputs, application boot/routes, full non-TIA PHP coverage, complete Vitest D-66/D-67 metric and changed-branch reports, generated/non-executable manifest, coverage/risk-classification negative controls, architecture/PHPStan negative-control evidence, local impacted-test evidence, dedicated TIA baseline evidence, and production build. Phase 1 starts only when the clean-checkout baseline is green, every Phase 1 behavioral decision is signed, and later-wave blockers have named owners and enforceable gates.

---

## Phase 1 — MVP foundation and one complete marketplace chain

**Status:** `IN PROGRESS` — identity foundation started 23 September 2026

**Current kickoff, 2026-09-23:** Aminu's subsequent request to start Phase 1 authorizes implementation against [engineering-2026-09-23.4](phase-0/engineering-contract-draft-2026-09-20.md) section 11. PR #95 is merged to `dev` at `b2bc524e6bcdcdcc37426978ddaf09964562c51a`; its PHP, web, PostgreSQL and negative-control checks passed. Erastus's current specification review remains waived, not performed; the original contract-freeze scheduling below does not reopen that review. Earlier defaults-only snapshots retain their historical authorization flags. Issue #90 and remaining operational/external evidence stay open as separate gates, without blocking this isolated foundation slice or being reported as complete. Code review, integration and promotion gates still apply.

**Publication:** the reviewed identity foundation, PostgreSQL setup and two P2 fixes are committed and pushed as [`d7dc506a0e9e0e32407d5fbdcfe4e4a5ea01619c`](https://github.com/rozine-rw/rozine/commit/d7dc506a0e9e0e32407d5fbdcfe4e4a5ea01619c) on `feat/phase-1-identity-foundation`, based on the verified `dev` revision above. The controlled-membership and active-role slice is committed and pushed on the same branch as [`144066b`](https://github.com/rozine-rw/rozine/commit/144066b8b5ca4fb6ec9d4888451cae8431ee7034), after independent working-tree review found no actionable issues and Aminu authorized publication.

**Current identity slice:** Fortify still creates an unverified person Party and account atomically with no memberships. A dedicated verified staff account with confirmed MFA can record reviewed identity evidence, link logins through one canonical provider reference, and provision/change memberships with reasons, evidence references, revisions and replay protection. Existing identity or membership histories require reconciliation instead of being silently merged. Investor/Business coexistence and Auditor exclusivity are serialized on the Party. Active-role selection persists per account and authorization rechecks current verification, membership/revision, role, record ownership and Auditor MFA while holding locks through the protected operation. `dashboard` and `/api/v1/identity` expose the shared `identity-v2` Resource; web/API mutation contracts and [TypeScript types](../resources/js/types/identity.ts) are available for the launcher. The only permitted actions exposed here select or view identity role context. External KYC/KYB verification, entity mandates, transactional permissions, consent/recovery, full staff permissions and Auditor biometric binding remain separate work. The launcher, authorized role homes, separate staff entry and validated return positions are integrated in the checkpoint 1 candidate described below. No whole checkpoint, Phase 0 exit, Alpha acceptance or deployment is claimed.

**Foundation verification, 2026-09-23 (historical):** 818 full non-TIA PostgreSQL PHP tests / 4,161 assertions pass with 100.0% application line coverage. This includes the reviewed foundation, controlled provisioning, identity resolution, role authorization, immutable audit records, stale/replayed requests and four PostgreSQL concurrency cases. The additive access migration is applied to the local PostgreSQL application database; test/demo databases remain separate. Pint, PHPStan, TypeScript, production build and local source/risk-manifest validation pass. All 329 web tests pass with 100% collected lines, statements, functions and branches; `vp check` retains the existing 32 warnings and two existing canvas-environment diagnostics remain in web tests. The source manifest includes 88 generated paths, seven declaration-only paths and 114 authored executable paths. Independent working-tree review is recorded below; exact-commit client provenance/changed-branch admission, hosted checks, checkpoint review and acceptance remain outstanding.

**Checkpoint 1 implementation, 2026-09-24:** PR #97 is merged to `dev` at `2774c03` under Aminu's explicit review waiver for that PR. The follow-on candidate in [PR #98](https://github.com/rozine-rw/rozine/pull/98), on `codex/phase-1-checkpoint-one`, combines the role-home/staff/bookmark adapters in `9484b2b` with Erastus's 1B snapshot `67c4528`. The live launcher consumes `identity-v2`, selects roles with revision/UUID commands, restores only authorized named destinations and refreshes access on focus/reconnect. All four rich role surfaces remain local/testing synthetic previews until their server workflows exist. Local verification at `04e191c568bc8e9634d3aed0eb64fb5f858803c7` passes 1,023 non-TIA PostgreSQL tests / 5,412 assertions at 100% application line coverage, 727 web tests at 100% lines/statements/functions/branches over 304 authored executable files with exact-SHA D-67 admission, PHPStan, TypeScript, production build, inventory and i18n checks. `vp check` reports zero errors and 41 warnings across the combined source. The [isolated browser journey](../tests/Browser/CheckpointOneBrowserTest.php) passes real login/CSRF, role switching, bookmark restoration/cold reload, cross-tab denial, logout, cross-login isolation, Auditor MFA gating and staff MFA entry; the desktop and 390×844 screenshots were inspected. Exact-commit hosted gates and non-author checkpoint acceptance remain separate from this local evidence.

**Estimated two-developer agent-native active engineering:** `2.5–3 focused weeks` · **Confidence:** Low

**Scheduling note:** Place this work in Weeks 1–3. Days 1–2 are a joint contract freeze; after that, Aminu's server/API/domain lane and Erastus's Inertia React UI/UX lane run in parallel and meet at five mandatory integration checkpoints. Agents work only on bounded, non-overlapping action, Resource, UI, fixture, and adversarial-test slices. No real money or participant data is enabled.

### Goal

Reach `MVP ALPHA`: one Business applies, one eligible Auditor verifies, the core computes the governed result, one Investor funds, Admin releases the proceeds, and one repayment posts—all through the shared Laravel core and role-aware responsive web surfaces.

### Dependencies and release surface

- Depends on Phase 0.
- Implements the happy path across the launcher, Business, Auditor, Investor, and Admin applications.
- Uses deterministic provider fakes and isolated acceptance fixtures; it is not a shippable or regulated pilot.

### Parallel ownership and contract-first workflow

| Workstream | Owner | Timing | Responsibility |
|---|---|---|---|
| Phase 1.0 — Contract freeze | Aminu and Erastus | Days 1–2 | Aminu publishes and Erastus validates action inputs/results, transaction boundaries, named web and `/api/v1` routes, Form Request shapes, shared Resource schemas, TypeScript prop types, exact-money serialization, stable state/error/message codes, deterministic fixtures, authorization/active-role rules, and event/reconnect contracts. |
| Phase 1A — Server, API, and authoritative logic | Aminu | Weeks 1–3, parallel | Own Domain/Application/Infrastructure code, models, migrations, factories, policies, provider ports/adapters, Form Requests, Inertia and API controllers, shared Resources, `/api/v1` Phase 1 endpoints, financial/security rules, observability, and PHP evidence. |
| Phase 1B — Inertia React UI/UX | Erastus | Weeks 1–3, parallel | Own the launcher and four role surfaces, forms and interactions, typed Resource consumption, Wayfinder calls, responsive/PWA presentation, accessibility, localization, client state/reconnect behavior, and TypeScript/React/browser evidence. |
| Phase 1C — Integration and Alpha acceptance | Aminu and Erastus | Daily and at exit | Integrate at the five named checkpoints, approve shared-contract changes together, cross-review the other lane's latest SHA, run full exact-SHA gates, and witness the complete Alpha chain. |

The split assigns implementation ownership without splitting the system. Inertia pages consume the same Resources through Laravel web controllers and do not call the public API over HTTP. React may present previews returned by the server but may not calculate authoritative money, underwriting, eligibility, permissions, fees, ownership, or workflow transitions. Native authentication, device registration, and app-store clients remain Phase 5 work even though Phase 1 establishes `/api/v1` parity for the implemented actions.

### Checklist

#### Phase 1.0 — Joint contract freeze

- [ ] Freeze action inputs/results and transaction boundaries; web and `/api/v1` route names; Form Request shapes; shared Resource schemas; exact-RWF serialization; stable state/error/message codes; authorization/active-role rules; TypeScript prop contracts; deterministic provider and journey fixtures; outbox events; and refresh/reconnect behavior.
- [ ] Freeze the vertical integration order and one accountable editor for every shared migration, state machine, action, policy, Resource, route contract, and generated type; contract changes require both developers' approval before either lane proceeds.
- [x] Prove one exemplar action through an Inertia controller and `/api/v1` controller returns the same Resource facts, stable codes, authorization outcome, and fixture identity without the web application calling its API over HTTP. **Local identity exemplar, 2026-09-23:** both controllers invoke [`GetIdentityContext`](../app/Application/Identity/GetIdentityContext.php) and serialize [`IdentityContextResource`](../app/Http/Resources/IdentityContextResource.php); [feature tests](../tests/Feature/IdentityFoundationTest.php) prove exact response/Party identity parity, matching unverified outcomes and authentication denial. This read-only exemplar does not close the remaining contract freeze or checkpoint 1 acceptance.

#### Phase 1A — Aminu: server, API, and authoritative logic

**Progress tracking, 2026-09-23:** checked implementation subitems have the linked code and local test evidence. Broad rows remain open until their full scope is complete; hosted checks, code review, integration and promotion retain their separate gates.

- [ ] Establish shared application actions, record-level policies, state machines, exact-RWF value objects, outbox/events, machine-readable errors, Eloquent Resource contracts, and the matching Inertia plus `/api/v1` transport adapters. **IN PROGRESS — identity foundation.**
  - [x] Establish the shared registration/read actions, identity persistence port and adapter, stable identity codes, explicit Resource fields, and Inertia/API adapters. Evidence: [identity boundary ADR](phase-0/adr-0001-modular-monolith-boundaries.md#phase-1-identity-entry-points--2026-09-23) and [transport/Resource tests](../tests/Feature/IdentityFoundationTest.php).
  - [ ] Complete record-level authorization, governed state transitions, exact-money types and outbox/events as their Phase 1 slices are implemented.
- [ ] Implement the Fortify identity/Party model, role membership, active-role authorization, MFA/consent rules, and server contracts for the launcher; reassess the two approved Fortify exceptions without duplicating them for mobile. **IN PROGRESS — controlled identity access implemented; remaining checkpoint 1 rules open.**
  - [x] Create an unverified person Party and account atomically through Fortify, grant no membership on registration, and reject supplied verification/role authority. Evidence: [persistence adapter](../app/Infrastructure/Identity/EloquentIdentityRepository.php) and [registration, rollback and mass-assignment tests](../tests/Feature/IdentityFoundationTest.php).
  - [x] Send the registration verification email, enforce the verified-email dashboard gate and accept the generated verification link while keeping Party verification separate. Evidence: [`MustVerifyEmail` on User](../app/Models/User.php) and the [registration-to-verification regression](../tests/Feature/Auth/RegistrationTest.php).
  - [x] Add Party/membership persistence and evaluate current memberships: allow Investor/Business coexistence, deny Auditor conflicts across logins linked to the same Party, and expose only verified, active role availability. No transaction permission is granted. Evidence: [role rules](../app/Domain/Identity/RoleAccess.php), [unit cases](../tests/Unit/IdentityRoleAccessTest.php) and [identity feature cases](../tests/Feature/IdentityFoundationTest.php).
  - [x] Reassess both existing Fortify adapters for this identity slice and record the shared registration boundary without creating a separate API authentication implementation. Evidence: [Fortify reassessment](phase-0/adr-0001-modular-monolith-boundaries.md#phase-1-identity-entry-points--2026-09-23).
  - [x] Implement controlled identity resolution across separately registered Parties and membership provisioning/lifecycle actions. Evidence: [shared management actions and persistence boundary](phase-0/adr-0001-modular-monolith-boundaries.md#phase-1-identity-entry-points--2026-09-23), [operator/identity/membership regressions](../tests/Feature/IdentityMembershipManagementTest.php) and [PostgreSQL races](../tests/Concurrency/IdentityAccessConcurrencyTest.php). Resolution records an authorized operator's reviewed provider reference; automated external KYC/KYB and merging established histories are outside this action.
  - [x] Implement persistent active-role selection and server-side authorization for role-scoped actions. Evidence: [selection action](../app/Application/Identity/SelectActiveRole.php), [authorization entry point](../app/Application/Identity/AuthorizeActiveRole.php), [role and ownership denials](../tests/Feature/ActiveRoleAuthorizationTest.php) and [revocation/selection concurrency tests](../tests/Concurrency/IdentityAccessConcurrencyTest.php). Reinstated or revised memberships require a fresh selection; future business commands must call this authorization boundary with their record Party and expected context revision.
  - [x] Publish authorized role-home adapters and the separate staff-entry contract. The four reserved homes are registered and enforce current selected-role or explicit staff authority. `staff-access-v1` grants only `admin.open`, requires verified dedicated staff identity and effective MFA, and remains independent from the identity operator. Evidence: [entry/navigation ADR](phase-0/adr-0001-modular-monolith-boundaries.md#checkpoint-1-entry-and-navigation-adapters--2026-09-24), [role-home tests](../tests/Feature/RoleHomeAndBookmarkTest.php) and [staff tests](../tests/Feature/StaffAccessTest.php). The full staff operational matrix remains open.
  - [x] Freeze and implement per-account/per-role return-position contracts with Phase 1B. [Erastus confirmed](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5802126452) named routes and validated parameters/query only, with local scroll state. `role-bookmark-v1` persists the implemented role-home sections, reauthorizes restoration, invalidates positions after membership changes, and falls back from obsolete destinations. Evidence: [bookmark tests](../tests/Feature/RoleHomeAndBookmarkTest.php) and [PostgreSQL concurrency tests](../tests/Concurrency/IdentityAccessConcurrencyTest.php). Future detail routes require their own allowlist/record policies; integrated client and browser acceptance remain below.
  - [ ] Implement the remaining governed MFA, consent, recovery, entity-mandate and privileged-staff authority rules and complete the server launcher contract. Confirmed MFA now gates identity operators and Auditor role selection; this does not complete biometric binding, general staff authorization or the remaining onboarding rules.
- [ ] Implement minimal KYC/KYB, company lookup without prohibited tax identifiers, D-64-approved director/owner/signatory and corporate-authority evidence, and registered bank/MoMo provider contracts/fakes.
- [ ] Implement immutable statement upload, parsing/normalization/correction lineage, policy-versioned underwriting, capacity, DSCR, rating, pricing, schedule, and actionable refusal using Appendix A fixtures.
- [ ] Implement the Business application/listing and Auditor accreditation, assignment, conflict, procedure, evidence, reconciliation, filing, co-signature, and seal actions; enforce that no Auditor or staff input can set a rating, capacity, yield, or credit verdict.
- [ ] Implement balanced double-entry accounts, segregated client/company/control balances, immutable journals, compensating reversals, ledger-derived wallet balances, actor-attributed events, idempotent deposit/reservation/primary settlement, Holding creation, disbursement, one repayment, Investor payout, reconciliation identifiers, and receipts using provider fakes.
- [ ] Define and contract-test the secondary-ready Holding lot, eligibility snapshot, Order, cash/unit reservation, chosen ask, approved 0.35% buyer and 0.35% seller fee posting, halt, cancellation/expiry, record date, and exactly-once settlement interfaces; executable trading remains feature-flagged until Phase 3 acceptance.
- [ ] Implement the Investor and Admin server contracts, authorization, queues, maker-checker boundary, ledger drill-down, reason-required controls, outbox delivery, logs, metrics, and alerts needed by Alpha.
- [ ] Add PHP unit, feature, Resource/API parity, property, denial, idempotency, PostgreSQL concurrency, and architecture tests with each slice; maintain 100% PHP line coverage and zero-error Pest-aware PHPStan/Larastan. **IN PROGRESS — local identity evidence.**
  - [x] Cover identity registration/rollback, membership rules, transport parity, caller isolation, verification and membership denials, database uniqueness and Resource field allowlisting. Evidence: [identity feature tests](../tests/Feature/IdentityFoundationTest.php) and [role-rule unit tests](../tests/Unit/IdentityRoleAccessTest.php).
  - [x] Enforce the identity persistence boundary with [architecture tests](../tests/Architecture/ArchitectureTest.php) and the [`identity-boundary` negative control](../scripts/quality/verify-negative-controls.sh); verify the planted violation fails and the clean suite passes after removal.
  - [x] Run the full local non-TIA PostgreSQL PHP coverage suite and PHPStan/Larastan for the identity foundation, database switch, review fixes and controlled-access slice: 818 tests, 4,161 assertions, 100.0% application line coverage and zero static-analysis errors, as recorded in Local verification above.
  - [x] Use PostgreSQL for application and database tests with isolated test/demo databases and restricted owners. Evidence: [database isolation and rollback tests](../tests/Feature/PostgreSqlConfigurationTest.php), [demo isolation tests](../tests/Feature/DemoSafeguardsTest.php) and the local verification record above.
  - [x] Regenerate the [baseline inventory](phase-0/baseline-inventory.md) for the controlled-access tables, routes and module boundaries. The required `inventory:baseline --check` passes, and all six [inventory regression tests](../tests/Feature/BaselineInventoryTest.php) / 32 assertions pass with TIA disabled.
  - [x] Resolve both P2 findings from [Review uncommitted changes](thread://01a0cdfa-70ef-7490-9648-6cf0261deda9?hostId=local): remove the Party index before its column during rollback, and enable the registration email-verification flow. Follow-up verification on 2026-09-23 passes 62 focused tests / 264 assertions on PostgreSQL, including registration, email verification, identity rules, transport parity, database isolation, rollback/reapplication and architecture. This records the reviewed working-tree fixes; exact-commit checkpoint review remains below.
  - [x] Prove one canonical person under concurrent resolution, one winner for conflicting role grants and stale role switches, and revocation serialization through a protected operation. Evidence: [four PostgreSQL concurrency cases](../tests/Concurrency/IdentityAccessConcurrencyTest.php). [Management tests](../tests/Feature/IdentityMembershipManagementTest.php) also prove audit write rollback, immutable stored history and fresh operator checks before replay.
  - [x] Complete independent working-tree review of controlled membership provisioning and active-role authorization. [Review uncommitted changes](thread://01a0cf48-f35f-7342-9c20-47f61beed51f?hostId=local) found no actionable issues on 2026-09-23 and verified 121 focused PHP tests with TIA disabled, 329 web tests, TypeScript checks and generated-route comparison. `vp check` reported zero errors and the existing 32 warnings. Aminu accepted the review and authorized commit/push; exact-commit checkpoint gates remain separate.
  - [ ] Add the remaining slices' property, financial idempotency/replay and PostgreSQL concurrency evidence; complete exact-commit hosted gates and non-author review before checkpoint acceptance.

#### Phase 1B — Erastus: Inertia React UI/UX and client behavior

**Status, 2026-09-24:** `IN PROGRESS`; Erastus's [issue #96](https://github.com/rozine-rw/rozine/issues/96) branch is integrated through [`67c4528`](https://github.com/rozine-rw/rozine/commit/67c45282a41a6353857e6c3ab0bbbb99a02f4113) in the checkpoint 1 candidate. Its launcher and Business, Auditor, Investor and Admin preview surfaces are retained. The launcher/types/identity fixtures now use `identity-v2`, live server selection and bookmark routes, separate `staff-access-v1`, and denied/stale/MFA/retry states. Preview links are explicitly synthetic and are supplied only by local/testing fixtures. This closes the implemented launcher slice below; the remaining workflow pages still need their governed backend contracts and acceptance.

- [x] Implement the typed launcher, authorized application list, persistent active-role context, role switching, and return-position behavior against the frozen identity/Resource fixtures. Evidence: [launcher tests](../tests/web/suite/launcher.test.tsx), [entry-page tests](../tests/web/auth-settings/identity-entry.test.tsx), [focus/reconnect tests](../tests/web/auth-settings/access-refresh.test.tsx) and the [isolated browser journey](../tests/Browser/CheckpointOneBrowserTest.php). All role commands use the server contract; bookmark scope is the implemented role-home sections.
- [ ] Apply the approved star mark and role lockups through typed, accessible `LogoMark`/`LogoLockup` components; prevent stretching, duplicate accessible names, and role-color-only identification.
- [ ] Externalize user-visible strings from the first slice; establish Kinyarwanda, English, and French catalogs, stable message-code mapping, missing-key/hard-coded-string lint, and pseudo-localization even though the launch-language subset remains D-07.
- [ ] Implement the Business registration, resumable application, exact server-returned pre-acceptance economics, audit timeline, digital acceptance, listing, and raise-progress screens.
- [ ] Implement Auditor accreditation/status, eligible-job, conflict, procedure, evidence-reconciliation, online-filing, co-signature, and seal screens without client-authoritative financial or credit logic.
- [ ] Implement Investor verification, wallet readiness, deal list/detail, audit evidence, unavoidable cost/risk confirmation, exact RWF 5,000 purchase, portfolio position, payout, and receipt screens from shared Resources.
- [ ] Implement the Admin application queue, party view, maker-checker presentation, ledger drill-down, event log, and reason-required control screens.
- [ ] Cover loading, empty, validation, authorization, policy-gated, provider-failure, retry, success, cold-reload, responsive, accessible, and reconnect states; propagate committed server events without manual reload and reconcile from authoritative Resources after reconnect.
- [ ] Add behavior-first Vitest/React Testing Library page, component, form, hook, accessibility, and Resource-fixture tests plus browser journeys; maintain every D-66/D-67 metric, TypeScript, lint, and production-build gate.

#### Phase 1C — Joint integration and Alpha acceptance

- [ ] Integrate checkpoint 1: Party, authorization, active role, and launcher. **IN PROGRESS — implementation and local browser checks pass; exact-commit promotion/review remains open.**
  - [x] Compare the pushed 1A contract at `144066b` with Erastus's 1B source/fixtures at `613e3e4` and publish the [issue #96 integration response](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5800301779). The focused server identity/role suite was rerun with TIA disabled: 35 tests / 175 assertions pass. This verifies the documented server behavior, not the combined UI branch.
  - [x] Confirm the checkpoint 1 role-home, return-position and staff-access contract shapes together. [Erastus confirmed the handoff](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5802126452): bookmarks contain only the named route, validated parameters and query; scroll remains local. `staff_access` is separately versioned and cannot be inferred from marketplace membership or identity-operator access. Implementation and the full staff permission matrix remain separate work.
  - [x] Promote the initial 1A foundation to `dev`. [PR #97](https://github.com/rozine-rw/rozine/pull/97) merged on 2026-09-24 as [`2774c03`](https://github.com/rozine-rw/rozine/commit/2774c03724a950fce5737e8b536b69f23763f002), after all five hosted checks passed at `742cd04`. Aminu explicitly waived Erastus's review for this PR and authorized the merge; this records a scoped waiver, not a completed review or a blanket waiver for later checkpoints.
  - [x] Implement and verify the remaining 1A role-home, bookmark and staff-entry adapters, then integrate the 1B launcher/types/fixtures against `identity-v2`. The shared identity type/export, routes, locale catalogs and source/risk manifests are reconciled; the dashboard and identity routes remain server-backed. Local full-suite and browser evidence is recorded above and in the [entry/navigation ADR](phase-0/adr-0001-modular-monolith-boundaries.md#checkpoint-1-entry-and-navigation-adapters--2026-09-24).
  - [x] Commit and push the integrated candidate, open [PR #98](https://github.com/rozine-rw/rozine/pull/98) into `dev`, and pass local exact-commit PHP/web/browser gates at `04e191c` as recorded above.
  - [ ] Complete [PR #98](https://github.com/rozine-rw/rozine/pull/98)'s exact-commit hosted gates, non-author review and promotion into `dev`. The PR #97 review waiver applies only to the already merged foundation; it is not recorded as review of this candidate.
- [ ] Integrate checkpoint 2: Business application, statement parsing, underwriting, and Auditor filing.
- [ ] Integrate checkpoint 3: wallet, Investor purchase, ledger, primary settlement, and Admin disbursement.
- [ ] Integrate checkpoint 4: repayment, payout, online propagation/reconnect, and secondary-ready Holding/Order contracts.
- [ ] Integrate checkpoint 5: the complete witnessed Business → Auditor → Core → Investor → Admin Alpha chain and exact-SHA evidence pack.
- [ ] At every checkpoint, reconcile Resource schema, TypeScript contract, deterministic fixture, authorization, error-code, and route identities; each developer reviews the other lane's latest SHA and the exact candidate passes the full non-TIA PHP gate, complete web coverage gate, architecture/static/type/lint/build gates, and applicable browser checks.
- [ ] Build Phase 1 controls, logs, metrics, alerts, fixtures, denial cases, and recovery evidence inside the same slices; no later Admin/hardening phase may supply missing safety retrospectively.

### Deliverables

**Aminu — server/API/domain:** shared Laravel actions, policies, state machines, exact-money types, events/outbox, models/migrations/factories, provider adapters, Inertia/API controllers, shared Resources, `/api/v1` Phase 1 endpoints, Party/KYC/KYB, deterministic underwriting, Auditor workflow, balanced ledger, wallet, primary settlement, disbursement, repayment, payout, reconciliation, Admin controls, and frozen secondary-ready Holding/Order/reservation/fee/halt contracts.

**Erastus — Inertia React UI/UX:** typed launcher and role switching; Business, Auditor, Investor, and Admin Alpha surfaces; approved logo components; localization/catalog foundation; responsive/PWA, accessibility, reload/reconnect, and complete screen-state behavior.

**Joint:** approved contract schemas, TypeScript prop contracts, deterministic end-to-end fixtures, authorization matrix, five integration-checkpoint records, prohibited-client-authority and prohibited-principal-path evidence, witnessed Alpha chain, and the exact Alpha-commit quality pack containing 100% PHP line coverage; complete D-66/D-67 web metric, branch, and changed-branch reports; architecture; Pest-aware PHPStan/Larastan; frontend static/build; source/risk manifests; browser results; review attestations; and full-suite run identities.

### Acceptance Criteria

- [ ] The complete Business → Auditor → Core → Investor → Admin chain succeeds once from a clean isolated environment and can be replayed deterministically.
- [ ] Every journal balances, client/company/control money remains segregated, and every displayed monetary figure opens its authoritative ledger/schedule basis.
- [ ] Repeating or reordering a financial command/callback produces one effect; no optimistic UI can create ownership or money.
- [ ] Rating/capacity/yield are computed only by the approved engine and trace to immutable inputs, intermediates, and policy versions.
- [ ] Every mutation records actor, role, reason where required, before/after, time, correlation, and policy version; no delete/overwrite path exists.
- [ ] An eligible Investor can invest exactly RWF 5,000 only after full cost/risk disclosure; insufficient balance, ineligible Party, stale Note, or wrong role fails server-side.
- [ ] A user sees only authorized records and applications; role switching never leaks state or requires a second login.
- [ ] One committed core change appears in all affected online applications without manual reload and converges after reconnect.
- [ ] Alpha screens have actionable errors/gates and survive a cold reload without losing accepted drafts or committed state.
- [ ] Every Phase 1 action exposed through both transports returns the same authorized Resource facts, stable codes, exact-money serialization, and policy version through Inertia and `/api/v1`; the web application never calls its own public API over HTTP.
- [ ] No React component, hook, form, browser store, Resource, or controller contains authoritative money, underwriting, eligibility, permission, fee, ownership, or transition logic; server-side denial tests prove client requests cannot override it.
- [ ] All five integration checkpoints have matching Resource/TypeScript/fixture identities, approval of shared contract changes by both developers, cross-review of the latest candidate SHA, and complete applicable gates.
- [ ] The current logo/role identity is consistent and accessible across launcher and four applications.
- [ ] Alpha user-facing strings are catalog-backed; missing-key/hard-coded-string lint and representative pseudo-localization pass without layout or meaning loss.
- [ ] The exact Alpha commit passes the complete non-TIA Pest suite at 100.0% `app/` line coverage and every Section 11.1 web threshold: 100.0% lines/statements/functions globally and per file, 100.0% critical branches globally and per file, 95.0% non-critical branches globally and at least 90.0% per file, and 100.0% newly changed branches. Every architecture, PHPStan/Larastan, TypeScript, lint, build, and applicable browser gate is green. Coverage alone does not substitute for the financial, authorization, property, concurrency, replay, denial, accessibility, branch/state, or user-journey assertions above.

### Verification and exit gate

At each of the five checkpoints, run Resource/API/Inertia parity, authorization, stable-error, exact-money, and deterministic-fixture contract tests plus the applicable PHP and React slice suites. At Alpha exit, run golden/property tests, ledger invariants, idempotency/PostgreSQL concurrency tests, provider replay tests, policy/Resource parity and denial suites, complete Vitest/React coverage, Inertia browser journeys, PWA reload checks, accessibility smoke checks, and the witnessed end-to-end chain. Archive the exact commit's full PHP coverage; web metric matrix, risk-tier and immutable base/head changed-branch reports; architecture; Pest-aware PHPStan; frontend static/build; source/risk manifest outputs; and both developers' latest-SHA reviews. Impacted-test results may support iteration but are not Alpha exit evidence. Phase 1 may exit only as `MVP ALPHA`; no real-money, production, or regulatory claim is permitted.

---

## Phase 2 — MVP lifecycle completeness, offline verification, and operations

**Status:** `NOT STARTED`

**Estimated two-developer agent-native active engineering:** `2.5–3.5 focused weeks` · **Confidence:** Low

**Scheduling note:** Place this work in Weeks 3–6. Start non-activatable state fixtures and secondary eligibility/read models during the final stable Phase 1 slice. One developer integrates servicing, reconciliation, distress, and secondary lifecycle prerequisites while the other integrates the shared screen-state matrix and Auditor offline/PWA workflow; bounded agents fan out by contract. Provider behavior, field scheduling, and real-device browser evidence can extend calendar time.

### Goal

Turn the alpha path into a complete operating lifecycle in which every named screen/state has a valid next action, Auditor fieldwork survives disconnection, monthly evidence and money reconcile, and exceptions are visible and governable.

### Dependencies and release surface

- Depends on Phase 1 and the approved Auditor PWA/native-exception decision.
- Completes the 9 Business, 8 Auditor, 11 Investor, and 10 Admin screen contracts plus launcher recovery behavior.
- Uses sandbox/fake rails for engineering acceptance; live provider certification remains Phase 4.

### Checklist

- [ ] Implement every stable screen/state ID from the MVP matrix, including loading, empty, validation, gated, frozen, expired, overdue, failed settlement, unavailable provider, retry, offline, conflict, reconnect, and recovery.
- [ ] Complete Business dashboard, application/rating/raise states, schedule and receipts, monthly report composer, audit history, wallet history, profile/document expiry, and actionable refusal/requalification.
- [ ] Enforce the approved monthly report window, required evidence, Business submission, originating Auditor co-signature, immutable publication/amendment, missed-window controls, and affected-party notifications.
- [ ] Implement full repayment/disbursement/payout/withdrawal schedules, exact approved fees, early-payoff behavior, record-date allocation, final residuals, statements, and ledger/provider reconciliation.
- [ ] Implement Auditor dispatch/rotation/capacity/conflict, deadlines, prepared job package, fixed procedures, comparison/reconciliation, variance explanation, immutable filing/seal, reports, attributable earnings, and licence/sanction states.
- [ ] Complete Auditor offline/PWA capture with encrypted local package, camera-only capture where required, geo/time provenance, storage/permission failure, process kill/restart, duplicate/reordered sync, conflict resolution, lost-session recovery, and no data loss.
- [ ] Implement Investor audit/report visibility, portfolio totals, issue/live rating, schedule/payouts, concentration, flags/freezes/arrears, withdrawal, statements/exports, and honest realised-versus-projected presentation.
- [ ] Implement the feature-flagged secondary eligibility matrix and read models from current Holding ownership, Note standing/health, latest report, remaining schedule, restrictions, record date, and active global/per-Note halt; stale, Arrears, Default, Disputed, frozen, or unowned positions cannot reach an order command.
- [ ] Implement Admin Today, reconciliation/break ownership and ageing, Book, Exceptions, Partners, Parties, scoped act-as, freezes/releases, recovery plans, immutable amendments, regulator-ready event reconstruction, and operational reports required by active flows.
- [ ] Implement approved arrears, reporting breach, cure, dispute/complaint, recovery, write-off/recovery, and cross-role state propagation; no PDF penalty/default shortcut is permitted.
- [ ] Enforce reason-required mutations, policy-driven maker-checker/dual approval, no audit/ledger deletion, and compensating financial remedies only.
- [ ] Implement notification inbox/preferences/delivery evidence for required events without allowing opt-out of mandatory notices.
- [ ] Preserve drafts, position, offline queue, and committed facts across refresh, close/reopen, reconnect, and deployment-compatible Resource changes.
- [ ] Prove report completion, photo upload, and critical dashboards under throttled mobile networks; keep performance/accessibility/security budgets continuously green.
- [ ] Keep the Investor Automation screen visibly gated with the missing approval and next step; it cannot execute Plus mandates in the MVP.
- [ ] Extend the Pest behavior/architecture and Vitest/React behavior suites with every lifecycle slice, including offline sync, provider replay/reversal, reconciliation, distress, authorization, screen-state branches, and secondary-eligibility invalidation; preserve every Section 11 PHP and D-66/D-67 web threshold plus zero-error static analysis.

### Deliverables

- Complete 38-screen/state implementation and evidence matrix.
- Monthly reporting, servicing, payouts, withdrawals, statements, notifications, and cross-role portfolio experiences.
- Auditor offline/PWA package, capture, sync, conflict, seal, earnings, licence, and sanctions workflows.
- Admin daily reconciliation, breaks, exceptions, partner/party oversight, recovery, and supervisor reconstruction controls.
- Distress/dispute/recovery state machines, immutable amendments, and provider/reconciliation runbooks.
- Secondary eligibility/read models, state-transition fixtures, disclosure facts, and halt prerequisites ready for Phase 3 settlement integration.
- Full lifecycle test, device/browser, performance, accessibility, and operational evidence packs.
- Exact Phase 2 commit quality pack containing full PHP coverage and complete Section 11.1 web metric/branch evidence, architecture, Pest-aware PHPStan/Larastan, frontend static/build, source/risk manifests, and test-run evidence.

### Acceptance Criteria

- [ ] Every named MVP screen/state renders the right authorized facts and a permitted next action; no blank frame, generic denial, or unexplained freeze remains.
- [ ] An Auditor completes a representative field visit without signal, survives interruption, syncs exactly once without loss/corruption, and cannot back-date or gallery-borrow protected evidence.
- [ ] No submitted audit/report is editable; corrections are linked, attributable amendments that preserve what prior users saw.
- [ ] Monthly reporting and co-signature enforce the approved window and evidence contract; a missed window triggers only the approved attributable controls.
- [ ] Schedules, repayments, payouts, withdrawals, fees, early payoff, and final residuals reconcile to the franc across ledger, Resources, and all roles.
- [ ] Daily reconciliation cannot silently close with an unexplained break; breaks remain visible, aged, assigned, and reason-resolved.
- [ ] Arrears, freezes, disputes, recovery, licence, and evidence-integrity changes propagate immediately to every affected authorized role.
- [ ] Every lifecycle transition that makes a Holding ineligible immediately removes or blocks its secondary action and deterministically cancels or suspends affected open reservations under the approved D-26 rules.
- [ ] A cold reload/reconnect loses no accepted draft, offline capture, position, or committed state.
- [ ] Business, Auditor, Investor, and Admin acceptance matrices from PDF pages 6, 8, 11, and 13 are either passing or explicitly mapped to Phase 3 finish work; no conflicting PDF rule is used as evidence.
- [ ] The exact Phase 2 exit commit passes the complete non-TIA Pest suite at 100.0% `app/` line coverage and every applicable D-66/D-67 web threshold, including 100.0% newly changed branches; every architecture and static/build gate is green, and no exclusion, risk downgrade, or suppression conceals an offline, UI-state, provider, reconciliation, or lifecycle path.

### Verification and exit gate

Run time-travel schedules/windows, accounting properties, provider replay/reversal, offline kill/reorder/corruption, camera/geo/permission, reconciliation, state-transition, notification, authorization, complete Vitest/React coverage, browser/PWA device, accessibility, and throttled-network suites. Archive fresh full PHP coverage; web metric matrix, risk-tier and immutable base/head changed-branch reports; architecture; Pest-aware PHPStan; frontend static/build; and source/risk manifest outputs for the exact exit commit. Exit requires witnessed monthly lifecycle, offline visit, distress/recovery, and daily reconciliation evidence.

---

## Phase 3 — MVP market completion, product finish, demo, and release candidate

**Status:** `NOT STARTED`

**Estimated two-developer agent-native active engineering:** `2–3 focused weeks` · **Confidence:** Low

**Scheduling note:** The historical web/PWA placement was Weeks 4–8 for the feature-flagged secondary lane after Phase 1 freezes ledger/Holding contracts, with Week 9 planning closure and Week 10 remediation. D-04 has triggered the proposed Section 9.1 adjustment; these are not unchanged calendar commitments. Governed settlement activation and final acceptance still wait for Phase 2, and mandatory secondary scope is unchanged. Agents work on bounded order/settlement, browser, concurrency/failure and acceptance slices while both developers retain daily integration and cross-review. External reviewer scheduling/turnaround is additional.

### Goal

Produce a polished, secure, accessible, BRS-compliant, sandbox-ready responsive-web `MVP RELEASE CANDIDATE` with peer-to-peer liquidity, complete supervisory evidence, and an isolated resettable demo—before native mobile, Pulse, Plus execution, or other extensions consume delivery capacity.

### Dependencies and release surface

- Depends on Phases 0–2.
- Completes the MVP responsive web/PWA, launcher, peer-to-peer market, regulator/demo surfaces, and release evidence.
- Does not authorize a regulated sandbox, production money, native-store release, Pulse, Rozine principal inventory, reserve cover, or Plus execution.

### Checklist

- [ ] Implement Investor-to-Investor secondary eligibility, order, reservation, chosen ask, partial-fill/cancel/expiry policy, halt, disclosure, atomic settlement, Holding transfer, and BRS 3% seller fee.
- [ ] Prohibit Rozine principal inventory, proprietary orders, market making, price setting, reserve-cover claims, maker/taker/acquisition fees, and any demo sale “to Rozine.”
- [ ] Keep executable Plus bands/mandates feature-flagged off; provide only the approved gated explainer and move implementation to Phase 7.
- [ ] Close D-61 before RC: either obtain signed approval to defer the PDF's Plus/mandate Investor rows and mark them `DEFERRED — NOT MVP-APPLICABLE` with rationale/owner, or approve the complete Plus policy and rebaseline the MVP scope/timeline before implementing it.
- [ ] Complete required portfolio, concentration, realised/projected return, book/partner/fee/reconciliation, board, and self-describing regulatory reports from durable source events.
- [ ] Build a resettable, isolated seeded book covering healthy, arrears, frozen, recovery, matured, peer-to-peer exited, and refused cases across all four BRS rating bands; Distressed is never listable.
- [ ] Deliver the 90-second launcher journey: sign in, choose an authorized role, enter a meaningful state, perform a safe representative action, and trace the result to its source evidence.
- [ ] Normalize approved star/vector masters, semantic filenames, clearspace/viewBoxes, responsive headers, favicon/PWA/Apple-touch assets, monochrome/dark fallbacks, and real 16/32px tests; unused alternate lockups remain out of production.
- [ ] Complete WCAG 2.2 AA web review, keyboard/screen-reader/text-scaling/reduced-motion coverage, content/disclosure review, and approved launch-language checks.
- [ ] Meet throttled-3G first-interaction and layout-stability budgets on the minimum supported mobile viewport; complete load, queue, provider-outage, restore, and failover evidence.
- [ ] Run threat modeling, static/dependency/dynamic checks, independent penetration/security review, privacy/retention review, financial certification, and close every high-severity finding.
- [ ] Run the 12 spine, 8 Business, 8 Auditor, 10 Investor, 8 Admin, launcher/demo, and applicable BRS acceptance matrices with immutable linked evidence; no row may disappear, and any scoped deferral requires its signed conflict disposition.
- [ ] Execute an internal no-real-participant/no-money rehearsal of support, reconciliation, incident, daily-review, rollback, and stop procedures.
- [ ] Prepare checked `dev -> uat` release, migration/rollback, operations, support, and go/no-go evidence without promoting to production or presenting fixtures as live.
- [ ] Run the exact RC commit through the authoritative clean-checkout PHP and web gates: non-TIA Pest at 100.0% `app/` lines and the complete D-66/D-67 Vitest metric, risk-tier branch, and changed-branch gates, plus all architecture, PHPStan/Larastan, TypeScript, lint, build, browser, and accessibility checks. Publish outputs with the SHA and reject impacted-only, stale, or different-SHA evidence.

### Deliverables

- Atomic BRS-compliant peer-to-peer secondary market and halt/reconciliation controls.
- Finished responsive MVP applications, launcher, reports, and governed new-logo asset package.
- Resettable isolated demo book and scripted Business/Auditor/Investor/Admin/regulator demonstrations.
- Accessibility, performance, resilience, privacy, security, financial-certification, UAT, and internal-rehearsal dossiers.
- MVP acceptance matrix with evidence links, exceptions, owners, and signed release-candidate go/no-go record.
- Exact RC-commit cross-platform quality dossier: complete PHP coverage; web lines/statements/functions, risk-tier branch, and immutable base/head changed-branch reports; architecture-test results; Pest-aware PHPStan/Larastan and frontend static/build output; source/risk manifests; and dedicated TIA-baseline identity for subsequent developer use.

### Acceptance Criteria

- [ ] Eligible peer-to-peer settlement changes cash and Holding ownership exactly once or changes neither; no prohibited principal/market-maker path exists.
- [ ] Every applicable MVP acceptance row passes using BRS-governed values and behavior; a PDF row is `DEFERRED/NOT APPLICABLE` only through a signed conflict disposition, and no unresolved claim is marked passed.
- [ ] Every monetary fact traces to ledger/schedule evidence and every rating/capacity/evidence fact traces to immutable inputs, derivations, and policy versions.
- [ ] First meaningful interaction is under five seconds on the approved throttled-3G profile with no material post-paint layout shift.
- [ ] All supported MVP journeys pass accessibility, privacy, authorization, content/disclosure, and responsive checks.
- [ ] No unresolved high-severity security finding, unreconciled financial break, unowned operational blocker, or uncontrolled demo/live-data path remains.
- [ ] Demo reset is deterministic and isolated; no visitor can touch real records or confuse seeded activity with live activity.
- [ ] The approved new logo is derived from rights-cleared canonical vector masters and is consistent, accessible, and legible across launcher, four applications, reports, favicons/PWA assets, and demo material.
- [ ] Accountable Product, Finance/Risk, Compliance, Legal, internal Audit Operations, applicable external ICPAR/Audit Partner, Security, Engineering, Design/Brand, independent test, Support, and Operations owners sign the MVP RC.
- [ ] The exact RC commit passes clean-checkout full PHP and web executions—not impacted-test replays—with 100.0% `app/` lines and every D-66/D-67 client metric, risk-tier branch, and changed-branch threshold; all architecture/static/build gates pass, and no unexplained exclusion, risk downgrade, ignored error, warning, risky test, or deprecation remains.

### Verification and exit gate

Run the authoritative full PHP/web CI gates, contract/accounting/concurrency suites, responsive browser/PWA E2E, secondary failure injection, device/browser matrix, accessibility, 3G/load/soak, restore/rollback, security reviews, financial reconstruction, and witnessed acceptance. Archive PHP coverage; the complete web metric matrix, source/risk manifests, and immutable base/head changed-branch report; architecture; PHPStan/frontend static/build; and exact-SHA evidence. The dedicated baseline workflow may then record TIA state for developer replay. D-61 and every other MVP-scope disposition must be signed. Exit creates a sandbox-ready `MVP RELEASE CANDIDATE`; it does not close production/live BRS acceptance.

---

## Phase 4 — Governed MVP pilot, production-rail certification, and lifecycle proof

**Status:** `NOT STARTED`

**Estimated two-developer AI-assisted active engineering:** `3–5 focused weeks`, plus external authorization, certification, observation, and the real Note lifecycle · **Confidence:** Low

**Scheduling note:** Begins only from the Phase 3 release candidate. Provider/CMA/legal waiting and the observation period are not engineering weeks. Post-MVP feature work may begin in isolated branches after Phase 3, but it cannot enter the governed MVP pilot release without separate approval.

### Goal

Turn the release candidate into `LIVE MVP ACCEPTED` by certifying real rails, operating a controlled authorized cohort, completing the required real lifecycle, and proving the controls under live conditions.

### Dependencies and release surface

- Depends on the Phase 3 `MVP RELEASE CANDIDATE` and every provider, legal, regulatory, security, operations, and participant-readiness gate named below.
- Covers the governed sandbox and production-evidence boundary for the responsive-web MVP; it does not pull native mobile, Pulse, or unapproved extensions into the pilot.
- The three-month-or-longer Note lifecycle is elapsed production evidence, not engineering effort, and cannot be simulated closed.

### Checklist

- [ ] Obtain CMA/legal authorization, approved caps/participants/duration/stop criteria, production account topology, and named go/no-go authority.
- [ ] Certify KYC/KYB, registry, ICPAR, storage, parsing, MoMo, bank, messaging, and regulatory-report integrations with replay/reversal/outage evidence.
- [ ] Complete independent security retest, production data/residency/retention review, operational training, support, on-call, incident, reconciliation, and provider escalation readiness.
- [ ] Execute the regulated sandbox with approved participants, consent, limits, daily review, reconciliation, complaints, stop controls, and immutable evidence.
- [ ] Originate, fund, disburse, report, repay, and mature at least one real minimum-three-month Note on approved production rails.
- [ ] Prove real RWF 5,000 investment, monthly verified report, payout, eligible peer-to-peer secondary trade, and withdrawal on production rails where authorized.
- [ ] Prove live Auditor accreditation, field evidence, co-signature, publication, seal, and attributable earnings treatment.
- [ ] Reconcile bank/MoMo/provider/ledger/control accounts daily and resolve every break through governed actions.
- [ ] Record incidents, complaints, defaults/recoveries if they occur, KPI outcomes, policy versions, and every release/rollback decision without hiding adverse evidence.
- [ ] Promote only through checked `feat/* -> dev -> uat -> main` pull requests and verify production health/reconciliation after each approved release.
- [ ] Before every pilot promotion, rerun the complete PHP and D-66/D-67 web coverage, architecture, Pest-aware PHPStan, frontend static/build, and applicable browser gates on the exact promoted SHA; any PHP/TypeScript/React/configuration/dependency/risk-manifest change invalidates older evidence.

### Deliverables

- Provider certification, CMA/legal authorization, pilot protocol, and participant evidence pack.
- Live reconciliation, security, operations, incident, complaint, and supervisory reporting dossiers.
- Complete real Note lifecycle and role-journey evidence.
- Live MVP acceptance/go-no-go record and post-pilot findings backlog.
- Per-promotion exact-SHA PHP coverage plus the complete web metric matrix, source/risk manifest hashes, and immutable base/head changed-branch evidence linked to the release and pilot dossiers.

### Acceptance Criteria

- [ ] No real participant or money enters before all approvals, caps, providers, support, and stop controls are active.
- [ ] Every live journal balances, segregated balances reconcile, and duplicated/reordered provider events cannot lose or double-apply money.
- [ ] A complete real three-month-or-longer Note lifecycle and applicable production role journeys have witnessed evidence.
- [ ] Every live exception, complaint, break, incident, and policy action is attributable, controlled, and reconstructible.
- [ ] No unresolved critical/high security, financial, legal, regulatory, provider, or operational blocker remains at live acceptance.
- [ ] Pulse-specific BRS AC-11 and native-mobile acceptance remain open until Phases 6 and 5 respectively; `LIVE MVP ACCEPTED` does not misstate full-roadmap completion.
- [ ] Every promoted pilot SHA passes Section 11's complete PHP and TypeScript/React metric, risk-tier branch, changed-branch, architecture, and static/build gates; no prior SHA, impacted-only output, or risk reclassification without approval is accepted for a changed release.

### Verification and exit gate

Use live provider evidence, daily reconciliation, supervisor reconstruction, security retest, incident/rollback exercises, participant UAT, regulatory reports, exact-SHA full PHP coverage and D-66/D-67 web metric/risk-tier/changed-branch outputs, and the complete lifecycle dossier. Exit requires the named regulator/legal/product/finance/security/operations authorities to sign `LIVE MVP ACCEPTED`.

---

## Phase 5 — Post-MVP native mobile applications and store distribution

**Status:** `NOT STARTED`

**Estimated two-developer AI-assisted active engineering:** `5–8 focused weeks`, plus app-store review · **Confidence:** Low

**Scheduling note:** Starts after the Phase 3 MVP RC, preferably after Phase 4 stabilizes the live contracts. Reserve the first 2–3 focused days for the native runner/source-map and D-67 policy proof; this remains inside the estimate when the selected stack passes, and a failed proof blocks stack acceptance rather than weakening coverage. Auditor native work goes first if Phase 0 recorded an MVP exception; otherwise prioritize the highest-value role sequence. Native clients reuse the same actions/Resources and never fork business rules.

### Goal

Deliver governed native mobile access for the approved Investor, Business, and Auditor journeys using the established core, with platform-correct security, offline behavior, accessibility, branding, release, and compatibility controls.

### Dependencies and release surface

- Depends on the Phase 3 stable action/Resource contracts; production rollout should consume Phase 4-certified providers and policies.
- A narrowly scoped Auditor native companion may move earlier only through the Phase 0 PWA-assurance exception and a rebaselined MVP schedule.
- Covers native Investor, Business, and Auditor clients plus their versioned API, device, store, and support contracts; the Laravel monolith remains authoritative.

### Checklist

- [ ] Approve one shared mobile stack, application packaging/role strategy, supported OS/device matrix, API support/deprecation window, and store ownership.
- [ ] Approve native test/coverage runners with the stack decision and enforce 100.0% lines/statements/functions globally and per file for every first-party native target; 100.0% branches globally and per file for critical native/platform-bridge source; 95.0% branches globally and at least 90.0% per file for approved non-critical presentation/platform adapters; and 100.0% newly changed branches. A target whose runners cannot prove the contract cannot be selected or released.
- [ ] Before accepting the native stack, time-box a runner/source-map proof that includes unimported source and emits file-level line, statement, function, branch-location, and branch-outcome counts for shared source and every authored platform bridge on each shipped target; missing or opaque metrics block the stack decision.
- [ ] Create native source and risk manifests for platform scaffolding, code generation, declarations, vendor/build output, and every authored file. Mixed/unclassified files default critical; storage, sync, permission, identity/security, camera/location/biometric, push/deep-link authorization, provider, evidence-integrity, and recovery bridges are critical despite an adapter or platform label.
- [ ] Evaluate shared and platform-specific coverage/policy reports independently so one target cannot average away or conceal a deficient iOS, Android, shared-client, or custom bridge result.
- [ ] Complete versioned `/api/v1` authentication/token/device, error, pagination, idempotency, concurrency, offline, forced-update, and compatibility contracts.
- [ ] Reuse the Phase 1–4 application actions, policies, Resources, state machines, exact-money values, and disclosures; prohibit authoritative client-only calculations.
- [ ] Implement approved Investor and Business mobile journeys with complete gated/error/offline/reconnect states and Resource parity.
- [ ] Implement Auditor device binding/biometrics, offline encrypted packages, camera-only capture, geolocation, background sync, lost-device recovery, and integrity controls to the accepted standard.
- [ ] Implement push/deep links, notification preferences, required-notice behavior, delivery evidence, minimum-version, rollback, and support diagnostics.
- [ ] Produce platform-correct assets from canonical masters: iOS opaque source, Android adaptive foreground/background and monochrome icon, notification mark, safe-area splash, and store art; never stretch the JPEG tile.
- [ ] Approve a per-bundle identity matrix—store/app name, visible and accessible Rozine/role naming, role lockup, icon foreground/background, splash, notification mark, screenshots, and light/dark treatment—for either one role-switching app or each separate role app.
- [ ] Run real-device permissions, low-storage, process-kill, clock, network, accessibility, battery/background, upgrade/downgrade, and forced-update tests.
- [ ] Prepare signed builds, privacy declarations, store metadata, review responses, staged rollout, crash/health monitoring, and rollback.
- [ ] Keep every changed Laravel action, policy, Resource, API controller, sync endpoint, and provider seam within Section 11's 100% PHP line gate, and every shared TypeScript/React/native/platform-bridge file within the D-66/D-67 metric and risk-tier branch gates; real-device evidence remains additional.

### Deliverables

- Native client(s), versioned mobile API/Resource contracts, device/offline/push services, and compatibility policy.
- Platform icon/splash/store packages and a signed per-bundle identity matrix based on the approved new logo.
- Mobile security, accessibility, performance, real-device, store, rollout, and support evidence.
- Exact server/client commit quality evidence containing PHP, shared-web where applicable, and complete native lines/statements/functions, risk-tier branch, and changed-branch reports; native static analysis, source/risk manifests, real-device tests, and signed-build evidence for every release candidate.

### Acceptance Criteria

- [ ] The same actor/record/policy version receives semantically identical authorized facts and outcomes through Inertia and native API flows.
- [ ] No native client can bypass server authorization, limits, state transitions, idempotency, evidence integrity, or exact-money behavior.
- [ ] Required offline journeys survive process/network/device failure and converge without data loss or duplicate effects.
- [ ] Supported devices pass accessibility, permissions, performance, upgrade, deep-link, forced-update, and brand-asset tests.
- [ ] Every signed app bundle matches its approved store/app name, visible/accessibility identity, role treatment, icon layers, splash, notification mark, screenshots, and light/dark matrix.
- [ ] Store releases are signed, privacy-correct, observable, staged, and rollback-capable.
- [ ] Every exact server/client release commit passes 100.0% PHP lines and every applicable D-66/D-67 web/native metric, risk-tier branch, and changed-branch threshold; all architecture/static/build gates pass without replacing branch/state, real-device, permission, offline, accessibility, or store gates.

### Verification and exit gate

Run complete mobile unit/component/integration/contract/E2E and coverage suites, server Resource parity, full PHP/shared-web gates, real-device/offline/permission matrices, security review, accessibility audit, signed-build validation, and staged store acceptance. Archive every global/per-file metric, risk-tier branch report, immutable base/head changed-branch report, and source/risk manifest hash for the exact server/client commits. Exit requires production-compatible native journeys without a parallel domain implementation.

---

## Phase 6 — Post-MVP Pulse acquisition and authenticated conversion

**Status:** `PARTIAL FOUNDATION`

**Estimated two-developer AI-assisted active engineering:** `2–3 focused weeks` · **Confidence:** Medium

**Scheduling note:** Starts after the Phase 3 MVP RC so the public product consumes stable production calculations and onboarding contracts. Existing Pulse code is reused only where it passes reconciled truthfulness, privacy, and parity tests.

### Goal

Ship the BRS non-binding public acquisition surface without contaminating the MVP delivery path: truthful Business pre-qualification, Investor pledge illustration, real demand signals, governed sharing, and duplicate-free conversion into the authenticated applications.

### Dependencies and release surface

- Depends on the Phase 3 stable underwriting, identity, consent, onboarding, and Resource contracts; production activity counters also depend on consented real data.
- May proceed independently of native Phase 5, but cannot claim full-BRS acceptance until its own BRS AC-11 and Appendix A gates pass.
- Covers public Pulse and its authenticated handoff only; it does not move funds, issue Notes, or reopen MVP scope.

### Checklist

- [ ] Replace prototype/browser formula paths with the approved server underwriting action and shared Resource.
- [ ] Require the approved statement evidence path and present ineligible/manual/indicatively-pre-qualified branches from unrounded authoritative DSCR.
- [ ] Implement the approved Investor pledge range, rounding, non-binding/no-funds/no-guarantee/fee disclosure, and policy/version storage.
- [ ] Replace random counters/sequences and fictional live deals with persistent privacy-safe real activity and consented rotating pre-qualified records.
- [ ] Implement signed/unguessable expiring/revocable passes with minimal approved PII and abuse controls.
- [ ] Convert registrations into the existing Party/onboarding flow without duplicate identities or re-entry of permitted data.
- [ ] Remove prohibited tax, safety, guarantee, “bank-grade,” unproven encryption, fixed 13%, and other rejected claims.
- [ ] Complete responsive, accessibility, performance, upload, bot/rate-limit, privacy, content, and environment-isolation tests.
- [ ] Apply the approved primary Rozine lockup; role lockups appear only in their approved context.
- [ ] Extend Pest behavior/parity/abuse/privacy/authorization/architecture tests and Vitest/React behavior tests with the Pulse slice; preserve every Section 11 PHP and D-66/D-67 web coverage gate while keeping browser E2E evidence separate.

### Deliverables

- Remediated Pulse page/endpoints, production-engine calculation Resources, persistent registration/sequence/counter system, governed sample feed, passes, and onboarding handoff.
- Pulse parity, truthfulness, privacy, abuse, responsive, and conversion evidence.
- Exact Pulse-commit PHP coverage; complete web lines/statements/functions, risk-tier branch, and immutable base/head changed-branch reports; architecture; Pest-aware PHPStan/Larastan; frontend static/build; source/risk manifests; and browser-test evidence.

### Acceptance Criteria

- [ ] Pulse moves no money, issues no instrument, creates no obligation, and never presents a projection as guaranteed or realised return.
- [ ] Identical approved inputs/policy produce identical authenticated and Pulse results; no authoritative formula exists only in browser code.
- [ ] Public activity reflects real server facts, and production cannot silently fall back to seeded/random/fictional activity.
- [ ] Conversion preserves consent/provenance and cannot duplicate a Party.
- [ ] BRS AC-11 and Appendix A Pulse vectors pass with witnessed evidence.
- [ ] The exact Pulse exit commit passes complete PHP and web suites at 100.0% `app/` lines and every D-66/D-67 client metric, risk-tier branch, and changed-branch threshold, plus every architecture/static/build gate with zero errors. Pulse eligibility, formula/disclosure, pass/consent, no-funds/non-binding, sample/counter provenance, and conversion branches remain critical.

### Verification and exit gate

Run calculation/vector parity, aggregate reconciliation, sequence concurrency, pass enumeration/expiry/revocation, upload/abuse, privacy/content scans, complete PHP and D-66/D-67 web coverage, responsive browser E2E, and authenticated-conversion tests. Archive the complete metric matrix, risk/source manifest hashes, and immutable base/head changed-branch report. Exit closes the deferred Pulse acceptance boundary without changing MVP financial rules.

---

## Phase 7 — Approved post-MVP product, Plus, operational, and institutional extensions

**Status:** `BLOCKED`

**Estimated two-developer AI-assisted active engineering:** `4–8 focused weeks per approved tranche`; re-estimate after scope/policy approval · **Confidence:** Low

**Scheduling note:** Nothing in the PDF's “Not in MVP” lists is automatically authorized future scope. Work is selected into small tranches after Phase 3, with a formal BRS/product/legal change record where required and independent release gates.

### Goal

Add only extensions with demonstrated value, approved regulation/economics, bounded dependencies, and explicit non-regression protection for the live MVP.

### Dependencies and release surface

- Depends on Phase 3 for a stable MVP baseline and on a separate approved charter/amendment for each selected tranche.
- A tranche that changes live money, evidence, providers, or participant obligations also depends on the relevant Phase 4 certification and operations gate.
- No listed idea is committed scope merely because it appears in this phase.

### Checklist

- [ ] Decide, price, legally review, and version Plus membership/bands, rule-based mandates, suitability, concentration, preview, pause/cancel, and fee behavior before any executable automation.
- [ ] Evaluate concurrent raises, invoice/inventory financing, accounting-software integration, automated bank feeds, and expanded messaging as separate underwriting/servicing products—not UI toggles.
- [ ] Evaluate public/institutional APIs, third-party access, team/maker-checker accounts, data contracts, rate limits, consent, and support obligations.
- [ ] Evaluate Auditor handover, automated feed pull, ICPAR CPD filing, and advanced desktop review tooling against evidence integrity and professional standards.
- [ ] Evaluate Admin collections automation, bulk import, configurable workflow, advanced analytics, and external supervisor access with immutable controls.
- [ ] Keep discretionary managed portfolios and Investor-to-Investor wallet transfers prohibited until separately authorized.
- [ ] Keep Rozine principal inventory/market making, unapproved reserve cover, and rejected fee models prohibited unless a formal BRS/legal amendment explicitly replaces the invariant.
- [ ] For every approved tranche, update requirements, threat/data model, policies, migrations, API/Resource compatibility, fixtures, operations, monitoring, and rollback.
- [ ] Extend behavior and architecture tests before activating each approved tranche; every changed first-party PHP path remains subject to D-65 and every TypeScript/React/native path remains subject to D-66/D-67 metric, risk-tier branch, changed-branch, and static/build gates.

### Deliverables

- Approved tranche charter and amendment/decision record.
- Extension implementation with complete domain, web/native/API, Admin/control, test, evidence, operations, and migration/rollback slices.
- Post-release KPI and policy-impact report.
- Exact tranche-commit full PHP coverage; complete applicable web/native metric matrices, risk-tier branch and immutable base/head changed-branch reports; architecture/static/build; source/risk manifests; and client/E2E evidence.

### Acceptance Criteria

- [ ] No extension begins from an unapproved “Not in MVP” bullet or prototype constant.
- [ ] Every financial/regulatory extension has signed Product, Finance/Risk, Compliance, Legal, Security, Engineering, and applicable CMA approval.
- [ ] MVP ledger, evidence, authorization, disclosure, reconciliation, performance, and availability acceptance remain green.
- [ ] Each tranche is independently feature-flagged, reversible where possible, observable, and releasable through the governed branch chain.
- [ ] Every approved tranche's exact exit commit passes every applicable PHP and D-66/D-67 web/native metric, risk-tier branch, changed-branch, architecture, and static/build gate with zero errors; impacted-only runs never satisfy tranche exit.

### Verification and exit gate

Define and execute a tranche-specific gate before implementation starts, including every applicable Section 11 full PHP and D-66/D-67 web/native quality gate. Archive its metric matrices, source/risk manifest hashes, and immutable base/head changed-branch reports. Track and close each approved tranche independently; unapproved ideas remain in the deferred register and do not prevent an implemented tranche or release from being marked accepted. The umbrella phase remains available for future charters without implying that the whole idea backlog must be built.

---

## Phase 8 — Scale, reliability, and governed product evolution

**Status:** `NOT STARTED`

**Estimated two-developer AI-assisted active engineering:** `3–5 focused weeks for the first scale tranche`, then continuous · **Confidence:** Low

**Scheduling note:** Begins after live MVP evidence identifies real bottlenecks. Scale work is evidence-led and incremental; regional/multi-currency expansion is not bundled into infrastructure optimization.

### Goal

Scale the proven marketplace safely, reduce operational cost, and evolve the product from measured outcomes without weakening deterministic decisions, client-money controls, evidence integrity, or regulator visibility.

### Dependencies and release surface

- Depends on Phase 4 live evidence for production bottlenecks and on completion of whichever post-MVP client/product tranche is being scaled.
- Infrastructure optimizations may start from measured MVP evidence, but policy/model recalibration and regional/product expansion require separate authority.
- Covers measured scale, reliability, recovery, cost, and governed evolution; it is not a blanket authorization for new jurisdictions, currencies, or instruments.

### Checklist

- [ ] Define production SLOs/error budgets, capacity model, queue/DB/object-storage/provider limits, and cost/performance baselines from live evidence.
- [ ] Load/soak/failure-test monthly audit peaks, funding events, payouts, reports, secondary settlement, exports, and recovery without proportional manual work.
- [ ] Implement approved partitioning/caching/async/search/archival improvements without creating mutable duplicate financial or decision state.
- [ ] Mature observability, anomaly/fraud signals, reconciliation automation, backup/restore, disaster recovery, incident command, and provider failover.
- [ ] Measure BO-1–BO-8 with owned definitions, sources, targets, freshness, privacy limits, and dashboards; use results to approve the next tranche.
- [ ] Recalibrate only through governed policy/model change control, golden vectors, historical replay, impact reports, and named approvals.
- [ ] Treat multi-country, multi-currency, FX, new instruments, regional identity/registry rails, and new licences as separate discovery/program phases requiring formal authority.
- [ ] Maintain accessibility, localization, security, dependency, privacy/retention, device/API compatibility, and operational training continuously.
- [ ] Extend performance, failure, and architecture suites with every optimization; no caching, async, partitioning, archival, provider, web, or native change may lower any applicable Section 11 PHP or D-66/D-67 client coverage/static-analysis gate.

### Deliverables

- Scale architecture/capacity plan, SLOs/error budgets, cost model, and prioritized remediation tranche.
- Performance/resilience/recovery evidence, mature operational dashboards/runbooks, and product-outcome dashboards.
- Governed policy/model impact reports and separately approved expansion charters where applicable.
- Exact scale-tranche commit quality evidence, including full PHP coverage; complete applicable web/native metric matrices, risk-tier branch and immutable base/head changed-branch reports; source/risk manifest hashes; architecture/static/build; load/failure; and historical-replay outputs.

### Acceptance Criteria

- [ ] Approved scale and recovery objectives pass under representative load/failure while ledger/evidence/authorization invariants remain exact.
- [ ] No optimization introduces a second source of truth, hidden manual dependency, unreconciled cache, or non-replayable decision.
- [ ] BO-1–BO-8 are calculated from durable source events with named owners and no default-derived revenue incentive.
- [ ] Cross-border, FX, new-product, or regional behavior cannot activate without the required legal/regulatory/product program gate.
- [ ] Every scale tranche's exact exit commit passes every applicable PHP and D-66/D-67 web/native metric, risk-tier branch, changed-branch, architecture, and static/build gate with zero errors.

### Verification and exit gate

Run representative load/soak/failure, reconciliation, restore/DR, chaos/provider, security, privacy, accessibility, historical replay, and full applicable PHP plus D-66/D-67 web/native quality gates. Archive the complete metric/risk-tier/changed-branch evidence. Exit each scale tranche only when measured objectives improve without regressing any live MVP acceptance criterion.

---

> **Superseded requirement bank:** Work Packages L0–L12 below preserve the detailed checklist material from the former 13-phase sequence for crosswalk and ticket decomposition. Their phase numbers, statuses, estimates, scheduling notes, dependencies, and exit-transition wording are no longer the execution order. Phases 0–8 above and the crosswalk in Section 10 govern delivery. Every retained verification idea inherits Section 11's complete PHP, web, and applicable native coverage/static/build gates; abbreviated archive wording cannot weaken D-65/D-66/D-67.

## Work Package L0 — Governance, source reconciliation, and reproducible baseline

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Create a single, approved implementation baseline: reconcile conflicting policy, restore a reproducible application boot, freeze the first contract vocabulary, and establish evidence-based delivery controls before feature expansion.

### Dependencies and role surfaces

- No external provider is required to complete the documentation portion.
- All later Business, Investor, Auditor, Admin, and Pulse surfaces depend on this phase.
- Product, Finance/Risk, Compliance, Legal, internal Audit Operations, applicable external ICPAR/Audit Partner, Engineering, Design, and Security owners must be represented in approvals.

### Checklist

- [ ] Approve the authority order in Section 2 and record document owners/versioning rules.
- [ ] Disposition C-01–C-34; do not leave formula, fee, tax-data, disclosure, MVP-scope, signatory, or brand conflicts implicit.
- [ ] Complete and sign Appendix A's underwriting worksheet (`UW-01`–`UW-30`), including every owner, approved value/algorithm, effective policy version, evidence, and migration decision.
- [ ] Classify every Appendix A vector as `READY-TO-BASELINE`, `BASELINED`, `BLOCKED`, `QUARANTINED`, or `REJECTED`; no non-baselined vector may be represented as approved executable evidence.
- [ ] Answer the red decision gates in Section 16 and assign owners to amber decisions.
- [ ] Create a per-ID requirement register covering BO-1–BO-8, BR-1–BR-84, FR-100–FR-702, NFR-1–NFR-14, IR-1–IR-8, CR-1–CR-13, and AC-1–AC-12, mapping each ID to its checklist item, deliverable, test/evidence owner, and acceptance gate.
- [ ] Define a decision-record template for policy, legal, security, architecture, data, and UX decisions.
- [ ] Reconcile `composer.json`, `composer.lock`, and installed dependencies, including `laravel/head`, without adding or changing dependencies unless approved.
- [ ] Prove `php artisan about`, route discovery, the focused existing test suite, and the production asset build from a clean install.
- [ ] Capture the current database schema, route inventory, Resource payload, authentication behavior, CI gates, and deployment environments as baseline evidence.
- [ ] Define feature-branch ownership and enforce `feat/* -> dev -> uat -> main`, one checked pull request per hop.
- [ ] Define environment data rules so local/CI/UAT never masquerade fixture counters or businesses as live production activity.
- [ ] Define the minimum mobile-web viewport, safe-area, virtual-keyboard, orientation, zoom, text-scaling, and supported device/browser assumptions that active Phase 1 must design against.
- [ ] Create a product language glossary for Note, unit, fixed return, rating, health, standing, capacity, verification, seal, wallet, holding, and Audit Partner.
- [ ] Establish the prohibition scanner/search list for TIN, tax, RRA, EBM, guarantees, capital protection, APR, and unapproved “safe” language.
- [ ] Agree how changes to this plan are reviewed, versioned, and marked accepted.

### Deliverables

- Approved source/requirement register and conflict-disposition log.
- Signed underwriting decision worksheet and golden-vector approval ledger from Appendix A.
- Versioned architecture and policy decision records.
- Reproducible local/CI baseline with dependency and environment instructions.
- Current-state route, schema, Resource, authentication, and deployment inventory.
- Product glossary, prohibited-language/data list, and ownership matrix.
- Phase evidence template linking requirements, code, tests, screenshots, migrations, and approvals.

### Acceptance Criteria

- [ ] All 75 current planning inputs are represented by the source register or an asset-family entry, including every one of Robert's 31 current logo SVGs and 31 matching PNGs, the new standalone website reference, and the external MVP Specification.
- [ ] Every C-01–C-34 conflict has an approved outcome, owner, and affected phase.
- [ ] Every Appendix A `DECISION` row has an approved value/algorithm and every golden vector has a named owner, expected result, and policy version.
- [ ] A clean checkout installs, boots, lists non-vendor routes, builds assets, and runs the focused baseline checks without undocumented manual repair.
- [ ] No baseline route, schema, Resource, log field, fixture, or analytics event contains prohibited tax-identifier data.
- [ ] CI and deployment promotion rules are documented and demonstrably match repository configuration.
- [ ] Product, Compliance, Legal, Finance/Risk, Design, internal Audit Operations, applicable external ICPAR/Audit Partner, Security, and Engineering approve the baseline or record explicit exceptions.

### Retained verification ideas

Run dependency validation, application boot/route discovery, focused Pest tests, type/static checks already used by the repository, and the frontend production build. Archive machine-readable outputs and apply them to the active Phase 0 gate.

---

## Work Package L1 — Shared Laravel architecture, Resource contracts, and design system

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Build the shared platform seams that let web and mobile deliver the same authorized facts while presenting role-appropriate, responsive, accessible experiences.

### Dependencies and role surfaces

- Consume active Phase 0's vocabulary, source authority, and booting baseline.
- Applies to every Business, Investor, Auditor, Admin, and Pulse page/screen.
- The mobile framework decision may remain open briefly, but the API, error, pagination, and sync contracts may not.

### Checklist

- [ ] Define `/api/v1` conventions for authentication, pagination, filtering, includes, sorting, locale, idempotency, concurrency, rate limits, and deprecation.
- [ ] Define one machine-readable error taxonomy for validation, authorization, policy denial, invalid transition, idempotent replay, conflict, frozen party, KYC/KYB gate, maintenance, forced update, and retryable provider failure.
- [ ] Establish application actions/services and authorization policies shared by Inertia and API controllers.
- [ ] Create the Resource catalog named in Section 4 with conditional attributes/relationships and stable enums/codes.
- [ ] Add Resource contract tests proving identical authorized data for Inertia props and mobile JSON for the same actor, record, include set, and locale.
- [ ] Add negative contract tests proving sensitive/source-only fields are omitted by role and context.
- [ ] Define schema/API compatibility rules for mobile versions that may lag server deployments.
- [ ] Define cursor-based change/sync endpoints and per-record versioning for offline/reconnect reconciliation.
- [ ] Establish outbox-backed domain events and client refresh semantics; select the live transport later without coupling domain logic to it.
- [ ] Prove an exemplar committed transition propagates to every affected online role client without manual refresh, while reconnect reconciliation reaches the same final state.
- [ ] Define exact RWF value objects/serialization, timestamps, identifiers, geo points, file metadata, rating values, and policy-version representation.
- [ ] Build shared web layout props for role, permissions, maintenance, feature flags, minimum client version, notifications, and locale.
- [ ] Generate web route calls through Wayfinder and prohibit handwritten internal URLs where a named route/action exists.
- [ ] Create design tokens from the Phase 0-approved application palette; until a dated brand amendment exists, retain the guide values—Investor `#0A5CFF`, Business `#12A150`, Auditor `#DD8A00`, and Pulse `#08090D`—and never derive application tokens from JPEG samples.
- [ ] Require one primary action per view and the semantic shape/elevation system: 8px status pills, 12px buttons, 14–16px cards, 999px chips, and flat/resting/lifted elevation.
- [ ] Implement the typography contract: Inter for product UI, Source Serif for long-form documents, approved display/heading/body weights and tracking, 1.6 body line height, uppercase eyebrow treatment, and tabular numeric figures.
- [ ] Treat audience accents as interface context, not identity by color alone; always show an explicit role label and use text/icon/structure when a role-switching shell is approved.
- [ ] Define and test accessible foreground pairs or darker action variants for green/orange interface surfaces. The supplied role logos are green-on-white and orange-on-white; logo text may be contrast-exempt, but their sampled ratios do not authorize the same colors for normal UI text or controls.
- [ ] Preserve word-number-color rating order and prohibit gradients behind content; none of the current logo JPEGs establishes an approved gradient treatment.
- [ ] Create responsive shells and reusable navigation, cards, tables/lists, stepper, timeline, money, rating, status, evidence, empty/error/offline, disclosure, and confirmation components.
- [ ] Inventory all 31 SHA-unique SVG logo sources, replace non-semantic Frame filenames in production use, and record intended lockup, role, surface, background, safe area, clearspace, minimum rendered size, theme, accessibility treatment, provenance, and platform target.
- [ ] Record the former 60-image logo family as superseded/decommissioned source material; do not silently retain an obsolete mark or infer production masters from the old duplicates.
- [ ] Consume and verify the Phase 0-approved, rights-cleared vector/transparent masters, outlined wordmarks, provenance, and font/licence record; any later reconstruction or simplification still requires written brand-owner approval and visual sign-off.
- [ ] Normalize SVG viewBoxes, optical clearspace, minimum sizes, aspect ratios, and transparent padding; do not use the flattened JPEG canvases or baked whitespace as layout spacing.
- [ ] Build typed `LogoMark` and `LogoLockup` components with controlled variants, aspect-ratio protection, desktop/mobile header rules, decorative-image handling, and one accessible home-link name.
- [ ] Consume the Phase 0-approved primary Rozine lockup, canonical star, role-lockup contract, and accessible naming rule; do not reopen the integrated-star versus detached-star choice inside component implementation.
- [ ] Produce the MVP web asset set: simplified 16/32px mark, `favicon.svg`, 16/32/48 `.ico` fallback, Apple touch icon, and PWA `any` and `maskable` icons. Native iOS/Android adaptive, monochrome, notification, splash, and store assets belong to Phase 5.
- [ ] Treat the flattened 1280×1280 rounded-square JPEG only as a visual reference because it has baked white corners; never use it as a PWA, iOS, Android, notification, or favicon master.
- [ ] Build web/PWA launch compositions from a centered vector mark/lockup with approved light/dark safe areas; never stretch a flattened logo tile to the screen.
- [ ] Implement and verify the Phase 0-approved logo surface matrix covering the selected white-on-blue Rozine treatment, blue/green/orange role wordmarks on white, and monochrome/high-contrast/dark fallbacks.
- [ ] Consume the Phase 0-approved exact source colors for the core and three role lockups; JPEG samples near blue `#003AFF`, green `#179B71`, and orange `#C1641D` remain evidence only and must not silently recolor application chrome.
- [ ] Add component documentation and screenshot fixtures at defined desktop, tablet, smallest mobile-web, safe-area, portrait/landscape, virtual-keyboard, zoom, and text-scaling conditions.
- [ ] Externalize all user-visible strings from the first component, establish Kinyarwanda/English/French catalogs and stable message codes, and add missing-key/hard-coded-string lint plus pseudo-localization.

### Deliverables

- Architecture decision record and boundary diagram.
- Versioned API, Resource, error, idempotency, and sync contract specifications.
- Shared application-action and policy conventions with exemplar vertical slice.
- Design token package, accessible component foundation, and role shells.
- Governed logo/asset manifest and complete MVP web/PWA asset package; native/store assets remain Phase 5 work.
- Contract, authorization-omission, and responsive visual test suites.

### Acceptance Criteria

- [ ] One exemplar record rendered through an Inertia route and `/api/v1` returns the same Resource `data` for the same authorization context.
- [ ] The exemplar action has one business implementation and cannot diverge by controller type.
- [ ] Unauthorized/sensitive attributes are absent, not merely null or hidden in the UI.
- [ ] Exact RWF, rating, time, identifier, error, and state representations are stable and documented.
- [ ] No Resource performs writes or owns domain calculations.
- [ ] Responsive shells work without horizontal overflow at agreed breakpoints and meet the chosen accessibility standard.
- [ ] Automated contrast plus forced-colors/high-contrast checks pass for text, controls, status pills, focus, and role accents; a logo exemption does not exempt surrounding UI.
- [ ] All 31 current SVG sources and 31 matching PNG references are accounted for, the legacy family is explicitly retired, and provenance, licensing, exact colors, canonical masters, role naming, accessibility, and production variants are unambiguous.
- [ ] Logo components do not stretch, use the approved lockup by surface, expose one accessible name without duplicate screen-reader output, and never expose a WhatsApp source filename as visible or accessible text.
- [ ] Real 16/32px browser-tab checks, OS light/dark checks, PWA masks, and star/gap legibility pass for MVP assets; Android/iOS mask and notification checks pass in the native-mobile work package.
- [ ] The approved background matrix covers the supplied core and role treatments plus suitable dark, monochrome, and high-contrast fallbacks.
- [ ] Web production build, contract tests, type checks, and representative visual checks pass in CI.
- [ ] One committed cross-role state change reaches all affected online clients without manual refresh and converges correctly after an offline/reconnect cycle.
- [ ] String-catalog lint and pseudo-localization pass for the exemplar web/mobile role slice.

### Retained verification ideas

Use Pest feature/contract tests, policy-denial tests, payload snapshots or schema checks, query-count checks, TypeScript checks, production builds, and browser screenshots. Map the reviewed Resource/design-system exemplar to active Phase 1 and native contract consumption to active Phase 5.

---

## Work Package L2 — Identity, Party model, consent, KYC/KYB, and access control

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Turn starter authentication into a trustworthy, auditable identity and authorization foundation for individual and organizational actors across web and mobile.

### Dependencies and role surfaces

- Route authority/baseline prerequisites to active Phase 0 and the identity/shared-foundation implementation to active Phase 1.
- Covers shared registration/login/recovery/security pages plus Business owner, Investor, Auditor, staff, and supervisor onboarding gates.
- External identity, business-registry, sanctions/PEP, and ICPAR providers require adapters and approved manual fallbacks.

### Checklist

- [ ] Model Party separately from user credentials, with explicit individual/organization profiles and role memberships.
- [ ] Capture the BRS-required Business/owner registration data, contact details, premises geolocation, and registered bank/MoMo payout rails while enforcing the tax-identifier prohibition.
- [ ] Decide and implement whether one identity may hold multiple roles and how the active role changes without privilege leakage.
- [ ] Define organizational team roles for Businesses and institutional Investors if approved.
- [ ] Implement record-level policies for every role and staff function; default deny.
- [ ] Preserve Fortify web sessions and implement the approved Sanctum/mobile token lifecycle, device registration, revocation, and recovery.
- [ ] Require MFA for staff and Audit Partners; define step-up authentication for money, seal, payout-rail, policy, and act-as actions.
- [ ] Define biometric binding as an Auditor device/approval factor, including replacement and accessible recovery; never treat client biometrics as standalone identity proof.
- [ ] Implement KYC/KYB statuses, evidence, tier/category, limits, refresh/expiry, consent, beneficial-owner flow if required, and reasoned manual review.
- [ ] Build provider-neutral adapters for identity, business registry, ICPAR, sanctions, and PEP checks with request/response evidence, timeouts, retries, circuit breakers, and case creation.
- [ ] Ensure business registry integration does not ingest or retain a prohibited tax identifier.
- [ ] Implement legal terms, risk acceptance, privacy consent/lawful basis, version history, withdrawal where applicable, and immutable acceptance evidence.
- [ ] Implement account lifecycle: pending, active, restricted, suspended, frozen, closed, compromised, and recovered.
- [ ] Add session/token/device inventory, security notifications, remote revocation, and suspicious-login signals.
- [ ] Audit every authentication, role, permission, verification, consent, limit, device, and impersonation change.
- [ ] Implement read-only, bannered, reason-required, time-limited staff act-as with immutable logs; prohibit money/seal actions while acting as another party.
- [ ] Apply throttling, enumeration resistance, secure file access, and minimal PII in logs/analytics.

### Deliverables

- Party, role/membership, consent, verification, device, session/token, and provider-evidence models.
- Web and mobile identity/security flows for the three audiences.
- Provider adapter contracts and manual-review queues.
- Central authorization policy matrix and act-as control.
- Identity/KYC/KYB/consent audit evidence and test fixtures.

### Acceptance Criteria

- [ ] A user can complete the approved web and mobile authentication lifecycle without creating duplicate Parties.
- [ ] Cross-role and cross-organization access tests demonstrate default denial and no active-role privilege bleed.
- [ ] Unverified or expired parties cannot cross KYC/KYB, limit, money, job, or seal gates.
- [ ] Staff and Audit Partner MFA, device revocation, and session/token revocation work end-to-end.
- [ ] All provider failures produce deterministic retry/manual-review states without accidental approval.
- [ ] Consent and risk-acceptance evidence identifies exact text/version, actor, time, channel, and correlation ID.
- [ ] A Business cannot complete onboarding without required owner/contact/premises/rail data, and the premises coordinate is available to later dispatch without exposing it beyond authorized contexts.
- [ ] No request, database field, log, event, fixture, or Resource exposes prohibited tax identifiers.
- [ ] Security and authorization tests pass for both session and token transports.

### Retained verification ideas

Use Pest feature tests for Fortify/Sanctum behavior, policy matrices, token abilities, MFA/recovery, provider fakes, rate limiting, consent versions, and act-as restrictions. Map MVP authentication/authorization evidence to active Phases 1–3 and native token/device evidence to active Phase 5.

---

## Work Package L3 — Business evidence ingestion, deterministic underwriting, and rating

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Create one traceable production engine that converts immutable business evidence into verified cash-flow capacity, DSCR tier, pricing, schedule, and the single cross-product rating.

### Dependencies and role surfaces

- Route authority and policy sign-off to active Phase 0 and underwriting/evidence implementation to active Phases 1–2.
- Primary Business surfaces: statement upload, parsing, review, capacity, rating, explanations, and decline/requalification.
- Investor, Auditor, Admin, and Pulse consume the outputs through shared Resources.
- Requires an approved object store and a selected or adapter-backed parsing/OCR provider.

### Checklist

- [ ] Implement `evidence-policy-v1`: every declared active bank/MoMo rail, at least six complete consecutive `Africa/Kigali` calendar months with the latest complete month no more than 45 days stale, and no additional evidence types in v1. Enforce freshness at submission and revalidate it at calculation, offer, and issue. Treat 6–11 months as manual-only; allow 12+ months into the auto route subject to every other gate; require 12 months for seasonal/cyclical, restarted, or material-event cases. Audited-cash/POS evidence is supplementary and forces manual review when it changes underwriting cash flow.
- [ ] Store originals immutably with content hash, uploader, source, received time, and chain-of-custody metadata.
- [ ] Implement asynchronous parsing with progress, confidence, structured errors, retry, and human-review request.
- [ ] Preserve raw parser output separately from normalized and user-confirmed values; corrections are versioned, attributable, and reasoned.
- [ ] Encode the product-approved `engine-score-v1` scorecard, normalization curves, mandatory-evidence behavior, new-Business conduct neutral, arrears/default caps, and non-public component policy from Appendix A.3.2; complete its signed operational specification before activation.
- [ ] Define volatility trimming, NOCF/CFADS construction, all-debt obligations, and remaining material-event operations; implement the approved universal `1.25` TargetDSCR and Tier-2 principal reduction from `target-dscr-v1`.
- [ ] Implement the versioned `pricing-v1`/`target-dscr-v1` calculator with permitted tenors, exact Decimal operation order, half-up rate/money rounding, nearest-franc authoritative values, schedule residual reconciliation, and the post-round DSCR safety guard.
- [ ] Approve and encode C-03–C-08 and Appendix A `UW-01`–`UW-30`; promote every `READY-TO-BASELINE` vector and implement every `BASELINED` vector, including boundaries, missing/zero cases, one-franc residuals, integrated DSCR/capacity sequencing, and policy-version transitions.
- [ ] Implement the unified 0.0–5.0 rating, one decimal, exact bands (Strong 4.0–5.0, Stable 3.0–3.9, Weak 2.0–2.9, Distressed 0.0–1.9), issue/live history, and explanations.
- [ ] Automatically rescore and reevaluate eligibility when new verified statements or material events arrive; actively monitor existing Distressed Notes while preventing new listings.
- [ ] Implement normative period Coverage as verified inflow divided by verified outflow plus debt service, with explicit zero/missing denominator and rounding rules.
- [ ] Encode `risk-policy-v1`: approved Coverage-based Health boundaries, `Good/Review/Restricted/Suspended` standing vocabulary, `RB0/RB1/RB2/RB3` Note-risk vocabulary, worst-valid-trigger precedence, and the objective default backstop; complete and sign the trigger/cure/action matrix before activation. Keep launch probability of default `null/NOT_CALIBRATED`, never zero or fabricated.
- [ ] Distinguish rating, DSCR, capacity, Coverage, standing, health, risk band, and probability of default without presenting any of them as a second public business-quality score.
- [ ] Decline DSCR below 1.00, route 1.00–<1.25 to enhanced/manual review, and permit ≥1.25 automation only with all other gates.
- [ ] Retain decline inputs/results and present shortfall plus concrete requalification conditions.
- [ ] Make permanent overrides Superadmin-only, reason-required, policy-limited, and immutable; never permit override of an explicit legal/data prohibition.
- [ ] Expose inputs, outputs, explanations, confidence, verification state, calculation timestamp, and policy version through authorized Resources.
- [ ] Remove client-owned authoritative calculations from Pulse and future role clients.
- [ ] Build performance and concurrency controls for asynchronous parsing and recalculation.

### Deliverables

- Immutable statement/evidence storage and parsing pipeline.
- Versioned underwriting, rating, Coverage, health, standing, and risk-band policies with deterministic calculators/state models; launch probability of default remains explicitly `null/NOT_CALIBRATED` until a separately validated calibration policy is approved.
- Golden calculation corpus with approved expected results.
- Completed Appendix A vector records, machine-readable fixtures, and approval evidence.
- Business evidence/parsing/capacity/rating/requalification pages and mobile screens.
- Shared underwriting and rating Resources for all authorized roles.
- Admin review/override dependency and complete calculation trace.

### Acceptance Criteria

- [ ] Every result can be reconstructed from immutable evidence, normalized inputs, policy version, and rounding rules.
- [ ] No client can submit an authoritative capacity, rating, return, fee, or schedule value.
- [ ] No test path violates capacity, permitted tenor, approved return bounds, rating thresholds, or final-instalment reconciliation.
- [ ] DSCR tier boundaries behave exactly at 0.99, 1.00, 1.25, and values above 1.25.
- [ ] EngineScore and TargetDSCR/Tier-2 capacity vectors cover the approved weights/normalizations, new-Business neutral, arrears/default caps, missing evidence, 6/11/12-month history boundaries, anomalies, material events, exact boundary outcomes, accepted-amount recomputation, and post-round DSCR safety guard without copying the prototype score implicitly.
- [ ] Rating tests prove exact 4.0/3.0/2.0 thresholds, Stable-as-healthy language, automatic rescore/re-eligibility, and active monitoring of an existing Note that becomes Distressed.
- [ ] Coverage golden vectors prove the exact formula, zero/missing handling, and rounding. Health/standing/risk vectors prove every approved boundary/trigger, while PD vectors prove launch `null/NOT_CALIBRATED`, prohibited `0%`, and correct audience omissions across every Resource.
- [ ] UX/content tests prove the non-rating indicators are clearly named and cannot be mistaken for another public quality rating.
- [ ] The same Business has the same word, one-decimal rating, color code, and policy version in every Resource context.
- [ ] Parsing failure, low confidence, correction, resubmission, and decline remain explainable and auditable.
- [ ] The approved golden vectors pass across calculator, Resource, Inertia, and API tests.
- [ ] Every `READY-TO-BASELINE` Appendix A vector receives concrete fixtures, hashes, versions, and approvals; every vector then marked `BASELINED` passes at the domain, persistence, Resource, Inertia, versioned API, and mobile-contract layers; no `BLOCKED` or `QUARANTINED` vector is encoded as production behavior.
- [ ] Statement source documents and bank identifiers are inaccessible to unauthorized Investors.

### Retained verification ideas

Use property/boundary tests, golden vectors, mutation-resistant unit tests, upload/security feature tests, queue retry tests, policy/Resource omission tests, and representative performance tests. Apply named policy/model approvals to active Phase 0, engineering evidence to active Phases 1–2, and applicable CMA/sandbox authorization to active Phase 4. Product approval alone does not activate candidate policies.

---

## Work Package L4 — Auditor accreditation, dispatch, offline fieldwork, and verification seal

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Deliver the complete Audit Partner journey from verified ICPAR accreditation through fair dispatch, offline field evidence, versioned procedures, co-signature, seal, portfolio stewardship, and attributable earnings.

### Retained scope routing

- Route the online Auditor alpha to active Phase 1, full web/PWA lifecycle and offline assurance to active Phase 2, and native-only device/biometric behavior to active Phase 5.
- The MVP web/PWA is authoritative only if the Phase 0 assurance spike proves mandatory camera, location, provenance, interruption, encryption, and synchronization guarantees; otherwise only the approved thin-native exception moves into the MVP.
- ICPAR, mapping/geocoding, messaging, secure storage, and the approved PWA/native capability set remain required dependencies at their mapped active-phase gates.

### Checklist

- [ ] Implement Auditor lifecycle: pending verification, ICPAR checked, agreement signed, approved PWA session or native biometric/device binding where applicable, Active, suspended/yield-frozen, reinstated, and terminated.
- [ ] Validate current licence at accreditation, assignment, sync, co-sign, and seal; automatically suspend on expiry.
- [ ] Implement in-app MSA/ISRS 4400 review, signature, version retention, and renewal on material change.
- [ ] Version the agreed-upon-procedures checklist, training prerequisites, working-paper requirements, and evidence retention.
- [ ] Implement dispatch eligibility using approved coordinate source, 30 km rule, concurrency cap, rotation, licence, academy prerequisites, conflicts, and availability.
- [ ] Define acceptance timeout, decline/no-response reason, redispatch, travel rules, and business/auditor lateness attribution.
- [ ] Implement routine and randomly triggered Flash Audit jobs that are indistinguishable in advance, with a durable 24-hour deadline and urgency progression.
- [ ] Package assigned jobs for encrypted offline use with least data, expiry, policy/checklist version, and sync cursor.
- [ ] Implement deterministic client IDs, local encrypted drafts, durable ordered operation queue, resumable media uploads, hashes, acknowledgements, retry/backoff, and remote-wipe/lost-device response.
- [ ] Enforce camera-only capture and live location through the Phase 0-approved MVP surface; no insecure gallery fallback. Native-specific enforcement remains active Phase 5 unless approved as the thin Auditor exception.
- [ ] Capture tamper-relevant metadata and distinguish permission denial, spoof suspicion, stale location, offline capture, and server validation.
- [ ] Implement structured source/parsed/on-site comparison and mandatory reason for correction/rejection.
- [ ] Implement conflict declaration/decline with retained reason and automatic redispatch.
- [ ] Define canonical seal payload, key custody/rotation/revocation, irreversible application, verification UI, and amendment-chain behavior.
- [ ] Implement stewarded portfolio, upcoming windows, report history, health, breach visibility, and escalation.
- [ ] Define the Auditor earnings/attribution contract: 25% of collected service fees, payable monthly only when the approved SLA is satisfactory, with accrued/paid/frozen views and basis traceability; active Phases 1–2 own the mapped ledger/read-model/reconciliation slices.
- [ ] Build academy catalogue, module versions, assessments/completion, and eligibility consequences.

### Deliverables

- Auditor accreditation/agreement/licence/biometric lifecycle.
- Spatial/capacity/rotation/conflict-aware dispatch engine.
- Field-first approved PWA or narrowly excepted native offline job, evidence, procedure, comparison, co-sign, and seal workflow.
- Auditor web portfolio, earnings, academy, device, and sync-oversight surfaces.
- Seal specification, verification utility, key-management runbook, and evidence-chain model.
- Dispatch fairness, offline sync, licence-expiry, Flash Audit, and earnings test suites.

### Acceptance Criteria

- [ ] No Auditor receives or completes a job before Active status or with an expired licence.
- [ ] Dispatch deterministically enforces radius, cap, rotation, conflicts, and approved tie-breaking; every exclusion is explainable.
- [ ] An assigned job can be completed without connectivity, survive process/device restart, and sync exactly once without lost or duplicated evidence.
- [ ] Mandatory evidence originates from the in-app camera with required location and verifiable capture/upload metadata.
- [ ] Flash Audit selection is demonstrably random/unpredictable and indistinguishable in advance; once triggered, every device shows the same server deadline and applies approved breach handling after 24 hours.
- [ ] A seal is bound to actor, licence, exact content hashes, policy/checklist version, time, and key version and is independently verifiable.
- [ ] Amendments never mutate sealed/published originals.
- [ ] Conflict decline redispatches safely and preserves the reason without disclosing unnecessary sensitive data.
- [ ] Auditor earnings previews match deterministic fixtures for 25% of collected service fees, monthly payability, and satisfactory-SLA freeze/unfreeze; exact ledger reconciliation maps to active Phase 2 and live proof to active Phase 4.

### Retained verification ideas

Use provider contract tests, geo/radius boundaries, deterministic dispatch simulations, offline kill/restart/reorder/duplicate tests, media corruption tests, permission-denial tests, key rotation/revocation tests, and countdown tests. Apply supported-browser/PWA device evidence to active Phase 2, native real-device evidence to active Phase 5, and full live AC-3 publication evidence to active Phase 4.

---

## Work Package L5 — Business origination, co-signature, approval, and primary listing

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Let an eligible Business turn verified capacity into a fully disclosed, co-signed, approved Note listing without exceeding policy or obscuring cost, status, or rejection reasons.

### Dependencies and role surfaces

- Route the online origination/listing slice to active Phase 1 and remaining lifecycle/exception states to active Phase 2.
- Primary Business web/mobile surfaces: dashboard, raise builder, application timeline, Audit Partner coordination, approval, and listing.
- Auditor co-sign and Admin review/control are required dependencies; active Phase 1 owns the Investor deal Resource and primary-market UI slice.

### Checklist

- [ ] Implement Application and Note state machines with guarded transitions and explicit terminal/branch states.
- [ ] Support Note lifecycle: Draft, Submitted, Audit, Approved, Live, Funded, Repaying, Matured, plus Declined, Withdrawn, Expired, Arrears, Default, Recovered, and Written off.
- [ ] Build a raise builder constrained to current verified capacity, permitted tenors, approved size policy, and Business standing.
- [ ] Capture approved use of funds, permitted photos, selected tenor/amount, and all required disclosures.
- [ ] Present exact total Business cost, return, fees, instalments, dates, and full schedule in RWF before submission.
- [ ] Prevent Distressed, expired-KYB, overdue-report, suspended, or otherwise ineligible Businesses from listing.
- [ ] Bind each application to immutable evidence, calculation, rating, policy, consent, and disclosure versions.
- [ ] Require originating Audit Partner review/co-signature and current licence before approval/listing.
- [ ] Implement approval/manual-review/decline with reason codes, free-text evidence, shortfall, and requalification guidance.
- [ ] Implement Business timeline and notifications through audit, approval, Live, funding, expiry, refund, disbursement, and servicing.
- [ ] Define reservation, partial funding, oversubscription, cancellation/withdrawal, failed funding, expiry race, and full-funding behavior.
- [ ] Automatically expire unfunded Notes at 30 days and emit one idempotent fee-free Investor-refund instruction against the active Phase 1 financial-command contract.
- [ ] Produce authorized listing/deal Resources with verified financial disclosure, rating basis, exact economics, schedule, use, photos, and Audit Partner identity/licence.
- [ ] Prevent mutable listing edits; material changes create a new version/re-approval path.

### Deliverables

- Application/Note state machines and transition policy.
- Business raise builder, timeline, approval/decline, and requalification experiences.
- Auditor co-sign and Admin approval dependencies.
- Versioned listing/deal Resource and immutable disclosure snapshot.
- Funding-reservation and 30-day expiry specification with scheduled jobs.
- Lifecycle, authorization, boundary, expiry, and concurrency tests.

### Acceptance Criteria

- [ ] No requested or listed amount exceeds the current approved capacity at submission or Live transition.
- [ ] Only 3/6/9/12-month tenors and approved return bounds are possible; APR never appears.
- [ ] Business sees exact total cost, fees, instalments, and dates before committing.
- [ ] No Note reaches Live without eligible Business/KYB, verified inputs, non-Distressed rating, approved policy, current Auditor co-signature, and required disclosures.
- [ ] Concurrent edits/approvals cannot double-transition, oversubscribe, or list stale calculations.
- [ ] Decline/review decisions are attributable and actionable without revealing restricted internal signals.
- [ ] A 30-day unfunded Note expires automatically and emits the correct fee-free refund instruction exactly once; active Phases 1–2 prove the corresponding ledger/provider return and failure handling.
- [ ] Listing Resources are consistent between Inertia and API and immutable by version after publication.

### Retained verification ideas

Use state-machine transition tables, authorization tests, stale-version/concurrency tests, scheduled-time travel tests, exact-money snapshots, Resource parity tests, and mapped responsive-web/native journey tests. Apply staged-listing evidence to active Phase 1 and remaining states to active Phase 2.

---

## Work Package L6 — Financial core, wallets, reservations, and primary settlement

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Establish the balanced, idempotent financial foundation before any Investor commitment flow can be accepted, including wallet funding, reservation, primary settlement, holdings, expiry refunds, and daily reconciliation.

### Dependencies and role surfaces

- Route the ledger, wallet, reservation, and primary-settlement foundation to active Phase 1, failure/reconciliation completeness to active Phase 2, and live provider certification to active Phase 4.
- The mapped commands and Resources feed the active Phase 1 Investor path, active Phase 2 servicing/reporting, and active Phase 3 secondary settlement.
- Requires approved segregated-account design and bank/MoMo sandbox contracts; provider fakes are acceptable for deterministic CI, not production acceptance.

### Checklist

- [ ] Design exact-RWF balanced double-entry accounts, journals, posting rules, references, holds/reservations, reversals, and ledger-derived balances.
- [ ] Define segregated client-money topology, provider/bank control accounts, suspense, reconciliation identifiers, and maker-checker boundaries.
- [ ] Implement idempotent commands for wallet deposit, withdrawal, primary reservation, commitment settlement, reservation release, Note-expiry refund, and compensating reversal.
- [ ] Require verified Party/tier, registered payout rail, current Note/policy version, available ledger balance, idempotency key, and authorization for every financial command.
- [ ] Build bank/MoMo adapter contracts for request, webhook/polling, timeout, retry, duplicate/reordered callback, reversal, and manual reconciliation.
- [ ] Implement wallet, hold, transaction, ledger-entry, primary-settlement, and Holding Resources without mutable cached balances.
- [ ] Preserve the unconditional ability for an eligible Investor to invest exactly RWF 5,000; separately approve unit and residual-target mechanics.
- [ ] Implement primary reservation, partial-funding, oversubscription, failure, cancellation, expiry-race, full-funding, and stale-version rules.
- [ ] Create a settled Holding only from a posted primary-settlement journal; optimistic client state cannot create ownership.
- [ ] Implement the BRS fixed listing fee on the approved listing transition, with amount/refundability policy still explicit; prepare but do not prematurely charge repayment or secondary fees.
- [ ] Route every mapped origination 30-day-expiry instruction to a fee-free Investor refund exactly once.
- [ ] Implement automated ledger/provider/account reconciliation and a controlled unmatched/suspense workflow from the first money movement.
- [ ] Emit durable financial events/outbox records and propagate committed balance, funding, and Holding changes to affected online clients.
- [ ] Build treasury/reconciliation dependencies and immutable operator evidence for every adjustment/reversal.

### Deliverables

- Ledger/accounting specification and exact-RWF posting engine.
- Wallet, hold, reservation, primary settlement, Holding, refund, and reversal commands.
- Bank/MoMo adapter contracts, deterministic sandboxes/fakes, and webhook replay handling.
- Financial Resource contracts and treasury/reconciliation foundation.
- Primary funding concurrency, idempotency, balance, expiry-refund, and reconciliation suites.

### Acceptance Criteria

- [ ] Every journal balances and every displayed balance is derived from posted ledger entries.
- [ ] Retried, duplicated, reordered, and concurrently submitted commands/callbacks cannot lose or double-apply money.
- [ ] An eligible Investor can deposit and settle exactly RWF 5,000 through the command/API contract; an ineligible Party, unregistered rail, or insufficient balance is rejected server-side.
- [ ] Concurrent final-unit attempts cannot overfund a Note; full-funding transition and primary Holding creation occur exactly once.
- [ ] A Holding exists only after posted settlement and reconciles to Investor cash, Note funding, and ledger entries.
- [ ] A 30-day expiry refund is fee-free, idempotent, provider/ledger reconciled, and cannot race with a successful final settlement.
- [ ] Only the BRS-approved listing fee is chargeable at this phase, on the approved listing transition and with exact pre-action disclosure.
- [ ] Daily reconciliation reports exact matched, unmatched, and suspense totals with controlled, reasoned resolution.
- [ ] Committed wallet/funding/Holding changes reach affected online clients without manual refresh and converge after reconnect.

### Retained verification ideas

Use accounting invariants/property tests, exact-money vectors, provider contract/replay tests, concurrency and idempotency tests, time-travel expiry races, reconciliation fixtures, authorization matrices, and Resource parity tests. Apply Finance posting-rule approval and the green primary-settlement foundation to active Phase 1.

---

## Work Package L7 — Investor onboarding, marketplace discovery, and primary investment

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Give an eligible Investor a clear, risk-honest path from onboarding through evidence review and an exact, policy-compliant primary commitment.

### Dependencies and role surfaces

- Route the primary Investor journey to active Phase 1, portfolio/withdrawal/audit completeness to active Phase 2, and native Investor delivery to active Phase 5.
- Covers Investor web/mobile marketplace, deal details, evidence, watchlist, unit selection, confirmation, portfolio entry, education, and settings.
- Uses the disclosure contract approved for C-09, C-10, and C-18.

### Checklist

- [ ] Implement Investor category/tier, exposure/concentration limits, consent, risk acknowledgement, and wallet-readiness gates.
- [ ] Build live deal deck with curated ordering and governed sector/tenor/rating/yield filters.
- [ ] Clearly distinguish no results, not yet eligible, fully funded, expired, paused, and unavailable states.
- [ ] Build deal details with verified financial aggregates, rating basis, funding progress, exact economics, schedule, use, photos, originating Auditor identity/current licence, and material risks.
- [ ] Build published evidence viewer for monthly parsed values, Business/Auditor notes and photos, geo verification state, seal, and amendments.
- [ ] Enforce unavoidable first-investment and every-deal capital-loss/no-guarantee disclosure.
- [ ] Implement unit selector with approved unit/residual rule, always permitting an eligible RWF 5,000 investment, and show exact pre-commit RWF principal, fixed return, fees, payout schedule, and total proceeds.
- [ ] Enforce KYC/tier/category/concentration, Note status/capacity, wallet funds, feature halt, and stale-version checks server-side.
- [ ] Implement idempotent reservation/commitment and clear pending/success/failure/replay states.
- [ ] Implement watchlist, followed Businesses, new-raise alerts, and consented preferences.
- [ ] Seed portfolio/holding entry only from settled ledger facts, never optimistic client state.
- [ ] Build education/FAQ for rating, fixed return, fees, capital-loss risk, illiquidity, and secondary-market limitations.
- [ ] Ensure every displayed rating uses word, one-decimal number, color, issue/live distinction, and timestamp.
- [ ] Add accessibility for dense financial tables through responsive card/list alternatives without hiding material terms.

### Deliverables

- Investor onboarding/category/limit/risk-gate flow.
- Responsive marketplace, deal, evidence, watchlist, commitment, education, and initial portfolio surfaces.
- Primary investment command, reservation semantics, and exact confirmation receipt.
- Investor/Deal/Evidence/Holding Resource contracts.
- Disclosure, suitability/limit, concurrency, and commitment test suites.

### Acceptance Criteria

- [ ] An unverified, over-limit, underfunded, frozen, or ineligible Investor cannot commit through any transport.
- [ ] Every commitment shows and records exact principal, total fixed return, fees, dates, and capital-loss disclosure before authorization.
- [ ] An eligible Investor can select and complete an investment of exactly RWF 5,000 regardless of any larger default unit presentation.
- [ ] Two concurrent final-unit attempts cannot overfund the Note or debit twice.
- [ ] Investor disclosure omits raw statements/bank identifiers while exposing every legally approved verified aggregate and evidence state.
- [ ] No copy implies safety, guarantee, capital protection, Rozine lending, or assured liquidity.
- [ ] Settled commitment produces one ledger-backed holding/receipt; live funding progress reaches affected online clients without manual refresh and reconciles after reconnect.
- [ ] Web and mobile receive the same authorized Deal, Evidence, and Holding facts through shared Resources.

### Retained verification ideas

Use limit/disclosure matrices, exact-money tests, idempotency/concurrency tests, Resource omission tests, accessibility checks, and mapped responsive-web/native primary-investment journeys with provider fakes/sandboxes. Apply product/legal deal-disclosure approval to active Phase 1 and live-provider evidence to active Phase 4.

---

## Work Package L8 — Servicing, monthly reporting, portfolio, fees, and payouts

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Extend the mapped active Phase 1 financial core to complete the recurring Business–Auditor–Investor evidence and money cycle from full funding through maturity.

### Dependencies and role surfaces

- Route one repayment/payout slice to active Phase 1, the complete recurring lifecycle to active Phase 2, and live lifecycle proof to active Phase 4.
- Covers Investor wallet/portfolio/holding, Business disbursement/repayment/reporting, Auditor co-sign/earnings, and treasury/reconciliation dependencies.
- Requires approved bank and MoMo custody/settlement contracts, exact fee policy, schedules, and accounting design.

### Checklist

- [ ] Extend the active Phase 1 posting engine with idempotent disbursement, repayment, service-fee, Investor-fee, payout, early-payoff, recovery, and compensating-reversal rules; active Phase 3 owns secondary settlement.
- [ ] Operate bank/MoMo collection/disbursement/withdrawal callbacks against the segregated accounts, suspense, and reconciliation controls established in active Phase 1.
- [ ] Enforce the approved fee set and transition: fixed listing fee on listing, 2% Business service fee per repayment, 1% Investor repayment fee per payout, and no 3% seller fee except an atomic active Phase 3 trade.
- [ ] Route 25% of collected service fees to the attributable Audit Partner, payable monthly only when the approved SLA is satisfactory; freeze/unfreeze by attributable policy.
- [ ] Present pre-action RWF fee/economic disclosure and post-action receipt for every money command.
- [ ] Gate disbursement on full funding and all conditions; restrict withdrawal to registered verified rails.
- [ ] Generate immutable known monthly principal-plus-return schedules with exact dates, early-payoff full-return behavior, and final residual reconciliation.
- [ ] Define due-day/holiday/grace/partial/late allocation and collections policy before enabling production collection.
- [ ] Implement Business repayment initiation/status/receipt and Investor record-date pro-rata payout.
- [ ] Implement monthly report lifecycle: Drafting, Submitted, Pending audit, Co-signed, Published, with Disputed/SLA breach branches and linked amendments.
- [ ] Enforce the 1st–7th inclusive monthly window, requiring both Business submission and Auditor co-signature inside it, plus statement/evidence, camera-only geo evidence, premises cross-check, notes of at most 100 characters, and at most five photos per party.
- [ ] Require originating eligible Audit Partner co-signature and seal before publication.
- [ ] Automatically close unattended windows, identify the responsible partner, freeze that partner's yield, flag the Note, and notify every affected party.
- [ ] Recompute rating, Coverage, health, standing, and risk band on published reports/material events while preserving issue/live ratings and the single-quality-measure presentation rule. At launch, retain probability of default as `null/NOT_CALIBRATED`; only a later approved and validated model may produce a numeric PD.
- [ ] Build Business schedule/report/payment/wallet pages plus education for rating, cash flow, audit preparation, and fees.
- [ ] Build Investor wallet/portfolio/holding/report/payout pages with principal, projected return, payouts, diversification, rating mix, and idle balance totals derived from ledger facts.
- [ ] Build Auditor portfolio/earnings pages with collected-fee attribution, monthly payability, SLA, and frozen/accrued/paid states.
- [ ] Implement notification inbox/preferences and delivery records for windows, visits, reports, funding, disbursement, dues, payouts, arrears, disputes, and policy notices.
- [ ] Perform automated daily ledger/provider/account reconciliation with alerts, reasoned break resolution, and immutable evidence.

### Deliverables

- Servicing, fee, payout, and reconciliation extensions to the mapped active Phase 1 financial core.
- Production bank/MoMo collection/disbursement/withdrawal callbacks and provider evidence.
- Disbursement, schedule, repayment, payout, fee, and receipt workflows.
- Monthly report/evidence/co-sign/publication/amendment lifecycle.
- Complete Business servicing, Investor portfolio, and Auditor stewardship/earnings surfaces.
- Treasury and reconciliation control pages plus incident runbooks.

### Acceptance Criteria

- [ ] Every posted journal balances; an automated detector prevents or alerts on any imbalance.
- [ ] Retried/reordered commands and provider callbacks cannot lose or double-apply money.
- [ ] Wallet and portfolio balances equal ledger facts and are never mutable cached totals.
- [ ] Listing, Business service, and Investor repayment fees occur exactly once at their approved transitions; the seller fee is impossible outside a settled active Phase 3 trade.
- [ ] Full-funding/condition gates prevent premature disbursement; withdrawal reaches only a verified registered rail.
- [ ] Schedules, early payoff, record-date allocation, fees, final residuals, and payouts reconcile to the franc.
- [ ] No monthly report publishes unless the Business submitted and the eligible originating Auditor co-signed within the 1st–7th inclusive window with the required immutable evidence and valid seal.
- [ ] Missed report windows close automatically, freeze the responsible partner's yield, flag the Note, and notify all affected parties without an operator click.
- [ ] Auditor earnings reconcile exactly to 25% of collected attributable service fees, become payable monthly only under satisfactory SLA, and freeze/unfreeze by the approved attributable rule.
- [ ] Investor portfolio totals for principal, projected return, payouts, diversification, rating mix, and idle balance reconcile to Resource/ledger facts; Business education covers rating, cash flow, audit preparation, and fees.
- [ ] Daily reconciliation produces exact matched/unmatched totals, alert evidence, and controlled break resolution.
- [ ] Business, Investor, and Auditor see the same committed transition within the defined online consistency/SLA budget without manual refresh and converge after reconnect.

### Retained verification ideas

Use accounting invariant/property tests, concurrency/idempotency tests, provider contract/replay tests, schedule golden vectors, time-travel window tests, reconciliation fixtures, failure injection, and mapped responsive-web/native servicing journeys. Apply Finance posting approval and sandbox reconciliation to active Phase 2 and witnessed live AC-7 evidence to active Phase 4.

---

## Work Package L9 — Secondary market, arrears, disputes, default, and recovery

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Provide controlled liquidity and transparent exception handling without allowing ineligible trades, price manipulation, hidden distress, mutable evidence, or unbalanced settlement.

### Dependencies and role surfaces

- Route distress/dispute/recovery to active Phase 2 and Investor-to-Investor secondary settlement to active Phase 3, consuming the active Phase 1 ledger/Holding foundation.
- Covers Investor secondary order/listing/purchase, Business/Investor disputes, arrears/default visibility, and Admin recovery controls.
- Requires approved order, pricing, record-date, settlement-failure, collections, default, write-off, and recovery policy.

### Checklist

- [ ] Define the Investor-chosen ask, secondary order lifecycle, quantities/partial fills, price representation, tick/limit policy, expiry, cancellation, reservation, fees, and settlement failure.
- [ ] Permit sale only from settled owned holdings for eligible, current, Repaying Notes.
- [ ] Block trading for Arrears, Default, Disputed, paused, stale-evidence, frozen-party, or globally/per-Note halted states.
- [ ] Show required Note, report, rating, schedule, holding, price-versus-par, fee, risk, and liquidity evidence before purchase.
- [ ] Implement atomic cash/holding transfer and seller fee within one idempotent settlement boundary.
- [ ] Ensure Rozine cannot submit proprietary orders, act as principal/market maker, or set prices.
- [ ] Implement global/per-Note halt, reason, approval, notification, and resumptions with audit evidence.
- [ ] Define arrears thresholds, grace, reminders, materiality, partial-payment allocation, cure, collections, recovery authority, write-off, and post-write-off recovery consistently with D-11C's fixed 90-DPD or dual-approved unlikely-to-pay default backstop.
- [ ] Propagate distress/health/rating/report state consistently across Business, Investor, Auditor, and Admin views.
- [ ] Implement evidence-backed dispute creation, access controls, response/evidence, SLA, escalation, decision, remedy, and appeal if approved.
- [ ] Keep published reports/seals immutable; corrections use linked amendments and preserve what each Investor saw at commitment/trade time.
- [ ] Implement compensating financial remedies, never direct ledger editing.
- [ ] Build complete audit/supervisor reconstruction of order, settlement, distress, dispute, and recovery actions.

### Deliverables

- Secondary order/eligibility/reservation/atomic-settlement engine.
- Investor secondary discovery/order/management/confirmation/settlement pages and screens.
- Arrears/default/recovery state machine and cross-role experiences.
- Dispute, evidence, amendment, resolution, and remedy workflow.
- Halt controls, reconciliation extensions, and operational runbooks.
- Trade, distress, dispute, recovery, and immutable-history test suites.

### Acceptance Criteria

- [ ] An ineligible holding or Note cannot be listed, purchased, or settled through any route or race condition.
- [ ] Settlement is atomic: cash and holding ownership both change once or neither changes.
- [ ] Seller fee is the approved policy value and is disclosed/reconciled exactly.
- [ ] Rozine has no principal inventory, proprietary order, market-making, or price-setting path.
- [ ] Global/per-Note halts stop new and pending actions according to approved semantics and are fully auditable.
- [ ] Arrears/default/recovery states and evidence are consistent across all authorized role Resources.
- [ ] A dispute cannot alter a published/sealed original or posted ledger entry; amendment/compensation preserves history.
- [ ] Supervisor reconstruction identifies every actor, input, policy, transition, message, and financial effect.

### Retained verification ideas

Use property-based ownership tests, concurrent order/fill/cancel simulations, atomicity/failure injection, halt tests, record-date tests, amendment immutability checks, remedy/reversal reconciliation, and mapped sell/buy/distress/dispute journeys. Apply distress evidence to active Phase 2 and secondary settlement evidence to active Phase 3.

---

## Work Package L10 — Admin, compliance, policy, treasury, and supervisor control plane

**Archive status:** Non-governing requirement bank. Use active Phases 0–8 and the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Make every essential marketplace action observable, governable, policy-versioned, reasoned, and reconstructible without granting operators hidden or unsafe powers.

### Dependencies and role surfaces

- Distribute required controls through active Phases 1–4; route only separately approved advanced operations to active Phase 7.
- Covers FR-400–FR-418 and control dependencies for every role journey.
- Requires separate Compliance and Legal decisions, regulatory-report contracts, retention policy, and operational ownership.

#### Authoritative staff capability matrix

| Capability | Analyst | Approver | Treasury | Compliance | Superadmin |
|---|---:|---:|---:|---:|---:|
| View all directories/records | Yes | Yes | Yes | Yes | Yes |
| Approve/decline application | No | Yes | No | No | Yes |
| Override capacity | No | No | No | No | Yes |
| Verify/accredit ICPAR partner | No | Yes | No | Yes | Yes |
| Freeze/unfreeze a party | No | No | No | Yes | Yes |
| Freeze/release Audit Partner yield | No | Yes | Yes | Yes | Yes |
| Manual ledger entry/reversal | No | No | Yes | No | Yes |
| Halt/resume secondary market | No | No | Yes | Yes | Yes |
| Amend underwriting/fee policy | No | No | No | No | Yes |
| Feature flags/maintenance | No | No | No | No | Yes |
| Manage staff/roles | No | No | No | No | Yes |
| Read-only act-as | Yes | Yes | No | Yes | Yes |
| Broadcast | No | Yes | No | Yes | Yes |

### Checklist

- [ ] Build command KPIs and directories for Businesses, Investors, Audit Partners, applications, Notes, reports, orders, wallets, and cases.
- [ ] Build accreditation, dispatch, application, Note, report, distress, secondary, treasury, reconciliation, dispute, and notification queues.
- [ ] Implement the FR-409 Distressed Note board with outstanding amount, days late, risk band, probability-of-default state, why-flagged basis, recommended action, and authorized one-click execution with confirmation/reason/approval controls. At launch show `Not calibrated — insufficient outcome data`, never `0%`; any future numeric PD is restricted to explicitly authorized Approver, Compliance, and Superadmin risk functions.
- [ ] Implement the authoritative Analyst/Approver/Treasury/Compliance/Superadmin capability matrix for directories, approvals, capacity override, ICPAR accreditation, party/yield freeze, ledger reversal, market halt, policy, feature control, staff, act-as, and broadcasts.
- [ ] Require a non-empty reason for every administrative action; require explicit confirmation and policy-driven dual approval for materially destructive or financial actions.
- [ ] Make audit and ledger entries undeletable by every role, including Superadmin; corrections use append-only evidence and compensating entries.
- [ ] Implement least privilege, separation of duties, maker-checker approvals, and periodic access review.
- [ ] Implement immutable audit trail and read-only, reasoned act-as in the mapped active Phase 1–2 control slices.
- [ ] Implement versioned policy-as-data for permitted configurable values, with draft/review/approve/effective/retire lifecycle and impact preview.
- [ ] Prevent policy changes from silently rewriting historical calculations, disclosures, schedules, or eligibility.
- [ ] Implement role/app/environment feature flags, kill switches, maintenance, minimum mobile version, and forced update with audit history.
- [ ] Implement KYC/KYB refresh, transaction monitoring, sanctions/PEP review, AML case management, escalation, filing evidence, and account restrictions.
- [ ] Implement investor-category/exposure limits and exception governance.
- [ ] Implement daily segregation/reconciliation dashboards and controlled break/remedy flow.
- [ ] Implement complaint/dispute oversight, statutory response timing, and evidence production.
- [ ] Define data classification, lawful basis, consent, retention, legal hold, subject access/correction/deletion/anonymization, and export.
- [ ] Implement ISRS 4400 working-paper retention and access rules.
- [ ] Implement supervisor read-only seat, required reports, field-level minimization, exports, submission/acknowledgement, and complete reconstruction.
- [ ] Implement targeted messaging with template/version, audience, approval, channel, opt-out rules, delivery status, and legal-notice preservation.
- [ ] Implement sandbox caps that cannot deploy to production without formal approval.
- [ ] Add anomaly/fraud/tamper signals, security events, incident linkage, structured logs, metrics, alerts, and operational SLO dashboards.
- [ ] Define and instrument BO-1–BO-8 measures: capital/funded businesses/time-to-funding; registrations/first ticket/reinvestment; cost/report/SLA/parser accuracy; over-capacity/PAR; secondary volume/time-to-fill/spread; sandbox findings/licence; revenue composition/zero default-derived revenue; and Auditor accreditation/retention/yield share/discipline.

### Deliverables

- Complete Admin/compliance/treasury/supervisor control plane.
- Staff role/separation matrix and access-review workflow.
- Versioned policy and feature-control services.
- AML/sanctions/PEP, complaints, privacy-rights, retention, and regulatory-reporting workflows.
- Operational dashboards, alert/runbook set, and immutable evidence exports.
- BO-1–BO-8 metric catalog with definitions, owners, source events, privacy rules, targets, and dashboards.
- Control-plane authorization, audit, policy-history, and reconstruction tests.

### Acceptance Criteria

- [ ] Every FR-400–FR-418 capability has a role-gated page/API, action owner, a mandatory non-empty reason for every administrative action, and immutable audit evidence.
- [ ] Automated tests reproduce the authoritative five-role capability matrix and deny every unlisted action.
- [ ] No staff role can both initiate and approve a protected maker-checker action where separation is required.
- [ ] Protected actions require explicit confirmation and policy-driven dual approval; no actor, including Superadmin, can delete an audit or ledger entry.
- [ ] Historical records reproduce with their original policy/disclosure/calculation versions after later policy changes.
- [ ] Supervisor access is read-only, minimal, current, and sufficient to reconstruct decisions without exposing prohibited data.
- [ ] AML/KYC/KYB restrictions gate transactions consistently and preserve case evidence.
- [ ] Feature flags, halts, maintenance, and forced updates propagate to web/mobile while remaining auditable and reversible.
- [ ] Retention, legal hold, and subject-rights tests preserve statutory/financial/audit records while minimizing eligible PII.
- [ ] AC-4 and AC-8 can be demonstrated from one end-to-end Note without database editing.
- [ ] BO-1–BO-8 dashboards calculate their approved measures from durable source events, identify owner/target/freshness, and prove zero over-capacity originations and zero default-derived revenue.

### Retained verification ideas

Use staff-policy matrices, maker-checker tests, historical-policy replay, audit-log tamper checks, supervisor reconstruction exercises, compliance-case fixtures, privacy-right workflows, feature-control drills, and security/operations tabletop exercises. Apply each result at the active Phase 1–4 gate that owns the corresponding control.

---

## Work Package L11 — Pulse remediation, truthful public demand signals, and conversion

**Archive status:** Non-governing requirement bank. Pulse executes in active Phase 6; use the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Convert the existing Pulse implementation from a useful prototype into a truthful, responsive, non-binding public entry point that uses the approved production engine and hands qualified users into the authenticated role journeys.

### Retained scope routing

- Execute this bank only through active Phase 6 after the Phase 3 MVP RC; consume the active Phase 1 underwriting/identity core and active Phase 2 lifecycle contracts.
- Native deep-link behavior, if any, consumes the separately approved Phase 5 client contract and does not move Pulse into the MVP.
- Pulse covers public responsive web and authenticated handoff only; no money movement or instrument issuance is allowed.

### Checklist

- [ ] Replace prototype/client formula behavior with the active Phase 1 production-engine action and shared Resource once its policy is activated.
- [ ] Implement the BRS-required statement upload path. One original bank or MoMo PDF may produce an indicative Pulse result only when that single document covers the required consecutive history; it never proves production completeness across every declared active rail. Audited-cash-only or POS-only evidence registers the Business for full/manual review without an instant figure. A typed preview remains prohibited unless D-19 separately approves it.
- [ ] Implement the Investor pledge flow with intended RWF pledge amount and server-calculated `pulse-projection-v1`: illustrative return range `P × 10.0%`–`P × 15.0%` and gross repayment `P + return`, with each endpoint rounded half-up to the nearest franc. No tenor/rate is assumed and no money, contract, or obligation exists.
- [ ] Render the approved Business result branches from unrounded authoritative DSCR: `<1.00` = `Not currently eligible` with no amount; `1.00–<1.25` = `Indicative capacity under review` plus `MANUAL REVIEW REQUIRED`; `≥1.25` = `Indicatively pre-qualified`. Never allow displayed rounding to change the branch.
- [ ] Capture name, exactly one contact method, province/district, consent, and a persistent transactionally unique sequence.
- [ ] Show prominent non-binding, verification-required, no-funds, no-instrument, and no-obligation disclosure at input and result. The Investor illustration must also state that actual terms depend on the selected Note, a 1% fee applies to each repayment payout, capital/return are not guaranteed, actual receipts may be lower, and the range is neither APR nor investment advice.
- [ ] Replace random counters with server-backed privacy-safe aggregates from real activity.
- [ ] In production, replace hard-coded businesses with a rotating feed of consented, genuinely pre-qualified Businesses; fixtures are permitted only in clearly isolated non-production environments.
- [ ] Reconcile return, rounding, DSCR, note-size, revenue-cap, and 35%/50% claims with approved policy.
- [ ] Remove TIN, “safe,” guarantee, “bank-grade,” and unproven encryption language from all public content.
- [ ] Build shareable Investor/Business passes with consent, minimum PII, signed unguessable URL or generated image, expiry, revocation, and abuse controls.
- [ ] Make the standalone desktop concepts truly responsive; prevent horizontal overflow and preserve accessible touch targets on mobile web.
- [ ] Implement conversion from Pulse registration/pass to Party onboarding without duplicate identity or re-entering permitted data.
- [ ] Track privacy-safe funnel events and distinguish demo, sandbox, UAT, and production data.
- [ ] Add rate limiting, bot/abuse protection, upload controls, and public-error privacy.
- [ ] Retire or visibly mark the conflicting standalone prototype artifacts so teams do not copy them into production.

### Deliverables

- Remediated responsive Pulse page and versioned public endpoints.
- Server-backed statement/pre-qualification flow using the production calculation Resource.
- Persistent sequence, real aggregate counters, governed sample-deal feed, and shareable passes.
- Business/Investor onboarding handoff and funnel measurement.
- Updated public content/disclosures and prototype-retirement note.
- Pulse calculation-parity, truthfulness, privacy, abuse, and responsive tests.

### Acceptance Criteria

- [ ] Pulse moves no funds, issues no instrument, creates no obligation, and always displays the approved non-binding disclosure.
- [ ] The same approved inputs and policy version produce the same result in Pulse and the authenticated production engine.
- [ ] No authoritative formula runs only in browser/mobile code.
- [ ] Public counters equal real server registration/activity facts in every environment presented as live; no random, seeded, demo, or sample counter can appear as public activity.
- [ ] Investor pledge stores the intended RWF amount, both rate bounds, all four rounded endpoints, calculator version, and `pulse-projection-v1`; RWF `500,000` returns RWF `50,000–75,000` and gross RWF `550,000–575,000`, while RWF `5,005` returns RWF `501–751` and gross RWF `5,506–5,756`. The required disclosure is present and the prototype's fixed `13%` output is absent.
- [ ] Pulse branch tests at raw DSCR `0.999`, `1.000`, `1.249`, and `1.250` produce ineligible/manual/manual/indicatively-pre-qualified respectively; `1.249` stays manual even if a display formatter shows `1.25`.
- [ ] Sequences are persistent, unique, non-random identifiers with a privacy-safe public form.
- [ ] Shared passes reveal only approved fields, cannot be enumerated, and can expire/revoke.
- [ ] Conversion does not duplicate a Party and preserves consent/provenance.
- [ ] Production sample Businesses are consented, genuinely pre-qualified, and rotate by the approved policy; production cannot fall back to fictional fixtures.
- [ ] Pulse passes mobile/desktop accessibility, overflow, performance, and prohibited-claim checks.

### Verification and exit gate

Use golden calculation parity, aggregate reconciliation, deterministic-sequence concurrency, pass enumeration/expiry tests, bot/rate-limit tests, responsive Playwright journeys, and content scans. Active Phase 6—not this archive block—closes witnessed AC-11 evidence.

---

## Work Package L12 — Cross-platform hardening, sandbox pilot, and governed release

**Archive status:** Non-governing requirement bank. Hardening maps across active Phases 1–3, live proof to Phase 4, and native/store assurance to Phase 5; use the Section 10 crosswalk for status, estimates, sequencing, ownership, and release gates.

### Goal

Retain the assurance checklist for remapping: active Phases 1–3 prove the responsive-web MVP release candidate, Phase 4 proves regulated/live operation, Phase 5 proves native/store clients, and Phase 6 proves Pulse.

### Retained scope routing

- Do not wait for every post-MVP surface to close the Phase 3 MVP RC; apply each item only to the active surface and release gate identified in Section 10.
- Phase 4 owns sandbox/legal/real-participant and production lifecycle evidence; Phase 5 owns native device/store evidence; Phase 6 owns public Pulse evidence.
- No sandbox substitute closes a criterion that explicitly requires approved live providers or production rails.

### Checklist

- [ ] Agree web browser/OS, Android/iOS device, mobile OS, network, and app-version support matrices.
- [ ] Meet responsive performance budgets on mid-range Android and intermittent networks, including asynchronous parsing and media upload.
- [ ] Complete Auditor offline soak, conflict, process-kill, reboot, low-storage, clock-skew, lost-device, and reconnect tests.
- [ ] Test queue retries, webhook replay/reordering, provider outage, bank/MoMo reversal, partial outage, and recovery from backups.
- [ ] Define and meet availability, maintenance, RTO, RPO, backup cadence/retention, geographic separation, and restore-test objectives.
- [ ] Complete WCAG 2.2 AA web review and equivalent native accessibility review, including 44px+ touch targets where applicable, text scaling, screen readers, focus, contrast, and reduced motion.
- [ ] Validate the Phase 1 string catalogs and hard-coded-string lint across the complete product; complete the approved launch languages and pseudo-localization for Kinyarwanda, English, and French.
- [ ] Perform data-residency, privacy, retention, consent, secure-storage, key/secrets, least-privilege, and incident-response reviews.
- [ ] Run threat modeling, dependency/static/dynamic scans, penetration testing, and independent security review; close all high-severity findings.
- [ ] Run financial reconciliation, capacity, schedule, fee, rating, seal, evidence, policy-version, and audit reconstruction certification suites.
- [ ] Prepare app signing, store metadata/privacy declarations, adaptive icons/splash, deep links, push credentials, minimum-version, forced-update, and rollback processes.
- [ ] Prepare operations: dashboards, alerts, on-call, runbooks, reconciliation, incident/complaint handling, provider escalation, status communication, and support training.
- [ ] Load-test thousands of concurrent monthly audits and defined marketplace traffic without proportional manual operations growth.
- [ ] Execute an internal no-real-participant/no-money rehearsal of the candidate caps, consent, support, reconciliation, daily-review, and stop procedures using isolated acceptance fixtures; record it as readiness evidence, not regulated-sandbox acceptance.
- [ ] After CMA/legal authorization and provider/participant readiness, execute the regulated sandbox with approved caps, participants, data, consent, support, reconciliation, daily review, and stop criteria; its waiting and observation time is additional to the active-engineering estimate.
- [ ] Execute AC-1–AC-12 as witnessed end-to-end acceptance journeys and archive evidence.
- [ ] Promote only through checked `feat/* -> dev -> uat -> main` pull requests; verify staging before production and perform post-deploy health/reconciliation checks.

### Deliverables

- Web/mobile compatibility, accessibility, localization, and performance certification.
- Security threat model, penetration/independent review reports, remediation evidence, and incident plan.
- Backup/restore, resilience, provider-failure, reconciliation, and load-test evidence.
- Signed release candidates, store/release assets, deployment/rollback runbooks, and operator training.
- Sandbox pilot plan, caps, participant/support plan, success/stop criteria, and daily evidence pack.
- Separate sandbox dossier and AC-1–AC-12 production acceptance dossier; sandbox substitutes cannot satisfy a criterion that explicitly requires production rails or live providers.
- Production go/no-go record.

### Acceptance Criteria

- [ ] AC-1: a Business completes onboarding, evidence, application, audit, listing, funding, disbursement, reporting, repayment, and maturity on production rails.
- [ ] AC-2: an Investor completes KYC/wallet, invests exactly RWF 5,000, receives and inspects a monthly verified report, receives payout, completes an eligible secondary sale, and withdraws on production rails.
- [ ] AC-3: an Audit Partner completes live ICPAR validation, terms, dispatch, full offline field capture, co-signature, publication, and seal.
- [ ] AC-4: every required control-plane action is role-gated, observable, commandable where authorized, reasoned, and logged.
- [ ] AC-5: no path violates capacity, tenor, or approved return bounds; only the explicitly permitted logged Superadmin override exists.
- [ ] AC-6: unattended report-window closure automatically freezes the responsible partner's yield, flags the Note, and notifies every affected party.
- [ ] AC-7: daily ledger, provider, and bank reconciliation is exact and all breaks are controlled.
- [ ] AC-8: a supervisor can inspect live state and reconstruct every decision and state change.
- [ ] AC-9: no tax-system reference or tax identifier exists in application, data, logs, analytics, fixtures, exports, or interfaces.
- [ ] AC-10: the same word/one-decimal/color rating appears everywhere for the same versioned fact.
- [ ] AC-11: Pulse moves no funds, issues no instrument, public counters reflect real registrations, and production samples are consented rotating pre-qualified Businesses.
- [ ] AC-12: penetration and independent security review leave no unresolved high-severity finding.
- [ ] Restore, rollback, provider-outage, forced-update, maintenance, and incident exercises meet approved objectives.
- [ ] Product, Finance/Risk, internal Audit Operations, applicable external ICPAR/Audit Partner, Compliance, Legal, Security, Engineering, Design, Support, and Operations sign the go/no-go record.

### Retained verification ideas

Run the applicable CI, contract, browser/PWA or native, accessibility, performance/load/soak, disaster-recovery, financial-reconciliation, security, and witnessed-acceptance suites at the active Phase 3–6 gate that owns the surface. `main` promotion is never a substitute for UAT or live evidence.

---

## 10. Requirement-to-phase traceability

This group map is a navigation index, not acceptance evidence. The [MVP per-ID crosswalk](phase-0/mvp-crosswalk.md) and [deferred-scope register](phase-0/deferred-scope-register.md) now provide the MVP and post-MVP scope mappings to checklist destinations, governing source/conflicts, slices, required automated/witnessed evidence, owners, gates and statuses. They do not claim to complete the separate every-BRS-ID evidence register or to prove implementation acceptance.

| Requirement group | Primary phase(s) | Supporting phase(s) |
|---|---|---|
| MVP source authority, C-01–C-34, deferred scope | 0 | Every phase |
| `MVP-SPINE-AC-01..12` | 1–3 | 0, 4 |
| `MVP-BUSINESS-AC-01..08` and 9 screens/states | 1–2 | 3–4 |
| `MVP-AUDITOR-AC-01..08` and 8 screens/states | 1–2 | 3–5 |
| `MVP-INVESTOR-AC-01..06` and `AC-09..10`, plus 11 screens/states | 1–3 | 4, 6–7 |
| `MVP-INVESTOR-AC-07..08` Plus bands/mandates | 7 only after signed D-61 deferral; otherwise rebaseline into 3 after policy approval | 0, 3 |
| `MVP-ADMIN-AC-01..08` and 10 screens/states | 1–3 | 4, 7–8 |
| MVP launcher/demo/performance | 1, 3 | 0, 2, 5 |
| BO-1–BO-8 | 1–4, 8 | 6–7 |
| BR-1–BR-5 actors/permissions | 1–3 | 0, 4–7 |
| BR-10–BR-37 underwriting/pricing/rating | 1 | 0, 2, 6–8 |
| BR-40–BR-56 reporting/Auditor | 1–2 | 3–5, 8 |
| BR-60–BR-67 funds/fees/ledger | 1–4 | 6–8 |
| BR-70–BR-75 secondary market | 3–4 | 7–8 |
| BR-80–BR-84 and FR-500–FR-505 Pulse | 6 | 0–1, 3–4 |
| FR-100–FR-113 Investor | 1–4 | 5–8 |
| FR-200–FR-213 Business | 1–4 | 5–8 |
| FR-300–FR-312 Auditor | 1–4 | 5, 7–8 |
| FR-400–FR-418 Admin/supervisor | 1–4 | 7–8 |
| FR-600–FR-607 shared core | 1–4 | 5–8 |
| FR-700–FR-702 calculations | 1–4 | 6–8 |
| NFR-1–NFR-14 | Every implemented phase | Final MVP RC gate in 3; live proof in 4 |
| IR-1–IR-8 | 1–4 | 5–8 |
| CR-1–CR-13 | 1–4 | 5–8 |
| AC-1–AC-10, AC-12 | Evidence accumulates in 1–3; live criteria close in 4 | 5, 7–8 as applicable |
| AC-11 | 6 | 0–4 retain an explicit open/deferred status |

### Former-plan work-package crosswalk

| Former work package | New execution destination |
|---|---|
| L0 | Phase 0 |
| L1 | Phase 0 brand decisions; Phase 1 shared MVP foundation; Phase 5 native clients |
| L2 | Phase 1 identity happy path; Phase 2 exception completeness; Phase 5 native token/device behavior |
| L3 | Phase 1 underwriting; Phase 2 recurring recalculation; Phase 6 Pulse consumption |
| L4 | Phase 1 online Auditor path; Phase 2 offline/monthly/earnings; Phase 5 native assurance if approved |
| L5 | Phase 1 origination/listing; Phase 2 remaining states |
| L6 | Phase 1 ledger/primary settlement; Phase 2 failure/reconciliation; Phase 4 live certification |
| L7 | Phase 1 browse/buy; Phase 2 portfolio/withdrawal/audit; Phase 5 native Investor |
| L8 | Phase 1 one repayment; Phase 2 full servicing/reporting/payout; Phase 4 live lifecycle |
| L9 | Phase 2 distress/dispute/recovery; Phase 3 peer-to-peer secondary |
| L10 | Controls distributed through Phases 1–4; approved advanced operations in Phase 7 |
| L11 | Phase 6 after the MVP RC |
| L12 | Progressive evidence in Phases 1–3; governed live proof in Phase 4; native-store work in Phase 5 |

## 11. Cross-phase quality and evidence gates

Every implementation pull request must include evidence proportionate to its risk:

- A requirement/decision link and a statement of MVP web/PWA, post-MVP native where applicable, data, policy, compliance, brand, and operational impact.
- A migration/rollback or explicit “no data change” statement.
- Pest unit/feature tests using factories and deterministic fakes; contract tests for every changed Resource.
- Authorization-denial and sensitive-field-omission tests, not only happy paths.
- Exact-money, idempotency, concurrency, transition, and event/outbox tests for financial/workflow changes.
- The exact commands and evidence required by Sections 11.1–11.4: full 100% first-party PHP lines; D-66/D-67 web/applicable-native metric, risk-tier branch, and changed-branch coverage; architecture tests; Pest-aware PHPStan/Larastan; and correctly scoped impacted-test acceleration.
- Formatting, linting, frontend type checks, frontend production build, and applicable browser/native test gates.
- Responsive screenshots or browser checks for relevant loading, empty, error, denied, frozen, offline, and success states.
- MVP responsive-web/PWA tests, including mobile-browser/offline evidence where relevant; Phase 5 additionally requires native API/client and real-device evidence for permissions, biometrics, camera, location, background work, push, and offline sync.
- Accessibility evidence for changed user journeys.
- Observability: structured event names, correlation, metrics/alerts, and no PII/secrets in logs.
- Updated Resource schema for every changed surface and an API compatibility note whenever Phase 5/6/7 consumers are affected.
- New-logo manifest/component/surface evidence for any changed header, launcher, icon, report, notification, or release asset.
- Product/content/legal approval for money, risk, disclosure, verification, privacy, or regulatory wording.

### 11.1 First-party coverage and risk-tier contract

| Surface | Included source and hard gate | Authoritative clean-checkout evidence |
|---|---|---|
| Laravel/PHP | Every executable first-party line under `app/`, the approved `phpunit.xml` coverage source. | `./vendor/bin/pest --ci --no-tia --coverage --min=100` with Xdebug or PCOV, machine-readable report, and exact SHA. |
| Inertia TypeScript/React web/PWA | Every human-authored executable `.ts`/`.tsx` file under `resources/js` plus authored PWA/service-worker source, including matched files no test imports. Enforce 100.0% lines/statements/functions globally and per file; 100.0% critical branches globally and per file; 95.0% non-critical branches globally and at least 90.0% per file; and 100.0% newly changed branches. | `npm run test:web:coverage` from `npm ci`, using the Phase 0 Vitest/V8 configuration plus the checked-in source/risk manifests and changed-branch checker; archive machine-readable summary/LCOV, manifest identities, comparison base, and exact SHA. |
| Future native clients | From the first native source commit—including any thin-Auditor MVP exception—every human-authored executable client and platform-bridge file for every shipped target. Enforce the same lines/statements/functions, risk-tier branch, per-file, and changed-branch thresholds with approved stack-native runner(s). | Canonical full client coverage commands and machine-readable per-target reports fixed with the Phase 5 stack decision, plus source/risk manifest identities, comparison base, and exact client/server release commits. |

- New first-party behavior belongs inside the applicable measured source scope. Generated Wayfinder/API clients, declaration-only artifacts, test/configuration files, vendor code, and build output may be excluded only through exact paths in a checked-in machine-readable source manifest that records generator/provenance, owner, rationale, approver, and review trigger. Broad component/page/hook/library/platform exclusions are prohibited; customized generated or vendored code re-enters scope.
- A separate checked-in machine-readable risk manifest classifies every authored executable web/native/platform-bridge path as `critical` or `non-critical`; mixed, uncertain, unclassified, new, moved, or renamed source fails closed as `critical` until reviewed. One critical branch makes its containing file critical until the concerns are split. A downgrade from `critical` requires the other developer plus the relevant Finance/Risk, Security/Privacy, or Audit/Compliance owner, and the pull request must prove that governed behavior was removed rather than relocated.
- For D-67, `critical` always includes client/native source that calculates, decides, authorizes, mutates, validates, signs, schedules, expires, freezes, retries, reconciles, omits, redacts, or maps contracts for: money/fees/returns; wallet, ledger, Holding or ownership; primary/secondary orders, reservations, matching, fill/cancel/expiry/halt or settlement; idempotency/reconciliation; statement/parser lineage; underwriting, rating, capacity, eligibility, limits, rounding or disclosures; authentication, authorization, active role, consent, KYC/KYB, AML/sanctions, disputes, privacy/retention, prohibited data or sensitive-field handling; policy/override/versioned state transitions; audit events/outbox, evidence, seals, accreditation/dispatch/conflicts, provenance or immutable records; mandatory notices or regulatory reports; provider callbacks, timeouts, retries, reversals or failure recovery; PWA/native camera, location, biometrics, secure storage, permissions, offline queues, process recovery, conflict resolution or sync integrity; conditional API/Resource schema parity; and Pulse no-funds/non-binding/truthfulness safeguards.
- `Non-critical` is limited to presentation or platform-adapter source that renders or transports already-authoritative decisions without financial/trust branching, persisted state mutation, permission/security decisions, offline/retry/conflict behavior, or provider/evidence integrity logic. Device camera/location/biometric/secure-storage/push adapters and their denial, retry, process-death, or recovery paths remain critical when they protect a BRS guarantee.
- Current Pulse TypeScript/React remains covered while it is retained or reachable; its post-MVP product sequencing is not a coverage exemption. Dead code is deleted, not hidden. `components/ui` or similar starter code is covered unless exact files are proven immutable generated/vendored artifacts and application behavior is prohibited there.
- `@codeCoverageIgnore*`, V8/Istanbul ignore directives, native equivalents, and broad source exclusions are forbidden for reachable first-party PHP, web/PWA, or native behavior. Any truly unreachable framework glue exception names the exact path, owner, rationale, approver, expiry/review date, and removal plan.
- For web coverage, `coverage.include` (or its version-correct equivalent) must enumerate authored executable source so an unimported file cannot disappear from the denominator. Produce one complete machine-readable report, then use the checked-in policy validator to calculate separate critical and non-critical denominators and enforce global/per-file thresholds without automatic lowering or cross-cohort averaging. Native runners require equivalent unimported-source collection and per-target enforcement; a stack or custom Swift/Kotlin/Dart/TypeScript bridge that cannot produce the required metrics is not release-capable.
- The set of authored files in each complete coverage report must equal the expanded source-manifest set. Thresholds use exact covered/total counts rather than rounded display percentages; a 100.0% metric passes only when covered equals total, while 95.0% and 90.0% compare exact ratios. A zero denominator is `N/A` and cannot improve an aggregate. The policy validator itself has unit tests and fail/pass controls for every metric, tier, manifest, mapping, ratio, and SHA rule.
- “Newly changed branches” means every branch introduced or changed relative to the reviewed pull-request target merge base, calculated from machine-readable coverage and diff/source-map evidence. The changed-branch gate is 100.0% for both risk tiers and is re-evaluated against the exact target at each `feat/* -> dev -> uat -> main` hop; generated-only drift cannot be used to dilute the denominator.
- Every branch outcome in a new file is changed. A rename/move is treated as new unless content and source-map identity are proven. If diff, AST, branch-map, or source-map evidence cannot safely map a changed region, every branch in the affected authored file is treated as changed; if the affected file or required metric cannot be established, the gate fails.
- Numeric coverage never replaces correct assertions. Tests must prove exact-money/property vectors, transition/denial/state matrices, concurrent settlement, provider failure/reversal, React interaction/accessibility, offline/recovery, browser/PWA, and real-device outcomes. Branch thresholds describe execution evidence, not correctness or regulatory acceptance.
- D-65 remains the PHP numeric line-coverage contract. PHP code in the same critical domains still requires exhaustive risk-based branch/state/denial/property tests, but D-67's statement/function/branch percentages apply to TypeScript/React and native/platform-bridge runners only unless a later approved decision adds a proven PHP branch-metric gate.
- Snapshot-only rendering, shallow implementation-detail tests, test focus/skip markers, and zero-test success cannot establish coverage acceptance. Temporary uncovered line/statement/function, unimported in-scope file, uncovered critical branch, below-floor non-critical branch file, uncovered changed branch, and invalid risk downgrade must each make the gate fail before the controls are removed.
- Every PR archives all applicable machine-readable coverage reports, source/risk manifest hashes, comparison-base identity, runner/lock identities, and the exact commit SHA. Every phase exit and `dev -> uat -> main` promotion regenerates full PHP and client evidence for the promoted SHA; an older report, different SHA, TIA replay, watch run, or changed-test subset is insufficient.

### 11.2 Pest architecture-test contract

Phase 0 registers `tests/Architecture` as a Pest/PHPUnit suite and creates executable `arch()` rules. Rules expand with each module and initially enforce:

| Boundary | Required Pest architecture protection |
|---|---|
| Global first-party PHP | D-76 requires `declare(strict_types=1);` across human-authored PHP in `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, and `tests/`, including files without named symbols. Exclude Blade templates and generated `bootstrap/cache/**`; require an exact-file record for any future exception. Enforce with the Pest namespace rule and recursive syntax-aware file rule. Prohibit `dd`, `dump`, `die`, `var_dump`, and other approved debug/unsafe calls in release code. |
| HTTP/Inertia/API transport | Controllers and Form Requests follow naming/inheritance conventions and delegate governed behavior to application actions; they cannot depend directly on provider implementations or bypass protected ledger, underwriting, audit, or secondary entry points. |
| Application and Domain | Domain code cannot depend on `Illuminate\*`, `Laravel\*`, HTTP, Inertia, API Resources, Eloquent, queues, storage, presentation code, wall-clock/global-randomness helpers, or provider implementations. Application actions own orchestration/transactions and depend on injected contracts/ports rather than vendor SDKs. |
| Eloquent API Resources | Resources follow naming/inheritance conventions and may shape already-authorized, deliberately loaded data; they cannot depend on mutation actions, provider adapters, or own financial/workflow decisions. Behavior tests additionally prove that Resources do not query or mutate state. |
| Integrations and providers | Concrete adapters stay in the approved Integration/Infrastructure namespace behind application-owned interfaces; vendor DTOs/SDK types cannot leak into Domain contracts or Resource schemas. |
| Protected financial/evidence seams | The first implementation PR for a protected module must freeze its actual namespaces/entry points in ADR-0001, add `toOnlyUse`/`toOnlyBeUsedIn`-style rules with non-empty target assertions, and prove controlled fail/pass behavior before merge. The same PR includes the applicable behavior and concurrency tests. Absent future namespaces do not count as protection or require premature module implementation in Phase 0. |

Before enabling module-specific rules, Phase 0 freezes a namespace/module manifest in an architecture decision record; tests enforce the selected vertical-module or horizontal layout rather than inventing a second structure. Existing starter/legacy exceptions must name exact classes, owner, reason, removal phase, and expiry; namespace-wide `ignoring()` and permanent violation baselines are prohibited.

An architecture test is part of the normal Pest suite, not a documentation-only convention. Every boundary change updates the rule and its controlled fail/pass evidence in the same pull request. A human review and behavior tests remain necessary where dependency analysis cannot prove absence of database writes or business calculations.

### 11.3 Pest first-party PHPStan and Larastan contract

- Add `pestphp/pest-plugin-phpstan:^5.0`, keep Larastan for Laravel-aware analysis, include `vendor/pestphp/pest-plugin-phpstan/extension.neon`, and add `tests/` to the existing PHPStan paths.
- Keep the repository's current PHPStan level 7 as the initial no-regression minimum, reach zero errors across application/configuration/database/route/test paths, then raise the level only through a separately evidenced change. No blanket baseline or ignore may hide money, authorization, audit/evidence, secondary, provider, or test-definition errors.
- `composer ci:check:static` must include the same Pest-aware PHPStan configuration used locally and in CI. The plugin must validate Pest's functional API and test constructs, including typed `expect()` chains, closure context, duplicate descriptions, and invalid `covers()`/`throws()` references.
- Phase 0 removes starter placeholder test helpers and proves the gate using a temporary invalid Pest construct that fails analysis before being reverted.

### 11.4 TIA engine, client impacted-test modes, and CI topology

Pest TIA is a PHP/Pest developer-speed layer over trusted full-suite evidence. It does not calculate or satisfy the TypeScript/React or native coverage gates; those ecosystems may use their own watch/related-test modes locally under the same non-authoritative rule.

| Context | Required behavior |
|---|---|
| Local PHP developer/agent loop | Use `./vendor/bin/pest --parallel --tia` with Xdebug or PCOV. Prefer `pest()->tia()->locally()`; optionally enable `baselined()` after the GitHub artifact path is proven. |
| Local web/native developer/agent loop | Use the approved Vitest/native watch or related-test mode for rapid feedback, followed by focused behavior/static checks before handoff. These runs do not establish the numeric coverage gate. |
| Pull request and branch-promotion CI | Run `./vendor/bin/pest --ci --no-tia --coverage --min=100`, the complete `npm run test:web:coverage` plus its source/risk-policy and changed-branch validator, and the complete native coverage/policy suites whenever native source exists. Do not restore TIA result caches into the PHP gate or count any replay/watch/changed-test result as authoritative evidence. |
| Dedicated shared-baseline workflow | On the approved baseline branch and schedule, run the full suite with `--tia --fresh` and the 100% threshold, then upload the `./vendor/bin/pest --baseline` directory as the named `pest-tia-baseline` artifact. This is the only CI job allowed to use `--tia`. |
| Missing, stale, incompatible, or unavailable baseline | Record a fresh local baseline or continue with the normal full CI suite. Never fail open, reuse a different project/SHA as release proof, or commit machine-specific TIA state. |
| Phase/release exit | Archive full PHP coverage; applicable web/native global/per-file metric matrices, risk-tier branch reports, immutable base/head changed-branch reports, and source/risk manifest hashes; architecture/static/build results; commit SHA; runtime/dependency lock identities; and applicable PostgreSQL/browser/device evidence. Refresh the shared Pest TIA baseline only after all authoritative gates pass. |

CI may front-load `./vendor/bin/pest --ci --no-tia --group=arch`, an explicitly maintained non-TIA `critical` group, and focused client smoke/static jobs. These are additive fast-fail signals; none replaces the complete PHP gate, the full-source D-66/D-67 client gate, or the 100% newly changed-branch gate.

Phase 0 removes `--tia` and TIA cache restore/save steps from the existing main `tests.yml` test job, preserves the separate `tia-baseline.yml`, and validates its artifact/fetch fallback. PHP lockfiles, `phpunit.xml`, PHP/runtime changes, build configuration, and large refactors force a fresh TIA graph. JavaScript/native lockfiles, coverage configuration, source-map/transpiler settings, source/risk manifests, generated-code rules, diff-base logic, and client source changes force new complete client metric and changed-branch reports rather than a Pest TIA refresh. Under D-73 as amended on 2026-09-07, the runtime matrix exercises PHP 8.5 alone as the sole supported runtime; PostgreSQL is the only authoritative concurrency/locking environment for protected financial, ledger, reservation, secondary-settlement, and queue behavior. SQLite remains non-authoritative acceleration only. Under D-75, Node 24.15.0 is the single local/CI/build/TIA runtime and npm 10.9.8 is the pinned package-manager contract.

CI compares the checked-out `HEAD`, the workflow's expected tested SHA, the policy artifact's head SHA, and the deployment candidate before accepting evidence. Pull requests use the merge base with the exact target SHA; every promotion recalculates against that hop's target. A merge queue may test its deterministic merge SHA, but only that tested commit may be promoted.

A phase is `COMPLETE` only when all acceptance criteria have linked evidence, its exact exit commit passes Sections 11.1–11.4, and no open red decision invalidates the result.

## 12. Data and integration readiness register

| Dependency | Decision/evidence required before production use | Earliest phase |
|---|---|---:|
| Identity/KYC | Provider, countries/docs, assurance level, consent, webhook/retry/manual review, test environment, retention | 1 |
| Business registry | Provider/API, lookup key that avoids prohibited tax data, beneficial-owner scope, evidence/refresh | 1 |
| Sanctions/PEP/AML | Provider, matching thresholds, review/filing flow, ongoing monitoring, false-positive handling | 1/2 |
| ICPAR | Live register/API or approved manual verification, licence fields, refresh SLA, failure fallback | 1/2 |
| Maps/geocoding | Coordinate source, 30 km computation, accuracy, boundary behavior, PWA offline/privacy | 2 |
| Object storage/media | Rwandan residency, encryption, keys, malware scanning, signed access, retention, resumable upload | 1/2 |
| Statement parsing/OCR | Formats, accuracy/confidence benchmark, human correction, async SLA, retention, residency | 1 |
| Browser/PWA evidence integrity | Camera/location capabilities, local encryption, process/reload recovery, sync, supported mobile-browser matrix | 0/2 |
| Native biometrics/device integrity | OS support, enrollment/recovery, device change, accessibility, attestation expectations | 5 |
| Messaging/push | SMS/email/web-push/native-push providers, consent, sender identity, templates, receipts, retry, required notices | 2/5 |
| Mobile money | Custody/collection/disbursement/withdrawal scope, webhooks, reversal, idempotency, reconciliation, sandbox | 1; live in 4 |
| Commercial banks | Segregated accounts, settlement, statements/API, cutoffs, reconciliation, incident contacts | 1; live in 4 |
| Card funding | Explicit approve/remove decision, processor, fees, chargeback/fraud and reconciliation | 7 only if approved |
| Regulatory reporting | Schema, frequency, secure submission, acknowledgements, corrections, supervisor access | 3/4 |
| Observability/security | Residency, PII controls, retention, on-call integration, secrets/key management | Every phase |

## 13. Release train

Each phase is decomposed into reviewable vertical slices containing domain, Resource, Inertia/PWA surface, Admin/control, tests, audit/observability, and documentation. Native client slices are added only in Phase 5.

Under D-74, Phase 1 may use paired server/API and Inertia UI branches after the joint contract freeze. A lane may iterate independently against the approved Resource/fixture contract, but no checkpoint is complete or merge-ready until the paired slices integrate, contract identities match, both developers approve any shared-contract change, and each developer reviews the other's exact latest SHA.

1. Branch each slice from `dev` using `feat/*`.
2. Merge to `dev` only when the exact slice SHA passes full non-TIA PHP coverage, every complete clean-checkout D-66/D-67 web/applicable-native metric and changed-branch gate, architecture/static/type/lint/build, and applicable browser/device gates; the evidence is attached and the non-author developer has approved that exact latest SHA. Phase 1's alpha remains isolated and cannot be represented as shipped MVP.
3. At Phase 3, regenerate every applicable full PHP/web/native gate on the exact signed MVP release-candidate SHA, promote those commits from `dev` to `uat` in their own pull request, and perform the internal no-money rehearsal.
4. Do not promote the regulated MVP to `main` until Phase 4 approvals, provider certification, participant/cap/stop controls, and production readiness are signed.
5. Promote the same verified commits from `uat` to `main` in a separate checked pull request only after the exact promoted SHA passes every applicable complete PHP/web/native gate; verify migrations, queues/schedules, providers, reconciliation, responsive web/PWA/native clients, and rollback.
6. Keep Phases 5–8 in isolated feature flags/branches until the preceding release gate and their own approval are satisfied; they cannot change the governed pilot scope implicitly.
7. Verify `rozine.rw`, background processes, metrics, provider callbacks, financial reconciliation, security, and user-critical journeys after every approved production deployment.

No feature branch goes directly to `main`, and a green local or `dev` result is not production acceptance.

Under D-68, the two-developer team deliberately does not purchase a GitHub upgrade solely for private-branch protection. Until repository-native protection is available, every hop still uses a checked pull request, records the other developer's approval of the latest SHA, archives immutable full-suite evidence, and reruns the applicable gates on the resulting target-branch SHA. Deployment must consume only that successful target SHA and fail closed on missing, failed, stale, or mismatched evidence. A direct or unreviewed push is a policy violation that must fail promotion attestation, remain undeployed, and be reverted through a reviewed pull request; this operating control is not a claim that GitHub technically blocks the push.

## 14. Suggested phase review cadence

- Phase kickoff: confirm decisions, entry criteria, owner, threat/data review, and acceptance fixtures.
- Slice review: demonstrate one vertical path on responsive web/PWA and shared Resources with failure states and audit evidence; add native parity only in Phase 5.
- Weekly risk review: decisions, providers, policy drift, security/privacy, reconciliation, performance, and phase exit evidence.
- Phase exit: independent acceptance against every checkbox plus inspection of the exact SHA's PHP line report; client lines/statements/functions matrix; critical and non-critical branch reports; immutable base/head changed-branch report; source/risk manifests; architecture/static/build results; and every exclusion, classification, ignore, or suppression. Unmet criteria keep status open.
- MVP milestone review: label the outcome precisely as Alpha, Release Candidate, or Live Accepted.
- Release review: UAT/live evidence, migration/rollback, operational readiness, support, regulatory/legal approval, and explicit go/no-go.

## 15. Assumptions used to keep planning moving

These are working assumptions, not hidden product decisions:

- The BRS governs every conflict until an authorized amendment says otherwise.
- The MVP Specification governs MVP screen coverage, exclusions, and three-wave ordering, but not conflicting money/risk/regulatory values.
- Business, Investor, Auditor, Admin, and the launcher ship first as responsive web/PWA. Native apps are Phase 5; a thin Auditor native companion enters MVP only if Phase 0 proves the PWA cannot meet evidence-integrity requirements.
- Admin, compliance, treasury, and supervisor controls required by an active domain ship inside Phases 1–4. Pulse is deliberately post-MVP in Phase 6.
- MVP secondary liquidity is Investor-to-Investor only. Rozine inventory/market making is rejected, and executable Plus is deferred to Phase 7 pending formal approval.
- One Party model supports roles; whether one login may actively hold multiple roles remains a decision.
- Shared actions/Resources are mobile-ready during the MVP; API version 1 is completed before Phase 5 native implementation and remains backward-compatible for a governed window.
- Provider integrations use adapter contracts and fakes until vendors and sandboxes are selected.
- Kinyarwanda, English, and French are localization-ready from Phase 1; launch languages remain a decision.
- The two-developer Codex/Claude Code agent-native target is an eight-week stretch, Week 9 planning commitment, and Week 10 remediation ceiling for a pre-production engineering MVP release candidate that includes mandatory secondary trading. Exact dates remain provisional until developer/reviewer availability, D-26/D-27 and other red decisions, PWA assurance, providers, and pilot scope are confirmed.
- The Section 11 cross-platform quality contract is included in every applicable estimate: 100.0% PHP lines; 100.0% client lines/statements/functions globally and per file; 100.0% critical branches globally and per file; 95.0% non-critical branches globally and at least 90.0% per file; 100.0% newly changed branches; source/risk manifests; architecture/static/build gates; local impacted-test acceleration; and complete clean-checkout release evidence. The client rules apply to TypeScript/React immediately and future native/platform-bridge source from its first commit. No phase may trade these controls for schedule; if the existing untested client baseline cannot close D-66/D-67 in Phase 0, rebaseline the delivery window.
- Existing Pulse code is reusable only where it passes the reconciled policy and contract tests.
- Robert's 31 new-logo SVGs and 31 matching RGBA/alpha PNGs are received and hash-recorded source evidence, not approved production masters. XML validity, PNG basename/dimension parity, and the archive hash are evidenced under D-56; exact colors, canonical lockup, semantic mapping, vector/font/outline provenance and rights, required variants/surface rules, accessibility, and explicit Brand approval remain Phase 0 gates.

## 16. Clarifying questions and decision log

Answer by ID; short answers are sufficient. Red gates must be resolved before the named phase can exit. Amber questions can be refined during implementation but need an owner.

### Red — product, policy, and launch scope

- **D-01:** Should the BRS remain the governing authority for all C-01–C-34 conflicts, or is an amended BRS already planned?
- **D-02 — `PLANNING DEFAULT 2026-08-19`:** Use three distinct targets: Phase 1 `MVP ALPHA`, Phase 3 sandbox-ready responsive-web `MVP RELEASE CANDIDATE`, and Phase 4 `LIVE MVP ACCEPTED`. Confirm the target dates for the release candidate and regulated pilot separately.
- **D-03:** For post-MVP Phase 5, will native mobile use React Native/Expo, Flutter, or another stack? Is it one role-switching app or separate Investor, Business, and Auditor apps? If role-switching, every shell must expose persistent active-role context. The selected stack and any custom Swift/Kotlin/Dart/TypeScript bridge must support every D-66/D-67 global, per-file, risk-tier branch, and changed-branch gate from the first native commit.
- **D-04 — `DECIDED 2026-09-06: OPTION B`:** The Auditor ships as a narrow thin-native secure-capture companion with every ordinary Auditor screen on Inertia web/PWA. Option A is closed rather than untested: the assurance spike found four capabilities `UNSUPPORTED` on the web platform itself — hardware-bound key custody, mock-location detection, app/device attestation, and dependable out-of-band remote wipe — and three of them sit inside gates D-04 names as mandatory, so no device-matrix row can rescue it. Option C is rejected. The native surface is limited to camera capture, evidence encryption under Keychain/Keystore custody, attested location, integrity assertions, and the encrypted offline package with its ordered upload queue and remote wipe; everything else stays web. Full native role clients remain Phase 5. **This scoped MVP exception requires a schedule rebaseline**: the thin-native component is not inside the current two-developer Phase 1 estimate and needs a named owner and an estimate. Recorded in `docs/phase-0/d-04-auditor-capture-decision.md`; the device matrix that sizes the web/native boundary and the seven-day iOS dwell probe remain outstanding and do not reopen the platform choice.
- **D-05 — `OPTIONS PRESENTED 2026-08-29; DECISION PENDING`:** Choose (A) one external role per verified person; (B) allow Investor + Business-owner memberships under one Party while making Audit Partner mutually exclusive for MVP; or (C) allow every combination with related-party matching, per-Note conflict checks, recusal, redispatch, holding/order denials, and broader surveillance. **Recommendation:** Option B: require an explicit active role on every request, bind conflict rules to the verified person rather than the login, prohibit own/connected-Business investing or trading, and keep privileged staff accounts separate.
- **D-06 — `PLANNING DEFAULT 2026-08-19`:** Admin, compliance, treasury, and supervisor controls required by the delivered marketplace are MVP work inside Phases 1–4. Pulse remains in this roadmap but follows the MVP in Phase 6.
- **D-07:** Which languages must be complete at first pilot and first production release: Kinyarwanda, English, French?
- **D-08:** Which countries/residency/citizenship categories can participate at launch, and are institutional Investors or multi-owner Businesses in scope?
- **D-64 — `OPTIONS PRESENTED 2026-08-29; DECISION PENDING`:** Choose (A) exactly two verified directors/signatories for every Business; (B) an entity-type and corporate-mandate model with typed, effective-dated director, beneficial-owner, authorized-representative, and signatory relationships plus versioned individual/joint signing mandates and authority evidence; or (C) one primary representative plus manual Compliance review of everybody else. **Recommendation:** Option B; keep owner thresholds, evidence requirements, and mandate rules in approved versioned policy rather than application constants. No tax-clearance field is permitted.
- **D-09:** The BRS fixes a listing fee on listing; what is its amount, refundability, tax/accounting treatment, and exact state transition for “on listing”?
- **D-10:** The product must always permit an eligible RWF 5,000 investment. Is that also the universal unit size or only the minimum, and how should residual target amounts be handled?
- **D-11 — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Use `pricing-v1`: `round_half_up(clamp(10.0 + ((5.0 − PublishedRating) × 1.6) + TermPremium, 10.0, 15.0), 1 dp)`, where the 3/6/9/12-month premiums are `0.0/0.5/1.0/1.5`. Use the published one-decimal rating, Decimal arithmetic, half-up rate/money rounding, and nearest-franc authoritative values. Reject the 10.5% floor, `0.085`, `2.5`, fixed 13%, and authoritative RWF 100,000 rounding. See Appendix A.3.1.
- **D-11A — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Use `engine-score-v1` with weights: Coverage 30%, CFADS margin 20%, NOCF stability 20%, positive-month consistency 10%, verified-history depth 5%, and Rozine repayment/reporting conduct 15%. Six complete verified months are mandatory; missing mandatory evidence is `Unrated`, never zero. New-Business conduct is neutral `50`; post-grace arrears or an open reporting breach caps EngineScore at `58`, while default or at least 30 days past due caps it at `38`. Do not publish EngineScore or component points. Reject all prototype years, sector, base-score, margin-coefficient, and `40–92` clamp logic. See Appendix A.3.2.
- **D-11B — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Use universal launch TargetDSCR `1.25`, with no sector modifier. DSCR uses the actual computed/accepted/current contractual schedule, never a user-requested amount. A substantive `1.00–<1.25` case remains manual and, before issue, its principal is reduced to restore projected DSCR to at least `1.25`; `<1.00` declines. Use exact average contractual repayment for tiering. A quantization-only shortfall on newly computed capacity receives the minimum-franc `ROUNDING_GUARD` correction without creating a substantive manual case. Accepting less than capacity recomputes DSCR and can only improve, never weaken, the tier. See Appendix A.3.3.
- **D-11C — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Use `risk-policy-v1`: Health is Healthy at Coverage `≥1.25`, Watch at `1.00–<1.25`, Distressed below `1.00`, and Watch/Unavailable for an invalid denominator. Standing is `Good/Review/Restricted/Suspended`; Note risk is `RB0/RB1/RB2/RB3`, worst valid trigger wins; objective default is 90 days past due or an earlier dual-approved unlikely-to-pay event. Launch PD is `null/NOT_CALIBRATED`, never zero or fabricated; after a separately approved calibration it remains restricted to explicitly authorized Approver, Compliance, and Superadmin risk functions. See Appendix A.3.4.
- **D-12:** Are RWF 5M–50M Note bounds and the 35% annual-revenue ceiling approved policy, different values, or to be removed?
- **D-13 — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Pulse presents all BRS tiers from the unrounded production result: `<1.00` shows no amount and `Not currently eligible`; `1.00–<1.25` may show `Indicative capacity under review` only with `MANUAL REVIEW REQUIRED`; `≥1.25` shows `Indicatively pre-qualified`. Every branch remains non-binding and subject to full evidence, KYB, policy gates, and Audit Partner co-signature. See Appendix A.3.6.
- **D-14 — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Six complete consecutive `Africa/Kigali` calendar months, with the latest complete month no more than 45 days stale at submission and revalidated at calculation/offer/issue, are the absolute minimum; 6–11 months are manual-only and 12+ may enter the auto route. Require 12 months for seasonal/cyclical, restarted, or material-event cases. Accept and retain at most 24 months; the latest approved engine window drives the result and older approved months remain available for comparison/replay. Production covers every active bank/MoMo rail; audited-cash/POS are supplementary and manual if they change cash-flow totals. One Pulse bank/MoMo PDF can support only an indicative result and only when it contains the required consecutive history. No additional evidence types are approved in v1. See Appendix A.3.5.
- **D-15:** What Investor categories, KYC tiers, per-transaction, per-Note, per-Business, aggregate exposure, and concentration limits apply?
- **D-16:** Is card funding approved in addition to bank and MoMo? If yes, who owns fees, fraud, chargebacks, and negative balances?
- **D-17:** Who legally owns interest/float on segregated undeployed balances, and may any such value be recognized without contradicting client-money rules?
- **D-18:** What exact verified financial aggregates and evidence may each Investor see? Confirm that raw statements and bank identifiers remain restricted.
- **D-19:** Which Pulse inputs are permitted before required statement upload, and may a typed preview exist?
- **D-19A — `PRODUCT APPROVED 2026-08-18`; activation pending required sign-offs:** Use `pulse-projection-v1` with no assumed tenor or rate: show flat-return and gross-repayment ranges at the BRS bounds, `P × 10.0%` through `P × 15.0%`, rounded half-up to nearest francs. State that the pledge is non-binding, no funds move, actual terms depend on the selected Note, the 1% repayment fee applies to payouts, and capital/return are not guaranteed. Reject a single 13% projection. See Appendix A.3.6.
- **D-20:** What sandbox caps, participant limits, financial limits, duration, stop criteria, and approvals are required before any real-money pilot?
- **D-59 — `PLANNING DEFAULT 2026-08-19`:** The August 2026 MVP Specification governs MVP packaging, screen/state coverage, explicit exclusions, and build order. The BRS and activated decisions remain authoritative for conflicting financial, underwriting, audit-integrity, security, marketplace, prohibited-data, and regulatory behavior. Confirm or amend this authority split.
- **D-60 — `SCOPE APPROVED 2026-08-20`:** Investor-to-Investor secondary trading is mandatory for the MVP release candidate and may not be deferred to meet the compressed schedule. Phase 1 freezes its Holding/Order/fee/halt contracts, Phase 2 supplies lifecycle eligibility and read models, and Phase 3 completes and accepts orders plus on-platform atomic cash/Holding settlement. Rozine principal inventory, market making, price setting, reserve cover, maker/taker/acquisition fees, and any demo sale to Rozine are rejected. D-26/D-27 still govern the executable lifecycle, price, surveillance, and failure details.
- **D-61 — `PLANNING DEFAULT 2026-08-19`:** Executable Plus bands/automation are deferred to Phase 7 until fee, suitability, concentration, legal, and regulatory policy is approved. The MVP may show only an honest gated explainer. Confirm whether Product accepts this narrower BRS-safe MVP boundary.
- **D-62 — `PLANNING DEFAULT 2026-08-19`:** Pulse is deferred to Phase 6 because the MVP PDF defines four authenticated apps plus a launcher and omits Pulse. BRS AC-11 remains explicitly open until Phase 6. Confirm the deferral.

### Red — money, servicing, secondary, and distress

- **D-21:** What are the custody account topology, bank/MoMo rails, settlement cutoffs, withdrawal SLAs, and safeguarded-funds controls?
- **D-22:** What are primary reservation, partial funding, oversubscription, failed payment, cancellation, withdrawal, and 30-day expiry race rules?
- **D-23:** What due-day, holiday, grace, partial-payment, late-fee if any, and payment-allocation rules apply?
- **D-24:** What exactly happens operationally on early repayment while preserving the full promised return?
- **D-25:** D-11C fixes default at 90 days past due or an earlier dual-approved unlikely-to-pay event. What grace/materiality rules, arrears and cure transitions, evidence, authority, collections, recovery, write-off, and post-write-off treatment complete that lifecycle without changing the fixed backstop?
- **D-26 — `OPTIONS PRESENTED 2026-08-29; DECISION PENDING`:** Choose (A) fixed-price, all-or-nothing Orders over any seller-selected subset of fully settled eligible units; (B) a partial-fill order book with remaining units, multiple fills, and price-time priority; or (C) RFQ/manual matching. **Recommendation:** Option A for MVP with `Open → Reserved → Settled` and terminal `Cancelled/Expired/AdminCancelled/Suspended` paths, cancellation only while Open, atomic pre-funded cash/Holding settlement, idempotent reservation release, eligibility recheck at create/reserve/settle, and a holder-of-record snapshot based only on settlements completed before the published cutoff. Seven-day expiry and five-minute reservation are candidates, not approved values.
- **D-27 — `OPTIONS PRESENTED 2026-08-29; DECISION PENDING`:** For seller-chosen asks, choose (A) positive whole-RWF per-unit prices with a Finance/Legal-approved contractual-cashflow cap; (B) a configurable reference-price band and approved tick; or (C) unrestricted positive asks with disclosure only. **Recommendation:** use Option A for the all-or-nothing MVP, subject to Kimani/Robert and any required external approval; disclose ask total, outstanding principal, remaining contractual payouts, premium/discount, the seller's 3% fee, rating/health/report/schedule, and loss/no-guarantee risk. Block self/same-beneficial-owner trades; flag linked/circular trades, rapid cancel/relist, velocity and extreme pricing; preserve immutable events and per-Note/global halts. Rozine remains non-principal, non-maker, and non-price-setter.
- **D-28:** What dispute SLAs, evidence access, escalation, appeal, remedy, and compensation authorities apply?

### Red — Auditor and evidence integrity

- **D-29:** What exact ISRS 4400 procedure/checklist and working papers are required, who approves versions, and what may Investors view?
- **D-30:** Can ICPAR verification be live by API, periodic file, or manual review? What fields and refresh SLA are authoritative?
- **D-31:** BR-51 fixes the radius from the Audit Partner's registered office to the Business's registered premises. How are those coordinates sourced/refreshed, what accuracy/boundary rules apply, and should live-device distance be retained only as an additional field-integrity signal?
- **D-32:** What are dispatch rotation, concurrency defaults, availability, acceptance timeout, decline/no-response, redispatch, and travel rules?
- **D-33:** What conflicts of interest must be declared, how long do they persist, and when is ICPAR escalation required?
- **D-34:** How is lateness attributed between Business and Auditor so yield freeze or other consequences do not punish the wrong party?
- **D-35:** What canonical seal format, signing algorithm, key owner, rotation/revocation, public verification, and amendment-chain rules are approved?
- **D-36:** What offline retention, retry limit, lost-device/remote-wipe, device replacement, and sync-conflict rules are acceptable?
- **D-37:** Which Android/iOS versions and device-integrity controls are required to enforce camera-only, location, metadata, and biometric binding?

### Red — legal, compliance, data, and providers

- **D-38:** Which KYC/identity, business registry, sanctions/PEP/AML, ICPAR, parsing/OCR, maps, MoMo, bank, messaging, push, and storage providers are selected?
- **D-39:** Has Rwandan legal/regulatory counsel approved the Note structure, client-money flow, fixed-return disclosures, secondary market, complaints, and sandbox path?
- **D-40:** What data-residency boundary, classifications, retention periods, legal holds, deletion/anonymization rules, and subject-right SLAs apply?
- **D-41:** What regulatory report schemas, frequency, submission channel, acknowledgement/correction flow, and supervisor fields are required?
- **D-42:** What availability, RTO, RPO, backup/restore, performance, and concurrency objectives define production acceptance?
- **D-43:** What penetration-test scope, independent reviewer, remediation SLA, incident severity model, and go-live security authority apply?

### Red — brand and release identity

- **D-51 — `DECIDED 2026-09-07`:** The mark is the star. The primary Rozine lockup is the **detached leading star** — the star set apart, then `rozine` (Frames 83/84/89/92/95/96/101/102) — used in formal contexts including the platform, documents and reports, and it is the mark for every application surface. The **star integrated at the `i`** (Frames 85/90/93/97/98/103/104) is secondary, for branding, merchandise and campaign work, and does not appear in product UI. The gradient app icon (Frame 107) is dropped; app icons use the flat and monochrome treatments. Frame 86 remains a defective export of the secondary lockup and must be re-exported before any campaign use, but no longer blocks product work. The V1.0 guide's section 07 still describes the previous wing mark and is superseded.
- **D-52 — `DECIDED 2026-09-07; AMENDS THE BRAND SYSTEM`:** The logo's blue is used everywhere blue appears — *"consistency matters"*. Rozine Blue becomes **`#0039FF`** (was `#0A5CFF`), Business **`#1D9E75`** (was `#12A150`), Auditor **`#C2661F`** (was `#DD8A00`), and the Strong/Stable/Weak rating bands move with them to `#1D9E75` / `#0039FF` / `#C2661F`. Deep Blue is derived rather than picked, at the brand owner's instruction: **`#0027B0`** reproduces the exact ratio V1.0 held between `#0A5CFF` and `#0A3FB0`, landing 12.6 L* below core blue against the guide's original 14.3. Ink, Pulse and the neutrals are unchanged. This is an amendment requiring a dated V1.1 revision, and it changes product chrome as much as the logo files — core blue is every primary button, link, focus ring and active navigation state. The blue logo files need no recolouring since they already carry `#0039FF`. `#BA0D3B` is confirmed not part of the system: every occurrence is a `<circle r="0.5">` export artefact present in no logo shape.
- **D-56 — `REMEDY CHOSEN 2026-09-07: RESET IN INTER`:** The `rozine` wordmark was set in **San Francisco Pro**, whose licence covers user-interface use on Apple platforms and prohibits the modification that outlining letterforms constitutes, granting no trademark rights. An override was proposed on App Store grounds and then withdrawn. **The wordmark is reset in Inter**, which is SIL Open Font License permitting logos and trademarks outright and is already the product's self-hosted typeface, so the mark will match the interface exactly and the licence question is removed rather than carried as an accepted risk. **19 of the 31 files carry letterforms** and are re-exported; the 12 star-only files are unaffected and their derived favicon, app and maskable icons already ship. Unsplash material may not appear inside the mark, and any generative-tool artwork must be named in the record since authorship bears on registrability. External legal/regulatory clearance under D-39 remains a separate gate that D-71 forbids the internal pool from self-approving.
- **D-57 — `DECIDED 2026-09-07`:** Primary treatment is the blue logo on white; secondary is the white logo on blue. Remaining treatments follow the audience and the language of the campaign. Product chrome uses the primary treatment; role accents continue to identify a surface without taking over the interface.
- **D-63 — `CLOSED 2026-09-07`:** Role wordmarks pair with Rozine by default. Inside a signed-in application the visible mark may drop it and read just `investor`, since context already establishes Rozine. The **accessible name is always the paired form** — `Rozine Investor` — because a screen reader announces one string every time and it is the only identity a non-sighted user receives. Everything public-facing — marketing, the launcher, shared passes, exported documents, social cards — stays paired in both the visible mark and the accessible name.

### Amber — UX, operations, and delivery

- **D-44:** Which Auditor tasks may be completed on web, excluding any flow where browser capture would weaken camera/location requirements?
- **D-45:** Should Business accounts support owner plus finance/operator roles, and should institutional Investors support teams and maker-checker actions?
- **D-46:** What content/legal approval workflow governs translations, disclosures, notifications, and education materials?
- **D-47:** Is WCAG 2.2 AA the accepted web target, with equivalent native platform guidance?
- **D-48:** Which live delivery stack is preferred for web/mobile updates, and which push provider should be used?
- **D-49:** What are the notification channels, opt-in/opt-out rules, required notices, quiet hours, delivery-retention, and fallback order?
- **D-50:** What shareable-pass fields, consent, expiry, revocation, download format, and supported social-share destinations are approved?
- **D-53:** For the required BO-1–BO-8 metric family, what definitions, targets, freshness, owners, additional metrics, and PII/analytics exclusions are approved?
- **D-54:** What support hours, escalation owners, operational staffing, Audit Partner support, reconciliation ownership, and incident communication are expected?
- **D-55 — `UPDATED 2026-08-20`:** Plan for two dedicated expert developers orchestrating Codex and Claude Code across at most four bounded implementation/test worktrees with daily integration. Use an eight-week stretch target, Week 9 planning commitment, and Week 10 remediation ceiling for the pre-production responsive-web MVP RC, including mandatory secondary trading. Phase gates and second-human review for authorization, migrations, money, underwriting/risk, privacy, seals, provider callbacks, secondary settlement, and release-critical work remain mandatory. Confirm named availability, non-engineering reviewers, PWA assurance result, and separate MVP-RC/regulatory-pilot dates before converting this into a calendar commitment.
- **D-58:** What is the smallest supported mobile-web viewport and the launch device/browser/OS matrix, including orientation, safe-area, keyboard, zoom, and text-scaling expectations?
- **D-65 — `ENGINEERING QUALITY APPROVED 2026-08-23`:** Enforce 100.0% Pest line coverage over the approved first-party PHP source scope (`app/` initially), Pest architecture tests for the modular-monolith boundaries, and Pest's first-party PHPStan plugin over application and test code. Use the TIA engine for local developer/agent acceleration and a dedicated shared-baseline workflow only; every pull request, phase exit, and promotion runs the complete clean-checkout suite without TIA on the exact commit. Coverage never substitutes for risk-based assertions or client/browser/device evidence.
- **D-66 — `ENGINEERING QUALITY APPROVED 2026-08-23`:** Enforce 100.0% executable line coverage globally and per file over all shipped human-authored TypeScript/React web/PWA source and, from the first native-client commit including any thin-Auditor exception, all human-authored executable native and platform-bridge source. Only exact generated, declaration-only, test/configuration, vendor, and build artifacts may be excluded through the reviewed machine-readable manifest. Every pull request, phase exit, and promotion runs the complete clean-checkout client suite on the exact SHA; watch/related-test modes are local acceleration only. Coverage never substitutes for type/lint/build, component/contract, branch/state, browser/device, accessibility, offline/recovery, or platform tests.
- **D-67 — `ENGINEERING QUALITY APPROVED 2026-08-24`:** For every included human-authored TypeScript/React web/PWA and native/platform-bridge file, enforce 100.0% line, statement, and function coverage globally and per file. Client files containing financial or trust-boundary behavior require 100.0% branch coverage globally and per file. Remaining approved presentation/platform-adapter files require at least 95.0% branch coverage globally and 90.0% per file. Every branch introduced or modified relative to the recorded target merge base requires 100.0% coverage regardless of class. The reviewed machine-readable source/risk manifests govern exact exclusions and classification; mixed, uncertain, new, moved, renamed, or unclassified files default to critical. Every pull request, phase exit, and promotion archives full exact-SHA metric reports plus immutable base/head changed-branch evidence. D-65 remains the PHP numeric line-coverage contract; risk-based PHP branch/state assertions remain mandatory even though D-67's additional numeric metrics are client/native gates.
- **D-68 — `RELEASE GOVERNANCE APPROVED 2026-08-27`:** Do not make a paid GitHub plan or hosted private-branch-protection feature a dependency for the current two-developer team. Preserve `feat/* -> dev -> uat -> main` pull requests, complete hosted gates on the exact candidate and resulting target-branch SHAs, and require approval of the latest candidate SHA by the non-author developer. Deployment workflows, including manual dispatch, must fail closed when matching CI evidence or review attestation is missing, failed, stale, or mismatched. A direct or unreviewed push is a documented policy violation, cannot deploy, and must be reverted through a reviewed pull request. Reassess native branch protection if repository access or team size expands.
- **D-69 — `INTERNAL OWNERSHIP MAP CONFIRMED 2026-08-27`:** Aminu, Erastus, Robert, and Kimani form the internal project approval pool. Robert owns Product, Design, and Brand; Aminu and Erastus jointly own Engineering and Security; Kimani owns Audit Operations and Compliance. At confirmation, Finance/Risk, Legal, and independent-test assignment were still open; D-70 supersedes that interim condition. Every decision, policy, phase, and evidence record must record its accountable owner, required signatures, and any conflict or abstention. This pool does not replace legally or professionally required external counsel, CMA/sandbox, ICPAR/Audit Partner, provider-certification, penetration-test, or independent-assurance authority.
- **D-70 — `SMALL-TEAM SHARED INTERNAL RESPONSIBILITIES APPROVED 2026-08-28; SUPERSEDED BY D-71`:** This interim decision assigned Business, Finance/Risk, and internal Legal collectively to the four-person pool. D-71 replaces that shared allocation with named owners; D-70 remains only as decision history.
- **D-71 — `NAMED INTERNAL ROLE OWNERSHIP APPROVED 2026-08-28`:** Robert is the Business owner and internal Legal owner. Kimani is the Finance/Risk owner. Existing assignments remain: Robert owns Product, Design, and Brand; Kimani owns Audit Operations and Compliance; Aminu and Erastus jointly own Engineering and Security. Each applicable record must capture every required named-owner signature, conflict, or abstention. Independent-test approval may be supplied by any pool member who was not the sole author of the governed work or expected results, or by an eligible named external delegate. Ownership and eligibility do not themselves approve a record. Legally or professionally required external counsel, CMA/sandbox, ICPAR/Audit Partner, provider certification, penetration testing, and independent assurance remain separate gates and cannot be self-approved by the internal pool.
- **D-72 — `MODULAR-MONOLITH ARCHITECTURE APPROVED 2026-08-28; PULSE EXCEPTION REMEDIATED LOCALLY 2026-08-29`:** Approve ADR-0001 as written. Rozine uses the documented Domain/Application/Infrastructure/HTTP boundaries, one shared application/domain layer for Inertia and versioned API transports, `/api/v1` for future native clients, and shared Eloquent API Resources as the serialization—not business-logic—boundary. The 2026-08-29 working tree isolates Pulse behind Domain/Application/Infrastructure/HTTP layers and removes authoritative calculation from React and Resources. The two Fortify actions remain controlled framework exceptions and are reassessed with the Phase 1 identity module. Approval and local remediation do not close the broader architecture-test, clean exact-SHA, hosted-evidence, or PostgreSQL gates.
- **D-73 — `PHP 8.5 ONLY, AMENDED 2026-09-07`:** PHP 8.5 is the sole supported runtime — minimum, canonical, coverage and deployment. The earlier contract kept PHP 8.4 as a minimum-compatibility lane beside it; that lane is removed and the CI matrix runs 8.5 alone. `composer.json` requires `^8.5` so the manifest cannot claim support for a version nothing tests. **Consequence accepted:** nothing now proves the application runs on 8.4, so any host, container or contributor still on 8.4 is unsupported rather than untested. PostgreSQL remains authoritative for financial, ledger, reservation, secondary-settlement, queue, locking and concurrency evidence; SQLite may accelerate isolated unit/feature tests but cannot certify those behaviours.
- **D-74 — `PHASE 1 PARALLEL OWNERSHIP APPROVED 2026-08-28`:** Following a joint contract freeze, Aminu owns the authoritative server/API/domain/application/data/integration/security implementation and PHP evidence. Erastus owns Inertia React UI/UX, client behavior, responsive/PWA presentation, accessibility, localization, and TypeScript/React/browser evidence. Both approve shared contract changes, integrate at five defined checkpoints, and cross-review the exact latest SHA. This split assigns implementation responsibility; it does not divide the domain, permit Inertia to call the public API, move authoritative behavior into the client, defer safety, or weaken any Phase 1 acceptance or coverage gate.
- **D-75 — `NODE RUNTIME CONTRACT APPROVED 2026-08-29; VITE+ SOURCE FILE UPDATED 2026-08-30`:** Pin Node 24.15.0 for local development, both normal CI jobs, production asset generation, and the Pest TIA baseline workflow. Keep npm 10.9.8 pinned for deterministic lockfile installs. `.node-version`, package engine metadata, lock metadata, and hosted workflows must agree; `.node-version` is the canonical local runtime source after the approved Vite+ migration. A runtime or toolchain-governance change invalidates prior client build/coverage evidence and requires a clean install plus the complete client gate.

- **D-76 — `PHP STRICT-TYPES CONVENTION APPROVED 2026-09-10`:** Aminu approved requiring `declare(strict_types=1);` in every human-authored PHP file under `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, and `tests/`. Blade templates, generated `bootstrap/cache/**`, and vendor/generated artifacts outside these roots are excluded; no human-authored exception is approved. Enforce named symbols and standalone files in Pest/CI, reject later redeclarations, and require an exact-file owner/reason/approver/expiry record for any future exception. The conversion retains PHP 8.5, PHPStan, full non-TIA tests and 100% coverage. ADR-0001 already requires protected-module rules with each module's first implementation PR; recording that gate does not activate future financial/evidence behavior. Implementation and verification evidence belong in the Phase 0 kickoff record.

## Appendix A — Underwriting decision worksheet and golden-vector pack

This appendix is the approval and test contract for statement evidence, underwriting, rating, pricing, schedules, and Pulse projections. It deliberately separates requirements already fixed by the BRS from algorithms or policy values that remain undecided.

### A.1 Usage and status rules

**Unsigned preparation, 2026-09-10:** [decision review](phase-0/underwriting-decision-review.md), [48-vector source register](phase-0/underwriting-vector-review.json), [synthetic review fixtures](phase-0/policy-review-fixtures.json) and [Pest checks](../tests/Unit/PhaseZeroPolicyReviewTest.php) make the remaining choices and selected arithmetic reproducible. The 34 examples touch 22 source vectors only partially (plus secondary arithmetic); they do not complete any vector's required layers or replace the approval header's missing evidence/fixture-set hashes. All source dispositions below remain unchanged.

- `FIXED` means the BRS already governs the invariant. Implementation may clarify representation but cannot change the rule without a formal BRS amendment.
- `DECISION` means no production behavior may be encoded until the named decision is approved.
- `MIXED` combines a fixed invariant with one or more parameters or edge cases requiring approval.
- `PRODUCT APPROVED` records the selected product behavior from the 18 August 2026 plan review. It does not activate policy or replace the named Finance/Risk, Compliance, Legal, internal Audit Operations, applicable external ICPAR/Audit Partner, Engineering, Security, independent-test, or CMA/sandbox approvals.
- `READY-TO-BASELINE` means the BRS or a recorded product-approved candidate fixes the expected behavior, but the concrete fixture, hashes, activated policy/calculator versions, and independent approvals do not yet exist. It is not executable release evidence or proof of policy activation.
- A golden vector is `BASELINED` only when its inputs, intermediate values, expected outputs, rounding, policy version, and approvals are complete.
- A vector is `BLOCKED` when any expected value depends on an unresolved decision. A blocked vector is a release gate, not permission to use prototype behavior.
- `QUARANTINED` identifies a source/prototype behavior that has not been approved or rejected. It may not reach production; after a decision, it must become either an approved versioned vector or a `REJECTED` regression vector.
- `REJECTED` identifies a source/prototype behavior explicitly excluded by the approved policy and retained as a regression test.
- Every approved worksheet revision receives an immutable policy version, effective time, superseded version, approvers, rationale, and fixture-set hash.
- Historical calculations retain their original policy version and expected result after a later policy change.

### A.2 Approval header

| Field | Required value |
|---|---|
| Worksheet status | `PRODUCT-APPROVED CANDIDATE — NOT ACTIVATED`; D-11, D-11A–C, D-13, D-14, and D-19A are selected, while remaining decisions and required named sign-offs still block activation |
| Policy version | Candidate bundle `sandbox-v1`: `pricing-v1`, `engine-score-v1`, `target-dscr-v1`, `risk-policy-v1`, `evidence-policy-v1`, `pulse-presentation-v1`, and `pulse-projection-v1` |
| Effective date/time | `NOT ACTIVATED`; activation time must be stored in UTC with the approved Rwanda display convention |
| Supersedes | `None` for the candidate bundle; prototype behavior is not an active predecessor policy |
| Product decision approval | `APPROVED` in the plan review on 18 August 2026 |
| Internal project approval pool | Aminu; Erastus; Robert; Kimani — confirmed 27 August 2026; this names the pool but does not activate the worksheet |
| Product owner | Robert — confirmed Product owner; a signature is still required on each applicable decision record |
| Business owner | Robert — confirmed Business owner; a signature is still required on each applicable decision record |
| Finance/risk owner | Kimani — confirmed Finance/Risk owner; a signature is still required on each applicable decision record |
| Compliance owner | Kimani — confirmed Compliance owner; a signature is still required on each applicable decision record |
| Internal Legal owner | Robert — confirmed internal Legal owner; a signature is still required on each applicable decision record; required external counsel remains separate |
| External legal/regulatory counsel | `TBD` where law, regulation, sandbox conditions, or the approved policy requires external authority; no internal signature substitutes for it |
| Internal Audit Operations owner | Kimani — confirmed Audit Operations owner; a signature is still required on each applicable decision record; external ICPAR/Audit Partner authority remains separate |
| Engineering owner | Aminu and Erastus — confirmed joint Engineering owners; at least one accountable signature plus the required non-author review must be recorded per decision |
| Security owner | Aminu and Erastus — confirmed joint Security owners; at least one accountable signature plus the required non-author review must be recorded per decision |
| Independent test approver | Any internal pool member who was not the sole author of the governed work or expected results, or an eligible named external delegate; the accountable approver and signature remain required per record |
| Regulatory/CMA or sandbox approval | `TBD`; no approval is inferred from this product decision |
| Source versions | BRS v1.0; product decisions D-11, D-11A–C, D-13, D-14, and D-19A dated 18 August 2026. Open dispositions remain `UW-06`–`UW-08`, `UW-13`, D-12/`UW-17`, `UW-25`, the A.3.4 activation matrix, and every other unanswered decision in Section 16. |
| Fixture-set hash | `TBD` after machine-readable fixtures exist |

### A.3 Policy sign-off worksheet

| ID | Class | Decision or invariant | Approval/evidence required | Governing requirements |
|---|---|---|---|---|
| `UW-01` | `FIXED` | Every result records exact inputs, evidence references, policy version, calculator version, actor/system, and calculation time. | Resource/audit schema and replay test. | FR-602, FR-701, FR-702 |
| `UW-02` | `PRODUCT APPROVED` | `evidence-policy-v1` requires evidence from every declared active bank/MoMo rail. Audited-cash and POS are supplementary for all categories and force manual review when they change underwriting cash flow. No additional evidence type is approved in v1; tax identifiers remain prohibited. | Named sign-offs, evidence-source contract, and fixtures before activation. | FR-201, AC-9, D-14 |
| `UW-03` | `PRODUCT APPROVED` | At least six complete consecutive calendar months are required. Six–11 months are manual-only; 12+ may enter the auto route, and 12 are mandatory for seasonal/cyclical, restarted, or material-event cases. The latest complete month may be at most 45 days stale at submission and must be revalidated at calculation, offer, and issue. Accept/retain at most 24 months; the latest approved window drives the current result while older approved months remain replayable. | Boundary, gap, stale-history, seasonal, restart, 24-month ceiling, window-selection, and rescore fixtures. | BR-15–BR-16, D-14 |
| `UW-04` | `PRODUCT APPROVED` | Use `Africa/Kigali` calendar buckets `[month_start, next_month_start)`; store but exclude the current partial month; never zero-fill/interpolate gaps. Duplicate hashes are idempotent, overlapping transactions are deduplicated, and corrections/resubmissions append immutable versions with lineage. | Signed normalization specification, overlap corpus, and replay fixtures before activation. | BR-13, FR-602, D-14 |
| `UW-05` | `PRODUCT APPROVED` | Use exact RWF values; reject foreign currency in v1. Exclude own-account transfers and debt proceeds from operating inflow; net linked reversals/refunds against the original; treat debt service separately; count reconciled cash/POS activity once. Unclassified or low-confidence items require confirmation and cannot silently support capacity. | Signed transaction taxonomy, confidence configuration, and labelled benchmark corpus before activation. | BR-10–BR-13, FR-701, D-14 |
| `UW-06` | `MIXED` | `OwnerDraw` is subtracted from trimmed NOCF in CFADS; the source/recognition rules are undefined. | Approve definition, period alignment, missing value, and verification evidence. | Section 12 normative formulas |
| `UW-07` | `MIXED` | Existing Rozine obligations and disclosed external debt service are included. | Approve evidence, frequency, undisclosed-debt handling, and monthly alignment. | BR-12 |
| `UW-08` | `DECISION` | Volatility-trim method must prevent one exceptional month from inflating capacity. | Approve algorithm, minimum observations, tails, winsorization/exclusion behavior, and explanation. | BR-13 |
| `UW-09` | `FIXED` | `NOCF_m = Inflow_m − Outflow_m`. | Exact-RWF unit test and trace to normalized period inputs. | Section 12 normative formulas |
| `UW-10` | `FIXED` | `CFADS = trimmed_mean(NOCF) − ExistingDebtService − OwnerDraw`. | Exact-RWF unit test with every intermediate persisted. | Section 12 normative formulas |
| `UW-11` | `PRODUCT APPROVED` | `engine-score-v1` uses the six weighted normalized factors, evidence minimum, missing-data behavior, new-Business neutral, and adverse-conduct caps in A.3.2. EngineScore/components are non-public. The prototype base/year/sector/margin/clamp model is rejected. | Signed scorecard artifact, named sign-offs, fixture hashes, and material-event operations before activation. | Rating formula, D-11A |
| `UW-12` | `PRODUCT APPROVED` | Apply the BRS transform `Rating = clamp(EngineScore ÷ 20, 0.0, 5.0)`, round half-up to one decimal, then assign Strong ≥4.0, Stable ≥3.0, Weak ≥2.0, Distressed <2.0. | Clamp and tie vectors, including `77 ÷ 20 = 3.85 → 3.9` and `79 ÷ 20 = 3.95 → 4.0`. | BR-30–BR-37, Section 12, D-11 |
| `UW-13` | `MIXED` | `Coverage = Inflow_period ÷ (Outflow_period + DebtService_period)`. `risk-policy-v1` maps a zero, negative, missing, or otherwise invalid denominator to Health `Watch` with `Coverage unavailable`; infinity/NaN is forbidden. | Approve numeric storage/display precision; test the approved health/unavailable outcome independently of formatting. | Section 12, FR-209, FR-409, D-11C |
| `UW-14` | `PRODUCT APPROVED` | `target-dscr-v1` uses universal `1.25` with no sector modifier. Tier-2 cases remain manual and principal is reduced before issue until projected DSCR is at least `1.25`; accepted amounts below capacity are recomputed and can only improve DSCR. | Exact integrated boundaries, reduction, accepted-amount, and last-franc guard vectors. | Capacity formula, BR-14, D-11B |
| `UW-15` | `FIXED` | DSCR <1.00 declines; 1.00–<1.25 requires enhanced procedures/manual approval; ≥1.25 may auto-approve only with co-signature, KYB, and all other gates. | Exact lower/upper boundary vectors. | BR-14 |
| `UW-16` | `FIXED` | `Capacity = (CFADS ÷ TargetDSCR × Tenor) ÷ (1 + TotalReturn)` and is solved from serviceable repayment, never requested amount. | Exact intermediate values, nearest-franc result, and stale-policy protection. | BR-10–BR-11, Section 12 |
| `UW-17` | `DECISION` | Any RWF 5M–50M Note bounds or 35% annual-revenue cap. | Approve, replace, or reject D-12; absence of approval means neither constraint is production policy. | C-07, C-08, D-12 |
| `UW-18` | `FIXED` | Permitted tenors are exactly 3, 6, 9, and 12 months. | Reject every other tenor at validation and domain boundaries. | BR-20 |
| `UW-19` | `PRODUCT APPROVED` | `pricing-v1` is `round_half_up(clamp(10.0 + ((5.0 − PublishedRating) × 1.6) + TermPremium, 10.0, 15.0), 1 dp)` with 3/6/9/12-month premiums `0.0/0.5/1.0/1.5`. | Pricing matrix, floor/cap, explanation, and cross-transport parity vectors. | BR-22–BR-23, D-11 |
| `UW-20` | `FIXED` | Flat total return is 10.0%–15.0% inclusive; never APR; exact Business cost and Investor proceeds are shown before commitment. | Boundary/content vectors and prohibited-language scan. | BR-21–BR-23, BR-26 |
| `UW-21` | `PRODUCT APPROVED` | Use Decimal arithmetic without binary floating point; keep intermediate formula values unquantized; round rates half-up to one decimal and each final monetary result half-up to the nearest franc. Apply A.3.3's operation order and last-franc DSCR guard. RWF 100,000 rounding is rejected for authority; Pulse must retain the exact underwriting value. | Exact tie/order/safety vectors in every calculator and Resource layer. A coarse display, if later approved, must remain a separate non-authoritative field. | FR-700, BR-82, C-05, D-11, D-11B |
| `UW-22` | `FIXED` | `Instalment = (Principal × (1 + TotalReturn)) ÷ Tenor`; all instalments sum exactly to principal plus return, with residual on the final instalment. | One-franc residual and large-value vectors. | BR-24, FR-700 |
| `UW-23` | `FIXED` | Early repayment never reduces total return owed. | Early-payoff schedule and ledger vector. | BR-25 |
| `UW-24` | `PRODUCT APPROVED` | `risk-policy-v1` uses the Health/standing/risk vocabularies and objective default backstop in A.3.4; worst valid trigger wins. Launch PD is `null/NOT_CALIBRATED` and never public. None is a second public business-quality score. | Signed trigger/cure/action matrix, audience tests, and named sign-offs before activation; any later PD requires separately approved calibration/validation. | FR-209, FR-409, D-11C |
| `UW-25` | `MIXED` | Recompute rating on every verified monthly report/material event; retain and automatically rescore declines as new statements arrive. | Approve material-event taxonomy, effective time, job ordering, and stale-result behavior. | BR-16, BR-36 |
| `UW-26` | `FIXED` | Persist immutable issue rating and show both issue and live rating to holders. Existing Notes that become Distressed enter active monitoring; no new Distressed listing. | History and state-transition vectors. | BR-35, BR-37 |
| `UW-27` | `FIXED` | Pulse Business pre-qualification uses the same production engine and policy version and remains non-binding. | Byte/value parity across production action, Pulse Resource, Inertia, and API. | BR-80–BR-82, FR-501 |
| `UW-28` | `PRODUCT APPROVED` | `pulse-projection-v1` assumes no tenor/rate and displays half-up nearest-franc flat-return endpoints at `P × 10.0%` and `P × 15.0%`, plus gross-repayment endpoints `P + return`, with the approved non-binding/risk/fee disclosure. | Endpoint, rounding, version-history, disclosure-positive, and prohibited-language vectors. | FR-500, D-19A |
| `UW-29` | `FIXED` | Policy changes are versioned and prospective; historical results remain reproducible and are never silently recalculated. | Draft/review/approval/effective/retire workflow, migration decision, and old/new replay vectors. | FR-605, FR-702 |
| `UW-30` | `PRODUCT APPROVED` | DSCR uses the exact average repayment of the actual schedule being evaluated: computed new-capacity candidate, accepted principal at/below capacity, or existing/current Note—never a user-requested amount. For new capacity, use A.3.3's Decimal order and correct a rounding-only shortfall by the minimum francs without turning it into a substantive manual-band case. Recompute every accepted lower principal. | Integrated new-capacity, substantive manual-band, rounding-guard, existing-schedule, and accepted-amount fixtures. | BR-10–BR-14, BR-24, Section 12, D-11B |

#### A.3.1 `pricing-v1`

Use Decimal arithmetic throughout. Let `PublishedRating` be the BRS rating after half-up rounding to one decimal, and use these exact tenor premiums:

| Tenor | Premium |
|---:|---:|
| 3 months | `0.0` percentage points |
| 6 months | `0.5` percentage points |
| 9 months | `1.0` percentage points |
| 12 months | `1.5` percentage points |

```text
raw_total_return_percent = 10.0
                         + ((5.0 − PublishedRating) × 1.6)
                         + TermPremium

TotalReturnPercent = round_half_up(
    clamp(raw_total_return_percent, 10.0, 15.0),
    1 decimal place
)
```

The return is flat for the full Note term, not APR. The calculator records the published rating, tenor, raw/clamped/rounded rate, policy version, and explanation. Representative approved outputs are:

| Published rating | 3 months | 6 months | 9 months | 12 months |
|---:|---:|---:|---:|---:|
| `5.0` | `10.0%` | `10.5%` | `11.0%` | `11.5%` |
| `4.0` | `11.6%` | `12.1%` | `12.6%` | `13.1%` |
| `3.0` | `13.2%` | `13.7%` | `14.2%` | `14.7%` |
| `2.9` | `13.4%` | `13.9%` | `14.4%` | `14.9%` |
| `2.8` | `13.5%` | `14.0%` | `14.5%` | `15.0%` |
| `2.0` | `14.8%` | `15.0%` | `15.0%` | `15.0%` |

A numeric `10.5%` result remains valid where this formula produces it; what is rejected is the prototype's universal `10.5%` floor.

#### A.3.2 `engine-score-v1`

Every factor is a Decimal score from `0` through `100`. Define:

```text
up(x, low, high)   = 100 × clamp((x − low) ÷ (high − low), 0, 1)
down(x, low, high) = 100 − up(x, low, high)

relative_mad = median(abs(NOCF_m − median(NOCF)))
               ÷ max(abs(median(NOCF)), RWF 1)
```

| Factor | Weight | Approved normalization |
|---|---:|---|
| Coverage | `30%` | `up(Coverage, 1.00, 1.50)` |
| CFADS margin | `20%` | `up(CFADS ÷ average verified monthly operating inflow, 0.05, 0.25)` |
| NOCF stability | `20%` | `down(relative_mad, 0.10, 0.60)` |
| Positive-month consistency | `10%` | `up(positive NOCF months ÷ valid months, 0.50, 1.00)` |
| Verified-history depth | `5%` | `up(complete verified months, 6, 18)` |
| Rozine conduct | `15%` | `70% × on-time-instalment score + 30% × on-time-report score`; a Business with no Rozine history receives neutral `50` |

The uncapped score is the weighted sum. Derive the rating from the unrounded Decimal score; only the resulting rating is rounded half-up to one decimal under `UW-12`. Missing mandatory evidence, fewer than six complete verified months, an invalid required denominator, or a non-finite required factor produces `EngineScore = null`, rating `Unrated`, and no capacity/offer—not a zero score. Six–11 months remains manual-only even when a score can be calculated.

Apply adverse-conduct caps after the weighted sum: an unresolved post-grace arrears event or open reporting breach caps EngineScore at `58`; a default or at least 30 days past due caps it at `38`. The worst applicable cap wins. Persist factor inputs, normalized values, uncapped/capped scores, reasons, evidence/policy versions, and evaluation time for authorized replay. EngineScore and component points are restricted to authorized underwriting/Admin replay and must be omitted from public, Business, Investor, and Auditor Resources.

Minimum scorecard vectors include:

- Factors `100/100/100/100/50/50` produce EngineScore `90.0` and rating `4.5 Strong`.
- Coverage `1.40`, CFADS margin `20%`, relative MAD `15%`, `12/12` positive months, 12 months of history, and neutral conduct produce EngineScore `77.0` and rating `3.9 Stable`.
- Coverage `1.30`, CFADS margin `15%`, relative MAD `25%`, `10/12` positive months, 12 months of history, and neutral conduct produce EngineScore `58.666…` and rating `2.9 Weak`.
- An uncapped score `90` with an open post-grace arrears/reporting trigger becomes `58` and rating `2.9 Weak`; with default or at least 30 days past due it becomes `38` and rating `1.9 Distressed`.

The prototype's base `50`, years-in-operation coefficient/cap, sector points, margin coefficient/cap, and `40–92` clamp are rejected regression cases.

#### A.3.3 `target-dscr-v1` and authoritative operation order

Use TargetDSCR `1.25` for every launch category, with no sector modifier. DSCR is `CFADS ÷ exact average monthly repayment` for the actual schedule being evaluated: a computed new-capacity candidate, the Business's accepted principal at or below capacity, or an existing/current Note. It is never based on a user-requested amount because BR-10 prohibits the Business from requesting one.

For a substantive pre-issue schedule evaluation, `<1.00` declines, `1.00–<1.25` remains enhanced/manual, and `≥1.25` may enter the auto route only when every other BRS gate passes. If that pre-issue schedule is reduced from the manual band to a compliant principal, preserve its original manual branch. An existing/current Note is not retroactively declined; its recomputed DSCR feeds the approved monitoring/report/risk workflows. By contrast, a new maximum-capacity candidate that falls microscopically below `1.25` only because of nearest-franc quantization receives the minimum-franc safety correction below and is not reclassified as a substantive Tier-2 case; record reason `ROUNDING_GUARD`.

For a valid rating/tenor/CFADS calculation:

1. Resolve `TotalReturn` under `pricing-v1`.
2. With unquantized Decimal intermediates, compute `serviceable_total = (CFADS ÷ 1.25) × Tenor` and `theoretical_principal = serviceable_total ÷ (1 + TotalReturn)`.
3. Round the candidate principal half-up to the nearest franc.
4. Round `candidate_principal × TotalReturn` half-up to the nearest franc to obtain contractual return; total owed is their exact integer sum.
5. Compute the regular instalment by rounding `total owed ÷ Tenor` half-up to the nearest franc for the first `Tenor − 1` instalments; the final instalment is the exact residual. Tiering nevertheless uses exact average contractual repayment `total owed ÷ Tenor`, so presentation/residual position cannot change DSCR.
6. Recompute `projected_dscr = CFADS ÷ (total owed ÷ Tenor)`. If the new-capacity candidate is below `1.25` only because of monetary quantization, select the greatest whole-franc principal below it whose recomputed contractual return/schedule yields DSCR at least `1.25`. This is the maximum issuable principal; record the correction separately from substantive eligibility tiering.
7. Reject any accepted principal above that maximum. Recompute return, schedule, and DSCR for any accepted lower principal; it can only preserve or improve the DSCR tier.

Implementations may optimize the greatest-valid-principal search but must return the identical integer result. No binary floating point or display-rounded DSCR may participate in a threshold decision.

#### A.3.4 `risk-policy-v1`

- Health is derived from authoritative Coverage: `Healthy` at `≥1.25`, `Watch` at `1.00–<1.25`, and `Distressed` below `1.00`. An invalid denominator produces Health `Watch` and `Coverage unavailable`, never infinity, NaN, or fabricated zero.
- Business standing uses only `Good`, `Review`, `Restricted`, and `Suspended`.
- Note risk uses only `RB0 Routine`, `RB1 Enhanced monitoring`, `RB2 Intensive`, and `RB3 Default`; the worst currently valid trigger wins.
- Objective default is at least 90 days past due, or an earlier unlikely-to-pay event with the required dual approval and durable reasons/evidence.
- Probability of default is `null` with state `NOT_CALIBRATED` at launch, never `0%` or a fabricated estimate. A later numeric PD requires a new versioned, independently validated calibration decision and remains visible only to explicitly authorized Approver, Compliance, and Superadmin risk functions.
- Rating remains the sole public business-quality measure. Health, standing, risk band, and PD must be labelled as different operational indicators and may never be presented as substitute ratings.

Before activation, Finance/Risk, Compliance, and Legal must sign the complete trigger-to-standing/risk-band/action/cure matrix. No implementation may infer an unlisted trigger, cure period, or customer effect from the vocabulary alone.

#### A.3.5 `evidence-policy-v1`

- Require six complete consecutive calendar months in `Africa/Kigali`, with the latest complete month no more than 45 calendar days stale at submission. Revalidate that same freshness rule at calculation, offer, and issue; a result becomes stale rather than silently remaining eligible. Six–11 months is manual-only; 12+ may enter the auto route subject to every other gate. Require 12 months for seasonal/cyclical Businesses, restarted Businesses, and material-event re-underwriting. Retain up to 24 months; the latest approved window drives the current calculation while older evidence remains replayable, subject to D-40's eventual retention/deletion schedule.
- Production requires evidence from every declared active bank and MoMo rail. Bank/MoMo is primary; audited-cash/POS is supplementary for all categories and forces Audit Partner verification/reconciliation plus manual approval when it changes an underwriting cash-flow total. No other evidence type is approved in v1.
- One original bank or MoMo PDF can support an indicative Pulse calculation only when it covers the full required consecutive history. This never proves production evidence completeness across all declared active rails. Audited-cash-only or POS-only Pulse evidence creates a full/manual-review registration without an instant figure.
- Normalize into `[month_start, next_month_start)` Kigali calendar buckets. Store but exclude the current partial month; do not zero-fill or interpolate a gap. Reject foreign currency in v1 and retain exact RWF values.
- Treat a repeated content hash idempotently; deduplicate overlapping source transactions; exclude own-account transfers and debt proceeds from operating inflow; net linked reversals/refunds against the original; handle debt service separately; and count cash/POS activity only once after reconciliation. Unclassified or low-confidence items require confirmation and cannot silently support capacity.
- Retain immutable originals and hashes, source coverage, raw parser output, normalized versions, attributable corrections with reasons, parser/calculator/policy versions, and resubmission lineage. Corrections append versions and never overwrite history.

#### A.3.6 `pulse-presentation-v1` and `pulse-projection-v1`

Business Pulse uses the unrounded production-engine DSCR and does not alter BRS eligibility:

| Authoritative DSCR | Result contract |
|---:|---|
| `<1.00` | `Not currently eligible`; show no positive capacity or offer, retain the registration/evidence, and explain the next evidence or shortfall. |
| `1.00–<1.25` | `Potentially eligible — manual review required`; if the engine returns a positive figure, label it `Indicative capacity under review: RWF X` and place `MANUAL REVIEW REQUIRED` beside it, including on a shared pass. Never say offer, approved, or pre-approved. |
| `≥1.25` | `Indicatively pre-qualified`; show `Up to RWF X`, still subject to full verification, Audit Partner co-signature, KYB, and every policy gate. |

Displayed rounding never changes the branch. The summary may omit raw DSCR; if details show it, retain enough precision to explain why `1.249` remains manual.

Pulse must not manufacture a Tier-2 DSCR from a typed/requested amount. A newly computed maximum capacity normally finishes at or above `1.25` after `ROUNDING_GUARD`; the `1.00–<1.25` branch exists for a legitimate shared-engine evaluation of a substantive current/authorized schedule. Separate non-DSCR policy gates can also force manual review—for example, six–11 months of evidence—and every applicable manual reason must remain visible even when DSCR is at least `1.25`.

For Investor pledge principal `P`:

```text
return_min      = round_half_up(P × 0.100, nearest RWF)
return_max      = round_half_up(P × 0.150, nearest RWF)
gross_total_min = P + return_min
gross_total_max = P + return_max
```

Persist `P`, both rate bounds, all four endpoints, calculator version, and `pulse-projection-v1`. Label the values `Illustrative return if a later Note repays in full: RWF A–B` and `Illustrative gross repayment: RWF C–D`. Do not call the pledge result contractual: no Note or contract exists yet. Do not calculate a net amount without a real Note schedule; the 1% Investor repayment fee applies to each actual payout.

Required disclosure:

> Illustration only. Your pledge is non-binding and no funds are collected or invested. If you later invest in a Rozine Note that repays in full, its flat total return is set by the Business rating and 3, 6, 9, or 12-month term and will be 10.0%–15.0% of principal under the current policy. A 1% repayment fee applies to each payout. Capital and return are not guaranteed; actual receipts may be lower, including loss. This is not an annual rate or investment advice.

The prototype's single/fixed `13%` pledge projection is rejected. A sample card may show an exact rate only for a consented, genuinely pre-qualified Note with its own rating, term, and policy version.

### A.4 Golden-vector record format

Each vector must exist as a human-reviewed Markdown/JSON fixture with this minimum shape. The implementation may add fields but may not omit provenance or expected intermediates.

```yaml
id: GV-000
status: READY-TO-BASELINE | BASELINED | BLOCKED | QUARANTINED | REJECTED
purpose: Plain-language behavior being proved
policy_version: Exact immutable policy version
calculator_version: Exact deployed calculator version
source_requirements: [BR-00, FR-000]
evidence:
  - fixture_id: statement-fixture-id
    sha256: exact-content-hash
as_of_utc: 2026-01-01T00:00:00Z
inputs:
  normalized_periods:
    - period_start_utc: 2025-12-01T00:00:00Z
      period_end_utc: 2026-01-01T00:00:00Z
      display_timezone: Africa/Kigali
      inflow_rwf: 0
      outflow_rwf: 0
      existing_debt_service_rwf: 0
      owner_draw_rwf: 0
  coverage_period:
    period_start_utc: 2025-12-01T00:00:00Z
    period_end_utc: 2026-01-01T00:00:00Z
    inflow_rwf: 0
    outflow_rwf: 0
    debt_service_rwf: 0
  proposed_principal_rwf: null
  tenor_months: null
  policy_values: {}
expected:
  nocf_by_period_rwf: []
  trimmed_nocf_rwf: null
  cfads_rwf: null
  target_dscr: null
  proposed_monthly_repayment_rwf: null
  dscr: null
  capacity_rwf: null
  engine_score: null
  rating: null
  rating_band: null
  coverage: null
  health: null
  standing: null
  risk_band: null
  probability_of_default: null
  probability_of_default_state: NOT_CALIBRATED
  total_return_rate: null
  total_return_rwf: null
  instalments_rwf: []
  outcome_code: null
  disclosures: []
parity_targets: [domain, persistence, resource, inertia, api_v1, mobile_contract]
approvals:
  product: ROBERT_PENDING_SIGNATURE
  business: ROBERT_PENDING_SIGNATURE
  finance_risk: KIMANI_PENDING_SIGNATURE
  compliance: KIMANI_PENDING_SIGNATURE
  internal_legal: ROBERT_PENDING_SIGNATURE
  external_legal_regulatory: TBD_WHERE_REQUIRED
  internal_audit_operations: KIMANI_PENDING_SIGNATURE
  external_icpar_audit_partner: TBD
  engineering: AMINU_ERASTUS_PENDING_SIGNATURE
  security: AMINU_ERASTUS_PENDING_SIGNATURE
  independent_test: NON_AUTHOR_POOL_MEMBER_OR_EXTERNAL_DELEGATE_PENDING_SIGNATURE
  regulatory_cma_sandbox: TBD
```

### A.5 Minimum golden-vector matrix

| ID | Status | Inputs/condition | Expected result | Governs |
|---|---|---|---|---|
| `GV-001` | `READY-TO-BASELINE` | No verified statement/evidence. | No capacity or offer; reason code is stable and auditable. EngineScore/rating representation is deliberately out of scope and covered by `GV-037`. | BR-10, BR-15 |
| `GV-002` | `READY-TO-BASELINE` | Exactly 5, 6, 11, and 12 complete consecutive months; repeat with a missing month, current partial month, latest complete month 46 days stale, and a seasonal/restarted/material-event case. | Five months, a gap, or staleness produces no capacity/rating/yield while retaining evidence and scheduling rescore. Six–11 months is manual-only. Twelve months permits the auto route subject to every other gate and is mandatory for the special cases. | BR-15–BR-16, D-14 |
| `GV-003` | `READY-TO-BASELINE` | One accepted fixture with known normalized inflows/outflows and content hash. | Parsed/confirmed values trace to immutable evidence; a correction creates a version rather than overwriting source/parser output. | FR-201–FR-203, FR-701 |
| `GV-004` | `READY-TO-BASELINE` | Two otherwise identical Businesses; one has verified external/Rozine debt service. | Debt-inclusive CFADS/capacity is lower or equal and the difference is reconstructible. | BR-12 |
| `GV-005` | `BLOCKED` | Series with one exceptional positive NOCF month. | Exceptional month cannot inflate the offer under the approved `UW-08` trim algorithm. Numeric output awaits that algorithm. | BR-13 |
| `GV-006` | `BLOCKED` | Same normalized cash flow with and without a verified OwnerDraw. | CFADS changes by the approved OwnerDraw amount/period treatment. Numeric fixture awaits `UW-06`. | Section 12 |
| `GV-007` | `READY-TO-BASELINE` | Precomputed authoritative DSCR = `0.99`. | Decline; no capacity offer or listing path. | BR-14 |
| `GV-008` | `READY-TO-BASELINE` | DSCR = `1.00`. | Enhanced procedures and manual approval required; never auto-approved. | BR-14 |
| `GV-009` | `READY-TO-BASELINE` | Internal DSCR = `1.249`. | Enhanced procedures and manual approval required. Display rounding cannot move it into the auto tier. | BR-14 |
| `GV-010` | `READY-TO-BASELINE` | DSCR = `1.25`. | May auto-approve only when co-signature, KYB, and every other eligibility gate pass. | BR-14 |
| `GV-011` | `READY-TO-BASELINE` | A valid substantive existing/current or authorized pre-issue schedule—not a user request or quantization-only edge—produces DSCR in `1.00–<1.25`. | Preserve enhanced/manual status, reduce any pre-issue principal to the greatest whole-franc amount whose final contractual schedule yields DSCR `≥1.25`, and show the original reason plus reduced capacity. | `UW-14`, D-11B |
| `GV-012` | `READY-TO-BASELINE` | Offer capacity `C`; attempted accepted amount or Live listing is `C + 1` franc. | Server rejects; no client, Admin path, or race can list above `C` except the separately governed permanent Superadmin override. | BR-10–BR-11, BR-17 |
| `GV-013` | `READY-TO-BASELINE` | Tenor = `4` months; repeat for every value outside 3/6/9/12. | Validation/domain rejection with stable code; no calculation/listing occurs. | BR-20 |
| `GV-014` | `READY-TO-BASELINE` | Total-return candidates `9.9%`, `10.0%`, `15.0%`, `15.1%`. | `9.9%` and `15.1%` rejected; `10.0%` and `15.0%` are within bounds; no user-facing APR. | BR-21–BR-22 |
| `GV-015` | `READY-TO-BASELINE` | Price published ratings `5.0/4.0/3.0/2.9/2.8/2.0` at 3/6/9/12 months through repeated/domain/web/API/mobile calculations. | Every layer exactly matches the `pricing-v1` matrix in A.3.1, including floor/cap and explanation, with no prototype coefficient or fixed 13% path. | BR-23, FR-602, D-11 |
| `GV-016` | `READY-TO-BASELINE` | Ratings `4.0`, `3.9`, `3.0`, `2.9`, `2.0`, `1.9`. | Strong, Stable, Stable, Weak, Weak, Distressed respectively; word then one-decimal number then color. | BR-31–BR-34 |
| `GV-017` | `READY-TO-BASELINE` | EngineScores below `0`, exactly `0`, `77`, `79`, `80`, `100`, and above `100`. | Clamp rating to `0.0–5.0`; half-up gives `77 ÷ 20 = 3.85 → 3.9 Stable`, `79 ÷ 20 = 3.95 → 4.0 Strong`, and `80 → 4.0 Strong`. | Section 12, `UW-12`, D-11 |
| `GV-018` | `BLOCKED` | Coverage period inflow `10,000,000`; outflow `6,000,000`; debt service `500,000`. | Raw Coverage is `1.538461538…` and Health is `Healthy`; only persisted/display numeric precision remains blocked by `UW-13`. | Coverage formula, D-11C |
| `GV-019` | `READY-TO-BASELINE` | Coverage denominator is zero, negative, or missing. | Health is `Watch`, Coverage is unavailable, a stable reason/disclosure is emitted, and no layer emits infinity, NaN, or fabricated zero. | Coverage formula, FR-602, D-11C |
| `GV-020` | `READY-TO-BASELINE` | Principal `5,000`, total return `10.0%`, tenor `3`. | Total return `500`; total owed `5,500`; instalments `[1,833, 1,833, 1,834]`, with the residual in the final instalment. | BR-24, FR-700 |
| `GV-021` | `READY-TO-BASELINE` | `GV-020` is paid off immediately after the first scheduled instalment of `1,833` is paid. | Lifetime contractual total remains `5,500`; amount already paid is `1,833`; remaining early-payoff contractual amount is `3,667` before any separately governed fee. Early repayment does not reduce the `500` return or charge it twice. | BR-25 |
| `GV-022` | `BLOCKED` | Previously declined Business receives a new verified statement that improves the approved score/capacity inputs. | Automatic rescore runs without reapplication; eligibility changes only if the still-open volatility trim/debt/OwnerDraw rules and product-approved score/capacity policies pass. Numeric output remains blocked by `UW-06`–`UW-08`. | BR-16 |
| `GV-023` | `READY-TO-BASELINE` | Note issued at `4.0 Strong`; later verified report produces `3.0 Stable`. | Issue rating remains immutable at `4.0 Strong`; live rating becomes `3.0 Stable`; holders see both with timestamps/policy versions. | BR-36–BR-37 |
| `GV-024` | `READY-TO-BASELINE` | Existing Note's live rating becomes `1.9 Distressed`; a new raise is attempted. | Existing Note enters active monitoring; new listing is rejected. | BR-35 |
| `GV-025` | `BLOCKED` | Identical approved statement fixture/policy sent through authenticated underwriting and Pulse Business pre-qualification. | Authoritative capacity, rating, and indicative yield match exactly; Pulse adds only approved non-binding presentation. Numeric end-to-end output still awaits concrete evidence plus the open `UW-06`–`UW-08` upstream rules—not D-11/D-11A–B. | BR-82, FR-501, FR-602 |
| `GV-026` | `READY-TO-BASELINE` | Pulse pledges of RWF `500,000` and RWF `5,005`, with no Note/rating/tenor selected. | Return/gross ranges are `50,000–75,000`/`550,000–575,000` and `501–751`/`5,506–5,756`; policy/version/disclosure are present and no single 13% result or net-payout estimate appears. | FR-500, D-19A |
| `GV-027` | `REJECTED` | A calculator uses a universal 10.5% floor, prototype `0.085`/`2.5` coefficients, fixed 13%, or any other superseded D-11 pricing path. | Regression fails. Only `pricing-v1` may determine authoritative return; a valid `10.5%` produced by that formula is not itself rejected. | C-03–C-04, D-11 |
| `GV-028` | `READY-TO-BASELINE` | Calculate under policy `P1`, activate `P2`, then replay the historical calculation and create a new one. | Historical result reproduces under `P1`; new result uses `P2`; neither silently overwrites the other. | FR-605, FR-702 |
| `GV-029` | `READY-TO-BASELINE` | Attempt to submit or derive TIN/tax-system data through upload metadata, parsed fields, request, Resource, log, fixture, or analytics. | Field is rejected/not collected and never persists or propagates; underwriting remains possible from permitted evidence. | DR-8, FR-200, AC-9 |
| `GV-030` | `BLOCKED` | Worked calculation-layer candidate in A.6 uses explicit normalized upstream assumptions. | The approved pricing/TargetDSCR/rounding arithmetic reproduces exactly, but promotion to an end-to-end evidence vector still awaits concrete evidence, the open `UW-06`–`UW-08` upstream rules, and `UW-13` Coverage precision. | Section 12, `UW-06`–`UW-08`, `UW-11`–`UW-14`, `UW-19`, `UW-21`, `UW-30` |
| `GV-031` | `READY-TO-BASELINE` | A.6 flows through TargetDSCR, return, theoretical/candidate/final capacity, exact schedule, and accepted principal at `C` and `C−1`. | Candidate RWF `12,845,674` fails at DSCR `1.249999913…`; `ROUNDING_GUARD` selects RWF `12,845,673` without creating a substantive manual case, and `C−1` recomputes without worsening DSCR. No displayed rounding changes the decision. | BR-10–BR-14, BR-24, D-11B |
| `GV-032` | `READY-TO-BASELINE` | Raw production DSCR `0.999`, `1.000`, `1.249`, and `1.250` passes through Pulse. | Results are ineligible/manual/manual/indicatively-pre-qualified. A positive manual figure carries `MANUAL REVIEW REQUIRED`; `1.249` remains manual even if display rounding shows `1.25`. | BR-14, BR-82, D-13 |
| `GV-033` | `REJECTED` | Pulse prototype EngineScore base `50` plus `min(years, 10) × 1.5`. | Regression fails: neither constant, cap, nor calendar-year contribution exists in `engine-score-v1`. | `UW-11`, D-11A |
| `GV-034` | `REJECTED` | Pulse prototype sector points: Agriculture `4`, Retail & trade `6`, Logistics `5`, Manufacturing `4`, Services `5`, Energy `3`, Other `2`. | Regression fails: sector points do not exist in `engine-score-v1`. | `UW-11`, D-11A |
| `GV-035` | `REJECTED` | Pulse prototype margin contribution `min(margin, 0.45) × 42`. | Regression fails: the prototype coefficient/cap does not exist; use the approved CFADS-margin normalization. | `UW-11`, D-11A |
| `GV-036` | `REJECTED` | Pulse prototype final EngineScore clamp `max(40, min(92, score))`. | Regression fails: the prototype clamp cannot replace the approved scorecard or BRS rating transform. | `UW-11`–`UW-12`, D-11A |
| `GV-037` | `READY-TO-BASELINE` | Missing mandatory evidence or fewer than six complete consecutive months. | `EngineScore = null`, rating `Unrated`, no capacity/offer, stable disclosure/Resource fields, retained evidence, and scheduled rescore; never represent missing evidence as score/rating zero. | BR-15, D-11A, D-14 |
| `GV-038` | `REJECTED` | Any calculator floors or rounds an authoritative monetary capacity, return, fee, schedule, wallet, ledger, or settlement result to RWF 100,000. | Regression fails: authoritative monetary results use the BRS-fixed nearest-franc rule. Any approved coarse UI summary is a separate labelled presentation field and never replaces, persists as, or feeds the authoritative value. | FR-700, C-05, `UW-21` |
| `GV-039` | `QUARANTINED` | Apply a RWF 5M–50M Note boundary or 35% annual-revenue capacity ceiling. | Neither constraint reaches production unless D-12 explicitly approves it under a new policy version; no code may infer it from a prototype or brief. | C-07–C-08, `UW-17`, D-12 |
| `GV-040` | `READY-TO-BASELINE` | Principal RWF `5,005`, published rating `4.0`, tenor 6 months. | Rate `12.1%`; return RWF `606`; total owed RWF `5,611`; instalments `[935, 935, 935, 935, 935, 936]`, with exact cross-layer parity. | BR-20–BR-24, D-11 |
| `GV-041` | `READY-TO-BASELINE` | The three explicit normalized scorecard cases in A.3.2. | Scores/ratings are exactly `90.0/4.5 Strong`, `77.0/3.9 Stable`, and `58.666…/2.9 Weak`; all normalized inputs/intermediates and the policy version are replayable but not publicly exposed. | BR-30–BR-34, D-11A |
| `GV-042` | `READY-TO-BASELINE` | One bank/MoMo PDF covers exactly six complete months; repeat with multiple declared active rails and with audited-cash-only/POS-only Pulse evidence. | The single bank/MoMo document supports only an indicative, manual-at-six-month Pulse result and does not satisfy production completeness when other active rails exist; audited-cash/POS-only creates a full/manual-review registration without an instant figure. | FR-201, FR-501, D-14 |
| `GV-043` | `READY-TO-BASELINE` | Repeated content hash, overlapping bank/MoMo/POS transaction, own-account transfer, linked reversal, debt proceeds, and a corrected parser classification. | Duplicate ingest is idempotent; overlap counts once; transfer/debt proceeds are excluded from operating inflow; reversal nets the original; correction appends a new normalized version while historical replay remains unchanged. | BR-13, FR-201–FR-203, FR-701, D-14 |
| `GV-044` | `READY-TO-BASELINE` | Uncapped EngineScore `90` with (a) post-grace arrears/open reporting breach and (b) default or at least 30 days past due. | Case (a) caps at `58 → 2.9 Weak`; case (b) caps at `38 → 1.9 Distressed`; worst applicable cap wins and every trigger/reason is durable. | BR-30–BR-37, D-11A |
| `GV-045` | `READY-TO-BASELINE` | Coverage `1.25`, `1.249999`, `1.00`, `0.999999`, and invalid/missing denominator; inspect all audience Resources. | Health is Healthy/Watch/Watch/Distressed/Watch-Unavailable. Launch PD is `null/NOT_CALIBRATED`, never `0%`; numeric EngineScore/components and any future PD are absent from unauthorized Resources. | FR-209, FR-409, D-11C |
| `GV-046` | `BLOCKED` | Each standing/risk trigger, competing triggers, cure, and action passes through `risk-policy-v1`. | Worst-trigger precedence and the 90-DPD/dual-approved-unlikely-to-pay default backstop are fixed; exact Good/Review/Restricted/Suspended and RB0/RB1/RB2/RB3 transitions/actions await the signed activation matrix required by A.3.4. | FR-209, FR-409, D-11C |
| `GV-047` | `READY-TO-BASELINE` | Pulse pledge output omits any required non-binding/no-funds/risk/fee wording or uses `APR`, `expected`, `guaranteed`, net proceeds, or one fixed `13%` projection. | Content regression fails; only the approved range labels and complete A.3.6 disclosure pass. | FR-500, D-19A |
| `GV-048` | `READY-TO-BASELINE` | A pass/projection is created under policy `P1`, policy `P2` activates, then the old record is replayed and a new pledge is created. | The old record reproduces exactly under `P1`; the new pledge uses `P2`; neither result or disclosure is silently rewritten. | FR-605, FR-702, D-19A |

### A.6 Worked product-approved calculation vector — downstream ready, end-to-end blocked

This vector proves the product-approved downstream calculation order and its one-franc DSCR safety edge. Its six identical periods, classifications, debt service, OwnerDraw, trimmed NOCF, and precomputed EngineScore are explicit normalized fixture assumptions; it does not choose the still-open `UW-06`–`UW-08` upstream rules or independently prove the scorecard. The arithmetic is ready to baseline under D-11/D-11A–B, but promotion to an end-to-end golden vector still requires concrete immutable evidence, hashes, calculator/policy versions, and every named sign-off.

| Input/policy field | Candidate value |
|---|---:|
| Verified monthly inflows | RWF 10,000,000 for each of 6 complete months |
| Verified monthly outflows | RWF 6,000,000 for each of 6 complete months |
| Monthly NOCF | RWF 4,000,000 for each month |
| Normalized trimmed mean fixture | RWF 4,000,000 |
| Existing monthly debt service | RWF 500,000 |
| Monthly OwnerDraw | RWF 500,000 |
| CFADS | RWF 3,000,000 |
| TargetDSCR | `1.25` under `target-dscr-v1` |
| Tenor | `6` months |
| Precomputed EngineScore fixture | `80` |
| Published rating | `4.0 Strong` |
| Total return | `12.1%` under `pricing-v1` |

Expected calculation-layer arithmetic:

```text
NOCF each month = 10,000,000 − 6,000,000
                = 4,000,000

CFADS = 4,000,000 − 500,000 − 500,000
      = 3,000,000

Rating = round_half_up(clamp(80 ÷ 20, 0.0, 5.0), 1 dp)
       = 4.0 Strong

TotalReturn = round_half_up(
    clamp(10.0 + ((5.0 − 4.0) × 1.6) + 0.5, 10.0, 15.0),
    1 dp
)
            = 12.1%

Serviceable total = (3,000,000 ÷ 1.25) × 6
                  = RWF 14,400,000

Theoretical principal = 14,400,000 ÷ 1.121
                      = RWF 12,845,673.505798…

Nearest-franc candidate principal = RWF 12,845,674
Candidate return = round_half_up(12,845,674 × 0.121)
                 = RWF 1,554,327
Candidate total owed = RWF 14,400,001
Candidate exact-average repayment = 14,400,001 ÷ 6
                                  = RWF 2,400,000.166666…
Candidate DSCR = 3,000,000 ÷ 2,400,000.166666…
               = 1.249999913194…
Candidate therefore fails the exact 1.25 target after monetary rounding.

Greatest compliant principal = RWF 12,845,673
Compliant return = round_half_up(12,845,673 × 0.121)
                 = RWF 1,554,326
Compliant total owed = RWF 14,399,999
Compliant schedule = [2,400,000, 2,400,000, 2,400,000,
                      2,400,000, 2,400,000, 2,399,999]
Compliant exact-average repayment = 14,399,999 ÷ 6
                                  = RWF 2,399,999.833333…
Compliant DSCR = 3,000,000 ÷ 2,399,999.833333…
               = 1.250000086806…

Accepted principal C − 1 = RWF 12,845,672
Recomputed return = RWF 1,554,326
Recomputed total owed = RWF 14,399,998
Recomputed DSCR = 1.250000173611…; it does not worsen the tier.

Raw Coverage = 10,000,000 ÷ (6,000,000 + 500,000)
             = 1.538461538…
Health = Healthy
Coverage display precision remains blocked by UW-13, but it cannot change Health.
```

### A.7 Required execution layers

Every `READY-TO-BASELINE` vector must first receive concrete inputs, evidence hashes, versions, expected intermediates, and independent approvals. Every vector promoted to `BASELINED` must then prove the same outcome at all applicable layers:

| Layer | Required evidence |
|---|---|
| Domain calculator | Pure deterministic unit result with no database, clock, locale, or transport dependency. |
| Persistence | Inputs, intermediates, result, policy/calculator versions, and evidence hashes round-trip exactly. |
| Application action | Authorization, state gates, idempotency where relevant, stable outcome/reason codes, and durable event. |
| Eloquent API Resource | Authorized values and omissions are stable; no recalculation occurs inside the Resource. |
| Inertia web | Resource-derived values, explanations, disclosures, and formatting match the domain result. |
| `/api/v1` mobile contract | Same Resource facts and stable codes for the same actor/context/policy. |
| Mobile client | No authoritative local recomputation; offline display identifies cached policy/result age. |
| Admin replay | Authorized operator can reproduce the result from stored inputs without editing history. |
| Observability | Correlation and outcome/policy codes exist without raw statement data, secrets, or prohibited identifiers in logs. |

### A.8 Change-control gate

Any change to evidence normalization, EngineScore, TargetDSCR, capacity, rating, health/risk, return, rounding, or Pulse projection must:

1. Formally amend the BRS before changing any `FIXED` invariant; a policy version alone cannot override the BRS.
2. Create a new policy version and decision record for the approved change.
3. Add or update affected vectors without modifying historical expected results in place.
4. Run the full vector suite across every required execution layer.
5. Produce an impact report for outstanding offers, Live Notes, existing schedules, Pulse passes, and historical disclosures.
6. State explicitly whether recalculation is prohibited, prospective, or requires an approved migration/amendment.
7. Receive named Business, Product, Finance/Risk, Compliance, Legal, internal Audit Operations, applicable external ICPAR/Audit Partner, Engineering, Security, independent-test, and applicable CMA/sandbox approval before activation.

## 17. Plan validation checklist

- [x] Every active Phase 0–8 has a title, status, goal, checklist, deliverables, acceptance criteria, and verification/exit gate; Work Packages L0–L12 are explicitly non-governing requirement banks.
- [x] Every active phase has a conditional two-developer AI-assisted active-engineering duration, confidence level, and scheduling note; Section 9.1 records the MVP portfolio windows, overlap controls, external waits, and post-launch observation separately.
- [x] Business, Investor, Auditor, Admin, and launcher experiences are included in the responsive-web/PWA MVP; native role applications are an explicit post-MVP phase.
- [x] Investor-to-Investor secondary trading is non-deferrable MVP scope, is distributed across Phases 1–3, and retains eligibility, disclosure, reservation, concurrency, fee, halt, atomic on-platform settlement, reconciliation, and prohibited-principal-path gates.
- [x] Required compliance, treasury, supervisor, provider, and operational dependencies are included; Pulse is retained as explicit post-MVP BRS scope.
- [x] The confirmed Laravel/Inertia MVP, versioned post-MVP mobile API, shared action layer, and shared Eloquent Resource architecture are explicit.
- [x] All 75 current planning inputs—including the MVP Specification, the new standalone website reference, Robert's 31 new-logo SVGs, and 31 matching PNGs—are accounted for, and the former six-JPEG and legacy logo families are recorded as superseded.
- [x] Source conflicts are explicit and are not silently blended.
- [x] MVP acceptance families, BRS requirement families, and former Work Packages L0–L12 are crosswalked to active phases.
- [ ] The Phase 0 register maps every individual BO/BR/FR/NFR/IR/CR/AC ID to a checklist item, deliverable, test/evidence owner, and acceptance gate.
- [x] Financial integrity, offline evidence, authorization, privacy, accessibility, localization, observability, testing, and release promotion are cross-phase gates.
- [x] Every active phase explicitly carries the Section 11 quality contract: 100.0% `app/` PHP lines; 100.0% client lines/statements/functions globally and per file; 100.0% critical branches globally and per file; 95.0% non-critical branches globally and at least 90.0% per file; 100.0% newly changed branches; reviewed source/risk manifests; architecture/static/build gates; local impacted-test acceleration only; and exact-SHA complete clean-checkout phase/release evidence for web and applicable native targets.
- [x] Appendix A provides the underwriting policy worksheet, vector record schema, minimum vector matrix, candidate arithmetic example, layer-parity gate, and policy change control.
- [ ] Appendix A's decision rows, `READY-TO-BASELINE`, `BLOCKED`, and `QUARANTINED` vectors have approved dispositions, owners, concrete fixtures, hashes, and sign-offs.
- [ ] Product and regulatory owners have answered the red questions and approved the governing defaults.
- [ ] Phase 0 reproducible-baseline evidence is green.

## 18. Reference links

- [Laravel 13 Eloquent API Resources](https://laravel.com/docs/13.x/eloquent-resources)
- [Pest test coverage and threshold enforcement](https://pestphp.com/docs/test-coverage)
- [Pest architecture testing](https://pestphp.com/docs/arch-testing)
- [Pest TIA engine](https://pestphp.com/docs/tia)
- [Pest 5 first-party PHPStan plugin](https://pestphp.com/docs/pest5-now-available#content-first-party-phpstan-plugin)
- [Pest PHPStan configuration](https://pestphp.com/docs/phpstan)
- [Vitest coverage configuration](https://vitest.dev/guide/coverage.html)
- [Vitest coverage thresholds](https://vitest.dev/config/coverage.html)
- [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/)
- [Reference phased-plan structure](https://github.com/hussain4real/AAC/blob/main/docs/MAACC_Phased_Implementation_Plan.md)
