---
name: production-reads-need-approval
description: "Inspecting the production server (even read-only SSH) needs Erastus's explicit in-session approval; show the exact script first"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 9b2409bf-1995-4281-9a8b-b54e81cbf53a
  modified: 2026-09-14T08:14:05.022Z
---

On 2026-09-14 read-only SSH inspection of the Contabo box, and even local git reads tied to the
deployed `main`/`uat` releases, were blocked by the permission classifier as production reads until
Erastus approved them in chat. Once a reviewable, secret-safe script was shared and approved, it ran
without trouble.

**Why:** the box hosts production personal data, so looking at it is gated even when nothing changes.

**How to apply:** when a task needs production host facts, write the read-only script first (no
secret values printed — compare credentials on-host as booleans), send it to Erastus, and ask for
approval before the first SSH call instead of retrying after a denial. The standing
[[ship-to-production]] approval covers promotions, not ad-hoc host inspection or host changes.
See [[host-isolation-verification]].
