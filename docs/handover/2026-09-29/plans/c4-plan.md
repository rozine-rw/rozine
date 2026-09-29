# Rozine C4 server plan: repayment, payout, propagation and secondary-ready contracts

**What this is based on:** `origin/dev` at `f1428e12` (S3-B merged), #175 at `82bddac0` and #176 at `ef065d99`. Hussain has not posted a Q1–Q18 mapping yet. His only C4 answers are Plus (5853007475) and net-return display (5865775804). Robert has answered only R5; R1–R4 and R6–R9 are open.

## 1. Server slices, dependencies and start timing

| Slice | Scope | Depends on | Start |
|---|---|---|---|
| **S4-A1** Business servicing wallet and funding | Business wallet; `business.wallet.deposit` through the existing synthetic deposit adapter (collection) | S3-B (merged), Business authority | **Now** |
| **S4-A2** `repayment.pay` | Idempotent internal debit for the `due_now` option | S4-A1, S4-B, S4-C, and a Holding written by S3-C's `FundedCampaigns` adapter | After #175/#176 merge |
| **S4-B** Servicing domain and allocation | Frozen instalments; §11.4 order; per-Holding entitlements from `UnitRights`; 2% service fee; 25% steward share; earnings fee at the locked rate; late fees gated | Pure domain now; persistence needs `primary_holdings` activated | Domain now, persistence after merge |
| **S4-C** Payout postings | `WalletPostings` extension; payout outbox and worker; exactly-once `PAYOUT_CREDITED` | Stacks on #176's `2026_09_29_100200` | Schema and port now, stacked on #176 |
| **S4-D** DPD, arrears and Admin reads | Servicing fold, `servicing:advance` job, `ARREARS` cause, `StaffRepaymentsResource` plus requery | S4-B | After S4-B |
| **S4-E** Propagation beacon | `change_feed`, `/changes?after=` routes, `use-change-beacon.ts` | None; it emits for C3 effects first | **Now** |
| **S4-F** Secondary-ready contracts | Pure PHP contracts and domain arithmetic, the lot projection, TypeScript and fixtures; no routes | Lot projection needs Holdings | Contracts now |

## 2. Slice detail

### S4-A1: Business wallet
**Schema.** `investor_wallets.party_id` is `UNIQUE`. A sole trader's `entity_party_id` is the owner's own person Party (`app/Domain/Business/MandateAuthority.php:83`). So `lockForParty(entity_party_id)` would make the owner's Investor wallet and Business wallet the same row.

New forward migration `2026_09_30_100000_add_business_servicing_wallets.php`:
- Add `owner` (`investor` | `business`) and a nullable `business_id` FK to `business_profiles`.
- Replace the `party_id` unique constraint with two partial uniques: one on `party_id` for Investor wallets, one on `business_id` for Business wallets. Add an exactly-one `CHECK`.
- Add the account kind `business_available`.
- Add `business_funding_methods`, append-only, with `verification_source='synthetic'`.
- Allow `wallet_deposit_intents` to point at either an Investor or a Business method, with an exactly-one FK `CHECK`.
- `down()` refuses once any rows exist.

**Code.**
- `EloquentWalletStore` becomes audience-aware, or gets a sibling `EloquentBusinessWalletStore`.
- Authority: `AuthorizeActiveRole(user,'business')`, then the mandate permission.
- Journal key `business:<id>`. Reuse `RecordDepositIntent`, `DispatchDepositIntents` (`afterCommit`) and `ApplyProviderOutcome` unchanged.
- The §11.4 high-risk hold never blocks a deposit.

**Tests.**
- Sole trader with both roles gets two distinct wallets.
- Deposit replay and conflict; outer rollback makes no provider call.
- Forked duplicate success credits once.

**UI.** New `BusinessWalletProps` (C4) plus `business-wallet-c4*` fixtures. Only the 1B type exists at `types/business.ts:951`.

