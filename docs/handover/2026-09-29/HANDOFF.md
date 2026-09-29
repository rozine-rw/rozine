# Rozine MVP handoff: Erastus's lane (2026-09-29)

This handoff is for the next Claude account taking over Erastus's lane (Engineersticity on GitHub). The previous session ran from 2026-09-27 to 2026-09-29. This file, its plans, the review repros, the scripts and a memory snapshot are on branch **`handover/2026-09-29`** under `docs/handover/2026-09-29/`. Don't merge the branch; it's a reference.

## 1. The role and standing rules
- **The lane.** Erastus's lane covers UI/UX (Inertia React), independent reviews of Hussain's server PRs, coordination, and the server slices handed over to us: **S3-B wallet (merged)** and **S3-D disbursement (#176)**. Hussain (`hussain4real`) owns the S3-C Primary server work and reviews our PRs independently. Robert (`robtumaini`) is Product/Legal. Aminu signs contract amendments jointly.
- **Communication.** Use GitHub issues only. **#96** is engineering and **#99** is Robert's policy decisions. **#154** is the legal release gate. You have standing permission to post there without asking Erastus first.
- **Merging.** The dev fast lane lets you merge a PR to `dev` once it has green checks **and** Hussain's independent review at the **exact head SHA**. Use `gh pr merge N --merge --match-head-commit <sha>`. Never merge S3 server PRs without Hussain's review. Don't promote to `uat` or `main`, and don't deploy; that needs Erastus. Hussain's messages repeatedly say "no merge authorised"; he means his own review doesn't authorise it. Where he asks for a PR to stay draft, as with #181, respect that.
- **Branch flow.** Go `feat/*` → `dev` → `uat` → `main`. Branch off `origin/dev`, never off `main`.
- **Production access.** SSH to the Contabo box needs Erastus's explicit approval, with a read-only script shown first. Any host change needs separate approval.
- **Ask owners directly.** When input conflicts, post numbered **a/b decision questions** to the owner in the same issue straight away, not only an engineering gap table. Erastus asked for this explicitly on 2026-09-29.
- **Never idle.** Both lanes keep working the backlog while the MVP is unfinished.
- **Security.** Never request, store or repeat secrets. Use least privilege and read-only first.

## 2. Open PRs (all drafts into `dev`)
| PR | Head | What | State / next action |
|---|---|---|---|
| #175 (Hussain) | `8d500fc7` | S3-C Primary reservations, checkout, confirm, expiry, funding candidate | Ours to review. Every range up to `8d500fc7` is reviewed and posted. Watch #96 for new ranges and review each one immutably (see §4). |
| #176 (ours) | `8a931443` | S3-D disbursement, staff step-up, synthetic payouts | With Hussain for recheck of `48f92447..8a931443` (the cross-intent replay fix). Merge order is #181 → #175 → #176; rebase onto #175 once it lands (see §5). |
| #181 (ours) | `2bdc315e` | Investor `deposit_credit` binding to intent amounts (migration `175521`) | Hussain approved it. He asked to keep it draft until the integration decision. First in the merge order. |
| #184 (ours) | `5719055b` | Business campaign copy: no recycling promise; "Committed", not "Funded"; "Not yet committed or reserved" | Green; awaiting Hussain's review, then merge under the fast lane. |
| #185 (ours) | `3c78ac31` | Investor copy: lapsed holds don't "come back to this raise" | Green; awaiting Hussain's review. He also has an open question on `primary.cancel_body` ("…these notes are released"). |
| #177 (ours) | `de1845b7` | C3 exit checklist and the end-to-end chain browser test | Awaiting Hussain's review. The checklist is in the PR body; keep it updated. |
| #178 (ours) | `f09221ed` | Server-rendered 5xx page | Awaiting Hussain's review. |
| #179 (ours) | `ee692141` | S4-E change beacon | Stays draft until the C4 lane split is agreed. |
| #180 (ours) | `0aa5f38e` | Contract amendment §12 (PA-01..PA-13) | Awaiting joint sign-off from Erastus, Aminu and Robert. Don't implement the reversals it lists until then. |

**Merged this session:** #182 service-fee disclosure (`3b120394`) and #183 `CAMPAIGN_SETTLEMENT_REQUIRED` copy (`45060a19`). Earlier: #158–#174 (UI/Phase 2) and #172 (S3-B, `f1428e12`).

**WIP snapshots, pushed only as backup:**
- `wip/snapshot-agent-a132e9d8ae68199b7`
- `wip/snapshot-agent-aab06632ac88d8e02`
- `wip/snapshot-cool-carson-3fa5a2`
- `wip/snapshot-focused-tereshkova-43cd22`
- `wip/snapshot-launch-json`

These capture uncommitted state from old local worktrees; the first four are 6 days to 3 weeks old. They're probably stale or superseded, so check them before using. `wip/snapshot-launch-json` holds local `.claude/launch.json` preview configs.

## 3. Waiting on people
### Erastus
1. **Hosting decision (a release blocker).** Robert's Privacy Note §4.1 says data is stored on servers in Rwanda, but staging and production run on a Contabo VPS that isn't in Rwanda (region to be confirmed in the Contabo panel). Either move the hosting or have Robert revise the text (#154 Q13).
2. **Sign-off on #180.**
3. **Upload limits.** Raise PHP-FPM `post_max_size` and nginx `client_max_body_size` from 25M to 64M, staging first, then production. The command and rollback are in the prior session; `scripts/check-upload-limits.sh` is the read-only check that was run. Dispute evidence needs about 52 MB.
4. **Later decision:** should a *missing* `service_fee` block signing (#182's preview-only flag)? We recommend switching it on in the same release as Hussain's server field.
5. **Later decision:** the production migration window and retry policy for the Business-wallet migrations (M1/M2).

### Robert (#154, comment 5885143755)
- **Q1–Q16** cover:
  - fees: RWF 50,000 application fee vs RWF 0 for MVP, the fee charged twice, 2% upfront vs per-repayment;
  - Default at day 45 vs day 31;
  - the Plus tier table (5 vs 6 tiers);
  - sandbox caps vs the 50% cap;
  - investor categories;
  - the promised features: cart checkout, withdrawals, auto-debit, credit bureau and first-loss reserve, biometrics;
  - data localisation, CMA/NCSA confirmation, the privacy@ mailbox, and languages.
- The documents are `investor-terms-2026-10-01`, `business-terms-2026-10-01` and `privacy-note-2026-10-01`. They take effect on **1 October**. Their text is in `legal/`; `legal/gap-154.md` is the gap list and `legal/q-robert-154.md` the questions.
- **Hold the swap** from the placeholder legal text to these documents until Q13 and the conflicts are answered.
- **Also open on #99:** late-fee R1–R4, R6 and R9; the Plus capital basis; seven amendment questions (comment 5874627603); and the fee-table question (5876226181). The current contract rule is §11.4, where 2% is charged on repayments.

### Hussain
- Reviews of #177, #178, #184 and #185, plus the #176 recheck.
- Server work he owns:
  - the campaign progress contract fields (see §6);
  - the expiry-sweep starvation fix;
  - lifecycle states;
  - the stuck-at-deadline fully-committed raise;
  - recycling, which is a checkout-activation blocker;
  - `lockFunded` as a retained reader (see §5);
  - refund-aware progress;
  - the service-fee server field;
  - the fee rounding decision, with Aminu.
- The flaky test `IdentityAccessConcurrencyTest:1515` (same-key audit-source replay race) is reported on #96. We offered to fix it and he hasn't answered yet.

## 4. How to review #175
- **Always review an immutable range**, `A..B`, exactly as Hussain names it on #96.
- **Use a fresh detached worktree** under your scratchpad: `git fetch origin pull/175/head:refs/remotes/origin/pr175`.
- **Use a private PostgreSQL cluster**: `initdb`, a port in the 55xx range, built from the repo's CI `prepare-test-databases.sql` with a non-superuser test role. **Never use ports 5432, 5433 or 5439**; 5439 is shared with Hussain's agent.
- **Run tests the way that works:**
  - `vendor/bin/pest` with `php -d memory_limit=2G`, because `artisan test` ignores `-d`.
  - Run `npm ci && npm run build` in the worktree, because a copied manifest breaks UiPreviewTest.
  - If a PHP tool exits 255 with no output, re-run it with `PAO_DISABLE=1`.
- **Attack the range:** real `pcntl_fork` races proven with `pg_locks` / `pg_blocking_pids`, raw-SQL negatives, both insertion orders plus `SET CONSTRAINTS ALL IMMEDIATE`, migration install deadlocks against in-flight commands, and rollback-list registration. Then mutation-test with `repros/*/mutate.py`.
- **Post** the review with `gh pr review 175 --comment --body-file …`, with repros inline in `<details>`, plus a short summary on #96. The previous reviews on the PR are the model.
- **Status as of `8d500fc7`:**
  - **All ranges reviewed.**
  - **Open Hussain items (P2):** the sweep starvation (A1), lifecycle always `live` (B1), the fully committed raise stuck at its deadline, and `lockFundingCandidate` not being usable as `lockFunded`.
  - **P3s:** listed in each review.

## 5. #176 integration notes (ours, for after #175 merges)
- **Preserve these #175 migrations** in `ledger_entry_source` and the rollback lists: `165949` (the `primary_commitment` ban), `175455`, `195022` and `212446` (keep it after `195022`). #176's `100200` narrows `primary_issue` to `primary_reservation` only.
- **Fixtures.** `WalletIssuePostingTest`, `WalletIssueConcurrencyTest` and `DisbursementReviewRegressionTest` need `PrimaryReservationFixture::terminalVersion($source,'confirmed')` before the commit. The fresh-ULID fallback in `PrimarySourceFixture` is refused. The round trip must re-check triggers, function bodies and constraints after `100200`.
- **Mechanical conflicts** with #175: `AppServiceProvider.php`, `ArchitectureTest.php`, `docs/phase-0/baseline-inventory.md` (regenerate it) and `PostgreSqlConfigurationTest.php`. Also add #181's `175521` to the lists.
- **`FundedCampaigns`** (agreed): `lockBusiness(businessId)` → sorted staff locks → `lockFunded(campaignId)`, which only relocks the Business it already holds. Hussain's `lockFundingCandidate` **cannot** be `lockFunded`:
  - it needs a pre-deadline campaign;
  - it refuses once cash is issued or refunded;
  - it locks Primary and wallets before the proof;
  - it returns in reservation-id order.

  `lockFunded` needs a retained reader built on `lockRetained`. That's Hussain's concrete adapter.
- **D5 staff connections.** `StaffConnections` stays fail-closed. We own staff-person resolution; Hussain owns the Business/Investor projection (`rejectKnownConnections` is complementary). No email mapping and no permissive fallback. A staff maker who is also a mandate person is caught today only because the adapter refuses.

## 6. Next tasks (in order)
1. **Campaign progress UI binding, fixture-first.** This had just started when the session ended and was stopped with no branch created. Contract: #96 5885379912, our proposal 5885213047.
   - `progress.units.unavailable`, so that committed + reserved + available + unavailable = total.
   - `remaining` = target − committed − live reserved, labelled "Not yet committed or reserved".
   - `lifecycle` ∈ {`live`, `fully_reserved`, `sold_out_pending_settlement`, `inventory_unavailable`, `closing_pending_settlement`}. Precedence: persisted terminal state, then elapsed deadline, then inventory. Keep top-level and progress lifecycle consistent. Cancel shows only when `can_cancel` is set.
   - No client subtraction, and fail closed on missing or unknown fields.
   - Stop the countdown in the settlement states.
   - Stack the PR on #184's branch `fix/campaign-copy-no-recycling-promise`, and retarget it to dev after #184 merges.
   - Fixtures per state in `resources/fixtures/ui/`, Vitest per state at 100% coverage, and a full-scale browser check in light and dark at 1440 and 390 px.
   - The UI mismatch list it fixes is in the #175 review of `f1e99b16..3141615a`, in `campaign-progress.tsx` at lines 116–120, 139, 145, 150–161, 190–241, plus the `en.ts` catalog at 3387–3393.
2. **Merge #184 and #185** once Hussain approves them (fast lane, exact head).
3. **Keep reviewing** each new #175 range as Hussain posts it.
4. **The #176 rebase** after #175 merges (see §5), then Hussain's recheck.
5. **The C4 lane split.** The proposal is in `plans/c4-plan.md`, and **gate 6** is there too: the borrower service-fee disclosure is required before any binding borrower signing flow. The Business wallet (Option C) is at revision 2, posted on #96 as 5875616015. Hussain has answered N1–N4. Implementation waits until after #176, and he has to approve it first.
6. **Legal documents.** When Robert answers, load them into the apps versioned and hashed, in place of the synthetic placeholders, and update #180 with any policy that's adopted.
7. **When checkout HTTP is activated (U3-C binding):**
   - never present a fresh-key replay of a terminal release as a second credit;
   - bind `service_fee` + `expected_schedule` on Publish;
   - review the `primary.cancel_body` copy.

## 7. Tooling and gotchas
- **Front end:** use `npx vp fmt` and `npx vp lint` and grep their output for "error"; don't use prettier or eslint directly. The web gate is `npm run ci:web`, which needs 100% on all four coverage metrics. `vp fmt` rewrites `config/client-source-manifest.json`, so revert that.
- **Client coverage manifests:** regenerate with `scripts/regen-manifests.py` when you add web files.
- **`inventory:baseline --check`** must run against a *migrated* pgsql test database.
- **CI behaviour:** a PR that conflicts with its base gets no CI. Coverage is an aggregate 100% gate across four PHP shards, and the concurrency tests don't count towards it.
- **Migrations:** each new one must go into the explicit rollback lists in `PostgreSqlConfigurationTest`, `AuditReportPersistenceTest`, `AuditAssignmentTest`, `BusinessExposureReservationTest` and `WalletSchemaTest`, wherever relevant.
- **Worktrees:** never run `git checkout`, `pull`, `merge` or `reset` in a worktree another agent is using. Read other branches with `git show origin/<branch>:<path>`.
- **Shell:** in zsh, quote `${var}` before a colon, because `$c:refs` triggers zsh modifiers.
- **Watcher:** `scripts/watch.sh` is a GitHub poller for Monitor. It emits new non-Engineersticity comments plus PR check changes. Edit the hard-coded `S=` state path first.
- **`scripts/fastmerge.sh`:** a per-group check waiter used for the dev fast lane.
- **Known flake:** `IdentityAccessConcurrencyTest:1515`. Re-run the failed job once the run completes: `gh run rerun <id> --failed`.
- **Session limits:** background review agents hit the session usage limit twice. When they stop, resume them by message; they keep their worktree and cluster.

## 8. What's in this folder
- `plans/`: the S3-B/S3-D/C4 plans, the Business wallet design with addendum revision 2, the progress contract proposal, the service-fee proposal and the amendment draft.
- `legal/`: Robert's three documents, our gap list and our Q1–Q16.
- `repros/review175*-repros/`, `repros/review176-repros/`: every independent-review repro test we posted, plus the mutation harnesses.
- `scripts/`: `watch.sh`, `fastmerge.sh`, `check-upload-limits.sh` (read-only) and `regen-manifests.py`.
- `memory/`: a snapshot of the persistent memory files. IPs and panel URLs are redacted, and no secrets are included. `MEMORY.md` is the index. The live copy is at `~/.claude/projects/-Users-engineersticity-Documents-projects-rozine/memory/` on Erastus's Mac.
