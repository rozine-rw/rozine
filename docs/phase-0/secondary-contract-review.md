# D-26 / D-27 — secondary-market contract for owner review

**Status:** `PROPOSED — UNSIGNED — NOT IMPLEMENTED` · **Version:** review-2026-09-10.1

**Subsequent response, 2026-09-17:** See the [Robert/Kimani consolidated decision sheet](stakeholder-decision-sheet-2026-09-17.md) for captured partial-fill/fee choices, other decisions and remaining questions. The proposal and pending-status statements below are the retained 10 September snapshot, not the latest owner choices. Contracts, fees and historical fixtures are not changed or activated by the response sheet.

**Goal:** Give the Phase 1 Holding/Order contract freeze an explicit BRS-safe proposal covering orders, prices, disclosures, settlement, cutoff races and failure recovery. This is not a schema freeze, policy activation or authorization to ship a market.

## Recommended choices and non-negotiable boundary

Use the plan's recommended **D-26 Option A**: fixed-price, all-or-nothing orders over a seller-selected subset of fully settled eligible units. Use recommended **D-27 Option A**, subject to K/Robert and required external approval: seller-chosen positive whole-RWF unit asks with a disclosed contractual-cashflow cap. The cap is a policy eligibility ceiling, not a Rozine valuation, bid, liquidity commitment or firm exit quote. If Legal determines that the cap conflicts with BR-75's no-price-setting rule, amend this proposal before freeze; do not bypass the concern by relabelling it.

The governing [BRS](../Rozine-BRS.md) BR-70–75/FR-108/407 and approved D-60 require Investor-to-Investor secondary settlement in the MVP. No Rozine principal inventory, market making, proprietary orders, maker/taker/acquisition fee, reserve cover or guaranteed exit is allowed. Peer-to-peer trading of Arrears, Default or Disputed Notes is prohibited, not an alternative exit channel. The [MVP crosswalk](mvp-crosswalk.md) SEC-01–SEC-09 remains the delivery/evidence map.

Owners: **Kimani** Finance/Risk/Compliance; **Robert** Product/Business/internal Legal; **Aminu** core/API/schema/security/server tests; **Erastus** UI/client/browser evidence. Joint contract review and an eligible non-author reviewer are required. D-39 external legal/regulatory/CMA approval remains separate.

## Explicit candidate parameter sheet

Every value in this table is **proposed**, except where the BRS already fixes it. The signature column is pending for every proposed choice.

| ID | Proposed value / rule | Authority / trade-off |
|---|---|---|
| SEC-R01 | All-or-nothing fill; subset size chosen at order creation; no partial fills or order book matching engine | D-26 Option A. Simpler atomic settlement, but less fill flexibility than partial orders |
| SEC-R02 | Order expires 7 × 24 hours after server creation; no silent extension | Existing D-26 candidate, still unsigned. Seller may explicitly cancel/relist under current eligibility |
| SEC-R03 | Buyer reservation expires after 5 minutes; funding must already be settled in the wallet | Existing D-26 candidate, still unsigned. No bank/MoMo payment is awaited inside the trade |
| SEC-R04 | Seller ask: positive whole RWF per unit; total ask ≤ allocated remaining contractual gross cashflows; no reference-price quote | D-27 Option A, Finance/Legal approval needed; future cashflows are not guaranteed |
| SEC-R05 | Exactly 3% of settled gross consideration charged to seller, rounded half-up once per trade; no buyer acquisition fee | BR-62/64 and FR-700. Existing investor 1% fees still apply to future actual Note payouts |
| SEC-R06 | Apply the BRS RWF 5,000 investment minimum to a secondary purchase unless a signed BRS/product/legal disposition narrows it | BR-27/D-10 interpretation requires explicit sign-off; see small-holding constraint below |
| SEC-R07 | Freeze holder-of-record at a published cutoff marker at 00:00 Africa/Kigali on each contractual due date | Candidate record-date convention; D-23 schedule/calendar still required. Delayed payout does not move entitlement |
| SEC-R08 | Halt or eligibility loss terminates affected open/reserved orders as Suspended; no automatic reactivation | Conservatively revalidate and require explicit seller relisting after authorized clearance |
| SEC-R09 | Price change requires cancel/recreate while Open; cancellation while Reserved is rejected and explains the reservation | Preserves immutable consent/price. Timeout or buyer release can return the still-valid order to Open |
| SEC-R10 | Self/same-beneficial-owner trading blocked. Candidate review-only flags: reciprocal owner-pair trades within 30 days; ≥3 cancels/relist actions in 1 hour; ≥5 order creations in 10 minutes; ask below 50% of remaining contractual gross | Thresholds are proposed surveillance seeds, not calibrated fraud rules. Create cases only; no automatic default, confiscation or customer penalty |

