# Phase 0 - MVP engineering contract baseline

**Version:** engineering-2026-09-23.4

**Answer reconciliation:** 2026-09-21; IN-01, IN-02 and IN-04 populated from supplied answers  
**Status:** MVP_DEFAULTS_BASELINED - ERASTUS_SPEC_REVIEW_WAIVED_BY_USER - NOT_IMPLEMENTED

**Decision authority:** Aminu's explicit delegation to select MVP defaults

**Phase 1:** NOT_STARTED; this update establishes specification defaults, not application implementation

**Engineering review:** ERASTUS_REVIEW_RECORDED - AMINU_AMENDMENTS_ACCEPTED remains historical evidence for the original scope. **No further Erastus review is required for this MVP specification baseline.** The current review requirement is waived, not marked performed or signed by him.

**Current instruction:** "We need to go with defaults for now and no need for Erastus review, we are working towards an MVP for now". Section 11 selects the five remaining configuration groups under that delegation. These are new MVP defaults, not newly discovered Robert/Kimani answers. Actual operational evidence cannot be supplied by selecting a default.

**Current reading order:** this status and section 11, then sections 10, 8.4/8.5 and 3–7 wherever unchanged. Section 11 is controlling where it resolves a gap or explicitly amends a candidate mechanic. The companion's `effective_authority_record` is `mvp_baseline`. Sections 8–10 and the older JSON approval/candidate objects preserve historical scopes; their pending configuration/review labels are not current blockers. Historical status was DRAFT_FOR_JOINT_REVIEW - NOT_FROZEN - NOT_IMPLEMENTED. Reviewed section hashes stay intact, without pretending Erastus reviewed this revision.

**Accepted policy:** CONFIRMED_AS_REPORTED_BY_AMINU. Aminu reports that the owners already gave the go-ahead for the minor defaults. Four/five-month pricing, NOCF/projection conventions, hold calendar, 2%-annual penalty design, allocation/recovery targets and the fully backed reserve design do **not** need a repeat minor-policy approval round. This records his report, not invented direct signatures or external clearance. Actual capital/custody/provider evidence remains separate.

**Current engineering baseline:** all five CFG groups have explicit versioned MVP defaults in section 11. No extra internal specification-approval round is queued. Later policy changes require a new version and must not rewrite purchased terms. Actual reserve capital, professional engagements, providers, regulatory/operational clearance, deployment and issue #90 evidence remain separate and unverified here. No application or financial behavior is activated by this document.

## 1. Scope, authority and evidence

This records Aminu's specification work. It turns the [current stakeholder consolidation](stakeholder-policy-consolidation-2026-09-20.md) into an amendment map, shared-contract candidates, worked examples and an input register. Section 9.1 preserves Aminu's original six selected-section confirmations. Section 9.2 records Erastus's actual review and Aminu's subsequent acceptance of the AR-02/AR-05 amendments, including the lock-order and seller-bundling clarifications. This transcription does not sign a revised hash on Erastus's behalf, alter runtime policy, create application modules or declare Phase 0 complete. Unchanged section hashes and the original review evidence are preserved separately from the amended sections.

Read this with the [source-authority record](source-authority-record.md), [BRS](../Rozine-BRS.md), [plan and Appendix A](../Rozine_Phased_Implementation_Plan.md), [architecture decision](adr-0001-modular-monolith-boundaries.md) and [synthetic companion pack](engineering-contract-fixtures-2026-09-20.json). The older underwriting/secondary proposals and 48-vector register remain history, not current constants. No new Robert questionnaire is created.

Evidence checked directly: Answers.docx.pdf Q01-Q12, Robert's complete 09:52 and 11:21 replies, the fee confirmation recorded in the consolidation, and existing source/decision registers. PDF Q02 confirms one-off resolutions, same-period returned draws and unavailable-data treatment; Q03 confirms peak monthly debt service and repeat-track conditions; Q10 confirms half-up whole-RWF rounding per fill and no minimum fee. Later replies supersede the PDF's secondary minimum, standard DSCR behavior, recovery calendar, policy transitions and account-hold release wording. The fee is settled: 35 basis points each side, not 70% and not the old maker/taker rates.

## 2. Amendment map for joint review

This is a proposed versioned amendment overlay, not a silent edit of BRS v1.0. Each row inherits the source date/effective-build scope from the consolidation. Runtime effective version/time remains unset. Unchanged rules remain applicable unless explicitly identified below; a quoted superseded source must never become an implementation default.

| ID | Affected source | Selected direction / retained boundary | Related review input |
|---|---|---|---|
| AM-01 | BR-20; UW-18/19; D-11; Appendix A pricing table | Tenors are exactly 3/4/5/6 months. Retain the 10-15% flat-return range, existing rating transform and 3/6-month pricing. No interpolation of 4/5-month premiums without explicit approval: section 8.4 now records Aminu's acceptance of exact 1/6 and 1/3 percentage-point premiums, subject to the retained review gates. | IN-01 |
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

These are logical contracts, not database migrations or existing endpoints. AR-02 incorporates Erastus's A2-1/A2-2/A2-3 amendments accepted by Aminu; field and route names remain provisional until implementation naming checks. Keep one application/domain result behind both web and `/api/v1` transports; Inertia consumes shared Resource facts through its controller, not by calling the public API over HTTP.

| Primitive / envelope | Proposed exact representation and behavior |
|---|---|
| Money | `{currency: "RWF", amount: "10035"}`; integer decimal string, no float or formatted commas. Signed values only in explicitly signed fields. Ledger debit/credit magnitudes stay nonnegative. |
| Ratio | Exact numerator/positive denominator decimal strings, or `null` plus an unavailable reason. Display precision is separate and cannot affect permission, Health or DSCR. |
| Units / rates | Whole-unit nonnegative integer strings; command quantity must be positive. Fee rates are integer basis points: buyer 35, seller 35. Total return retains one-decimal-percent precision. |
| Identity / version | Opaque IDs; integer aggregate revision; policy, evidence, disclosure and contract versions. IDs are not authority; every resource/action checks the current authenticated Party and active role. |
| Time | UTC RFC3339 server timestamps plus named business calendar/timezone where needed. Timer validity is `[created_at, expires_at)`; at the expiry instant no new action may use the expired permission. Countdown rendering uses an offset from `server_time`, never the browser clock alone. The server decides expiry. |
| Result | `operation_id`, `code`, authorized `data`, `revision`, `policy_version`, `server_time`, `allowed_actions`, `field_errors`. Stable codes map to localized text; UI text is not parsed to decide workflow. |
| Command | Idempotency key unique per authenticated Party and command name + hash of canonical JSON of permitted user fields only + expected aggregate revision + permitted user choices. Server resolves actor, price/rate/eligibility/amounts and records a correlated immutable outcome. |

Retain the idempotency key/result lookup for at least the longest timer the command can create plus 24 hours: seven-day bids/listings require at least eight days. Authorized `operations.show` reconnect lookup depends on this record remaining available. This minimum is not permission to delete ledger/history or repeat a previously committed effect; no financial-record deletion policy is introduced. Identical body replay returns the recorded outcome; a changed permitted body under the same Party/command/key produces `IDEMPOTENCY_CONFLICT`.

Failure mapping: `VALIDATION_FAILED` (422); scoped 404 when the actor cannot see the resource at all; `ACTION_FORBIDDEN` (403) when they can see it but cannot perform the action. Fix that visibility/action distinction deterministically per resource type, with no existence disclosure through lookup. Other codes: `VERSION_CONFLICT`/`IDEMPOTENCY_CONFLICT` (409), `UNDERWRITING_EVIDENCE_REQUIRED`, `POLICY_INPUT_REQUIRED`, `DSCR_BELOW_CUTOFF`, `CAPACITY_BELOW_MINIMUM`, `EXPOSURE_LIMIT`, `NOTE_INELIGIBLE`, `TRADE_BELOW_MINIMUM`, `PRICE_OUT_OF_BOUNDS`, `INSUFFICIENT_AVAILABLE_FUNDS`, `RESERVATION_EXPIRED`, `ORDER_CLOSED`, `RESTRICTION_ACTIVE`. Domain denials return the same stable code/facts across transports; exact transport wrappers remain subject to joint review. No generic retry may repeat a successful financial effect.

Client refresh/reconnect: no optimistic UI for reserve, confirm, bid, cancel or settlement. Show pending until the server records an outcome or authorized operation lookup resolves it. Fetch the current operation by its original correlation/idempotency identity after an uncertain response, then authorized aggregate revisions. A timeout is not a failed settlement. Display separate pending/confirmed/rejected/stale states; never invent a successful balance or replay a financial command with a new identity just because the connection dropped. Partial fills show filled units, remaining units and released amounts from server facts. On `RESERVATION_EXPIRED`, `ORDER_CLOSED`, `NOTE_INELIGIBLE` or `RESTRICTION_ACTIVE`, refresh and re-present the serialized server outcome without auto-resubmission. On `VERSION_CONFLICT`, refresh and ask the user to confirm again; do not retry permission failures. `allowed_actions` guides UI but the server rechecks every action. Raw bank identifiers, source statements, internal score components, trigger evidence and PD are not public Resource fields; audience access remains gated by IN-09.

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

The historical heading is retained for stable review boundaries. The following incorporates Erastus's review and Aminu's accepted amendments (section 9.2); it is a specification, not runtime proof.

