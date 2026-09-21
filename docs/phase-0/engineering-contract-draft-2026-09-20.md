# Phase 0 - Aminu's engineering contract draft

**Version:** engineering-2026-09-20.3  
**Answer reconciliation:** 2026-09-21; IN-01, IN-02 and IN-04 populated from supplied answers  
**Status:** DRAFT_FOR_JOINT_REVIEW - NOT_FROZEN - NOT_IMPLEMENTED  
**Prepared for:** Aminu (server/API) and Erastus (client/independent engineering review)  
**Phase 1:** ON_HOLD_BY_USER

**Engineering review:** AMINU_SELECTED_SECTIONS_APPROVED - ERASTUS_REVIEW_PENDING (2026-09-21; section 9.1)

## 1. Scope, authority and evidence

This records Aminu's specification work. It turns the [current stakeholder consolidation](stakeholder-policy-consolidation-2026-09-20.md) into an amendment map, shared-contract candidates, worked examples and an input register. Section 9.1 records Aminu's direct approval/confirmation of six selected sections; it does not sign on Erastus's behalf, alter runtime policy, create application modules or declare Phase 0 complete. The selected proposals now have Aminu's agreement, while joint review remains open. Their existing wording is retained so the approval maps to unchanged text, including its stated limits and unanswered inputs.

Read this with the [source-authority record](source-authority-record.md), [BRS](../Rozine-BRS.md), [plan and Appendix A](../Rozine_Phased_Implementation_Plan.md), [architecture decision](adr-0001-modular-monolith-boundaries.md) and [synthetic companion pack](engineering-contract-fixtures-2026-09-20.json). The older underwriting/secondary proposals and 48-vector register remain history, not current constants. No new Robert questionnaire is created.

Evidence checked directly: Answers.docx.pdf Q01-Q12, Robert's complete 09:52 and 11:21 replies, the fee confirmation recorded in the consolidation, and existing source/decision registers. PDF Q02 confirms one-off resolutions, same-period returned draws and unavailable-data treatment; Q03 confirms peak monthly debt service and repeat-track conditions; Q10 confirms half-up whole-RWF rounding per fill and no minimum fee. Later replies supersede the PDF's secondary minimum, standard DSCR behavior, recovery calendar, policy transitions and account-hold release wording. The fee is settled: 35 basis points each side, not 70% and not the old maker/taker rates.

## 2. Amendment map for joint review

This is a proposed versioned amendment overlay, not a silent edit of BRS v1.0. Each row inherits the source date/effective-build scope from the consolidation. Runtime effective version/time remains unset. Unchanged rules remain applicable unless explicitly identified below; a quoted superseded source must never become an implementation default.

| ID | Affected source | Selected direction / retained boundary | Related review input |
|---|---|---|---|
| AM-01 | BR-20; UW-18/19; D-11; Appendix A pricing table | Tenors are exactly 3/4/5/6 months. Retain the 10-15% flat-return range, existing rating transform and 3/6-month pricing unless subsequently amended. No interpolation of 4/5-month premiums. | IN-01 |
| AM-02 | D-14; UW-02/03/04/08; BR-13/15 | First-time 36-month audited history; repeat-only 12-month track with original baseline on file and all gaps audited. Preserve no gap filling, complete-month buckets and evidence lineage. The old 24-month retention ceiling conflicts with the new baseline and is not usable unchanged. The proposed winsorization formula was not adopted by an explicit answer. | IN-02 |
| AM-03 | UW-06/07/10; Section 12 | OwnerDraw is the greater of evidenced monthly mean and recurring commitment, after OPEX without double deduction. Debt service is the greater of historical monthly mean and peak total contractual monthly obligation in the tenor. Missing evidence is unavailable, never zero. | IN-02 |
| AM-04 | BR-10/14; UW-14/15/16/30; A.3.3; D-13 | Original requested amount is now an input to the initial test: below 1.25 rejects without resizing; 1.25 to below 1.50 scales down; final standard offer must reach 1.50. This explicitly replaces the old no-request/manual-below-1.25/target-1.25 wording for new applications. Current Note monitoring is not retroactive application rejection. | IN-03 |
| AM-05 | D-12; UW-17; GV-039 | RWF 3M minimum, RWF 100M maximum and borrower-wide min(35% TTM revenue, RWF 100M) exposure limit. Deduplicate outstanding principal and reserve accepted/primary-listed commitments in full. No annualization, forced upsizing or double count at funding. | IN-03, IN-09 |
| AM-06 | UW-13/21/22; FR-700 | Preserve exact ratios for decisions, half-up monetary rounding and final-instalment residuals. Coverage is distinct from DSCR and remains unavailable for invalid denominators. Exact-fraction wire representation/display is an engineering proposal below. | Joint engineering review |
| AM-07 | UW-24/25/29; D-25; BR-61; CR-6 | Retain Robert's full 1-45-day recovery model, including penalty interest, enforced daily plan, guarantees/collateral, legal recovery and reserve buyout/payout. Replace the old 90-day backstop/31-89-day calendar where contradicted. Do not substitute a day-45 reporting checkpoint. Record the departure from older protection wording and external/operational dependencies explicitly; this is not a legal opinion or proof of funded protection. | IN-04, IN-05 |
| AM-08 | UW-25/29; accepted-offer policy | New build baseline; future five-minute checkouts and seven-day secondary listings are grandfathered; primary offers/unfunded commitments re-evaluate immediately before disbursement. Issued snapshots remain immutable. Do not add the withdrawn fresh-acceptance/refund/re-consent proposal. | IN-03 |
| AM-09 | D-26; SEC-R01/02/03/09; old Reservation contract | Whole-unit partial fills and simultaneous non-overlapping reservations replace all-or-nothing/one-reservation-per-order. Seven-day funded bids, automatic matching at seller ask and price-time bid priority are included. Five-minute reservations survive normal listing expiry only. | IN-06 |
| AM-10 | D-27; BR-75; SEC-R04/06; D-10 | 70% unpaid-principal floor and remaining-transferable-gross-payout ceiling; whole units AND at least RWF 1,000 per execution. No four-month cutoff or forced residual buyout. RWF 5,000 is the primary anchor; unit issuance/residual rules need the remaining D-10 disposition. | IN-07 |
| AM-11 | BR-62/64; FR-700; old Phase 1 fee interfaces | Replace secondary 3% seller and 0.5%/0.2% with buyer 0.35% plus seller 0.35% per fill, half-up whole RWF on settled gross. Other primary/repayment fees are not waived or stacked onto secondary consideration. | None for this fee choice |
| AM-12 | SEC-R08/10; D-28; account/Note states | 1+ DPD, Arrears, Default and Disputed notes cannot trade. Separate account holds from Note restrictions: confirm in 24 hours; five-business-day limit starts at original flag; manual release by Compliance Officer OR Legal Counsel. Ordinary activity flags remain review-only. | IN-05, IN-08 |