### Small-holding constraint requiring an explicit decision

If remaining contractual cashflows for a holding are RWF 3,000, SEC-R04 caps its ask at RWF 3,000, while SEC-R06 requires a buyer ticket of at least RWF 5,000. No price can satisfy both. Consequently a previously valid RWF 5,000 primary position may become **unlistable near maturity** under this combined policy.

Recommended current-BRS treatment: deny the undersized secondary order, explain why, and retain the hold-to-maturity/payout path. A seller may combine **their own eligible units of the same Note** if that genuinely reaches the approved minimum; do not invent cross-Note bundles or new credit. If Product requires every residual holding to be sellable, it must explicitly approve a different minimum interpretation/exception and any necessary BRS/legal amendment. It cannot be resolved by hiding the cashflow cap, adding a Rozine buyback, overriding fees or promising liquidity. Unit size/residual allocation still requires D-10; examples do not select a universal RWF 5,000 unit.

## Proposed domain contracts — no migrations created

| Contract | Facts that must be frozen after the decisions |
|---|---|
| Holding / lot | Note, beneficial owner/Party, immutable originating settlement, units acquired/disposed, available vs encumbered units, revision, and separately vested payout entitlements; authoritative balance derived from durable unit movements |
| Order | Seller/Note/eligible lot allocation, units, whole-RWF unit ask and total, disclosure/policy/fee versions, lifecycle/revision, created/expiry UTC, reason/correlation, and immutable snapshots sufficient for replay |
| Reservation | Order, eligible buyer/beneficial owner, exact encumbered units/cash, server expiry, original idempotency key/body hash and revision; one effective active reservation per order |
| Trade | Unique Order/reservation settlement, seller/buyer, transferred units, gross/fee/net, ledger batch, unit-movement batch, cutoff/entitlement version, server commit ordering and event/outbox IDs |
| Record-date entitlement | Per-Note scheduled-payout ID, published cutoff, serialized cutoff marker/high-water sequence, entitled holder units, exact allocation/remainder rule and immutable payout state |
| Market halt / policy / surveillance case | Global/per-Note scope, active revision, authority/reason/effective time, review/release record and related immutable events; cases do not silently mutate historical cash or units |

Do not store mutable display balances as authoritative Holding/wallet truth. Do not cache a rating, price eligibility, risk or schedule in a client as approval to settle. Resource contracts expose authorized current facts, state versions, exact money, causes and allowed commands; they never calculate or write the domain result.

## State and command matrix

This matrix is a proposed contract. A policy owner must approve terminal-state and reservation behavior before schemas are frozen.

| From | Command / condition | To | Cash/unit consequence and recovery |
|---|---|---|---|
| No Order | Create after seller/holding/price/disclosure validation | Open | Encumber exactly the selected units against double listing; wallet cash unchanged |
| Open | Reserve by eligible distinct buyer with sufficient settled wallet funds | Reserved | Atomically encumber buyer gross cash and this order's units; duplicate requests return the same reservation |
| Reserved | Confirm while reservation/order valid and all current gates still pass | Settled | One atomic transaction transfers cash/units, posts seller fee and both histories/outbox; no partial effect |
| Reserved | Buyer releases or reservation times out while order still eligible/unexpired | Open | Release buyer cash exactly once; seller's order allocation remains encumbered for the still-open order |
| Open or Reserved | Order's 7-day expiry wins serialization | Expired | Release all remaining buyer reservation and seller order encumbrances once; no cancellation/expiry fee |
| Open | Seller cancel | Cancelled | Release seller allocation once; retain order history |
| Open or Reserved | Authorized Admin cancellation with reason | AdminCancelled | Release uncommitted cash/units once; already settled trade cannot be cancelled by this command |
| Open or Reserved | Global/per-Note halt or authoritative eligibility loss | Suspended | Stop confirmation, release encumbrances once; no automatic resume; seller may create a fresh order after lawful clearance |
| Terminal | Replay identical command | Same terminal state | Return recorded outcome; no duplicate fee, credit, unit movement or outbox event |
| Any | Wrong actor, stale body/version, conflicting idempotency reuse, expired credential, forbidden transition | No change | Stable typed denial and a safe inspect/retry/re-authenticate action; no raw state write |