- Keep immediate funded-bid matching distinct from interactive five-minute checkout. The matcher executes at eligible seller ask without creating an extra five-minute waiting period. Match highest bid first, then earlier server admission sequence. For multiple asks, use lowest eligible ask then earlier sequence; this engineering choice is accepted, not attributed to Robert (IN-06).
- A5-2 bid reserve: for quantity `q` and limit unit price `p`, reserve `q * (p + ceil(p * 35 / 10,000))`. For each fill of `k` units at eligible ask `a <= p`, the actual buyer fee is `half_up(k * a * 35 / 10,000)`. In the same transaction as the fill, release `k * (p + ceil(p * 35 / 10,000)) - (k * a + actual buyer fee)`. After every nonterminal fill, `reserved_remaining = remaining_units * (p + ceil(p * 35 / 10,000))`. On filled/cancelled/expired/halted/ineligible terminal states, release the whole remaining reserve exactly once; after terminal release, reserved cash is zero even if unmatched units remain in order history. UI calls the reserve "maximum you can pay, including fees" and shows actual cost on receipts. Interactive fixed-ask checkout reserves its exact gross plus fee, with no bid buffer. Neither actual fee rates nor per-fill rounding change.
- A5-3 minimum-fill choice: match the bid's remaining units against one listing's available units. If that candidate gross is below RWF 1,000, skip that listing for this bid and continue scanning in ask-priority order; if none qualifies, the bid waits. No automatic combination of separate listings, even for the same Note. One Trade has one buyer, one seller and one listing. This does not prohibit a seller from bundling their own available, unencumbered units of the same Note into one listing, as Robert allows. No cross-Note/cross-seller bundle, forced residual purchase or reuse of encumbered units. A residual left by a partial fill below the minimum may wait or be cancelled; it cannot execute below the floor or be published as a new standalone sub-minimum listing. IN-07 still governs exact lot allocation and price-band eligibility.
- A5-1 serialization: acquire the market gate shared for normal operations; only a market-wide halt takes it exclusively. Matching, checkout confirmation, Note halt and payout cutoff take the affected Note gate exclusively. Ordinary single-Note commands take one Note gate; explicit cross-Note commands acquire all required Note gates in ascending ID order before any wallet lock. Under the Note gates, discover candidates, lock affected Parties/wallets in ID order, then orders/holdings in ID order, and revalidate. Never acquire additional Note locks while holding wallet locks. Roll back deadlock/serialization failures and retry a bounded number of times with the same idempotency key. This resolves the review's one-Note/cross-Note wording; it is not PostgreSQL race proof or a chosen numeric retry budget.
- Financial state, ledger entries, unit movements, result/idempotency record and outbox events commit in one database transaction. No live provider request inside secondary settlement. Conflicting body reuse produces `IDEMPOTENCY_CONFLICT`; identical replay returns recorded facts with no second debit, fee or transfer.
- Proposed events: `CalculationPublished`, `OfferReevaluationRequired`, `ExposureReserved`, `ListingAdmissionClosed`, `ReservationReleased`, `TradeSettled`, `RestrictionChanged`, `EvidenceReceiptCommitted`. Each carries immutable event ID, aggregate/revision, occurred/effective/recorded timestamps, correlation and scoped references. Consumers deduplicate and ignore stale revisions; durable events are not the public payload.
- At record cutoff, serialize an entitlement marker before settlements at/after that boundary; preserve due-payout ownership while future unvested entitlements transfer. Server transaction ordering and cutoff markers, not browser/provider timestamps, govern. Due-day/holiday and partial-payment allocation still require IN-05/09.

## 7. Recovery, holds and policy transitions

The selected recovery sequence is unchanged: days 1-7 freeze secondary, retry collections, daily SMS/email; days 8-21 direct outreach, penalty interest, enforced daily plan, weekly investor updates; days 22-30 formal default if not cured by day 30, legal recovery/guarantees/collateral/CRB; days 31-45 maximum legal liquidation or reserve buyout/payout for final investor capital resolution. This is not replaced by a progress checkpoint. IN-04/05 identify missing execution inputs and evidence, not authority to weaken that direction or a claim that payout can already be guaranteed.

New policy revisions preserve completed issue snapshots. Unaccepted calculations/offers become stale and recalculate; future accepted primary offers/unfunded commitments re-evaluate immediately before disbursement. Existing secondary listings retain original terms to their seven-day expiry; reserved checkouts retain their five minutes. An old worker may publish only if evidence/policy/aggregate versions still match; otherwise record a stale result without replacing newer facts. Missing acceptance/funding/issuance mapping is IN-03, not permission to add a new refund or re-consent policy.

Unconfirmed automated holds expire after 24 elapsed hours. Human confirmation does not reset the original five-business-day clock. At its limit, absent a confirmed violation, release that hold; manual release is Compliance Officer OR Legal Counsel. Other independently applicable restrictions remain. IN-08 must supply the business calendar, high-risk trigger definitions and formal-finding path. Ordinary uncalibrated trading-frequency/cancellation alerts remain review-only.

## 8. Remaining input register - not a repeated questionnaire

**Historical register at engineering-2026-09-23.3:** Sections 9.3–9.6 preserve Aminu's successive confirmations and section 10 preserves the subsequent candidate. **Section 11 now resolves CFG-01–CFG-05 and waives Erastus's current specification review.** The rows below explain the previous gaps and their provenance, not requests for another approval or assertions that those design values remain missing.

`ANSWER_INCORPORATED` means Robert's supplied answer has been entered and must not be asked again as an open policy choice. It does not mean an absent numeric parameter or operational arrangement has been supplied. `PARAMETER_GAP` identifies only those absent values. `OWNER_INPUT_REQUIRED` applies to other unresolved values/dispositions; `ENGINEERING_REVIEW` means Aminu/Erastus can resolve an implementation choice, subject to owner review if it changes participant behavior. Existing provider track records remain PREPARED_NOT_STARTED until actual evidence arrives. No acknowledgement, provider contact or signature is manufactured by this draft.

| ID | State / owner | Precisely what remains and what was already resolved |
|---|---|---|
| IN-01 | POLICY_ACCEPTED / ENGINEERING_REVIEW - A/E | Exact four/five-month premiums and final-only rounding are supplied by section 8.4. Section 9.6 removes repeat owner approval of those choices. |
| IN-02 | METHOD_ACCEPTED / MAPPING_DRAFTED - A/E | MC-01 supplies monthly evidence classification/lineage and mean-to-CFADS examples without trimming peaks. Retention and procedure configuration remain CFG-05; full scorecard/vector validation remains IN-12. |
| IN-03 | STATE_BOUNDARIES_DRAFTED / CFG-03_OPEN - A/E with existing Product/Finance policy owners | MC-02 separates acceptance, committed cash, full funding, disbursement and issue; carries exposure once; blocks failed rechecks without rewriting funded/issued terms. Primary checkout/cancellation and changed-funded-offer cash disposition remain genuinely unspecified, not resolved by an invented refund/re-consent rule. |
| IN-04 | POLICY_ACCEPTED / OPERATIONAL_EVIDENCE_SEPARATE - Kimani/Robert; A/E mapping | Section 8.5's penalty, recovery and 100%-backed reserve design remains selected; section 9.6 records owner go-ahead as reported by Aminu. Actual capital, custody/payout instruments, guarantees/collateral and subsequent-recovery arrangements remain unverified. No repeat minor-policy approval. |
| IN-05 | SERVICING_MAPPING_DRAFTED / CFG-04_OPEN - A/E | MC-03 specifies calendar DPD boundaries, closing-day penalty basis, cumulative posting, whole-RWF daily targets and compensating corrections. Origination due-date selection, partial-receipt holder distribution and cure/restriction authority still require explicit configuration. |
| IN-06 | ENGINEERING_AMENDMENTS_ACCEPTED_AND_INCORPORATED - Aminu/Erastus | Erastus's reviewed ask priority, bid reserve/release, immediate-match/checkout distinction, atomicity, events and cutoff are recorded. Aminu accepted AR-02 and AR-05, resolving the lock wording and preserving seller same-Note bundling while prohibiting automatic inter-listing combination. The sub-minimum choice is skip, then wait if no candidate qualifies. No repeat decision request; revised-pack checks and eventual exact-SHA review remain distinct from this specification agreement. |
| IN-07 | COMPONENT_ALLOCATION_DRAFTED / CFG-02_OPEN - A/E with Product/Finance policy owners | MC-04 conserves each immutable component across fixed unit ordinals and transfers its existing rights without re-rounding. The primary unit grid and target remainder policy are still absent; RWF 5,000 is not silently made a universal denomination. |
| IN-08 | DEADLINE_MAPPING_DRAFTED / CFG-04_OPEN - A/E | MC-05 fixes deadline precedence, separate cases and no automatic relisting. The calendar convention is accepted; actual holiday data is operational evidence. Trigger/action scope and formal-finding authority remain explicit required configuration, not a blanket discretionary freeze. |
| IN-09 | AUTHORITY_CONTRACT_DRAFTED / CFG-01_AND_CFG-03_OPEN - A/E with relevant policy owners | MC-06 defines mandatory policy/version/mandate checks. Actual listing-fee values, investor limits, role combinations, disclosure/override policy and primary checkout/cancellation rules are not supplied by the UW/SEC answers. Missing values fail closed, not zero/unlimited. |
| IN-10 | INGESTION_CONTRACT_DRAFTED / CFG-05_OPEN - Aminu/Erastus and Audit Operations | MC-07 specifies the evidence envelope, duplicate/reordered/revoked outcomes and receipt-versus-approval distinction. Actual procedure/standing/dispatch/seal/offline/device/retention policies and native estimate acceptance remain outstanding. Issue #90 measures physical behavior and is not changed here. |
| IN-11 | PROVIDER_PORT_CONTRACT_DRAFTED / EXTERNAL_EVIDENCE_SEPARATE - named track leads | MC-08 specifies correlated requests, verified results, unknown outcomes, callbacks, reversals and fake cases. CRB is an explicit debt-evidence subtrack, not a KYC substitute. Existing 13-track kickoff/account/contract evidence remains separate and unverified. |
| IN-12 | NEW_SPEC_CHECKS_ADDED / EXACT_REVISION_REVIEW_AND_FREEZE_PENDING - A/E | Original 37-example/48-vector review and AR-02/AR-05 agreements remain recorded. The .3 completion candidate and new examples need their own independent review. CFG-01–CFG-05, remaining worksheet expectations and the full exit-evidence checklist prevent a claim of whole-contract freeze. |

### 8.1 IN-01 - tenor answer incorporated

**Source:** Answers.docx.pdf Q01; unchanged pricing from Appendix A.3.1. The PDF explicitly permits every whole month **3, 4, 5 and 6**. Nine- and twelve-month new offers are excluded. This answers the tenor choice completely.

The retained pricing rule is `round_half_up(clamp(10 + (5 - PublishedRating) * 1.6 + TermPremium, 10, 15), 1 decimal place)`, a flat return over the full term, not APR. Existing premiums are **0.0 percentage points for three months** and **0.5 for six months**. These are carried forward from the existing approved pricing table, not attributed to a new Robert answer.

