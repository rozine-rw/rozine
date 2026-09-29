---
name: phase-1b-ui-decisions
description: "Erastus's 2026-09-23 decisions for building the Phase 1B MVP UI from the Claude Design project (dark mode, desktop width, login, scope, fixture data)"
metadata:
  node_type: memory
  type: project
  originSessionId: 94be1f2e-5e3d-4621-a5b3-6bff0afd9745
  modified: 2026-09-23T18:19:22.561Z
---

Phase 1B (Erastus's Inertia React UI lane) is built to match the Claude Design project
"Rozine" (https://claude.ai/design/p/21934a13-54ff-4a1b-a89a-318968f2394d, "Rozine Suite" + the
`Investor/Business/Auditor/Admin.dc.html` sources, `rozine-core.js`, `CLAUDE.md` locked rules).
Hussain Aminu (= "Aminu" in the plan) builds 1A server/API and 1C integration.

Decided by Erastus on 2026-09-23:
- **Fidelity:** "EXACTLY like the design", desktop + mobile, light + dark.
- **Dark mode:** the design is light-only. Claude derives a dark palette from the brand (navy
  `#0c1830` base, Admin's dormant `th*` dark tokens as seed), shows launcher + one screen per app in
  both modes, and rolls it out only after Erastus approves.
- **Desktop:** not the prototype's fixed 1113×750 frame. Fill the window but **cap the width**
  (~1440px, centred on the page background); pixel-match the design at 1113×750.
- **Login (revised same day):** each app keeps **its own auth design exactly as designed** —
  Investor intro/role/sign-up/login/KYC, Business login + TIN/OTP sign-up, Admin console sign-in
  (Auditor has none in the design). One account/session underneath, so switching apps needs no
  second login. Use design copy verbatim; list policy-conflicting lines (e.g. "Sell back to
  Rozine") for Robert's review rather than rewriting them. (Earlier "one Investor-style login for
  all" answer is superseded.)
- **Dark palette:** the derived dark launcher (navy #0a1220 page, #101a2e cards, lifted accents)
  was approved on 2026-09-23 — carry it into every app.
- **Scope:** the plan's Phase 1B / crosswalk screens only, each exact to its design screen. Plus,
  lessons, insights, automation execution and secondary trading wait for their phases.
- **Data:** typed prop contracts + deterministic frontend fixtures rendered via local/testing-only
  preview routes; swap to real Resources as Hussain ships them.
- **Hussain comms:** via GitHub issues. Since 2026-09-24 Claude may post there without showing
  Erastus first — see [[gh-issues-comms]].
- **No TIN anywhere (BRS AC-9):** wherever the design shows/collects a TIN (Business sign-up,
  hero, wizard, confirm row; Investor institution form), keep the layout but use the **RDB company
  code** instead. Approved by Erastus 2026-09-23.
- Design bugs (client-side money maths, fee shown ≠ fee charged, auditor Pass/Fail judgement) are
  fixed, not copied: UI shows server facts only.
- **Business scope widened (2026-09-23):** besides the plan's 1B list, build every crosswalk MVP
  screen Home/tab bar links to (Profile, Wallet, Rating/financial health, Repayments, Audit prep) —
  no dead links. On Profile: company name read-only (RDB), no "Unlink" on the payout bank (support
  only), and a read-only certificate + signatories card in the design's card style; list each
  deviation for Robert.

How to get design source: the claude.ai design API works from a signed-in page
(`/design/anthropic.omelette.api.v1alpha.OmeletteService/ListFiles|GetFile`, JSON, base64
content); the built-in browser pane blocks downloads, so bundle files into one JSON and download via
Claude in Chrome (Erastus had to allow multiple downloads once).

Related: [[erastus-dev-base-branch]], [[rozine-frontend]].
