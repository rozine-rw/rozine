# Checkpoint 2: local manual testing

This pack exercises the merged checkpoint 2 implementation with synthetic data. It stops at publication of a co-signed audit report. Campaign publication, investment, disbursement and repayment belong to later checkpoints.

## Setup and repeat runs

Use a fresh local PostgreSQL database with no consent or engagement releases. Existing developer databases that have already run audit scenarios will be refused and preserved. From the repository root, use `APP_ENV=local`, a loopback host, and database `rozine` or the dedicated `rozine_manual`:

```sh
composer install
vp install --frozen-lockfile
php artisan migrate --no-interaction
php artisan local:checkpoint-two --no-interaction
```

The command prints the accounts and application/report paths. Open `storage/app/private/manual-tests/checkpoint-two/START-HERE.md` for clickable links. The machine-readable IDs are in `manifest.json` beside it. The equivalent explicit seeder is `php artisan db:seed --class=CheckpointTwoSeeder --no-interaction`; ordinary `db:seed` does not install this pack.

For a separate manual-testing database, create an empty local `rozine_manual` database and point a separate checkout's `.env` at it before running the commands above. Each checkout must keep its own private storage and manifest with its database. This lets you keep your normal developer database intact. The opt-in command is hidden from `artisan list` and refuses execution outside local/testing even when invoked by name.

Keep the scheduler running in another terminal while testing:

```sh
php artisan schedule:work --no-interaction
```

The scheduler runs `audits:advance-offers` and `statements:extract` every minute. It advances expired offers and processes pending statement extraction in bounded processes. A queue worker does not replace the scheduler; without it those states can remain pending. Stop this terminal when you finish testing.

Rerunning preserves all accounts, passwords, workflow progress and existing records. It does not reset, truncate or re-date evidence. Keep the manifest with its database. If the manifest is stale, missing while reserved accounts remain, or the database already has unrelated consent/engagement releases, the command refuses to overwrite them. Use a separate clean local installation for a fresh pack; do not run `migrate:fresh` against a database you want to retain. Pending deadlines and credential/evidence freshness continue to follow real time.

The pack requires Composer development dependencies and `Tests\Support` scenario builders. It runs the same application actions used by the acceptance tests. Local environments allow loopback PostgreSQL databases named `rozine`, `rozine_manual` or `rozine_test`; testing is restricted to `rozine_test`. UAT, demo and production are refused. Tests use the dedicated `rozine_test` database, never the manual-testing database.

## Logins

Every newly created account uses this synthetic password:

```text
Rozine-C2-local-only-42!
```

| Case | Business login | Auditor login | Initial state |
| --- | --- | --- | --- |
| Draft | `business-draft@c2.rozine.invalid` | `auditor-draft@c2.rozine.invalid` | Saved raise, 36 verified months, ready to evaluate and submit |
| Review | `business-review@c2.rozine.invalid` | `auditor-review@c2.rozine.invalid` | Organization application signed by one of two required signatories |
| Seal | `business-seal@c2.rozine.invalid` | `auditor-seal@c2.rozine.invalid` | Submitted application; Flash procedure completed, ready to seal |
| Co-sign | `business-cosign@c2.rozine.invalid` | `auditor-cosign@c2.rozine.invalid` | Auditor sealed the report; Business signature pending |
| Published | `business-published@c2.rozine.invalid` | `auditor-published@c2.rozine.invalid` | Co-signed report published with a verifiable seal |

The second organization signatory is `signatory-review@c2.rozine.invalid`. The main Operations account is `operations@c2.rozine.invalid`; it has Approver and Compliance access. Additional scenario-specific staff accounts appear in the command output and manifest.

Business accounts do not require MFA. New Auditor and staff accounts each have a separate authenticator secret and no pre-generated recovery codes. Obtain their current six-digit code with the account alias:

```sh
php artisan local:checkpoint-two --otp=auditor-seal
php artisan local:checkpoint-two --otp=operations
```

