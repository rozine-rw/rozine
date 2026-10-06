# Auditor PWA assurance report — D-04

**Status:** `INTERIM — ENGINEERING FINDING COMPLETE, DEVICE MATRIX OUTSTANDING`
**Spike run:** 2026-09-06
**Author:** Erastus Kirui (Engineering/Security, Phase 1B lane)
**Required countersignatures:** Aminu (Engineering/Security), Kimani (Audit Operations/Compliance)
**Evidence:** `runs/2026-09-06-desktop-chromium-148.json`, harness at `harness/`

## Recommendation

**Fail closed into Option B** — a narrow thin-native secure-capture companion, with every ordinary
Auditor screen staying Inertia web/PWA.

This is the plan's own standing recommendation, and the spike now has the evidence for it.

## Why the device matrix cannot change this

Four of the seven D-04 gates are `UNSUPPORTED` on the web platform itself, not on a particular
device or browser version. No row of the device matrix can turn them into a pass, because there is
no API to call on any browser:

| Gate | Missing capability | Native equivalent that exists |
|---|---|---|
| G2.4 | No way to bind an encryption key to hardware or to device unlock. The offline package key is protected by browser origin isolation alone. | iOS Keychain with Secure Enclave and `…ThisDeviceOnly`; Android Keystore with StrongBox and user-authentication-bound keys. |
| G5.2 | No mock-location flag and no provider identity. A developer-options mock, a patched browser, or a devtools sensor override is indistinguishable from a genuine fix. | Android `Location.isFromMockProvider()`; platform location-integrity signals. |
| G5.3 | No app or device integrity attestation. The page cannot prove it is unmodified, nor that the device is not rooted or emulated. | iOS App Attest; Android Play Integrity. |
| G6.3 | No dependable out-of-band remote wipe. Web Push is best-effort, needs a home-screen install and granted notifications on iOS, and there is no MDM or OS hook. | MDM wipe, keychain invalidation on biometric change, attestation revocation. |

D-04 names `location/provenance` and `revocation` as mandatory gates. G5.2, G5.3, and G6.3 sit
squarely inside them. **The Option A exit rule is therefore already unsatisfiable**, and no further
device testing can rescue it.

One precision, so the record is accurate rather than merely damning: Safari does support Private
Access Tokens, which let an origin server verify that a request came from a genuine Apple device.
That is a network-time anti-fraud signal about the *device*, not an attestation that the *client
code* is unmodified, and it is unavailable at exactly the moment that matters — an offline capture
in the field. It does not close G5.3.

## What the web platform does do well

Option B keeps every ordinary Auditor screen on the web, so these results are not academic; they
define how much stays out of the native component.

| Gate | Finding |
|---|---|
| G1.1–G1.3 | `getUserMedia` to canvas to encrypted blob is genuinely camera-only. No file input, so no gallery path exists to close. Capture never touches user-chosen storage. |
| G2.1–G2.3 | A non-extractable AES-GCM key survives an IndexedDB round trip and still decrypts; `exportKey` rejects. Confidentiality at rest is real, and only the custody guarantee is weak. |
| G3.3 | IndexedDB writes commit atomically. Five records in, five out, none malformed. |
| G4.3 | Duplicate and reordered submissions commit exactly once: five submissions of three distinct items produced three commits and two duplicate rejections. |
| G4.4 | A chunked upload interrupted at chunk three resumed and committed all eight chunks once each. |
| G6.1 | A client-enforced package TTL evaluates on open and can shred without any network. |
| G7.1–G7.2 | Crypto-shredding works: destroying the key left the ciphertext permanently unrecoverable. Stores delete cleanly. |

Exactly-once sync, resumable upload, atomic durability, encryption at rest, and crypto-shredding are
all achievable on the web. The gap is not competence at handling evidence — it is the inability to
prove anything about the client that produced it.

## Caveats carried forward

| Gate | Caveat |
|---|---|
| G1.5 | Canvas capture discards EXIF, so all provenance is app-generated. That is preferable to trusting device EXIF, but it is only as trustworthy as the client — which G5.3 says is unattested. |
| G5.4 | `Date.now()` is whatever the auditor sets. A server-signed time token issued with the package, plus monotonic deltas since open, bounds drift within a session but cannot establish absolute time offline. |
| G6.2 | Revocation lands only when the device next reaches the server. Until then the package stays readable. |
| G7.3 | No browser API verifies erasure of the physical storage. Crypto-shredding is the compensating control and must be named as the approved erasure standard. |

## The iOS storage cliff

Safari deletes all script-writable storage — IndexedDB, Cache API, and the service worker
registration — for an origin the user has not interacted with for seven days. Home-screen web apps
are exempt; plain browser tabs are not.

For a field auditor this is the difference between an app that keeps a week-old unsynced visit and
one that silently loses it over a public holiday. Option A on iOS would depend on every auditor
installing to the home screen and never being talked out of it, which is not an enforceable control.
G3.5 is the probe that measures this and it takes seven calendar days, so it must be started on day
one of any matrix run.

## Scope of the Option B exception

Recommended boundary, for Aminu and Kimani to confirm:

**Native (narrow, secure-capture only)**
- Camera capture with hardware-attested provenance.
- Evidence encryption with Keychain/Keystore custody, bound to device unlock.
- Location acquisition with mock-provider detection.
- App Attest / Play Integrity assertions bound to each captured item.
- Encrypted offline package storage, its ordered upload queue, and remote wipe.

**Web/PWA (everything else)**
- Accreditation, licence, agreement, and academy screens.
- Job list, dispatch acceptance, decline, and conflict declaration.
- Checklist and procedure execution, source/parsed/on-site comparison.
- Reconciliation, filing, co-signature, and seal.
- Portfolio, earnings, and report history.

This keeps the native surface small enough to stay outside the MVP's Inertia architecture without
forking the domain, and it matches the plan's routing note: full native role clients remain Phase 5.

## Outstanding work before D-04 can be signed

1. Run the reduced device matrix — rows 1, 3, and 4 of `README.md` — to size the web/native
   boundary above. The purpose is no longer to test Option A; it is to confirm how much of the
   Auditor journey can safely stay on the web under Option B.
2. Start G3.5 (seven-day dwell) on day one of that run, on both an installed and an uninstalled iOS
   origin.
3. Get Kimani's Compliance answer on G6.4: is a stolen unlocked device holding an unexpired
   encrypted package, openable by anyone with the browser profile, acceptable for governed audit
   evidence? A "no" independently confirms Option B.
4. Estimate the thin-native component and record it as a scoped MVP exception with an owner, so it
   does not silently become Phase 5 work.
5. Countersign and close D-04.

## Effect on the plan

- Phase 0's PWA assurance spike item can close once items 1–5 above are done.
- Phase 0 acceptance criterion "The web/PWA Auditor route is either proven capable in principle or
  replaced by a documented, estimated thin-native exception" is met by the Option B route, not the
  Option A route.
- Phase 1B's Auditor screens remain scoped as web/PWA and are unaffected; the online Auditor alpha
  was always Phase 1 and the offline capture work was always Phase 2.
- The thin-native component needs an owner and an estimate that the current two-developer Phase 1
  plan does not carry.
