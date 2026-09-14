# Deferred scope and retained MVP boundaries

**Status:** `REGISTERED — NOT PRODUCT APPROVAL` · **Version:** 1.0 · **Date:** 2026-09-10

**Goal:** Preserve every PDF exclusion and former-plan item routed to Phases 5–8, name its destination and owner, and prevent a deferral from silently removing mandatory BRS controls or authorizing a future product.

## Authority and status

Sources: [archived August 2026 MVP PDF](../Rozine%20MVP%20Spec.pdf), pages 2/6/8/11/13; [active plan](../Rozine_Phased_Implementation_Plan.md), Phases 5–8, Section 10 and retained Work Packages L0–L12; [BRS](../Rozine-BRS.md); [source authority](source-authority-record.md); [MVP crosswalk](mvp-crosswalk.md). PDF SHA-256: `5019b5f6a53e44d5f42b539c77429113c1eb5c18c6392b0a0916b0e9dc18642a`.

- `DEFERRED PLAN` means the plan assigns a later delivery phase; it is not accepted implementation or permission to activate the capability.
- `PROPOSED DEFERRAL` means a required scope signature remains open. In particular, D-61 has **not** made Plus criteria `DEFERRED — NOT MVP-APPLICABLE`, and D-62 remains the Pulse planning default awaiting confirmation.
- `DISCOVERY ONLY` means an excluded idea has a possible evaluation destination, not a committed implementation phase or release date.
- `PROHIBITED` means no implementation without a formal replacement of the governing BRS/legal invariant. Calling it Phase 7 does not relax the prohibition.
- `RETAINED MVP` means the literal exclusion conflicts with the BRS or an approved decision; the named core capability cannot be deferred.

Owners use the plan's existing pool: **A** Aminu (core/actions/Resources/server tests), **E** Erastus (client/native/browser evidence), **K** Kimani (Finance/Risk, Compliance, Audit Operations), **Rbt** Robert (Product, internal Legal, Brand). A/E are jointly accountable for Engineering/Security; the actual native implementation assignment and estimate must still be entered under D-04/D-03. Every approval record needs named signers; non-author independent-test approval and required external CMA/legal/provider/ICPAR/security assurance remain separate under D-71.

## Every “Not in MVP” source bullet — 20 entries

Each source section contains four bullets, including repeated exclusions. Repeats are retained as separate IDs so source completeness can be audited. Compound bullets preserve each component explicitly. The destination references an owned tranche below; source exclusions do not by themselves authorize that tranche.