### S4-B: Servicing domain and allocation (`App\Domain\Servicing`)
- **Schedule.** `NoteSchedule` comes from the retained campaign payments plus `IssueSchedule::dates` (the Kigali anchor with the original day clamped).
- **Full-instalment allocation.** A full-instalment receipt pays each Holding exactly its frozen `UnitRights` instalment component. MC-04/CFG-02's cursor rule already conserves the campaign total across ordinals, so no proportional rounding runs.
- **Partial allocation.** `EntitlementDistribution::largestRemainder` (CFG-04: floor, then largest remainders, ties by ordinal) is written as pure code but gated off until R8 is answered.
- **Earnings fee.** Taken from `holding.terms.earnings_fee.rate_bps`, half-up on the return paid in each instalment. This is the same rule as `PrimaryTerms::payoutFees`, so the fees paid add up to the fee quoted at checkout.
- **Late fees.** `ServiceFee` and `LateFeeLadder` are parameterised by policy; no rates are hardcoded.

**Schema.** `2026_09_30_110000_create_note_servicing_tables.php`. Every table is immutable (trigger rejects update/delete), with an encrypted payload, a `sha256` digest, and a `down()` that refuses once rows exist.
- `servicing_policies`: versioned. Holds `service_fee_bps=200`, `steward_share_bps=2500`, allowed options and `late_fee_policy_version NULL`. A missing policy returns `POLICY_INPUT_REQUIRED`.
- `note_servicings`: one gate row per campaign, inserted with `ON CONFLICT` as wallets are.
- `note_instalments`: inserted once. A trigger checks that the sum of Holding rights equals each component.
- `repayments`: operation, option, amount, component split, `servicing_revision_before`, `effective_at`, Kigali `effective_date`, receipt.
- `payout_entitlements`: unique `(holding_id, instalment_index)`, with `record_date`, `vested_at`, principal, return, `fee_rate_bps`, `fee_policy_version`, fee and net. A deferred trigger binds each amount to that Holding's rights, and the per-repayment sum to the repayment (the #172 P2 lesson).
- `servicing_events` plus a `note_servicing_fold()` function, following the `disbursement_fold` pattern.
- `late_fee_assessments`: the table exists, but a `CHECK` requires an approved policy row. Nothing is seeded outside testing.

### S4-A2: `repayment.pay`
**Command.**
- `App\Application\Servicing\PayRepayment` takes `{request_id, identity_context_revision, note_id, option, expected_servicing_revision, quoted_total}`.
- The server recomputes the amount and returns `VERSION_CONFLICT` on a mismatch. It never checks `RESTRICTION_ACTIVE`.
- In one transaction it records `REPAYMENT_RECEIVED`, the allocation, the entitlements, `REPAYMENT_ALLOCATED`, the payout outbox rows and the `change_feed` rows.

**Test clock.** `next_instalment` stays behind policy until R8 is answered. For C5 witnessing, use `SyntheticServicingClock`, guarded like `SyntheticWalletGuard`.

**Lock order.** Business (`business_profiles`) → staff users in ascending id (staff commands only) → campaign/note → `note_servicings` → repayment → Business wallet → ledger. Holdings are immutable and are read without locks. The payout worker locks only entitlement → Investor wallet → ledger. It never takes a Business or campaign lock, so it can't form a cycle with Primary checkout (campaign → reservation → wallet).

### S4-C: Payout postings
**Migration.** `2026_09_30_120000_add_servicing_postings_to_wallet_ledger.php` adds the system accounts `servicing_clearing`, `service_fee_revenue`, `steward_share_payable` and `earnings_fee_revenue`, plus two entry kinds:
- **`repayment_debit`** (Business wallet): debit `business_available`. Credit `servicing_clearing` (principal + return), plus service-fee revenue and the steward payable.
- **`payout_credit`** (Investor wallet, `source_type='payout_entitlement'`, cause `repayment`): debit clearing by the gross amount. Credit `investor_available` with the net, and `earnings_fee_revenue` with the fee.

**Deferred check** in `ledger_entry_balance_check`, extended the way #176 did it:
- The exact bucket shape and amounts must equal the entitlement row.
- One credit per entitlement.
- Credits paid out of a repayment's clearing can never exceed what that repayment put in.

**Port additions** on `WalletPostings`: `lockServicingWallet(businessId)`, `repay(...)` and `creditPayout(..., PostingCause)`. `PostingSource::SOURCES` and `PostingCause::TYPES` widen to match.

**Worker.** `App\Application\Servicing\CreditPayouts` is triggered by `afterCommit` and by a scheduled `payouts:credit`. It refuses to run inside an open transaction. It is internal only, so no provider is involved.

