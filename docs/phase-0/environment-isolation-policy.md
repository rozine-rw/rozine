# Environment isolation, feature flags, seeded-demo boundaries and reset controls

**Status:** `DEFINED 2026-09-10` · **Owners:** Aminu and Erastus — Engineering/Security; Kimani —
Compliance (data classification); Robert — internal Legal (personal data)

**Implementation addendum, 2026-09-14:** Phase 0M in [kickoff evidence](kickoff-evidence.md) records local demo/UAT notices, temporary switch ownership/expiry, and a guarded synthetic Pulse fixture reset. This is not host-isolation acceptance or the Phase 3 financial demo book. The merged runtime requires `rozine_uat` for both UAT database and role and **denies all UAT/staging seeding and destructive resets**; it supersedes the earlier permissive staging entries below. Historical host observations have not been reverified by this addendum.

The requirement is that demo and UAT facts can never look live or touch real records. That is a
property of the whole estate, not of one config flag, so this document states what each environment
is for, what data it may hold, which boundaries hold today with evidence, and which do not yet.

---

## The environments

| Environment | Branch | `APP_ENV` | Holds | Database | Indexed |
|---|---|---|---|---|---|
| Local | any | `local` | Fixtures and developer data only | SQLite file per developer | — |
| CI | the PR's exact SHA | `testing` | Factory data, destroyed after each run | In-memory SQLite; PostgreSQL 17 service for the concurrency lane | — |
| Staging | `uat` | `staging` | Test data only — **never real participant data** | `rozine_staging`, role `rozine` | No — `noindex, nofollow` |
| Production | `main` | `production` | **Real personal data** — the live waitlist | `rozine_production`, role `rozine_prod` | Yes |
| Demo | — | `demo` (proposed) | The seeded demo book, and nothing else | Its own database | No |

The demo environment does not exist yet. Phase 3 builds it; this document fixes its boundaries now
so they are not invented under release pressure.

## Data classification

Production holds personal data today. Every waitlist row carries a name, a contact (email or phone),
province and district, an IP address and a user agent. That makes production the only environment
where a leak, a stray seed or a misrouted test has a real person on the other end of it.

**Rule:** real participant data exists in production and nowhere else. It is never copied to staging,
a demo, a developer machine or a CI artefact — not anonymised-in-principle, not "just once for a
bug". A reproduction uses fixtures shaped like the failing record.

---

## Boundaries that hold today

Each is enforced in code or configuration, and each was checked rather than assumed.

| Boundary | Enforcement | Evidence |
|---|---|---|
| Separate databases and credentials | Distinct DB and role per environment | Deployment record; both `.env` files |
| No stack traces on public servers | `APP_DEBUG=false` on staging and production | Read from both servers 2026-09-10 |
| Non-production is not indexed | `robots` is `noindex, nofollow` unless `isProduction()` | `AppServiceProvider::configureHead` |
| No destructive schema commands in production | `DB::prohibitDestructiveCommands` blocks `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe` | `AppServiceProvider::configureDefaults` |
| **No seeding in production** | `SeedCommand::prohibit` — **added in this change** | `ApplicationDefaultsTest` |
| Strict passwords in production | 12+ characters, mixed case, digits, symbols, breach-checked | `ApplicationDefaultsTest` |
| Deployments admit only evidenced SHAs | D-68 admission gate | `.github/scripts/verify-deployment-admission.sh` |

### Why seeding was on the list

`DB::prohibitDestructiveCommands` covers five commands and **not** `db:seed`, although `SeedCommand`
supports the same prohibition. `DatabaseSeeder` creates `test@example.com` with the factory password
`password`, already email-verified. Before this change, one `php artisan db:seed` on the production
box would have planted a live, verified account with a publicly known password in the database that
holds the waitlist. Seeding is now refused in production by the same mechanism as the other five.

---

## Known gaps

These are real, owned, and not closed by this document. Recording them is the point: an isolation
policy that lists only what works describes a system nobody runs.

| Gap | Consequence | Owner | Fix |
|---|---|---|---|
| **The server runs PHP 8.4; D-73 pins deployment to 8.5** | `deploy-remote.sh` aborts before any step on either environment. **No deploy can currently succeed.** | Aminu and Erastus | Install `php8.5-fpm`, recreate both FPM pools on 8.5 sockets, repoint both nginx vhosts, then deploy. |
| Staging and production share one `deploy` user | A code-execution flaw in staging can read production's `.env`, and with it the production database password and APP_KEY | Aminu and Erastus | A separate `deploy-prod` user, its own key, and file ownership that staging cannot read |
| Both deploy with the same `STAGING_SSH_*` secrets | Compromising one environment's pipeline compromises both | Aminu and Erastus | Environment-scoped secrets, one set per GitHub environment |
| One Resend sending key for both | Staging can send as production's domain | Aminu and Erastus | A production-only key |
| One VPS hosts both | A resource or security failure on the box takes both down at once | Aminu and Erastus, with Kimani under D-42 | Acceptable for the waitlist; revisit before any money moves, against the D-42 availability and recovery objectives |

