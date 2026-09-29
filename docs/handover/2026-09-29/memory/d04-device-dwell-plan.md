---
name: d04-device-dwell-plan
description: "Issue #90 D-04 physical-device runs and iOS dwells: hosting choice, devices, the Safari-use-days caveat, and what it's blocked on"
metadata: 
  node_type: memory
  type: project
  originSessionId: 9b2409bf-1995-4281-9a8b-b54e81cbf53a
  modified: 2026-09-14T08:42:05.221Z
---

Issue rozine-rw/rozine#90: Erastus owns three physical-device runs (Android installed, iPhone
installed, iPhone tab) and two ≥168-hour iOS dwell observations using the harness in
`docs/phase-0/d-04-pwa-assurance/harness` (last changed `bde0827`).

**Decided 2026-09-14 (Erastus):** host the harness on a dedicated Cloudflare Pages project (planned
name `rozine-d04-harness`) — `pages.dev` is on the Public Suffix List, so it is its own site, away
from rozine.rw and off the Contabo box. Devices: a mid-range Android with Chrome and a **spare** iPhone
on current iOS. Preparation and five proposed procedure refinements were posted on #90 for Aminu to
accept.

**Published 2026-09-14:** https://rozine-d04-harness.pages.dev/ (Cloudflare Pages, Erastus's
account, production deployment `aaef1544`, files byte-identical to `bde0827`). Pages 308-redirects
`/index.html` → `/` by default, which would break the harness's offline installed-app launch
(`start_url` and the SW precache both use `./index.html`), so the deploy adds a `_redirects` file with
`/index.html / 200`. Keep that rule on any redeploy — and don't redeploy once device runs start.
**Next:** Erastus's hands-on device session (deferred by him on 2026-09-14), then 7+ days.

**Non-obvious rules to keep:** WebKit's cap deletes tab storage after 7 *days of Safari use*, not
calendar days, so the tab dwell needs Safari used on other sites on ≥7 logged days. Installed
home-screen apps are exempt. Use only the Pages production URL (per-deployment hash URLs are
different origins), no redeploys during a dwell, and on reopening screenshot before pressing
"Recount after dwell" — if storage was evicted, that press silently starts a new dwell.

**Deferred 2026-09-28 (Erastus):** set D-04 aside; it will be conducted during UAT with real users. Exclude it from MVP estimates and schedule talk until then.

Related: [[host-isolation-verification]].
