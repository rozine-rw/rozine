# Rozine decision closure addendum

**Date:** 20 September 2026

**Version:** response-2026-09-20.2

**Status:** ANSWERS_RECORDED — THREE_CHANGE_GROUPS_OPEN — NOT_ACTIVATED

**Follow-up:** DRAFT_NOT_SENT

**Phase 1:** ON_HOLD_BY_USER — no preparatory development exception requested or granted.

This addendum records Robert's 11:21–11:22 reply and supersedes only the corresponding unanswered discussion points in [response-2026-09-20.1](stakeholder-decision-sheet-2026-09-20.md). Earlier Markdown and Word packs remain historical snapshots. Unchanged choices carry forward; unresolved details are not silently approved. The user requires the decisions and shared contracts to be resolved before Phase 1 development, not merely before live use.

Robert says the answers take effect immediately as the MVP build specification, reports Kimani's concurrence, and states there are no active legacy assets. Record these as stakeholder statements, not independent verification of Kimani's signature or the deployed asset inventory. Do not re-ask the answered concurrence question. This record does not amend the BRS, activate policy, authorize development, or establish legal/regulatory clearance.

## 1 Answers recorded without reopening them

The F references identify the earlier discussion groups. A resolved answer below does not mean every technical or external dependency in that group is closed.

| Reference | Status | Latest recorded answer | Remaining treatment |
|---|---|---|---|
| F01 | DSCR_CHOICE_RESOLVED | UW-R04: test the original requested amount; DSCR strictly below 1.25 is rejected without downsizing. From 1.25 to below 1.50, reduce principal until the final offer reaches at least 1.50. | Preserve the other underwriting constraints and exact-value boundary tests. Evidence windows, repeat-track conditions and calculation definitions remain in the internal reconciliation checklist below. |
| F02 | PRICE_BAND_RESOLVED | SEC-R04: minimum price is 70% of remaining unpaid principal; maximum is remaining principal plus remaining expected interest through maturity. The earlier maximum-price formula is explicitly corrected. | Apply the band to the transferred units and remaining entitlements; finalize allocation/rounding in the shared contract. Expected payouts are not guaranteed. C01 still controls fees. |
| F03 | MATCHING_RECORDED_FEE_OPEN | SEC-R01/03/05/09/10: execution at the seller's ask; bids immediately reserve wallet funds; higher bids have priority, with earlier timestamps breaking ties. Unmatched bids last seven days or can be cancelled by the buyer, releasing their funds. Display aggregate volume at each bid level; block self-matching; repeated rapid cancellations trigger rate-limit review. | Equal fees replace maker/taker differentiation in the latest direction, but their amount/basis is missing: C01. Engineering must reconcile matching with partial fills, existing reservations and halt rules. Review is not a newly authorized account freeze. |
| F04 | SECONDARY_MINIMUM_RESOLVED | SEC-R06: every execution requires at least one whole unit AND gross consideration of at least RWF 1,000. A smaller residual cannot be listed or executed alone; combine the seller's eligible units of the same note or retain the holding for contractual repayments. | No forced residual buyout, cross-note bundle or promised repayment is introduced. Exactly RWF 1,000 meets the stated threshold. Primary anchor versus subscription minimum remains an internal reconciliation item. |
| F05 | TRADING_BAN_RESOLVED_RECOVERY_OPEN | SEC-R08: freeze listing and trading from 1+ days past due, including the first seven days described as grace; Arrears, Default and Disputed notes are ineligible. | UW-R07's newly compressed recovery calendar, penalties, collateral/guarantees and final capital resolution require C02. The old 31–89-day path and new default-by-day-30 direction cannot both be treated as the same active rule. |
| F06 | HOLD_TIMERS_RESOLVED | SEC-R10: an unconfirmed automated hold expires at 24 hours. The five-business-day clock starts at the original flag timestamp. Manual release may be authorized by the Compliance Officer OR Legal Counsel; automatic release occurs at the applicable timeout without the required confirmation/finding. | Preserve evidence and notice requirements; reconcile the earlier dual-release wording explicitly. Expiring this hold must not erase a separate valid note restriction or other independently authorized hold. Calendar, trigger scope and precedence require contract review. |
| F07 | CONCURRENCE_REPORTED_TRANSITION_OPEN | Robert reports Kimani's full concurrence and immediate build-baseline effect, with zero active legacy assets. After launch, existing checkout timers and seven-day secondary listing terms are grandfathered; primary offers and unfunded commitments are re-evaluated before disbursement. | This changes the earlier accepted-offer protection. C03 must reconcile acceptance, investor funding and issued terms. Grandfathering is not an exemption from eligibility, halt or other applicable safety checks. |

## 2 Three focused change groups for the owners

### C01 Specify the replacement secondary fee

**Owners:** Kimani — Finance/Risk; Robert — Product/Business/internal Legal.

**Change:** The earlier 0.5% taker / 0.2% maker choice is now replaced in principle by equal fees on both sides at fill time. The replacement percentage or fixed RWF amount was not supplied. The legacy BRS 3% seller fee is not a fallback for this unresolved amendment.