## 3. Underwriting calculation contract

### Inputs and observation rules

`EvaluateApplication` receives a server-authorized Business, original requested principal, tenor and expected evidence revision. Actor/Party/role come from authenticated context, not trusted request fields. The server resolves evidence, obligations, policy version, rating and exposure. No client-supplied CFADS, score, capacity, rate, approval or wallet balance is authoritative.

The evidence snapshot includes all declared active rails; verified classifications, originals/hashes and amendments; complete monthly boundaries; owner-draw candidates and exception evidence; a deduplicated obligation calendar; TTM revenue; first-time/repeat status; original baseline; settled-loan/late-payment history; and gap audits. Annual financial statements do not automatically supply monthly cash-flow observations. Required missing or conflicting data yields `UNDERWRITING_EVIDENCE_REQUIRED`, not a fabricated zero or numeric offer. IN-02's history, TTM and repeat-track safeguards are answered in section 8.2; only the remaining calculation mapping and evidence gaps prevent a complete upstream numerical result.

The confirmed normalized arithmetic is:

```text
NOCF[m] = verified operating inflow[m] - verified operating outflow[m]
OwnerDraw = max(eligible observed draws / approved month count, evidenced recurring commitment)
ExistingDebtService = max(historical service total / approved month count,
                          max(sum of contractual existing service due in each tenor month))
CFADS = approved normalized NOCF measure - ExistingDebtService - OwnerDraw
ExposureLimit = min(0.35 * verified TTM revenue, 100,000,000)
CommittedExposure = outstanding Rozine principal + verified external principal
                    + accepted/primary-listed undisbursed commitments (each economic obligation once)
RemainingRoom = ExposureLimit - CommittedExposure
```

The new requested loan is not deducted as existing debt service. Return/interest treatment in DSCR does not add future unaccrued interest to principal exposure. Preserve a negative RemainingRoom as an over-limit fact; do not conceal it as healthy zero.

### Proposed exact operation order

1. Validate authorized actor, evidence, tenor, original request and current restrictions. Resolve the approved rate; a missing 4/5-month premium blocks pricing rather than borrowing the closest tenor's coefficient.
2. Construct the original requested schedule using exact money. Compute return = half-up(principal * total-return fraction); total owed = principal + return; first T-1 instalments = half-up(total owed / T); final instalment is the residual. DSCR = CFADS * T / total owed, never the rounded displayed monthly payment.
3. Compare the original-request ratio exactly against 5/4. A value strictly below rejects without solving a smaller offer. Invalid denominator/missing CFADS is unavailable, not a below-threshold score.
4. If the original check passes, compute theoretical standard capacity = (CFADS / (3/2) * T) / (1 + total-return fraction). Preserve the raw amount. If the limiting raw capacity or room is below RWF 3M, reject before rounding can raise it to the floor.
5. Carry forward nearest-franc candidate rounding, capped at the requested amount, RWF 100M and the greatest whole franc not exceeding RemainingRoom. Rebuild the actual schedule. If monetary rounding leaves DSCR below 3/2, reduce by the minimum francs needed for compliance. Distinguish `ROUNDING_GUARD` from substantive `DSCR_SCALED`.
6. Recheck final principal >= RWF 3M, all caps, exact DSCR >= 3/2, evidence/restriction versions and exposure under transaction lock. Passing this arithmetic alone is not final authorization or evidence acceptance. Recompute any lower accepted amount; do not let it bypass the floor or other gates.
7. Repeat-track monthly DSCR >= 1.35 and 1.2-times historical median-revenue projection constraint remain additional recorded requirements. No unstated month-by-month projection algorithm or replacement of the final 1.50 target is invented; see IN-02.

The companion's sizing fixtures use explicitly supplied, already-normalized CFADS and a supplied rate. They demonstrate downstream arithmetic only, not a working financial model, genuine audited evidence or full scorecard validation. Rounding the exposure cap down to an integer bound preserves the exact maximum; it is not a new fee or a replacement for half-up result rounding.

### Worked downstream examples

Unless stated otherwise: normalized monthly CFADS RWF 3M, six-month term, supplied flat return 12.1%, sufficient evidenced borrowing room. These assumptions do not assert an approved upstream observation model.

