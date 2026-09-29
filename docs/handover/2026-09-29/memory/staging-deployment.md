---
name: staging-deployment
description: "How Rozine's staging (staging.rozine.rw) AND production (rozine.rw) are hosted on one Contabo box, deployed, and wired to CI/CD"
metadata: 
  node_type: memory
  type: project
  originSessionId: 15577510-fb24-46f1-a7aa-eea61915c6a8
  modified: 2026-09-14T08:13:37.877Z
---

Rozine staging was set up 2026-07-29; production added on the same box 2026-07-30. **Both environments share one Contabo VPS.**

**Host:** Contabo VPS (Cloud VPS 4), Ubuntu 24.04, IPv4 `<redacted-ip>` (4 cores / 7.8 GB — plenty of headroom). SSH via key alias `rozine-staging` in `~/.ssh/config` (key `~/.ssh/rozine_staging`, user `root`). Contabo panel: <contabo-panel-url>

**Shared stack:** nginx 1.24 + PHP 8.5-FPM (installed 2026-09-12; both vhosts point at `php8.5-fpm-rozine{,-prod}.sock`, CLI `php` → 8.5.10; the old php8.4-fpm service still runs idle pools) + PostgreSQL 16.15 (localhost only) + Node 24.15.0 (NodeSource apt, `apt-mark hold`) / Composer. App roots owned `deploy:deploy`, `.env`/storage `deploy:www-data`; the single `deploy` user runs both apps' FPM pools, queue workers, cron schedulers and deploys (shared — see [[deploy-notes]] and [[host-isolation-verification]]). nginx http-level `fastcgi-buffers.conf` fix applies to both vhosts. Let's Encrypt via certbot with auto-renew. Host clock is CEST (UTC+2).

**Staging (staging.rozine.rw ← `uat`):** app `/var/www/rozine`, db `rozine_staging` / role `rozine`, FPM pool `rozine` (`php8.4-fpm-rozine.sock`), queue `rozine-queue`, workflow `.github/workflows/deploy-uat.yml`.

**Production (rozine.rw + www ← `main`):** app `/var/www/rozine-prod`, db `rozine_production` / role `rozine_prod`, FPM pool `rozine-prod` (`php8.4-fpm-rozine-prod.sock`), queue `rozine-prod-queue`, workflow `.github/workflows/deploy-prod.yml`. APP_ENV=production. Running the waitlist here for now.

**Secrets & CI:** both deploy workflows rsync source then SSH-build on the server as `deploy`, using GitHub secrets `STAGING_SSH_KEY` / `STAGING_SSH_HOST` / `STAGING_SSH_USER` (these are really "server access", reused by prod too). `tests.yml` runs CI on all PRs. Server `.env` files hold DB passwords, APP_KEY, and the Resend key — never in git.

**Mail:** Resend SMTP (`smtp.resend.com:465`, smtps, user `resend`, from `noreply@mail.rozine.rw`; verified Resend domain `mail.rozine.rw`). Both envs currently share one Resend sending key.

**Branch flow:** `erastus-dev`/`aminu-dev` → `dev` → `uat` (deploys staging) → `main` (deploys prod). Work on this machine starts from `erastus-dev` — see [[erastus-dev-base-branch]].

To deploy: merge to `uat` (staging) or `main` (prod). To run a server command: `ssh rozine-staging '...'`.
