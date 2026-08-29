# ADR-0001 — Laravel modular-monolith boundaries

**Status:** Accepted

**Date:** 2026-08-24

**Approved:** 2026-08-28 — Engineering, Aminu and Erastus

**Owners:** Engineering — Aminu and Erastus; Security — Aminu and Erastus; Product, Business, and internal Legal — Robert; Compliance, Audit Operations, and Finance/Risk — Kimani; independent test — a pool member who was not the sole author or an eligible external delegate; required external legal/regulatory authority remains separate

## Decision

Rozine remains one Laravel 13 deployment and one transactional data boundary. Inertia web routes and versioned mobile API routes are separate transports over the same application actions. They must not duplicate financial, authorization, underwriting, evidence, or secondary-market rules.

New bounded contexts use vertical modules with internal layers:

```text
App\Domain\<Context>          pure rules, values, states, and domain events
App\Application\<Context>     use cases, transactions, policies, and ports
App\Infrastructure\<Context>  Eloquent persistence and provider adapters
App\Http                       Inertia/API controllers, requests, and Resources
```

The transport contract is shared deliberately:

1. An Inertia controller and its `/api/v1` counterpart invoke the same application action.
2. The action returns an application result or authorized read model, never an Inertia response or vendor DTO.
3. One Eloquent API Resource shapes the result for both transports. The API returns that Resource directly; the Inertia adapter resolves the same Resource into props.
4. A Resource may shape deliberately loaded and authorized data, but may not calculate money, authorize, mutate state, call providers, or perform workflow transitions.
5. Provider implementations remain behind application-owned ports. SDK types do not enter Domain objects or Resource schemas.

Cross-context writes happen only through the target context's application action or declared port. Direct table/model writes across protected ledger, settlement, underwriting, marketplace, audit-evidence, or identity seams are prohibited.

## Initial executable rules

`tests/Architecture/ArchitectureTest.php` starts with rules that are true of the current code: PSR-4 casing, prohibited debug/termination helpers, transport naming/inheritance, Resource isolation from actions/infrastructure/calculators, and transport-independent support calculations. Module-specific rules are added with the first module rather than pretending absent namespaces are already enforced.

## Exact legacy exceptions

| Class | Exception | Owner | Removal gate |
|---|---|---|---|
| `App\Http\Controllers\PulseController` | Directly queries `PulseSignup` and calls `PulseUnderwriting`; it predates the application-action boundary. | Aminu and Erastus — Engineering | Isolate or remove before Phase 1 begins; Pulse product scope remains deferred to Phase 6. |
| `App\Actions\Fortify\CreateNewUser` | Implements a Fortify framework contract inside the starter-kit authentication seam. | Aminu and Erastus — Engineering/Security | Reassess with the Phase 1 identity module; never duplicate it for the mobile API. |
| `App\Actions\Fortify\ResetUserPassword` | Implements a Fortify framework contract inside the starter-kit authentication seam. | Aminu and Erastus — Engineering/Security | Reassess with the Phase 1 identity module; never duplicate it for the mobile API. |

These are exact, temporary exceptions—not namespace-wide ignores or permission for new code to repeat the pattern.

## Consequences

- Laravel owns both web and mobile business logic without creating a second backend.
- Inertia remains a server-driven web transport; native clients consume `/api/v1` later.
- Shared Resources make schema parity testable while shared actions preserve behavioral parity.
- Financial/evidence transactions stay inside one database boundary and can use PostgreSQL locking and idempotency controls.
- The architecture suite grows alongside modules and cannot replace behavior, authorization, concurrency, or Resource contract tests.

## Approved Phase 0 contract

- [x] Use the documented Domain/Application/Infrastructure/HTTP namespace boundaries.
- [x] Start post-MVP native API versioning at `/api/v1`.
- [x] Use Eloquent API Resources as the shared serialization boundary, not the business-logic boundary.
- [x] Preserve one shared application/domain layer for Inertia web and mobile API transports.
- [x] Accept the three exact legacy dispositions. `PulseController` must be isolated or removed before Phase 1 starts; the two Fortify actions remain controlled framework exceptions and must be reassessed with the Phase 1 identity module.

Acceptance closes the architecture decision. It does not close the remaining implementation evidence: `PulseController` isolation/removal, the expanded module rule catalog, controlled architecture-test failures, and exact-SHA hosted results must still pass before Phase 1 governed behavior begins.
