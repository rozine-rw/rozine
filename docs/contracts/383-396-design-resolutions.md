# Design resolutions for #396 and #383

Proposed for design review by @hussain4real (design authority), in answer to [#433 comment 6507027925](https://github.com/rozine-rw/rozine/pull/433#issuecomment-6507027925). This file is documentation only. It changes no runtime behaviour, permissions, settings or data, and it authorises nothing beyond what that comment authorises. It amends `docs/contracts/396-initial-statement-verification.md` and `docs/contracts/383-atomic-cart-checkout.md` (draft PR #433) only where it says so.

Source references are to `dev` at `e8af42fbf824bc44239bd7d4bc86a406b0667586`.

---

## R1. Initial-audit lifecycle under routine dispatch (#396)

### R1.1 The coupling today

Selecting `kind = routine` alone is not enough. Routine is currently coupled to the monthly report in four places:

| Coupling | Where | Effect on a first audit if left as is |
|---|---|---|
| Completion | `EloquentAuditAssignmentStore::finish()` and `published()` | An accepted engagement completes only when its newest report is sealed and published. A first audit produces a statement verification, not a report, so it would stay `accepted` for ever. That holds the partner's workload (`workload()` counts `accepted`), and every later request on the Business returns `AUDIT_ASSIGNMENT_RESUMED`. |
| Monthly dispatch | `requestMonthly()` | Any routine assignment created this Kigali month counts as `$dispatched`, and any non-completed engagement blocks the monthly offer. |
| Report kind | `EloquentAuditReportStore` (start, around line 197) | Starting a report on a routine engagement creates a **monthly** report for the previous month, starting at step `statements`. |
| Business reporting window | `EloquentBusinessAuditWindows::upcoming()` | Any open routine engagement is shown to the Business as a monthly reporting window with a seal-by date. |

### R1.2 Resolution: keep `kind = routine`, add an explicit `purpose`

The engagement keeps `kind = 'routine'`, so `AuditAssignmentClock` gives it the preferred 4-hour accept window per offer and a visit due 48 hours after acceptance, with no code change there. A new immutable field in the assignment state records why it exists:

```text
purpose: 'monthly' | 'initial_verification'      // absent on existing rows = 'monthly'
```

- **Who sets it.** Only the new `audit.initial.request` wrapper writes `initial_verification`. `requestMonthly()` writes `monthly`. The existing general `audit.assignment.request` keeps its current behaviour and writes `monthly`, so no existing caller changes meaning.
- **Immutability.** `purpose` is set at creation, copied into every `AuditAssignmentVersion` snapshot, and never changed by offer, accept, decline, redispatch or close. A forward-only migration adds nothing to existing rows: readers treat a missing `purpose` as `monthly`.
- **Eligibility for the wrapper.** `audit.initial.request` refuses `INITIAL_AUDIT_NOT_APPLICABLE` (422) when the Business:
  - already has a completed engagement of any purpose;
  - has any statement verification current under `storedVerification`;
  - or appears in `MonthlyAuditBusinesses::due()`.

  The first audit is therefore only ever for a Business with no audit history and no issued notes. These are exactly the Businesses that monthly dispatch never selects, so the two can never compete for the same month.

### R1.3 How an initial engagement completes and releases workload

`finish()` and `published()` become a single `done(AuditAssignment $record): bool` with two branches. The monthly branch is unchanged, character for character.

- **monthly**: the existing rule. The newest report is sealed, there is no draft amendment, and the report is published.
- **initial_verification**: complete when **all** of these hold:
  1. `status = accepted`;
  2. there is a statement verification appended under **this** assignment ID, by **this** assignment's `party_id`, at this assignment's current revision binding;
  3. that verification is **current** under the existing `storedVerification` / `withVerificationValidity` checks, at the moment `done()` runs and under the same locks.

Completion stays where it is today. It is triggered lazily inside the Business-locked dispatch scope (`request`, `requestMonthly`), and additionally **inside the `statement.verify` effect itself**. When `RecordStatementVerification` commits a current verification for an `initial_verification` engagement, the same transaction persists `complete()` with command `audit.assignment.complete` and the verification ID as the reason. This releases the partner's workload in the same commit as the verification. Nothing waits for a later request.

What completion does **not** do:
- It does not create, seal, publish or co-sign a report, and it does not count as one. The report register, earnings, co-sign windows and BR-42 publication gate are untouched.
- It does not mark the Business eligible. Eligibility still comes only from `EloquentBusinessApplicationStore` reading current verified evidence over the full `UnderwritingObservationWindow`. A shorter verified window completes the engagement (factual review is done) but remains `refused` for underwriting, as agreed for P396-03.
- It does not require a submitted application. Nothing in this branch reads `BusinessApplication`.

If the verification later becomes non-current (a new original or transcription, a mandate change and so on), the **completed engagement stays completed** and its history is immutable. A new factual review needs a new engagement. Because the Business now has a completed engagement, it is no longer eligible for `audit.initial.request`; the re-review goes through the existing general request (`audit.assignments.manage`). This is deliberate: "initial" happens once per Business.

### R1.4 Keeping it out of the monthly report paths

| Path | Change |
|---|---|
| `requestMonthly()` `$dispatched` | Counts only `kind = routine AND purpose = monthly`. A completed initial engagement neither suppresses nor stands in for a monthly audit. An open initial engagement still blocks it, through the existing "non-completed engagement" rule, but by R1.2 that Business is never in `due()`. |
| Report start (`EloquentAuditReportStore`) | Refuses `AUDIT_REPORT_NOT_APPLICABLE` (409) for an `initial_verification` engagement before any draft is created. Monthly and flash report creation, steps, seal and publication are unchanged. |
| `EloquentBusinessAuditWindows::upcoming()` | Ignores `initial_verification` engagements: no monthly window and no seal-by date are shown. The Business sees the initial-audit status from the #396 readiness projection instead. |
| Report register and earnings | No change. They read reports, and an initial engagement has none. |
| Overdue and SLA (`EloquentOverdueAudits`) | No change. It keys on `complete_by`, which `AuditAssignmentClock` leaves null for every routine engagement. `visit_by` is recorded but not enforced for any routine engagement today, and this proposal does not add enforcement. |

### R1.5 Tests the implementation PRs must add

1. A brand-new verified Business runs initial request, accept and current verification. The engagement is `completed` in the verification's own commit, the partner's `active_count` drops by one, and no report row exists.
2. A current verification by a different party, or under a different assignment, does not complete the engagement.
3. A non-current verification does not complete the engagement. A later invalidation leaves a completed engagement completed.
4. In a month where a Business's initial engagement completed, `requestMonthly()` still dispatches normally once the Business becomes due. An initial engagement never counts as `$dispatched`.
5. Starting a report on an initial engagement refuses, with no draft written. `upcoming()` returns null for it.
6. `audit.initial.request` refuses for a Business with a completed engagement, with a current verification, or listed in `due()`.
7. Existing monthly and flash suites pass unchanged.
8. On real PostgreSQL, verify racing redispatch, close or decline gives one winner, and completion never happens from a stale party.

---

## R2. Aggregate hold expiry (#383)

### R2.1 What the existing sweep already guarantees, and what it doesn't

`primary:expire-reservations` runs every minute with `withoutOverlapping(5)` and a default limit of 100. `EloquentPrimaryReservations::sweepExpired()` handles each candidate in its own transaction. A failure is recorded in `primary_expiry_failures`, which orders retries fairly, and the sweep carries on with the rest. This means:

- **Correctness never depends on the sweep.** Confirmation already refuses at or after `expires_at` under locks. An elapsed hold can never become a purchase, whenever it is released.
- **The sweep is not a release-time guarantee.** A run can be skipped by overlap or deploy, be bounded by `limit`, or fail on one row. "About one tick", as I wrote before, was wrong as a guarantee, and I withdraw it.

### R2.2 Atomic group release

A new `ExpireCartReservation` application action settles one aggregate checkout in **one transaction**, using the contract's §8 lock phases:

1. Lock the checkout root `FOR UPDATE`. If it is already terminal (`confirmed`, `released` or `expired`), return its retained terminal evidence and stop. Nothing is re-executed.
2. Recheck `now >= expires_at` under the lock. If the hold has not elapsed, return `NOT_DUE` with no effect.
3. Release **every** child through the existing per-child release internals (not the public journaled `expire()` entry point). Each child keeps its own ordinal claims, wallet hold return and version row. Every child is released in the same savepoint, or the whole transaction rolls back.
4. Append the aggregate's terminal version `expired`, record its **group expiry evidence** row and write the change-feed entries, all in the same commit.

There is no state in which some children are released and others still held. A child can never be expired on its own once it belongs to an aggregate: the per-child sweep skips children with an aggregate membership row and leaves them to the group path. This is the membership guard from contract §8.

### R2.3 Retained, idempotent evidence

New table `primary_cart_expiries`:

```text
checkout_id   ulid  PRIMARY KEY            -- one terminal expiry per aggregate, ever
expired_at    timestamptz NOT NULL
trigger       text NOT NULL CHECK IN ('sweep','lazy','release_command')
child_count   int  NOT NULL
children_sha256 char(64) NOT NULL          -- CanonicalJson of ordered child reservation IDs + release versions
operation_id  ulid NULL                    -- set when triggered by an investor command (lazy/release)
```

- The primary key makes a second settlement of the same aggregate impossible. A deferred constraint trigger requires `child_count` and `children_sha256` to match exactly the aggregate's member children, each with a terminal release version, at commit.
- A retry after a timeout re-enters at step 1 and returns the retained row.
- Failures reuse the existing pattern. A new `primary_cart_expiry_failures` table records `(checkout_id, last_attempted_at, exception_class, reason_code)`, retries are ordered `last_attempted_at ASC NULLS FIRST`, and an error is logged. A failed aggregate never blocks the others.

### R2.4 When settlement runs

1. **Sweep.** The same scheduled command gains an aggregate pass before the per-child pass, with its own limit (default 100, maximum 1000), processing oldest `expires_at` first.
2. **Lazy settlement before an investor action.** The new cart actions (`QuoteInvestorCart`, `reserveAll`, `ReleaseCartReservation`) and the cart read, when the Party has an aggregate past `expires_at`, settle it first:
   - **Separate transaction, committed before the investor's command starts.** It never runs inside the command's journal effect. A failed settlement therefore cannot roll back or alter a journaled command, and a rolled-back command cannot undo a settlement.
   - **Same action, same locks** as the sweep (R2.2). The sweep and the lazy path race safely: the loser sees the retained evidence at step 1.
   - **On settlement failure, the investor's command refuses** with `CART_RESERVATION_RELEASE_PENDING` (409), with `financial_effect: none` and no journal effect. The failure is recorded as in R2.3 for the sweep to retry. The UI shows "Releasing your earlier hold. Try again in a moment." It never quotes or reserves on top of a hold it could not settle, so P383-01's one-live-hold rule can't be bypassed.
   - **Lazy settlement is never authority.** It runs only for the authenticated Party's own aggregate, after the normal Investor authorisation, and it records `trigger = 'lazy'` with that command's `operation_id`.
3. **Explicit release** (`ReleaseCartReservation`) of an already-elapsed aggregate settles it as an expiry (`trigger = 'release_command'`) and returns `CART_RESERVATION_EXPIRED` with the retained evidence. It never returns `CART_RELEASED` for an elapsed hold.

### R2.5 Service target

- **Investor-visible guarantee.** An elapsed hold never blocks the investor's next cart action, because that action settles it first or refuses with `RELEASE_PENDING` and no effect. Funds are never shown as held past the next interaction unless settlement itself is failing.
- **Background target.** p99 settlement within **5 minutes** of `expires_at` under normal load. That allows a skipped run, a 5-minute overlap lock and one retry.
- **Alert.** An aggregate still unsettled **15 minutes** after `expires_at`, or any row in `primary_cart_expiry_failures` older than 15 minutes, raises an operations alert (`Log::critical`, plus the same channel the existing expiry failures use). This is a target to monitor, not a guarantee. The safety property (no purchase after expiry, no partial release) does not depend on it.

### R2.6 Tests the implementation PRs must add

1. Two- and three-child aggregates expire with every child released in one commit. An injected failure on the n-th child, on the evidence row, or on the deferred check at commit rolls everything back, and nothing is released.
2. Sweep versus lazy versus explicit release versus confirm, on real PostgreSQL: there is exactly one terminal outcome and cash is conserved.
3. The per-child sweep never touches an aggregate member.
4. A lazy settlement failure refuses the quote or reserve with `RELEASE_PENDING`, no journal effect and no new hold. The sweep then retries and succeeds.
5. A retry and replay after a timeout returns the same retained evidence.
6. A row unsettled past 15 minutes raises the alert.

---

## Implementation order once these are approved

1. #396 step 2: reviewed-save preconditions and the readiness projection. This is independent of R1 and can start now.
2. #396 step 3: `purpose`, `done()`, the in-verification completion, the monthly, report and window guards (R1), then the `audit.initial.request` wrapper.
3. #396 steps 4–5: transports, then screens.
4. #383, in the contract's order, with R2's group expiry inside step 4.

Each step is its own PR to `dev`, with @hussain4real as the required reviewer. No merge, deployment, financial activation, new permission or shared-data change is implied by this document.
