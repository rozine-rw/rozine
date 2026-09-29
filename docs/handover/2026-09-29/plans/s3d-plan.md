# S3-D plan: disbursement and issue

## What the code has today (checked on origin/dev plus the #172 checkout)

- **Staff authority.** `AuthorizeStaffPermission::check(int $userId, string $permission)` and `::handle(int $userId, string $permission, Closure $op)` wrap `EloquentIdentityAccessStore::withStaffPermission`. That locks `users` FOR UPDATE and requires `party_id IS NULL`, a verified email, confirmed MFA and an enabled `StaffAccount`.
  - `app/Domain/Identity/StaffPermission.php` has no `disbursements.*` permissions.
  - Its `superadmin` row grants every existing permission.
  - `StaffAccount` holds only `user_id`, `enabled` and `roles`. Nothing links a staff account to a person.
- **Step-up.** `EloquentAuditStepUp::issue/consume` uses `audit_step_up_proofs`. It stores `proof_sha256` and `credential_binding` (a hash of the TOTP secret, confirmed time and password), gives the proof 5 minutes, and consumes it by setting `consumed_at` under FOR UPDATE.
  - `STEP_UP_INVALID` and `STEP_UP_EXPIRED` are 403. A wrong code comes from `FortifyAuthenticator` as `STEP_UP_CODE_INVALID` (422).
  - The throttle is `audit-step-up`: 5 per minute per account, 20 per minute per IP.
  - **The seal puts `proof_sha256` into the journal fingerprint** (`EloquentAuditReportStore.php:93`). Approve must not copy this, or a same-key replay would need the consumed proof again.
- **Journal.** `OperationJournal::execute(actorKey, actorUserId, command, requestId, targetType, targetId, permittedInput, authorize, operation)`.
  - `authorize` runs on every replay. A `CommandRejection` thrown inside `operation` is recorded.
  - `staff:<id>` keys are already accepted.
- **Funding facts.**
  - `business_campaigns`, `business_exposure_reservations` and `business_campaign_closures` are immutable.
  - The closure `phase` CHECK allows only `cancelled` or `expired`.
  - The closure, exposure and campaign triggers all lock `business_profiles` FOR UPDATE. The expiry sweep also locks the Business before the campaign.
  - Exposure can only be reserved or released. There is no "outstanding" record.
  - **Nothing stores a verified Business payout destination, and nothing stores conditions precedent.**
- **Wallet port (S3-B).** `WalletPostings` offers `lockForParty`, `hold`, `commit`, `release` and `refund`. The kinds are the four `PrimaryPosting::MOVEMENTS`, and the only system accounts are `deposit_clearing` and `deposit_fee_revenue`.
- **Admin page.** `C3AdminDisbursementsProps`, `C3DisbursementDetail`, `DisbursementStepUp{purpose, route|null}` and `approval_binding` are in `resources/js/types/admin.ts:703-878`. `StaffApplicationsResource` currently sends `nav.disbursements: null`.

New code goes in `App\{Domain,Application,Infrastructure}\Disbursement`.

## Commits

**1. Pure domain** (`app/Domain/Disbursement/`)
- `DisbursementState` folds the event log into the state, revision, maker and hold placer. The states are `ready`, `awaiting_second_approver`, `on_hold` (keeping the prior state), `queued`, `dispatched`, `succeeded` and `failed_closing`.
- Guards:
  - approve or reject needs actor ≠ maker (`SELF_APPROVAL_FORBIDDEN`);
  - release needs actor ≠ placer;
  - hold, reject or release from `queued` onwards is `DISBURSEMENT_IN_FLIGHT`.
- `PayoutOutcome::transition(current, event)` is MC-08 for payouts:
  - from `pending` or `unknown`: applied;
  - same state: duplicate;
  - `succeeded` then `failed`: after_final, which opens an exception and never reverses anything;
  - `failed` then `succeeded`: conflict, an exception that blocks the refund;
  - same event id with a different content hash: key_conflict;
  - an authenticated event that doesn't match: unverifiable, which is retained and blocks.
- `Reconciliation::decide(intent, events)` returns `matched_success`, `matched_failure`, `exception` or `open`, with RWF 0 tolerance (§11.5).
- `IssueSchedule::dates(effectiveAt, termMonths)`: the Kigali date, then each due date is `anchor->addMonthsNoOverflow(i)` counted from the anchor each time. That gives Jan 31 → Feb 28/29 → Mar 31 (§11.4).
- `IntentDigest` is sha256 over the JCS form of `{disbursement_id, revision, campaign_id, exposure_reservation_id, amount, currency, destination_sha256, commitments_digest, provider, environment}`.
- Tests: `tests/Unit/Disbursement*Test.php`.

