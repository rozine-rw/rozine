# #383 — Atomic whole-cart primary checkout

Proposed contract version: `cart-checkout-1` · 2026-10-28 · server owner: Aminu / Codex.

Documentation only: this PR changes no runtime behavior, permissions, settings, live records or financial activation. The contract was independently reviewed against `rozine-rw/rozine@52a68e22c028060448efaf7c45e8c3372bc7a9de`, tree `1351a5033335fa5b90b38d26704b67353685bc64`. Unless stated otherwise, source paths and line references below refer to that pinned revision.

Publication refresh, 2026-10-29: base `dev` is `e8af42fbf824bc44239bd7d4bc86a406b0667586`. The inspected delta includes #359 refusal copy/tests, local app-entry/assets and the #432 staff-permission refactor. The latter resolves role-backed current staff grants through the existing authorization boundary; `audit.assignments.manage`, current identity/mandate checks and the relevant assignment/statement contracts remain applicable. These source reads do not certify the refactor or runtime behavior. No new product policy was adopted. [Erastus confirmed UI ownership](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6502081931); the proposal register below remains unresolved.

## 0. Decision status: approved direction versus proposals

The owner-approved requirements are atomic whole-cart reserve/confirm, one server-calculated band on outstanding held-note principal plus the entire checkout, the stated exclusions, and the server/UI ownership boundary. Documented rates and existing financial/access invariants remain in force. Action names, payloads, storage and coordination mechanisms below are proposed engineering contracts for review. They are not evidence that product defaults were separately approved or that runtime implementation is authorized.

| Proposal | Explicit unresolved choice |
|---|---|
| P383-01 | One live aggregate hold per Party is a proposed duplicate-checkout strategy. Multiple revision-bound batches would need an equally safe alternative; no new investment limit is approved here. |
| P383-02 | Blocking cart edits while a batch is held, and refusing adoption of conflicting standalone holds, are proposed user-flow choices. A versioned editable-cart alternative must preserve exact snapshot/child ownership and prevent stale deletion or double purchase. |
| P383-03 | Reusing the existing 300-second reservation horizon as a quote TTL is proposed. The current source establishes a hold window, not a quote TTL. |
| P383-04 | One shared hold deadline capped by the earliest campaign expiry, without extension on re-quote, is proposed aggregate lifecycle behavior using existing per-line limits. It must not extend any existing permitted hold. |
| P383-05 | Separate group expiry settlement and a visible `release_pending` state are proposed. The release trigger, recovery path and acceptable settlement delay are unresolved; do not enable this path while funds could remain stranded. Group release must remain atomic and must not depend on restored Investor app access. |
| P383-06 | Applying the corrected basis to future standalone checkout and every other fee-pricing consumer is a proposed consistency scope. Inventory and agree the affected consumers before implementation. Historical investments and non-fee exposure definitions are excluded. |
| P383-07 | Suppressing a new consent solely for a nonfinancial provenance revision is proposed. It never permits a change to acknowledged disclosure content, policy, price, rate, rights or totals without explicit reconfirmation. |

Sections illustrating these choices and the corresponding acceptance scenarios are conditional on their resolution. Unresolved choices must not silently become implementation defaults. Exact outstanding-capital authority and per-child operation-evidence representation are technical prerequisites still requiring design/proof; no values or authority may be inferred to fill them.

## 1. Governing decisions and ownership