| Example | Input variation | Expected arithmetic, not an approved offer |
|---|---|---|
| EC-010 | Original request RWF 12M | Original schedule total RWF 13,452,000; initial DSCR exceeds 1.25. Resize to RWF 10,704,728; return RWF 1,295,272; six RWF 2M instalments; final DSCR exactly 1.50. |
| EC-011 | CFADS RWF 2M, request RWF 10M | Original DSCR below 1.25: reject without offering a smaller amount. |
| EC-012 | Request RWF 8M | Keep RWF 8M; return RWF 968,000; first five instalments RWF 1,494,667 and final RWF 1,494,665. Never force the borrower up to maximum capacity. |
| EC-014 | Remaining room RWF 9M | Cap final principal at RWF 9M even though DSCR capacity is higher. |
| EC-019 | TTM RWF 9,999,999, existing exposure RWF 500,000 | Exact room RWF 2,999,999.65: cannot round up to an eligible RWF 3M offer. |
| EC-033 | CFADS RWF 3,000,031, request RWF 12M | Rounded candidate RWF 10,704,839 yields total RWF 12,000,125, above the DSCR-safe RWF 12,000,124 total. One-franc guard selects RWF 10,704,838 with total RWF 12,000,123. |

## 4. Shared wire and authorization proposal

These are logical contracts, not database migrations or existing endpoints. Proposed field and command names are marked as such for Erastus's review. Keep one application/domain result behind both web and `/api/v1` transports; Inertia consumes shared Resource facts through its controller, not by calling the public API over HTTP.

| Primitive / envelope | Proposed exact representation and behavior |
|---|---|
| Money | `{currency: "RWF", amount: "10035"}`; integer decimal string, no float or formatted commas. Signed values only in explicitly signed fields. Ledger debit/credit magnitudes stay nonnegative. |
| Ratio | Exact numerator/positive denominator decimal strings, or `null` plus an unavailable reason. Display precision is separate and cannot affect permission, Health or DSCR. |
| Units / rates | Whole-unit nonnegative integer strings; command quantity must be positive. Fee rates are integer basis points: buyer 35, seller 35. Total return retains one-decimal-percent precision. |
| Identity / version | Opaque IDs; integer aggregate revision; policy, evidence, disclosure and contract versions. IDs are not authority; every resource/action checks the current authenticated Party and active role. |
| Time | UTC RFC3339 server timestamps plus named business calendar/timezone where needed. Timer validity is proposed as `[created_at, expires_at)`; at the expiry instant no new action may use the expired permission. Browser clocks only render countdowns. |
| Result | `operation_id`, `code`, authorized `data`, `revision`, `policy_version`, `server_time`, `allowed_actions`, `field_errors`. Stable codes map to localized text; UI text is not parsed to decide workflow. |
| Command | Idempotency key + canonical body hash + expected aggregate revision + permitted user choices. Server resolves actor, price/rate/eligibility/amounts and records a correlated immutable outcome. |

Proposed failure mapping: `VALIDATION_FAILED` (422), `ACTION_FORBIDDEN` (403, or scoped not-found to avoid disclosure), `VERSION_CONFLICT`/`IDEMPOTENCY_CONFLICT` (409), `UNDERWRITING_EVIDENCE_REQUIRED`, `POLICY_INPUT_REQUIRED`, `DSCR_BELOW_CUTOFF`, `CAPACITY_BELOW_MINIMUM`, `EXPOSURE_LIMIT`, `NOTE_INELIGIBLE`, `TRADE_BELOW_MINIMUM`, `PRICE_OUT_OF_BOUNDS`, `INSUFFICIENT_AVAILABLE_FUNDS`, `RESERVATION_EXPIRED`, `ORDER_CLOSED`, `RESTRICTION_ACTIVE`. Domain denials return the same stable code/facts across transports; exact transport wrappers remain subject to joint review. No generic retry may repeat a successful financial effect.

Client refresh/reconnect: fetch current operation by its correlation/idempotency identity after an uncertain response, then authorized aggregate revisions. A timeout is not a failed settlement. Display separate pending/confirmed/rejected/stale states; never invent a successful balance or replay a financial command with a new identity just because the connection dropped. `allowed_actions` guides UI but the server rechecks every action. Raw bank identifiers, source statements, internal score components, trigger evidence and PD are not public Resource fields; audience access remains gated by IN-09.

## 5. Proposed actions and logical records

All names below are draft contracts. `web` route names omit the `api.v1.` prefix; API counterparts add it under `/api/v1`. Reuse existing identity/auth routes at implementation time; this table does not create duplicate login endpoints. Reads use GET, commands POST unless explicitly noted. Explicit quantities/versions and idempotency are mandatory on relevant commands.

