# Design resolutions for #396 and #383

Proposed for design review by @hussain4real (design authority). Revision 2 answers the eight blockers in [#434 review 6507534193](https://github.com/rozine-rw/rozine/pull/434#issuecomment-6507534193) on revision 1 (`11a30776`). Revision 1 itself answered [#433 comment 6507027925](https://github.com/rozine-rw/rozine/pull/433#issuecomment-6507027925).

This file is documentation only. It changes no runtime behaviour, permissions, settings or data. It amends `docs/contracts/396-initial-statement-verification.md` and `docs/contracts/383-atomic-cart-checkout.md` (draft #433, reviewed at `3d4e42f2`) only where it says so. Source references are to `dev` at `e8af42fbf824bc44239bd7d4bc86a406b0667586`.

Each blocker is answered in the section marked **[B1]**…**[B8]**.

---

## R1. Factual statement review under routine dispatch (#396)

### R1.1 The coupling today

Selecting `kind = routine` alone is not enough. Routine is currently coupled to the monthly report:

| Coupling | Where |
|---|---|
| Completion only when the newest report is sealed and published | `EloquentAuditAssignmentStore::finish()` / `published()` |
| Report creation needs a submitted application | `EloquentAuditReportStore` (lines 170–218) → `EloquentBusinessApplicationStore::withAuditBinding()` (lines 960–969) |
| Any routine engagement created this Kigali month counts as monthly dispatch | `requestMonthly()` `$dispatched` |
| A report on a routine engagement is created as a monthly report | `EloquentAuditReportStore` (around line 197) |
| An open routine engagement is shown as a monthly reporting window | `EloquentBusinessAuditWindows::upcoming()` |
| Rotation reads consecutive **completed assignments**, not report rows | `EloquentAuditAssignmentStore::workload()` (lines 715–735) |
| Verification replay and lookup admit only `accepted` engagements | `EloquentStatementStore::verify()` (lines 236–249) and lookup (lines 373–385), via `withAccepted()` |

### R1.2 One purpose for every factual review, first or repeat [B2]

The engagement keeps `kind = 'routine'`. `AuditAssignmentClock` therefore gives it the preferred 4-hour accept window per offer and a visit due 48 hours after acceptance, with no change to the clock.

A new immutable field in the assignment state records why the engagement exists:

```text
purpose: 'monthly' | 'statement_review'          // absent on existing rows = 'monthly'
```

`statement_review` covers **both** the first factual review of a new Business **and** any later re-review, so revision 1's "once per Business" rule is withdrawn. This fixes the case of a Business whose first verification becomes non-current (because of an amended original or transcription, or a mandate or evidence change). It gets a new `statement_review` engagement with the same lifecycle. It never goes through the general request, the report path or an application.

- **Who sets the purpose.**
  - The new staff action writes `statement_review`. It is renamed from `audit.initial.request` to **`audit.statement_review.request`**, keeping the same authority (`audit.assignments.manage`) and the same readiness binding (contract §6).
  - `requestMonthly()` writes `monthly`.
  - The existing general `audit.assignment.request` keeps its current behaviour and writes `monthly`, so no existing caller changes meaning.
- **Immutability.** `purpose` is set at creation, copied into every `AuditAssignmentVersion` snapshot, and never changed by offer, accept, decline, redispatch, conflict or close. Existing rows are not backfilled: readers treat a missing `purpose` as `monthly`.

### R1.3 When a statement review may be requested [B5]

Revision 1's predicate (no completed engagement, not listed in `MonthlyAuditBusinesses::due()`) and its "can never compete" claim are withdrawn. `due()` is a monthly scheduling query, not an authoritative issuance predicate, and "no completed engagement" is not "no audit history". The scope is defined from authoritative evidence instead.

`audit.statement_review.request` is applicable when, under the Business lock taken by the existing dispatch scope, all of these hold:

1. The Business authority conditions of contract §6 are met: a verified Business, entity and people, an effective mandate, retained originals and a current source-linked transcription.
2. **No current statement verification exists** for the Business's current evidence revision and transcription, judged by the existing `storedVerification` / `withVerificationValidity` currentness. The point of a review is to produce one.
3. **No engagement of any purpose is open** (`offered`, `accepted` or `operations`). The existing one-open-engagement-per-Business rule is unchanged:
   - an open `statement_review` is returned as `AUDIT_ASSIGNMENT_RESUMED`, as today;
   - an open `monthly` or `flash` refuses `AUDIT_ENGAGEMENT_OPEN` (409), with that engagement's `kind`/`purpose` and status, so staff see why;
   - a latest engagement that is `closed` keeps today's `AUDIT_ENGAGEMENT_CLOSED` behaviour.

Issued notes and earlier history do **not** make a Business ineligible for a factual review. A repeat issuer with an amended source is a valid case.

**How it interacts with monthly dispatch (no "never competes" claim).** Both paths run under the same Business lock and the same one-open-engagement rule:
- A statement review open when `audits:request-monthly` runs (hourly) makes that run return `null` for the Business, through the existing non-completed rule.
- `$dispatched` is narrowed to `kind = routine AND purpose = monthly`. Once the review completes, the next hourly run in the same Kigali month dispatches the monthly audit normally.
- A monthly engagement open when staff request a review refuses `AUDIT_ENGAGEMENT_OPEN`, and staff retry once it completes.

The tests in R1.8 cover issuance in the current month, the month boundary, existing history and concurrent dispatch.

### R1.4 Completion, and keeping workload separate from rotation [B6]

`finish()` and `published()` become one `done()` predicate with two branches. The monthly and flash branch is **unchanged**: the newest report is sealed, there is no draft amendment, and it is published. The `statement_review` branch is complete when all of these hold:

1. `status = accepted`;
2. there is a statement verification appended under **this** assignment ID, by **this** assignment's `party_id`, bound to this assignment's accepted revision;
3. that verification is current under `storedVerification` / `withVerificationValidity`, checked under the locks already held.

**Where completion runs.**
- It runs inside the `statement.verify` effect, in the same transaction that appends the verification. The verify path already runs inside the dispatch `scope()`, which holds the Business lock, the candidate Party locks and the assignment row lock before evidence locks.
- Completion **reuses that held context**. It does not open a new scope and takes no lock after the evidence locks.
- It writes only the assignment row and one `AuditAssignmentVersion`, with `command = 'audit.assignment.complete'`, `reason` = the verification ID, and `selection_basis` from the held context with `candidates: []`. There is no dispatch and no candidate selection.
- The existing lazy `finish()` in `request` and `requestMonthly` also recognises completed reviews, so an engagement whose verifying transaction somehow left it accepted is still completed under those paths' locks.

**Every consumer of completed assignments, audited:**

| Consumer | Rule after this change |
|---|---|
| `workload()` → `active_count` | Counts `accepted` only. A completed review releases active workload. **This is the only workload effect.** |
| `workload()` → `consecutive_reports` (rotation; baseline "three consecutive reports", engineering contract lines 553–561) | Filters completed assignments to `purpose = monthly` (and missing purpose). A statement review is not a report and **never counts toward rotation**, in either direction. |
| `request()` / `requestMonthly()` "existing is completed → new engagement" | Unchanged. A completed review lets the next engagement of any purpose be created. |
| `EloquentAuditorDirectoryStore` (`active_engagements`, accepted-only) | Unchanged. |
| `EloquentAuditorEarningsStore`, report register, `EloquentAuditorFiledReportStore` | Unchanged. They read reports, and a review has none. Reviews earn nothing under the current fee shares, so payment for them is a product decision outside this design (BRS OD-19 covers Flash and arrears visits only). |
| `withAssigned()` (completed engagements readable at their accepted revision) | Used for retained receipts (R1.5). |
| `retainsVerification()` | Unchanged. It already admits `completed`. |

**What completion does not do.**
- It does not create, seal, publish or co-sign a report.
- It does not mark the Business eligible. Eligibility still comes only from current verified evidence over the full `UnderwritingObservationWindow`.
- It does not read or require a `BusinessApplication`.

**If the verification later becomes non-current**, the completed engagement stays completed and its history is immutable. R1.3 then permits a new `statement_review`.

### R1.5 Retained receipts after completion [B1]

Because completion commits in the same transaction as the verification, a lost response followed by a retry or lookup must still reach the receipt. Today both enter `withAccepted()`, which would refuse `ASSIGNMENT_NOT_ACCEPTED` once the engagement is completed.

**Resolution: split the authority by phase.**

| Phase | Authority | Rule |
|---|---|---|
| Replay of `statement.verify` (journal finds a retained row for the same actor key, command, request ID and fingerprint) | `withAssigned()`: `accepted` **or** `completed`, the current actor's own engagement, read at its accepted revision | Current authorisation is still rechecked (current auditor context, own assignment, current accreditation and identity). A revoked or foreign actor is refused exactly as today. The retained receipt is returned unchanged, and nothing is re-executed. |
| `FindStatementVerificationOperation` lookup | The same `withAssigned()` | The receipt must also be target-bound: the journal row's `target_id` equals the route assignment, and its `actor_key` equals the current actor. Otherwise `OPERATION_NOT_FOUND`. |
| A **new** `statement.verify` effect (no retained row) | Inside the effect, after replay lookup and under the held locks: `status = accepted`, otherwise `ASSIGNMENT_NOT_ACCEPTED` (403) | New verification writes stay restricted to accepted engagements. A completed engagement can never take a second verification through a new request ID. |

The fingerprint is unchanged. The replay or new decision is made inside the journal's advisory lock, as `EloquentOperationJournal::execute()` already does.

### R1.6 Staying out of the monthly report paths

| Path | Change |
|---|---|
| `requestMonthly()` `$dispatched` | Counts only `kind = routine AND purpose = monthly`. |
| Report start (`EloquentAuditReportStore`) | Refuses `AUDIT_REPORT_NOT_APPLICABLE` (409) for a `statement_review` engagement before any draft is written. The application requirement and the monthly/flash creation, steps, seal and publication are unchanged. |
| `EloquentBusinessAuditWindows::upcoming()` | Ignores `statement_review` engagements. The Business sees review status from the readiness projection. |
| Overdue/SLA | No change. `EloquentOverdueAudits` keys on `complete_by`, which is null for every routine engagement. Routine `visit_by` is recorded but not enforced today, and **this design delivers no new SLA enforcement.** |

### R1.7 What #396 step 2 may do before this design is approved [B8, readiness part]

Step 2 is limited to:
- the reviewed-save preconditions and fingerprint (contract §5);
- **raw readiness metadata**: evidence revision, manifest hash, current transcription, declared coverage, the existing open engagement's id/kind/status, and `source_verified`.

Step 2 does **not** compute `ready_to_request`, `reasons` for review applicability, or `allowed_actions` for the review request. Those implement R1.3 and ship with step 3, after approval. Until then the projection returns `ready_to_request: null` and no request action.

### R1.8 Tests the implementation PRs must add

1. **New business, full path.** A brand-new verified Business goes through statement review, accept and current verification. The engagement is `completed` in the verification's commit. The partner's `active_count` drops by one, `consecutive_reports` is unchanged, and no report or application exists.
2. **Lost response.** Inject a lost response after commit, then retry with the same request ID: the original receipt comes back. Lookup returns it too. A foreign auditor, a revoked accreditation and a wrong route assignment each get `OPERATION_NOT_FOUND` or a refusal. A new request ID on the completed engagement gets `ASSIGNMENT_NOT_ACCEPTED`.
3. **Re-review.** After completion, amend an original, then the transcription, then change the mandate. Each makes the verification non-current. A second `statement_review` is requestable, completes and releases workload without an application.
4. **Rotation.** Three completed reviews by one partner do not count toward three consecutive reports. Monthly rotation behaves as before.
5. **Competition with monthly and flash.** Cover:
   - a monthly engagement open → review refused `AUDIT_ENGAGEMENT_OPEN`;
   - a review open → the hourly monthly run skips the Business, then dispatches after completion in the same month;
   - issuance in the current month, and the Kigali month boundary;
   - an existing completed monthly history;
   - a closed latest engagement.
6. **Report paths.** Report start on a review is refused with no draft written. `upcoming()` returns null for a review.
7. **Concurrency.** Real PostgreSQL races between:
   - two staff requests;
   - review request and `requestMonthly`;
   - verify and redispatch, close, decline or conflict.

   Each race has one winner, and nothing completes from a stale party.
8. **Regression.** Existing monthly and flash suites pass unchanged.

---

## R2. Aggregate hold expiry (#383)

### R2.1 What the sweep guarantees today, and what it doesn't

`primary:expire-reservations` runs every minute. `withoutOverlapping(5)` is the mutex expiry if a run dies, not a delay on normal runs. The default limit is 100, and each candidate is settled in its own transaction, with failures recorded in `primary_expiry_failures`.

**Correctness never depends on the sweep.** Confirmation refuses at or after `expires_at` under locks. **The sweep is not a release-time guarantee.** Revision 1's "about one tick" claim is withdrawn.

### R2.2 One Business-first acquisition order for every settlement path [B3]

Every aggregate settlement follows the contract's §8 phases. This is true whether it comes from the trusted sweep, the explicit investor release, or the pre-command settlement in R2.5.

0. **Discovery, holding no locks.** Read the checkout's membership: its child reservation IDs, their campaign IDs and those campaigns' Business IDs. Compute a membership snapshot hash. None of this is treated as authoritative.
1. **Lock every distinct `BusinessProfile`**, in ascending ID order, through the same helper campaign loading uses (`EloquentPrimaryCampaignSource`, lines 88–91), so the order matches reserve, confirm and funding.
2. **Lock the actor.**
   - Investor-triggered paths lock the acting User, then the ordered Party authority set.
   - The trusted sweep has no acting User and locks the checkout owner's Party only.
   - Then lock the cart root, the checkout root and the parent key, in that fixed order.
3. **Revalidate.** Re-read membership under the locks and compare it with the discovery snapshot. If it changed, **roll back the whole transaction and retry from step 0 holding no locks**. Allow at most 3 attempts, then record `MEMBERSHIP_UNSTABLE` as a failure (R2.6). A Business lock is never added after step 1.
4. **Lock campaigns, then roots.** Lock every member campaign in ascending ID order, with the retained publication, closure and funding gates. Then lock every child reservation root and commitment in deterministic campaign/root order, reusing the full existing Primary lock protocol, including terminal claim-retirement records.
5. **Lock wallets.** Collect **every affected wallet** (the investor's, plus any terminal-claim owner's) and lock them in ascending Party ID order.
6. **Settle** (R2.3).

Tests: real PostgreSQL races of aggregate expiry against reserve, confirm, release, single-line expiry, funding, closure and issuance, using aggregates that span **several Businesses** in reversed campaign order. They must show no deadlock inversion, no overclaim and no partial release.

### R2.3 Atomic group settlement and its evidence [B4]

Within the locks from R2.2, in **one transaction**:

1. If the checkout is already terminal (`confirmed`, `released` or `expired`), return its retained terminal evidence and stop.
2. Recheck `now >= expires_at` under the lock. If the hold has not elapsed, return `NOT_DUE` with no effect.
3. Release every child.

The release takes one of two **distinct evidence paths**. Each keeps today's actor, command and target constraints (`2026_09_28_152823_bind_primary_evidence_to_command_actors.php`):

| Path | Child evidence | Aggregate evidence |
|---|---|---|
| **Trusted expiry**: the sweep, or pre-command settlement (R2.5) | Each child gets the existing trusted-expiry version: `state = expired`, **`operation_id = NULL`**. The constraint trigger already admits exactly this (lines 27–31). No investor command receipt is needed or invented. | A new **trusted settlement record** (R2.4) with its own durable identity. |
| **Explicit release** before expiry (`ReleaseCartReservation`) | Each child gets a `released` version bound to its **own distinct internal child `primary.release` command receipt**, as contract §8 (lines 181–183) requires. A parent cart or checkout ID never substitutes for a child receipt. | The parent `cart.release` operation and the complete parent/child binding. |

A release command that arrives **after** `expires_at` does not take the release path. It performs trusted expiry and then returns `CART_RESERVATION_EXPIRED` (R2.5).

Then, in the same commit:
- append the aggregate's terminal version (`expired` or `released`);
- write the evidence;
- write the change-feed entries.

A deferred constraint trigger checks that the evidence's child set equals the aggregate's membership exactly, with each child having the matching terminal version, and that nothing is missing, extra or reused. An injected failure on any child, the evidence or the deferred check rolls everything back.

The per-child sweep skips any reservation that belongs to an aggregate, so a child is never expired alone.

### R2.4 The durable trusted settlement identity [B4]

```text
primary_cart_settlements
  id               ulid PRIMARY KEY            -- the trusted settlement identity
  checkout_id      ulid NOT NULL UNIQUE        -- at most one trusted expiry per aggregate
  settled_at       timestamptz NOT NULL
  expires_at       timestamptz NOT NULL        -- copied, for lag measurement
  path             text NOT NULL CHECK IN ('sweep','pre_command')
  causal_request   jsonb NULL                  -- {actor_key, command, request_id}; set for pre_command, never an operation_id
  child_count      int  NOT NULL
  children_sha256  char(64) NOT NULL           -- CanonicalJson of ordered child IDs + their expired version revisions
```

- **Identity.** `id` is generated in the settlement transaction and is independent of any command journal.
- **Causal reference.** The pre-command path records the *request identity* that triggered it, not an `operation_id`, because the journal only creates the operation ID after replay lookup and retains it after the effect.
- **Idempotency and retries.** `UNIQUE(checkout_id)` makes a second settlement impossible. A retry re-enters at R2.3 step 1 and returns the retained row.
- **Crash between settlement commit and command start.** This is harmless. The hold is settled and evidenced, and no command row exists. The client's retry with the same request ID finds no retained receipt, sees the aggregate already terminal, and runs as a new command against current state.

### R2.5 When settlement runs, and side effects of reads [B8]

**A GET never settles.** Revision 1 had the cart read trigger settlement; that is withdrawn. The cart projection and `GetCartReservation` are read-only. An elapsed, unsettled aggregate is shown as `release_pending`, with its `expires_at` and no actions except Release. The reviewed read/quote boundary is unchanged.

Settlement runs only in:

1. **The trusted sweep.** This is the recovery path that **always** exists. It settles any elapsed aggregate from authenticated original hold evidence, **whatever the investor's current authority is**: revoked, frozen, switched context or lost app access. It needs no Investor session and admits no new investment.
2. **Pre-command settlement**, for the investor commands `QuoteInvestorCart`, `reserveAll` and `ReleaseCartReservation`. The order is:
   1. **Authorise** the current Investor as today.
   2. **Replay first.** Look up a retained receipt for (actor key, command, request ID) through a read-only `OperationJournal::retained()` lookup (new, mirroring the private `lookup()`). If one exists, return it through `execute()`'s normal replay. **No cleanup runs**, so a retry always returns its original receipt.
   3. **Otherwise, settle.** If the Party has an elapsed aggregate, settle it in a **separate transaction that commits before the command's journal transaction starts**, with `path = pre_command`. A settlement failure therefore cannot alter a journaled command, and a rolled-back command cannot undo a settlement.
   4. **If settlement fails, refuse.** The command refuses with `CART_RESERVATION_RELEASE_PENDING` (409), `financial_effect: none`, and **its own journaled rejection receipt**. That receipt is distinct from the settlement evidence and does not claim that settlement happened. The failure is recorded for the sweep (R2.6).
   5. **On success, run the command** through `execute()` as a new command.
3. **An explicit release after expiry** settles by trusted expiry (R2.3) and returns `CART_RESERVATION_EXPIRED` with a reference to the settlement `id`. It never returns `CART_RELEASED` for an elapsed hold.

Revision 1's "never blocks" wording is qualified: an elapsed hold does not block the investor's next cart command **when settlement succeeds**. When it fails, the command refuses with no effect, and recovery is the sweep's job.

### R2.6 Retry fairness, backlog and service targets [B7]

**One ordering.** The sweep's aggregate pass selects elapsed, non-terminal aggregates where `next_attempt_at IS NULL OR next_attempt_at <= now`, ordered by `COALESCE(next_attempt_at, expires_at) ASC, checkout_id ASC`, up to the limit.

**Bounded retry.** On failure, `primary_cart_settlement_failures(checkout_id PK, attempts, first_failed_at, last_attempted_at, next_attempt_at, exception_class, reason_code, resolved_at)` is updated:
- `attempts += 1`;
- `next_attempt_at = now + min(2^attempts minutes, 60 minutes)`.

A failing batch therefore moves behind fresh ones, and repeated failures of the oldest batches cannot use up the limit and starve the rest.

**Poison.** After 8 attempts the row is marked `quarantined`. It is still retried hourly, alerts immediately (below), and does not count against the limit.

**Resolved failures.** A later successful settlement sets `resolved_at` in the same transaction. Resolved rows are retained for audit.

**Backlog.** If more aggregates are due than the limit, the remainder carries to the next run in the same order. The limit is configurable from 1 to 1000.

**Measurement.**
- Every settlement records `settled_at - expires_at` (settlement lag).
- Each sweep run records a heartbeat: `{run_started_at, finished_at, examined, settled, failed, backlog_remaining}`.

**Targets, proposed for agreement and not verified capacity.**
- Settlement lag p99 ≤ 10 minutes.
- No elapsed aggregate unsettled for more than 30 minutes.

Revision 1's 5-minute p99 is withdrawn, because a dead run's 5-minute mutex expiry plus the next run does not fit inside it. These numbers are starting points, to be adjusted after measuring on staging under seeded load.

**Alerts and ownership.** The repository has no paging integration today; existing single-line expiry failures only call `Log::error`. This design emits each condition below as a `Log::critical` and as a staff-visible operations metric. A log alone does not establish a response, so **the owner and the delivery route (paging or chat) must be named by @hussain4real or Erastus before #383 step 4 merges**. Until then, this design does not claim that anyone responds. The conditions:
- heartbeat older than 5 minutes (scheduler liveness);
- any aggregate unsettled for more than 30 minutes;
- any quarantined row;
- a backlog that grows across three consecutive runs.

### R2.7 Tests the implementation PRs must add

1. **Atomicity.** Two- and three-child aggregates **across different Businesses** expire in one commit. An injected failure at each child, the evidence, the heartbeat or the deferred check rolls everything back.
2. **Races.** Real PostgreSQL races of trusted settlement against reserve, confirm, explicit release, single-line expiry, funding, closure and issuance: one terminal outcome, cash conserved, no inversion. Membership change between discovery and lock retries from no locks.
3. **Crashes.** Crash or timeout after settlement commit and before command start, then retry with the same request ID: no second settlement, and the command runs as new. Retry after a retained receipt: the original receipt comes back and no cleanup runs.
4. **Revoked authority.** Revoked or frozen Investor authority: the sweep still settles; investor commands are refused, as today.
5. **Evidence.** Trusted expiry children carry `operation_id NULL, expired`. Explicit release children carry distinct `primary.release` receipts. Mixing the two fails the deferred check.
6. **Poison and backlog.** A poison batch, a backlog larger than the limit, recovery after failure, `resolved_at`, and every alert condition.
7. **Reads.** A GET with an elapsed hold shows `release_pending` and writes nothing.

---

## Implementation order

1. **#396 step 2** (independent of this design): reviewed-save preconditions and raw readiness metadata only (R1.7).
2. **#396 step 3** (needs R1 approval): `purpose`, `done()`, in-verify completion, the receipt authority split, the consumer filters, the report, monthly and window guards, then `audit.statement_review.request` and readiness applicability.
3. **#396 steps 4–5**: transports, then screens.
4. **#383**, in the contract's order, with R2 inside step 4. The alert owner must be named before that step merges.

Each step is its own PR to `dev`, with @hussain4real as the required reviewer. This document authorises no merge, deployment, financial activation, new permission, shared or live data change, or #336 work.
