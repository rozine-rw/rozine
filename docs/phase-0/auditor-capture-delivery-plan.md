# Auditor capture — scope, incremental estimate and evidence plan

## Status

`PREPARED 2026-09-10 — ESTIMATE PROPOSED, NOT A CALENDAR COMMITMENT`

D-04 Option B is already approved. This document sizes that **narrow native capture exception**, not full native role applications and not a new platform decision. Aminu owns the shared Laravel/server boundary; Erastus owns the companion and its web handoff, subject to their acceptance of the allocation below. Both review the contract. Kimani retains Audit Operations/Compliance authority. No owner has signed this estimate or any new D-29/D-31/D-35/D-36/D-37/D-40 rule here.

Sources: [D-04 decision](d-04-auditor-capture-decision.md), [assurance procedure](d-04-pwa-assurance/README.md), [BRS](../Rozine-BRS.md) FR-300–FR-312/NFR-3–NFR-9, and [plan](../Rozine_Phased_Implementation_Plan.md) Sections 9.1/11/D-65–D-67/D-74. [Machine-readable readiness](delivery-readiness.json) is the testable estimate/run/track register; it contains no fabricated device run or approval.

## Goal

Make the extra native work and its release gates explicit before treating the existing web/PWA MVP schedule as a commitment. Preserve one Laravel domain/application layer and shared Resources. The companion captures and transports evidence; it never sets a rating, capacity, yield, licence standing, accepted financial figure, co-signature or seal.

## Scope and ownership boundary

| Surface | Included responsibility | Excluded authority |
|---|---|---|
| Web/PWA — Erastus | Accreditation/agreement, jobs/dispatch/conflict, procedures and comparisons, review/correction, reconciliation/filing/co-signature UI, portfolio/earnings; companion launch and upload/recovery status | No gallery fallback for governed capture; no browser-only assertion that protected evidence is verified |
| Thin native companion — Erastus | Device-bound access to an assigned package; camera-only capture; location/time/integrity metadata; key custody; encrypted bounded offline package; ordered resumable upload and loss/revocation handling | No independent Party model, general role app, financial engine, direct ledger access, client-issued seal or silent acceptance |
| Laravel core — Aminu | Assignment/device authorization, versioned package and handoff, server verification of envelope/hash/sequence/replay/revocation, atomic ingestion acknowledgement, retained lineage and shared Resource status | A token or client success flag cannot replace core authorization, report review or provider/regulatory approval |

The web/native contract is a **design requirement**, not a frozen schema: bind assignment, actor, device/key, package/version, evidence identifier, content digest, sequence, procedure reference, capture-time basis and location observations. Bind upload assertions to the same immutable content and operation; retain server receipt time separately. A retry reuses the operation identity. A final acknowledgement means the core committed the evidence exactly once, not that an audit or financial decision passed.

The native framework and distribution route remain unselected. CAP-01 must prove the required platform APIs, signing/integrity support, accessibility and D-66/D-67 coverage tooling before a dependency is proposed. D-03's full Phase 5 client choice is not silently decided by this exception.

### Assurance limits that must survive implementation