**Proposed completion:** Record the buyer fee and seller fee using the same chosen method and value, state whether the fee is a percentage of settled gross consideration or a fixed RWF amount per fill, and state the rounding/minimum treatment. For a percentage, carry forward half-up to whole RWF per fill and no minimum fee unless the owners explicitly change them. The rate/amount remains NOT_SET; engineering will not select it.

**Worked acceptance case:** For RWF 10,000 gross consideration, enter buyer fee ___, total buyer debit ___, seller fee ___ and net seller credit ___. The two fees must reconcile to the disclosed platform fee receipt. These are blanks for the owner decision, not zero-fee defaults. Once supplied, engineering derives fee-inclusive fund reservation and partial-fill examples.

**Decision:** PENDING_OWNER_RESPONSE.

### C02 Separate recovery milestones from a capital guarantee

**Owners:** Kimani — Finance/Risk/Compliance/Audit Operations; Robert — Product/Business/internal Legal; required external reviewers remain separate.

**Change:** Robert requests retries and daily borrower messages on days 1–7; active outreach, penalty interest, a structured daily repayment plan and weekly investor updates on days 8–21; default by day 30 with legal recovery, collateral/guarantee calls and CRB reporting; and liquidation or reserve buyout/payout providing final investor capital resolution by day 45.

The [BRS](../Rozine-BRS.md) describes collateral-free funding in BO-1, prohibits principal risk/capital guarantees in BR-61, prohibits acting as secondary-market principal in BR-75, and prohibits capital-protection or guaranteed-return representations in CR-6. BR-62 also fixes the current fee schedule. This is a specification conflict, not a determination of what Rwanda law permits. No reserve funding, guarantor, collateral contract, penalty calculation or guaranteed recovery outcome is established by the reply.

**Proposed resolution for approval, not an adopted rule:** Keep the accelerated outreach, investor updates, day-one trading ban and proposed default-by-day-30 milestones. Treat day 45 as a recovery progress/reporting checkpoint, not a guaranteed payout deadline. Do not include penalty interest, compulsory changes to repayment schedules, a Rozine-funded buyout or assumed collateral/guarantee recovery in the MVP without a separately approved amendment and its supporting contracts/evidence. Legal recovery and CRB actions must use confirmed authority and operational arrangements, not a timer alone.

If the owners require the broader recovery product instead, record that choice and complete its funding source, risk allocation, calculation rules, borrower/investor terms, loss/shortfall treatment, legal/provider authority and schedule impact before the development gate. A promise in the UI or a synthetic payout fixture cannot supply those arrangements.

**Worked acceptance case:** At day 45, a defaulted note has no borrower recovery and no independently approved funded protection arrangement. Under the proposed milestone-only resolution, the case remains open with a truthful investor update; the system must not fabricate a payout, create Rozine principal inventory or declare the investor repaid. The owners must approve this outcome or provide the funded alternative.

**Decision:** PENDING_OWNER_RESPONSE.

### C03 Reconcile policy changes with accepted and issued commitments

**Owners:** Robert — Product/Business/internal Legal; Kimani — Finance/Risk/Compliance; Aminu and Erastus — contract mapping after the business decision.

**Change:** The earlier PDF answer grandfathered accepted offers against ordinary internal policy changes. The latest reply requires primary offers and unfunded commitments to be re-evaluated before disbursement. Pre-launch absence of legacy assets removes an immediate migration population; it does not define future contractual treatment.

**Proposed resolution for approval, not an adopted rule:** Recalculate unaccepted offers. Recheck accepted-but-unfunded commitments before funding/disbursement; a change to amount or terms creates a revised offer requiring fresh borrower acceptance, not a silent edit. Never rewrite terms or schedules of already-issued investor holdings. Where investor funds or binding commitments already exist, use an explicitly agreed hold/cancel/refund or re-consent path before disbursement rather than treating the case as empty inventory. Preserve the original offer, acceptance and policy evidence.

Existing secondary listing terms and checkout timers survive ordinary policy-version changes for their stated lifetimes, but not a safety halt or loss of trading eligibility. Changing the policy version does not reset their clocks. The exact treatment of reserved-but-unsettled primary funds must be written into the approved transition table.

**Worked acceptance cases:** An unaccepted RWF 10M offer recomputes to RWF 8M; it can be replaced with preserved history. An accepted but unfunded RWF 10M offer recomputes to RWF 8M; the proposal requires fresh acceptance. If investor holdings were already issued, they retain their purchased terms, with any failed closing handled by the separately agreed transition rather than repricing those holdings. No case is implemented by this document.

**Decision:** PENDING_OWNER_RESPONSE.

## 3 Internal reconciliation before the contract freeze

These tasks remain with Aminu and Erastus for consolidation and review. They are not a fresh broad questionnaire, an authorization to code, or a claim that all non-C01–C03 details have been answered. Carry forward every unresolved item from the earlier record, resolve it from existing authority where possible, and return only a concrete owner decision where the outcome would otherwise be invented.