Use the code immediately at the authenticator challenge. Codes are single use: after using a code to log in, wait for the next 30-second time window before using another code to confirm sealing. The command only reads the seeded account's current code; it does not bypass authentication or create a step-up proof. Sign out before switching accounts, or use separate browser profiles.

## Walkthrough: application to published report

Use the **Draft** case for the complete interactive journey. The other cases let you jump to a particular stage. Open links from `START-HERE.md` while signed into the corresponding account.

1. **Business:** log in as `business-draft`. Open its Application link. Review the prefilled raise: RWF 12,000,000, six months, equipment, with 36 months of synthetic statement history. Continue through the quote and review screens. Read the synthetic terms, privacy and disclosures; enter a signer name and submit. Expect a submitted application rather than an open draft, including after a reload.
2. **Auditor:** sign in as `auditor-draft` with its MFA code. Open the case's Auditor job link and choose **Start the audit**. The assignment and evidence verification are already accepted. Complete the Flash review, check-in and photos steps using the seeded source facts. On the ledger step use observed stock **38000000** and confirm reconciliation. The extra photo title can be **Stock room**. Continue to the seal preview. If findings require a note, enter one before continuing.
3. **Seal:** inspect the report preview, request a fresh authenticator code with `--otp=auditor-draft`, confirm the step-up and seal. Expect an immutable sealed report with Business co-signing still pending. Sealing alone does not publish the report.
4. **Business:** rerun `php artisan local:checkpoint-two` to refresh the guide with the report created in step 2. Sign in as `business-draft` again and open the Draft **Business report and co-sign** link in `START-HERE.md`. Review it, accept the co-sign declaration and confirm. Expect a published report. Reload and verify that the publication and signature remain visible. The seal verification link verifies the cryptographic seal; it does not disclose the private report.
5. **Operations:** sign in as `operations` with MFA. `/admin` opens the basic staff workspace. The Draft Operations case link currently returns an authorized JSON resource, not a finished Operations screen. Inspect its status and `allowed_actions`; an accepted or completed job does not offer every exception-resolution action.

For the **Review** case, sign in as `signatory-review`, open the Review Application link and supply the second signature. The first signature is already recorded. The application must not submit before all required signatories accept the current versions.

For **Seal**, **Co-sign**, and **Published**, use their direct Auditor and Business report links. They are independent cases, so changing one does not advance the others.

## Sample evidence and scope

`storage/app/private/manual-tests/checkpoint-two/synthetic-statement.csv` contains the same rolling 36-month history used by the scenarios: two entries each month, RWF 4,000,000 sales and RWF 1,000,000 costs. The originals, extraction/transcription and auditor verification are already persisted. Private statement download links in the Auditor procedure exercise authorized access to the originals. This CSV is a sample for inspection; a general statement-upload interface is not part of this walkthrough.

Accreditation, engagement terms, business mandates, premises and office coordinates, credit facts, photo metadata and signing keys are synthetic local fixtures. Consent text explicitly says it is not legally approved. These fixtures do not validate production providers, real field capture, legal approval or live money movement. The UI preview routes are separate static examples; use the authenticated routes in the generated guide to test persistence.

Flash assignments expire 24 hours after dispatch, shown in the Auditor screen. Run the active journeys within that window. Rerunning this seeder deliberately does not extend those deadlines. The current local Vite server also returns 404 for its generated font URLs; the verified flows render with fallback fonts.

## Checks

- Log in and out as Business, the second signatory, Auditor and Operations.
- Reload midway through an application and confirm saved progress.
- Complete the Draft journey and verify sealed versus published states.
- Confirm that another case's Business account cannot open the private report.
- Inspect the sample statement and the private original from the Auditor procedure.
- Rerun the command and confirm your manual progress remains unchanged.

Automated regression checks:

```sh
php artisan test --compact --no-tia tests/Feature/CheckpointTwoManualTestPackTest.php tests/Architecture/ArchitectureTest.php
```
