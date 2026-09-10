# C-01–C-34 conflict disposition register

**Status:** `DISPOSITIONED 2026-09-09` · **Generated** by `scripts/governance/build-conflict-register.py`

Section 8 of the plan states each conflict and the default this plan uses. That default is a
working assumption, not an approval. This register adds what Phase 0 requires of each one:
whether the default disposes of it or a named decision must still be signed, who owns that,
and the gate it has to close before.

Conflict text is quoted from Section 8 rather than retyped, so the two cannot drift apart.

**17 settled · 17 blocked on a signed decision.**

No PDF page, brief, prototype constant or formula note overrides the BRS anywhere below. Where
a conflict is settled, it is settled because the BRS or an approved decision governs — never
because the louder document won.

## Blocked — a signed disposition is required

These cannot be encoded as behaviour until their owner signs. Several sit directly on the Phase 1
chain, which is why Phase 0 cannot exit on the strength of the defaults alone.

| ID | Conflict | Owner | Must close by | Why it is not settled |
|---|---|---|---|---|
| **C-03** | Briefs/current Pulse use a 10.5% return floor; BRS permits 10–15%. | Kimani — Finance/Risk | Appendix A activation, before Phase 1 underwriting | D-11 selects the 10.0% floor, but Appendix A is a product-approved candidate rather than an activated policy. The floor cannot be encoded until it is signed. |
| **C-04** | Formula PDF uses `0.08` risk and `1.5` term coefficients; the Pulse prototype/current implementation uses `0.085` and `2.5`. | Kimani — Finance/Risk | Appendix A activation, before Phase 1 underwriting | Same activation gate as C-03. The prototype coefficients are rejected either way; what is unsigned is the replacement. |
| **C-05** | Formula PDF rounds down to RWF 100,000; prototype/current behavior rounds to nearest RWF 100,000. | Kimani — Finance/Risk | Appendix A activation, before Phase 1 underwriting | Rounding and DSCR-floor behaviour are money rules. They need the Appendix A signature, not just the D-11/D-11B selection. |
| **C-06** | BRS permits DSCR 1.00–<1.25 through enhanced/manual approval; prototypes allow only ≥1.25. | Kimani — Finance/Risk | Appendix A activation, before Phase 1 underwriting | D-13 preserves the manual tier as indicative only. Activation is what stops it becoming an offer. |
| **C-07** | Briefs/Pulse claim RWF 5M–50M note boundaries; BRS does not baseline them. | Kimani — Finance/Risk | Before primary listing in Phase 1 | Note boundaries are unbaselined in the BRS. They must become versioned policy or stay absent; the brief claim cannot be hard-coded. |
| **C-08** | Pulse/briefs use a 35% annual-revenue ceiling; BRS does not. | Kimani — Finance/Risk | Before Phase 1 capacity calculation | The 35% revenue ceiling has no BRS basis. It is excluded from production capacity until approved as policy. |
| **C-09** | Investor Brief uses a 50% per-raise concentration cap; BRS delegates category limits to policy. | Kimani — Finance/Risk | Before primary-investment acceptance in Phase 1 | The BRS delegates concentration limits to policy and none exist. Accepting investment without them would encode an unapproved limit by omission. |
| **C-10** | Business Brief says investors do not see raw revenue/bank details; BRS requires verified financial disclosure. | Robert — Product and internal Legal | Before Phase 1 Resource contract freeze | Exactly which aggregate financial facts an Investor may see is a disclosure decision. The Resource schema cannot freeze before it. |
| **C-14** | Pulse asks for typed summaries while BRS requires a statement upload. | Robert — Product | Before Phase 1 evidence ingestion | Statement upload is mandatory under the BRS. Whether a typed preview may exist alongside it, and how it is labelled, is unapproved. |
| **C-16** | Business-plan float/interest language may conflict with segregated-funds and no-spread positioning. | Robert — internal Legal, with Kimani — Finance/Risk | Before any treasury behaviour is built | Float and interest ownership touch segregated funds. This needs legal and accounting sign-off, and external counsel under D-39, before a single line is written. |
| **C-21** | MVP PDF page 5 says 3–6-month terms, five years of statements, and a 35% revenue ceiling. | Kimani — Finance/Risk | Appendix A activation, before Phase 1 underwriting | BRS tenors and the D-14 routing survive, but the specific manual and auto routes are unactivated policy. |
| **C-22** | MVP PDF page 5 implies a penalty and then default after day 7. | Kimani — Finance/Risk | Before Phase 2 arrears behaviour | No penalty may be invented. D-11C default and arrears consequences must be signed before any consequence is coded. |
| **C-24** | MVP PDF pages 7–8 say nearest-first dispatch and a 48-hour visit. | Kimani — Audit Operations | Before Phase 2 dispatch | BRS eligibility and the 24-hour rule govern, but D-32 must settle the dispatch policy before it is implemented. |
| **C-25** | MVP PDF pages 9–10 show 10–20%, annualised return, a 0.5% withdrawal fee, 10%–4.5% Investor/Plus fees, and other fee ladders. | Kimani — Finance/Risk | Before Phase 1 fee disclosure | The exclusive BRS fee schedule governs and every new fee is quarantined. Which of the PDF fees survive is unapproved, and fee disclosure is on the Phase 1 chain. |
| **C-26** | MVP PDF pages 9 and 12 imply reserve cover and a 5% loss-reserve floor. | Kimani — Finance/Risk, with Robert — internal Legal | Before any protection language ships | A reserve floor is an accounting and disclosure commitment. It stays excluded until funded, accounted for and legally reviewed. |
| **C-33** | MVP PDF pages 2 and 9 promise both MoMo networks and same-day withdrawals. | Kimani — Finance/Risk | Phase 4 provider certification | Both-network MoMo and same-day withdrawal are provider targets, not promises. D-21 and D-38 contracts must fix cutoffs, reversals and settlement windows first. |
| **C-34** | MVP PDF page 5 requires exactly two directors/signatories, each ID-verified; the BRS does not establish a universal two-person company/signing rule and entity mandates may differ. | Robert — Product, with Aminu — Engineering | Before the Phase 1 Party model freezes | Two directors must not be hard-coded. D-64 decides who must be verified, and the Party model cannot freeze before it. |

