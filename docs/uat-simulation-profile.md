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

## Proposal

### 1. One explicit, expiring switch

Add `isolation.uat_simulation_enabled` (env `ROZINE_UAT_SIMULATION`, default `false`). Like
`demo_flags`, it carries `uat_simulation.owner`, `.reason` and `.expires_at` (00:00 UTC), and must be
reviewed before any extension.

`assertSafeConfiguration()` would add these checks, and boot fails closed on any of them:

- The switch must be a boolean.
- It may be `true` only when the profile is `uat`. On `production`, `demo`, `local` or `testing` it
  raises `ISOLATION_UAT_SIMULATION_DENIED`.
- Once expired it raises `ISOLATION_UAT_SIMULATION_EXPIRED`.
- The existing checks are unchanged: live money stays `false`, no provider credentials, dedicated
  database, debug off, non-live URL.

### 2. The guard change that needs agreement

Both synthetic guards would admit exactly one more case. Nothing else in them changes:

```php
public function allowed(): bool
{
    $profile = $this->isolation->profile();

    return $this->config->get('isolation.live_money_enabled') === false
        && (in_array($profile, ['local', 'testing'], true)
            || ($profile === 'uat' && $this->config->get('isolation.uat_simulation_enabled') === true));
}
```

The local/testing behaviour, the exception messages and every `assertAllowed()` call site stay as
they are. `guardCommand()` keeps `local:*` commands on local/testing. UAT uses the staff panel below,
not Artisan.

**This proposal deliberately does not widen `SyntheticDisbursementGuard`'s `FundedCampaigns`
binding on UAT** (see stage C).

### 3. Stages

| Stage | What testers can do on UAT | Depends on |
| --- | --- | --- |
| **A. Deposits** | An Investor or Business starts a deposit; a superadmin-only *Simulation* panel delivers the provider outcome (succeeded, failed, unknown or pending). The panel uses the existing `SyntheticEventSigner`, so the real `ApplyProviderOutcome` path, ledger postings and receipts run unchanged | Guard change; a new staff permission `simulation.operate` (superadmin only); a deposit policy decision (below) |
| **B. Payouts** | Staff dispatch an approved disbursement; the panel answers the synthetic payout provider's sends, queries and callbacks | Stage A, and a **current** `FundedCampaigns` source (stage C) |
| **C. Funded notes and servicing** | A campaign that really closes against simulated deposits becomes funded, its Holdings convert, and servicing and repayments run | Hussain's S3-C funding writer and orchestration; the Holding-side producer; `NoteServicing`. **Not** synthetic fixtures |

On UAT, funded state must come only from the real writer running against simulated deposits.
Binding `SyntheticDisbursementSources` as `FundedCampaigns` on UAT would let fixtures act as
funding authority, so this proposal excludes it. Until stage C exists, UAT disbursement stays
unavailable, and the panel says so instead of faking it.

### 4. Always labelled

Every simulated provider record keeps provider `synthetic`, as today. With the switch on, the existing
`nonLiveEnvironment` banner says *Simulated money — no real funds move*, and simulated receipts carry
the same label. The panel shows the switch owner and expiry.

### 5. Off switch

Unsetting `ROZINE_UAT_SIMULATION` (or reaching the expiry) closes both guards at the next boot. New
deposits then answer *unavailable* exactly as today. Simulated rows already in `rozine_uat` stay
readable and labelled. They are never migrated anywhere, and `rozine_uat` is never production.

## Tests the implementation must ship

- A guard matrix across profile (`local`, `testing`, `demo`, `uat`, `production`) × switch (`unset`,
  `false`, `true`) × live money: `allowed()` is true only for local/testing, or for uat with the switch
  on, and always false with live money on.
- `assertSafeConfiguration()` refuses the switch on every non-UAT profile, and refuses it once
  expired or non-boolean.
- On `production` the switch can never bind a synthetic adapter: container bindings resolve to the
  unavailable adapters.
- On UAT with the switch off, behaviour equals today's: every existing `Unavailable*` binding and
  deployment-isolation test still passes.
- Panel actions require `simulation.operate`. Each is journaled with a reason and labelled in
  receipts. `FundedCampaigns` stays unavailable on UAT.
- The deployment-admission negative controls stay green. The UAT deploy prints the switch state and
  expiry in its evidence.

## Decisions needed

1. **Hussain:** agreement to the two-line guard change and the stage boundaries, in particular that
   stage B waits for a current `FundedCampaigns` source rather than UAT fixtures.
2. **Erastus:**
   - whether testers self-serve outcomes through the panel or an engineer drives them;
   - the switch owner and first expiry date;
   - who holds `simulation.operate`.
3. **Deposit policy on UAT (Robert, #99):** deposits require a published deposit policy version. Should
   UAT use the engineering synthetic policy fixture, labelled as such, or wait for Robert's deposit
   limits? This proposal adopts no fee, limit, tenor or verification policy.

Until these are answered and reviewed, staging keeps today's behaviour: **synthetic money runs only on
local and testing, and staging has no synthetic deposits.**
