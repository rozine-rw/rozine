# Rozine

**A capital marketplace for Rwanda's profitable private businesses.**

Profitable Rwandan SMEs raise collateral-free growth capital sized to their own verified cash flow. Ordinary Rwandans and institutions invest from RWF 5,000 into audited, cash-flow-verified notes paying a fixed **10–15% total return** over 3 to 12 months, with monthly CPA-attested proof of performance and a secondary market for early exit.

Rozine is not a lender. It takes no deposits, holds no credit risk on its balance sheet, and never intermediates money as principal. It operates the infrastructure — underwriting, verification, listing, servicing, reporting and liquidity — and earns thin, transparent fees at each point where it adds measurable value.

> Rwanda has a stock exchange for its largest companies. Rozine is the capital market for everyone else.

---

## Status

This repository currently contains **the governing business and brand documentation only**. Production code has not yet landed here.

| | |
|---|---|
| **Phase** | Pre-build — BRS v1.0 baselined, prototype complete, production build-out starting |
| **In this repo** | BRS, business plan, branding styles, logo asset library, Pulse landing pages |
| **Prior work** | A Next.js prototype covering all five apps exists in the history of the predecessor repository. It is **not** carried forward here. |

---

## The thesis

1. Rwanda's profitable small businesses cannot borrow, because banks price **collateral**, not cash flow.
2. Rwanda's savers cannot invest, because nothing retail-accessible sits between a 7% deposit and a risky side hustle.
3. Both failures share one root cause: **nobody can cheaply verify that a small business actually earns what it says it earns.**
4. Rozine solves verification with machine-parsed bank/MoMo statements co-signed on-site by ICPAR-accredited CPAs — then turns the verified cash flow into a tradable, fixed-return note.
5. Revenue comes from the rails, not the risk: five thin fees on origination, servicing and liquidity. No balance sheet, no lending-licence exposure.

## How verification works

Machine parsing makes verification **cheap**. Licensed humans make it **credible**. Neither alone is sufficient; together they are the product.

- A business uploads 6–24 months of bank and mobile-money statements — not a business plan, not projections, not a self-declared revenue figure. The parsing engine reconstructs gross inflow, outflow, volatility, seasonality and debt-service capacity.
- Every listing and every monthly report is co-signed by an **Audit Partner**: an ICPAR-accredited CPA who visits the premises in person, captures geo-tagged photographic evidence through the Rozine Auditor app, performs agreed-upon procedures under **ISRS 4400**, and applies a cryptographic verification seal.
- Verification is not a one-time gate. Between the **1st and 7th of every month**, each funded business must upload fresh statements and receive an on-site co-signature. The resulting report is published to every holder of that note. Missing the window has consequences, not reminders: SLA escalation, a frozen Audit Partner yield share, and a flag on the note.

## The five applications

Rozine ships as five coordinated applications over **one shared transactional core**. Every entity — party, note, order, report, ledger entry, policy — lives in a single source of truth, so an action in one app propagates instantly to the others.

| App | User | Purpose |
|---|---|---|
| **Investor** | Retail & institutional investors | Discover deals, inspect verified financials and audit evidence, invest from RWF 5,000, track a portfolio, trade on the secondary market, manage a wallet |
| **Business** | Borrowing SMEs | Onboard and verify, upload statements, see computed capacity, create and manage a raise, submit the monthly report, track repayments and standing |
| **Auditor** | ICPAR Audit Partners | Receive dispatched jobs within a 30 km radius, run 24-hour Flash Audits, capture geo-tagged evidence on site, co-sign reports, track the 25% yield share |
| **Admin console** | Rozine staff | Total command over every object and action: underwriting policy, ICPAR licence verification, spatial dispatch, SLA enforcement, treasury and ledger, compliance cases, feature flags, RBAC and a full audit trail |
| **Pulse** | Pre-launch public | Demand aggregation — investors register non-binding pledges, businesses pre-qualify from one statement. Collects no funds, issues no instrument. |

Making the Audit Partner a first-class user with a purpose-built app is what converts verification from a manual cost centre into a dispatchable, measurable workflow — and is why one operations team can supervise thousands of monthly audits rather than dozens.