At every gate, check current beneficial ownership, KYC/tier/limits/related-party permissions, Note Repaying/current status, settlement finality, disputes/freezes, licence/report visibility required by the disclosure contract, global/per-Note halt, available units/cash, expiry, policy/disclosure revision and self-trade rules. No `status=Repaying` cache is enough if an authoritative restriction has since arrived. D-05/D-15/D-18/D-22/D-23/D-25/D-64 must resolve any shared-policy ambiguity before acceptance.

## Atomicity, idempotency and race contract

- All money and unit writes, Order/reservation state, fee allocation and durable events/outbox commit together in PostgreSQL or not at all. No external provider call belongs inside this settlement transaction. Redis/client locks alone cannot certify the invariants.
- Serialize create/reserve/settle/cancel/expiry/halt/cutoff against the affected authoritative records in one documented deterministic lock order. Hold a compatible global-market gate lock plus Note gate and sorted ownership/wallet/lot locks; global/per-Note halt uses the conflicting lock before advancing its revision. Engineering must prove deadlock handling and bounded retries with real concurrent connections before selecting the final implementation.
- Recheck all time-sensitive facts under those locks. Concurrent final-unit buyers have one winner; no unit can be simultaneously allocated to multiple open orders. Reservation expiry and settlement have one serialized legal outcome, never both success and refund.
- Idempotency is scoped to verified actor, command and key, with canonical request-body hash and retained result. Same key/body replays; changed body is a conflict. Financial idempotency retention follows approved D-40 record retention, not a guessed short TTL.
- After a crash with uncertain commit, query the immutable trade/ledger correlation before retrying. A client timeout is not a failed trade, and a provider deposit reversal cannot silently unwind a settled secondary transaction. Such a reversal enters the approved wallet/incident workflow.
- Post-settlement correction is a separately authorized compensating process preserving both original cash and unit history. It cannot be implemented as deleting the trade or resurrecting its Order.
- Surveillance records and reports must be attributable, privacy-scoped and replayable. Candidate SEC-R10 thresholds create review cases only; authenticated core controls, not a risk-label shortcut, govern restrictions.

## Price, fee and disclosure contract

Before seller creation and buyer confirmation, show Note/current rating/health, latest permitted verified report, remaining payout schedule, selected units, contractual outstanding principal, remaining **unvested** contractual gross cashflows, total seller ask, premium/discount, seller's exact 3% fee/net proceeds, buyer's cash debit, and capital-loss/illiquidity risk. Clearly distinguish ask from expected value or a firm platform quote. Scheduled contractual receipts are not guaranteed realised return.

The seller's 3% fee is on gross settled trade value; no fee is charged on listing, reservation, cancellation or expiry of this secondary Order. Other primary-market fees remain separately governed; this does not waive the BRS Business listing fee. For `gross=10,050`, seller fee is `round_half_up(301.5)=302`, seller net is `9,748`, and buyer debit is `10,050`. A rounding-to-zero seller fee on a hypothetical tiny amount does not bypass the RWF 5,000 purchase gate.

For `u` selected units, an integer unit ask `p` is permitted only if `p>0` and `p×u≤remaining_contractual_gross_for_selected_units`. Use the authoritative remaining allocation for this exact lot, not a rounded average cashflow per unit multiplied back. The buyer must see all future ordinary payout fees separately; do not invent a net ROI without the actual remaining schedule and entitlement allocation.

## Holder-of-record and cutoff boundary

The proposed cutoff is published with the contractual schedule. A settlement before the cutoff transfers entitlement to future, not-yet-vested scheduled payouts; already vested entitlements remain with their recorded holder even if cash is paid late. For the due-date boundary, a serialized cutoff marker fixes the entitled-holder snapshot. Settlement at/after the boundary receives no entitlement to that due-date payout. Database commit ordering under the shared Note gate, not client time, upload time or provider value date, resolves the race; Engineering must document a precise marker/timestamp implementation before freeze.