None of these is acceptable once production holds financial records. All of them must close before
Phase 4 admits real money, and the PHP 8.5 gap must close before the next deployment of any kind.

---

## Seeded-demo boundaries

The Phase 3 demo book is a resettable, isolated seeded book covering healthy, arrears, frozen,
recovery, matured, peer-to-peer-exited and refused cases across all four BRS rating bands.

- **It lives only in the demo environment.** It is never seeded into production, and production
  refuses to seed at all.
- **Every seeded record is marked as seeded** in data, not only in presentation, so no code path can
  mistake one for a participant.
- **Production cannot fall back to seeded, random or fictional activity.** An empty state renders
  as empty. This is C-11 and C-12, and it is why the Pulse waitlist numbers are server-allocated.
- **Distressed is seeded but never listable** (C-31), so the demo cannot show an ineligible deal as
  eligible.
- **No demo flow sells to Rozine** (C-27). Secondary liquidity in the demo is Investor-to-Investor,
  exactly as in production.
- **Demo identities cannot authenticate against production**, and production identities do not exist
  in the demo.

## Reset controls

| Environment | Reset | Guard |
|---|---|---|
| Local, CI | `migrate:fresh --seed` | None needed |
| Staging | `migrate:fresh --seed` | Permitted — staging holds test data only |
| Demo | One deterministic, idempotent command that rebuilds the demo book from a fixed seed | Refuses to run unless `APP_ENV` is `demo` |
| Production | **None exists, by construction** | Destructive commands and seeding are both prohibited |

A demo reset must produce the same book every time. A reset that depends on the clock or on random
data is not deterministic, and a non-deterministic demo cannot be compared run to run.

---

## Feature flags

The original 2026-09-10 baseline had no general feature-flag mechanism. The current bounded implementation uses `config/isolation.php` through `EnvironmentIsolation` for the two demo fixture switches; it does not introduce a second registry or a flag library.

- **Flags gate unfinished or unapproved behaviour, never unapproved money.** A flag may hide a
  screen or a flow; it may not switch production onto policy that has not been activated. Money,
  underwriting, fee and settlement behaviour is gated by activated, versioned policy, not by a flag.
- **Default off in production.** A flag absent from production configuration resolves to off.
- **Every flag carries an owner, a reason and an expiry.** A flag past its expiry is a failing
  test, not a forgotten conditional.
- **Both states are tested.** A flag with only its on-state tested has an untested production path.
- **Flags are read through one accessor**, never as scattered `env()` calls — which the
  architecture suite already forbids outside `config/`.

**Mechanism:** a config-driven `config/features.php` read through a single accessor is sufficient for
the MVP and adds no dependency. Adopting a flag library such as Laravel Pennant is a dependency change
and needs Engineering approval before it is introduced; it is recorded here as a candidate, not a
decision.

---

## What this document does not do

It does not build the demo environment; Phase 3 does. It does not close the server-isolation gaps;
each has an owner above. It does not introduce a flag library. It does make one boundary real in code
— production now refuses to seed — because a boundary that is only written down is not a boundary.

### Locally verified foundation reset (2026-09-14)

`php artisan demo:reset --confirm=pulse-foundation-v1` restores only two reserved Pulse rows in a dedicated `APP_ENV=demo` database, after the existing isolation checks and both `ROZINE_DEMO_ENABLED=true` and `ROZINE_DEMO_RESET_ENABLED=true` opt-ins. Both switches default off; enabling them on any other profile is refused. Their shared Engineering owner, purpose and expiry are recorded in `config/isolation.php`; enabled demo switches fail closed from **2026-10-14 00:00 UTC**, unless Engineering reviews and changes that lease. Disabled production remains unaffected.

The fixture contacts end in `@rozine-demo.invalid`, names explicitly say synthetic, queue identities are `DEMO-INV-0001` and `DEMO-BIZ-0001`, and `user_agent=rozine-synthetic:pulse-foundation-v1` records authored provenance. Every supplied field and timestamp is fixed; database-assigned row IDs remain stable once created. This marker identifies a fixture, not a general-purpose authorization credential. Updates require both the reserved contact and the marker at write time; collisions and database errors roll the whole fixture batch back. No production data is copied or anonymized, no passwords/accounts are created, and no rating, loan, return or live transaction is asserted.

This is a **fixture-set reset, not a whole-database reset**: unrelated signups, accounts, counters, files and queues are retained. No web reset route exists. Use only after dedicated demo provisioning; the command does not migrate a stale schema. Rebuilding the healthy/arrears/frozen/recovery/matured financial demo and provider-specific sandbox behavior remains later gated work.
