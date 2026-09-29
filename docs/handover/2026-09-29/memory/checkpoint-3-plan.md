---
name: checkpoint-3-plan
description: "C3 (wallet, primary purchase, settlement, disbursement + AC-02/AC-03) plan proposed on #96 2026-09-26: slice order, lanes, #99 inputs, scaffold PR #131"
metadata:
  node_type: memory
  type: project
  originSessionId: 39184b4e-2867-4960-913f-072766a49b6e
  modified: 2026-09-26T15:08:40.761Z
---

C3 start + plan posted on #96 (comment 5847304040) on 2026-09-26, after the C2 tail closed. **Hussain confirmed the order (5848518844)** and declined our offer to build S3-B: he keeps all server/ledger/provider work; we keep UI, browser and cross-review. **AC-02 is settled:** the final required signature on the existing `application.submit` reserves full exposure atomically (no separate Accept action); staff release (`applications.review`) verifies it, Publish reuses it once. First increment = PR #132 (approved by us at `a1c96e1c`, 9 batch items). Plus basis questions sent to Robert on #99 (5849324256); Diamond is 4.0% >RWF 250M. Erastus (2026-09-26 night) delegated merges to us to keep C3 moving while he's offline.

- **Server (Hussain), one PR per slice straight into `dev`** (avoids another #101-sized branch): S3-A AC-02/AC-03 (minimal staff release → Business Publish → live campaign, lifecycle, campaign progress/cancel) → S3-B wallet/ledger (synthetic deposit adapter) → S3-C primary purchase (deals, capacity, quote, 5-min reservation with ordinals, confirm/release/cancel, full-funding lock, 30-day expiry refunds) → S3-D disbursement + issue (maker/checker, step-up, worker recheck, reconciler/requery, failed closing) → joint close.
- **UI (us):** U3-A release/publish/campaign, U3-B wallet, U3-C deals/checkout/commitment/portfolio, U3-D admin disbursements, U3-E browser journeys for the whole chain. Bind as soon as shapes are posted.
- **#99 inputs for C3 (Robert 2026-09-25 12:49, final):** N3 listing fee = **RWF 0 for MVP** (RWF 50,000 at submission is post-MVP); C3 = **50% single-investor cap per raise replaces all other limits**, RWF 5,000 min unit stays; Plus = fee-on-earnings tiers 10% → 5% by active capital (bites at payout = C4; Auto-Deploy is automation → later phase). Proposed: C3 quote's `payout_fee` comes from the server at current tier, no tier UI until C4.
- **Scaffold:** draft PR #131 (`feat/phase-1b-c3-scaffold` → `erastus-dev`), rebased on dev `7516e2fa` at `2c0dd53d`, ci:web 100% + UiPreviewTest green. Contract reference: v2 proposal (copied to this session's scratchpad `prev/c3-contract-proposal-v2.md`).
- **Coverage manifests** (`config/client-{source,risk}-manifest.json`) are hand-maintained partitions of every tracked `resources/js/**/*.ts(x)`; on rebase conflicts take the base side and regenerate (types-only files go in `declarationOnlyPaths`; risk manifest carries the source manifest's sha256).

**Why:** Erastus (2026-09-26) wants the MVP finished ASAP and handed the lane to an agent while away.

**How to apply:** track each slice against this list; refuse scope creep; bind UI immediately. Related: [[checkpoint-2-exit-list]], [[stay-on-mvp-course]], [[robert-99-policy-adopted]].

**Progress log (2026-09-26/27):** #129, #130, #133 (promotion), #131 (scaffold) and #132 (AC-02 reservation, `69221eff` on dev) merged; #134 promotes #131 to dev. C4 contract proposal v1 posted on #96 (5849678439) — Hussain will map Q1–Q18 after the S3-A release/Publish handoff and wants fee ladder, C7 penalty allocation, Plus basis, early/partial payment, auto-collection and withdrawal kept visibly provisional; the change beacon must authorize at feed read too; no push dependency. Robert's C4 questions R1–R9 on #99 (5849676518).

**Release prerequisite (checked 2026-09-28 with Erastus's approval):**
- **Current limits on both staging and prod:** FPM pool `upload_max_filesize=25M` (fine for 10 MB files), `post_max_size=25M`, nginx `client_max_body_size 25M`.
- **Problem:** dispute uploads are 5 × 10 MB, so the last two limits are too low.
- **Proposed fix:** raise `post_max_size` and `client_max_body_size` to 64M in `/etc/php/8.5/fpm/pool.d/rozine{,-prod}.conf` and `/etc/nginx/sites-available/{staging.rozine.rw,rozine.rw}`, then run `-t` and reload. It awaits Erastus's OK to apply ([[production-reads-need-approval]]).

**S3-A progress (2026-09-27):** #141 staff release + Publish merged to dev (`fa37e723`) after review + live Chromium journey (found 2 integration bugs, fixed); UI #137/#140/#143/#144 merged; journey PR #146. Plus fee UI #139 merged (`EarningsFee{tier, rate_bps, basis:'return_only', policy_version}`, DISCLOSURE_STALE on tier change). Remaining S3-A: staff queue/Admin Inertia page, campaign restrictions/expiry/cancel/refunds, funded progress. CI speedup #145 (neg-controls 51m→2m) under re-review.

**2026-09-28 (Phase 2 fill while S3-B is built):** merged to dev:
- #158 read-failure retry
- #159 state map `ui-state-coverage.json` (dispositions include `deferred` for D-61)
- #160 deal lifecycles and the all-closed deck, with deck and panel fixes Erastus caught in the browser
- #161 gated Auto-Deploy explainer, I-09 (Profile sub-page, optional `links.automation`)
- #162 state fixtures and an Investor fixture coherence test

`HoldingSummary.value` is now invested + return received, with gain = value − invested. Posted on #96 (5865690807); gross vs net gain is open for C4. The S3-B binding target is posted on #96 (5865126315) and Hussain confirmed it (5865166529). Skeleton batch 4 is parked until the server uses `Inertia::defer`.

**2026-09-28, later:**
- **#163** (portfolio cards open their own notes; totals derived) merged.
- **#164** (Admin Book and Exceptions, read-only and fixture-first) merged `ba1012dd`. Hussain OK'd the shapes after two corrections: props extend `AdminFrameShellProps`, and DPD is null until due, 0 on the due date, positive when overdue.
- **#165** (Admin table `empty` slot, outside the scroller) merged.
- **Hussain on C4:** the live earnings display uses net (`PortfolioEarnings.realised.net_return`), the legacy `gain` stays gross, and "value" is cumulative performance, not a balance.
- **Next Phase 2 Admin screens:** Reconciliation (Ad-04), Reports (Ad-10), Partners coverage (Ad-07). Batch their shape proposals into one #96 post, since Hussain's priority is S3-B.
- **#166 merged `2ea552eb`:** Admin Reconciliation, Reports and Partner coverage, read-only and fixture-first. Hussain's corrections applied: `ExceptionItem.subject {kind: business|account, label}` replaces `business`; `ReportPack.incomplete` has `link: null`; the download link returns the exact `as_of` snapshot. All 10 spec Admin screens now exist as previews.
- **S3-B status (Hussain, #96 5867625347, 2026-09-28):** the binding contract is agreed but implementation has NOT started, with no ETA. The C3 critical path now waits on server work.
  - Our lane per Hussain: audit the wallet UI/tests against the binding contract; add only the missing web tests; write the 4 browser journeys as plans marked "awaiting S3-B draft".
  - We may not do PHP tests, server hooks or CI changes. The audit agent is running on branch `feat/s3b-wallet-ui-audit`.
- **2026-09-28 10:24 UTC: S3-B SERVER HANDED TO US** (#96 5868070263). Aminu explicitly authorized delegating server work to our agent. We own the first S3-B end-to-end slice: wallet balances, a balanced immutable journal, synthetic deposits, PHP tests, and binding the wallet page. Hussain does the independent contract/accounting review, retry/concurrency verification and integration review, and won't touch the same files.
  - **Interface to follow:** 5861394415 (server sketch), 5865166529 (UI binding), 5867886222 (§11.4 hold doesn't block deposits), plus the engineering contract.
  - **Out of scope:** S3-C refunds and S3-D closing. No new merge or deploy authority; no uat/main promotion.
  - **Deliverables:** an early draft PR against dev; reply on #96 with branch, head, boundaries and blockers; publish seed and synthetic-event hooks for him.
- #170 (S3-D UI audit: step-up proof defect) and #171 (S3-C: stale acknowledgement defect) were queued to merge; 5 contract questions posted on #96.
- **2026-09-28 ~14:40 local:** #172 (S3-B) is READY FOR REVIEW at `838f59d7`, with full hosted CI green.
  - Worktree `scratchpad/fu`, branch `feat/s3b-wallet-ledger`; private PG cluster `scratchpad/pgs3b` on port 5474.
  - `WalletPostings` port `dfdcbc9b`: hold/commit/release/refund, one source lifecycle, keyed by the reservation.
  - Hussain found the balance bypass after an early flush; it's fixed with a line-level deferred trigger plus a transaction-local seal.
  - Don't merge #172 without Hussain's independent review.
- **#175 (Hussain, S3-C domain) approved from our review at `c45ca2bd`**, after two rounds. The disclosure digest is now derived server-side with JCS over the terms and rights, and the fees are per-payout half-up against the approved ladder.
- **2026-09-28: S3-D SERVER + ADMIN UI BINDING HANDED TO US** (#96 5871504691), after the #172 findings are fixed.
  - Hussain keeps S3-C persistence, full funding, cancellation/expiry and exposure, and independently reviews our S3-D heads.
  - Boundaries: two distinct staff approvers with no threshold or bypass; a single-use bound step-up proof; an S3-C port that fails closed; a durable intent before send, outside the transaction; no resend on requery; exactly-once issue or refund; synthetic providers local/testing only; no merge or deploy authority added.
- **Hussain's #172 review (5871460536):** P1, dispatch before the outermost commit, fix with afterCommit; P2, the DB guard doesn't bind the movement amount to its own anchor. Being fixed by the resumed S3-B agent.
- **2026-09-28: #172 S3-B MERGED to dev `f1428e12`,** after Hussain's independent approval at `dce72e25`. The WalletPostings port is on dev.
  - S3-D is draft #176 (`feat/s3d-disbursement`, worktree `scratchpad/s3d`, PG on 5477).
  - Next: the `primary_issue` wallet extension on #176, and reviewing Hussain's #175 persistence increment.

**2026-09-28 (late):**
- **Business wallet (Option C):** Hussain accepted it as a provisional direction (5874524670), and the addendum closing his 4 points is posted (5874750331). Business commands keep the acting person's `party:` journal key, not `business:`. Build only after #176 merges and he approves it.
- **#175:** the S2 review is posted. Hussain took both findings: the unbound-id WalletIndependentReviewTest, and blocking `primary_commitment`. The F3 delta review is running.
- **#177:** all checks green at de1845b7 and awaiting Hussain's review. The checklist has C4 → IN-PROGRESS, with D5 ownership shared (we own staff-person resolution and the StaffConnections adapter).
- **#176:** handed to Hussain for his recheck at 8953b20f, all green. The open port-shape question is `lockBusiness`, split out of `FundedCampaigns`. #175's 165949 is preserved because `primary_issue` allows only reservation sources.
- **#175:** F3 review posted (P2-a/b, P3-a/b), and Hussain is fixing them. The caller/S2 review (ad598a09..2271131e) is running; the requote review (2271131e..85b68691) is queued.
- **Business wallet:** addendum rev 2 posted (5875616015), with Hussain's N1–N4 answers. N4: the production migration window and retry policy need Erastus's explicit approval as release owner.
- **Investor deposit_credit shape fix:** separate draft PR in progress (worktree dcs).
- **Hussain accepted the #176 defaults (5875990806):**
  - `FundedCampaigns::lockBusiness` then sorted staff locks, then `lockFunded` (relocks the held Business only).
  - Lapsed intents stay queued; there is no auto void, and operator recovery is an activation gate.
  - Reconcile may open disbursements for verified funded campaigns.
- **Integration TODO for #176 after #175 lands:** keep 165949 and 175455. Fixtures need `PrimaryReservationFixture::terminalVersion($source,'confirmed')` before the first commit (or `'released'` before a release).
- **#175 reviews:** ad598a09..2271131e approved (3 P3s). 2271131e..85b68691 and 85b68691..c8f30fcb are running.
- **#175 review status (2026-09-28 night):**
  - 2271131e..85b68691 (confirm/requote): changes requested.
  - 85b68691..c8f30fcb: approved with P3s. The 175455 install deadlock can be fixed by dropping `business_campaigns` from the lock list.
  - Still open: K2 (replayed commit accepted), K4 (cancel after confirm), K5 (receipt code unbound).
- **#176 integration after #175:** WalletIssuePostingTest, WalletIssueConcurrencyTest and DisbursementReviewRegressionTest need `terminalVersion($source,'confirmed')`. The fresh-ULID fixture fallback is refused. The round trip must re-check 175455 triggers and function bodies after 100200.
- **UI rules from Hussain for when #175 HTTP checkout is bound (U3-C):**
  - A fresh-key release on an already-released reservation returns the original release evidence (intentional state idempotency). The copy must never describe a repeated terminal response as an additional credit.
  - Do not show placeholder zero progress or investor counts as truth. Hussain owns the campaign progress read-model (`c87b6e08` summary); the Business display wiring is still pending with him.
  - `CAMPAIGN_SETTLEMENT_REQUIRED` copy is in #183.
- **Open PRs (2026-09-28 late):**
  - #176 at 8a931443, handed to Hussain for recheck.
  - #181 approved by Hussain, still draft, order #181 → #175 → #176.
  - #182 service-fee UI, draft.
  - #183 refusal copy, draft.
  - #179 S4-E, draft.
  - #180 amendment, awaiting sign-offs.
- **Campaign progress contract agreed (#96 5885379912, 2026-09-29):**
  - `progress.units.unavailable`, where committed + live reserved + available + unavailable = total.
  - `remaining` = target − committed − live reserved. Its label is "Not yet committed or reserved", not "available to buy".
  - Lifecycle states: `live`, `fully_reserved`, `sold_out_pending_settlement`, `inventory_unavailable`, `closing_pending_settlement`. Precedence: persisted terminal state, then elapsed deadline, then inventory.
  - The percentage is labelled "Committed", not "Funded".
  - UI: no client subtraction, and fail closed if a field is missing.
  - Ownership: the server side and recycling (a checkout-activation blocker) are Hussain's. The #184 copy fix, then the fixture-first binding PR, are ours.
- **Merged to dev this session:** #183 (45060a19) and #182 (3b120394).
- **#154:** Robert was asked Q1–Q16 (5885143755). Awaiting his answers.
- **#175 review backlog cleared up to 8d500fc7 (2026-09-29).** Key open items (all Hussain's):
  - the expiry-sweep starvation fix;
  - `lifecycle` states;
  - a fully committed raise stuck at its deadline;
  - `lockFundingCandidate` ≠ `lockFunded`. `lockFunded` needs a retained reader built on `lockRetained` that accepts issued/refunded cash, takes Primary locks after the proof, and returns commitments in commitment-id order;
  - recycling, which blocks checkout activation.
- **D5:** `rejectKnownConnections` (Investor side) is complementary to our `StaffConnections`. Staff-maker-is-a-mandate-person cases are caught only because our adapter fails closed; the real adapter needs staff→person Party resolution.