Do not sell a matured or fully paid holding; its history remains visible. Failed payout, holiday shifts, partial repayment, overdue status and due-date changes must use D-23/D-25-approved rules. A payout arriving later must neither move the cutoff nor let both seller and buyer collect the same instalment. The fee/ask cap is recomputed from remaining **unvested** cashflows after any cutoff, not from all unpaid receivables indiscriminately.

## Required acceptance scenarios before D-26/D-27 can close

These are future implementation tests, not claimed passes from the arithmetic review pack.

| ID | Required scenario / invariant | Owner and evidence |
|---|---|---|
| SEC-T01 | Fully settled/current/owned eligibility; deny Arrears/Default/Disputed/frozen/unsettled/self/same-owner/over-limit | Aminu core denial matrix; Erastus UI cause/next-action journey; K witness |
| SEC-T02 | Ask below/at/above contractual cap; valid/invalid unit divisibility; exactly 5,000 and undersized residual | Aminu exact-money tests; Erastus disclosure; K/R sign minimum/cap interaction |
| SEC-T03 | Fees at half-franc boundary, once per settlement; no maker/taker/acquisition/cancel/expiry fee | Aminu ledger conservation and replay; K independent reconciliation |
| SEC-T04 | Duplicate create/reserve/confirm; same key changed body; replay after crash/timeout | Aminu idempotency/outbox tests; Erastus uncertain-state recovery |
| SEC-T05 | Two concurrent buyers; duplicate lot listings; concurrent sell vs primary/other reservation | Aminu PostgreSQL multi-connection races; non-author review |
| SEC-T06 | Reserved confirm vs timeout/order expiry/cancel/admin cancel; one legal terminal result | Aminu race/fault injection; Erastus permitted actions; K witness |
| SEC-T07 | Inject failure at every cash/fee/unit/event write; transaction rolls back or exact completed trade is discoverable | Aminu PostgreSQL rollback/recovery proof; K reconciles both cash and units |
| SEC-T08 | Global/per-Note halt or eligibility loss races with settlement; no effect after losing gate | Aminu lock-order/halt proof; independent Treasury/Compliance exercise |
| SEC-T09 | Settlement before/at/after cutoff; delayed/partial payout; changing owner cannot duplicate entitlement | Aminu cutoff/allocation tests; K schedule/ledger replay |
| SEC-T10 | Cancel/relist price revision, stale disclosure/fee policy, buyer no longer authorized | Aminu policy/revision tests; Erastus exact consent and stale-state UI |
| SEC-T11 | Review-only surveillance cases, privacy limits and authorized resolution; flags do not auto-default or alter history | Aminu/K case-policy matrix; Erastus authorized Admin walkthrough |
| SEC-T12 | Trace buyer/seller wallet→trade→unit movements→fees→record date→subsequent payout and regulatory export | Aminu reconciliation/export tests; Erastus workflow; K/R independent witnessed proof |

## Sign-off checklist and remaining authority

- [x] Recommend explicit D-26/D-27 options, candidate parameters, lifecycle, atomicity, pricing/fee/disclosure, cutoff and recovery behavior.
- [x] Expose the minimum-ticket/remaining-cashflow conflict and the required shared-policy dependencies.
- [x] Prepare synthetic arithmetic boundary examples in [policy-review-fixtures.json](policy-review-fixtures.json) and preserve planned integration scenarios separately.
- [ ] K/R approve or amend SEC-R01–SEC-R10; settle D-10/minimum interpretation and D-23/cutoff dependencies, and obtain required external review of the pricing/secondary structure.
- [ ] A/E jointly freeze the actual Holding/Order/reservation/fee/entitlement/halt/Resource schemas and approved versions after those decisions; no schema freeze is asserted here.
- [ ] Assign a named non-author independent reviewer and record actual signatures, effective time and policy/version hashes.
- [ ] Implement and pass SEC-T01–SEC-T12 with real PostgreSQL concurrency plus the plan's full PHP/client/static/architecture and witnessed gates.

Until then, D-26 and D-27 remain **DECISION PENDING**, D-60 remains mandatory MVP scope, and no financial parameter in this review is activated.