| ID / proposed route name | User input / server-resolved result | Atomic boundary / missing policy |
|---|---|---|
| AC-01 `business.applications.evaluate` | Requested principal, tenor, expected evidence revision -> Calculation and refusal or proposed Offer facts | Immutable calculation snapshot; revision-checked publication. IN-01/02/03 |
| AC-02 `business.offers.accept` | Offer ID, accepted amount, version -> accepted offer and exposure reservation | Lock Business exposure and offer; reserve full accepted commitment once. IN-03/09 |
| AC-03 `business.listings.publish` | Accepted offer/version, required disclosures -> primary campaign | Reuse existing exposure reservation; never add it again on publication. Listing fee/mandate gates IN-09 |
| AC-04 `investor.primary.reserve` / `.confirm` | Campaign, quantity/amount, version -> reservation/holding/receipt | Primary funding/oversubscription/cancellation rules IN-09; no borrowed secondary expiry rule |
| AC-05 `admin.disbursements.authorize` | Campaign/version, required reason/approval refs -> authorized disbursement operation | Maker/checker and accepted/funded/issued state boundary IN-03/09; provider call after durable intent, reconciled separately |
| AC-06 `investor.secondary.listings.create` | Owned lot allocations, whole units, unit ask, disclosure version -> Listing | Validate exact transferable allocations; reserve seller units once; no sale fee yet. IN-07 |
| AC-07 `investor.secondary.reservations.create` / `.confirm` | Listing, units, expected versions -> Reservation, then Trade | Reserve cash/units together; confirm rechecks note/account gates and commits all settlement effects together |
| AC-08 `investor.secondary.bids.create` / `.cancel` | Note, units, limit unit price, versions -> funded Bid or cancellation receipt | Fund-backed limit order, price-time matching, fee buffer and safe releases. IN-06 |
| AC-09 `investor.secondary.listings.cancel` | Listing/version, unreserved quantity -> cancelled remainder | Reserved units cannot be cancelled by seller; no deletion of settled trades |
| AC-10 `admin.market.halt` / `.release` / `.cancel` | Scope/version, reason, evidence refs -> attributed restriction/cancellation | Serialize with matching; separate Note/account scopes. No automatic relist; IN-08/09 |
| AC-11 `operations.show` / `holdings.show` / `secondary.listings.show` | Authorized ID -> current Resource facts and revision | Read-only; no permission derived from stale cache; investor disclosure IN-09 |
| AC-12 `auditor.assignments.show` / `evidence.submit` / `reports.cosign` | Assignment/evidence lineage/procedure refs -> authorized package, receipt, report state | Evidence ingestion is not financial approval; seal/offline/authority gates IN-10 |
| AC-13 `admin.repayments.reconcile` / `recoveries.record` | Reconciled event/reason/evidence -> servicing and investor-entitlement facts | No direct balance editing; provider idempotency, allocation, penalties/recovery IN-04/05/09 |

| Logical record / Resource | Minimum proposed facts and invariants |
|---|---|
| Calculation / Offer | Business; original request; track; evidence/policy/calculator versions; exact intermediate ratios; computed and accepted amounts; rate/schedule; restriction causes; revision and timestamps. Internal intermediates are audience-scoped, not an investor payload. |
| Exposure commitment | Unique economic obligation, borrower, amount, state, source offer/campaign/disbursement IDs and revisions. Reserved -> funded transfers classification once; repayment reduces outstanding principal only through reconciliation. |
| Holding / lot | Note/owner, immutable originating issue/settlement, acquired/disposed units, allocation revision, available/encumbered units and separately vested entitlements. Durable unit movements are authoritative. |
| Listing | Seller/Note, allocated lots, immutable ask/disclosure/fee-policy snapshot, expiry, admission state and reason, quantities available/reserved/settled/released. Initial quantity equals their sum. Expired admission may coexist with live reservations. |
| Bid | Buyer/Note, requested/remaining/filled units, limit unit price, available reserved cash, policy/fee snapshot, server admission sequence, expiry and cancellation reason. Only executable funded eligible residual quantity enters demand aggregation. |
| Reservation | Listing/buyer, exact allocated units, gross/buyer-fee cash commitment, immutable expiry, revisions and idempotency identity. Multiple rows may coexist for disjoint quantities. |
| Trade | Buyer/seller, listing/bid/reservation refs, units/ask/gross, both fees, seller net, ledger and unit batch IDs, entitlement/cutoff revision, policy version and serialization sequence. Immutable after commit. |
| Entitlement | Note/scheduled payout, cutoff marker, holder units, allocation/remainder rule, vested amounts and reconciliation lineage. Late payment cannot reassign a vested payout to a later buyer. |
| Restriction / review case | Scope, trigger/authority, evidence refs, original flag, confirmation/release timestamps, business-calendar version, notice/counter-evidence deadline and outcome. Releasing one case does not clear another restriction. |

No executable schema is frozen for any row whose referenced input remains missing. Primary and Auditor contracts are deliberately represented as gated interfaces here, not falsely completed by the underwriting/secondary answers.

## 6. Secondary arithmetic, states and concurrency

For exact transferred allocations, `G = whole units * whole-RWF seller ask`. Require units >= 1, G >= 1,000, `G >= 0.70 * allocated unpaid principal`, and `G <= allocated remaining transferable gross payouts`. With fractional derived bounds, compare exact fractions; a whole-RWF price must be at least the ceiling of the floor and no more than the floor of the ceiling. Never round a failing trade into the band. Paid/vested seller entitlements are excluded from transferred future cashflows. IN-07 controls allocation, not a presumed universal RWF 5,000 denomination.

Each side pays `half_up(G * 35 / 10,000)`. Buyer debit = G + buyer fee; seller credit = G - seller fee; platform fee = both fees. Buyer debit equals seller credit plus platform fee. RWF 10,000 gives 35/35 fees, debit 10,035 and seller credit 9,965. Two separate RWF 1,000 fills incur 4 + 4 per side; one RWF 2,000 fill incurs 7 per side. Per-fill rounding is confirmed; no extra minimum fee or fragmentation surcharge is introduced.

### Quantity-aware transition contract

