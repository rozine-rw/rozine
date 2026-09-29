---
name: checkpoint-2-exit-list
description: "Agreed fixed exit list for Phase 1 checkpoint 2 (PR #101), with slice order and lanes, confirmed by Hussain on #96 on 2026-09-24"
metadata:
  node_type: memory
  type: project
  originSessionId: 94be1f2e-5e3d-4621-a5b3-6bff0afd9745
  modified: 2026-09-24T14:51:32.235Z
---

**Status: ACCEPTED 2026-09-25.** PR #101 merged to `dev` as `bb0fa320` from candidate `c50e035e`, on Aminu's authorization. Checkpoint 3 starts on a new branch from `dev`. **Post-C2 tail closed 2026-09-26:** #119–#128 merged, including N6 (#126: a 24h co-sign window, 1 signatory, auto-approve, disputes with CPA then staff), the walkthrough fixes (#121), navigation (#124) and the manual test pack (#123). Only a small nav-binding UI PR remained. C3 kickoff was posted on #96 (AC-02/AC-03 first).

Checkpoint 2 has a fixed exit list, proposed on #96 (comment 5816417397) at Erastus's request and confirmed by Hussain on 2026-09-24.

- **Server (Hussain), in order:**
  1. **S-C:** Auditor own-conflict receipts/register, scoped offer and job lists, naming/deadline map, minimal Operations redispatch/close.
  2. **S-A and S-B together:** immutable quote and evaluate command, review-step advancement, signatures/submission; persisted underwriting bound to the verified snapshot.
  3. **S-D:** audit procedure steps, TOTP step-up, seal, request changes/reject/amend, business co-sign by the 7th. Persisting and viewing the sealed report is part of S-D.
  4. **Joint close.**

  **S-E** (web and `/api/v1` routes plus Resources) ships inside each slice.
- **UI (us):** each binding starts as soon as its slice is reviewed.
  - U-A: Business Apply live.
  - U-B: Jobs, job file and Portfolio live.
  - U-C: procedure, seal and co-sign live.
  - U-D: committed browser journeys.
- **Close:**
  - J-1: requirement audit with full gates at the exact SHA.
  - J-2: mutual exact-candidate reviews.
  - J-3: merge #101 into `dev`. Checkpoint 3 then starts on a new branch; no checkpoint 3 work goes into #101.
- **Out of scope:**
  - publish/listing and investor purchase (checkpoint 3);
  - offline (Phase 2);
  - D-04 native device attestation, which stays an open MVP gate;
  - provider and regulator certification.

  Synthetic legal and provider inputs are allowed only in isolated Alpha. C6 stays open, and checkpoint acceptance does not authorize promotion to UAT or main.
- **Cadence:** review per finished slice at an exact SHA, batching ordinary fixes; blockers are reported immediately.

**Why:** Erastus asked on 2026-09-24 whether there was an end in view. #101 kept growing through per-commit review loops, so the list gives checkpoint 2 a defined finish.

**How to apply:** check each new #101 slice against this list. Refuse scope creep into #101. Start the matching UI binding as soon as a slice is marked ready. Don't treat foundation approvals as whole-checkpoint acceptance. Related: [[gh-issues-comms]], [[phase-1b-ui-decisions]].