| ID | PDF page / exact exclusion scope | Disposition and destination | MVP boundary / required approval | Owners | Status |
|---|---|---|---|---|---|
| DFR-PDF-01 | 2 · No public API for institutions | E-03, Phase 7 evaluation | Public developer/institutional API product only. Shared internal/versioned `/api/v1`, Resources, authorization and D-04 companion endpoints stay in MVP where needed | A/E/K/Rbt | DISCOVERY ONLY; internal contracts RETAINED MVP |
| DFR-PDF-02 | 2 · No native app stores; web only | N-01–N-06, Phase 5 | Full role-native/store distribution later; **D-04 Option B narrow secure-capture companion is already an MVP exception**, not deferrable by this bullet. Its secure distribution/device proof must fit the rebaseline | A/E/K/Rbt | DEFERRED PLAN; D-04 exception RETAINED MVP |
| DFR-PDF-03 | 2 · No Kinyarwanda beyond public pages | No blanket language deferral; D-07 launch-language decision | BRS NFR-9 and plan require externalized strings and Kinyarwanda/English/French support. Required first-pilot/production translations must be signed by D-07; later expansion is S-06 | A/E/Rbt | RETAINED MVP baseline; language rollout GATED |
| DFR-PDF-04 | 2 · No multi-currency, multi-country, or FX | S-05, Phase 8 separate discovery: (a) currencies, (b) jurisdictions, (c) FX | RWF/Rwanda baseline retained. Each component needs its own legal/regulatory/accounting/custody/identity charter, not an infrastructure flag | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-05 | 6 · No concurrent raises per Business | E-02, Phase 7 separate product evaluation | No concurrent-raise feature authorized; preserve existing obligations in capacity and approved active-note rules. BRS policy changes require approval | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-06 | 6 · No invoice or inventory financing | E-02, Phase 7 separate evaluations: (a) invoice finance, (b) inventory finance | New underwriting/servicing instruments; normal stock evidence within current Auditor/Business reporting remains MVP | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-07 | 6 · No accounting-software integration | E-02, Phase 7 evaluation | Approved statement upload, parsing, manual correction and required payment/registry adapters remain MVP | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-08 | 6 · No messaging beyond the CPA thread | E-02, Phase 7 expanded conversation evaluation | Business–CPA coordination stays MVP, as do BRS FR-110/416 notices, targeted broadcasts, required legal/operational messages and support/dispute paths | A/E/K/Rbt | DISCOVERY ONLY; mandatory notices RETAINED MVP |
| DFR-PDF-09 | 8 · No partner-to-partner handover mid-visit | E-04, Phase 7 evaluation | This does not remove conflict declaration, lawful Admin reassignment, revocation or recovery required by FR-312/403 | A/E/K | DISCOVERY ONLY |
| DFR-PDF-10 | 8 · No automated bank-feed pull | E-04 with E-02, Phase 7 evaluation | Required bank/MoMo payment/reconciliation integration and uploaded statement comparison stay MVP; reconcile-feed screen cannot imply uncontracted automatic pull | A/E/K | DISCOVERY ONLY |
| DFR-PDF-11 | 8 · No ICPAR CPD credit filing | E-04, Phase 7 evaluation | External CPD submission excluded; BRS Auditor academy, procedure training/completion and licence checks remain MVP | A/E/K | DISCOVERY ONLY |
| DFR-PDF-12 | 8 · No desktop-only review tooling | E-04, Phase 7 advanced tooling evaluation | Responsive Auditor review/checklist/reconcile/file screens and Admin oversight remain MVP; no blanket exclusion of desktop access | A/E/K | DISCOVERY ONLY |
| DFR-PDF-13 | 11 · No public API | E-03, Phase 7 evaluation; duplicate source of DFR-PDF-01 | Excludes public API product, not internal core/mobile contracts or required supervisor read-only access | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-14 | 11 · No discretionary managed portfolios | E-06, Phase 7 only after separate authority | Neither rule-based Plus nor staff powers may create discretionary investment authority. Separate mandate/suitability/legal/regulatory authorization required | A/E/K/Rbt | PROHIBITED until separately authorized |
| DFR-PDF-15 | 11 · No in-app fund transfers between Investors | E-06, Phase 7 only after separate authority | Direct wallet-to-wallet transfers prohibited; **atomic cash consideration for an eligible peer-to-peer secondary trade stays MVP** under D-60/BR-73 | A/E/K/Rbt | PROHIBITED direct transfers; secondary RETAINED MVP |
| DFR-PDF-16 | 11 · No multi-currency holdings | S-05, Phase 8 separate discovery; duplicate currency component of DFR-PDF-04 | RWF holdings/ledger unchanged; no silent currency dimension, conversion or FX balances | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-17 | 13 · No automated collections dialler | E-05, Phase 7 evaluation | Approved arrears notices, manual controlled collections, complaints, recovery cases and escalation remain MVP | A/E/K/Rbt | DISCOVERY ONLY |
| DFR-PDF-18 | 13 · No bulk data import | E-05, Phase 7 evaluation | Optional general operator import excluded; ordinary approved statement ingestion, migrations and isolated deterministic test/demo seeding remain necessary MVP implementation work | A/E/K | DISCOVERY ONLY |
| DFR-PDF-19 | 13 · No configurable workflow builder | E-05, Phase 7 evaluation | BRS state machines, versioned policy, approval queues and immutable Admin controls remain MVP, not an optional workflow-builder project | A/E/K | DISCOVERY ONLY |
| DFR-PDF-20 | 13 · No third-party access accounts | E-03/E-05 optional extensions only | **BRS FR-418/CR-12 supervisor read-only seat is retained in Phases 3–4.** No new third-party developer/team account model without approved scope/consent/access controls | A/E/K/Rbt | RETAINED MVP supervisor; extensions DISCOVERY ONLY |

## Post-MVP destination tranches and release conditions

