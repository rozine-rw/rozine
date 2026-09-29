# S3-B implementation plan: Investor wallet, balanced journal and synthetic deposits

Everything below was read at `dev` cc136bac. **Blocker:** worktree HEAD is 95315c68, which is #169 on top of #168, not the literal cc136bac. Diff HEAD against cc136bac before branching.

## 1. What can be reused

**Operations and idempotency**
- `OperationJournal::execute(actorKey, actorUserId, command, requestId, targetType, targetId, permittedInput, authorize, operation)` and `find(actorKey, command, requestId, authorize)`.
- `EloquentOperationJournal` does the following:
  - hashes `{target_type, target_id, input}` with JCS;
  - takes the advisory lock `actor|command|request_id`;
  - returns the stored result on a hash match, or throws `IDEMPOTENCY_CONFLICT` (409) on a mismatch;
  - **journals `CommandRejection`s too**, so replaying a refusal returns the same refusal;
  - sets retention to 8 days.
- `command_operations` has an immutability trigger. The actor key must match `party:<ulid>`.
- `OperationResource` returns `{operation_id, status, code, data, …}` and uses `http_status`.
- `JcsCanonicalJson` rejects floats. Money is a string.

**Closest end-to-end example: #152 `campaign.cancel`**
- Routes are at `routes/web.php:149` and `routes/api.php:130`.
- `CancelCampaignRequest` checks `tokenCan` on the API.
- The controller is `BusinessPublicationController::cancel`, which calls `presentCancellation()` to build `data:{receipt,current,next}`.
- `ManageBusinessCampaigns` is a thin wrapper over the `BusinessCampaignStore` contract.
- `EloquentBusinessCampaignStore::cancel` does the following:
  - runs `authority->handle(...)`, which locks and authorizes;
  - locks the row;
  - calls `journal->execute('party:'.$partyId, …, 'campaign.cancel', …)`;
  - throws rejections carrying `data`.
- The lookup goes through `FindBusinessOperation` → `findCancellation` → `journal->find`, with a Party check inside `authorize`.
- Lookup links come from `BusinessPublicationResource::lookup()`. It builds the route with the zero UUID and then does `str_replace(..., '{request_id}')`.
- The UI adds `?command=` and `identity_context_revision` itself (`use-operation-command.ts:268`).

**Party, authority and KYC**
- `AuthorizeActiveRole::handle($userId, 'investor', null, $expectedContext, fn($identity))` locks the User and the Party, then runs `ActiveRolePolicy::authorize`.
- A revision mismatch fails with `ACTIVE_ROLE_REVISION_CONFLICT` (409).
- `RoleAccess` requires `party.verified`, so KYC is enforced by the investor role itself. No separate eligibility model exists.

**Things that do not exist yet**
- Funding-method, bank or MoMo models.
- Wallet, ledger, balance or restriction tables.
- A provider adapter, a PHP `ProviderOutcomeState`, or a Money value object. Money is handled as `ExactFinancialValue::amount()` with Brick, stored in `decimal(N,0)` columns.
- The only ledger-like tables are `business_exposure_reservations` and `business_campaign_closures`.

**Environment safety**
- `EnvironmentIsolation` provides `profile()`, `canSeed()` and `guardCommand()`.
- `config('isolation.live_money_enabled')` must be `false`.
- The isolated-fixture pattern is `RecordIsolatedBusinessCreditFacts` plus `CheckpointTwoSeeder::assertLocal()`, which allows local/testing on a local pgsql database only.

**The wallet page today**
- It is preview-only: `routes/preview.php` renders the fixture in local/testing.
- `/investor` is `RoleHomeController`, which renders `identity/role-home`. There is no `investor.wallet` route.
- The UI loads the quote through `router.reload({only:['funding'], data:{kind:'deposit', amount}})`. **The server must compute `funding.quote` from the query.**

## 2. Conventions

**Migrations**
- ULID primary keys, `timestampTz`, and `decimal(p,0)` for money.
- An encrypted `payload` text column plus `sha256`, checked on read.
- plpgsql `BEFORE UPDATE OR DELETE` rejection with ERRCODE `23514`.
- Parent locks inside the insert trigger.
- `down()` refuses to run once rows exist.