| Event | Required outcome |
|---|---|
| Create listing | Reserve selected eligible seller units; admission Open. Units cannot also back another listing. |
| Reserve checkout | Reserve only requested available units and buyer gross plus fee. Start one immutable five-minute deadline. Other unreserved quantities remain available. |
| Successful checkout / automatic match | Transfer exact units, cash and both fees atomically; reduce the corresponding reserved quantities; preserve unfilled quantities and entitlement history. |
| Normal seven-day listing expiry | Close admission, return unreserved units to seller; existing reserved units keep their original checkout deadlines. No new reservation after expiry. |
| Checkout timeout | Release cash once; if listing still open/eligible, return units to its available allocation; otherwise return units to seller. Never resurrect expired admission. |
| Seller cancellation | Cancel only unreserved selected quantity; leave existing reservations and completed trades intact. |
| Buyer bid cancel/expiry | Cancel unmatched quantity and release its remaining cash once. A match already committed cannot be erased. |
| Note halt/eligibility loss | Block matching/settlement; terminate affected uncommitted reservations and release encumbrances, retain history; explicit relist after authorized clearance. Normal-expiry priority does not defeat a trading ban. |
| Account hold | Apply its approved scope, preserving holdings; exact effect on existing bids/reservations requires IN-08 rather than assuming a Note-wide ban. |

### Engineering choices proposed for Erastus's review

- Keep immediate funded-bid matching distinct from interactive five-minute checkout. The matcher executes at eligible seller ask without creating an extra five-minute waiting period. Match highest bid first, then earlier server admission sequence. For multiple asks, propose lowest eligible ask then earlier sequence; this tie/ask-selection choice is not attributed to Robert (IN-06).
- One possible safe bid funding bound is `quantity * limit unit price + quantity * ceil(limit unit price * 35 / 10,000)`, sufficient even for one-unit fills with per-fill rounding. This may temporarily reserve more than the final fee. Erastus must review the disclosed buffer and partial-release behavior before freeze (IN-06); actual fees remain 35 bps per fill. Reject or amend this mechanism explicitly, not by silently overdrawing a wallet.
- A candidate fill that cannot reach the execution minimum must not be posted. Whether to skip that ask, combine compatible allocations or leave the bid waiting needs an explicit deterministic matching rule (IN-06). No forced residual purchase or cross-Note bundle follows from the minimum.
- Proposed serialization: market gate, Note gate, affected Parties/wallets sorted by ID, then orders/holdings sorted by ID. Halt and payout-cutoff operations take conflicting locks on the same gates. Review lock upgrades/order and retries before implementation; this is not PostgreSQL race proof.
- Financial state, ledger entries, unit movements, result/idempotency record and outbox events commit in one database transaction. No live provider request inside secondary settlement. Conflicting body reuse produces `IDEMPOTENCY_CONFLICT`; identical replay returns recorded facts with no second debit, fee or transfer.
- Proposed events: `CalculationPublished`, `OfferReevaluationRequired`, `ExposureReserved`, `ListingAdmissionClosed`, `ReservationReleased`, `TradeSettled`, `RestrictionChanged`, `EvidenceReceiptCommitted`. Each carries immutable event ID, aggregate/revision, occurred/effective/recorded timestamps, correlation and scoped references. Consumers deduplicate and ignore stale revisions; durable events are not the public payload.
- At record cutoff, serialize an entitlement marker before settlements at/after that boundary; preserve due-payout ownership while future unvested entitlements transfer. Server transaction ordering and cutoff markers, not browser/provider timestamps, govern. Due-day/holiday and partial-payment allocation still require IN-05/09.

## 7. Recovery, holds and policy transitions

The selected recovery sequence is unchanged: days 1-7 freeze secondary, retry collections, daily SMS/email; days 8-21 direct outreach, penalty interest, enforced daily plan, weekly investor updates; days 22-30 formal default if not cured by day 30, legal recovery/guarantees/collateral/CRB; days 31-45 maximum legal liquidation or reserve buyout/payout for final investor capital resolution. This is not replaced by a progress checkpoint. IN-04/05 identify missing execution inputs and evidence, not authority to weaken that direction or a claim that payout can already be guaranteed.

New policy revisions preserve completed issue snapshots. Unaccepted calculations/offers become stale and recalculate; future accepted primary offers/unfunded commitments re-evaluate immediately before disbursement. Existing secondary listings retain original terms to their seven-day expiry; reserved checkouts retain their five minutes. An old worker may publish only if evidence/policy/aggregate versions still match; otherwise record a stale result without replacing newer facts. Missing acceptance/funding/issuance mapping is IN-03, not permission to add a new refund or re-consent policy.

Unconfirmed automated holds expire after 24 elapsed hours. Human confirmation does not reset the original five-business-day clock. At its limit, absent a confirmed violation, release that hold; manual release is Compliance Officer OR Legal Counsel. Other independently applicable restrictions remain. IN-08 must supply the business calendar, high-risk trigger definitions and formal-finding path. Ordinary uncalibrated trading-frequency/cancellation alerts remain review-only.

## 8. Remaining input register - not a repeated questionnaire

`ANSWER_INCORPORATED` means Robert's supplied answer has been entered and must not be asked again as an open policy choice. It does not mean an absent numeric parameter or operational arrangement has been supplied. `PARAMETER_GAP` identifies only those absent values. `OWNER_INPUT_REQUIRED` applies to other unresolved values/dispositions; `ENGINEERING_REVIEW` means Aminu/Erastus can resolve an implementation choice, subject to owner review if it changes participant behavior. Existing provider track records remain PREPARED_NOT_STARTED until actual evidence arrives. No acknowledgement, provider contact or signature is manufactured by this draft.

