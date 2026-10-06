# UAT simulation profile (proposal)

**Status: proposal for review (task 28, coordinated on #96).** This document changes no code, guard,
configuration, workflow or deployment. Nothing here is enabled by merging it. The guard changes it
describes need Hussain's agreement first, and enabling the profile on staging needs Erastus's
separate go-ahead.

## Why

Testers on staging (`staging.rozine.rw`, profile `uat`) cannot see money move. Outside local and
testing, every deposit, payout, funding and servicing port answers *unavailable*. That is correct for
live money and stays so for production. The request is to let UAT exercise those journeys with
**simulated** money, with live money off and no provider credentials, so testers can accept the
screens and state changes end to end.

## What exists today

| Concern | Current behaviour | Source |
| --- | --- | --- |
| Profile | `staging`/`uat` → `uat`; isolated (dedicated `rozine_uat` database, no provider credentials, file drivers, debug off, non-live HTTPS URL) | `app/Application/Environment/EnvironmentIsolation.php` |
| Live money | `isolation.live_money_enabled` must be `false` on every profile; it is not an environment toggle | `config/isolation.php`, `assertSafeConfiguration()` |
| Synthetic deposits | `SyntheticWalletGuard::allowed()` is true only on `local`/`testing` with live money off. Otherwise `DepositProvider` and `SyntheticEventSigner` bind to `UnavailableDepositProvider` | `app/Application/Wallet/SyntheticWalletGuard.php`, `AppServiceProvider::register()` |
| Synthetic payouts | `SyntheticDisbursementGuard::allowed()` is the same gate. On local it binds `FundedCampaigns`, `PayoutDestinations`, `StaffConnections` and `PayoutProvider` to synthetic fixtures; elsewhere they are unavailable adapters | `app/Application/Disbursement/SyntheticDisbursementGuard.php`, `AppServiceProvider::registerDisbursements()` |
| Commands | `local:wallet` (seed an Investor, method and policy; deliver signed provider events), `local:disbursement` (seed a funded synthetic campaign and destination; deliver callbacks; script queries). `guardCommand()` refuses `local:wallet` and `wallet:dispatch-deposits` off local/testing | `app/Console/Commands/PrepareSynthetic*.php`, `EnvironmentIsolation::guardCommand()` |
| Funding and holdings | `FundedCampaigns` is synthetic only on local. `HoldingSource` reads retained, historical evidence. The S3-C funding writer and Holding conversion are unfinished | `AppServiceProvider`, #96 |
| Servicing | `NoteServicing` is `UnavailableNoteServicing` on every profile | `AppServiceProvider` |

## Proposal (revision 3, after reviews 5424739389 and 5425309445)

Revision 1 proposed widening `SyntheticWalletGuard` and `SyntheticDisbursementGuard`. Review showed
why that is wrong: `registerDisbursements()` uses the same `SyntheticDisbursementGuard::allowed()`
result for `FundedCampaigns`, `PayoutDestinations` and `StaffConnections`. Admitting UAT through it
would make campaign fixtures, fixture destinations and fixture connections authoritative on staging.
**Revision 2 leaves both existing guards and every current binding unchanged.** Revision 3 names the
deposit outbox worker's own gate, says that revocation needs a process reload, and states that expiry
never turns a pending or unknown obligation into a failure.

### 1. A separate provider-only guard

A new `UatProviderSimulation` guard answers one question: may the *provider edge* (the external
payment provider's sends, queries and signed callbacks) be simulated? It never decides funding,
destination, connection or admission authority.

`allowed()` is true only when **all** of these hold at the moment it is called, not just at boot:

- the profile is `uat`;
- `isolation.live_money_enabled === false`;
- `isolation.uat_simulation_enabled === true` (env `ROZINE_UAT_SIMULATION`, default `false`);
- `now('UTC') < isolation.uat_simulation.expires_at` (00:00 UTC), with `owner` and `reason`
  recorded beside it like `demo_flags`.

`assertSafeConfiguration()` adds two boot checks only: the switch must be a boolean, and it may be
`true` only on `uat` (`ISOLATION_UAT_SIMULATION_DENIED` on any other profile). An **expired** switch
is deliberately *not* a boot failure. Expiry closes `allowed()` at runtime while the application keeps
booting and serving reads. The existing isolation checks are unchanged: live money off, no provider
credentials, a dedicated database, debug off, a non-live URL.

### 2. Binding matrix

| Port | local / testing (today, unchanged) | UAT, switch off or expired | UAT, switch on and unexpired |
| --- | --- | --- | --- |
| `DepositProvider`, `SyntheticEventSigner` | synthetic (`SyntheticWalletGuard`) | `UnavailableDepositProvider` | `SyntheticDepositProvider` via `UatProviderSimulation` (stage A) |
| Deposit outbox worker (`DispatchDepositIntents`, `wallet:dispatch-deposits`) | allowed (`SyntheticWalletGuard`, `guardCommand`) | refused (`ISOLATION_SYNTHETIC_WALLET_DENIED`) | allowed only while `UatProviderSimulation` allows it (stage A); `local:wallet` stays refused |
| `PayoutProvider` | synthetic (`SyntheticDisbursementGuard`) | `UnavailablePayoutProvider` | unavailable until stage B; then the synthetic provider edge only |
| `FundedCampaigns` | `SyntheticDisbursementSources` | `UnavailableFundedCampaigns` | **unchanged:** `UnavailableFundedCampaigns` until the current S3-C writer exists, then that writer. Never fixtures |
| `PayoutDestinations` | synthetic fixtures | `UnavailablePayoutDestinations` | **unchanged:** unavailable until a verified-destination producer exists. Never fixtures |
| `StaffConnections` | synthetic fixtures | `EloquentStaffConnections` | **unchanged:** `EloquentStaffConnections` |
| `SyntheticDisbursementFixtures`, `SyntheticWalletFixtures`, `local:*` commands | available | refused (`guardCommand`, `assertAllowed`) | **refused:** UAT never seeds fixtures |
| `NoteServicing` | `UnavailableNoteServicing` | unchanged | unchanged until stage C |

`SyntheticWalletGuard` and `SyntheticDisbursementGuard` keep their exact local/testing-only
behaviour, messages and call sites. The only changed rows are the two provider rows and the deposit
outbox worker. Each picks the synthetic provider edge only when the new guard allows it, and stays
unavailable or refused otherwise. The worker's UAT admission is a separate `guardCommand` and
dispatcher check against `UatProviderSimulation`; it does not widen `SyntheticWalletGuard`.

### 3. Runtime expiry and revocation

- **Checked per call.** Every synthetic provider method already calls its guard's `assertAllowed()`.
  On UAT it calls `UatProviderSimulation::assertAllowed()` instead. A worker that booted before
  expiry therefore refuses its first provider call after expiry. The decision is never cached at
  boot; only the configured switch and expiry values are loaded.
- **Revocation is bounded by process reload.** Unsetting `ROZINE_UAT_SIMULATION` takes effect in a
  process only when that process reloads its configuration. `config:clear` alone does not refresh a
  long-running queue worker's in-memory config, so revocation means a deploy, or clearing the cached
  config *and* restarting the web processes and workers (`queue:restart`). Expiry needs neither: the
  `expires_at` value is already loaded, and every call compares it with the clock.
- **Queued and in-flight work.** The outbox worker checks the new guard before it claims anything.
  After expiry or revocation it refuses, so unclaimed intents stay recorded and pending. If a send that
  was already claimed throws at the boundary, the existing dispatcher records it `unacknowledged` and
  never retries it automatically; it is left for reconciliation of the same operation. A signed
  simulated callback arriving after expiry is refused by `verify()` before any outcome is applied.
- **Never a financial failure.** Expiry, revocation or unavailable provider verification never marks an
  intent or obligation failed. Pending and unknown obligations keep their state and history, because
  a deposit stays pending until a verified `succeeded` or `failed` event is applied.
- **Reads.** Simulated intents, outcomes, ledger postings and receipts stay readable and labelled
  after expiry or revocation, because reads never resolve a provider. They live only in `rozine_uat`.
- **Staff simulator actions.** Every action on the stage A panel runs through the existing command
  journal under the actor's current permission. It takes the full financial lock order and applies its
  financial writes and feed entries in one atomic boundary. Nothing bypasses the journal or the
  isolation boundary.

### 4. Stages

| Stage | What testers can do on UAT | Prerequisites |
| --- | --- | --- |
| **A. Deposits** | A superadmin-only Simulation panel answers the simulated provider edge for deposits (succeeded, failed, unknown, pending) through the existing signer. The real outcome, ledger and receipt paths run. | The new guard, the two provider bindings, a simulation permission, a deposit-policy decision (§ Decisions) |
| **B. Payouts** | The panel answers the simulated payout provider edge for a disbursement that the real gates already approved | Stage A, **plus** authenticated current producers for all of: the `FundedCampaigns` writer, the verified payout destination, staff connections, the broader graph and global exposure, and forward settlement. Fixtures never stand in for any of these |
| **C. Funded notes and servicing** | A campaign that really closes against simulated deposits becomes funded, its Holdings convert, and servicing runs | Current admission, verified destination, complete broader connections and global exposure, the current funded writer, authenticated forward settlement and successful Holding conversion. Retained `HoldingSource` facts and historical closing certificates are **not** these authorities |

Until a stage's prerequisites exist, its routes stay gated and the panel says it is unavailable rather
than simulating around it.

### 5. Always labelled

Simulated provider records keep provider `synthetic`, as today. With the switch on, the existing
`nonLiveEnvironment` banner says *Simulated money — no real funds move*, simulated receipts carry the
same label, and the panel shows the switch owner and expiry.

## Tests the implementation must ship

- A guard matrix across profile (`local`, `testing`, `demo`, `uat`, `production`) × switch (`unset`,
  `false`, `true`) × expiry (before, at, after) × live money: `UatProviderSimulation::allowed()` is
  true only on `uat` with the switch on, before expiry and with live money off.
- `SyntheticWalletGuard` and `SyntheticDisbursementGuard` behave exactly as today on every profile.
  The existing tests stay unchanged and pass.
- A container-binding matrix on UAT with the switch on asserts `FundedCampaigns`,
  `PayoutDestinations` and `StaffConnections` resolve to exactly today's UAT adapters, and that the
  fixture ports and `local:*` commands refuse.
- `assertSafeConfiguration()` refuses the switch on non-UAT profiles and when it is not a boolean,
  but boots with an expired switch.
- After the test clock passes expiry:
  - a long-lived worker that booted before expiry refuses its next claim;
  - a provider call from an already-resolved provider refuses;
  - an intent recorded before expiry stays pending, and a claimed send that throws is recorded
    `unacknowledged`; neither is ever marked failed;
  - a late signed callback is refused without applying an outcome;
  - every simulated record is still readable.
- Revocation: after the switch is unset and config is reloaded, web and worker processes refuse. A
  test documents that a worker which has not been restarted keeps its loaded value until expiry.
- On `production`, no setting can resolve a synthetic provider.
- The deployment-admission negative controls stay green, and the UAT deploy prints the switch state
  and expiry in its evidence.

## Decisions needed (none adopted here)

1. **Hussain:** the binding matrix and stage boundaries above, before any implementation PR.
2. **Erastus:** whether a simulation permission exists and who holds it; whether testers self-serve
   outcomes; the switch owner and first expiry; and when to deploy to staging.
3. **Robert (#99):** the deposit policy on UAT, either the engineering synthetic fixture (labelled
   as such) or his deposit limits. No fee, limit, tenor or verification policy is adopted here.

Until these are answered and reviewed, staging keeps today's behaviour: **synthetic money runs only on
local and testing, and staging has no synthetic deposits.**