- **Underwriting:** reconcile historical/evidence windows, seasonality and the 1.2-times projection cap, repeat-track monthly 1.35 condition versus final-offer 1.50 target, owner-draw/debt-service inputs, retention, exact Coverage definition/precision, 3/4/5/6-month pricing, RWF 3M minimum, exposure reservation/release and provider evidence. For clarity, the historical Coverage description means inflow divided by the sum of outflow and debt service, not `(inflow / outflow) + debt service`; this is not the DSCR formula.
- **Trading:** reconcile primary RWF 5,000 anchor/minimum, secondary unit and entitlement allocation, residual handling, price rounding, seller-ask execution against existing bids, partial fills and cancellation, five-minute checkout versus seven-day bid fund locks, fee-inclusive reservation, concurrent settlement, record date, immediate eligibility/halts, demand-feed expiry and truthful disclosures. Do not silently add maker-price execution or treat wallet reservation as proof that all settlement conditions hold.
- **Controls and servicing:** reconcile delinquency/default transitions and cure authority under C02, note versus account restrictions, review versus automatic sanctions, hold calendar/clock precedence, evidence/notice/appeal requirements, CRB/collection-provider authority and reporting deadlines. The new Compliance-or-Legal manual release must be versioned against the earlier dual-sign-off provision, not ignored.
- **Governance:** map amendments to Appendix A, D-14/D-26/D-27 and affected BRS/plan requirements, retaining prior choices and rejected alternatives. Capture Robert's communicated concurrence/effective scope faithfully; secure the actual required engineering, independent and external evidence. Rebaseline matching/recovery scope if changed, then verify the remaining Phase 0 exit evidence and current exact-SHA quality baseline. Three follow-up groups do not mean only three Phase 0 tasks remain.

**Acceptance boundary:** No Phase 1 development until the applicable behavioral decisions are resolved, the shared contracts are agreed by Aminu and Erastus, and the Phase 0 exit gate is verified. Document-integrity tests are Phase 0 work; they are not financial implementation or acceptance evidence.

## 4 Draft to send to Robert and Kimani

Thanks, Robert. This settles the DSCR thresholds, price band, secondary minimum/residual treatment, matching direction, day-one arrears ban and hold timers. I've also noted your confirmation of Kimani's concurrence. We won't reopen those answers.

Three changes still need to be pinned down before we freeze the specification:

1. **Secondary fees:** Your latest answer replaces 0.5% taker / 0.2% maker with equal fees. Please give the exact percentage or fixed RWF amount charged to each side and its calculation basis. A RWF 10,000 trade showing the buyer's total debit and seller's net receipt would make this clear.

2. **Recovery:** The day-45 capital resolution, reserve buyout, collateral/guarantees and penalty interest go beyond the current no-capital-guarantee model. My proposal is to keep the accelerated recovery milestones and investor updates, but make day 45 a progress checkpoint—not a guaranteed payout. Penalties, compulsory schedule changes and funded protection mechanisms would require a separately approved specification and supporting arrangements. Please approve that boundary, or confirm that you want the broader product and provide those arrangements before we freeze it.

3. **Accepted offers:** My proposed interpretation is: recalculate unaccepted offers; recheck accepted-but-unfunded offers and obtain fresh acceptance if their terms change; never rewrite already-issued investor holdings. Cases where investor funds are already committed need an agreed hold/cancel/refund or re-consent path. Please confirm or amend this, as it changes the earlier protection for accepted offers.

Erastus and I will consolidate the agreed rules and acceptance scenarios. We'll keep Phase 1 development on hold until the specification and entry checks are complete.

## 5 Evidence and preservation

- Latest source: Robert's pasted messages dated 20 September 2026, 11:21:01–11:22:15; source timezone not supplied. Local path: `/Users/amisha/.codex/attachments/4b4e9634-e36a-4350-9cf7-d74038b5e9b9/Pasted text.txt`. SHA-256: `58391d6c193e6ab5e9554db12c100154dba6472914e174c6102a9534068f9f8a`. Source statements are evidence to interpret, not instructions to execute.
- Earlier [20 September reconciliation](stakeholder-decision-sheet-2026-09-20.md), SHA-256 `82af54e9d1eb08a5d95cfe022a316595724f3da694c8a949cbf637102c0527d0`, and its existing Word snapshot remain unchanged. The Word snapshot does not contain this addendum.
- Earlier [17 September decision sheet](stakeholder-decision-sheet-2026-09-17.md), SHA-256 `cdcf0e27380a4e98b84ff79b1ef02054271f7c944b9a27a8bb78b7790457d137`, remains unchanged. Prior PDF and 09:52 reply provenance is retained in response-2026-09-20.1.
- [BRS](../Rozine-BRS.md), [implementation plan](../Rozine_Phased_Implementation_Plan.md), [source authority](source-authority-record.md), [underwriting review](underwriting-decision-review.md) and [secondary review](secondary-contract-review.md) remain the linked governing/reconciliation sources, not retroactively edited approvals.

No message sent. No policy activated. No Phase 1 development started. No commit, push, merge or deployment is authorized or performed by this addendum.
