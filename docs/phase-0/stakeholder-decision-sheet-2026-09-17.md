# Robert / Kimani — consolidated decision sheet, 17 September 2026

**Version:** response-2026-09-17.1

**Status:** `PARTIAL_CHOICES_CAPTURED — CLARIFICATIONS_OPEN — NOT_ACTIVATED`

**Delivery:** `DRAFT_NOT_SENT`

**Purpose:** Preserve the choices received, answer the questions, and obtain precise decisions without silently changing the BRS, production policy or historical test results.

## Source and authority

Hussain supplied the messages as input from Robert and Kimani. The pasted messages identify Robert as the speaker, dated 17/09/2026 from 12:18:17 PM to 12:20:06 PM. Their timezone was not supplied; these are source-local timestamps, not asserted UTC. The tables below are attributed summaries, not a verbatim transcript or an independently retrieved WhatsApp export.

Robert's explicit choices are decision evidence, not unanswered proposals. This sheet does not infer Kimani's individual concurrence on every Finance/Risk/Compliance detail from the message attribution. The remaining confirmations concern the exact wording and unresolved boundaries below, not a request to repeat already clear answers.

Read with the [10 September underwriting proposal](underwriting-decision-review.md), [10 September secondary proposal](secondary-contract-review.md), [BRS](../Rozine-BRS.md), [implementation plan](../Rozine_Phased_Implementation_Plan.md) and [source-authority record](source-authority-record.md). The earlier proposal tables retain their historical status; this sheet records the subsequent responses. Where a choice departs from the BRS or an earlier recorded decision, a versioned amendment is required. No source-precedence change is implied.

**Production activation: NONE. Effective policy version/date: NOT_SET.** No signature, external clearance, schema freeze, calibrated model, implemented rule, baselined vector or Phase 0 completion is asserted. Unresolved fields have no engineering-selected default.

## 1. Received choices and remaining boundaries

Status meanings: `CHOICE_CAPTURED` = an explicit choice, with any remaining scope listed; `QUESTION_OPEN` = a question or suggested direction, not a final algorithm; `CHANGE_REQUEST` = a requested revision without exact replacement values; `PARTIAL_ANSWER` = a clear answer that does not resolve the original question.

### Underwriting and cross-cutting assumptions

| ID | Response status | Received from Robert — summary | Remaining boundary / reply |
|---|---|---|---|
| TENOR | CHOICE_CAPTURED | Shortest term 3 months; longest 6 months, not 12. | Exact permitted set and effective scope: Q01. Amends BR-20 and dependent schedules/pricing examples. |
| HISTORY | QUESTION_OPEN | Five-year accounting is cited as the reason seasonal high months can be understood. | Mandatory eligibility history or optional context? Contradicts D-14's recorded window/retention policy: Q04. |
| UW-R01 | QUESTION_OPEN | Would treating owner withdrawals as costs simplify auditing without harm? | Recognize the cash consumption once; distinguish distributions from operating expense and select the measurement rule: Q02. |
| UW-R02 | QUESTION_OPEN | Would CRB integration make existing-debt assessment easier, and how would it solve the problem? | Evidence source is not the debt-service algorithm; access, completeness and obligation-calendar choices: Q03. |
| UW-R03 | QUESTION_OPEN | Why exclude high months when they reflect genuine seasonality across a long history? | Distinguish recurring seasonality from exceptional receipts; select and validate the capacity algorithm/window: Q04. |
| UW-R04 | PARTIAL_ANSWER | Reject DSCR below 1.25 without flexibility. | Strict DSCR choice captured, but the original question was Coverage precision. Resizing/manual-route boundaries also need resolution: Q05. |
| UW-R05 | CHOICE_CAPTURED | RWF 3M floor, RWF 100M ceiling and 35% of annual revenue as cap. | Revenue definition, per-Note versus aggregate exposure and below-floor handling: Q06. |
| UW-R06 | CHOICE_CAPTURED | Unaccepted offers expire/recalculate; issued terms and bought rating/return/schedule stay fixed; retain evidence and reject stale overwrites. | Accepted-but-not-issued state and effective event scope: Q07. Issue snapshots remain distinct from current monitoring. |
| UW-R07 | CHANGE_REQUEST | Keep the shape/three rows, but shorten periods to address problems sooner. | Identify the rows; supply exact escalation/cure periods and authority: Q08. No new default timer inferred. |

### Secondary trading

