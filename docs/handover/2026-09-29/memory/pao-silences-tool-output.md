---
name: pao-silences-tool-output
description: "pao swallows PHP fatal errors on any version; a PHP tool exiting non-zero with no output means re-run with PAO_DISABLE=1"
metadata: 
  node_type: memory
  type: project
  originSessionId: a4a71da1-d43b-4cba-9923-f47a984701a0
  modified: 2026-09-08T07:59:03.366Z
---

On PHP 8.4 (Herd's php84, 8.4.23), `vendor/bin/phpstan analyse` exited 1 and printed
absolutely nothing — no errors, no summary, empty stdout and stderr — on any input including a
one-line file with no config. `PAO_DISABLE=1` restored the output, revealing a second 8.4-only
problem underneath: PHPStan crashed in a parallel worker at PHP's 128M default memory limit.

**Partly resolved 2026-09-08** by moving the machine to PHP 8.5 (see [[staging-deployment]] for the
D-73 runtime contract). On 8.5.8, normal PHPStan runs — pass or fail with errors — report properly
with neither `PAO_DISABLE` nor a memory flag.

**Still silent on a PHP fatal error.** Corrected 2026-09-09: a duplicate `use` statement made
PHPStan exit **255 with zero output** on 8.5, exactly as on 8.4. `PAO_DISABLE=1` showed the fatal
immediately. So pao formats PHPStan's own output fine but swallows a fatal during analysis.

**Why:** the silence cost real damage once — the gate looked broken rather than red, so it got
skipped, and ten type errors reached `dev`.

**How to apply:** any PHP tool exiting non-zero with **no output at all** — 255 especially — means a
fatal error pao is swallowing. Re-run with `PAO_DISABLE=1` to see it. This is not version-specific
and did not go away with 8.5.

Never treat a silent non-zero exit as "the tool is broken, skip it" — the negative-control harness
in `scripts/quality/verify-negative-controls.sh` now refuses to trust a gate it cannot see run, for
exactly this reason.
