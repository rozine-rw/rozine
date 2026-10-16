# Appendix A — decision review and fixture preparation

**Status:** `PROPOSED — UNSIGNED — NOT ACTIVATED` · **Version:** review-2026-09-10.1

**Subsequent response, 2026-09-17:** See the [Robert/Kimani consolidated decision sheet](stakeholder-decision-sheet-2026-09-17.md) for captured choices, requested amendments and remaining questions. The proposal and pending-status statements below are the retained 10 September snapshot, not a claim that no answers have since arrived. No policy or historical fixture is activated by the response sheet.

**Goal:** Turn the unresolved Appendix A rules into choices that Robert and Kimani can sign, and prepare reproducible review examples without creating a production underwriting engine or pretending that an unsigned vector is baselined.

## Authority and deliverables

Governing sources: [BRS v1.0](../Rozine-BRS.md), [plan Appendix A](../Rozine_Phased_Implementation_Plan.md#appendix-a--underwriting-decision-worksheet-and-golden-vector-pack), D-11/D-11A–C/D-13/D-14/D-19A, and [source authority](source-authority-record.md). This review adds **recommendations**, not an amendment, signature, effective date or activated policy. The selected product rules remain as recorded; this draft does not reopen their 10–15% flat return, 3/6/9/12 tenors, one-decimal rating, exact-money arithmetic, 1.25 TargetDSCR or 90-DPD/dual-approved-UTP default backstop.

Companion artifacts:

- [All 48 source-vector dispositions](underwriting-vector-review.json), preserving the source status and identifying what still lacks fixtures, implementation or approval.
- [Synthetic review examples](policy-review-fixtures.json), with exact input hashes and expected arithmetic. They are **normalized test inputs**, not bank statements, provider evidence or audited Business records.
- [Secondary contract proposal](secondary-contract-review.md) for D-26/D-27.
- [Pest review checks](../../tests/Unit/PhaseZeroPolicyReviewTest.php). Passing checks prove source-register integrity and example arithmetic, **not** application behavior, independent validation, fixture baselining or approval.

No code under `app/`, no schema, no provider, no live record and no existing Pulse formula is changed by this preparation. Existing prototype tests remain implementation evidence only; their pass cannot approve the production policy candidate.

## Decision sheet — recommendations, not selected answers

Every row remains `PENDING OWNER DECISION`. **K** is Kimani (Finance/Risk, Compliance, Audit Operations), **R** Robert (Product, Business, internal Legal), **A/E** Aminu/Erastus (Engineering/Security). Required external authority and a named non-author independent approver remain additional.

| Review ID / existing gate | Recommended disposition | Main alternative / trade-off | Owners / closing evidence |
|---|---|---|---|
| UW-R01 · UW-06 OwnerDraw | Deduct verified owner distributions/personal withdrawals once, outside operating outflow. Use the greater of verified-window monthly mean and an evidenced recurring monthly commitment. Missing/ambiguous evidence is a review gate, never implicit zero | Historical mean alone is simpler but can understate an evidenced increase. An unverified estimated allowance would introduce an unsupported underwriting input | K/R; A/E contract review. Signed classification/evidence rules and zero/missing/payroll/reversal/one-off vectors |
| UW-R02 · UW-07 debt service | Build an evidenced obligation calendar. Use the greater of verified historical mean monthly debt service and the maximum total contractual service due in any month of the candidate tenor; include existing Rozine obligations and external principal/interest/mandatory charges once | Forward monthly average admits more capacity but can hide a balloon or peak instalment; a month-by-month capacity constraint is more expressive but changes the simple scalar contract | K/R; A/E. Signed calendars, debt/operating-flow separation, balloon/maturity/unknown-debt vectors |
| UW-R03 · UW-08 volatility trim | Upper-tail winsorization of valid monthly NOCF: cap the highest `max(1, floor(n/5))` observations at the largest retained observation; retain every downside month; exact mean afterward | Symmetric trimming also removes low months and can conceal downside. A median is robust but discards more magnitude information. The proposed fraction is a review candidate, not a validated credit-risk calibration | K/R; A/E. Approve algorithm after sensitivity/labelled-history review, with single-upside-outlier, negative month, ties, 6/12/24-month and non-positive-CFADS examples |
| UW-R04 · UW-13 Coverage precision | Retain exact numerator/denominator as authority; allow a six-decimal derived ratio for display/cache only. Compare exact fractions for Health; display two decimals ordinarily, but a boundary label/expanded precision must explain any rounded apparent threshold crossing | Six-decimal stored authority is smaller but can change decisions close to 1.00/1.25. Binary floating point remains prohibited for financial/decision authority | K/R; A/E. Boundary/invalid denominator/serialization/replay vectors; no rounded decision input |
| UW-R05 · D-12 / UW-17 | Explicitly reject the prototype RWF 5M–50M Note bounds and 35% annual-revenue cap for this baseline. Keep computed capacity and all independently approved participant, exposure and sandbox limits | Retaining any of these values requires a reasoned product/risk amendment and back-testing; neither PDF nor existing Pulse constants constitutes approval | K/R; A/E. Signed D-12 disposition; then GV-039 becomes a rejection-regression case, not before |
| UW-R06 · UW-25 material events | Adopt the versioned event/invalidation contract below: verified monthly updates, verified financial/ownership/operating changes and approved compliance events immediately invalidate affected provisional results, then trigger ordered replay-safe recalculation | Manual periodic rescoring is easier but leaves stale offers valid; recalculating all historical contracts would violate immutability | K/R; A/E. Signed taxonomy, effective time and race/stale-result tests |
| UW-R07 · UW-24 / A.3.4 risk activation | Adopt the explicit trigger/action/cure proposal below, keep standing separate from Note risk, use worst active trigger per dimension, and keep PD null/NOT_CALIBRATED | Automatic default/party freeze on a reporting miss is rejected by governing policy. A different matrix needs explicit participant-impact review, not just different labels | K/R; A/E and independent review. Signed complete matrix, denied/authorized cures and competing-trigger vectors |

The economic trade-off in UW-R02/R03 must be reviewed together: both are deliberately conservative candidates and can reduce capacity substantially. No historical portfolio, labelled credit-outcome corpus or provider-attested statement corpus was supplied in this work, so there is **no claim of calibration, predictive performance or regulatory acceptance**.

## UW-R01 — OwnerDraw recognition

1. Build a versioned classification from each source transaction to exactly one of operating flow, debt service, owner withdrawal/distribution, transfer, reversal or review-required. Preserve the original and correction lineage. An owner salary already recognized as an operating expense is not also OwnerDraw.
2. A transfer between the Business's own declared accounts is not OwnerDraw; a transfer to an owner's personal beneficiary must be classified from evidence, not by assuming every similarly named account is Business-owned.
3. Sum verified, unreversed owner withdrawals over the same approved complete-month window as NOCF, divide exactly by the number of months, then compare with an evidenced forward recurring monthly commitment. Do not round intermediate means.
4. One-off owner distributions remain in the observed total unless K approves a separately versioned exception with evidence; a note saying “one-off” does not erase cash consumption.
5. Verified zero is explicit evidence/attestation plus reconciled transactions. Missing evidence, unexplained owner payments, unverified future commitments or conflicting classifications prevent a binding capacity result and enter manual evidence review.
6. Store both candidate amounts, the selected amount, the binding reason, evidence IDs, approver and policy version. Reject any double deduction from operating outflow and OwnerDraw.

This is a candidate recognition rule. It does not itself approve a document type beyond evidence-policy-v1, an owner-salary tax field or a business-authority model that bypasses D-64.

## UW-R02 — debt normalization and peak obligation

Use a deduplicated obligation ID, verified creditor/schedule, currency, remaining principal, due dates, mandatory contractual charges, payment evidence and classification lineage. The core supplies current Rozine contractual obligations; approved external evidence supplies other debt. Exclude new debt proceeds from operating inflow and remove separately classified debt service from operating outflow before deducting it here.

For the proposed scalar:

```text
historical_mean = verified debt-service total in the common window / valid months
forward_month_total[m] = sum of every existing obligation's required service in month m
ExistingDebtService = max(historical_mean, max(forward_month_total))
CFADS = exact winsorized NOCF mean - ExistingDebtService - OwnerDraw
```

The forward window is the candidate Note's actual approved tenor; future due dates follow the approved schedule/calendar policy. Quarterly service or a balloon is charged to its due month, not spread away. Fully discharged debt requires settlement evidence; a disputed or unknown schedule is a review gate. Recheck at calculation, offer and issue. Do not deduct the candidate new Note's own payment as existing debt; its service is already represented by DSCR and capacity. A non-positive CFADS yields no positive capacity, not an invented zero-risk result.

## UW-R03 — deterministic one-sided winsorization

The same evidence-policy-v1 window supplies every valid observation; no missing month is zero-filled. For `n` from 6 through 24:

```text
k = max(1, floor(n / 5))
sorted = sort original verified NOCF ascending
cap = sorted[n - k - 1]                  # zero-based index
adjusted[m] = min(original[m], cap)
trimmed_nocf = sum(adjusted) / n         # exact, unrounded
```

Persist originals, cap, adjusted values, affected period IDs and exact sum/count. Ties are stable and cannot change the result. Negative/zero months remain; never replace them with the median or clamp them positive. Existing approved scorecard `relative_mad` and positive-month factors continue to use the **original verified NOCF series**; this capacity trim does not silently rewrite score inputs.

Examples in the fixture pack show a high outlier's excess being fully capped and a downside month being retained. This is not a claim that one observation can never affect the threshold when the composition of the entire series changes. Approval requires sensitivity review across realistic series, not merely this synthetic proof.

## UW-R04 — exact Coverage and truthful formatting

Authoritative comparison uses cross multiplication of exact integers/Decimals, not formatted division. Preserve the denominator components so Admin can explain them. A zero, negative or missing denominator remains unavailable/Watch as already approved; it does not become numeric zero. A valid `1.249999` remains Watch even if ordinary formatting would display `1.25`; show “below 1.25” or enough precision alongside it. A six-decimal derived ratio is never reused for Health, scorecard normalization or eligibility. Keep original numerator/denominator and rounding/version metadata for replay.

## UW-R06 — event, invalidation and ordering contract

| Candidate event | Immediate effect | Recalculation / authority |
|---|---|---|
| Verified monthly report or linked verified amendment | Invalidate provisional calculations based on superseded inputs; preserve prior result/history | Recalculate from the new verified version; never edit issue rating, contracted yield or repayment schedule |
| Verified new/changed debt, owner distribution commitment, corrected financial classification or evidence coverage | Mark affected offer/calculation stale; block acceptance/issue until revalidation | K-authorized verified input change; append lineage, never rewrite raw evidence |
| Verified ownership/mandate/control change, operating suspension/restart, material premises/business change | Hold affected provisional decision pending KYB/material-event review | D-64 and evidence-policy-v1 govern; seasonal/restarted/material-event underwriting needs the approved 12-month history |
| Confirmed arrears/cure, report breach/cure or dual-approved UTP/default event | Propagate only the authorized lifecycle effect; stale policy/read models cannot authorize a new commitment | Recalculate permitted rating/health/standing/risk views; contractual terms remain immutable |
| Licence/evidence-seal invalidation or compliance restriction | Prevent use of invalid evidence/authority for new issue; flag impacted live records for authorized review | Audit Operations/Compliance decides the lawful remedy; no automatic erasure of a published report |
| Policy activation | New calculations use its effective version; in-flight offer acceptance rechecks it | Retain old inputs, versions and results for exact historical replay; do not retrospectively reprice live Notes |

Each event carries `event_id`, aggregate ID/revision, occurrence/effective/recorded UTC times, actor, reason, evidence/version references and correlation. Use a transactional outbox. Deduplicate by immutable event ID; serialize per aggregate revision. Workers commit a result only if input and policy revisions still match; a superseded worker records a discarded/stale outcome and cannot overwrite newer facts. No transport timestamp, background-job finish order or old client cache may decide which version wins.

## UW-R07 — proposed standing/risk/action/cure matrix

Each row below is a **candidate trigger**, except the already approved default and rating-cap facts it references. Compute standing and Note risk separately, taking the highest applicable restriction in each; a compliance suspension is not by itself a default diagnosis. A report's condition, a Note's tradability, Audit Partner yield and a Party's access are distinct state machines.

| Trigger | Candidate standing / Note risk | Permitted automatic effect | Cure / release authority |
|---|---|---|---|
| No active adverse trigger, current verified evidence, Coverage ≥1.25 | Good / RB0 | Routine monitoring | Re-evaluate after each material event |
| Coverage ≥1.00 and <1.25, or Coverage unavailable | Review / RB1 | Explain enhanced review/unavailable evidence; no inferred default or automatic wallet freeze | Valid current evidence and exact recomputation clear this trigger; any other trigger still wins |
| Valid Coverage <1.00 | Restricted / RB2 | Block new origination pending financial review; intensify monitoring, without automatically changing Note lifecycle to Default | New verified evidence plus authorized risk review; no silent timer-based cure |
| Monthly submission/co-signature SLA breach | Review / RB1 | BR-46 freezes the **responsible Audit Partner yield share** and flags/notifies holders; no automatic note default or penalty | Valid co-signed/amended report clears reporting breach; attributed yield release requires authorized Admin resolution |
| Confirmed post-grace arrears below 30 DPD | Review / RB1 | Approved arrears workflow and investor warnings; applicable Note lifecycle blocks trading; score capped at 58 | Ledger-confirmed cure under D-23/D-25; no assumed grace duration or unapproved fee |
| Confirmed arrears 30–89 DPD | Restricted / RB2 | Intensive review; score cap 38; Note remains non-tradable | Ledger-confirmed cure plus approved D-25 risk review, preserving history |
| At least 90 DPD or earlier dual-approved unlikely-to-pay | Restricted / RB3 | Governing Default workflow; no trading/new origination; score cap 38 | Only the approved D-25 recovery/default-resolution process; recovery does not delete default history |
| Lawful Compliance suspension, verified critical identity/authority failure, or confirmed fraud restriction | Suspended / existing financial risk or RB2 if a separately verified financial trigger requires it | Apply only recorded authority's scope; no rule may fabricate PD or default | Named authorized release with reason/evidence; financial/default triggers remain until their own cure |
| Evidence authenticity investigation without a confirmed fraud finding | Review / at least RB1 | Hold affected **new** evidence/offer decisions and create an attributed review case, not a conclusive fraud/default label | Audit Operations/Compliance closes the case with verified evidence and recorded outcome |

Unlisted cases fail closed for the requested privileged action and enter review; they do not acquire an invented customer penalty or default classification. PD remains `null/NOT_CALIBRATED` in every launch row. Health/standing/risk are not second public ratings. Any numerical grace, cure waiting period, escalation SLA or financial penalty still depends on its explicit D-23/D-25/D-28 decision; this matrix proposes no substitute.

## Golden-vector progress — honest evidence levels

The source matrix remains **34 READY-TO-BASELINE, 7 BLOCKED, 6 REJECTED, 1 QUARANTINED, 0 BASELINED**. The JSON register covers all 48 IDs, records source fingerprints and links available synthetic arithmetic examples. The 34 examples touch **22 source vectors partially**, alongside secondary arithmetic; 26 vectors still have no example. A vector with no review example explicitly says it still needs scenario construction; it is not quietly counted as tested. Multi-variant source vectors retain their full source condition even when a review example covers only one subcase.

The review fixture pack checks selected exact pricing/rating/schedule/DSCR/Coverage/Pulse arithmetic, proposed trim/debt/draw examples and secondary fee/price-boundary examples. It does **not** include provider-attested originals, an ingestion corpus, a deployed calculator, persistence/action/Resource/UI/native/Admin replay implementations, or independent expected-result approvals. Those are the remaining layers in Appendix A.7 and must be completed before baselining. Review fixture hashes identify synthetic inputs only and never stand in for missing bank/MoMo originals.

## Sign-off and activation checklist

- [x] Preserve existing approved/FIXED invariants and enumerate each unresolved policy choice with a recommendation, trade-off and owner.
- [x] Prepare all 48 source dispositions, exact synthetic review examples and programmatic checks without changing source statuses or production code.
- [ ] K/R choose or amend UW-R01–UW-R07 and approve the corresponding revised worksheet; A/E confirm contract/test feasibility.
- [ ] Supply/approve the labelled normalization/statement corpus, transaction taxonomy, confidence configuration, signed scorecard and complete risk/action/cure matrix.
- [ ] Replace every pending signature, fixture-set hash, policy/calculator version and effective-time field with actual approved evidence; name a non-author independent expected-result approver.
- [ ] Resolve all other Phase 1 decision dependencies, including D-05/D-09/D-10/D-15/D-18/D-22/D-23/D-25/D-29–37/D-64 and the applicable provider/legal contracts; do not bundle them into an assumed signature here.
- [ ] Implement the approved production slices and pass all applicable Appendix A.7 layers plus Section 11 exact-SHA gates; migrate review examples into baselined fixtures only through an approved versioned record.

**No signature has been supplied in this work.** Product approval, internal domain approval, independent testing and required external authorization are different gates. Neither this document nor passing its fixture tests closes Appendix A or authorizes real-money behavior.
