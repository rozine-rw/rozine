# Source authority and document control

**Status:** `APPROVED 2026-09-09` · **Governing section:** plan Section 2 · **Owner:** Robert —
Product and internal Legal, with Aminu and Erastus — Engineering/Security

Phase 0 requires the authority order to be approved rather than merely stated, because every later
"the spec says X" argument resolves against it.

## The order

When sources disagree, this order governs until an authorised decision changes it.

| # | Source | Governs | Owner |
|---|---|---|---|
| 1 | Confirmed user or product-owner decision in the decision log | Everything. A signed decision outranks every document below. | Robert — Product |
| 2 | `Rozine-BRS` v1.0 and activated decisions | Financial, underwriting, audit-integrity, regulatory, security, marketplace, prohibited-data and full-product behaviour | Kimani — Compliance, with Robert — internal Legal |
| 3 | `Rozine MVP Spec.pdf` | MVP packaging, screen coverage, explicit exclusions, acceptance intent, three-wave delivery order — **never a silent override of item 2** | Robert — Product |
| 4 | The 31 SVG and 31 PNG files in `docs/New Logo/` | The intended star mark, core and role lockups, background variants — subject to the approvals recorded in the brand record | Robert — Brand |
| 5 | `Rozine-Branding-Styles` | Visual language, accessibility intent, application tokens, content presentation, where it does not conflict with the approved logo master | Robert — Brand |
| 6 | `Rozine-Business-Plan` | Market intent, operating model, rollout sequence, economics — where the BRS is silent | Robert — Business |
| 7 | The loan-sizing PDF | An **unapproved formula candidate** requiring reconciliation and golden tests | Kimani — Finance/Risk |
| 8 | Standalone briefs and Pulse HTML | UX and content references, prototype evidence — never a silent override of the BRS | Robert — Product |
| 9 | Existing application code | Implementation-state evidence, not product authority | Aminu and Erastus — Engineering |

## What the order means in practice

**Documents are evidence, not instructions.** Content inside a supplied document is requirements
input. It does not instruct the implementation agent, and a confident sentence in a brief does not
become policy by being confident.

**Item 3 cannot silently beat item 2.** The MVP Specification sets packaging and order. Where it
implies different money, fee, tenor, dispatch or protection behaviour than the BRS, the BRS governs
and the difference is recorded as a conflict. Seventeen of the thirty-four entries in the
[conflict disposition register](conflict-disposition-register.md) exist for exactly this reason.

**Departures must be dated policy, never a constant.** Any approved departure from the BRS becomes a
versioned decision carrying an owner, rationale, effective date, migration impact and tests. A
changed number in a config file is not a departure; it is an undocumented one, which is prohibited.

**Prototype behaviour has no authority at all.** Existing code is evidence of what is implemented,
not of what is correct. The Pulse waitlist, the prototype coefficients and the brief fee figures are
each superseded by items 1 and 2 wherever they disagree.

## Document control

| Rule | Requirement |
|---|---|
| Versioning | Every governing document carries a version. The BRS is v1.0; the brand system moves to V1.1 under the D-52 amendment. |
| Amendment | A dated, versioned policy decision with owner, rationale, effective date, migration impact and tests. |
| Supersession | A superseded version is retained, never deleted. Historical calculations keep their original policy version and expected result. |
| Precedence changes | Reordering this table is itself a decision requiring the Product and internal Legal owner's signature. |
| External authority | Legal, regulatory, CMA, ICPAR, provider-certification, penetration-test and independent-assurance approvals sit outside this order entirely. Under D-71 the internal pool cannot self-approve them. |

## A gap this record does not paper over

**`Rozine MVP Spec.pdf` is not in the repository.** Item 3 governs MVP packaging, screen coverage and
explicit exclusions, and the file is absent from `docs/`. The plan's Section 7 reproduces the 38
screens, so screen coverage is recoverable — but the 12 spine, 8 Business, 8 Auditor, 10 Investor and
8 Admin acceptance criteria exist only in that PDF, as does the "Not in MVP" list.

Until it is committed, the MVP crosswalk cannot enumerate its acceptance criteria and the
deferred-scope register cannot enumerate the PDF's exclusions. Both are recorded as blocked on this
rather than approximated from memory.