For every row below, automated evidence means actual tests/report paths on the exact approved server/client commits, with policy/fixture versions. Witnessed evidence means recorded acceptance by an eligible non-author reviewer and applicable domain owner, not an AI statement or a checkbox in this document. All tranches inherit Section 11 full PHP and applicable web/native D-65/D-66/D-67 coverage, risk/source manifests, immutable base/head changed-branch reports, architecture/static/build, and governed PR promotion. They also require migrations/data compatibility, observability, operations/training and rollback or an explicit irreversibility treatment. Nothing here approves a dependency change or native stack.

| Tranche | Phase / preserved scope | Authority and entry gate | Automated / witnessed exit evidence | Owners | Status |
|---|---|---|---|---|---|
| N-01 | 5 · Native stack, role packaging, OS/device support, API support/deprecation, store ownership | D-03; stable Phase 3 contracts and Phase 4 production-compatible providers | Stack decision with runner/source-map proof including unimported source and every bridge/target; missing metrics block selection; independent stack/device-matrix review | A/E/K/Rbt | DEFERRED PLAN |
| N-02 | 5 · Versioned API, auth/token/device, errors, pagination, idempotency/concurrency, offline, forced-update compatibility | Shared Laravel actions/policies/Resources; no duplicated domain; L1/L2 | Inertia/API authorized-fact parity, deny/expiry/revocation/replay/version-contract tests; witnessed token/device replacement and recovery | A/E/K | DEFERRED PLAN; required D-04 seams earlier |
| N-03 | 5 · Full native Investor and Business journeys | D-03 role sequence; L1/L2/L7; existing Phase 1–4 domain behavior | Full journey/gated/error/offline/refresh parity and native accessibility tests; independent real-device investment/business flow | A/E/K/Rbt | DEFERRED PLAN |
| N-04 | 5 · Full native Auditor client/device assurance beyond narrow companion | D-04/D-03; L4; never defer already required evidence integrity | Device/biometric/camera/location/encrypted queue/remote-wipe failure tests; supported-device offline/process/storage/clock/permission/security review | A/E/K | DEFERRED PLAN only beyond MVP exception |
| N-05 | 5 · Push/deep links, preferences/required notices, delivery evidence, diagnostics/minimum version | Identity/privacy/required-notice policy and compatible API; L2/L12 | Authorized links, delivery/retry and update/rollback tests; device background/battery/upgrade/downgrade/forced-update/support walkthrough | A/E/K/Rbt | DEFERRED PLAN; companion-essential controls earlier |
| N-06 | 5 · Per-bundle identity/assets, signing, store metadata/privacy, staged rollout and monitoring | Approved canonical masters/rights and D-03 store/app matrix; L1/L12 | iOS opaque source, Android adaptive/monochrome, notification/splash/store art/build validation; witnessed light/dark/accessibility/store review and staged crash/rollback drill | A/E/Rbt/K | DEFERRED PLAN |
| P-01 | 6 · Pulse production-engine consumption, evidence and Business eligibility; Investor pledge illustration | D-62 confirmation; activated Appendix A/D-13/14/19/19A; BR-80–84/FR-500–505/AC-11 | Golden vectors and raw DSCR boundary/parity tests across Resource/web/API; witnessed non-binding input/result disclosures with no funds/instrument/obligation | A/E/K/Rbt | PROPOSED DEFERRAL; existing foundation not acceptance |
| P-02 | 6 · Persistent unique registrations/sequences, truthful counters, consented rotating sample feed | D-62; L11, C-11/12; consent/privacy policy | Concurrent unique sequence and aggregate reconciliation; production cannot fall back to random/fixture activity; consent/freshness review | A/E/K/Rbt | PROPOSED DEFERRAL |
| P-03 | 6 · Minimal-PII expiring/revocable passes, safe sharing and duplicate-free authenticated conversion | D-62; L11 identity/consent/abuse contracts | Enumeration/expiry/revocation/rate-limit and duplicate-Party tests; witnessed registration→pass→onboarding with retained consent/provenance and privacy-safe funnel | A/E/K/Rbt | PROPOSED DEFERRAL |
| P-04 | 6 · Truthful branded responsive Pulse, content/prototype retirement and isolation | D-62; approved brand, D-07 languages, rejected-claim register | Upload/privacy/abuse/claim scans and mobile/desktop accessibility/overflow/3G checks; witnessed AC-11 and Appendix A Pulse vectors; no unproven bank-grade/encryption/safety/fixed-13% claims | A/E/K/Rbt | PROPOSED DEFERRAL |
| E-01 | 7 · Executable Plus bands and rule-based mandates | **Signed D-61 required before removing MVP Investor AC-07/08**; full fee/suitability/concentration/legal/regulatory policy; C-29 | Approved threshold/hold/loss/prospective-fee, preview/pause/cancel/outcome and cap-race tests; witnessed exact charge and mandate consent. Otherwise rebaseline Phase 3 before implementation | A/E/K/Rbt | PROPOSED DEFERRAL; not MVP-exempt yet |
| E-02 | 7 · Concurrent raises; invoice/inventory products; accounting integration; expanded messaging/feeds | Separate charter per product, underwriting/servicing/data/consent/provider amendment | Product-specific eligibility, fee, evidence, repayment, failure and regression tests; K/Rbt and required external legal/provider approval | A/E/K/Rbt | DISCOVERY ONLY |
| E-03 | 7 · Public/institutional API, optional external/team/maker-checker accounts, contracts/rate limits/support | Separate scope and security/consent/legal approval; existing required organization/mandate policy is D-05/08/64 MVP decision | Cross-tenant denial, consent, scoped token/version/rate-limit tests; independent API/security/support readiness and client-contract review | A/E/K/Rbt | DISCOVERY ONLY |
| E-04 | 7 · Auditor handover, automatic feed pull, CPD filing, advanced desktop tooling | Separate professional-standard/evidence-integrity and provider charters | Handover chain, immutable evidence/identity, integration failure tests; Audit Operations and applicable ICPAR approval | A/E/K | DISCOVERY ONLY |
| E-05 | 7 · Collections automation, general bulk import, workflow builder, advanced analytics/external access | Optional extension only; cannot relocate FR-400–418, basic BO metrics, supervisor or required immutable controls | Permission/approval, import validation/idempotency, provenance and rollback tests; independent Operations/Compliance acceptance | A/E/K/Rbt | DISCOVERY ONLY |
| E-06 | 7 · Discretionary managed portfolios and direct Investor wallet transfers | Separate legal/regulatory/product authority explicitly changing prohibitions | Approved mandate/custody/consent/AML/funds-conservation tests and external authorization before activation | A/E/K/Rbt | PROHIBITED until separately authorized |
| E-07 | 7 · Principal inventory/market making/price setting; reserve cover; rejected fee models | C-26–28/BR-61/62/75 prohibit current-product behavior. Only formal BRS/legal replacement can authorize reconsideration | Until amended, negative tests assert no such routes/fees/claims; any future replacement needs independently certified financial/accounting/legal evidence | A/E/K/Rbt | PROHIBITED |
| E-08 | 7 · Complete approved-tranche delivery and change control | Each selected E tranche gets its own approved charter before work | Requirements/threat/data/policy/migration/Resource-compatibility/fixture/operations/monitoring/rollback pack; full exact-SHA regression gates and post-release KPI/policy-impact review | A/E/K/Rbt | REQUIRED IF A TRANCHE IS APPROVED |
| S-01 | 8 · Evidence-led SLOs, error budgets, capacity, cost, queue/DB/storage/provider limits | Phase 4 observed bottlenecks and explicit scale objective | Representative monthly audits/funding/payout/report/secondary/export load/soak/failure tests; independent measured objective review, no proportional manual-work workaround | A/E/K | DEFERRED PLAN |
| S-02 | 8 · Approved partition/cache/async/search/archive optimization | Measured S-01 bottleneck; preserve single authoritative financial/decision state | Reconciliation/concurrency/consistency/historical-replay and load tests; witnessed performance improvement with safe rollback | A/E/K | DEFERRED PLAN |
| S-03 | 8 · Mature observability/anomaly/fraud/reconciliation, recovery/incident/provider failover | Evidence-led extension of **already required MVP** monitoring/backup/restore/security controls | Alert, outage/chaos, restore/DR/provider failure and exact-money tests; witnessed incident command, RTO/RPO and support drills | A/E/K | DEFERRED PLAN; MVP baseline retained |
| S-04 | 8 · BO-1–BO-8 outcome refinement and governed policy/model recalibration | Durable source measures; named definitions/owners/targets/freshness/privacy; signed calibration authority | Golden vectors, historical replay, impact/metric reports, no default-derived revenue; domain/independent review before model activation | A/E/K/Rbt | DEFERRED PLAN; basic BO instrumentation retained |
| S-05 | 8 · Separate jurisdiction/currency/FX/instrument/identity/registry/licence programs | Formal Product/Finance/Risk/Compliance/Legal/Security/Engineering and applicable regulatory authorization per program | New accounting/ledger/identity/provider/policy compatibility and migration tests; external certification, authorized rollout and live monitoring | A/E/K/Rbt | DISCOVERY ONLY, not bundled with scale |
| S-06 | 8 · Continuous accessibility/localization/security/dependency/privacy/device/API/training maintenance | Approved live support/retention/privacy and change policies | Full affected behavior/security/accessibility/compatibility tests plus ongoing operational training and independent review | A/E/K/Rbt | CONTINUING OBLIGATION; not postponed MVP work |