**Tests.**
- Raw-SQL negatives: over-credit, a second credit, a cross-entitlement amount, and a line appended after an early flush (5869021194).
- Forked duplicate workers credit once; payout credit racing an Investor hold on the same wallet.
- Outer rollback discards the dispatch.

### S4-D: DPD, arrears and Admin reads
**Domain.** `Dpd` follows MC-03: the due date is DPD 0, with no weekend or holiday roll.
- `servicing:advance` runs every minute and is idempotent per (note, date). It records `ARREARS_STARTED` at DPD 1 and `ARREARS_CLEARED` only on a reconciled cure of principal and return (CFG-04).
- `defaulted` is never emitted in C4.
- The payment effective before the Kigali midnight boundary is evaluated first. That boundary race needs a test.

**Admin.**
- `StaffPermission`: treasury gets `repayments.view` and `.requery`; approver and compliance get view; superadmin gets view only.
- `StaffRepaymentsResource` produces `AdminRepaymentsProps` (`types/admin.ts:1037`).
- Requery applies only to the Business deposit intent. It observes the same operation and the send count never increases (5868190264 #4).

### S4-E: Propagation beacon
**Schema.** `change_feed` is append-only: `bigserial` cursor, `created_xid`, audience scope (`party_id` / `business_id` / `staff_queue`), topic, subject and revision.
- Rows are inserted in the effect's own transaction.
- A read returns only rows whose `created_xid` is below `pg_snapshot_xmin(pg_current_snapshot())`, so a cursor can't skip a transaction that commits late.
- Topics are filtered by current authorization at read time (5849714844).
- An expired cursor (retention at least 24 h) or an identity change returns `reset:true`.

**Routes.** `changes.index`, `api.v1.changes.index` and `staff.changes.index`.

**Client.** `use-change-beacon.ts` composes `use-bounded-poll`, `use-access-refresh` and `use-online`. No Reverb.

**Tests.**
- No cross-audience leak of topic ids.
- Out-of-order commits are never skipped.
- A stale revision is ignored.
- The two-context browser journey.

**UI.** Add `ChangeTopic` and `ChangeFeed` to `settlement.ts`; they are not there yet.

### S4-F: Secondary-ready contracts
- `App\Application\Secondary\Contracts\*` interfaces and DTOs, with no bindings or routes (Q11).
- Pure `Domain\Secondary\{FeeSplit, BidReserve (A5-2), PriceBand (§6)}` parameterised by `FeePolicySnapshot` bps.
- `HoldingLotProjection`: one `primary_issue` lot per Holding, with nothing disposed or encumbered.
- The eligibility snapshot is always `FEATURE_DISABLED`, plus `ARREARS` from S4-D.
- Binds to `HoldingSecondaryReady` (`types/investor.ts:1525`).

## 3. Policy inputs

**Settled**
- **Plus fee on earnings.** Tiers 10/8/6.5/5.5/5.0/4.0%, return only, locked at commitment, replacing the 1% fee (#99 5832645053, 5852160501; #96 5853007475). It is already frozen in `PrimaryTerms`.
- **Net-return display.** Show `realised.{return, fees, net_return}` (#96 5865775804).
- **Late-fee steps and pass-through.** N4's 5/5/5 steps exist. C7 passes late fees to investors with a detailed breakdown (#99 5831351265, 5831415513).
- **N7:** reconciliation tolerance is RWF 0.
- **Contract rules:**
  - due dates, allocation order, largest-remainder distribution, the 2% service fee, the 25% steward share, arrears clearing, and deposits and repayments never blocked by a hold (CFG-04 §11.4);
  - DPD (MC-03), provider outcomes (MC-08), and the secondary freeze at DPD 1 (§8.3).
- **Hussain's rules:** requery and reconcile semantics (5868190264 #4, 5871859618 #7 and #10).

**Open, what each blocks, and how to build around it**

| Input | Blocks | Built fail-closed or provisional |
|---|---|---|
| R1 (base), R2 (replace or stack on §8.5), R3 (fees on late fees) | Late-fee amounts in S4-B/S4-D | `LateFeePolicy` is unavailable, so there are no assessments; live `late_fees` is `null` |
| R4 (disclosure text) | Investor late-fee lines | Lines stay preview-only |
| R9 (note-level late-fee visibility) | Resource scope | Holding's own share only |
| R7 / Q7 (auto-collection) | Due-date debit job | `autocollect: null`; no job |
| R8 / Q8 (partial or early payment) | `next_instalment` and partial largest-remainder | `due_now` only; synthetic clock for witnessing |
| R6 / Q9 (withdrawal) | Investor loop | Out of C4 (Plan Phase 2) |
| Plus capital basis (5853012548) | S3-C tier selection only | **Not C4**: payouts read the locked rate |
| Q1–Q4, Q10–Q12 | Path, timing, attribution, fee rounding, beacon, versions | Build the recommended options as provisional |
| Steward identity and the SLA hold | Steward attribution | Posted to an unassigned `steward_share_payable` |

## 4. Proposed lane split

**Our lane**, as with S3-B/S3-D, with Hussain reviewing independently:
- S4-A1, S4-A2 (the transport and command wiring), S4-C, S4-E and S4-F;
- the S4-D Admin Resource, routes and permission mapping;
- every UI binding (U4-A to U4-G).

**Hussain's lane**, because it extends his Primary, Holding and `FundedCampaigns` persistence:
- S4-B (servicing domain, schedule and entitlement persistence, the Investor holding and portfolio Resources);
- the S4-D fold, DPD and arrears state;
- activating `primary_holdings` (#176 TODO).

## 5. Genuine blockers
1. **No contract amendment has landed** for any of these conflicts:
   - N4 against §8.5's 2%/yr penalty;
   - C7 against §11.4's "Collected penalties have a separate platform-revenue ledger";
   - Plus against §11.4's 1% investor fee;
   - N2 against AM-11/§6 and Plan L470's 35/35 split.

   Live late fees, investor late-fee shares and the S4-F fee constants are blocked until they do. Hussain (5849714844) requires reconciliation before live behaviour.
2. **Holdings don't exist yet.** The #176 `primary_holdings` table is not in use, and the S3-C adapter that writes Holdings (a TODO in `FundedCampaigns`) hasn't been built. S4-A2, S4-B persistence and S4-D can't run until it is.
3. **Sole-trader wallet collision.** The Party-keyed wallet would merge a sole trader's Investor and Business wallets; S4-A1 needs the Business-keyed migration above. The "Admin controls" provisions (§11.1) also require the two to stay separate.
4. **Witnessing a due-date payment in C5** needs either R8 (early payment allowed) or an approved synthetic clock (Q8).
5. **Release gate:** the Terms & Conditions and Privacy Note (#154) still gate any release beyond local and testing.

6. **Borrower service-fee disclosure gate** (agreed with Hussain in #96 comment 5876362074, raised from Robert's #99 comment 5876175076). **No binding borrower offer or signing flow is activated until the offer/quote contract discloses the §11.4 2% borrower service fee.** The disclosure must show its exact base (principal and contractual return actually repaid, excluding fees and penalties), its rate, when it is charged (with each instalment) and its rounding (half-up, whole RWF), all before signing. **Ownership:** Hussain owns the server contract field (C4 split); we own the UI binding on the Business offer/review and publish screens. **Scope:** this does not block isolated S3-C persistence or S3-D synthetic reviews, but it is a gate before any Alpha signing flow carries that liability. Robert's policy question stays open through #180. The baseline is §11.4 plus CFG-01 (RWF 0 MVP listing fee), and fixtures are not changed to match the unadopted table.

### Critical files for implementation
- `/Users/engineersticity/Documents/projects/rozine/app/Application/Wallet/Contracts/WalletPostings.php` (with #176's `issue` and `PostingCause`)
- `/Users/engineersticity/Documents/projects/rozine/database/migrations/2026_09_29_100200_add_primary_issue_to_wallet_ledger.php` (on #176; the pattern for `ledger_entry_balance_check`)
- `/Users/engineersticity/Documents/projects/rozine/database/migrations/2026_09_29_100100_create_primary_holdings_table.php` (on #176)
- `/Users/engineersticity/Documents/projects/rozine/app/Domain/Primary/UnitRights.php` and `PrimaryTerms.php` (on #175)
- `/Users/engineersticity/Documents/projects/rozine/app/Infrastructure/Wallet/EloquentWalletStore.php`
- `/Users/engineersticity/Documents/projects/rozine/resources/js/types/{investor,admin,settlement,business}.ts`