**Tests**
- Feature tests use `RefreshDatabase`.
- `tests/Concurrency` uses `DatabaseTruncation` plus `pcntl_fork` (see `exposureContenders()`).
- Browser tests use `BrowserJourney` with a fixed port and drive synthetic steps through `Artisan::call`.
- UI contract tests compare keys recursively (`roleHomeUiSameShape`).

**CI must also pass**
- `ArchitectureTest.php`.
- ADR-0001 §"Protected-module rule delivery gate": the first ledger PR must freeze its namespaces in the ADR, add rules that assert the target symbols exist, and add a negative control (`scripts/quality/verify-negative-controls.sh`).
- `php artisan inventory:baseline --check` against `docs/phase-0/baseline-inventory.md` (schema, migrations, routes, classes).
- Committed Wayfinder output under `resources/js/{actions,routes}`, checked by `verify-generated-client.mjs`.
- 100% line coverage.

## 3. Plan

Branch `feat/s3b-wallet-ledger` from `dev`. Commits in order:

**C1. Schema** (`2026_09_28_*`)
- `create_investor_wallets_table`: `id`, `party_id` unique FK. This is the per-Party lock gate. Immutable.
- `create_ledger_tables`:
  - `ledger_accounts`: `wallet_id` nullable, `kind` ∈ {investor_available, investor_held, investor_committed, deposit_clearing, deposit_fee_revenue}, RWF, unique(`wallet_id`, `kind`).
  - `ledger_entries`: `kind`, `source_type`, `source_id`, **UNIQUE(kind, source_type, source_id)**, `operation_id`, encrypted `payload` + `sha256`.
  - `ledger_lines`: `entry_id`, `account_id`, `direction` ∈ {debit, credit}, `amount decimal(20,0) CHECK > 0`.
  - A `DEFERRABLE INITIALLY DEFERRED` constraint trigger enforces Σdebit = Σcredit per entry at commit. Update and delete are rejected on all three tables.
- `create_deposit_policies_table`: unique `version`, `synthetic CHECK (synthetic)`, `fee`, `minimum`, `maximum`, `effective_at`. The check is deliberate: no live policy can be inserted without a forward migration.
- `create_investor_funding_methods_table`: `party_id`, `kind` ∈ {mtn, airtel, bank}, `label`, `masked`, encrypted `reference`, `verified_at`, `verification_source CHECK = 'synthetic'`, `revoked_at` (append-only).
- `create_wallet_deposit_intents_table`: immutable. `wallet_id`, `party_id`, unique `operation_id`, `request_id`, `method_id`, `amount`, `fee`, `credited`, `policy_version` FK, `provider`, encrypted provider ref, `payload` + `sha256`.
- `create_wallet_provider_events_table`:
  - **UNIQUE(provider, provider_event_id)**;
  - `intent_id`, `state` ∈ {pending, succeeded, failed, unknown}, `amount`, `currency`, `environment`, `verified`;
  - `disposition` ∈ {applied, duplicate, mismatch, conflict, after_final};
  - encrypted evidence;
  - a partial unique index on (`intent_id`) where `disposition = 'applied' AND state IN ('succeeded','failed')`.
- `create_investor_account_restrictions_table`: synthetic-only §11.4 case (`party_id`, `kind`, `since`, `source`). See Q1.
- Models in `app/Models/` with `$guarded=['*']`, encrypted casts and `$hidden` payloads, plus factories.
- Test: `tests/Feature/WalletSchemaTest.php` covers mutations rejected, an unbalanced entry rejected at commit, the duplicate-source unique, refused rollback and the `synthetic` checks.

**C2. Domain** (`app/Domain/Wallet/`, pure, uses Brick)
- `WalletMoney` parses `/^[1-9][0-9]{0,11}$/`.
- `JournalEntry` builds balanced lines: debit clearing = credit available + credit fee.
- `DepositPolicyTerms` gives the quote and the VALIDATION_FAILED bounds.
- `DepositOutcome::transition(currentState, event)` implements the MC-08 matrix:
  - a success is applied once;
  - a success after `failed` is a `conflict`;
  - a failure after success is `after_final`, which opens reconciliation and never reverses;
  - unknown and pending never become final.
- `WalletBalance` computes total = available + held + committed; `pending_deposits` stays separate.
- Tests: `tests/Unit/WalletMoneyTest.php`, `JournalEntryTest.php`, `DepositOutcomeTest.php`, `WalletBalanceTest.php`.