| ID | Response status | Received from Robert — summary | Remaining boundary / reply |
|---|---|---|---|
| SEC-R01 | CHOICE_CAPTURED | Smaller buyers may purchase part of a larger seller's position; unsold units remain with the seller. | Partial-fill direction replaces the all-or-nothing proposal. Quantity/reservation/remainder contract: Q09. |
| SEC-R02 | CHOICE_CAPTURED | Seven-day listing expiry is acceptable; asks why not keep listings until repayment and mentions a possible four-month rule. | Expiry rationale and clarification of the unverified four-month assumption: Q09. No lifetime listing or maturity cutoff selected. |
| SEC-R03 | CHOICE_CAPTURED | Five-minute reservation, with the money already in the wallet. | Use settled available funds, including the applicable buyer fee; apply reservations to selected units under Q09/Q10. |
| SEC-R04 | CHOICE_CAPTURED | Seller ask may not exceed what the Note will pay; discounts allowed. Intended as investor protection, not platform market making. | Remaining transferable entitlements, gross/net and all-in buyer cost: Q10. External legal characterization remains separate. |
| SEC-R05 | CHOICE_CAPTURED | Charge 0.5% taker and 0.2% maker to support trading activity. | Amend BR-62/64 and the seller-only fee contract; define roles, base, rounding and effective scope: Q10. |
| SEC-R06 | QUESTION_OPEN | Questions exclusion of small near-maturity holdings and asks what a RWF 1,000 secondary floor would allow. | Confirm a minimum executed consideration, not a unit face value; decide residual treatment: Q11. |
| SEC-R07 | CHOICE_CAPTURED | Adopt the 00:00 cutoff on ordinary days, Sundays and holidays. | Africa/Kigali holder-of-record cutoff; does not independently decide D-23 holiday adjustments to contractual due dates. |
| SEC-R08 | CHOICE_CAPTURED | Approves the proposed rule. | Refers to the supplied proposal: halt/eligibility loss suspends affected uncommitted orders, releases reservations and requires explicit relisting after clearance; no automatic reactivation. |
| SEC-R09 | CHOICE_CAPTURED | No seller cancellation/edit during buyer reservation; show five-minute expiry. Admin cancellation needs a recorded reason. | Authorized admin only; decide handling of an unreserved remainder during partial fills: Q09. |
| SEC-R10 | CHANGE_REQUEST | Avoid unfair restrictions; permit legitimate activity and define malicious conduct clearly. | Existing activity thresholds are review-only, not penalties. Approve conduct definitions and proportionate case handling: Q12. |

## 2. Targeted replies and decisions requested

Recommendations in this section are **proposed wording for confirmation**, not additional approvals attributed to either stakeholder. Robert owns Product/Business/internal Legal; Kimani owns Finance/Risk/Compliance/Audit Operations. Aminu and Erastus subsequently review executable contracts and acceptance evidence.

### Q01 — Permitted terms and effective scope

**Robert + Kimani:** Are permitted tenors exactly **3 and 6 months**, or every whole month **3, 4, 5 and 6**? State the effective policy version/date and treatment of in-flight applications/offers. Issued contractual schedules must remain unchanged under UW-R06. This message does not independently change the 10–15% flat-return policy or select new pricing premiums.

**Answer:** PENDING.

### Q02 — Owner withdrawals: include once, preserve classification

Yes: owner cash consumption belongs in affordability. The proposed formula already subtracts `OwnerDraw` after operating cash flow. Keep distributions separately identifiable instead of relabelling every withdrawal as an operating expense; an owner salary already included in operating expense must not be deducted again. Transfers between the Business's own accounts are not automatically owner withdrawals. Missing evidence is not zero.

**Kimani + Robert:** Confirm or replace the proposed amount: the greater of the verified-window monthly mean and an evidenced recurring monthly withdrawal commitment. Confirm treatment of one-off distributions, reversals and exceptions. This preserves audit traceability without silently changing operating-margin or score inputs.

**Answer:** PENDING.

### Q03 — CRB evidence and the actual debt-service rule

CRB can help discover and corroborate liabilities and repayment behaviour. TransUnion advertises a consumer credit product with outstanding-loan/repayment information and Rwanda webservice/API availability. That is not proof of Rozine's access, commercial-Business coverage, a complete forward schedule or a contracted integration. [Provider source, checked 17 September 2026](https://www.transunionafrica.com/product/trendedview-report).

