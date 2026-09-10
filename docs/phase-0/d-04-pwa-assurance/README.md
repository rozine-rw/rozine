# D-04 — Auditor capture assurance spike

**Phase 0 checklist item:** "Run a time-boxed PWA assurance spike for Auditor offline packages,
in-browser camera-only capture, geolocation, timestamp/provenance, process interruption, durable
local encryption, reconnect, conflict, and sync. Record a thin-native MVP exception if any mandatory
guarantee cannot be met."

**Decision it feeds:** D-04, which chooses between

- **Option A** — web/PWA-only Auditor capture, permitted *only* if this spike proves every gate;
- **Option B** — a narrow thin-native secure-capture companion, with all ordinary Auditor screens
  staying Inertia web/PWA;
- **Option C** — defer protected capture and call the result Alpha/demo rather than the governed MVP.

The plan's standing instruction is to **fail closed into Option B** if any mandatory guarantee fails.
This spike exists to make that call on evidence, not on preference. Its time box is two days.

## What is here

| Path | What it is |
|---|---|
| `harness/` | Self-contained vanilla-JS PWA that probes each gate. No build step, no framework, imports nothing from `resources/js`. |
| `runs/` | One exported JSON per device run. These are the evidence. |
| `report.md` | The assurance report. Findings, verdict, and the Option A/B/C recommendation. |

The harness is measurement apparatus, not a proposed implementation, and it is deliberately outside
`resources/js` so it stays out of the D-66/D-67 source and risk manifests. Nothing in it ships.

## Gate families

Each maps to one clause of D-04. A gate's verdict is the worst status any of its probes reached.

| Gate | Question it answers |
|---|---|
| G1 | Can capture be camera-only, with no reachable gallery path? |
| G2 | Can the offline package be encrypted at rest with defensible key custody? |
| G3 | Does evidence survive process kill, restart, and storage eviction? |
| G4 | Does the queue drain in order, exactly once, and resume after interruption? |
| G5 | Can location, time, and device provenance be trusted? |
| G6 | Can an assignment be revoked and a lost device wiped? |
| G7 | Can evidence be purged in a way an auditor's regulator would accept? |

## Statuses

| Status | Meaning |
|---|---|
| `PASS` | Measured and met. |
| `PARTIAL` | Met with a caveat that must be carried into the report. |
| `FAIL` | Measured and not met on this platform. |
| `UNSUPPORTED` | The platform has no API for this at all. A permanent finding, not a retry. |
| `BLOCKED` | **Not measured here.** A permission was denied or the environment suppressed it. Re-run properly; never read as a pass or a fail. |
| `PENDING` | Not run yet, or a manual probe awaiting the tester. |

`BLOCKED` exists because an automation browser denies camera, location, notifications, and
persistent storage by default. Treating those denials as failures would fabricate a case against
Option A, which would be as dishonest as fabricating one for it.

## Running it

### Locally

```bash
php -S 127.0.0.1:8099 -t docs/phase-0/d-04-pwa-assurance/harness
```

Then open `http://localhost:8099`. Localhost counts as a secure context, so camera, geolocation,
service workers, and Web Crypto all work without a certificate.

### On real devices

Camera, geolocation, service workers, and installability all require a secure context, so a phone
needs HTTPS. Either is fine:

- a tunnel to the local server (`cloudflared tunnel --url http://localhost:8099`), which is fastest
  for a single session; or
- a static copy served under `staging.rozine.rw`, which is better if the seven-day dwell probe
  (G3.5) needs the URL to stay alive.

Do not test iOS storage behaviour through a tunnel that changes hostname between sessions — the
seven-day eviction rule is per-origin, so a new origin resets the clock and voids the result.

## Device matrix

The spike is not complete until every row has an exported run in `runs/`.

| # | Device | Browser | Mode | Why this row |
|---|---|---|---|---|
| 1 | Android mid-range (the realistic auditor phone) | Chrome | Installed to home screen | The expected shipping configuration. |
| 2 | Android mid-range | Chrome | Plain browser tab | Shows what an auditor who never installs gets. |
| 3 | iPhone, current iOS | Safari | Installed to home screen | The only iOS configuration with persistent storage and Web Push. |
| 4 | iPhone, current iOS | Safari | Plain browser tab | Exposes the seven-day eviction cliff. |
| 5 | iPhone, one major iOS version back | Safari | Installed | Auditors will not all update. |
| 6 | Android low-end / older WebView-based browser | Chrome or OEM default | Plain tab | Worst realistic case. |

Desktop Chromium is a capability baseline only. It is not a matrix row and cannot close a gate.

## Procedure per run

1. Open the harness. Confirm `secureContext: true` in the environment block.
2. On rows that call for it, install to the home screen first, then reopen from the icon and confirm
   `displayMode: standalone`.
3. Press **Run all automatic probes**. Grant every permission prompt.
4. Work the manual probes in order, following the instruction printed on each:
   - **G1.4** — try to reach the photo library from the capture flow. Any route that works is a fail.
   - **G3.4** — press *Recount records* to set a baseline, fully force-quit (not background), reopen,
     press *Recount records* again.
   - **G4.5** — airplane mode on, capture twice, confirm queueing, airplane mode off, confirm each
     item lands exactly once.
   - **G6.4** — a Compliance judgement, not a technical one. Record the decision and its owner.
   - **G3.5** — start the dwell, then leave the device alone for seven full days. This is the one
     probe that cannot fit inside the two-day box; start it on day one of the spike on every device.
5. Press **Export result JSON** and commit the file to `runs/` as
   `YYYY-MM-DD-<device>-<browser>-<mode>.json`.

## Exit rule

Option A may be recommended only if every gate on every matrix row is `PASS`, with no `FAIL`, no
`UNSUPPORTED`, and no unresolved `PARTIAL` on a mandatory guarantee. Anything else fails closed into
Option B, and the report must state which specific gate forced it. A `BLOCKED` probe is not a
result; the run is incomplete until it is re-measured.