## Settled by the governing default

Each of these is disposed of by the BRS or by an approved decision. They still carry an owner,
because a settled conflict can be reopened by a later amendment and someone has to notice.

| ID | Conflict | Governing disposition | Owner | Gate |
|---|---|---|---|---|
| C-01 | Business Brief requires a valid TIN; BRS prohibits tax identifiers and tax-system references. | The BRS prohibition is absolute and needs no further decision. What remains is engineering: prove no registry integration receives or persists a TIN. | Aminu and Erastus — Engineering/Security | Phase 1 Party model freeze |
| C-02 | Investor Brief says 2% secondary seller fee; BRS says 3%. | 3% stands until a versioned fee amendment exists. No amendment is proposed, so nothing is pending. | Kimani — Finance/Risk | Phase 3 secondary contract freeze |
| C-11 | Pulse uses random counters and random sequence numbers. | Server-backed values replace random counters. The waitlist numbering half was closed on 2026-09-06 under D-73 with PostgreSQL-certified allocation. | Aminu and Erastus — Engineering/Security | Phase 6 Pulse remediation |
| C-12 | Pulse shows hard-coded fictional deals as activity. | Fabricated activity is prohibited outright. Consented records or unmistakable fixtures only. | Robert — Product | Phase 6 Pulse remediation |
| C-13 | Pulse performed client-side calculations while BRS requires the production engine. | Remediated: React renders server-returned Resource facts and the Domain/Application/Infrastructure boundary is enforced by the architecture suite. | Aminu and Erastus — Engineering/Security | Closed 2026-08-29 |
| C-15 | Investor Brief mentions card funding; BRS integration inventory specifies bank and mobile money. | Bank and mobile money only. Card rails stay out until fees, disputes and chargebacks are approved, which is a separate decision rather than a pending one. | Kimani — Finance/Risk | Phase 4 provider certification |
| C-17 | “Bank-grade” and “end-to-end encrypted” claims are broader than proven controls. | Unprovable security claims are replaced with precise ones backed by implemented controls. No decision is pending; this is editorial discipline. | Aminu and Erastus — Engineering/Security | Phase 3 content finish |
| C-18 | “How safe” rating language conflicts with explicit capital-loss risk. | Safety and capital-protection language is prohibited. The rating describes verified business quality and standing. | Robert — Product | Phase 3 content finish |
| C-19 | The 31 replacement SVGs establish a star mark and explicit Investor-blue, Business-green, and Auditor-orange wordmarks, but still provide competing Rozine lockups and candidate colors that differ from the brand-guide tokens. File delivery does not itself establish brand authority. | Superseded by the D-52 amendment: the logo package colours are now authoritative and the guide is reissued as V1.1. | Robert — Brand | Closed 2026-09-07 |
| C-20 | MVP PDF pages 2 and 14 specify four web apps plus a launcher, no native stores, and omit Pulse; the full BRS and prior roadmap include mobile and Pulse. | Superseded by the D-04 decision: responsive web/PWA for the MVP with a narrow thin-native Auditor capture companion. Pulse stays post-MVP. | Aminu and Erastus — Engineering/Security | Closed 2026-09-07 |
| C-23 | MVP PDF page 7 says the Auditor earns 10% of the charge and Flash Audits earn nothing. | The BRS 25% share, monthly payability and SLA freeze govern. The PDF figures do not override them. | Kimani — Audit Operations | Phase 2 Auditor earnings |
| C-27 | MVP PDF pages 10 and 14 let Rozine buy holdings onto its own book. | Rejected outright. Secondary liquidity is Investor-to-Investor; Rozine holds no principal inventory and makes no market. | Robert — Product, with Kimani — Compliance | Phase 3 secondary contract freeze |
| C-28 | MVP PDF page 10 introduces maker/taker and acquisition fees. | Rejected for the current product. The BRS 3% seller fee on a settled trade is the only secondary fee. | Kimani — Finance/Risk | Phase 3 secondary contract freeze |
| C-29 | MVP PDF pages 9–10 require Plus bands, auto-deployment, a 50% raise cap, and five-note language without an approved BRS policy. | The Automation screen is a gated explainer in the MVP. Executable Plus moves to Phase 7 behind product and policy approval. | Robert — Product | Phase 7 |
| C-30 | MVP PDF pages 2–3 say every displayed figure traces to a ledger entry, but ratings/capacity are not ledger facts. | Monetary amounts trace to ledger entries; ratings, capacity and evidence facts trace to their own versioned records. Both are traceable, to different things. | Aminu and Erastus — Engineering/Security | Phase 1 Resource contract freeze |
| C-31 | MVP PDF page 14 asks demo Businesses across “all three ratings”; the BRS has four rating bands. | All four BRS bands are seeded. Distressed is historical and non-listable, so it can never appear as an eligible deal. | Kimani — Finance/Risk | Phase 3 demo seed |
| C-32 | The MVP PDF embeds a logo treatment while the user later confirmed Robert supplied replacement 31-SVG and matching 31-PNG packages. | Superseded by D-51: the detached leading star is the primary lockup and the embedded PDF treatment is non-authoritative reference art. | Robert — Brand | Closed 2026-09-07 |

## What this register does not do

It does not activate Appendix A. Six of the blocked conflicts — C-03, C-04, C-05, C-06, C-21 and
the underwriting half of C-25 — wait on the same signature, so activating that worksheet closes
more of this register than any other single act.

It does not substitute for external authority. C-16 and C-26 need legal and accounting review
beyond the internal pool, and D-71 forbids the pool from self-approving that.

Governing defaults quoted here remain what the plan says they are. Changing one requires a dated,
versioned policy decision with an owner, rationale, effective date, migration impact and tests —
never an undocumented constant.