**2. Schema** (`database/migrations/2026_09_29_*`)
- Conventions follow S3-B: ULIDs, `decimal(12,0)`, payload plus sha256, plpgsql rejection of UPDATE/DELETE, and `down()` refuses once rows exist.
- Tables:
  - `disbursements`: one per campaign (UNIQUE `business_campaign_id`, `exposure_reservation_id`), with amount, `destination_sha256`, masked destination and funding snapshot.
  - `disbursement_events`: UNIQUE(`disbursement_id`, `revision`), with kind, actor, `operation_id` and reason.
  - `disbursement_step_up_proofs`: `proof_sha256` UNIQUE, the bindings, `credential_binding`, `expires_at`, `consumed_at` and `consumed_operation_id`. A trigger allows only one change of `consumed_at` from NULL to a value.
  - `disbursement_intents`: UNIQUE `disbursement_id` (no reauthorize, Q12), `operation_id`, `provider_reference_sha256`.
  - `disbursement_dispatches`: phases `queued`, `claimed`, `sent`, `unsent` and `recheck_failed`, UNIQUE(`intent_id`, `phase`).
  - `disbursement_provider_calls`: append-only `send` or `query` rows. These are the send-count evidence.
  - `disbursement_provider_events`: UNIQUE(`provider`, `event_id`, `content_sha256`), with source `callback`, `query` or `requery`, and a disposition.
  - `disbursement_reconciliations`: UNIQUE `intent_id`.
  - `disbursement_closings`: UNIQUE `disbursement_id`, kind `issued` or `failed_closing`.
  - `primary_holdings`: UNIQUE `commitment_id`, with ordinals, rights, terms, schedule, `issued_at`, `disbursement_effective_at`, `effective_date` and `receipt_id`. Its owner is question 9.

**3. Staff step-up exchange**
- Route: POST `admin/disbursements/{disbursement}/step-up`, named `staff.disbursements.step-up` and `api.v1.staff.disbursements.step-up`. Middleware: `throttle:disbursement-step-up` (the same limits as the seal) and `cache.headers:private;no_store`.
- Request: `ConfirmDisbursementStepUpRequest {expected_revision, intent_digest, code}`. The TS type also carries `request_id`, which I propose dropping (question 11).
- `EloquentDisbursementStepUp::issue` runs inside `handle(userId, 'disbursements.approve')`. It locks the disbursement and refuses in this order:
  1. the existing staff codes, or a scoped 404;
  2. `SELF_APPROVAL_FORBIDDEN`;
  3. `VERSION_CONFLICT`;
  4. `DIGEST_STALE`;
  5. `STEP_UP_CODE_INVALID` or `MFA_REQUIRED`.
- It returns `{proof, expires_at}` as JSON. The exchange is never journaled or logged.
- `consume(userId, disbursementId, revision, amount, destinationSha, digest, proof)` is called only inside approve's first execution. It checks against `VerifyAuthenticator::binding`.

**4. Commands**
- All command routes are POST `admin/disbursements/{disbursement}/{authorize|approve|reject|hold|release-hold|requery}`, named `staff.disbursements.*` and mirrored under `api.v1.` with the `staff:disbursements:manage` ability.
- The lookup is GET `admin/disbursements/operations/{request_id}?command=`, named `staff.disbursements.operations.show`.
- `permittedInput` is `{expected_revision, reason}` only. The target comes from the route.

| Command | Permission | Result |
|---|---|---|
| authorize | `.authorize` | `DISBURSEMENT_AUTHORIZED` |
| approve (+`step_up_proof`) | `.approve`, and ≠ maker | `DISBURSEMENT_INTENT_RECORDED` (queued), or `CAMPAIGN_FAILED_CLOSING` |
| reject | `.approve`, and ≠ maker | `DISBURSEMENT_AUTHORIZATION_REJECTED` |
| hold | `.hold` | `DISBURSEMENT_HELD` |
| release_hold | `.approve`, and ≠ placer | `DISBURSEMENT_HOLD_RELEASED` |
| requery | `.requery` | `PROVIDER_QUERY_RECORDED` |

- Refusals:
  - 422: `VALIDATION_FAILED` for an empty or control-character reason;
  - 403: `STEP_UP_REQUIRED`, `STEP_UP_INVALID`, `STEP_UP_EXPIRED`, `SELF_APPROVAL_FORBIDDEN` and the existing staff codes;
  - 409: `VERSION_CONFLICT`, `IDEMPOTENCY_CONFLICT`, `DISBURSEMENT_IN_FLIGHT`, `PROVIDER_OUTCOME_UNRESOLVED`, `POLICY_INPUT_REQUIRED` and `DISBURSEMENT_STATE_INVALID` (for example, requery after reconciliation);
  - 404: scoped;
  - 503: `RETRYABLE_CONTENTION`.