---

## Engineering principles

These are architectural constraints, not aspirations. Each maps to a hard requirement in the BRS.

- **One source of truth.** All apps read and write the same core. No app holds private authoritative state. *(FR‑600)*
- **Money is a ledger, never a field.** No balance is ever mutated; every balance is derived from immutable double-entry records. Reversals are new compensating entries referencing the original. *(DR‑2, BR‑65)*
- **Exact decimals in RWF.** Floating-point representation of money is prohibited. *(DR‑1)*
- **Deterministic and reconstructible.** Capacity, DSCR, rating, pricing, schedules, dispatch and fees must be reproducible from stored inputs and explainable to a user or a regulator years later. *(FR‑602, DR‑5)*
- **Policy as data.** Every threshold, rate, cap and SLA is configurable, versioned and attributed — never hard-coded. A decision records which policy version governed it. *(FR‑605, DR‑6)*
- **Idempotent by default.** Every financial operation must be idempotent; a retried request must not double-post. *(FR‑603)*
- **Everything is attributable.** Every state change emits a durable event and is attributable to an actor. Every administrative action writes an immutable audit entry with a mandatory reason. No role, including Superadmin, may delete an audit or ledger entry. *(BR‑2, BR‑5, NFR‑5)*
- **Offline-tolerant field capture.** The Auditor app must complete a full capture with no connectivity at a rural premises and sync without data loss. *(NFR‑7, FR‑311)*

## Invariants that must never break

| Rule | |
|---|---|
| **Capacity is computed, never requested** | A business accepts an offer at or below computed capacity. It can never ask for an amount. This is the platform's single most important risk control. *(BR‑10, BR‑11)* |
| **No listing without co-signature** | No note may be listed without an Audit Partner co-signature on the originating verification. *(BR‑40)* |
| **Flat total return, never APR** | Return is a flat total percentage of principal, bounded 10–15% on every note at every tenor. The term "APR" must not appear on any user-facing surface. *(BR‑21, BR‑22)* |
| **One rating, one scale** | Exactly one measure of business quality is published: 0.0–5.0 to one decimal, identically in every app. Any second competing score is prohibited. *(BR‑30, BR‑31)* |
| **Segregated funds, no principal risk** | Investor funds are held in segregated accounts, never commingled. Rozine takes no principal risk and may never represent a note as capital-protected. *(BR‑60, BR‑61, CR‑6)* |
| **Evidence is captured, never uploaded** | Photographs must come from the in-app camera with live geolocation. Gallery upload is rejected, and coordinates are cross-checked against the registered premises. *(BR‑43, BR‑44)* |
| **Published reports are immutable** | Corrections are issued as amendments linked to the original, never as edits. *(BR‑45)* |
| **Pulse touches no money** | Pulse must not collect funds, issue any instrument, or create any binding obligation, and must disclose that on every surface. *(BR‑80, BR‑81)* |

> ### ⚠️ Deprecated in full: tax-authority verification
>
> The verification model changed fundamentally at BRS v1.0. **RRA sync, EBM device/TIN linkage, automated tax-receipt scraping and tax compliance badging are deprecated in full.** They must not appear in any interface, data model, contract or communication, and no TIN, EBM device identifier or tax-authority credential may be stored anywhere in the system — existing columns must be dropped. Acceptance is conditional on this. *(DR‑8, AC‑9)*
>
> The replacement is bank/MoMo statement ingestion with OCR parsing, plus on-site co-signature by ICPAR-accredited Audit Partners under ISRS 4400 agreed-upon procedures.

---

## The underwriting engine

Credit logic is deterministic, auditable and published. There is no black box: a business can see exactly why it received the capacity, rating and yield it did.

```
NOCF_m   = Inflow_m − Outflow_m
CFADS    = trimmed_mean(NOCF) − ExistingDebtService − OwnerDraw
DSCR     = CFADS ÷ ProposedMonthlyRepayment
Capacity = (CFADS ÷ TargetDSCR × Tenor) ÷ (1 + TotalReturn)
Instalment = (Principal × (1 + TotalReturn)) ÷ Tenor
Rating   = clamp(EngineScore ÷ 20, 0.0, 5.0) rounded to 1 dp
Coverage = Inflow_period ÷ (Outflow_period + DebtService_period)
```

