@hussain4real: the S3-D step sequence and contracts, published **before any action is wired**, as you asked in 5871504691. The draft PR follows once the #172 corrections have your re-review. New code lives in `App\{Domain,Application,Infrastructure}\Disbursement`.

## Step sequence
1. **Pure domain.** `DisbursementState` is built from the event log (ready → awaiting_second_approver → queued → dispatched → succeeded | failed_closing, with on_hold keeping the prior state).
   - Guards: approve or reject needs actor ≠ maker (`SELF_APPROVAL_FORBIDDEN`); hold release needs actor ≠ placer; queued and later states refuse hold, reject and release (`DISBURSEMENT_IN_FLIGHT`).
   - `PayoutOutcome` follows the MC-08 matrix (applied, duplicate, after_final, conflict, key_conflict, unverifiable).
   - `Reconciliation::decide` uses RWF 0 tolerance.
   - `IssueSchedule` uses the Kigali date, with each due date computed from the original anchor day and month-end clamped (Jan 31 → Feb 28/29 → Mar 31).
   - `IntentDigest` is JCS over {disbursement, revision, campaign, exposure reservation, amount, currency, destination hash, commitments digest, provider, environment}.
2. **Immutable schema.** `disbursements` (one per campaign), `disbursement_events`, `disbursement_step_up_proofs` (hashed, single-use), `disbursement_intents` (one per disbursement), `disbursement_dispatches`, `disbursement_provider_calls` (append-only `send`/`query`, which is the send-count evidence), `disbursement_provider_events` (a unique key plus a content hash), `disbursement_reconciliations`, `disbursement_closings` and `primary_holdings`, which is Q9.
3. **Staff step-up exchange.** Route `POST admin/disbursements/{disbursement}/step-up`, named `staff.disbursements.step-up`, plus its `api.v1` mirror.
   - Body: `{expected_revision, intent_digest, code}`, under `throttle:disbursement-step-up` (5/min per account, 20/min per IP, the same as the seal) and `no-store`.
   - It checks `disbursements.approve` and not-the-maker, and returns `{proof, expires_at}` directly. It is never journaled or logged.
   - The proof is bound to the principal, the purpose `disbursement.approve`, the record, the revision, the amount, the destination hash and the intent digest. It is consumed atomically inside approve's first execution only.
   - **Unlike the seal, the proof hash is NOT part of the journal fingerprint**, so a same-key successful replay recovers the result without the consumed proof, while still rechecking lookup authority.
4. **Commands.** `POST admin/disbursements/{disbursement}/{authorize|approve|reject|hold|release-hold|requery}`, named `staff.disbursements.*`, plus `api.v1` mirrors with the `staff:disbursements:manage` ability. The lookup is `GET admin/disbursements/operations/{request_id}?command=`. The body is `{request_id, expected_revision, reason}`, and approve adds `step_up_proof`. The target always comes from the route.

| Command | Permission | Receipt |
|---|---|---|
| authorize | `disbursements.authorize` | `DISBURSEMENT_AUTHORIZED` |
| approve | `disbursements.approve`, ≠ maker, plus proof | `DISBURSEMENT_INTENT_RECORDED` (queued), or `CAMPAIGN_FAILED_CLOSING` on a failed recheck |
| reject | `disbursements.approve`, ≠ maker | `DISBURSEMENT_AUTHORIZATION_REJECTED` (voids the maker authorization only) |
| hold | `disbursements.hold` | `DISBURSEMENT_HELD` |
| release_hold | `disbursements.approve`, ≠ placer | `DISBURSEMENT_HOLD_RELEASED` (neither approves nor pays) |
| requery | `disbursements.requery` | `PROVIDER_QUERY_RECORDED` (observes only) |

   Refusals:
   - **422:** `VALIDATION_FAILED` (empty or control-character reason).
   - **403:** `STEP_UP_REQUIRED`, `STEP_UP_INVALID`, `STEP_UP_EXPIRED`, `SELF_APPROVAL_FORBIDDEN`, plus the existing staff codes.
   - **409:** `VERSION_CONFLICT`, `IDEMPOTENCY_CONFLICT`, `DISBURSEMENT_IN_FLIGHT`, `PROVIDER_OUTCOME_UNRESOLVED`, `POLICY_INPUT_REQUIRED`, `DISBURSEMENT_STATE_INVALID`.
   - **404:** scoped.
   - **503:** `RETRYABLE_CONTENTION`.