Recommended evidence flow: authorized bureau lookup plus creditor contracts/statements and internal Rozine obligations → reconcile and deduplicate debt → verify principal, interest, mandatory charges and due dates → apply the selected capacity rule. Do not treat a no-hit, stale record or disputed schedule as zero debt. Provider onboarding, lawful access/consent, retention, freshness and outage handling require separate confirmation; no live lookup or onboarding is authorized by this sheet.

**Kimani + Robert:** Choose the debt-service method: the original conservative scalar `max(historical monthly mean, largest contractual month within the proposed loan tenor)`, or a specified month-by-month affordability method. Confirm the evidence fallback and designate the provider-contact owner. CRB integration does not select this method for us.

**Answer:** PENDING.

### Q04 — History and genuine seasonality

Robert is right that recurring seasonal peaks should not automatically be treated as abnormal windfalls. The original upper-tail adjustment was an uncalibrated review candidate, not a proven model. Equally, a five-year average can hide a cash shortage during a three- or six-month repayment period.

Recommended direction for risk review: retain original observations, distinguish evidenced seasonal cycles from exceptional receipts, and assess the actual repayment-period cash profile and downside. This is not a signed replacement formula, a new bullet-payment schedule or permission to omit low months.

**Kimani + Robert:** Is five years mandatory for eligibility or optional contextual history? D-14 currently records at least six complete consecutive months, manual-only at 6–11, twelve required for seasonal/restarted/material-event cases, possible auto review at 12+, and at most twenty-four months accepted/retained. Decide the exact new eligibility minimum, retained history, current calculation window and seasonal algorithm, including insufficient-history cases. Approve representative examples/sensitivity review before calibration or acceptance is claimed.

**Answer:** PENDING.

### Q05 — Strict DSCR is not the Coverage-precision decision

Captured: exact DSCR below 1.25 is ineligible; **1.249999 must not round up to pass**. In the BRS, `DSCR = CFADS / ProposedMonthlyRepayment`, whereas `Coverage = period inflow / (period outflow + period debt service)`. They are different metrics and serve different decisions.

**Kimani + Robert:**

1. May a smaller recomputed offer qualify if its final repayment schedule satisfies exact DSCR ≥1.25, or is the whole Business disqualified? Does this remove the existing 1.00–<1.25 manual/indicative route entirely, or only prohibit issuing below threshold?
2. Separately confirm the original Coverage proposal: exact numerator/denominator for decisions; six-decimal cache/display is non-authoritative; truthful boundary labels; invalid/missing denominator means unavailable, not numerical zero.

Do not automatically apply the origination rejection rule to existing-Note health, default or contractual terms. A strict ratio is not itself a legal-compliance opinion.

**Answer:** PENDING.

### Q06 — Loan bounds, annual revenue and exposure

Captured: RWF 3M minimum, RWF 100M maximum, and 35% annual-revenue cap. Recommended contract: eligible principal cannot exceed verified cash-flow capacity or any applicable approved cap; never increase capacity to reach the RWF 3M floor. If no permissible principal reaches 3M, no eligible offer is produced.

**Kimani + Robert:** Define annual revenue (for example trailing twelve verified complete months versus last financial year), treatment of businesses with less than twelve months, and whether 35% and 100M apply per Note or across outstanding exposure. Specify aggregation of existing and concurrent commitments and confirm the no-forced-upsize treatment. No annualization or cap scope is assumed.

**Answer:** PENDING.

### Q07 — Accepted but not issued; historical versus current facts

Captured: unaccepted offers die/recalculate; issued terms and purchased issue-rating snapshots do not change; old evidence stays; an older calculation cannot overwrite a newer one. Current health/risk observations may still change without rewriting the issue facts.

**Robert + Kimani:** What happens when an offer has been accepted but the Note has not yet been issued and a material event occurs? Confirm which verified events trigger invalidation and their effective ordering, including policy changes. Recommended engineering contract retains versioned evidence, idempotency and stale-write rejection; legal treatment of accepted commitments must be explicit.

**Answer:** PENDING.

### Q08 — Faster intervention without invented default rules

**Kimani + Robert:** Identify the intended "three rows" in the original multi-trigger matrix. For each changed row supply: trigger, exact period/unit, clock start, automatic action, review owner, cure evidence and release authority. Distinguish reporting deadlines, early-arrears escalation and cure periods from the current 90-DPD/dual-approved-unlikely-to-pay default backstop.

Recommended direction: earlier review/alerts and proportionate new-origination restrictions may be considered independently of declaring default. No shorter default timer, penalty, wallet freeze or automatic cure is selected here. Probability of default remains `null/NOT_CALIBRATED`.