Capacity is solved **backwards from serviceable monthly repayment**, never forwards from a requested amount. The trimmed mean exists so a single exceptional month cannot inflate an offer. All monetary results round to the nearest franc; rates round to one decimal place.

### Decision tiers

| Tier | DSCR band | Treatment |
|---|---|---|
| **Tier 1 — auto-approve** | ≥ 1.25× | Cleared by the engine, subject to Audit Partner co-signature and KYB completion |
| **Tier 2 — audit band** | 1.00× – 1.25× | Enhanced on-site procedures and manual underwriter approval; capacity reduced |
| **Tier 3 — decline** | < 1.00× | Rejected, with the computed figures and specific, actionable guidance on what must change |

A decline is not a dead end. The business keeps its profile, keeps uploading statements, and is automatically re-scored as its cash flow improves — turning today's rejection into tomorrow's pipeline. *(BR‑16)*

### The rating

One number, one word, one colour. The word leads, the number supports, the colour reinforces.

| Band | Rating | Meaning to an investor |
|---|---|---|
| **Strong** | 4.0 – 5.0 | Deep, stable, well-covered cash flow with a clean verification history. Selective by design |
| **Stable** | 3.0 – 3.9 | Sound and serviceable — the healthy default for a well-run business, **not** a warning |
| **Weak** | 2.0 – 2.9 | Thin coverage or volatile receipts. Investable only with the elevated return that accompanies it |
| **Distressed** | < 2.0 | Impaired. Not listable; existing notes enter active monitoring and recovery |

The scale is out of five rather than one hundred because it is instantly legible to a first-time investor and carries no false precision. The "Strong" floor sits at 4.0 rather than the midpoint so the label means genuinely exceptional.

---

## Business model

Five thin, transparent fees. No spread on the investor's return, no principal risk, and **no revenue derived from default** — the largest line is collected only when a business actually repays.

| Fee | Rate | Paid by | Trigger |
|---|---|---|---|
| Listing | Fixed | Business | On successful listing, after underwriting and co-signature |
| Service | 2% of repayment | Business | Monthly, on each scheduled repayment — **25% routed to the Audit Partner** |
| Repayment | 1% of payout | Investor | On each payout received |
| Secondary | 3% of trade | Seller | On each secondary-market transaction |
| Float & treasury | Variable | — | Yield on undeployed segregated balances, within regulatory limits |

No other fee may be charged, and all fees must be disclosed in francs before the user commits to the action that incurs them. *(BR‑62, BR‑64)*

## Product parameters

Policy-managed — configurable, versioned and attributed. These are baseline values, not constants.

| Parameter | Baseline |
|---|---|
| Permitted tenors | 3, 6, 9, 12 months |
| Total return floor / ceiling | 10% / 15% flat on principal |
| Minimum investment | RWF 5,000 |
| Auto-approve DSCR | ≥ 1.25× |
| Audit-band DSCR | 1.00× – 1.25× |
| Rating band thresholds | 4.0 / 3.0 / 2.0 |
| Monthly reporting window | 1st – 7th of month |
| Flash Audit completion | 24 hours |
| Audit Partner dispatch radius | 30 km |
| Audit Partner yield share | 25% of service fee |
| Listing validity | 30 days |
| Note / photo limits | 100 characters; 5 photographs per party per report |

## Regulatory position

Rozine is built regulator-first — compliance is a design constraint expressed in the architecture, not a document produced afterwards. It operates a marketplace for investment instruments: not a deposit-taking institution, not a lender of record.

Entry is sequenced through the **Rwanda Capital Market Authority (RCMA)** regulatory sandbox, with demand proven via Pulse before any capital is at risk. Sandbox caps and conditions must be enforceable as policy values, without a code change. The Admin console is built so a supervisor can be given a **read-only seat** over the entire live operating state. *(CR‑12, CR‑13, FR‑418)*

KYC/KYB before any transaction, continuous AML monitoring with case management, sanctions and PEP screening, daily reconciliation of segregated funds, full listing disclosure, enforceable investment limits, a documented dispute process, and Rwandan data-protection compliance are all mandatory. *(CR‑1 – CR‑13)*