**Source distinction:** neither the PDF nor either complete 20 September reply gives a four- or five-month premium or a replacement rating-to-return table. The 10-15% bound does not determine those values. Section 8.4 now supplies `four_month_premium` and `five_month_premium` through Aminu's explicit acceptance of the assistant's proposal, not through a newly discovered Robert answer.

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

**Engineering follow-through:** Aminu/Erastus map annual statements to verified monthly observations, the historical-median series and forecast net-cash-flow inputs, retained evidence/replay and the transaction-classification corpus. The old 24-month ceiling cannot discard the required 36-month baseline. Section 8.4 now selects `standard_nocf_method` and the projection method through Aminu's acceptance; Kimani's credit-risk approval and worked-case validation remain separate. The resolved 36/12-month history, TTM, gap-audit and 1.35/1.2 safeguards are not reopened.

### 8.3 IN-04 - full recovery answer incorporated

**Source:** complete 20 September 11:21 reply, point 5, retained as directed by the user. This supersedes the older 31-89/90-day sequence; it is not a new proposal for Robert to reconfirm.

| Stage | Recorded action |
|---|---|
| 1+ DPD, Arrears, Default or Disputed | Immediately freeze secondary listing and trading. |
| Days 1-7 | Grace/technical-clearing stage with automated payment retries and **daily SMS/email** to the borrower; secondary remains frozen. |
| Days 8-21 | Recovery-team outreach, **penalty-interest accrual**, an **enforced structured daily repayment plan**, and **weekly investor portal updates**. This fixes the stage in which penalties/daily recovery operate, not their unspecified amounts. |
| Days 22-30 | Formal-default stage: if uncured by day 30, declare Default, immediately initiate legal recovery, call guarantees/collateral and file CRB default reports. Exact deadline ordering is IN-05. |
| Days 31-45 maximum | **Legal liquidation proceedings or reserve-fund buyout/payout mechanisms** execute to deliver **final investor capital resolution**. Keep the full answer; do not recast day 45 as merely a reporting checkpoint. |

**Source versus later design:** Robert's source supplies no penalty percentage or complete accrual/daily-plan calculation. Section 8.4 selected the simple overdue-principal basis; section 8.5 now records Aminu's acceptance of the proposed 2%-annual rate, grace/accrual/rounding/allocation and daily-plan formula. `penalty_rate` is resolved as a design input, not attributed to Robert or a regulator. IN-05 still covers due-date/holiday/intraday mapping and the remaining executable ledger boundaries.

**Operational evidence, not a repeated policy question:** the answer selects liquidation or reserve buyout/payout but supplies no actual reserve-funding arrangement, provider/balance, authorized payout instruments or guarantee/collateral instruments. Section 8.5 selects the reserve design and shortfall response, not an actual bank balance or signed agreement. Kimani/Robert own those financial/operational arrangements; Aminu/Erastus map the evidenced arrangement into the contract. `reserve_funding_arrangement` remains null. Recording the day-45 requirement does not certify funding, enforceability or provider capability; required external clearance remains separate.

The companion's `answer_reconciliation` preserves these source-backed dispositions as the engineering-2026-09-22.1 snapshot. `accepted_defaults` preserves the engineering-2026-09-23.1 choices; `financial_defaults` records the subsequent engineering-2026-09-23.2 approval and explicitly supersedes the earlier unresolved penalty design. Actual `reserve_funding_arrangement` evidence remains null and both activation flags stay false. No broad follow-up questionnaire is generated.

### 8.4 Explicit defaults accepted by Aminu - 2026-09-23

Historical scope of engineering-2026-09-23.1. Section 8.5 supersedes this section's penalty rate/schedule and reserve-design gaps; the other selected defaults remain unchanged. Section 9.6 supersedes the historical owner-review wording below; it is not a current request to approve those defaults again.

These choices supersede only the corresponding gaps in the earlier sections. They are design inputs accepted from the assistant's proposal, not backdated Robert/Kimani answers or production configuration.

| Input | Accepted choice | Retained boundary |
|---|---|---|
| Four/five-month pricing | Exact **1/6** and **1/3 percentage points** respectively, added to the existing rating-derived total-term return. Three months remains 0; six months remains 1/2 point. Clamp the complete result to 10–15%, then half-up round once to one decimal place. | Fractions are not pre-rounded to 0.1667/0.3333 and are not APR. A 5.00 rating gives 10.0%, 10.2%, 10.3%, 10.5% for 3/4/5/6 months. Financial-owner concurrence and legal applicability remain required. |
| Standard normalized NOCF | Exact arithmetic mean of **36 verified monthly observations for first-time applicants**, or **12 for eligible repeat borrowers**. Keep genuine seasonal peaks, zero and negative months; no automatic trimming or positive clamping. | Missing evidence is unavailable, not zero. Preserve the confirmed owner-draw and existing-debt deductions once. Existing scorecard inputs, TTM exposure and eligibility rules are not replaced. |
| Monthly projection | For each projected calendar month, use its corresponding observation from the **latest audited year**, with **no assumed growth**. | Preserve negative observations and refuse missing source months. This is the baseline monthly operating-cash-flow projection, not a replacement for the confirmed deductions, repeat 1.2-times-median-revenue cap, repeat monthly 1.35 DSCR or final standard 1.50 target. Kimani's credit-risk approval and worked-case validation remain pending. |
| Hold calendar | **Africa/Kigali**; Monday–Friday excluding officially observed Rwanda public holidays. Starting after the original flag date, count five eligible dates; expire at the original local time on the fifth date. | Confirmation never restarts the clock. The separate 24-hour deadline is elapsed time. The convention is selected, but the official holiday data/version is not supplied or verified here. Synthetic holiday cases are not a real calendar. Trading's all-days record cutoff is unchanged. |
| Penalty basis | Simple, non-compounding accrual on **overdue principal only**. | No numeric rate, day-count, accrual start/end, rounding/allocation or daily-plan formula was proposed or approved. The rate stays null, not zero. Preserve Robert's recovery stages and day-45 direction. |

**No reserve default:** reserve funding, custody, coverage and payout authority require actual arrangements and evidence. No funding source, balance, guarantee, collateral instrument or shortfall treatment is invented. The other non-numeric gaps in IN-03/05/07/08/09/10/11 remain outside this approval.