- [Issue #383](https://github.com/rozine-rw/rozine/issues/383), INV-R03/R04: reserve and confirm every cart line atomically. A failing line never produces a partial purchase.
- [Owner direction 6466800280](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6466800280) supersedes the recommendation-only hold: one server-calculated earnings-fee band from **outstanding principal in held notes + the whole checkout principal**. Exclude wallet cash, reservations and confirmed-but-unissued purchases.
- [BRS v2 reconciliation 6144181964](https://github.com/rozine-rw/rozine/issues/99#issuecomment-6144181964), checked against [versioned Investor terms §§5–6](https://github.com/rozine-rw/rozine/blob/52a68e22c028060448efaf7c45e8c3372bc7a9de/docs/legal/investor-terms-2026-10-13.md): all lines lock the same rate for the investment's life; fee is on return only. Changed financial terms require explicit reconfirmation. Confirmed commitments are final; failed-raise return remains separate.
- Peer [6466716552](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6466716552) proposes `reserveAll`/`confirmAll` and claims the cart UI. It explicitly leaves server work with Aminu. This artifact keeps those action names but defines the missing quote and acknowledgement fields. No UI files are changed here. [Erastus confirmed cart UI ownership](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6502081931); this confirms the UI lane, not acceptance of every proposal below.
- #359/PR429 figure-free refusal copy, #396, servicing, payout, failed closing and restricted #336 work are outside this contract's implementation scope.

## 2. Invariants

1. A quote includes the **entire saved cart**, including a closed/unavailable line as a refusal. Never silently skip invalid lines, shrink quantities or choose a valid subset.
2. Reserve either retains all child reservations, ordinal claims and wallet holds, or retains none. Confirm either retains all child commitments, cash postings, cart removals, notices and change-feed events, or retains none. A rejection receipt alone is permitted. An unexpected integrity/storage error rolls back the receipt and all effects.
3. The amount held/committed is the exact sum of line principal. Preserve existing `UnitRights` ordinal allocation, return allocation, exact RWF rounding, exposure, wallet restrictions, concentration, known-connection and authenticated campaign-source requirements. Band exclusions do **not** exclude held/committed positions from concentration or other risk limits.
4. Each reserved line has its own immutable rights, disclosure and child root. All children reference one aggregate checkout and one common band snapshot. Lines may have different contractual returns/tenors; their earnings-fee **rate and policy** must match.
5. Current authenticated Investor authority is required before reads, writes, retries and receipt lookup. The server derives user/Party from the session or bearer token. No actor/Party, rate, principal, eligibility or wallet identity comes from request assertions.
6. Confirmation cannot proceed under unseen terms. Aggregate acknowledgement covers every line disclosure, financial totals, band policy and rate. Replaying an old acknowledgement cannot accept a replacement quote.

Current KYC-R05 risk acknowledgements, current legal/disclosure acceptance, wallet eligibility and every existing source/admission refusal remain required. This contract changes the fee basis and aggregation boundary; it grants no exception to those prerequisites.

## 3. Verified rate table and capital basis

| Tier | Inclusive floor (RWF) | Exclusive ceiling (RWF) | Rate bps | Display percent |
|---|---:|---:|---:|---:|
| standard | 0 | 1000000 | 1500 | 15 |
| bronze | 1000000 | 5000000 | 1200 | 12 |
| silver | 5000000 | 25000000 | 1000 | 10 |
| gold | 25000000 | 100000000 | 900 | 9 |
| platinum | 100000000 | null | 800 | 8 |

These are existing documented thresholds, not new policy. `EarningsFeeBand::POLICY_VERSION = brs-2.0-inv-r04`; `basis = return_only`. Integer basis points are canonical; `rate_pct` is a server display string, not a client calculation.

`H = authenticated outstanding principal of notes currently held by this Party`.
`C = Σ exact proposed line principal`.
`B = H + C`; select once at reserve, and again once during confirmation's revalidation. Reservations, wallet buckets (including `investor_committed`), pending purchases, contractual return and fees contribute **zero** to H. Do not subtract the cart hold from H; it was never included.

`next_band_at` is the next strictly higher floor; `next_band_distance = max(0, next_band_at - B)`. Both are null in platinum. Return H, C and B separately so “active capital” cannot be confused with wallet balance or pre-checkout capital.

**Proposed scope P383-06:** use the corrected held-note basis for future standalone one-line checkout (`C` is then that checkout's total), after inventorying and agreeing the affected fee consumers. Do not silently broaden this task into unrelated consumer rewrites. Existing risk/concentration consumers keep their own exposure definitions. Batch admission receives the one precomputed common fee snapshot and builds each line's `PrimaryTerms::disclosed` with that snapshot and its own `payoutFeeFor`; it must not call today's per-line `CurrentPrimaryAdmission::terms` repeatedly.

**Historical fee invariance:** a confirmed investment keeps its exact original fee tier/rate, policy version, disclosure/hash, rights and retained receipts, including history created under the previous capital calculation. Never recalculate, re-hash, migrate, backfill or overwrite those snapshots using this correction or current capital. Current pricing applies only to a new quote or an unconfirmed held re-quote with explicit reconfirmation. Replay/lookup, issuance, servicing, refund and historical read paths consume their authenticated original terms; they do not run new admission to reconstruct fees. Any future transfer treatment remains its separately governed contract, not permission to rewrite the original locked investment.

New internal fact seam: `HeldNoteCapital::lockCurrent(partyId) -> {outstanding_principal, revision, sha256, complete, as_of}` inside the shared wallet/capital serialization boundary. It must authenticate current holding ownership and principal reductions, and serialize with issuance, transfers and repayment principal changes. The existing `RetainedInvestorCheckoutFacts` sum of original holding principal is usable only with positively established no-principal-reduction/no-transfer lifecycle coverage; absence of a producer is not proof of zero activity. Missing/incomplete authority refuses `POLICY_INPUT_REQUIRED`; never fall back to wallet, seeded or browser totals. Enabling servicing/secondary later requires this seam's verified implementation before those writers can affect pricing.

## 4. Types and common transport rules

IDs: lowercase ULIDs; request IDs: UUIDs; SHA-256: 64 lowercase hex; revisions: nonnegative integers (positive for persisted quotes/holds). Money/units: canonical decimal strings within the existing domain's 64-digit money bound, no floats, exponents, commas, signs or leading zeros; positive line units. RWF only. Cart limit remains `InvestorCart::MAX_LINES = 50`.

Canonical order is campaign ID ascending, then cart-line ID; one line per campaign. Preserve display order separately. Hash with existing `CanonicalJson`, not arbitrary JSON serialization. Unknown command fields, duplicated line/campaign IDs, duplicate acknowledgement entries, partial line lists and mismatched route/body targets are 422 validation failures. Compute each line's fee with existing `PrimaryTerms::payoutFeeFor`: half-up per return instalment, then sum across instalments/lines. Do not round one fee over combined cart return. `net_return` means contractual return less scheduled earnings fee, excluding principal; projected total received equals principal plus net return and remains subject to the existing collection/risk disclosure.

```text
Money = {currency: "RWF", amount: decimalString}
FeeBand = {
  tier: standard|bronze|silver|gold|platinum, rate_bps: integer,
  rate_pct: decimalString, basis: "return_only", policy_version: "brs-2.0-inv-r04",
  held_note_principal: Money, checkout_total: Money, active_capital_after_checkout: Money,
  next_band_at: Money|null, next_band_distance: Money|null,
  capital_revision: integer, capital_sha256: sha256
}
LineQuote = {
  cart_line_id: ulid, campaign_id: ulid, units: decimalString,
  campaign_revision: integer, publication_sha256: sha256,
  principal: Money, rights: UnitRights.toArray(), ordinal_ranges: existingRanges,
  terms: PrimaryTerms.toArray(), disclosure_version: string, disclosure_sha256: sha256,
  earnings_fee: Money, net_return: Money
}
Quote = {
  id: ulid, revision: integer, contract_version: "cart-checkout-1",
  cart_id: ulid, cart_revision: integer, cart_sha256: sha256,
  lines: LineQuote[], principal: Money, earnings_fee: Money, net_return: Money,
  fee_band: FeeBand, disclosure_sha256: sha256,
  created_at: utcTimestamp, expires_at: utcTimestamp, confirmation_required: true
}
```

`Quote.disclosure_sha256` hashes a versioned canonical object containing cart ID/revision/hash, ordered campaign/publication identities, units, ordinal rights, all per-line disclosures, exact totals and common financial band fields/policy. Capital revision/hash are retained for revalidation but a nonfinancial revision bump alone must not pretend the terms changed. Bind any other acknowledgement-bearing disclosure/policy versions exactly. No private connection/other-investor facts appear in the quote.

Mutations use the existing `OperationResource` envelope: `operation_id`, `status` (`completed|rejected`), `code`, `data`, `revision`, `policy_version`, `server_time`, `recorded_at`, `allowed_actions`, `field_errors`, `errors`. `server_time` is fresh presentation time; `recorded_at` is retained operation time. HTTP 200 is not proof of a purchase: inspect `code`. A re-quote is completed work with **zero purchases**.

Web and `/api/v1` transports invoke the same application actions. Web uses authenticated session + CSRF. API reads/lookup require existing `investor:read`. Every API mutation requires **both `investor:read` and `investor:command`**, preserving the existing primary command guard, plus current Investor role, verified Party, context revision and normal wallet restrictions. The proposed quote POST persists a quote record and also requires both abilities. Token ability never substitutes for actor authority. Private/no-store responses and existing throttling apply. Proposed paths below are reserved design, not registered endpoints.

## 5. Operations

| Action / proposed route (API prefix `/api/v1`) | Request | Response / effects |
|---|---|---|
| `GetInvestorCart` · GET `/investor/cart` | current context | Existing lines + `cart_id`, monotonic `cart_revision`, `cart_sha256`, count of deals/units, active checkout reference. Projection is advisory; no reservation. Never advertise a computed band if capital input is unavailable. |
| `QuoteInvestorCart` · POST `/investor/cart/quote` | `{identity_context_revision, expected_cart_revision}` | `CART_QUOTED`, `Quote`, or whole-cart refusal. Persists immutable quote metadata only; no units/cash. Terms shown by the header come from this server quote. |
| `ManageInvestorCart::reserveAll` · POST `/investor/cart/reservations` | `{request_id, identity_context_revision, expected_cart_revision, quote_id, expected_quote_revision, quote_disclosure_sha256}` | `CART_RESERVED`, aggregate hold below; or `CART_QUOTE_STALE`/refusal with no holds. Never reprices and reserves silently. |
| `GetCartReservation` · GET `/investor/cart/reservations/{checkout}` | current context | Aggregate current state/revision, retained quote, child states, `expires_at`, confirmation actions; only own checkout. |
| `ManageInvestorCart::confirmAll` · POST `/investor/cart/reservations/{checkout}/confirm` | `{request_id, identity_context_revision, expected_revision, quote_id, expected_quote_revision, acknowledged_disclosure_sha256}` | `CART_CONFIRMED`, `CART_REQUOTED`, or whole-cart refusal. No client line amounts/fees. |
| `ReleaseCartReservation` · POST `/investor/cart/reservations/{checkout}/release` | `{request_id, identity_context_revision, expected_revision}` | `CART_RELEASED`: return all held principal/claims atomically; no fee; does not cancel confirmed commitments. |
| `FindCartCheckoutOperation` · GET `/investor/cart/operations/{request_id}` | `{identity_context_revision, command, cart_id, checkout_id?}` | Exact authorized receipt; command whitelist `cart.reserve`, `cart.confirm`, `cart.release`. Reserve targets cart; confirm/release target checkout. Wrong owner/target/unknown = `OPERATION_NOT_FOUND`. |
| `ExpireCartReservation` · internal scheduler only | retained checkout ID, server time | Aggregate terminal expiry and return of all child holds/claims once. No new HTTP scheduler authority. |

`CartReservation = {id, cart_id, cart_revision, revision, state, quote: Quote, expires_at, lines:[{cart_line_id,campaign_id,reservation_id,reservation_revision,principal,disclosure_sha256}], total_held: Money}`.

`CART_CONFIRMED.data = {checkout_id, quote_id, disclosure_sha256, fee_band, confirmed_at, total_principal, lines:[{cart_line_id,campaign_id,reservation_id,commitment_id,entry_id,principal,fee_rate_bps,disclosure_sha256}], cart:{revision,count,removed_line_ids}}`. Retain parent/child operation references. Receipt amounts/rates must agree with authenticated persisted evidence.

Quote retries may create a fresh immutable quote ID; no funds/claims exist and a quote is never treated as a command receipt or purchase. A new quote does not silently replace an acknowledged held quote. Reserve/confirm/release require stable idempotency keys as specified below.

## 6. State, revisions and re-quote

Cart receives a stable root and monotonic revision. Every actual add/change/remove/bought mutation increments it; no-op mutation need not. Persist a canonical line snapshot hash. Current code has no cart root/revision. Quote, hold and line revisions are distinct; do not reuse `identity_context_revision` for any of them.

**Proposed flow P383-01/P383-02:** at most one live aggregate hold per Party; while it exists, cart add/change/remove refuses `CART_CHECKOUT_ACTIVE` and the user releases or waits for expiry before editing. Conflicting standalone holds refuse `CART_EXISTING_RESERVATION` with an authorized reference rather than being silently adopted. These are reviewable choices, not approved limits. Whichever flow is selected, single-line commands and lifecycle workers cannot independently mutate a batch child or remove its snapshot line; guard the shared seam.

```text
saved cart -> quoted -> held(r1) -> confirmed(terminal, every child confirmed)
                                -> held(r+1, replacement quote, awaiting explicit confirm)
                                -> released(terminal, every child released)
                                -> expired(terminal, every child expired)
```

**Proposed timing P383-03/P383-04:** quote expiry reuses the existing primary hold horizon (`ReservationWindow`, maximum 300 seconds), capped by the earliest cart campaign expiry. The source does not establish a quote TTL. The proposed hold starts only at successful reserve, with **one shared expiry** = min(reserve time + existing 300-second window, every campaign expiry); all children use that deadline and re-quote does not extend it. These aggregate timing choices need resolution before implementation. Existing exact-at-expiry refusal and per-line maximum windows cannot be weakened. Quote expiry and hold expiry are distinct.

Reserve rechecks cart revision/hash, quote ownership, expiry, capital/financial policy and exact rights/publication disclosures. Changed displayed terms yield `CART_QUOTE_STALE` with `replacement_quote` where calculable and `requires_reconfirmation: true`. No cash is held; the UI must show it before a new reserve request with a fresh request ID.

For a new command, confirm rechecks all current admission/integrity requirements, common band and per-line terms under the locks. If still admissible but any acknowledged financial/disclosure term changes, append one replacement quote and all necessary child term versions atomically, increment aggregate revision once, keep all children held and return `CART_REQUOTED` with replacement quote, changed fields and `requires_reconfirmation: true`. No commitment, cash conversion or cart deletion occurs. UI displays the entire refreshed total/band and uses a **new request ID, revision and aggregate acknowledgement** on the next explicit Confirm. Replaying the first command returns its old re-quote; it cannot confirm the new terms. P383-07 proposes that an opaque provenance-only revision with identical acknowledged content need not trigger financial consent; that exception is not settled policy.

A line no longer admissible cannot convert any other line. **Proposed expiry behavior P383-05:** rejected confirmation creates no financial mutation; a separate idempotent group transaction settles expiry and the response shows `release_pending` until settlement. This differs from current single-line `expireRejected` and is not an approved delay or an established scheduler guarantee. Trigger, delay bound and recovery must be settled before enabling it; alternatively define an equally atomic group settlement contract. Never release/expire only one batch child. Group cleanup must remain available even after loss of Investor app access, authenticate original hold evidence, and admit no new investment. Confirmed state is final; no aggregate cancellation endpoint is introduced.

## 7. Refusal contract

```text
data = {
  cart_id, checkout_id?, cart_revision, atomic: true, financial_effect: "none",
  line_refusals: [{cart_line_id,campaign_id,code,http_status,field_errors,max_units?}],
  cart_refusals: [{code,field_errors}],
  replacement_quote?: Quote, requires_reconfirmation?: true
}
```

Use existing domain codes verbatim inside `line_refusals`: e.g. `CAMPAIGN_NOT_FOUND`, `CAMPAIGN_CLOSED`, `CAMPAIGN_FUNDED`, `UNITS_UNAVAILABLE`, `INVESTOR_CAMPAIGN_CAP_EXCEEDED`, `CONNECTION_ASSESSMENT_INCOMPLETE`, `CONNECTED_BUSINESS_INVESTMENT_PROHIBITED`, `POLICY_INPUT_REQUIRED`. These are not the peer proposal's hypothetical `CAMPAIGN_FULL`/`CONCENTRATION_LIMIT`. Do not disclose linked Party IDs or private risk facts. Collect all safely evaluable line refusals in canonical order; if a shared failure prevents evaluation, return it in `cart_refusals` and do not fabricate line outcomes.

| Condition | HTTP / aggregate code | Required next step |
|---|---|---|
| Empty or malformed cart, invalid units | 422 / `CART_EMPTY` or validation | Correct saved cart |
| Current role/context/identity denial | 403 or existing identity status/code | Refresh identity/resolve existing authority; no receipt exposure |
| Changed cart | 409 / `CART_REVISION_CONFLICT` | Reload cart, obtain quote |
| One or more line admission failures | 409 / `CART_LINES_REFUSED` | Show line details, edit only after releasing a live batch |
| Insufficient aggregate available cash | 422 / existing `INSUFFICIENT_AVAILABLE_FUNDS` | Return required total/own available amount only; zero new holds |
| Stale/expired quote | 409 / `CART_QUOTE_STALE` or `CART_QUOTE_EXPIRED` | Show fresh quote and require new acknowledgement |
| Stale aggregate/child revision | 409 / `VERSION_CONFLICT` | Refresh current batch; no auto retry with new terms |
| Elapsed batch | 409 / `CART_RESERVATION_EXPIRED` | Reconcile aggregate expiry, quote/reserve again |
| Released/confirmed/other terminal batch | 409 / `CART_RESERVATION_NOT_HELD` | Read retained result; no second purchase |
| Reused request ID, different canonical input | 409 / `IDEMPOTENCY_CONFLICT` | Original command remains authoritative |
| Foreign/unknown quote or batch | 404 / `CART_QUOTE_NOT_FOUND` or `CART_RESERVATION_NOT_FOUND` | No existence leak |
| Integrity failure, database failure | safe 5xx / existing integrity handling | Roll back, lookup same request before retry; never report a financial refusal as success |

New aggregate codes above are proposed additions for the reviewed design, not current API guarantees or settled product defaults. Existing child codes are inherited; UI maps both, never uses exception text as business copy. Global structural validation may precede journaling and have null `operation_id`; business refusals after authority/target validation are retained in the operation journal.

## 8. Transaction and concurrency design

Do **not** loop over HTTP endpoints or public `PrimaryCheckout::reserve/confirm` methods: each owns a journal and transaction, can catch its own rejection, can re-quote one child, and can acquire later Business locks after wallet locks. An outer transaction around that loop is insufficient if rejected child receipts are treated as success.

Proposed engineering boundary: a cart coordinator has one externally idempotent parent `OperationJournal::execute` and a batch-aware internal Primary collaborator. All child effects run inside its single effect savepoint; collect preflight failures before effects. Lift reusable allocation/admission/posting internals without bypassing evidence authentication. A child rejection escapes to the parent and rolls back all child effects. Internal child evidence described below is not an independently executable checkout or a partially committable child journal.

**Required schema compatibility discovered in independent review:** existing reservation roots uniquely bind `origin_operation_id`, versions uniquely bind non-null `operation_id`, and commitments uniquely bind `operation_id` (`2026_09_28_143756_create_primary_reservation_records.php:22,40,52`). Actor/target and completed-outcome triggers require the existing `primary.reserve`/`primary.confirm`/`primary.release` child command evidence; confirmation receipt triggers bind exact child results in both directions (`2026_09_28_152823_bind_primary_evidence_to_command_actors.php`, `2026_09_28_163057_require_completed_primary_command_outcomes.php`, `2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php`, `2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php`, all under `database/migrations/`). Passing one parent operation ID to all children cannot satisfy this schema.

The batch adapter therefore needs **distinct authenticated internal child command/receipt identities**, immutable parent + line + phase links, and complete-set binding to the parent manifest/outcome, created only inside the parent's effect transaction. Each child preserves its own actor/Party, campaign/reservation target, original hold identity, exact result/revision and ledger links required by existing constraints. Parent retry resolves the retained complete child set; it never executes an internal child by a client-supplied ID. A batch re-quote creates only held-term-version evidence and no confirmation receipts. Deferred constraints must reject missing, extra, reused or differently targeted children; nth-child, final-parent and commit-time failure roll back the entire effect set. The exact forward schema/adapter representation remains a blocking technical design item before runtime implementation. Do not drop uniqueness, actor, receipt, cash or completeness constraints; do not rewrite historical evidence to fit the batch.

Proposed global acquisition phases to validate against every affected writer, retained through outer commit:

1. Discover candidate cart/checkout line and Business IDs without treating them as authoritative. Lock **all distinct BusinessProfile rows ascending ID before User/Party/wallet**; then lock acting User and existing ordered Party authority set. Re-read cart/checkout mapping and compare the snapshot. If discovery changed, abort/retry discovery from no locks; never add a lower-phase Business lock after authority.
2. Validate current actor; lock Party cart root, checkout root, parent journal key and quote target in a fixed order. All cart/single-line writers must honor the cart root guard. Uniqueness prevents two live aggregate holds even for different request IDs.
3. Lock every campaign ascending ID, authenticated publication/closure/funding/exposure/connection gates, then all affected reservation roots and commitments in deterministic campaign/root order. Reuse the full existing Primary lock protocol, including terminal claim-retirement records; do not reduce it to locks on requested lines alone.
4. Collect **every affected wallet** (investor plus any terminal-claim owner), lock ascending Party ID as the existing reserve path does; then authenticate held-note capital and current balances under its shared writer gate. Issuance already takes wallet locks; capital must be read only after that lock, so issuance in another Business serializes without requiring its Business lock after wallet. Future principal/ownership writers must share this gate or remain unavailable.
5. Compute proposed rights/totals and one band; evaluate all line/current global conditions against the full planned delta. Persist all hold or confirmation effects, aggregate versions, notices/outbox/change feed and bought-line removals together. Constraint failure at commit rolls everything back. One cart revision increment removes the exact snapshot lines; the active-cart guard prevents replacing a newer line by campaign-only deletion.

Existing single-line, funding, refund, issuance, expiry and migration writers must be checked against these phases; update shared lock helpers/aggregate membership checks before enabling cart commands. Keep nested savepoints beneath the same outer transaction, no external I/O under locks, and bounded deadlock retry with the **same request identity**. No after-commit money writes. Outbox publication may occur after commit; retaining the outbox entry is part of commit. The contract is not concurrency-certified until real PostgreSQL two-process tests pass.

Membership enforcement includes **trusted per-child expiry sweeps, release/re-quote/confirm writers and funding/closure paths**, not only HTTP endpoints. Before a batch's all-or-none terminal effect, a writer targeting one child must delegate to the aggregate coordinator or refuse that independent transition. It cannot expire/release/confirm a child and leave siblings held. After aggregate confirmation, each campaign's existing separately authenticated funding/issuance/failed-closing lifecycle remains independent; the original cart's atomic confirmation does not promise that unrelated campaigns subsequently fund or issue together.

Persistence design: cart root/revision; immutable quote records; checkout root + append-only versions; unique child membership and authenticated complete parent/child operation links; exact disclosure/capital provenance; group expiry/release terminal evidence. A one-live-batch/Party constraint applies only if P383-01 is selected. Preserve existing child reservation/ledger/claim constraints. Forward-only schema changes over occupied history, no backfill that certifies old receipts, and no historical repricing.

## 9. Idempotency and uncertain outcomes

Scope keys: current `party:<partyId>` + command + request UUID, target-bound as above. Fingerprint validated context revision, target, expected revisions, quote IDs and acknowledgement hash using existing `CanonicalJson`. Retained successful/rejected commands return their original receipt without re-running admission, after **current** authorization. Keep existing journal retention semantics (currently 8 days); durable checkout state/child uniqueness must still prevent duplicate conversion after operation-record expiry. Never treat missing/expired journal receipt as permission to reconfirm an already confirmed batch.

Current role/Party/target authorization precedes receipt lookup, but **expected cart/quote/child revisions, quote TTL, held/terminal state and new financial admission are checked only inside a new operation's effect after journal replay lookup**. A committed reserve retried after its quote expires must replay its receipt rather than fail a new preflight check or create a second hold. Immutable historical targets remain resolvable for authorized replay. No controller may reprice or reject an original receipt because current capital/terms changed.

Before new writes after a timeout, lookup with the original command/request/target. Found confirmed = refresh/remove purchased lines from retained receipt; found re-quote = display new terms; found rejected = display refusal. `OPERATION_NOT_FOUND` means no retained receipt was found, not proof that all related lifecycle history is absent; retry **identical** command identity, whose durable state checks resolve ambiguity. A lost connection after commit creates no second hold or commitment. Two identical concurrent requests return one operation. Different request IDs on the same live batch cannot confirm twice.

## 10. Acceptance and integration handoff

These are required implementation tests, **not executed in this design task**:

| ID | Scenario / invariant |
|---|---|
| C383-01 | Two valid campaigns: quote is whole cart; reserve all, confirm all, one common rate; exact totals/ledger conservation, removed line IDs and correct remaining badge. |
| C383-02 | Every position in cart fails independently, then multiple failures: stable per-line codes; no roots/claims/holds/commitments/notices/cart deletion leak. Closed lines never disappear from the checkout total silently. |
| C383-03 | Last line insufficient capacity, cap or known connection; aggregate wallet one franc short; absent source/acknowledgement: zero new effects. Excluded band inputs still count for their existing risk rules. |
| C383-04 | Test every band floor at −1 / exact / +1; held principal + multi-line total crosses floor though each line alone does not; all lines choose the crossing rate. Wallet/held reservations/unissued commitments do not change band. |
| C383-05 | Capital falls/rises or financial policy/disclosure changes before reserve/confirm: stale reserve has no holds; confirm re-quotes all, commits none; old acknowledgement/request cannot accept replacement; explicit fresh acknowledgement confirms. |
| C383-06 | Re-quote retains original shared expiry. At exact expiry refuse; aggregate sweep returns every hold once; sweep/release/confirm races conserve cash/claims and have one terminal winner. |
| C383-07 | Cart edit versus quote/reserve; simultaneous checkouts; single-line hold/confirm/release against a batch child: guard/no stale deletion/no bypass. |
| C383-08 | Same key/same input and lookup replay exact result; same key/different input conflicts; different keys cannot double-confirm; timeout-before/after outer commit; retry after journal retention cannot duplicate durable effects. |
| C383-09 | Revoke/switch current actor or restrict wallet before command/replay/lookup; another Party guesses cart/quote/checkout/request IDs: current authority and non-disclosure preserved. |
| C383-10 | Inject failure on each nth child write, final wallet posting, cart delete, notice, outbox and deferred constraint; entire outer commit rolls back. |
| C383-11 | Actual PostgreSQL parallel reserve carts with reversed campaign order/shared wallet; cart versus single-line reserve, expiry, funding/closure, issuance and principal source updates. No deadlock inversion, overclaim, overspend, mixed band or partial effects. |
| C383-12 | Existing primary suites, retained purchase/issuance/failed-closing/refund evidence and schema-install races still pass; changed capital/band policy never changes confirmed/issued historical fee snapshots, disclosures, hashes or replay receipts; investor cancellation remains final. Distinct child command identities and exact complete parent binding satisfy original actor/receipt/ledger constraints at outer commit. |

Sequence: (1) server owner accepts this payload seam and records any amendment; UI owner confirms against it; (2) implement authenticated held-capital source and batch lock/membership primitives with tests; (3) cart revisions/quote/aggregate storage and parent journal; (4) atomic reserve/re-quote/confirm/release/expiry with rollback and real concurrency tests; (5) thin web/API adapters, Resources and generated routes in an explicitly coordinated change; (6) hand off sample receipts/refusals to confirmed UI owner; (7) independent exact-head review and normal feature→dev→uat→main gates. This documentation publication does not authorize runtime implementation, merge, deployment or financial activation.

Existing-code gaps: `ManageInvestorCart`/`InvestorCart` have no batch actions/revision; `GetInvestorCart` explicitly omits fees; `RetainedInvestorCheckoutFacts::activeCapital` adds `investor_committed` and original issued principal; `CurrentPrimaryAdmission::terms` adds only current line; `EloquentPrimaryCheckout` journals one campaign; `EloquentPrimaryReservations::confirm` may re-quote/expire/delete one line; `EloquentInvestorCart::bought` deletes by Party/campaign without snapshot revision. Refactor these seams coherently; an added UI button cannot satisfy the contract.

Unresolved decisions: P383-01–P383-07 and affected fee-consumer scope require explicit handoff resolution; no silent defaults. Unresolved technical prerequisites: authenticated outstanding-capital completeness, exact shared lock helpers, internal child receipt identities/complete parent binding and forward aggregate schema/constraints need design plus isolated PostgreSQL proof before runtime implementation. No fee threshold/capital-basis product decision remains open. Historical fee snapshots must never be repriced. Cart UI ownership is confirmed in comment 6502081931; action/payload and proposal resolution remain part of this handoff. No delivery date is inferred.