- Proposed role mapping in `StaffPermission` (question 3):
  - treasury: view, authorize, hold, requery;
  - approver: view, approve, hold;
  - compliance: view, hold;
  - superadmin: view only.
- The mapping tests land before any route is registered.

**5. Worker, provider and reconciler**
- `PayoutProvider` has `name()`, `idempotentSends()`, `send(PayoutInstruction): bool`, `query(PayoutInstruction): ?VerifiedPayoutEvent` and `verify(array): VerifiedPayoutEvent`.
- `SyntheticPayoutProvider` signs with HMAC over JCS and is bound only when a `SyntheticDisbursementGuard` allows it (local or testing, `live_money_enabled === false`). Otherwise `UnavailablePayoutProvider` is bound, as in the S3-B pattern.
- Approve records the intent and a `queued` dispatch row in the same transaction, then `DB::afterCommit(fn () => dispatch(intentId))`. A rollback discards it.
- `DispatchDisbursements` (`disbursements:dispatch`) throws if `DB::transactionLevel() > 0`.
  - Its claim transaction locks the whole chain and rechecks.
  - A failed recheck writes `recheck_failed` and does the failed closing in that same transaction, with no claim.
  - A pass commits `claimed`. The send happens outside the transaction, then `sent` or `unsent` is recorded.
  - Once claimed, the disbursement is in flight. An interrupted claim is resent only if `idempotentSends()`; otherwise it becomes unknown and goes to the reconciler.
- `ReconcileDisbursements` (`disbursements:reconcile`, scheduled every minute with `withoutOverlapping` in `routes/console.php`) and requery both call `query()` on the same operation outside any transaction, then apply the result in their own transaction. Neither ever calls `send()`.

**6. The S3-C port** (`app/Application/Disbursement/Contracts/FundedCampaigns.php`)

```php
public function funded(?string $before, int $limit): array;                 // list<FundedCampaignRef>, no locks
public function lockFunded(string $campaignId): FundedCampaign;             // inside the caller's tx
public function recheck(FundedCampaign $c): RecheckResult;                  // passed|failed(causes)|unavailable
public function issue(FundedCampaign $c, VerifiedPayout $p, string $closingId): void;   // funded→issued, exposure committed→outstanding
public function failClose(FundedCampaign $c, list<string> $causes, string $closingId): void; // closure phase failed_closing, refunds, exposure release
```

- `FundedCampaign` binds:
  - campaign, Business and exposure ids;
  - principal and `funded_at`;
  - `commitments_digest`;
  - one entry per commitment: id, Party, origin operation, units, ordinal ranges, `UnitRights::toArray()`, `PrimaryTerms::toArray()` and principal;
  - `VerifiedDestination{id, token_sha256, masked, verified_at}`.
- Invariants: the commitment principals add up to the campaign principal and the exposure principal, and units = principal / 5,000.
- Until S3-C wires its adapter, `UnavailableFundedCampaigns` is bound and throws `FUNDING_SOURCE_UNAVAILABLE`, so everything fails closed.
- **Proposed lock order:** `business_profiles` → staff `users` (ascending id: actor, maker, placer) → campaign → `disbursements` → step-up proof → reservations and commitments (id order) → wallets (Party id order) → ledger. The worker and reconciler skip the staff step.
- **What S3-B's port lacks for issue:**
  1. There is no `primary_issue` kind. Proposed: `investor_committed` → a new system account `disbursement_settlement` (`wallet_id NULL`), following `primary_commit`.
  2. The `ledger_accounts` and `ledger_entry_source` CHECKs allow neither.
  3. `protect_primary_posting`, plus the amount binding in the 140000 migration, expect both lines in the entry's own wallet, and nothing makes issue and refund mutually exclusive terminals.
  4. There is no causal `cause_operation_id` provenance for a posting driven by a disbursement.
  5. `refund()` is documented for pre-funding only. A failed-closing refund is the same fee-free movement from committed to available, not a payout.

**7. Terminal effects** (a single transaction in the reconciler)
- A matched success does the following once, all in that transaction:
  - `FundedCampaigns::issue`;
  - `primary_issue` for each commitment;
  - one `primary_holdings` row for each commitment, with rights unchanged and a schedule built from `IssueSchedule`;
  - `effective_date` from the authenticated `effective_at`, never the callback's arrival time;
  - an `issued` closing and a `HOLDING_ISSUED` receipt per holding.
- A matched failure, or a failed recheck before dispatch, calls `failClose`.
- An exception, unknown or conflict refuses closing with `PROVIDER_OUTCOME_UNRESOLVED`.