**Legal-review dependency:** [BNR Regulation 55/2022, Article 61](https://www.bnr.rw/documents/REGULATION_N___55_2022_OF_27_10_2022_RELATING_TO_FINANCIAL_SERVICE_CONSUMER_PROTECTION.pdf) was identified during proposal review as relevant to penalty basis and flat-interest treatment. Qualified Rwanda counsel must determine applicability to Rozine's specific notes and schedule. This is a recorded review requirement, not a legal clearance or a replacement pricing model.

That revision retained the 37 original examples, 48 vector dispositions and eleven prior amendment examples unchanged, and added seven pricing and four synthetic-calendar examples. Its fixture SHA-256 was `8e1c4bd6043f4610c9556ef093ec4e542e0eeda4f3365fb9bce916d8ed666639`. These remain specification arithmetic, not credit calibration, provider capability or independent approval.

### 8.5 Financial-default package accepted by Aminu - 2026-09-23

Historical approval transcription for engineering-2026-09-23.2. Section 9.6 records the subsequent user-reported owner go-ahead and supersedes this revision's pending-owner-review wording; it does not fabricate Erastus's review or external clearance. The rate is a proposed product rate accepted by Aminu, **not a regulatory rate or validated market benchmark**. Preserve Robert's full recovery stages and day-45 final investor capital-resolution requirement.

| Design item | Aminu-accepted default |
|---|---|
| Penalty rate and basis | **2% per year (200 annual basis points), not per month**, simple/non-compounding on overdue principal only. Never charge penalties on contractual interest, fees or accumulated penalties. |
| Grace and accrual | Each overdue principal amount receives **seven penalty-free calendar days**. Accrue from its eighth overdue day, without backdating. For each chargeable day use `eligible overdue principal * 2 / (100 * days in that calendar year)`: 365 normally, 366 in a leap year; split accrual across years. |
| Rounding | Retain exact fractional accrual. Post the difference between the new cumulative accrual rounded half-up to whole RWF and the previously posted cumulative accrual. Do not round every daily charge independently or compound posted penalties. |
| Stop conditions | Stop for repayment of that principal, write-off or an applicable legal restriction. Moving beyond recovery day 21 does not itself stop accrual. |
| Payment allocation | Apply receipts to **oldest overdue principal first**, then due contractual interest/return, then penalties. No automatic collection-cost surcharge. |
| Daily recovery plan | During **days 8-21 inclusive**, the daily target is remaining arrears divided by remaining calendar days through day 21 (`22 - current DPD`). Recalculate after receipts and require recovery-team affordability review. This is a collection target, not a new issued investor schedule or authority to rewrite one. |
| Reserve funding and coverage | For the pilot, use **company/shareholder capital** to cash-back **100% of all outstanding principal promised protection**, including new protected commitments before acceptance. Investor wallet balances and expected future fees are not funding. This is a required funding design, not evidence that capital exists. |
| Reserve custody and payout | Segregated account with legally documented custody/payout arrangements. Pay protected investor principal less principal already recovered; no guaranteed future return or penalty income. Prepare during days **31-44** and complete by **day 45** if recovery has not resolved protected principal. |
| Reserve controls and shortfall | Daily reconciliation, **two distinct authorized approvers**, recorded payout evidence and documented legal treatment of subsequent recoveries. Insufficient backing blocks additional protected commitments. An actual shortfall is an incident, **not a paid state**. |

**Worked scope:** RWF 3,000,000 overdue for 30 chargeable days in a 365-day year accrues `1,800,000 / 365` RWF, posting **RWF 4,932** cumulatively. In a leap year it posts **RWF 4,918**. Synthetic backing of RWF 13M covers RWF 10M existing protected principal plus a RWF 3M new protected commitment; RWF 12,999,999 or unverified backing does not. Passing this backing check alone does not authorize a commitment or payout.

**Design acceptance is not evidence closure:** actual `reserve_funding_arrangement` remains null. Required real capital, custody and payout instruments, guarantee/collateral arrangements, subsequent-recovery legal treatment, original applicability/disclosure evidence, provider evidence and issue #90 results are not defaulted to passed. Section 9.6 supersedes .2's request for another financial-terms approval: the selected defaults need no repeat owner round. Actual arrangements and any outstanding original external clearance remain evidence matters; A/E still review the exact new calculations/transitions. No actual account, funding transfer, borrowing commitment, recovery collection or payout is created here.

**Subsequent engineering mapping:** MC-03 now supplies the candidate intraday boundary, reconciled effective-time/correction rule and daily-target rounding; CFG-04 still records missing due-date/distribution/authority policy. These are new engineering mechanics for exact-revision review, not a retroactive expansion of the .2 approval. Other input-register, provider, brand/native/device and Phase 0 exit evidence is unchanged. Phase 1 stays ON_HOLD_BY_USER.

That revision added eight penalty and three reserve-backing examples while preserving every original and previous-amendment example. All are synthetic specification checks, not runtime or funded-protection proof. Historical .2 fixture SHA-256: `227cb86bb7ff8e11f73650a50eea8dc2a5fbba03b4aefdeda7a857c0395661ee`.

### Provider intake prepared, not sent

Use the existing [provider register](provider-dependency-register.md), not a replacement procurement list. DEP-01/02/03/04/05/06/07/10/12 route to Kimani with Aminu's technical input; DEP-08/11 to Robert with engineering; DEP-09 storage/key custody to Aminu with Erastus cross-review; DEP-13 native distribution to Erastus with Aminu's server input. These are the register's proposed allocations, not new acknowledgements.

For each, record lead acknowledgement, existing-arrangement reference, allowed operations/fields, request/result/error/event shapes, authentication/callback validation, timeout/retry/idempotency, reconciliation/reversal semantics, environment isolation, evidence reference and next review date. Non-API legal/regulatory tracks use document scope/version/outcome instead of invented endpoints. Specify synthetic success/failure/stale/reordered/revoked outcomes; a fake clearance artifact is not actual approval. Secrets stay out of the repository. No outreach occurs in this task.

## 9. Acceptance and handoff

The companion pack contains an explicit disposition for every GV-001-GV-048, retaining original statuses and keeping baselined/implementation flags false. Revised history, DSCR, tenor, bounds and risk expectations must not inherit the old ready status. Small-principal schedule examples remain arithmetic examples only, never eligible RWF 3M loan offers. Numerical worked examples and planned state/race scenarios are separate collections, so a text assertion cannot be reported as a successful runtime race.

**Historical review artifact (engineering-2026-09-22.1):** the original 37 numerical examples, 16 planned scenarios, 48 vector dispositions, three source-backed input reconciliations and six Aminu section-review records were preserved. That revision added the scoped Erastus review, Aminu's amendment acceptance, two amended section hashes, six bid-reserve arithmetic examples and five minimum-fill examples. Its fixture SHA-256 was `53e20605a45b30065e29de211836f2f5cf1b0800ab924beefcde69a3c88e352c`; the current fixture identity is in section 10.9, and selected defaults are in sections 8.4/8.5. These are synthetic specification checks, not provider evidence or executed application trades.

### 9.1 Aminu's recorded section review - 2026-09-21

**Evidence:** six direct user response annotations on the earlier linked review list (source message `msg_08a35f686d830a89016ab0c52c412487d0b5966cd547f5dd16`). The quoted line numbers are navigation history, not stable section identifiers. The selections were mapped to engineering-2026-09-20.2 and preserved unchanged in .3. The companion retains those historical hashes and responses; only AR-02 and AR-05 now have separate amended hashes and acceptance records in section 9.2.

| Review ID | User annotation | Selected scope | Verbatim response | Recorded result |
|---|---:|---|---|---|
| AR-01 | 1 | Section 3: exact operation order and worked downstream examples | approved | AMINU_APPROVED |
| AR-02 | 2 | Section 4: wire formats, authorization, failures and reconnect behavior | correct | AMINU_CONFIRMED |
| AR-03 | 3 | Section 5: actions and logical record structures | correct | AMINU_CONFIRMED |
| AR-04 | 4 | Section 6: secondary arithmetic and quantity-aware states, before engineering choices | correct | AMINU_CONFIRMED |
| AR-05 | 5 | Section 6: specific engineering choices for joint review | agreed | AMINU_AGREED |
| AR-06 | 6 | Section 7: recovery, account holds and offer transitions | correct | AMINU_CONFIRMED |

This is Aminu's historical review, not an agent-generated signature and not Erastus's concurrence. In particular, AR-01 approves the written downstream calculation order and displayed examples with their supplied-input assumptions, not an absent upstream formula or independent baselining of all 48 vectors. The original AR-05 agreement retained unresolved sub-minimum-fill alternatives and partial-release details; section 9.2 now records their resolution. AR-06 retains Robert's full recovery and transition direction without fabricating financial parameters or operational evidence.

No missing input is filled by this approval. No whole-contract freeze, production activation, Phase 0 exit or Phase 1 development authorization is recorded. Erastus's later review is separately attributed below, not inferred from Aminu's annotations.

Section hash convention: SHA-256 of the exact UTF-8 substring from `start_heading` (inclusive) to `end_heading` (exclusive), including whitespace. A later edit to approved text requires a new review disposition rather than carrying this approval over silently.

### 9.2 Erastus's review and Aminu's amendment acceptance - recorded 2026-09-22

**Review source:** [Erastus's engineering review on issue #91](https://github.com/rozine-rw/rozine/issues/91#issuecomment-5764744196), authored by Engineersticity on 2026-09-21 at 17:31:17 UTC. It reviews engineering-2026-09-20.3, fixture SHA-256 `afa73ed3277e86b356437fe456c8278e1c4acc1451264fb7e6ea3d7610db4b48`, identified by him as merged in PR #92 (`1811cc29`). Dispositions: AR-01/03/04/06 agree; AR-02 agree with A2-1/A2-2/A2-3 amendments; AR-05 requires A5-1/A5-2/A5-3 changes.

**Aminu's direct acceptance:** "Okay, agreed with what Erastus proposed for AR-02"; "You're correct with your intuition for the locking mechanism"; then "I agree with you" to adopting the partial-release and skip rules while preserving seller same-Note bundling. These decisions authorize the amendment transcription in sections 4 and 6. They do not claim either developer has reviewed the exact newly edited hash.

- A2-1/A2-2/A2-3: Party/command-scoped keys and canonical permitted-field hashes; longest timer plus 24 hours minimum retention; deterministic scoped 404 versus actionable 403; pending financial UI and server-time countdowns.
- A5-1: one Note gate for ordinary single-Note commands; all required Note gates sorted before wallets for cross-Note commands; discovery before wallet locking, revalidation, same-key bounded retries.
- A5-2: each fill releases unused price/fee reserve atomically; unmatched active units retain only their limit-price reserve; terminal release is exactly once. No buffer for fixed-ask checkout.
- A5-3: skip sub-minimum single-listing candidates and wait if none qualify. The matcher does not combine separate listings. A seller may bundle their own available same-Note units into one listing, preserving Robert's answer. This clarification, and the cross-Note lock clarification, are Aminu-approved interpretations, not verbatim reviewer wording.

**Independent evidence scope:** Erastus reports recomputing EC-001-EC-037 with his own exact-fraction implementation (37/37 match), recomputing GV-020/021/026/040, reviewing all 48 GV dispositions without corrections, and proving/checking the bid buffer. Record this as reviewer-provided evidence for the original .3 snapshot. The original numeric/vector collection hashes remain pinned and unchanged. The eleven new amendment examples and the revised document hash are not covered by that original check; local Pest arithmetic is not a substitute for his independent revised-candidate review. No further confirmation of his already stated original dispositions is requested here.

**Ownership recorded from his review:** Aminu edits the server contract/fixtures (sections 3-6); Erastus owns client flows and DEP-13 native handoff. Integration order is wire primitives, secondary state machine, web UI, then native companion after web parity. Native estimate/allocation and remaining owner/evidence gates are not filled by assigning ownership.

Remaining review checklist:

- [ ] Review AM-01-AM-12 against sources; reject any accidental old constant or unsupported approval.
- [x] Record Erastus's six section dispositions and Aminu's acceptance of the AR-02/AR-05 amendments, including client error, partial-fill, deadline and uncertain-outcome requirements. These are design decisions, not executed UI evidence.
- [ ] Trace every remaining IN item to actual evidence/owner decision or an enforceable later gate; no silent default.
- [x] Record Erastus's independent original .3 numerical/vector review against its exact fixture hash.
- [ ] Review the revised candidate and new amendment examples at their exact hash before final freeze/promotion; complete missing expectations without inventing owner inputs.
- [x] Record the editor/client ownership and integration order provided by Erastus; native estimate/schedule acceptance remains IN-10.

When authorized, prepare the candidate commit and full exact-SHA CI/non-author review package under the approved branch flow. This draft does not claim the final contract freeze, architecture/API parity implementation, PostgreSQL race proof, real-device evidence, provider kickoff, Phase 0 exit or Phase 1 start. Old source files and Word snapshots are unchanged.

**Historical handoff at .22.1:** Erastus's original review and Aminu's amendment decisions are recorded, with AR-02/AR-05 incorporated and IN-06 choices resolved. IN-01/02/04 answers remain incorporated, with only unsupplied parameters, engineering mapping and operational evidence retained as gaps. Revised-candidate independent review, joint freeze and other input-register items remain open. The [additive resolution comment on #91](https://github.com/rozine-rw/rozine/issues/91#issuecomment-5773405275) was posted on 2026-09-22 without replacing the original snapshot or closing the issue. No application development, provider calls, commit, push, merge or deployment was authorized by that update.

### 9.3 Aminu's as-is input confirmation - 2026-09-23

Historical confirmation of engineering-2026-09-22.1, before the explicit defaults were proposed and accepted. Section 9.4 records that later decision without rewriting this earlier scope.

**Direct user instruction:** "lets close and confirm all inputs as it is".

**Recorded decision:** CONFIRMED_AS_WRITTEN. Aminu accepts the definitions, selected rules and recorded dispositions in IN-01–IN-12 as they stand in engineering-2026-09-22.1. No further Aminu confirmation of those same written choices is requested. This records his acceptance, not a new signature attributed to Robert, Kimani, Erastus or an external authority.

**Accepted baseline:** PR #93, feature commit `d95d62e8d926eb603e3382510c79e9333f22801a`, merged to `dev` as `a75febd5a60f561ed0bf78c3d76c87642946dea5`. The user reported Erastus's review and merge complete; [PR #93](https://github.com/rozine-rw/rozine/pull/93) and the [successful five-job merge-commit CI run](https://github.com/rozine-rw/rozine/actions/runs/35766097070) were verified on 2026-09-23. This supersedes the earlier candidate-promotion-pending narrative, without rewriting the original .3 independent-review scope or claiming a new independent recomputation of the eleven amendment examples. The companion fixture SHA-256 remains `53e20605a45b30065e29de211836f2f5cf1b0800ab924beefcde69a3c88e352c`; sections 3–7, arithmetic examples and their approved hashes are unchanged by this acceptance record.

**What cannot be closed as supplied input:** `four_month_premium`, `five_month_premium`, `standard_nocf_method`, `penalty_rate`, `reserve_funding_arrangement` and `hold_business_calendar` remain null. The non-numeric gaps listed in IN-03/05/07/08/09/10/11 also remain as written. Acceptance does not select an unstated option, interpolate a price, supply a formula, certify an arrangement or manufacture evidence. These gaps are not waived or moved to a later phase by this instruction; their dependent behavior stays blocked until an explicit value/disposition and applicable evidence exist.

**Closure boundary:** Aminu's confirmation of existing written inputs is complete; resolution of all executable inputs and joint contract freeze are not complete. Issue #90 device/dwell results, provider kickoff, brand exports/rights and native delivery evidence remain separate. No application code or financial behavior is activated. Phase 1 remains ON_HOLD_BY_USER; this record does not declare Phase 0 exit or authorize development with unresolved inputs.

### 9.4 Aminu accepts the explicit proposals - 2026-09-23

Historical scope of engineering-2026-09-23.1, before the later financial-default approval in section 9.5.

**Direct user instruction:** "go with your proposals", responding to the immediately preceding table and projection/penalty/reserve explanation. Record **AMINU_ACCEPTED_PENDING_REQUIRED_REVIEW** for section 8.4. The selected premium values, mean/projection methods, calendar convention and simple overdue-principal penalty basis no longer require another Aminu choice. This does not attribute concurrence to Robert/Kimani, credit-risk validation to Kimani, independent revised-pack review to Erastus or legal clearance to counsel.

The new artifact version is **engineering-2026-09-23.1**. The companion's `prior_snapshot` preserves the engineering-2026-09-22.1 fixture identity; all original section hashes and reviewer scopes remain historical and intact. The accepted-default overlay and its new examples need their own review. `penalty_rate` and `reserve_funding_arrangement` remain null, and the missing penalty schedule, official holiday dataset and other workflow/evidence inputs remain open. **Phase 1 remains ON_HOLD_BY_USER**; no application implementation, commit, push, deployment, issue closure or Phase 0 exit is implied.

### 9.5 Aminu accepts the financial-default package - 2026-09-23

**Evidence:** direct user response annotation 1 on assistant message `msg_08a35f686d830a89016ab37ebe9cf887d0a04df44f98c2a91f`. Selection: "My recommendation: adopt this as the proposed financial-default package. It would resolve these design choices once approved". Verbatim response: **"go ahead"**.

**Recorded decision:** AMINU_ACCEPTED_DESIGN_PENDING_REQUIRED_REVIEW_AND_EVIDENCE for section 8.5. The proposal's penalty economics, grace/accrual/rounding, payment ordering, recovery target and fully backed pilot-reserve design are accepted by Aminu and no longer need another selection from him. This does not state that Robert/Kimani approved the new financial terms, counsel gave legal clearance, Erastus checked the revised examples or any reserve account was funded.

That revision was **engineering-2026-09-23.2**. The companion's `financial_defaults_previous_snapshot` pins .1 at `8e1c4bd6043f4610c9556ef093ec4e542e0eeda4f3365fb9bce916d8ed666639`. Earlier source answers, selected defaults, original section hashes and independent review scopes are preserved. Only actual `reserve_funding_arrangement` remains in the historical missing-parameter object; this is not an assertion that the whole input register is complete. Section 9.6 supersedes the historical pending-owner-review flags; current engineering configuration, review and evidence status is in section 10.9. No Phase 0 exit, Phase 1 development, commit/push, issue closure, external communication or financial activation was authorized merely by recording that acceptance.

### 9.6 Owner go-ahead reported by Aminu; specification work authorized

**Direct user clarification:** "what does legal/owner needs to review again, they already gave the go ahead because these are minor decisions". Record **CONFIRMED_AS_REPORTED_BY_AMINU** for the accepted defaults in sections 8.4/8.5. The historical `required_reviews` objects remain intact for provenance; `current_review_status` supersedes their repeat minor-policy approval flags. Do not send the same defaults back for approval. This does not invent a direct Robert/Kimani signature, an external legal opinion, funded reserves or Erastus's review of the new revision.

**Current instruction:** annotation 1, **"proceed"**, on "All this specification work can proceed while #90 runs. Actual reserve funding and provider paperwork remain separate operational evidence—not another minor-policy approval round." This authorizes the engineering candidate below, not Phase 1, issue #90 changes, provider outreach or financial operations. New engineering mappings are identified as such, not attributed to Robert's answers. Blank business-policy values are not filled by extending the minor-default approval to choices that were never proposed.

## 10. Consolidated completion candidate - engineering-2026-09-23.3

This is the preserved .3 candidate. Section 11 now adopts these mechanics for the MVP except where expressly amended, supplies CFG-01–CFG-05 and removes the Erastus-review gate. Read any pending-input/review wording below as history, not as a current approval request. Operational evidence remains separate; no synthetic example certifies real money or device behavior.

### 10.1 MC-01 - verified monthly evidence to underwriting

Each observation binds `business_id`, complete `month`, source-document/transaction references, content hashes, classification/procedure version, audit status and supersession lineage. Reconcile the complete rail inventory before accepting a month. Deduplicate the same economic transaction across imports; ambiguous duplicates and unresolved classifications block the snapshot. A repeated month cannot make a short history complete.

Classify operating inflow/outflow separately from financing, inter-account transfers, owner draws/returns, debt principal and debt service. Transfers are not new revenue; owner draws and existing debt service are not deducted inside operating outflow and again from CFADS. The confirmed exception handling for returned draws and one-off items still applies. An audited true zero is allowed; a missing or unverified observation is not zero. Revised evidence creates a new immutable snapshot, invalidates stale calculations and never edits an issued snapshot.

Compute the 36/eligible-12-month mean and both max deductions as exact fractions on the **same selected observation window**. Round only where the approved schedule/pricing rules require it. CM-01 produces CFADS RWF 3M from 30 months at RWF 2.4M and six at RWF 7.2M, less RWF 100,000 each for draws and debt; feeding this into EC-010 preserves the RWF 10,704,728 result. CM-02 retains six negative months and yields exact CFADS `3,700,000 / 3`, not a rounded input. CM-03 is repeat-window arithmetic only, not proof of repeat eligibility.

The corresponding-month/no-growth projection, monthly 1.35 test, 1.2-times historical-median-revenue cap, final 1.50 standard target, original-request cutoff and TTM exposure safeguards all remain mandatory. These examples do not finish the full scorecard or supply missing monthly source evidence. The 36-month baseline must remain retrievable under a versioned retention schedule; no automatic deletion at the older 24-month ceiling, unlimited raw-data retention or invented statutory period is selected. CFG-05 supplies that schedule.

### 10.2 MC-02 - primary commitments, funding and issue boundaries

| State / event | Candidate contract and failure outcome |
|---|---|
| Calculation / proposed offer | Not a cash movement. Requested principal outside RWF 3M–100M is invalid input; no forced upsizing or silent clamping. Inside that range, test the original request before any permitted downsizing. |
| Accepted offer | One economic commitment ID reserves the full accepted amount against borrowing room. Publishing and investor funding carry that same ID; neither creates a second exposure. |
| Live / partly funded campaign | Track available capacity, live reservations and confirmed investor commitments separately. Lock the campaign/exposure aggregate before affected wallets in deterministic order; revalidate capacity, mandates, limits and expiry. Never accept more than remaining capacity or issue a transferable Holding from an unpaid reservation. Primary checkout duration and voluntary cancellation remain CFG-03, not borrowed from secondary. |
| Normal 30-day unfunded expiry | At `live_at + 30 * 86400 seconds`, no new admission/confirmation. An incompletely funded campaign closes, reservations release and all committed investor principal returns in full without fee (BR-28), atomically with ledger/exposure effects. Unlike secondary, no late checkout extends this deadline. A late provider receipt is reconciled separately; it cannot reopen the campaign. |
| Fully funded / pre-disbursement | Full funding is not disbursement. Recheck current evidence, original-request/final DSCR, exposure, restrictions, mandate and conditions precedent. A failed recheck blocks disbursement and preserves cash ownership/term snapshots; do not invent an automatic changed-term acceptance, cancellation or refund. CFG-03 must supply the final disposition for this branch. |
| Disbursement pending / unknown | Persist an authorized intent/outbox after the precheck. Keep exposure committed. An HTTP timeout is not proof that funds moved or failed; no second send with a new identity. |
| Confirmed disbursement / issue | On the verified successful outcome, atomically convert the same exposure from committed to outstanding and issue immutable investor terms/rights with the contractual effective date. This engineering issue boundary does not permit changing terms already purchased during funding. No partial or unknown provider outcome becomes `ISSUED` automatically. |

Race acceptance cases for later implementation: last two investors compete for the final capacity; expiry competes with confirmation; accepted commitment competes with another application; policy/evidence revision changes after precheck; callback arrives after expiry; duplicate successful callback. The specification defines the serial outcome, but these are not executed database races.

### 10.3 MC-03 - servicing dates, accrual and recovery bookkeeping

**Engineering timing candidate:** store the disclosed immutable due date and calculate DPD from calendar dates in Africa/Kigali: due date = DPD 0, next date = DPD 1. Do not roll a disclosed due date forward for weekends/holidays. This does not choose the origination day-of-month/month-end convention; that is CFG-04. Secondary's daily 00:00 entitlement boundary remains all-days and separate from a bank processing cutoff.

DPD 30 runs through the end of that local date. If still uncured, formal Default applies at the start of DPD 31; a reconciled payment effective before that boundary is evaluated first. Preserve the days 22–30 recovery stage, day-1 trading ban and day-45 final capital-resolution obligation. Cash arrears, report compliance, disputes, account cases and reserve payout are separate states: clearing one does not clear all restrictions. A day-45 shortfall is an incident, never a successful payout flag.

For the intraday penalty candidate, close each chargeable local calendar day using that principal item's remaining eligible balance **after reconciled receipts effective on that day**. No charge on the portion fully cleared that day, no start-of-day charge after repayment and no intraday compounding. Keep an exact fraction for the relevant 365/366-day year, accumulate, then post the approved cumulative rounded delta. Correct verified backdated receipts with attributed compensating entries and a new calculation revision, never by rewriting ledger history. Provider effective time must be authenticated/reconciled; browser time and an unverified callback do not establish payment.

Daily collection targets use the approved `arrears / (22 - DPD)` for DPD 8–21, half-up to whole RWF and capped at remaining arrears. Recompute after each reconciled receipt; RWF 1 remaining at DPD 8 can round to zero today, but becomes RWF 1 by DPD 21 if still unpaid. Affordability review and the original issued schedule remain intact. Allocation remains oldest overdue principal, then due contractual return, then penalties. Excess is unapplied cash pending its authorized disposition, not an invented fee or silent prepayment. Borrower service fees/investor payout fees retain their own disclosed bases; do not stack secondary fees or collection surcharges onto them.

The remaining partial-receipt holder distribution, statutory/cure authority and actual due-date policy are CFG-04. Do not report the entire servicing model as frozen merely because penalty arithmetic passes.

### 10.4 MC-04 - exact unit-component ownership and residuals

For a **specified equal-weight issued-unit grid**, freeze integer unit ordinals and each principal/return instalment component at issue. For a component of `M` whole francs across `U` units, allocate `floor(M/U)` to each, then one additional franc to each of the first `M mod U` ordinals. Apply the rule independently to each immutable component; the unit's total rights are the sum of its component allocations. This is not a claim that every unit necessarily has a RWF 5,000 face value.

UC-01 allocates RWF 10,001 as `[3334, 3334, 3333]`; UC-02 allocates RWF 2 as `[1, 1, 0]`. Listing, reservation and trade carry the exact selected unit ordinals and original component rights. Splitting or merging holdings never reallocates the remainder or duplicates a unit. Vested entitlements stay with the record-date owner; only future transferable rights move. Secondary price limits use the selected units' actual unpaid principal and remaining transferable payout, not a recomputed rounded average. Ask, fees, whole-unit/minimum-trade rules and accepted matching/lock order do not change.

This settles component rounding **conditional on a chosen grid**, not the missing choice of primary denomination or issue-target remainder handling (CFG-02). It also does not silently choose a cash-distribution algorithm for partial repayments (CFG-04). No loan is rounded down to a multiple of RWF 5,000 without that explicit decision.

### 10.5 MC-05 - hold deadlines and independent restriction cases

Treat automated flag, confirmation, formal finding, expiry and manual release as revision-checked events on one case. Confirmation is allowed strictly before `flagged_at + 24 hours`; at that instant an unconfirmed hold has expired. The original five-business-date deadline never resets. At that deadline a confirmed review hold expires absent a separate evidenced formal finding. A finding requires its authority, basis, scope, notice and next review date; it is not a renamed indefinite review hold. Do not invent a universal maximum duration for a legal restriction.

Evaluate `allowed_actions` against the union of current applicable case scopes and Note restrictions. Releasing one case cannot clear another; it cannot recreate cancelled orders/bids or reset old timers. A restriction admitted before a conflicting settlement blocks that settlement; one admitted after it cannot erase the completed trade. Affected reserved cash/units must have an exactly-once release or continued legal restraint according to the configured scope, not a guessed withdrawal permission. High-risk triggers and existing bid/checkout action scope remain CFG-04; ordinary frequency/cancellation alerts remain review-only. The official holiday dataset must carry source/version/coverage; synthetic dates are not operational evidence.

### 10.6 MC-06 - authority and policy completeness

Every command resolves authenticated person, active Party/role, ownership, effective mandate and applicable policy versions on the server. A client-supplied role, a valid resource ID or `allowed_actions` cached before a revocation is insufficient. Missing role-combination/mandate policy fails closed; this specification does not silently select a previously unsigned D-05/D-64 option. All required joint signatories must have valid authority for the same command/version; a numerical count cannot substitute for a mandate.

Listing fee/tax/refund policy, investor category/tier limits, own/connected-business restrictions, investor disclosure audiences and override permissions are mandatory structured inputs. Missing means `POLICY_INPUT_REQUIRED`, never zero fee, no limit, permissive audience or superadmin bypass. No override may manufacture missing evidence, an unbalanced ledger or a required approval. Raw statements, bank identifiers, internal risk components and trigger evidence remain restricted. Existing scoped 404/403 and idempotency contracts are unchanged.

### 10.7 MC-07 - Auditor package and ingestion boundary

The candidate envelope is `assignment_id`, `actor_id`, `device_key_id`, `package_id/version`, `procedure_version`, `evidence_id`, sequence, content digest, capture-time basis, location observations and signed manifest reference. Authenticated actor/assignment/key/policy are checked independently of those supplied fields. Separate captured time, uploaded time and server committed time. Signatures bind canonical bytes/content identity; key/algorithm/canonicalization policy is versioned under CFG-05, not chosen by a client.

| Ingestion event | Required specification outcome |
|---|---|
| Same identity, same digest | Return the original ingestion receipt; no second evidence/report item. |
| Same identity, different digest or conflicting sequence | `EVIDENCE_CONFLICT`; retain conflict provenance, never overwrite the original. |
| Out-of-order or partial upload | Stage only; do not mark report-ready until the authorized complete manifest is satisfied. |
| Key/assignment/package revoked or expired at commit | `EVIDENCE_REJECTED`; an earlier capture timestamp alone does not bypass current authority. |
| Clock reboot/rollback, unsupported integrity or missing required evidence | Explicit unverified/review/denial outcome from the approved policy; no fabricated trustworthy location/time or gallery fallback. |
| Successful ingestion | `INGESTED_NOT_AUDIT_APPROVED`. Co-signature requires current standing, correct procedure, completed review and valid seal. |

A corrected published report is an amendment, never an overwrite. Lateness attribution records which required action was unavailable and who controlled it; uploading alone does not justify freezing the Auditor's yield. Offline revocation cannot promise immediate device erasure. Native support, TTL/retention, dispatch, standing refresh, conflicts and seal lifecycle remain explicit CFG-05 settings. D-04 Option B and the native estimate/allocation remain as documented; no companion implementation, device pass or issue #90 result is created here.

### 10.8 MC-08 - provider port and failure contract

Financial intent fields: internal `operation_id`, kind, Party/mandate references, exact RWF amount, tokenized beneficiary/account reference, original idempotency identity, expected resource/policy revisions and environment. Results bind that operation, provider reference, `PENDING | SUCCEEDED | FAILED_FINAL | UNKNOWN`, authenticated effective/observed timestamps, evidence reference and mapped error. References are opaque; secrets and raw account identifiers are not logs or public Resources. Nonfinancial verification ports return scoped/versioned evidence outcomes, not financial success or policy approval.

Persist intent/outbox before calling the provider, outside database locks. `UNKNOWN` means reconcile or query the **same** operation, not retry with a new key or release its reserved money. A provider that does not guarantee safe idempotent sends gets no automatic send retry after an uncertain result. Database serialization/deadlock retries use the original command key, reload all versions and rerun the entire local transaction, with a candidate maximum of **three total attempts**. Exhaustion returns `RETRYABLE_CONTENTION`; the UI resolves operation status before a same-key retry. The retry count is engineering configuration, not a new financial policy.

Callbacks must pass signature/authentication, environment, operation, currency, amount and transition checks. Duplicate delivery returns the recorded result without another posting. A late failure after confirmed success opens reconciliation; it cannot reverse money. A verified reversal is a distinct linked compensating operation under its own authorization. Conflicting final results remain an exception until reconciled. Every successful local effect commits ledger, units, aggregate version, receipt and outbox together; acknowledgement alone is not proof of settlement.

Offline fake cases are success, pending, timeout/unknown, final failure, wrong identity/amount/environment, duplicate, reordered, conflicting final, revoked evidence and verified reversal. CRB has an explicit debt-evidence subtrack under Kimani's existing coordination; KYC clearance never proves external debt completeness. All 13 existing provider tracks remain, including non-API document tracks; this does not create accounts, certify adapters, start outreach or complete provider paperwork.

### 10.9 Exact remaining handoff - no repeat default approval

| Record | Still required | Why this is not another approval of the accepted defaults |
|---|---|---|
| CFG-01 | Primary listing-fee amount/refund/tax, investor limits, role/mandate, disclosure/override configuration | These values/options are not in the UW/SEC answers or accepted default packages. Zero/unlimited/permissive defaults would change the product. |
| CFG-02 | Primary issuance unit grid and target remainder rule | The RWF 5,000 anchor and whole-unit secondary rule do not determine how an arbitrary eligible loan principal is issued. |
| CFG-03 | Primary checkout/voluntary cancellation and changed-funded-offer cash disposition | BR-28 fixes normal expiry refunds, but not these branches. The withdrawn blanket re-consent/refund proposal is not reinstated. |
| CFG-04 | Origination due dates, partial-receipt distribution, high-risk hold action scope and formal-finding/cure authority | The new time/rounding examples resolve their stated mechanics, not absent operational/financial policy. |
| CFG-05 | Versioned audit/standing/dispatch/conflict/lateness/seal/offline/device/retention configuration | Specified ingestion failures cannot choose professional procedures, retention periods or platform capability. Native estimate/allocation acceptance is also separate. |
| Engineering review | A/E compare MC-01–MC-08 and new examples against this exact document/fixture revision; finish outstanding worksheet expectations, then freeze only when the five configuration groups are resolved | Original Erastus review, AR-02/AR-05 agreement and PR #93 merge are not reopened or credited to new material. |
| Operational evidence | Actual reserve capital/custody/payout and guarantee arrangements, real provider kickoff/contracts, official calendar and applicable original external clearance | Evidence must exist; approved design is not a bank balance, provider agreement or external opinion. No repeat minor-policy approval is requested. |
| Issue #90 | Physical-device and actual elapsed dwell evidence | No harness origin, device run, timer or ticket was changed by this work. |

Configuration must be attached to its actual blocked actions, as listed in `completion_candidate.unresolved_configuration`. The original `missing_parameters` object is retained for snapshot compatibility, not advertised as an exhaustive current gap list. `current_review_status` supplies current authority; `accepted_defaults.required_reviews` and `financial_defaults.required_reviews` are historical records. Original snapshots, 37 numeric cases, 48 dispositions and reviewed section hashes remain intact. The new examples are synthetic specification verification, not executed application races, provider certification or independent sign-off.

Historical .3 fixture SHA-256: `5ab03735062470596aa5ef7eba747fb890a29eab4f13a5bcb707789837359c53`.

## 11. Selected MVP defaults and specification-review waiver

**Authority and scope:** Aminu directly instructs us to use defaults and not wait for Erastus's review. Record **MVP_DEFAULTS_SELECTED_BY_USER_DELEGATION** and **WAIVED_BY_USER_NOT_PERFORMED**. This establishes the current MVP specification baseline; it does not forge another owner's signature, complete a professional engagement or certify production readiness. The earlier reports/approvals remain exactly attributed to their original scopes. No repeated internal specification review or new Robert/Kimani questionnaire is required.

The following are explicitly selected product/engineering defaults, not legal/regulatory limits. Implement them as versioned policy values, with the accepted DSCR, fees, exposure, recovery, hold timers and purchased-term protections unchanged except for the narrowly identified issuance/funding mechanics below. Any evidenced lower external limit applies before live use. This update does not change GitHub branch protection, execute financial actions or declare all of Phase 0 finished.

### 11.1 CFG-01 - simple pricing, investor limits and authority

| Setting | Selected MVP default |
|---|---|
| Primary listing fee | **RWF 0, explicitly waived for the MVP.** Record a zero-fee disclosure/receipt, not a missing value or hidden future debt. Refund is zero because nothing was charged. This supersedes the unpriced fixed-listing-fee requirement for this MVP version only. |
| Tax presentation | No listing-fee tax charge when no listing fee is collected; this is not a legal tax-exemption assertion. Other quoted fees remain gross-inclusive with no undisclosed surcharge. Actual tax classification/accounting must be evidenced before real-money charging; no new tax rate or external clearance is invented. |
| Investor verification | Unverified/incomplete KYC/KYB: **RWF 0 transaction capacity**. Verified individuals and legal entities initially use the same capped tier. |
| Investment caps | Gross transaction **RWF 1M**; per Note the lower of **RWF 1M or 20% of original issued principal**; per Business **RWF 2M**; aggregate **RWF 5M**. These are provisional product limits, not claimed Rwanda statutory thresholds. |
| Exposure measurement | Outstanding principal plus principal covered by pending primary commitments/secondary reservations. Reserve room atomically, convert rather than double-count on confirmation, release once on cancellation and reduce on principal repayment. Self/connected-Business trades remain prohibited. Fees require available wallet cash in addition to the gross transaction limit. |
| Roles | D-05 Option B: Investor + Business memberships may coexist; Audit Partner is mutually exclusive for the same verified person. Privileged staff accounts are separate. Enforce connected-person restrictions across logins. |
| Mandates | D-64 Option B: sole trader uses one verified owner/signatory; a company uses its verified entity-specific effective mandate. Verify declared beneficial owners/controllers/directors and every required signatory; do not hard-code two directors. Missing/conflicting authority denies the command. |
| Disclosures | Public: anonymised market summaries. Verified investor: disclosed financial aggregates, rating/basis, tenor, schedule, fees, risks and report summaries. Holder: own terms, receipts, entitlements and recovery updates. Business/assigned Auditor: own/assigned evidence. Compliance/Legal: case-scoped evidence. Raw statements, bank identifiers and internal trigger evidence are never public. |
| Overrides | No MVP override of DSCR, exposure, return, price-band, segregation, KYC, evidence or ledger integrity gates. Operational exceptions need actor/reason/history. Manual money adjustments/refunds outside deterministic system rules require two distinct authorized staff and compensating entries. The Erastus specification-review waiver is not a waiver of these money controls. |

### 11.2 CFG-02 - RWF 5,000 issuance units, without rounding up

Select **RWF 5,000 principal per primary unit** and one unit as the primary minimum. Robert supplied the anchor; using it as the MVP issuance denomination is a new delegated implementation choice, not a backdated Robert answer. Secondary still requires whole units and at least RWF 1,000 gross, with the approved price band and no forced residual buyout.

After the original-request 1.25 test and DSCR-safe/cap sizing, **round the candidate principal down to the nearest RWF 5,000 before offer acceptance**. Rebuild total return/schedule and recheck the RWF 3M floor, room and final 1.50 DSCR. Never round up, collect a hidden remainder, force a RWF 3M offer from insufficient capacity or change an accepted/issued amount. The unrounded capacity remains in the calculation record. EC-010 remains the historical nearest-franc capacity example, RWF 10,704,728; the new MVP offer becomes **RWF 10,700,000 / 2,140 units**, with 12.1% return RWF 1,294,700 and total RWF 11,994,700. The amount reduction is disclosed before acceptance.

Preserve the approved total-payment schedule. Split principal over the tenor using half-up first instalments and a final residual; contractual return per instalment is its total payment minus principal. Amend MC-04's per-component remainder rule to **carry a cyclic remainder cursor across instalments**, with separate principal/return cursors initially at ordinal 1. Each component gives every unit the floor share, then distributes the remaining francs to successive fixed ordinals from its cursor and advances the cursor. This conserves each instalment and ensures every unit receives exactly RWF 5,000 principal across the complete schedule. Never reset the cursor on a holding transfer or recalculate issued rights. Small synthetic unit examples demonstrate rounding only, not eligible loan sizes.

For repeat-track monthly checks, cap projected NOCF at the lower of the corresponding audited month's NOCF and 1.2 times the 12-month median revenue; keep negative values. Subtract the confirmed owner draw once and the greater of historical mean debt service or that month's contractual existing debt. Every projected month must cover its actual new scheduled payment by at least 1.35. Select the largest RWF 5,000 multiple at or below the standard capacity satisfying every monthly check, the standard 1.50 target and all other caps. If none reaches RWF 3M, reject. This supplies the remaining projection mapping without trimming the historical mean or weakening the original-request cutoff.

### 11.3 CFG-03 - primary checkout and failed closing

Primary checkout requires wallet funds and reserves exact whole units/capacity/cash for **five minutes or until campaign expiry, whichever occurs first**. No primary checkout survives the 30-day deadline. No oversubscription, automatic partial allocation or quantity increase. Before confirmation a reservation can be cancelled and released once.

Before full funding, investors may cancel their confirmed commitment for a full principal refund without fee; the Business may cancel the campaign with the same full refunds and exposure release. After full funding, neither has a unilateral cancellation action. Confirmation/cancellation/full funding/expiry serialize on the campaign gate; the winning committed state governs. Normal unfunded expiry keeps BR-28's full, fee-free refund.

**New explicit MVP default for a failed pre-disbursement check:** before a provider payment has been dispatched, cancel the unissued campaign, refund all committed investor principal without fee and release its exposure. A changed principal, rate or schedule is not silently substituted into an investor commitment. An eligible replacement is a separate unaccepted offer and, if the borrower proceeds, a new campaign with fresh investment decisions—not automatic re-consent or migration of the old investors. This resolves the previously missing branch under Aminu's new delegation; it is not attributed to Robert or represented as an earlier accepted proposal.

Once the provider intent has been dispatched or its outcome is unknown, do not refund the same money or start another payment. Preserve the immutable intent and reconcile first; verified failure can release/refund, while verified success records the original purchased terms. Already-issued rating/return/schedule/rights remain immutable. Recheck current policy/evidence immediately before dispatch; later changes do not rewrite an in-flight authorized payment or executed issue.

### 11.4 CFG-04 - dates, receipts and account cases

Due dates use the verified disbursement's Africa/Kigali calendar date as the anchor. The first is one month later; derive each subsequent date from the **original day**, clamped to the target month's last day. January 31 therefore gives February 28/29 then March 31, not March 28/29. Payment is due through that local date, without weekend/holiday shifts. MC-03's DPD, closing-balance penalty, day-30 boundary and whole-franc daily targets apply unchanged.

For each reconciled receipt, allocate to oldest overdue principal, due contractual return, then penalties. Within the frozen component's record-date ownership, distribute proportionally to its **unpaid entitlements**: take each exact fractional share's floor, then give the residual francs to the largest fractional remainders, ties by immutable unit ordinal. Never pay more than the component owes. Repeat independently for each real receipt; final completion clears every remaining entitlement. Do not merge/split provider receipt identities to manipulate rounding. Excess is borrower unapplied cash, not an automatic fee or undisclosed early repayment. The original flat return is not discounted on an authorized early repayment.

Keep the existing 2% borrower service fee as an additional liability on principal/contractual return actually repaid, excluding fees and penalties; do not reduce investor principal to collect it. Keep the 1% investor fee on actual contractual payout, half-up in whole RWF. Route 25% of service fees actually collected to the eligible steward, subject to the existing SLA hold; do not allocate uncollected fees. Collected penalties have a separate platform-revenue ledger, not a promised investor return. Actual tax classification remains a live accounting evidence matter, not another MVP fee-policy selection.

High-risk temporary holds trigger on verified account compromise, authenticated provider fraud alerts, or the conjunction of a new payout destination, credential reset within 24 hours, and a withdrawal of at least RWF 100,000 representing at least 80% of available wallet funds. Ordinary trading/cancellation frequency remains review-only. Block withdrawals, new primary commitments and secondary buying/selling. Cancel affected unsettled reservations/bids once and release funds to their owner's balance, still subject to that owner's withdrawal restriction; cancel affected seller listings without undoing completed trades. Deposits, reconciled repayments and owned credits continue; no automatic external withdrawal or relisting follows.

The existing unconfirmed 24-hour expiry and original five-business-day review clock remain unchanged. A formal finding is a separate evidenced Compliance Officer **or** Legal Counsel decision, with a **30-calendar-day default expiry**, review every five business days, and no automatic renewal. A documented binding external order uses its actual scope/duration rather than this product timer. Notice remains due within 24 hours unless a recorded legal prohibition prevents it. Clearing reconciled overdue principal/return clears only the ordinary arrears cause; other restrictions persist. Default release requires a recorded Compliance/Legal reconciliation. Unpaid penalties stay a separate receivable, not a new instalment-DPD clock.

### 11.5 CFG-05 - minimum Auditor operating policy

**Procedure MVP-AUP-1:** verify the complete rail inventory/originals; reconcile every selected month's opening/closing balances and transactions; deduplicate and classify operating flows/transfers/draws/debt; verify debt schedules, baseline/gaps and exceptions; check registered premises and capture lineage; reproduce underwriting inputs/results; record factual findings/exceptions and co-sign/seal. Any unexplained reconciliation difference blocks verification; tolerance is RWF 0. This is the software's minimum procedure template, not an assertion that an actual professional engagement has been agreed or performed. Actual engagement terms/findings belong to the practitioner and engaging party under the applicable [ISRS 4400 framework](https://www.iaasb.org/publications/international-standard-related-services-isrs-4400-revised).

| Setting | Selected default |
|---|---|
| Standing | Documented manual ICPAR check, no older than 30 days at dispatch/co-signature, plus immediate certificate-expiry/revocation denial. An API is not required to build the MVP. |
| Dispatch | Registered office/premises coordinates verified within 365 days and on any move; distance plus both uncertainty radii must be at most 30 km. Maximum three active engagements. Prefer fewest active assignments, then oldest last assignment, then stable partner ID. |
| Rotation/conflicts | At most three consecutive reports for the same Business before rotation. Deny current financial interest, owner/director/employee/adviser ties within 24 months, and declared close family/business conflicts. No eligible alternative goes to Audit Operations; it does not silently waive a conflict. |
| Timers | Routine acceptance four hours, visit within 48 hours after acceptance; Flash acceptance one hour and completion within 24 hours of original dispatch. Three automated dispatch attempts, then an operational queue with the original deadline. Reassignment does not reset the Flash deadline. |
| Reporting/lateness | Business input target day 3; both report and co-signature still due by day 7. Yield freeze requires evidence that the missed SLA was the Auditor's responsibility; missing Business inputs, system outages or an unassigned job are not automatically Auditor fault. Attribution and release are logged. |
| Seal | JWS ES256 with key ID over JCS-canonical report/manifest payload; SHA-256 evidence digests. Organization-controlled managed server key; device key hardware-bound. Server-key rotation 90 days, device-key rotation 180 days, immediate compromise revocation. Preserve signing time/key/revocation/amendment history; public verification exposes only opaque report ID, digest, seal status and amendment links. |
| Offline package | Capture authorization **24 hours**, upload deadline/maximum unacknowledged local retention **7 days**, both measured from server package issuance; acknowledged local-copy retention **24 hours from acknowledgement**. These are different deadlines. A capture strictly before its cutoff may upload strictly before the later upload deadline if current assignment/key authority still passes. Reboot/untrusted elapsed time stops new capture until online refresh. |
| Upload/recovery | Ten automated attempts per reconnect, exponential backoff from 30 seconds capped at 15 minutes, original operation identity; then visible needs-attention, not silent success. Maximum five images per party/report, 10 MiB per image, 50 MiB per package. Retain until acknowledged or retention expiry; expiry requires crypto-shredding and a recorded loss/recapture case, never a pass. Offline inability to execute a wipe is not proof of erasure. |
| Device target | Android 12+ and iOS 16+ with hardware-bound key, biometric unlock, camera-only path and an accepted server integrity verdict. These are initial support targets, not claimed proven capabilities. Unsupported/unknown capability denies governed capture; no gallery or weak-integrity fallback. GPS accuracy worse than 100 m or premises discrepancy over 250 m creates review, not trusted evidence. |
| Lost/replaced device | Revoke old device key/packages server-side immediately; restore committed evidence only, register the replacement and recapture missing items. Do not clone device private keys or claim an offline device was wiped. |

Use existing proven implementations of [JWS ES256](https://www.rfc-editor.org/rfc/rfc7518.html) and [JCS canonicalization](https://www.rfc-editor.org/rfc/rfc8785.html); this specification does not implement cryptography or install dependencies. Authentication of content is not proof that a photographed scene or GPS observation is truthful.

**Retention:** necessary financial/identity/signed-audit records and supporting loan evidence: **10 years after the later of final transaction and end of relationship**; unfunded rejected-application working copies: **180 days** unless separately required evidence; ordinary operational logs: **90 days**; encrypted backup rotation: **35 days**. Identified legal holds suspend deletion until recorded release. Apply authorized purge/irreversible anonymisation and retain minimal deletion receipts; a restored backup re-applies the deletion register. Do not retain all raw personal data merely because some financial records must be retained. The ten-year financial-record safeguard is consistent with [FIC's published recordkeeping guidance](https://www.fic.gov.rw/fileadmin/user_upload/FIC/Newsroom/Quick_Compliance_Information/KEY_AML-CFT_OBLIGATIONS_FOR_CONCERNED_STAKEHOLDERS.pdf); purpose-limited retention and any cross-border authority remain subject to [Rwanda DPO guidance](https://dpo.gov.rw/faqs). This is not blanket legal clearance or an instruction to delete existing records now.

Use the existing thin-native scope and the 6–10 Aminu / 9–15 Erastus developer-day estimate for MVP planning, with the previously described 2–3-week allowance. This records a planning assumption, not Erastus's personal availability or completed device/distribution evidence. No extra native implementation is started in this update.

### 11.6 Current closure and next boundary

CFG-01–CFG-05 are **RESOLVED_BY_MVP_DEFAULT**, and MC-01–MC-08 are adopted except for the explicit amendments above. There is no remaining Erastus specification-review gate and no repeat minor-policy approval request. Preserve original numerical/source-vector evidence as history; test the current policy against implementation in the applicable development phase rather than falsely re-labelling old vectors as a new independent review.

This is a changeable, versioned MVP build baseline, not production activation or a claim that every future edge case has been eliminated. Real reserve funding/custody/payout arrangements, provider/regulatory/professional evidence, actual native feasibility and #90 physical-device/dwell results remain separate. Tests, coverage and deployment integrity remain required for future code changes. Phase 1 application development, issue closure and deployment were not requested or performed in this update. A subsequent direct request authorizes committing, pushing and opening a PR to `dev`; it does not authorize merging or starting Phase 1.

Current fixture SHA-256: `c42815c865f452e76f63b369b374cf08823fb0fe1034569c505b35765b7a488a`.

### Local verification scope

Current MVP baseline engineering-2026-09-23.4, pre-publication verification: **334 tests, 2,305 assertions** pass across the engineering-contract, policy-review and delivery-readiness Pest suites with `--no-tia`, after fast-forwarding to `dev` at `67d5faf24cf895970d79662061cb65f755193902` (including PR #94's separate device-evidence work). Before that synchronization the same focused command passed 330 tests / 2,301 assertions. The 72 new specification cases cover current delegated authority/review waiver, all five resolved configuration groups, rejection of fabricated review/live evidence, RWF 5,000 quantization and the original-request cutoff, cyclic unit-component conservation, partial-receipt allocation, exact repeat projections, month-end due dates, investor limits, primary expiry/refund boundaries and separate capture/upload deadlines. Original reviewed sections and example collections retain their historical hashes. These are specification guards and test-only reference calculations, not implemented application behavior, independent review, executed races or physical-device proof.

The full local PHP suite passes **684 tests, 3,649 assertions, 100.0% application line coverage** via `composer test:php:coverage`. Full PHPStan, Pint and `git diff --check` pass. `vp install --frozen-lockfile --prefer-offline` leaves the tracked dependencies unchanged; `vp check` succeeds with 32 warnings in untouched files, and the web suite passes **329 tests in 34 files**, with two existing canvas-environment diagnostics. These local runs do not substitute for clean-checkout hosted checks on the eventual PR head SHA. The subsequent commit/push/PR request is the publication authority; the PR records its actual SHA and hosted status. No merge, Phase 0 exit, Phase 1 implementation or runtime activation is claimed here.

Prior completion candidate engineering-2026-09-23.3: **258 tests, 2,021 assertions** passed across the engineering-contract, policy-review and delivery-readiness Pest suites with `--no-tia`. Pint, targeted PHPStan and `git diff --check` passed. The 49 added cases cover user-reported approval scope, explicit configuration gaps, exact seasonal/negative CFADS, immutable unit-component conservation, servicing and primary-expiry boundaries, provider outcome examples and evidence-conflict semantics. Original section hashes and independently reviewed collections remain unchanged. These are test-only reference calculations and specification guards, not an application workflow engine, executed concurrency proof, independent review or operational evidence. Changes remain local and uncommitted; no hosted CI, Phase 0 exit or Phase 1 start is claimed.

Prior financial-default revision engineering-2026-09-23.2: **209 tests, 1,853 assertions** passed across the engineering-contract, policy-review and delivery-readiness Pest suites with `--no-tia`. Pint, targeted PHPStan and `git diff --check` passed. Added checks covered per-principal grace, normal/leap-year accrual, year transitions, cumulative posting, repayment/stopped segments, daily targets, payment ordering, backing sufficiency and rejection of invented approval/funding. These were specification checks, not runtime collection/settlement, financial calibration or funded-protection proof.

Prior defaults revision engineering-2026-09-23.1: **180 tests, 1,747 assertions** passed across the engineering-contract, policy-review and delivery-readiness Pest suites with `--no-tia`. Pint, targeted PHPStan and `git diff --check` passed. Added checks covered exact tenor premiums/final rounding, mean observation windows, retained projection constraints, synthetic hold-calendar dates and approval provenance. These were specification checks, not application implementation or credit-model validation; no hosted CI or independent revised-pack approval was claimed.

Local verification on 2026-09-22: **158 tests, 1,651 assertions** across the engineering-contract, policy-review and delivery-readiness Pest suites, run with `--no-tia`. Pint and targeted PHPStan pass. The web suite passes **329 tests in 34 files** with two canvas-environment diagnostics; `vp check` exits successfully with **32 warnings in untouched frontend/test/tooling files**. Its formatter excludes the documentation paths; JSON parsing, document/link checks and `git diff --check` are checked separately. The retained tests cover integer/rational arithmetic, cash/schedule conservation, source hashes, vector dispositions, links, owner-input records and historical/current section hashes. New checks cover bid partial releases, terminal-release conservation, minimum-fill selection and approval scope. Specification examples and planned scenarios are not runtime matching, client or PostgreSQL race proof. No full coverage collection, clean-install candidate CI, browser/native/host verification or independent revised-pack sign-off is claimed.
