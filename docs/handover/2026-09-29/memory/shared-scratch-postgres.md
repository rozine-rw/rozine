---
name: shared-scratch-postgres
description: "The scratch PostgreSQL (port 5439, rozine_test) is shared with Hussain's agent on this machine; reserve windows on #96 and never stop the service"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 94be1f2e-5e3d-4621-a5b3-6bff0afd9745
  modified: 2026-09-25T18:35:57.337Z
---

The scratch PostgreSQL on port 5439, with its `rozine_test` database, is shared between our agents and Hussain's agent. They run on the same machine.

**Rules:**
- Take turns in announced "DB windows" on #96. Post when you take one and when you release it.
- Run one full suite at a time.
- While someone else holds the window, only use a private database name (via a `DB_DATABASE` override) on an already-running server, or do static or web-only work.
- **Never stop or restart the service** when releasing a window. Leave it in the running state you found it in. Hussain asked for this on 2026-09-25, because stopping it interrupted his setup.

**Better option for reviews and our own suites:** start a private cluster with `initdb` into its own scratchpad directory, on another port such as 5441. Run the tests there, then stop and delete it afterwards. This never contends with the shared service. Seed it with CI's `.github/scripts/prepare-test-databases.sql`, not a hand-made superuser `rozine_test` role; otherwise 11 tests in `DemoSafeguardsTest`/`PostgreSqlConfigurationTest` fail. The scratchpad path is too long for a Unix socket, so connect over TCP (`-h <redacted-ip>`).

**Why:** concurrent `migrate:fresh` runs on `rozine_test` caused deadlocks, "relation does not exist" failures and false test results on 2026-09-25.

**How to apply:** give every agent prompt that touches PHP tests these rules explicitly.

Related: [[gh-issues-comms]], [[pao-silences-tool-output]].
