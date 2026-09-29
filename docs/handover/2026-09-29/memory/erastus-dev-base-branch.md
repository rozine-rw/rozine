---
name: erastus-dev-base-branch
description: "SUPERSEDED 2026-09-27: Rozine work now goes feat/worktree → dev directly; erastus-dev is retired (it doubled CI time)"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 39184b4e-2867-4960-913f-072766a49b6e
  modified: 2026-09-27T07:28:17.695Z
---

**Current rule (Erastus, 2026-09-27):** branch new work from `origin/dev` and open PRs straight into `dev` (worktree → `dev` → `uat` → `main`). Do not use `erastus-dev` anymore. This matches the repo CLAUDE.md ("Branch new work off `dev`").

**Why:** every change ran the full hosted CI (~45–60 min) twice — once into `erastus-dev`, again on the `erastus-dev` → `dev` promotion PR. Erastus asked to drop the hop to save that time.

**Dev fast lane (Erastus, 2026-09-27, #150 merged `b4d599fa`):** PRs into `dev` merge once the fast checks are green (TypeScript/React gate, deployment admission, PHP negative-control groups), their own tests pass locally, and the one review at the exact head is done — no waiting for the full PHP coverage gate/PostgreSQL lane, which run on the dev push. A red dev run is fixed forward immediately and blocks promotion. uat/main keep every check + deployment admission. Erastus also said: don't let draft PRs slow you down — I may approve and merge drafts.

**How to apply:**
- Cut `feat/*` from `origin/dev`; PR base `dev`; for the web coverage gate use `CLIENT_COVERAGE_TARGET_SHA=$(git rev-parse origin/dev)`.
- Never branch off `main`. Still one PR per hop for `dev` → `uat` → `main` ([[ship-to-production]]).
- Leave the `erastus-dev` branch in place (don't delete it without asking); it was last synced with `dev` at the final promotion (#142, carrying #139/#140).
- **Stacked PRs strand work** (2026-09-24, #105/#106): if a PR is stacked on another feature branch, retarget it to `dev` the moment its base merges.

History: 2026-09-14 → 2026-09-27 the chain was `feat/*` → `erastus-dev` → `dev`.