| ID | State / owner | Precisely what remains and what was already resolved |
|---|---|---|
| IN-01 | ANSWER_INCORPORATED / PARAMETER_GAP - Kimani/Robert | Section 8.1 records permitted 3/4/5/6-month tenors and the retained pricing contract. Only the four/five-month premium values or an explicit replacement schedule are absent. Do not interpolate them. |
| IN-02 | ANSWER_INCORPORATED / CALCULATION_MAPPING_PENDING - Aminu/Erastus; Kimani for absent financial definitions | Section 8.2 fills the 36-month first-time baseline, repeat-only 12-month track, gap audits, TTM, 1.35 monthly DSCR, 1.2-times median-revenue cap and collection safeguards. Remaining work is the exact standard NOCF/projection calculation, monthly evidence mapping and retention/taxonomy validation, not another choice of history thresholds. |
| IN-03 | OWNER_INPUT_REQUIRED + ENGINEERING_REVIEW - Robert/Kimani and A/E | Exact accepted/partly funded/funded/issued/disbursed state boundary; failed re-verification materiality and disposition of committed investor cash. Latest reevaluation direction remains selected; no assistant refund/re-consent default. Review treatment of initial requests outside permitted bounds without bypassing original-request rejection. |
| IN-04 | ANSWER_INCORPORATED / EXECUTION_PARAMETERS_AND_EVIDENCE_PENDING - Kimani/Robert with Aminu/Erastus mapping | Section 8.3 fills the complete 1-45-day recovery stages, penalty/daily-plan stage, investor updates and final capital-resolution mechanisms. Only unsupplied charge/repayment calculations and actual reserve/guarantee/collateral arrangements remain; these are not requests to reconfirm the recovery policy. |
| IN-05 | OWNER_INPUT_REQUIRED + ENGINEERING_REVIEW - Kimani/A/E | Due-date, holidays, exact day-30 deadline and recovery/reporting cure authority under the new calendar; partial/late repayment allocation. Seven-day reporting window and day-1 trading ban are settled. |
| IN-06 | AMINU_AGREED / ERASTUS_REVIEW_PENDING - owner review for behavioral changes | Aminu agreed to the written ask-selection/priority, immediate-match/checkout distinction, funding-bound, serialization, atomicity, event and cutoff proposals (AR-04/05). Erastus must review them. The written alternatives for a sub-minimum candidate fill and unspecified partial-release details still require an explicit disposition; agreement does not choose an unstated alternative. Price-time bid priority, seller-ask execution and bid lifetime are settled. |
| IN-07 | OWNER_INPUT_REQUIRED + ENGINEERING_REVIEW - Robert/Kimani and A/E | Universal primary unit denomination versus anchor, residual issue-target handling, per-lot principal/payout allocation and rounding. Whole-unit secondary trades, RWF 1,000 minimum and 70%-to-payout band are settled. |
| IN-08 | OWNER_INPUT_REQUIRED - Kimani/Robert; A/E calendar mapping | High-risk trigger definitions; business-calendar version/timezone; existing bid/checkout scope during account hold; documented formal-finding/continued-restriction process. 24h, original five-business-day clock and OR release authority are settled. |
| IN-09 | OWNER_INPUT_REQUIRED - relevant Product/Compliance/Finance owners | Remaining D-05 role combinations, D-64 mandates, D-09 primary listing fee, D-15 investor limits, D-18 disclosure audiences, D-22 primary funding races, D-23 allocation and applicable override limits. Proposed options are not signed answers. |
| IN-10 | OWNER_INPUT_REQUIRED + ENGINEERING_REVIEW - Kimani and A/E | D-29-37 Auditor procedures, standing checks, dispatch/conflicts, lateness, seals, offline/device contract; accept thin-native boundary/estimate/allocation. Preserve D-04 Option B without starting companion implementation here. |
| IN-11 | OWNER_EVIDENCE_REQUIRED - named track leads | Reconcile the 13 provider tracks and actual kickoff/account/contract evidence. CRB is expressly named in the answers and Kimani owns communication; map that debt-evidence subtrack explicitly rather than assuming the KYC provider covers it. Provider certification remains at its applicable later gate. |
| IN-12 | AMINU_SECTION_REVIEW_RECORDED / JOINT_REVIEW_PENDING - Erastus and applicable non-author approver | Six direct user confirmations are recorded as AR-01-AR-06, with scoped content hashes. Erastus's review, missing expected results, full worksheet/vector dispositions and final shared-contract freeze remain open. No whole-pack independent approval is inferred from Aminu's selected-section review. Host/device/brand and full exact-SHA exit evidence remain separate. |

### 8.1 IN-01 - tenor answer incorporated

**Source:** Answers.docx.pdf Q01; unchanged pricing from Appendix A.3.1. The PDF explicitly permits every whole month **3, 4, 5 and 6**. Nine- and twelve-month new offers are excluded. This answers the tenor choice completely.

The retained pricing rule is `round_half_up(clamp(10 + (5 - PublishedRating) * 1.6 + TermPremium, 10, 15), 1 decimal place)`, a flat return over the full term, not APR. Existing premiums are **0.0 percentage points for three months** and **0.5 for six months**. These are carried forward from the existing approved pricing table, not attributed to a new Robert answer.

**Narrow parameter gap:** neither the PDF nor either complete 20 September reply gives a four- or five-month premium or a replacement rating-to-return table. The 10-15% bound does not determine those values. `four_month_premium` and `five_month_premium` therefore remain null in the companion; no nearest-tenor or interpolation default is introduced.