**8. Admin Resource binding**
- `StaffDisbursementController` has `index`, `show`, `command`, `stepUp` and `operation`.
- `StaffDisbursementsResource` builds `C3AdminDisbursementsProps` and renders `admin/disbursements`:
  - the shell comes from the `StaffApplicationsResource` helpers, with `nav.disbursements` and `badges.disbursements` set;
  - per-viewer `allowed_actions` and `actions`;
  - `viewer_is_maker` and `viewer_placed_hold`;
  - `approval_binding`;
  - `links.operation` keeps a literal `{request_id}`;
  - there is no `preview_outcome`.
- `step_up.route` stays null until commit 3's tests pass. After that it is sent only when approve is allowed for this viewer.
- `ProviderOutcome` goes to the staff audience only.

**9. Tests**
- Feature tests (`tests/Feature/Disbursement*Test.php`):
  - replay versus a changed body;
  - self-approval, and a hold released by its own placer;
  - a stale, expired, foreign, reused or wrong-purpose proof, including an Auditor proof;
  - a replay without the proof that still rechecks lookup authority;
  - authority revoked between the step-up and approve;
  - outer rollback versus commit, with provider transaction level 0;
  - duplicate, conflicting, key-collision and unverifiable events;
  - requery where `send` count stays unchanged and `query` count rises by one;
  - exactly-once issue and refund, with Jan 31, Feb 29 and Kigali-midnight cases.
- `tests/Concurrency/DisbursementConcurrencyTest.php` (pcntl fork):
  - two checkers racing, and approve versus hold;
  - the worker recheck racing a revocation;
  - success versus failure events;
  - duplicate success events racing the reconciler;
  - raw-SQL attempts at double issue or refund after issue.

**10. Hooks and freeze**
- `local:disbursement` is hidden and guarded like `local:wallet`. It offers `--seed`, `--event=<intent> --state= [--event-id] [--amount] [--effective-at]`, `--script-query=`, `--fail-recheck=` and `--counts`.
- ADR-0001 gets an S3-D section.
- `ArchitectureTest` gets the rule "disbursement records are only accessed from the disbursement adapters".
- `verify-negative-controls.sh` gets a `disbursement-boundary` control in the `business` group.
- Regenerate with `inventory:baseline`. Seed and event commands go in the PR body.

## What can be built now versus what needs S3-C

- **Now, using `SyntheticFundedCampaigns` behind the test and local guard:** commits 1–5, 8, 10, most of 9, and the WalletPostings issue extension, stacked on #172.
- **Needs S3-C persistence:**
  - the concrete `FundedCampaigns` adapter (commitment states, the full-funding lock, exposure conversion, the `failed_closing` closure phase);
  - the Investor portfolio and Holding Resources;
  - browser journeys J-D1 to J-D5 on a real funded campaign;
  - lock-order sign-off before concurrent commands are enabled.

## Blockers and questions

1. **No verified payout destination exists.** Who owns its source and verification? Until then everything fails closed. (§10.8 tokenized beneficiary; §2e `destination`.)
2. **No conditions-precedent record exists.** Does a missing input refuse with `POLICY_INPUT_REQUIRED`, or count as a failed recheck? (MC-02 fully-funded row; §11.3; §10.6.)
3. **The role mapping (G-OPS-01) and superadmin's current grant-all row.** (§11.1 Overrides; 5829690398 Q11.)
4. **Maker revoked before approval.** Is the maker's authorization then void? (Q11 "currently authorized".)
5. **Connected-staff check.** `StaffAccount` has no person link. (§2e separation; §11.1 Roles.)
6. **A recheck failure at authorize time.** Is it a refusal, or failed closing? (Q10; v2 §2e names only approve and worker.)
7. **What counts as reconciled.** Is one authenticated matching event enough, or is a query observation also needed? (§10.8; §11.5.)
8. **The lock order proposed in step 6.** The `business_profiles` lock taken by the triggers comes before the campaign. (MC-02; 5871504691.)
9. **Ownership of `primary_holdings`, and the change to the closure `phase` CHECK and actor rule.** Both touch S3-C/Business persistence. (5871504691.)
10. **A late success after a reconciled failure has already been refunded.** Is it a blocked exception with no ledger effect? (§10.8 reversal.)
11. **The step-up request body.** Should `request_id` be dropped from it? Separately, `FortifyAuthenticator` does not stop a code being reused within its time window. (5868190264 answer 5.)

### Critical Files for Implementation
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Application/Wallet/Contracts/WalletPostings.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Infrastructure/Auditor/EloquentAuditStepUp.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Domain/Identity/StaffPermission.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Infrastructure/Operations/EloquentOperationJournal.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/resources/js/types/admin.ts