**C3. RecordDepositIntent** (`app/Application/Wallet/`)
- Contracts: `Contracts\WalletStore`, `Contracts\DepositProvider`, `Contracts\SyntheticWalletFixtures`. Actions: `RecordDepositIntent`, `GetInvestorWallet`, `FindWalletOperation`.
- `app/Infrastructure/Wallet/EloquentWalletStore::deposit()` runs in this order:
  1. `AuthorizeActiveRole::handle(user, 'investor', null, ctx)`;
  2. lock the wallet;
  3. `journal->execute('party:'.$party, $user, 'wallet.deposit', $rid, 'investor_wallet', $party, {identity_context_revision, amount, method_id}, fn(){}, op)`.
- Inside `op`: a missing or inapplicable policy throws `POLICY_INPUT_REQUIRED` (409). A method that is not the caller's, not verified or revoked throws `DEPOSIT_METHOD_UNVERIFIED` (409, no existence disclosure). Out-of-bounds amounts throw `VALIDATION_FAILED` (422 with `field_errors.amount`). **Restriction is not checked (§11.4).** Otherwise it inserts the intent and returns `DEPOSIT_INTENT_RECORDED` with `{receipt, party-scoped ids}`, and no ledger write.
- Provider initiation happens after commit, outside locks (MC-08). The synthetic provider only acknowledges `pending`.
- Bind all of this in `AppServiceProvider`.

**C4. ApplyProviderOutcome, provider and guard**
- `ApplyProviderOutcome` takes the verified event from `DepositProvider::verify()` and checks signature, environment, intent, currency and amount. A mismatch is recorded as `mismatch` and never credits.
- It then locks the wallet, then the intent; inserts the event (the unique index dedupes); applies `DepositOutcome`; and on success posts the ledger entry (source = intent, unique) plus the `DEPOSIT_CREDITED` receipt in the entry payload (`receipt_id` = entry id).
- The original journal result is never touched.
- Adapters: `Infrastructure/Wallet/SyntheticDepositProvider` (HMAC over JCS using an app-key-derived synthetic secret) and `UnavailableDepositProvider`.
- The binding selects synthetic only when `profile() ∈ {local, testing}` and `live_money_enabled === false`.
- `ApplicableDepositPolicy` ignores `synthetic` rows outside local/testing.

**C5. HTTP**
Middleware: `auth, verified, throttle:60,1, cache.headers:private;no_store`.

| Method | Path | Name | Handler |
|---|---|---|---|
| GET | `investor/wallet` | `investor.wallet` | `InvestorWalletController@show` |
| POST | `investor/wallet/deposits` | `investor.wallet.deposit` | `InvestorWalletController@deposit` |
| GET | `investor/wallet-operations/{request_id}` | `investor.wallet.operations.show` | `InvestorWalletController@operation` (`whereUuid`) |

- API mirrors under `api.v1.` with the token abilities `investor:read` and `investor:command`.
- Requests in `app/Http/Requests/Investor/`:
  - `ShowWalletRequest`: `kind`, `amount`, `movement`, `before`, `receipt`, `identity_context_revision`;
  - `DepositRequest`: `request_id` uuid, `identity_context_revision`, `amount.currency` in RWF, `amount.amount` digits, `method_id` ulid;
  - `ShowWalletOperationRequest`: `command` in `wallet.deposit`, plus `identity_context_revision`.
- `InvestorWalletResource` builds `C3InvestorWalletProps`:
  - `contract_version: investor-primary-v1`, `identity_context_revision`, fresh `server_time`;
  - `allowed_actions` contains `wallet.deposit` only if the policy, a verified method and authority all exist;
  - `wallet` with ledger-derived buckets; `holds: []`;
  - `funding` with `kind` from the query and `quote` from the amount;
  - `deposits` with pending/unknown first, `intent_receipt` from the journal and `credit_receipt` from the entry;
  - external `history` from credits and an empty internal list;
  - `receipt` from `?receipt=`;
  - `earnings: null`, `exports: null`;
  - `links.operation` with the `{request_id}` placeholder; no `preview_outcome`; no provider fields.