### 8.2 IN-02 - history and repeat-track answers incorporated

**Sources:** Answers.docx.pdf Q02-Q06; complete 20 September 09:52 reply, point 2; 11:21 reply, point 1 for the final standard target. The later repeat-only restriction resolves the PDF's broader introductory description.

| Answered field | Recorded answer |
|---|---|
| First-time qualification history | **36 months / three years of audited financial statements**, replacing five years. No short-history annualization. |
| Repeat-track eligibility and observation window | **12 consecutive months of audited bank and primary MoMo statements**, an original passed 36-month audit baseline on file, and at least **one fully settled Rozine note with zero late payments**. This exception is repeat-only. |
| Unmonitored gap / back-to-back borrowing | Fully audit every unmonitored month between repayment and the next application before an offer. For back-to-back borrowing, continuous monthly audit logs supply the new TTM base. |
| Revenue-cap observation window | **Trailing 12 months**, taken from the first-time 36-month history or the repeat borrower's ongoing monthly audits. Do not substitute the three-year revenue average for TTM. |
| Repeat monthly affordability | **DSCR >= 1.35 in every month of the proposed tenor**; the latest final standard-offer target of 1.50 is not silently lowered. |
| Repeat revenue-spike safeguard | **Projected monthly cash flow capped at 1.2 times historical median revenue**. Retain this wording: it is a cap, not a complete cash-flow forecast algorithm or an instruction to replace cash flow with revenue. |
| Repeat collection safeguard | Enforced automated collection through bank standing orders or locked MoMo collection accounts. The requirement is recorded; provider availability is not asserted. |
| Draw and existing-debt deductions | Greater-of-mean/recurring OwnerDraw and greater-of-historical-mean/peak-contractual debt service, with the Q02 exceptions and evidence rules already recorded in section 3. |

**Seasonality disposition:** Robert challenged automatically removing genuine high seasonal months; his later Q04 answer selects three years with the Q03 exception. Preserve the observed peaks and downside with their evidence. The assistant's proposed upper-tail winsorization is not an approved default. Those answers do not select an exact standard NOCF averaging/normalization formula or a forecast generator.

**Engineering follow-through:** Aminu/Erastus map annual statements to verified monthly observations, the historical-median series and forecast net-cash-flow inputs, retained evidence/replay and the transaction-classification corpus. The old 24-month ceiling cannot discard the required 36-month baseline. Any financial formula still absent needs Kimani's decision, rather than being presented as Robert's answer. The resolved 36/12-month history, TTM, gap-audit and 1.35/1.2 safeguards are not reopened. `standard_nocf_method` remains null until that formula is defined.

### 8.3 IN-04 - full recovery answer incorporated

**Source:** complete 20 September 11:21 reply, point 5, retained as directed by the user. This supersedes the older 31-89/90-day sequence; it is not a new proposal for Robert to reconfirm.

| Stage | Recorded action |
|---|---|
| 1+ DPD, Arrears, Default or Disputed | Immediately freeze secondary listing and trading. |
| Days 1-7 | Grace/technical-clearing stage with automated payment retries and **daily SMS/email** to the borrower; secondary remains frozen. |
| Days 8-21 | Recovery-team outreach, **penalty-interest accrual**, an **enforced structured daily repayment plan**, and **weekly investor portal updates**. This fixes the stage in which penalties/daily recovery operate, not their unspecified amounts. |
| Days 22-30 | Formal-default stage: if uncured by day 30, declare Default, immediately initiate legal recovery, call guarantees/collateral and file CRB default reports. Exact deadline ordering is IN-05. |
| Days 31-45 maximum | **Legal liquidation proceedings or reserve-fund buyout/payout mechanisms** execute to deliver **final investor capital resolution**. Keep the full answer; do not recast day 45 as merely a reporting checkpoint. |

**Narrow execution gaps:** the source gives no penalty percentage, chargeable balance, day-count/compounding/rounding/allocation convention or daily-plan amount derivation. Penalties belong to the days 8-21 stage; an exact first accrual timestamp and treatment after that stage still need the executable schedule. `penalty_rate` stays null, not zero, and no equal-daily-payment formula is assumed.

**Operational evidence, not a repeated policy question:** the answer selects liquidation or reserve buyout/payout but supplies no actual reserve-funding arrangement, provider/balance, authorized payout process, guarantee/collateral instruments or shortfall treatment. Kimani/Robert own those financial/operational arrangements; Aminu/Erastus map the evidenced arrangement into the contract. `reserve_funding_arrangement` remains null. Recording the day-45 requirement does not certify that funding, enforceability or provider capability exists; required external clearance remains separate.

The companion's `answer_reconciliation` records these three source-backed dispositions. Unspecified values remain null and both activation flags remain false. No broad follow-up questionnaire is generated.

### Provider intake prepared, not sent

Use the existing [provider register](provider-dependency-register.md), not a replacement procurement list. DEP-01/02/03/04/05/06/07/10/12 route to Kimani with Aminu's technical input; DEP-08/11 to Robert with engineering; DEP-09 storage/key custody to Aminu with Erastus cross-review; DEP-13 native distribution to Erastus with Aminu's server input. These are the register's proposed allocations, not new acknowledgements.

For each, record lead acknowledgement, existing-arrangement reference, allowed operations/fields, request/result/error/event shapes, authentication/callback validation, timeout/retry/idempotency, reconciliation/reversal semantics, environment isolation, evidence reference and next review date. Non-API legal/regulatory tracks use document scope/version/outcome instead of invented endpoints. Specify synthetic success/failure/stale/reordered/revoked outcomes; a fake clearance artifact is not actual approval. Secrets stay out of the repository. No outreach occurs in this task.