## Exact active Phase 5–8 checklist coverage

`P<n>-C<nn>` is the stable register reference for a checklist item **in its original order on 2026-09-10**. The abbreviated clause names below identify the full clause in the active plan; they do not shorten its requirements. Future reordered/added plan bullets must retain/update this mapping explicitly. Each referenced tranche supplies owner, approval and automated/witnessed exit evidence. Phase-level Deliverables, Acceptance Criteria and Verification/exit gates apply in full to every approved tranche in that phase.

| Checklist ID | Preserved clause | Destination |
|---|---|---|
| P5-C01 | Stack, packaging/role strategy, supported devices, API window, store ownership | N-01 |
| P5-C02 | Approve stack-native runners and all global/per-file/risk-tier/changed-branch gates | N-01 |
| P5-C03 | Time-box unimported-source/bridge/target source-map and branch-metric proof | N-01 |
| P5-C04 | Native source/risk manifests; mixed and security/evidence/platform bridges critical | N-01/N-02/N-04 |
| P5-C05 | Independent per-target metrics; no iOS/Android/shared/bridge averaging | N-01 |
| P5-C06 | Versioned auth/token/device/error/pagination/replay/concurrency/offline/update contracts | N-02 |
| P5-C07 | Shared actions/policies/Resources/state machines/money/disclosure, no client formulas | N-02/N-03/N-04 |
| P5-C08 | Complete Investor/Business native journeys and Resource parity | N-03 |
| P5-C09 | Auditor binding/biometric/encrypted capture/location/sync/lost-device integrity | N-04; D-04 mandatory subset in MVP |
| P5-C10 | Push/deep-link/preferences/required notices/delivery/minimum-version/rollback/diagnostics | N-05 |
| P5-C11 | Canonical iOS/Android adaptive/monochrome/notification/splash/store assets | N-06 |
| P5-C12 | Signed per-bundle name/role/icon/splash/notification/screenshots/theme identity matrix | N-06 |
| P5-C13 | Real-device permission/storage/process/clock/network/a11y/battery/update matrix | N-03/N-04/N-05 |
| P5-C14 | Signing/privacy/store-review/staged rollout/crash-health/rollback | N-06 |
| P5-C15 | Full PHP and D-66/D-67 client/bridge gates plus real-device evidence | N-01–N-06 |
| P6-C01 | Replace prototypes with approved server underwriting action/Resource | P-01 |
| P6-C02 | Approved evidence and raw-DSCR ineligible/manual/indicative branches | P-01 |
| P6-C03 | Pledge range/rounding/no-funds/no-guarantee/fees/policy-version storage | P-01 |
| P6-C04 | Persistent privacy-safe real counters/sequences and consented rotating samples | P-02 |
| P6-C05 | Signed/unguessable expiring/revocable minimal-PII passes and abuse controls | P-03 |
| P6-C06 | Existing Party/onboarding conversion without duplicate identity/data re-entry | P-03 |
| P6-C07 | Remove tax/safety/guarantee/bank-grade/encryption/fixed-13% rejected claims | P-04 |
| P6-C08 | Responsive/a11y/performance/upload/bot/privacy/content/isolation tests | P-04 |
| P6-C09 | Approved primary Rozine lockup; role context only where approved | P-04 |
| P6-C10 | Full Pest/Vitest parity/abuse/privacy/authorization/architecture/coverage gates | P-01–P-04 |
| P7-C01 | Decide, price, legally review and version complete Plus/mandate policy | E-01 |
| P7-C02 | Separate concurrent-raise/invoice/inventory/accounting/feed/messaging products | E-02 |
| P7-C03 | Public/institutional API, third-party/team/maker-checker contracts and obligations | E-03 |
| P7-C04 | Auditor handover/feed/CPD/desktop integrity and professional standards | E-04 |
| P7-C05 | Collections/import/workflow/analytics/external supervisor extensions | E-05; required supervisor stays MVP |
| P7-C06 | Keep managed portfolios/direct Investor transfers prohibited pending authority | E-06 |
| P7-C07 | Keep principal/reserve/rejected fees prohibited pending formal BRS/legal amendment | E-07 |
| P7-C08 | Complete requirement/threat/data/policy/migration/API/fixture/ops/rollback tranche | E-08 |
| P7-C09 | Extend behavior/architecture tests; unchanged full PHP/client quality thresholds | E-08 and every selected E tranche |
| P8-C01 | SLO/error-budget/capacity/queue/DB/storage/provider/cost baselines | S-01 |
| P8-C02 | Monthly-peak/funding/payout/report/secondary/export/recovery load/soak/failure | S-01 |
| P8-C03 | Approved partition/cache/async/search/archive without duplicate mutable truth | S-02 |
| P8-C04 | Mature observation/fraud/reconciliation/backup/DR/incident/provider failover | S-03 |
| P8-C05 | BO-1–BO-8 owned definitions/sources/targets/freshness/privacy/dashboards | S-04 |
| P8-C06 | Governed model/policy recalibration, vectors/replay/impact and approvals | S-04 |
| P8-C07 | Separate country/currency/FX/instrument/identity/registry/licence programs | S-05 |
| P8-C08 | Continuous accessibility/localization/security/dependencies/privacy/compatibility/training | S-06 |
| P8-C09 | Extend performance/failure/architecture suites, no weakened PHP/client gates | S-01–S-06 |