- `deposit()` and `operation()` return `OperationResource` with `data:{receipt, current, next:null}`.
- Add `investor.wallet` to the `IdentityViolation` page list in `bootstrap/app.php`.
- Regenerate Wayfinder.
- Tests: `WalletDepositCommandTest`, `WalletProviderOutcomeTest`, `WalletHttpTest`, `InvestorWalletUiContractTest`, `WalletSyntheticIsolationTest`, with a shared `tests/Support/InvestorWalletFixture.php`.

**C6. Synthetic hook for Hussain**
- Hidden command `local:wallet` (`app/Console/Commands/PrepareSyntheticWallet.php`):
  - `--seed=<email>` creates an investor with a verified MTN method and policy `synthetic-deposit-policy-0`;
  - `--no-policy`;
  - `--restrict=<email>`;
  - `--event=<request_id> --state=succeeded|failed|unknown|pending [--event-id] [--amount]`.
- Guard: `assertLocal()`-equivalent checks plus a new `EnvironmentIsolation::guardCommand('local:wallet')` rule.
- Document it in `docs/Checkpoint_Three_Wallet_Manual_Testing.md`.

**C7. Protections**
- Freeze the Wallet namespaces and entry points in `adr-0001`.
- `ArchitectureTest`:
  - a symbol-existence test;
  - wallet and ledger models only in `App\Infrastructure\Wallet`, `App\Models` and `Database\Factories`;
  - `DepositProvider` and `SyntheticWalletFixtures` only in Wallet infrastructure, `AppServiceProvider` and the console command.
- Add `wallet-boundary` to the `business` negative-control group, so the required matrix does not change.

**C8. Concurrency** (`tests/Concurrency/WalletDepositConcurrencyTest.php`)
- Two forked processes applying the same success event, and two with different event ids for the same intent. Both give 1 entry, 1 credit and the balance equal to the amount.
- Success racing failure.
- The same `request_id` posted twice concurrently gives 1 intent.
- A lock-hold probe like `ExposureReservationConcurrencyTest`.

**C9. Browser** (`tests/Browser/InvestorWalletBrowserTest.php`, port 8036, phone and desktop)
- (a) deposit, then pending outside total, then `Artisan::call('local:wallet', …succeeded)`, then total and available rise and the credit receipt opens;
- (b) intercept and abort the POST response, then the lookup resolves it and there is one intent;
- (c) unknown shows "not yet confirmed"; failed shows nothing credited;
- (d1) restricted: the form is present, the intent is accepted, no credit;
- (d2) `--no-policy`: no form, and a direct POST returns 409 `POLICY_INPUT_REQUIRED`.
- Every journey asserts that no URL contains `/preview/`.

**C10.** Regenerate `inventory:baseline` and tick the S3-B line in `Rozine_Phased_Implementation_Plan.md` as partial.

## 4. Genuine blockers and open questions

- **Q1. Restriction source (§11.4, MC-05 §10.5).** No hold or case model exists, but journey d1 needs a server-side restriction. Proposal: an S3-B, synthetic-only, append-only restriction table that the page reads. Staff hold/release commands would come later. Hussain needs to confirm the scope.
- **Q2. InvestorAppLinks (§5 AC-11; binding 5865126315 fact 6).** There are no live routes for deals, portfolio, profile, notifications, launcher or `link_account`. Proposal: point them at `investor.home` until those pages exist. The alternative is to make them nullable in the TS type.
- **Q3. Credit-receipt identity (§4 Result, §10.8).** A provider event has no Party actor for `command_operations`. Proposal: `DEPOSIT_CREDITED` gets `operation_id` = ledger entry id and carries the intent's `request_id`. The lookup returns the unchanged intent result, and the credited state shows in `current`.
- **Q4. Non-zero deposit fee (§10.6, §11.1).** No fee authority exists. The journal supports a fee line, but S3-B policies should stay at fee 0 unless Hussain disagrees.
- **Q5. Synthetic in demo/UAT (EnvironmentIsolation, §11 live-money gate).** This plan limits it to local/testing. Hussain has to confirm whether local is enough for his independent checks.

### Critical files for implementation
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Infrastructure/Operations/EloquentOperationJournal.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Infrastructure/Business/EloquentBusinessCampaignStore.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Http/Controllers/BusinessPublicationController.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/app/Application/Environment/EnvironmentIsolation.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/tests/Architecture/ArchitectureTest.php
- /private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/fu/resources/js/types/investor.ts