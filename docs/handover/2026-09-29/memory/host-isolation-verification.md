---
name: host-isolation-verification
description: "Issue #89 read-only host audit of the shared Contabo box (2026-09-14): what failed, the urgent SSH exposure, and the UAT promotion trap"
metadata: 
  node_type: memory
  type: project
  originSessionId: 9b2409bf-1995-4281-9a8b-b54e81cbf53a
  modified: 2026-09-14T08:42:09.186Z
---

Issue rozine-rw/rozine#89 (assigned to Erastus, reviewer Aminu) asked for read-only proof of
staging/production isolation. Evidence and a PASS/FAIL matrix were posted as issue comments on
2026-09-14; the reusable script lived only in the session scratchpad, but its full text is in the
second #89 comment (collapsed) — copy it from there to re-verify after remediation.

**Found 2026-09-14:**
- **Fixed same day (Erastus approved):** SSH was password-enabled for root with ~65k failed attempts
  a week. Now key-only via `/etc/ssh/sshd_config.d/01-rozine-hardening.conf` (sorts before
  `50-cloud-init.conf`, which still says `PasswordAuthentication yes` — keep the drop-in), plus
  fail2ban (sshd jail, systemd backend, nftables). Root password still set on purpose, as the Contabo
  VNC console fallback. The only accepted password login ever was the 2026-07-29 key install.
- One `deploy` user/key deploys both environments; repo-level `STAGING_SSH_*` secrets, no GitHub
  Environments. Both FPM pools, queue workers and cron run as `deploy`, which can read prod `.env`
  and write the prod app root. `www-data` can read both `.env` files.
- PostgreSQL: table grants are separate, but PUBLIC still has CONNECT/TEMP on `rozine_production`,
  so the staging role `rozine` can connect to it. No `rozine_uat` DB/role exists.
- Staging and production share the same Resend SMTP credential; outbound traffic is unrestricted.
- No database backups found on the host (Contabo panel snapshots unchecked).
- Deployed releases: staging `97c211f`, production `661480a` — neither has the Phase 0 safeguards.

**Why it matters:** the merged UAT guard (`isolation:check --expect=uat`) will refuse the current
staging host (DB/role must be `rozine_uat`, no SMTP credentials, `public/storage` must target
`storage/isolated/uat/public`). Because rsync replaces the code *before* `composer install` boots the
app, promoting `dev` → `uat` before fixing the host aborts the deploy AND leaves staging erroring.

**How to apply:** before any uat promotion, check these prerequisites are done; before claiming a
boundary holds, re-run the script. Production reads over SSH need the user's explicit approval in the
session (see [[production-reads-need-approval]]). Related: [[staging-deployment]], [[deploy-notes]],
[[d04-device-dwell-plan]].