## Former Work Packages — preserve the moved portion, not a wholesale deferral

The former L numbers are a requirement bank, not the active delivery order. Only the listed portion moves to a post-MVP tranche. Every other mandatory BRS capability in those packages stays at the active Phase 1–4 destination; the PDF's shorter screen list cannot remove it.

| Register ID | Former package / moved portion | Post-MVP destination | What explicitly stays in MVP |
|---|---|---|---|
| DFR-LEGACY-01 | L1 native consumption of API/Resource/error/compatibility contracts and platform assets | N-01/N-02/N-06, Phase 5 | Shared core contracts/exemplar, exact-money serialization, outbox refresh, web roles/components/brand/PWA assets, string catalogs and Resource-denial tests in 1–3; D-04 seams earlier |
| DFR-LEGACY-02 | L2 full native token/device/security journeys | N-02/N-05, Phase 5 | Party/mandates, Fortify sessions, MFA/step-up, authorization/consent/KYC/KYB, provider controls and act-as in 1–2; native capture identity/binding/revocation required by D-04 in MVP |
| DFR-LEGACY-03 | L3 Pulse consumption of approved engine and native consumer parity | P-01, Phase 6; N-02/N-03/N-04, Phase 5 | Authoritative underwriting, ingestion, golden vectors, Resources and recurring recalculation in 1–2; Appendix A activation cannot wait for Pulse |
| DFR-LEGACY-04 | L4 complete role-native Auditor UI/device assurance beyond D-04 subset | N-04, Phase 5 | Accreditation/MSA/ISRS, training/academy, fair dispatch, field capture/offline/seal/amendment, earnings and mandatory secure-capture device evidence in 1–2 |
| DFR-LEGACY-05 | L7 full native Investor browse/evidence/watchlist/commit/portfolio/education/settings | N-03, Phase 5 | Complete responsive primary journey/disclosures/limits/settlement/notifications in 1–2; no native-only domain rules |
| DFR-LEGACY-06 | L10 separately approved advanced operations beyond FR-400–418; later outcome/scale refinements | E-03/E-05/E-08, Phase 7; S-03/S-04, Phase 8 | All required Admin/compliance/treasury/supervisor controls, maker-checker, privacy/retention, policy history, immutable exports, baseline BO-1–BO-8 and monitoring in 1–4 |
| DFR-LEGACY-07 | L11 production-engine/evidence/pledge branches and policy reconciliation | P-01, Phase 6 | Shared underwriting/identity core in 1–2; no Pulse money or instrument issuance |
| DFR-LEGACY-08 | L11 name/contact/location/consent/unique sequence, counters and real rotating sample feed | P-02, Phase 6 | Core consent/privacy/identity controls in 1–2; no fake production activity tolerated meanwhile |
| DFR-LEGACY-09 | L11 signed shareable passes, handoff, privacy-safe funnel and abuse control | P-03, Phase 6 | Reuse existing Party, no duplicate identity; any native deep link additionally consumes N-05 |
| DFR-LEGACY-10 | L11 disclosures/claim removal, responsive UI, brand, environment isolation and prototype retirement | P-04, Phase 6 | Existing served code remains tested and subject to truthfulness/privacy controls; deferral never exempts executable source from quality gates |
| DFR-LEGACY-11 | L12 full role-native device/OS/accessibility/signing/store/deep-link/push/update/rollback evidence | N-01/N-03/N-04/N-05/N-06, Phase 5 | RC responsive/PWA/mandatory companion device/security/load/recovery proof in 1–3; live provider/regulatory/go-no-go/real lifecycle in 4 |
| DFR-LEGACY-12 | L12 Pulse-specific witnessed BRS AC-11 evidence | P-01–P-04, Phase 6 | BRS AC-1–AC-10/AC-12 accumulate in 1–3 and close applicable live obligations in 4; never claim full-BRS acceptance before AC-11 |
| DFR-LEGACY-13 | L12 further scale beyond accepted load/recovery baseline | S-01–S-06, Phase 8 | Existing representative-load, availability/RTO/RPO, restore/security/reconciliation/operator-training acceptance still gates MVP |
| DFR-LEGACY-14 | L0, L5, L6, L8, L9 — no additional post-MVP capability moved | No wholesale deferral: active Phases 0–4 | Governance; origination/listing; ledger/primary funds; servicing/reporting/payout; distress/dispute/recovery; **D-60 mandatory peer-to-peer secondary in Phase 3** |

