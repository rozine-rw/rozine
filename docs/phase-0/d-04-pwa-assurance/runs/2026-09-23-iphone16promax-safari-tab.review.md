# Review note — 2026-09-23 iPhone 16 Pro Max, Safari, plain tab

**Artifact:** `2026-09-23-iphone16promax-safari-tab.json`, filed verbatim as the device produced it.
SHA-256 `5fc5327d45600b468dc96bf32c3fb3d4303c4855a6ffb39c0088a975e6fdc3fc`.

**Run status: D04-IOS-TAB is started, not complete.** Three of the export's PASS results are not
accepted as evidence, for the reasons below. The register records the row as `STARTED_INCOMPLETE`.

## Device and origin

| Field | Value |
|---|---|
| Device | iPhone 16 Pro Max (tester-reported; 440x956 @3x) |
| OS | iOS 27.0 (tester-reported from Settings) |
| Browser | Safari, `Version/27.0` |
| Mode | Plain browser tab (`displayMode: browser`, `iosStandalone: false`) |
| Origin | `https://rozine-d04-harness.pages.dev` (production URL, harness `bde0827`) |
| Session | 2026-09-23T05:16:31.951Z to 05:18:17.178Z (105 s) |
| Secure context | true |

Safari's user agent reports `CPU iPhone OS 18_7` while the tester reports iOS 27.0. Apple freezes
user-agent tokens, which is also why the existing desktop run in this directory reports
`Mac OS X 10_15_7`. The tester-reported version is authoritative; the harness cannot read it.

## Probes not accepted

All four manual probes in this export carry the detail `Confirmed by tester.`, which the harness
writes for its **Mark pass** button (`harness/app.js:896`). That button records a verdict without
performing the measurement. For two probes that is the intended mechanism; for three it is not.

| Probe | Recorded | Disposition | Why |
|---|---|---|---|
| G3.4 force-quit | PASS at 05:16:38.5Z | **PENDING** | 6.6 s after the page loaded, and 41 s before the automatic probes ran. A real force-quit ends the page session, so a genuine pass is recorded in a session that began after the baseline press. The measured action (`app.js:436`) reports a record count against a stored baseline, not this string. |
| G3.5 seven-day dwell | PASS at 05:17:04.4Z | **PENDING** | 32 s into a 105 s session. 168 h had not elapsed. The measured action (`app.js:466`) would have returned `PENDING — Dwell started …` and begun the clock. No dwell baseline exists, on this origin or any other. |
| G4.5 airplane mode | PASS at 05:17:47.1Z | **PENDING** | 19 s after G1.4. Airplane mode on, two captures, queue confirmed, airplane mode off and queue drained does not fit in that window, and no measured detail was recorded. |
| G1.4 gallery fallback | PASS at 05:17:28.3Z | **accepted** | A tester judgement by design; **Mark pass** is the correct mechanism. |
| G6.4 offline residual risk | PASS at 05:18:05.2Z | **accepted** | A Compliance judgement by design. Still needs Kimani's answer per `report.md` outstanding item 3. |

## Probes accepted from this run

Automatic probes ran on a real iPhone against the published origin, and their results stand. They
agree with the 2026-09-06 desktop run wherever that run was able to measure at all.

- **G1.1–G1.3 PASS.** Rear camera opened (`Back Triple Camera`, 1920x1080) and captured to an
  encrypted blob with no file picker. First physical-device confirmation of the camera-only path.
- **G1.5 PARTIAL.** Canvas capture carries no EXIF, no camera signature, no attestation.
- **G2.1–G2.3 PASS, G2.4 UNSUPPORTED.** Non-extractable AES key survives an IndexedDB round trip;
  no hardware or unlock binding exists on the web.
- **G3.1 BLOCKED.** Persistent storage refused in a plain tab. Measurable only in installed mode.
- **G3.2 PASS.** 41,231 MB free of a 41,232 MB quota.
- **G3.3 PASS.** 1 to 6 records, 0 malformed.
- **G4.1 PASS, G4.2 UNSUPPORTED.** Service worker controls the origin; `SyncManager` is absent, so
  the queue drains only while the app is open and foregrounded. Confirmed on real iOS.
- **G4.3, G4.4 PASS.** Exactly-once duplicate handling; chunked upload resumed after HTTP 503.
- **G5.1 PASS** (±12 m in 1,138 ms), **G5.2, G5.3 UNSUPPORTED**, **G5.4 PARTIAL**.
- **G6.1 PASS, G6.2 PARTIAL.**
- **G7.1, G7.2 PASS, G7.3 UNSUPPORTED.**

## G6.3 is mode-limited, not a platform verdict

G6.3 out-of-band remote wipe recorded `FAIL — ReferenceError: Can't find variable: Notification`.
On iOS the Notification API exists only in installed home-screen web apps, so a plain tab cannot
reach it. Treat this as **BLOCKED for this row**, the same way G3.1 is blocked, and measure it in
D04-IOS-INSTALLED. This does not soften `report.md`'s G6.3 finding, which already states that web
push on iOS needs a home-screen install and granted notifications.

## What this run does not change

No gate verdict in `report.md` changes, and the Option B recommendation is untouched. This run adds
physical-device confirmation for capability probes that previously had only a suppressed desktop
reading.

## Remaining work on this row

1. Reopen the same Safari tab on the same device and run the measured actions for **G3.4** and
   **G4.5**. Export again; that export supersedes this one for those probes.
2. **G3.5** cannot be satisfied in a session. Pressing **Recount after dwell** starts D04-DWELL-02,
   after which the origin must be left untouched for at least 168 h, with seven logged days of
   Safari use on unrelated sites per the accepted refinement 1. Screenshot the environment block and
   G3 before pressing anything.
3. D04-IOS-INSTALLED and D04-ANDROID-INSTALLED remain `NOT_RUN`.