- App/device integrity is a signal about software and its request, **not independent proof that a pictured scene, location or device clock is truthful**. Apple describes server-validated App Attest assertions; Google binds a request digest to an integrity verdict. Our engineering inference is to verify a content-bound envelope and retain uncertainty rather than call those APIs a camera or GPS attestation certificate. [Apple App Attest](https://developer.apple.com/documentation/devicecheck/validating-apps-that-connect-to-your-server), [Google request binding](https://developer.android.com/google/play/integrity/standard).
- The online integrity flow must not be assumed to work offline. Offline capture requires the D-36/D-37-approved bounded package/key/time contract; reconnect verification is explicit. Until approved, an offline item stays unverified and cannot be sealed or used as trusted input. Unsupported devices, stale assertions and service outages must not select an unapproved weaker route.
- Offline devices cannot promise immediate receipt of a remote-wipe command. Revocation blocks server acceptance immediately; client crypto-shredding occurs when the applicable revocation/expiry control can execute, under the approved lost-device policy. An unlocked stolen device with an unexpired package remains an explicit risk, not a solved API claim.
- Crypto-shredding is the approved erasure term. Neither this plan nor a deletion callback proves physical flash erasure. Absolute offline time is not asserted; D-04's signed time basis and monotonic deltas preserve a bounded claim, with reboot/rollback cases requiring explicit disposition.
- If the first native proof cannot meet a mandatory D-04 property, return the evidence to A/E/K under D-36/D-37. Do not reinstate Option A or C, soften a gate, invent a TTL or present unsupported provenance as a pass.

## Incremental estimate — proposed, low confidence

A developer-day is one focused human engineering day including agent supervision and review. These are **additional native-specific tasks** beyond the existing Phase 1/2 shared-core, ordinary web, evidence-ingestion and generic offline/reconciliation work. Generic upload services, audit business rules, normal screens and the existing financial engine are not estimated a second time. Platform-specific adapters, negative tests and device evidence are included; accounts, vendor turnaround, policy decisions and independent scheduling are separate elapsed waits.

| ID | Additional native-platform work | Aminu days | Erastus days | Depends on |
|---|---|---:|---:|---|
| CAP-01 | Boundary/feasibility proof: stack, APIs, distribution/integrity and measurable failure contract | 1 | 1–2 | D-04; access to devices/accounts; unresolved values remain gated |
| CAP-02 | Thin shell, secure one-use handoff, device registration/session and key custody | 1 | 1–2 | CAP-01; approved device/access contract |
| CAP-03 | Camera-only native path and content-bound location/time/integrity envelope | 1–2 | 2–3 | CAP-02 |
| CAP-04 | Native encrypted package, kill/reboot recovery, expiry/revocation and crypto-shredding | 1–2 | 2–3 | CAP-02; D-36/D-37/D-40 |
| CAP-05 | Native resume/commit integration and web launch/status/recovery handoff | 1–2 | 1–2 | CAP-03/CAP-04; existing shared-core ingestion contract |
| CAP-06 | Both-platform adversarial tests, accessibility, device evidence, source/coverage gates and review pack | 1–2 | 2–3 | CAP-05 |
| **Total incremental effort** | **15–25 developer-days** | **6–10** | **9–15** | No extra human capacity implied |

Erastus's 9–15 additional days are the longer lane. With both named developers available, stable contracts and agent-assisted execution, reserve **2–3 additional focused engineering weeks**. The same developers are already allocated to the web MVP: this is reallocated capacity and must shift the portfolio window unless separately approved scope or staffing changes offset it. Do not divide total effort by an imaginary extra team or count native and web work simultaneously for one person.

The former eight-week stretch / Week 9 planning / Week 10 remediation window excluded this exception. The **candidate**, not approved, portfolio becomes Weeks **10–11 stretch**, **11–12 planning**, **12–13 remediation** from satisfied entry gates. Phase-by-phase calendar dates remain unset until A/E accept the split, the reduced device proof is available and the red contracts close. This does not postpone mandatory secondary trading or shrink its tests. Independent remediation beyond the modeled scope triggers another rebaseline, not a promise that Week 13 absorbs every failure.

The seven-day iOS dwell can overlap other engineering after it genuinely starts on a stable origin. It cannot be shortened by agents, converted into developer-days or claimed to have started from creation of this document. Native signing/distribution and independent assurance turnaround are not included in the 2–3-week allowance.

## Device and dwell execution pack

Use only disposable synthetic capture material and a stable authorized HTTPS test origin. Do not upload real customer documents, enable live provider calls or publish the harness without separate deployment authorization. Device matrix ownership remains **Erastus**. Record the exact model, OS/browser build, installation mode, origin, harness/build SHA, UTC start/end, permissions, synthetic fixture hashes and resulting artifact hash. An emulated viewport or desktop Chromium is not a physical-device row.

| Run ID | Existing matrix row | Required execution | Current status |
|---|---:|---|---|
| D04-ANDROID-INSTALLED | 1 | Mid-range physical Android/Chrome, installed | `NOT_RUN` |
| D04-IOS-INSTALLED | 3 | Physical iPhone/current supported iOS/Safari, installed | `NOT_RUN` |
| D04-IOS-TAB | 4 | Physical iPhone/current supported iOS/Safari, plain tab | `NOT_RUN` |
| D04-DWELL-01 | G3.5, installed iOS | Separate baseline and result with at least 168 elapsed hours | `NOT_STARTED` |
| D04-DWELL-02 | G3.5, plain-tab iOS | Separate baseline and result with at least 168 elapsed hours | `NOT_STARTED` |

Per device, follow the existing harness procedure for permissions, camera/gallery attempts, force-quit/reopen, offline two-item capture, reconnect and duplicate/reordered uploads. Record denied permissions as `BLOCKED`, not `FAIL` or `PASS`. Preserve counts/digests before and after each interruption. Record storage pressure, OS changes, origin interactions and install/uninstall events; an uncontrolled or reset observation must be marked inconclusive/restarted, not silently reused.

For both dwell modes, start from a captured baseline; keep the origin stable; do not open/poll the origin during the observation window. An automated reminder may remind the operator, but must not visit the origin and refresh its activity. Do not assume an eviction outcome: record observed persistence/loss and any caveat. If elapsed time is short, the origin changed or the observation was disturbed, the result remains pending/inconclusive. Review the evidence under Option B to size what the **web** half may retain, not to reopen the closed PWA-only option.

The later native acceptance matrix must additionally prove: denied camera/gallery injection; changed content/hash; wrong device or assignment; replay and sequence conflict; clock rollback/reboot; expired/revoked/lost key; revoked assignment before commit; kill/storage pressure/partial upload; duplicate concurrent commit; unsupported integrity/service outage; and web handoff cancellation or malicious return target. Synthetic assertions test the implementation but cannot certify Apple/Google or physical devices.

## Checklist and deliverables

- [x] Record the narrow boundary and incremental A/E estimate, keeping baseline work out of the addition.
- [x] Enter the candidate schedule adjustment and identify physical/elapsed-time evidence separately.
- [x] Prepare three device runs and two dwell records with honest unstarted states.
- [ ] A/E accept the estimate, framework/distribution proof and named allocation; record latest-SHA non-author review.
- [ ] K and the required owners close D-29/D-31/D-35/D-36/D-37/D-40 for the affected implementation; record permitted unsupported/expired/lost-device outcomes.
- [ ] Erastus exports the three physical-device records and both completed dwell records; a reviewer accepts the results and caveats.
- [ ] Implement CAP-01–CAP-06 under the applicable phase gates and pass D-65/D-66/D-67 plus browser/device/independent evidence.

Deliverables are this scoped plan, the machine-readable readiness record, the amended scheduling note and the future device/implementation/review artifacts. No native source, provider account, signed build, real-device run or financial behavior is delivered by this preparation.

## Acceptance criteria

Planning preparation passes when task IDs and estimates reconcile, the two-developer capacity model is explicit, each device observation has an accountable owner and an unambiguous evidence state, and the existing platform/policy boundaries remain unchanged. Native readiness requires the unchecked gates above. Passing the document-integrity tests proves neither device capability nor acceptance of the estimate.