## Approval and re-entry rules

1. The proposing owner names the precise register IDs and amended requirements, business rationale, release boundary, costs, risks and effective date. Each country/currency/instrument/product is a distinct decision, not a bulk Phase 7/8 sign-off.
2. K/Rbt and A/E review financial, legal, security, implementation and migration/compatibility consequences. Obtain required external authority; the internal pool cannot certify a provider or regulator decision.
3. Freeze applicable policy, schema/Resources, fixtures and automated/witnessed acceptance **before** activating an implementation tranche. Preserve immutable historical policy, event and evidence replay.
4. Maintain feature/rollout gates, migration and recovery plans, monitoring, operator responsibility and support. Follow `feat/* → dev → uat → main` with exact-SHA CI and non-author review at each hop; a signed charter is not deployment approval.
5. Attach actual acceptance evidence and independent signatures to the tranche. A single accepted tranche does not mark all of Phase 7's optional idea backlog complete, and no unsigned future idea blocks unrelated approved work.

## Checklist, deliverables and acceptance boundary

- [x] Every one of the 20 PDF exclusion bullets is present, including repeated and compound exclusions.
- [x] Every active Phase 5–8 checklist item has an owned destination (15 + 10 + 9 + 9 = 43).
- [x] The post-MVP portions of every former package are preserved with explicit retained-MVP boundaries; L11's whole Pulse scope is routed.
- [x] Native secure capture, internal APIs, language foundations, mandatory notices/academy, supervisor access and secondary cash settlement are protected from overbroad exclusion.
- [ ] Rbt/K and the required named co-signers approve D-61/D-62 and any other requested scope changes. No such signatures are manufactured here.
- [ ] Each selected future tranche receives its own charter, tests, witnessed proof and release approval before implementation/activation.

**Deliverables:** this versioned register, the linked per-ID MVP crosswalk, and hash-preserved PDF source. **Acceptance here is enumeration and traceability only.** Phase 0 remains open; no deferred feature, financial amendment, native stack, live provider or production release is approved by creating this document.
