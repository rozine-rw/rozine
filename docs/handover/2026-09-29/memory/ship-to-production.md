---
name: ship-to-production
description: "Erastus has standing approval for Rozine changes to go all the way to production, without asking per release"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 478ce7a5-b65f-49c1-9184-5f53d81fc1c3
  modified: 2026-09-14T06:27:53.732Z
---

For rozine-rw/rozine, do not stop at `erastus-dev`, `dev` or `uat` and ask
whether to release. Erastus said on 2026-08-30: "Everything goes to production
from here on." Carry work through the whole chain — `feat/*` → `erastus-dev` →
`dev` → `uat` → `main` (on this machine work starts from `erastus-dev`, see
[[erastus-dev-base-branch]]) — and `main` auto-deploys to rozine.rw.

**Why:** Erastus found the per-release confirmations slow after seeing the
promotion work several times, and owns the repo and the infrastructure.

**How to apply:** still one PR per hop, still wait for the checks on each, and
still verify staging before `main` and production after it — the standing
approval removes the question, not the care. Keep flagging anything that
genuinely changes the decision (a failing gate, a schema change, a factual
error going live, another person's unpromoted work riding along) rather than
letting it through silently. See [[staging-deployment]] for the chain and
[[deploy-notes]] for the pipeline gotchas.
