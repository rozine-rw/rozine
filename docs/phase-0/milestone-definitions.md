# The three MVP milestones

**Status:** `RECORDED 2026-09-09` · **Governing decision:** D-02 · **Owners:** Aminu and Erastus —
Engineering/Security; Robert — Product; Kimani — Compliance and Finance/Risk

Three distinct targets, each with its own gate. The reason to write down what each one **does not**
prove is that all three sound like "the MVP works", and only the third means anything to a
participant with real money.

---

## `MVP ALPHA` — Phase 1

**The claim:** the end-to-end primary-market chain works once.

One Business applies, one eligible Auditor verifies, the core computes the governed result, one
Investor funds, Admin releases the proceeds, and one repayment posts — all through the shared Laravel
core and role-aware responsive web surfaces. Holding and Order contracts exist and are
secondary-ready.

**Gate:** the chain completes end to end against deterministic provider fakes and isolated
acceptance fixtures, with the applicable PHP and client coverage gates green on the exact SHA, five
integration checkpoints passed, and cross-review by the non-author developer.

**What it does not prove**

- That it works twice, concurrently, or under any load.
- That it is shippable. It is not, and no participant may touch it.
- That the money rules are *approved*. Phase 1 runs on candidate policy; Appendix A is unactivated,
  so the numbers are shaped correctly rather than authorised.
- That failure paths work. Refusal, freeze, arrears, amendment and recovery states are Phase 2.
- That the secondary market works. Contracts are shaped for it; nothing trades.
- That real rails exist. Every provider is a fake.

---

## `MVP RELEASE CANDIDATE` — Phase 3

**The claim:** every pre-production-eligible MVP criterion and documented production surrogate passes
in isolation.

Polished, secure, accessible, BRS-compliant and sandbox-ready responsive web, including **atomic
Investor-to-Investor secondary settlement**, complete supervisory evidence and an isolated,
resettable demo.

**Gate:** the authoritative full PHP and client CI gates, contract, accounting and concurrency
suites, responsive browser and PWA end-to-end tests, secondary failure injection, the device and
browser matrix, accessibility, 3G and load runs — all green, with every acceptance matrix row linked
to immutable evidence and no row silently dropped.

**What it does not prove**

- That anyone is authorised to run it. Regulatory and sandbox permission is Phase 4.
- That the rails work. Bank, mobile money, KYC and registry integrations are still surrogates;
  a surrogate that passes says nothing about a provider that has not been certified.
- That real participants behave as fixtures do.
- That the lifecycle completes in production time. A repayment schedule that passes in a test clock
  has not survived a real month.
- That Pulse or native clients are ready. Both are explicitly post-MVP, and this milestone must not
  be read as full-roadmap completion.

---

## `LIVE MVP ACCEPTED` — Phase 4

**The claim:** the product has run for real, under supervision, and the controls held.

**Gate:** provider and regulatory authorisation in place; certified real rails; a controlled
authorised cohort; the required production observation lifecycle completed; daily reconciliation,
supervisor reconstruction, security retest, incident and rollback exercises, participant UAT and
regulatory reports all evidenced.

**What it does not prove**

- That the roadmap is finished. BRS AC-11 for Pulse and native-mobile acceptance remain open —
  Phases 6 and 5 — and this milestone must not be stated as though they were closed.
- That the cohort generalises. A controlled authorised group is not the open market.
- That scale holds. Phase 8 exists for reliability and evolution under growth.
- That approved extensions are live. Plus execution and the rest of Phase 7 are separate gates.

---

## Rules that apply to all three

Each milestone is **gate-based, not date-based**. Section 9.1 gives conditional duration ranges;
calendar commitments require the red decisions, providers, staffed reviewers and reproducible
baseline to be confirmed first.

A milestone is reached when its gate passes on an exact SHA with archived evidence — not when the
work "feels done", and not by a partial or replayed run. An impacted-test run, a watch-mode run or a
prior report for a different SHA is never sufficient.

No milestone may be claimed with a scoped deferral unless that deferral carries its own signed
conflict disposition. Quietly dropping an acceptance row is how a gate becomes decorative.
