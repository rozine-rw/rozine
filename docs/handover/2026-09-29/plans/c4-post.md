@hussain4real: **a C4 lane proposal**, in keeping with the no-idle rule. It's based on `dev` `f1428e12`, #175 and #176. You haven't posted a C4 Q1–Q18 mapping beyond Plus (5853007475) and net-return display (5865775804), and Robert has only answered R5. So everything below that touches open policy is built fail-closed or clearly provisional.

## Slices
| Slice | Scope | Depends on | Can start |
|---|---|---|---|
| **S4-A1**: Business servicing wallet | Business wallet, plus `business.wallet.deposit` through the existing synthetic collection adapter | S3-B (merged), Business authority | **now** |
| **S4-A2**: `repayment.pay` | Idempotent debit for the `due_now` option; the server recomputes the amount and returns `VERSION_CONFLICT` on a mismatch | A1, B, C, and Holdings via your `FundedCampaigns` adapter | after #175/#176 |
| **S4-B**: servicing domain and allocation | Frozen instalments, the §11.4 order, per-Holding entitlements from `UnitRights`, the 2% service fee, 25% steward share, the earnings fee at the **locked** `rate_bps` (per-instalment half-up, the same rule as `PrimaryTerms::payoutFees`), with late fees gated | Pure domain now; persistence needs `primary_holdings` activated | domain now |
| **S4-C**: payout postings | `WalletPostings` + `repay` / `creditPayout`, a payout outbox and worker (`afterCommit`, refuses inside a transaction), exactly-once `PAYOUT_CREDITED`, and per-entitlement DB amount/shape binding (the #172 P2 lesson) | stacks on #176's ledger extension | schema and port now |
| **S4-D**: DPD, arrears and Admin reads | MC-03 DPD, an idempotent `servicing:advance` job, `ARREARS_STARTED`/`CLEARED`, `StaffRepaymentsResource`, requery with no resend | B | after B |
| **S4-E**: propagation beacon | Append-only `change_feed` with a snapshot-safe cursor (`created_xid < pg_snapshot_xmin`), authorization at read time, `reset:true` on an expired cursor or identity change; `/changes?after=` plus a `use-change-beacon` client, no Reverb | none; emits for C3 effects first | **now** |
| **S4-F**: secondary-ready contracts | Interfaces and DTOs only (no routes or bindings), pure fee-split, bid-reserve and price-band arithmetic, the Holding lot projection, and eligibility always `FEATURE_DISABLED` | lot projection needs Holdings | contracts now |

## Proposed split
- **Ours** (like S3-B/S3-D; you review independently): **S4-A1, S4-A2 wiring, S4-C, S4-E, S4-F**, plus the S4-D Admin Resource, routes and permissions, and every C4 UI binding.
- **Yours** (it extends your Primary/Holding persistence): **S4-B** (servicing domain, schedule and entitlement persistence, Investor holding and portfolio Resources), the S4-D fold, DPD and arrears state, and activating `primary_holdings` (the #176 TODO).

I'd start **S4-E and S4-A1 now**, and S4-C's schema and port stacked on #176, unless you object.

## Design issue to settle before S4-A1: sole-trader wallet collision
`investor_wallets.party_id` is `UNIQUE`, and a sole trader's `entity_party_id` is the owner's own person Party (`MandateAuthority.php:83`). `lockForParty(entity_party_id)` would therefore make their Investor and Business wallets **the same row**, which also breaks the §11.1 separation.

Proposal: a forward migration adds an `owner` column (`investor | business`) and a nullable `business_id`. The global unique becomes two partial uniques: `party_id` for investor wallets and `business_id` for business wallets, with an exactly-one CHECK. It also adds a `business_available` account and append-only `business_funding_methods`. No existing S3-B behaviour changes. OK?

## Open policy (built fail-closed; none blocks starting)
- **Late fees:** R1, R2 and R3 (the base, and replace vs stack on §8.5) mean `LateFeePolicy` is unavailable. There are no assessments, and live `late_fees` is `null`.
- **R4, R9:** preview only, with Holding-own-share scope.
- **R7 auto-collection:** no job.
- **R8 partial or early payment:** `due_now` only. Witnessing a due-date payment in C5 needs either R8 or an approved guarded synthetic servicing clock.
- **R6 withdrawal:** out of C4.
- **Contract amendments still outstanding:** N4 vs §8.5, C7 vs the §11.4 penalty ledger, Plus vs the §11.4 1% fee, and N2 vs AM-11/§6 and the 35/35 split. These gate live late fees and any secondary fee constants. They need the joint amendment with Aminu.
- **Proposed lock order:** Business → staff (staff commands only) → campaign/note → `note_servicings` → repayment → Business wallet → ledger. The payout worker locks only entitlement → Investor wallet → ledger, never Business or campaign, so it can't form a cycle with checkout.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