**Answer:** PENDING.

### Q09 — Partial fills, listing expiry and cancellation scope

Captured: partial purchases, seven-day listing expiry, five-minute funded reservations and no seller interference with an active reservation. A listing expires; the underlying holding does not. Expiry is useful to refresh seller intent, current disclosures and price eligibility. A permanent listing would need separately agreed ongoing revalidation/consent; seven days is not asserted as a legal mandate.

**Robert + Kimani:**

1. Confirm fixed-price partial fills of selected eligible units without requiring automatic order-book matching. Choose one active buyer reservation per listing or simultaneous non-overlapping quantity reservations.
2. During an active reservation, must the whole listing remain unchanged, or may the seller cancel its unreserved remainder? Recorded admin reasons do not replace authorization. Settled trades cannot be erased by cancellation.
3. Confirm expiry seven times twenty-four hours from server creation, with no extension on partial fill, and decide whether listing expiry ends an outstanding reservation. Proposed treatment: the earlier listing/reservation expiry wins; uncommitted cash/units are released once. Open price changes remain cancel/recreate unless explicitly amended.
4. What does the mentioned "four months" mean: original tenor, loan age or remaining time? No such cutoff was found in the reviewed BRS/proposal; it is not adopted. Near-maturity, matured/fully-paid and Arrears/Default/Disputed are distinct conditions.

Minimum fill size, allowed unit granularity and residual handling must agree with Q11. Aminu/Erastus must update partial-fill settlement, fee, cancellation, expiry, halt and cutoff race tests before schema freeze.

**Answer:** PENDING.

### Q10 — Transferable payout ceiling and fee contract

Captured: seller-selected prices at or below a payout ceiling, with 0.5% taker and 0.2% maker charges. Proposed roles for this fixed-price market: seller creates the listing (maker); buyer accepts it (taker). Neither charge is a rebate.

**Kimani + Robert:**

1. Confirm the ceiling concerns remaining contractual payouts attributable to the exact units transferred, excluding paid and already-vested seller entitlements. Choose gross or net-of-future-payout-fees cashflows, and whether the ceiling limits gross consideration alone or the buyer's fee-inclusive debit. This matters at the upper price boundary.
2. Confirm both fees use settled gross consideration, are rounded half-up to whole francs once per partial fill, and have no additional minimum fee unless explicitly approved. Confirm the other fee lines are unchanged and specify the effective version/in-flight-order treatment.

The current BRS uses 3% seller-only secondary fees and no buyer acquisition fee: this requires an explicit amendment, not an extra fee stacked on 3%. Disclose gross price, each party's fee/net and future payout fees. Future contractual cashflows are not guaranteed value, liquidity or realized return. Robert's protective intent is recorded; required external review of the pricing structure and BR-75 interpretation remains separate.

**Answer:** PENDING.

### Q11 — What the RWF 1,000 minimum permits

If selected as minimum **executed gross consideration**, RWF 1,000 permits otherwise eligible secondary trades from 1,000 to 4,999 that the proposed 5,000 minimum would exclude. It is not a minimum face value or a fixed price per unit, and it does not change the primary-investment minimum.

Illustration only, assuming the Q10 roles/base and a price permitted by the selected ceiling: gross 1,000 → buyer fee 5, total debit **1,005**; seller fee 2, net receipt **998**. These exact percentages need no fractional-franc rounding in this example. It is not an activated fee or return guarantee.

**Kimani + Robert:** Confirm or reject 1,000 as the secondary-only executed minimum. Decide allowed unit granularity and treatment of a residual worth less than 1,000: prohibit fills creating untradeable residuals, allow an explicit final-residual exception, or retain the residual for payout/combination with the owner's eligible same-Note units. An 800 residual cannot be sold under a universal 1,000 minimum without an exception. No cross-Note bundle, Rozine buyback or guaranteed exit is proposed.

**Answer:** PENDING.

### Q12 — Legitimate activity, malicious conduct and review safeguards

The existing SEC-R10 numerical thresholds create **review-only flags, not automatic penalties**. Frequent trading, repeated cancellation, reciprocal trades or a deep discount is not by itself a fraud finding. The thresholds were not calibrated.

Proposed conduct-policy scope for Kimani/Robert and qualified counsel to refine:

- Wash/self/same-beneficial-owner trades or collusive trades intended to fabricate price, demand or volume.
- Deceptive orders/reservations without genuine trading intent, including attempts to obstruct others; spoofing/layering terminology applies only where supported by the actual market design.
- Material false statements, fabricated evidence or concealment of information required for informed consent.
- Misuse of confidential information or privileged access, including front-running where applicable.
- Identity/account fraud, unauthorized trading, double-selling or evading ownership, unit/cash reservation or eligibility controls.
- Illicit-funds activity and evasion of applicable financial-crime restrictions, with reporting/handling governed by the approved compliance process.

**Kimani + Robert:** Approve/amend the categories; define evidence, reviewer, permitted interim safeguards, reasons, notice/appeal where lawful, and release authority. Choose which uncalibrated numerical flags to retain, revise or remove. Distinguish hard integrity denials from review cases and authorized sanctions. Do not silently remove existing self/related-party restrictions or interpret "no apparent harm" as exemption from law.

CMA expressly includes investor protection against fraud, deceit and manipulation within its mandate; this supports the need for review, not a claim that this proposed list is a complete legal rulebook. [CMA role, checked 17 September 2026](https://www.cma.rw/about-us/role-of-cma).

**Answer:** PENDING.

## 3. Amendment and evidence handoff — not performed by this sheet

| Area | Reconcile after exact owner decisions | Acceptance evidence needed before claiming implementation |
|---|---|---|
| Terms/history/seasonality | BR-20, D-14, plan C-21, UW-R03, pricing and schedule inputs; retain old policy versions | Allowed/disallowed tenors, history gaps/staleness, seasonal timing, downside and migration cases; no automatic change to flat-return bounds |
| DSCR/Coverage/caps | BR-14, D-11B/D-13, D-12/UW-17 and UW-R04/UW-R05 | Exact below/at/above thresholds, denominator validity, final-schedule resizing, below-3M, revenue/exposure/concurrent-cap cases |
| Material events/risk | UW-R06/UW-R07, D-23/D-25/D-28 and accepted-to-issued transitions | Event taxonomy, stale-worker races, immutable issue facts, explicit timer/action/cure and authorized-release cases |
| Partial secondary settlement | D-26, Holding/Order/Reservation/Trade contracts, SEC-T01–SEC-T12 | Real PostgreSQL partial-fill/reservation/expiry/cancel/halt/cutoff races; wallet/unit conservation and replay; UI quantity and timeout states |
| Fees/minimum/price | BR-27/62/64/75, D-10/D-27, SEC-R04–SEC-R06 | Buyer fee-inclusive funding, gross/net ceiling boundaries, per-fill rounding/fragmentation, residual units and vested-entitlement exclusion |
| Conduct/provider/legal | SEC-R10, provider-access/data contract, D-39 and related external gates | Authorized access, missing/stale/disputed evidence, review-only flags, legitimate-activity cases and recorded independent/external dispositions |

The [existing fixture pack](policy-review-fixtures.json) and [48-vector source register](underwriting-vector-review.json) remain unchanged historical review artifacts. Their old examples are not proof of these revised choices. Build a separately versioned set of amended expected results after the choices are complete; retain provenance and independently reviewed evidence.

## 4. Confirmation record

| Required confirmation | Current evidence / remaining action |
|---|---|
| Robert — Product/Business/internal Legal | Attributed choices captured above. Confirm the exact revised wording and answer Q01–Q12 where in scope. |
| Kimani — Finance/Risk/Compliance/Audit Operations | Input was supplied as from both stakeholders; individual concurrence on the exact revised finance/risk/compliance wording remains to be recorded. |
| Aminu + Erastus — Engineering/Security | Contract feasibility, schemas, tests and UI/server agreement remain pending; drafting this sheet is not their joint acceptance. |
| Named eligible non-author reviewer | Name, scope, version and actual independent evidence/sign-off remain pending. |
| External legal/regulatory/provider authorities | Applicable D-39 and other external dispositions remain pending; internal approval cannot substitute for them. |
| Amendment activation | Effective version/date NOT_SET; affected artifacts, migration/in-flight scope and required approvals must be explicit before activation. |

Suggested reply format: `response-2026-09-17.1; role/name; IDs confirmed; Q01–Q12 answers or explicit exclusions; amendment effective scope; evidence reference; timestamp/timezone`. Do not record a blanket signature over unanswered fields. This sheet is prepared for review, **not sent**, committed, pushed, merged or deployed by its creation. Phase 0 and the Phase 1 behavioral contract freeze remain open.