## 9. Acceptance and handoff

The companion pack contains an explicit disposition for every GV-001-GV-048, retaining original statuses and keeping baselined/implementation flags false. Revised history, DSCR, tenor, bounds and risk expectations must not inherit the old ready status. Small-principal schedule examples remain arithmetic examples only, never eligible RWF 3M loan offers. Numerical worked examples and planned state/race scenarios are separate collections, so a text assertion cannot be reported as a successful runtime race.

**Review artifact:** 37 numerical examples, 16 planned scenarios, 48 vector dispositions, three source-backed input reconciliations and six Aminu section-review records. Fixture SHA-256: `afa73ed3277e86b356437fe456c8278e1c4acc1451264fb7e6ea3d7610db4b48`. This identifies the synthetic pack, not provider evidence. Erastus's and the independent expected-result review fields remain null until an actual review is supplied.

### 9.1 Aminu's recorded section review - 2026-09-21

**Evidence:** six direct user response annotations on the earlier linked review list (source message `msg_08a35f686d830a89016ab0c52c412487d0b5966cd547f5dd16`). The quoted line numbers are navigation history, not stable section identifiers. The selections are mapped to the corresponding text in engineering-2026-09-20.2 and preserved unchanged in this revision; the companion stores the exact section hashes and responses.

| Review ID | User annotation | Selected scope | Verbatim response | Recorded result |
|---|---:|---|---|---|
| AR-01 | 1 | Section 3: exact operation order and worked downstream examples | approved | AMINU_APPROVED |
| AR-02 | 2 | Section 4: wire formats, authorization, failures and reconnect behavior | correct | AMINU_CONFIRMED |
| AR-03 | 3 | Section 5: actions and logical record structures | correct | AMINU_CONFIRMED |
| AR-04 | 4 | Section 6: secondary arithmetic and quantity-aware states, before engineering choices | correct | AMINU_CONFIRMED |
| AR-05 | 5 | Section 6: specific engineering choices for joint review | agreed | AMINU_AGREED |
| AR-06 | 6 | Section 7: recovery, account holds and offer transitions | correct | AMINU_CONFIRMED |

This is Aminu's own review, not an agent-generated signature and not Erastus's concurrence. In particular, AR-01 approves the written downstream calculation order and displayed examples with their supplied-input assumptions, not an absent upstream formula or independent baselining of all 48 vectors. AR-05 records agreement with the concrete proposals while retaining the explicitly unresolved sub-minimum-fill alternatives and partial-release details. AR-06 retains Robert's full recovery and transition direction without fabricating financial parameters or operational evidence.

No missing input is filled by this approval. No whole-contract freeze, production activation, Phase 0 exit or Phase 1 development authorization is recorded. Erastus and independent expected-result approval remain null in the companion.

Section hash convention: SHA-256 of the exact UTF-8 substring from `start_heading` (inclusive) to `end_heading` (exclusive), including whitespace. A later edit to approved text requires a new review disposition rather than carrying this approval over silently.

Erastus's remaining review checklist:

- [ ] Review AM-01-AM-12 against sources; reject any accidental old constant or unsupported approval.
- [ ] Review the six Aminu-confirmed sections and record concurrence or amendments; resolve the remaining IN-06 alternatives and verify UI error, partial-fill, deadline and uncertain-outcome flows.
- [ ] Trace every remaining IN item to actual evidence/owner decision or an enforceable later gate; no silent default.
- [ ] Independently review the numerical examples and revised GV dispositions; approve the exact fixture/draft version and hash.
- [ ] Agree one editor per shared contract, native allocation and integration order; record both developers' actual decisions.

After that, prepare the candidate commit and full exact-SHA CI/non-author review package under the approved branch flow. This draft does not claim the final contract freeze, architecture/API parity implementation, PostgreSQL race proof, real-device evidence, provider kickoff, Phase 0 exit or Phase 1 start. Old source files and Word snapshots are unchanged.

**Current handoff:** Aminu has approved/confirmed the six selected engineering sections (AR-01-AR-06). IN-01/02/04 answers are incorporated, with only the unsupplied parameters, engineering mapping and operational evidence retained as gaps. Erastus's review, independent expected-result approval, joint freeze and the other input-register items remain open. No application development, provider calls, messages, commit, push, merge or deployment.

### Local verification scope

The engineering-contract, policy-review and delivery-readiness Pest suites pass together without TIA: **129 tests, 1,425 assertions** after the 2026-09-21 section-review record. They check independent integer/rational arithmetic (including a separately implemented bounded search for sizing), cash/schedule conservation, source hashes, current vector dispositions, links, the three source-backed input records, all six reviewed section hashes and negative controls against fabricated approval/defaults. Those controls reject erasing Aminu's recorded review, expanding it into full freeze or independent approval, and inventing tenor premiums, seasonality formulas or reserve arrangements. The planned state/race scenarios are not runtime tests. Pint and targeted PHPStan pass. The existing web suite passes 329 tests but emits canvas-environment diagnostics; the full `vp check` exits successfully with 32 warnings in untouched frontend/test/tooling files. Its configured formatter excludes these documentation paths, so it does not certify their layout. JSON parsing and document/link checks pass separately. No full coverage collection, clean-install candidate CI, browser/native/host verification or independent sign-off was performed for this draft.
