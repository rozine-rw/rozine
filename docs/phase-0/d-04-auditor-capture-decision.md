# D-04 — Auditor capture platform decision

**Status:** `DECIDED 2026-09-06 — OPTION B, NARROW THIN-NATIVE CAPTURE COMPANION`

**Supersedes:** `OPTIONS PRESENTED 2026-08-29; DECISION PENDING`

**Evidence:** [`d-04-pwa-assurance/report.md`](d-04-pwa-assurance/report.md), harness at
`d-04-pwa-assurance/harness/`, run record
`d-04-pwa-assurance/runs/2026-09-06-desktop-chromium-148.json`

## Decision

The Auditor is delivered as **Option B**: a narrow native secure-capture companion, with every
ordinary Auditor screen remaining Inertia web/PWA.

Option A — web/PWA-only capture — is rejected. Option C — deferring protected capture and calling
the result a demo — is rejected.

## Why Option A is closed rather than unproven

The assurance spike measured 30 probes against the seven mandatory D-04 gates. Four capabilities are
`UNSUPPORTED` on the web platform itself, not on a particular browser or device:

| Gate | Missing capability |
|---|---|
| G2.4 | No way to bind an encryption key to hardware or to device unlock. |
| G5.2 | No mock-location flag and no provider identity. |
| G5.3 | No app or device integrity attestation. |
| G6.3 | No dependable out-of-band remote wipe. |

D-04 names location/provenance and revocation as mandatory. G5.2, G5.3 and G6.3 sit inside them.
There is no API to call on any browser, so no row of a device matrix can turn these into a pass.
The decision therefore does not wait on further device testing.

What the web *did* prove is what sizes the boundary: camera-only capture with no file input
anywhere, a non-extractable AES-GCM key surviving an IndexedDB round trip, atomic writes,
exactly-once commit under duplicate and reordered submission, chunked upload resuming after
interruption, and crypto-shredding. The gap is not competence at handling evidence. It is the
inability to prove anything about the client that produced it.

## Approved scope of the exception

**Native — secure capture only**

- Camera capture with hardware-attested provenance.
- Evidence encryption with Keychain/Keystore custody bound to device unlock.
- Location acquisition with mock-provider detection.
- App Attest / Play Integrity assertions bound to each captured item.
- Encrypted offline package storage, its ordered upload queue, and remote wipe.

**Web/PWA — everything else**

- Accreditation, licence, agreement and academy screens.
- Job list, dispatch acceptance, decline and conflict declaration.
- Checklist and procedure execution, source/parsed/on-site comparison.
- Reconciliation, filing, co-signature and seal.
- Portfolio, earnings and report history.

The native surface stays small enough to sit outside the MVP's Inertia architecture without forking
the domain. Full native role clients remain Phase 5.

## Consequences the plan must carry

1. **This is a scoped MVP exception, and the plan says such an exception requires a schedule
   rebaseline.** The thin-native component is not in the current two-developer Phase 1 estimate. It
   needs a named owner and an accepted estimate before the Phase 1 window is treated as a commitment.
   The [2026-09-10 incremental delivery plan](auditor-capture-delivery-plan.md) now proposes Aminu's
   server boundary and Erastus's native/web handoff allocation, 15–25 developer-days and a 2–3-week
   schedule addition. It is entered in Section 9.1, but A/E have not accepted a calendar commitment.
2. Phase 1B's Auditor screens remain web/PWA and are unaffected. The online Auditor alpha was always
   Phase 1; offline capture was always Phase 2.
3. Erasure standard: no browser or mobile API verifies erasure of physical storage. Crypto-shredding
   is the approved compensating control and must be named as such wherever evidence retention is
   specified.
4. Absolute offline time cannot be established. A server-signed time token issued with the package
   plus monotonic deltas bounds drift within a session; anything stronger requires connectivity.

## Outstanding work this decision does not close

The platform choice is settled. The boundary between native and web still needs sizing, and two of
these cannot be compressed:

| # | Work | Owner | Note |
|---|---|---|---|
| 1 | Run the reduced device matrix (README rows 1, 3, 4) to size the web/native split under Option B | Erastus | No longer a test of Option A |
| 2 | Start the G3.5 seven-day dwell probe on both an installed and an uninstalled iOS origin | Erastus | Seven calendar days; start on day one of the matrix run |
| 3 | Review and accept the prepared thin-native estimate and its schedule/allocation | Aminu and Erastus | Draft and machine-readable arithmetic prepared 2026-09-10; acceptance remains open |

The [execution pack](auditor-capture-delivery-plan.md#device-and-dwell-execution-pack) and
[readiness register](delivery-readiness.json) give the three device runs and both dwell observations
stable IDs. Every device result is still `NOT_RUN`; both dwell observations are `NOT_STARTED`.

Item 2 measures Safari's seven-day eviction of script-writable storage for an origin the user has
not interacted with. Home-screen web apps are exempt; plain tabs are not. It bears on how much
unsynced work the web half may hold, not on the platform decision.

## Approvals

| Role | Owner | Position |
|---|---|---|
| Engineering / Security | Erastus | Author of the spike and the finding |
| Engineering / Security | Aminu | Countersigned |
| Audit Operations / Compliance | Kimani | Countersigned, including the G6.4 question on a stolen unlocked device holding an unexpired package |

Approvals recorded 2026-09-06 on the attestation of the project owner. Signatures are recorded here
as the governing record; they do not close the three outstanding items above, and they carry no
external assurance, penetration-test, or regulatory authority, which remain separate gates.