5. **Worker, provider and reconciler.**
   - `PayoutProvider` has `send`, `query` and `verify`. `SyntheticPayoutProvider` is bound only under local/testing with live money off; otherwise `UnavailablePayoutProvider`.
   - Approve records the intent plus a `queued` dispatch in its transaction, and the dispatch runs via `DB::afterCommit` (the #172 P1 lesson). The dispatcher refuses to run inside an open transaction.
   - The worker claim rechecks under locks. A failed recheck means a deterministic failed closing, with no claim. The send happens outside the transaction. An interrupted claim is resent only if the provider accepts idempotent sends; otherwise it becomes unknown and goes to the reconciler.
   - A scheduled `disbursements:reconcile` and staff requery only `query()` the same operation, never `send()`, and the call log proves the send count is unchanged.
6. **S3-C port** (below). It fails closed via `UnavailableFundedCampaigns` (`FUNDING_SOURCE_UNAVAILABLE`) until your adapter exists.
7. **Terminal effects,** in one reconciler transaction.
   - A matched, reconciled success issues Holdings exactly once, with rights and terms unchanged. The effective date comes from the authenticated `effective_at`, never the callback arrival time. It also converts the exposure and writes a `HOLDING_ISSUED` receipt per Holding.
   - A matched failure, or a failed recheck, means `failClose` with full fee-free refunds.
   - Unknown, exception or conflict refuses closing.
8. **Admin Resource.** `StaffDisbursementsResource` builds `C3AdminDisbursementsProps` with per-viewer `allowed_actions`, the `viewer_is_maker`/`viewer_placed_hold` flags, `approval_binding`, and a lookup containing the literal `{request_id}`. `step_up.route` stays null until step 3's tests pass.
9. **Tests.** Every case in your list, plus pcntl concurrency: racing checkers, approve vs hold, recheck vs revocation, success vs failure, duplicate successes vs the reconciler, and raw-SQL double issue or refund after issue.
10. **`local:disbursement` hook** (guarded like `local:wallet`), plus the ADR freeze, the architecture rule, a `disbursement-boundary` negative control and the inventory.

## Proposed permission mapping (G-OPS-01)
- **treasury:** `disbursements.view`, `.authorize`, `.hold`, `.requery`
- **approver:** `.view`, `.approve`, `.hold`
- **compliance:** `.view`, `.hold`
- **superadmin:** `.view` only, **not** the grant-all row. That would break the "no superadmin bypass" rule, so it needs its own test.

The mapping tests land before any route is registered.

## Proposed S3-C port: `App\Application\Disbursement\Contracts\FundedCampaigns`
```php
public function funded(?string $before, int $limit): array;              // list<FundedCampaignRef>, unlocked
public function lockFunded(string $campaignId): FundedCampaign;          // inside the caller's transaction
public function recheck(FundedCampaign $c): RecheckResult;               // passed | failed(causes) | unavailable
public function issue(FundedCampaign $c, VerifiedPayout $p, string $closingId): void;          // funded → issued; exposure → outstanding
public function failClose(FundedCampaign $c, array $causes, string $closingId): void;          // failed_closing; refunds; exposure release
```
- `FundedCampaign` binds: the campaign, Business and exposure ids; principal and `funded_at`; `commitments_digest`; and each commitment (id, Party, originating operation, units, ordinal ranges, `UnitRights`, `PrimaryTerms`, principal). Its invariants: commitment principals sum to the campaign and exposure principal, and units = principal / 5,000.
- It also binds a `VerifiedDestination {id, token_sha256, masked, verified_at}`.

**Proposed lock order:** `business_profiles` → staff `users` (ascending id: actor, maker, placer) → campaign → `disbursements` → step-up proof → reservations and commitments (id order) → wallets (Party id order) → ledger. The worker and reconciler skip the staff step. The existing closure, exposure and campaign triggers already take `business_profiles` first, so it goes at the head.

**Wallet issue extension**, which S3-B's port lacks today:
- a new `primary_issue` kind, committed → a new system `disbursement_settlement` account;
- the ledger CHECK and the per-source amount binding widened for it;
- issue and refund made mutually exclusive terminals;
- a `cause_operation_id` for provenance.

A failed-closing refund reuses `refund` (committed → available, fee-free). It is never a payout, and there's no invented transfer.

## Questions (none blocks starting steps 1–5)
1. **Verified payout destination:** nothing stores one today. Who owns its source and verification? Until it exists, everything fails closed. (§10.8, §2e)
2. **Conditions precedent:** nothing records them. Does missing input refuse `POLICY_INPUT_REQUIRED`, or count as a failed recheck? (MC-02, §11.3)
3. **The role mapping above,** including superadmin as view-only.
4. **Maker revoked before approval:** is the maker's authorization void? (Q11, "currently authorized")
5. **Connected staff:** `StaffAccount` has no person link, so only a user-level check is possible today. (§2e, §11.1)
6. **A failed recheck at authorize time:** is it a refusal, or failed closing? (v2 §2e names only approve and the worker.)
7. **What counts as "reconciled":** one authenticated matching event, or does it also need a query observation? (§10.8, §11.5)
8. **The lock order above.**
9. **Ownership of `primary_holdings`, the closure `phase` CHECK and the actor rule:** these touch your Business/S3-C persistence, so shall we propose a narrow migration for you to review?
10. **A late success after a reconciled failure has been refunded:** a blocked exception with no ledger effect?
11. **Step-up body:** drop `request_id` (the TS type carries one). Also, `FortifyAuthenticator` doesn't stop a TOTP code being reused within its window, so should the exchange record used codes?

🤖 Generated with [Claude Code](https://claude.com/claude-code)
