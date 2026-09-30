# Integration notes: #176 (8a931443) + #181 (2bdc315e) + #175 (pinned 494e12d1)

Branch `feat/s3d-holdings-binding`, based on `origin/feat/s3d-disbursement` at `8a931443`.
This is the recipe for the real #176 rebase once #181 and #175 are in `dev`.

## Commits

| Commit | What |
| --- | --- |
| `e5e06163` | Merge #181 `origin/fix/deposit-credit-shape` (2bdc315e) |
| `4c40aff9` | Merge pinned #175 head `494e12d14f419eed0563bfc9c668b913f305e685` |
| `607792e3` | Fixture fixes so the merged tree passes its own suites; inventory regenerated |
| `607792e3..55ffca4d` | The Holdings binding (5 commits) |
| `6dfe23b0` | Merge `origin/dev` (`e848a3ac`, #187) |

The merge was mechanical. No semantic conflict in `FundedCampaigns`, staff connections, the funding
lock or the refund code. Nothing in `app/` needed a hand edit beyond import and binding unions.

## Merge 1: #181 into #176 (two conflicts)

1. `docs/phase-0/baseline-inventory.md` — migration table. Kept both; `175521` sorts before the
   `2026_09_29_*` rows. Regenerated later anyway.
2. `tests/Feature/PostgreSqlConfigurationTest.php` — rollback list. Both sides kept: #176's downs
   (100300, 100200, 100100, 100000) run before #181's `175521` down; on the way up `175521` comes
   before #176's.

`AuditReportPersistenceTest` and `WalletSchemaTest` took #181's changes without conflict.

## Merge 2: #175 (494e12d1) into the result (seven conflicts)

1. `app/Providers/AppServiceProvider.php` — import block only. Union of both sides, alphabetical
   (`Business\RetainedCampaignPublication` before the `Disbursement\*` imports). The `register()`
   bindings merged cleanly.
2. `routes/console.php` — both schedules kept: `primary:expire-reservations` and
   `disbursements:reconcile`.
3. `tests/Architecture/ArchitectureTest.php` — both blocks kept: #176's disbursement boundary
   expectations, then #175's funding/returned-cash expectations.
4. `docs/phase-0/baseline-inventory.md` — five hunks, both sides kept, then regenerated (below).
5. `tests/Feature/PostgreSqlConfigurationTest.php` — git auto-merged the down half into a WRONG
   order (#175's Primary downs before #176's). Rewritten by hand into true reverse migration order:
   `054318`, `112938`, `100300`, `100200`, `100100`, `100000`, `212446`, `195022`, `175521`,
   `175455`, `165949`, `163057`, `161335`, `154941`, `152823`, `151253`, `143756`, then the wallet
   migrations. Up is the exact reverse. The trigger / function body / constraint snapshot comparison
   from #175 stays after the last `up()`, so it now also re-checks 100200's bodies.
6. `tests/Feature/AuditReportPersistenceTest.php` — same interleave: `175521` down after `195022`
   down; #176's `100200`, `100100`, `100000` downs after `112938`; mirrored on the way up.
7. `tests/Feature/WalletSchemaTest.php` — one down list and one up list holding #175's four Primary
   migrations, #181's `175521` and #176's `100000`, `100100`, `100200`, plus both sides' final
   expectations and #181's extra helpers and tests.

Kept as the handoff asked: #175's `165949` (`primary_commitment_source_unavailable`), `175455`,
`195022`, `212446` (after `195022`), `112938` and `054318` are all in the rollback lists, and
#176's `100200` still names only `primary_reservation` for `primary_issue`, so it does not touch
`165949`'s separate constraint.

## Fixture fixes after the merge (`607792e3`)

The plain merge failed 147 of 1,763 tests in the affected suites. Three causes:

1. **#181 × #175.** #181 refuses a deposit credit that settles no recorded intent. #175's
   `PrimaryReservationRecordFactory::retainSyntheticHold` funded its synthetic hold from a bare
   `LedgerEntry::factory()` credit. It now creates a `WalletDepositCredit` bound to a
   `WalletDepositIntent` on the same wallet for the reservation principal, as #181 did for
   `WalletSchemaTest`. This one factory change cleared about 130 failures across the `Primary*`
   feature and concurrency files. **Hussain will meet this when #175 is rebased on #181.**
2. **#181 × #175.** #181 adds deferred constraint triggers on `ledger_entries`, so events are still
   pending after a settle. `PrimaryHoldBindingTest::holdBindingWallet()` now flushes them before the
   test runs `ALTER TABLE ledger_entries DISABLE TRIGGER` (PostgreSQL refuses that with pending
   trigger events).
3. **#175 × #176** (expected). `WalletIssuePostingTest`, `WalletIssueConcurrencyTest` and
   `DisbursementReviewRegressionTest` call `PrimaryReservationFixture::terminalVersion($source,
   'confirmed')` between hold and commit. `PrimarySourceFixture` was left alone; with #175 present it
   always delegates to `PrimaryReservationFixture::postingSource`, so its fresh-ULID fallback is dead.

`docs/phase-0/baseline-inventory.md` was regenerated with `php artisan inventory:baseline` against
a migrated private test database (port 5551, `rozine_test`), never the local default database.

## Merge 3: dev (e848a3ac) into the result (two conflicts)

`config/client-source-manifest.json` and `config/client-risk-manifest.json` only. Took our path
lists (they already hold #176's files; dev's new `service-fee.tsx` merged cleanly into the list),
then recomputed: counts 196 generated, 18 declaration-only, 371 authored, 585 tracked;
`coverageSetSha256` = sha256 of the sorted authored paths joined by newlines plus a final newline
(equals dev's value); the risk manifest's `sourceManifestSha256` = sha256 of the source manifest
file. Checked that the three lists partition `git ls-files resources/js` `*.ts(x)` exactly.

## Things to remember for the real rebase

- Every new migration goes into the explicit rollback lists: `PostgreSqlConfigurationTest`,
  `AuditReportPersistenceTest`, `AuditAssignmentTest`, `BusinessExposureReservationTest`,
  `WalletSchemaTest`, `PrimaryReservationSchemaTest`, and `PrimaryCampaignFundingTest` (it reverses
  `054318` on its own; the first full run missed it). The binding migration `084737` adds FKs from
  `primary_holdings` to four Primary tables, so its `down()` must run before `054318`'s and
  `143756`'s in all seven.
- Two real campaigns in one test replay `AuditSealingFixture`'s fixed authenticator secret inside
  Fortify's reuse window (`STEP_UP_CODE_INVALID`). `PrimaryHoldingFixture::committed()` clears the
  cache first.
- `SET CONSTRAINTS ALL IMMEDIATE` stays in force for the rest of the transaction. Follow it with
  `SET CONSTRAINTS ALL DEFERRED` when more writes come after it.
- Never commit regenerated Wayfinder output after a local build.
- Truncation runs before each Concurrency test, not after the last one. A Feature test that runs
  after a Concurrency test in the same process sees committed rows. Pass Feature paths first.
- `npm run build` failed twice after the dev merge on a network timeout; rebuild before trusting
  `UiPreviewTest` on dev's new service-fee fixtures.