## Roadmap

| Phase | Objective | Exit criteria |
|---|---|---|
| **1** | Pulse campaign and partnership execution | Signed institutional partners; substantial verified pledge book; pre-qualified business pipeline; parsing engine validated on real statements at volume |
| **2** | Production build and Audit Partner accreditation | Core services live; payment rails integrated; first ICPAR cohort onboarded, trained and contracted; security review passed |
| **3** | RCMA sandbox operation | Live notes originated, funded, serviced and repaid under supervision; complete monthly reporting record; measured default and recovery experience |
| **4** | Full licence and scale | Licence granted; caps lifted; institutional channel opened; secondary market at depth; regional expansion prepared |

---

## Repository layout

```
docs/
  Rozine-BRS.md                Business Requirements Specification v1.0  ← canonical
  Rozine-Business-Plan.md      Business plan (2026)                      ← canonical
  Rozine-Branding-Styles.md    Brand and visual identity guide           ← canonical
  Rozine-*.pdf                 Rendered distribution copies of the above
  New Logo/                    Logo asset library (PNG, plus PNGs/ variants)

resources/
  rozine-pulse-desktop.html        Pulse landing page — desktop
  rozine-pulse-mobile.html         Pulse landing page — mobile
  rozine-pulse-desktop-light.html  Pulse landing page — desktop, light theme
  rozine-pulse-mobile-light.html   Pulse landing page — mobile, light theme
```

The Pulse pages are self-contained bundles — markup, styles and content packed into a single self-extracting file, with no external dependency beyond web fonts. They are build output rather than reviewable source, so diffs on them are not meaningful; if the generating source exists, it belongs here alongside them.

**Markdown is canonical.** The `.md` files are the source of truth: they diff cleanly, review in a pull request, and let requirement IDs be traced line by line. The PDFs are rendered copies kept for distribution to partners, auditors and the regulator. **Edit the Markdown; regenerate the PDF from it.** If the two ever disagree, the Markdown is correct and the PDF is stale.

**The BRS governs.** It is the single reference against which the build is scoped, the compliance programme is evidenced, and delivery is accepted. Where any other artefact — including this README — conflicts with it, the BRS wins. Requirement IDs cited above (`BO‑n` objective, `BR‑n` rule, `FR‑n` functional, `DR‑n` data, `NFR‑n` non-functional, `IR‑n` integration, `CR‑n` compliance, `AC‑n` acceptance) are the canonical handles for tracing work back to it.

## Glossary

| Term | Definition |
|---|---|
| **Note** | The instrument issued by a business and held by investors, carrying a fixed total return and fixed monthly repayments |
| **Capacity** | The maximum a business may raise, computed from verified cash flow. Never negotiable upward |
| **CFADS** | Cash flow available for debt service — verified operating cash flow after existing obligations and owner drawings, volatility-adjusted |
| **DSCR** | Debt service coverage ratio — CFADS divided by proposed monthly repayment |
| **Audit Partner** | An ICPAR-accredited CPA contracted to perform on-site verification and co-sign reports under ISRS 4400 |
| **Verification seal** | Cryptographic hash binding auditor identity, licence, report contents and timestamp to a published report |
| **Flash Audit** | A randomly triggered 24-hour re-verification of a business and its Audit Partner, indistinguishable in advance from routine dispatch |
| **Health status** | Period-level condition derived from the verified report: Healthy, Watch or Distressed |
| **ISRS 4400** | International Standard on Related Services governing agreed-upon-procedures engagements |
| **ICPAR** | Institute of Certified Public Accountants of Rwanda |
| **RCMA** | Rwanda Capital Market Authority |
| **Pulse** | Pre-launch demand-aggregation portal. Collects no funds and issues no instrument |

---

**Confidential.** This repository and the documents it contains are confidential and issued for build, compliance and evaluation purposes. Nothing here constitutes an offer of securities, an invitation to invest, or financial advice. Parameter values reflect current product design and are subject to regulatory approval.

Rozine Technologies Ltd · Kigali, Rwanda
