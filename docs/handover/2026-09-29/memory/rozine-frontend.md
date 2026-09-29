---
name: rozine-frontend
description: "Rozine brand assets/colors, the document <head> setup (laravel/head), and the CI coverage gate"
metadata: 
  node_type: memory
  type: reference
  originSessionId: 15577510-fb24-46f1-a7aa-eea61915c6a8
  modified: 2026-07-30T07:11:07.898Z
---

**Brand:** "Blue leads." The mark is the **star**, not the wing — rebranded 2026-08-30. Source artwork is 31 SVG/PNG files in `docs/New Logo/` (`Frame 83`–`Frame 113`), a system rather than a pile: four colourways (blue `#0039FF`, green `#1D9E75`, orange `#C2661F`, crimson `#BA0D3B`), each with a **positive** lockup (mark on white) and a **reversed** one (white mark on colour), square marks in rounded (`rx=800`) and full-bleed forms, black/white mono variants, and three **sub-brand** wordmarks — `Frame 108` "investor" (blue), 109 (green), 110 (orange). Key files: **83** = primary horizontal lockup, **88** = white star on blue rounded square, **112** = same full-bleed (for maskable icons). The old wing survives only at `public/images/rozine-wing-white.png` for the Pulse pass card; app mark is `public/images/rozine-star-white.png`.

**Two blues, deliberately:** the marketing site paints `#1e3aff` (61 occurrences, from the design export) while the new artwork is `#0039FF`. They differ 6.8%, entirely in the red channel, and are indistinguishable side by side — so the palette was left alone rather than risk the verified design match.

**Favicons/icons:** generated from `docs/New Logo` with `rsvg-convert` + ImageMagick — `Frame 88` for favicon.svg/favicon-96/apple-touch/192/512, `Frame 112` for the **maskable** icon (Android crops it itself, so it must be full-bleed, not rounded). `favicon.ico` is multi-resolution 16/32/48 via `magick i16.png i32.png i48.png favicon.ico`. **STALE:** `public/og-image.png` still shows the wing and needs a designed 1200×630 card; the Pulse pass card also still repeats the wing as a tuned 36×20 watermark tile.

**Logos in-app:** sidebar (`app-logo.tsx`) = white star `<img>` on a `bg-[#0039ff]` tile. `app-logo-icon.tsx` (mobile header + the 3 auth layouts) masks the star via **CSS mask + `background-color: currentColor`** so it recolors with `fill-current`/`text-*`. The star is square, so its `aspectRatio` is `1 / 1` — the wing's was `766 / 384`.

**Social accounts:** `www.instagram.com/rozineapp`, `x.com/rozineapp`, `www.linkedin.com/company/rozine/`, `hello@rozine.rw`. The design export shipped placeholder `rozine.rw` handles; `resources/js/pages/home.tsx` is **generated**, so the rewrite lives in the generator, not the output.

**Document <head>:** managed by **laravel/head** (`composer require laravel/head`). Config lives in `AppServiceProvider::configureHead()` — `Head::inertiaGlobals()` for favicons/manifest/theme-color, `Head::defaults()` for description/canonical/OG/Twitter/env-aware robots; rendered by `@head` in `resources/views/app.blade.php`. NOTE: `Head::defaults()` evaluates its closure **eagerly at boot**, so `app()->isProduction()` there reflects boot-time env (prod=index, staging=noindex). The `<title>` stays managed by Inertia (serverHead needs Inertia ≥3.5; installed is 3.2). See [[deploy-notes]].

**CI gate (important):** `dev` CI is Pest + Test Impact Analysis enforcing **100% line coverage** (`pest --coverage --min=100 --tia`) plus `composer ci:check:static` (eslint, prettier, tsc, pint, phpstan). Any new PHP in `app/` must be fully covered by a test or CI fails. To cover env-branch code in the eager head defaults, re-boot the provider under production in a test: `app()->detectEnvironment(fn () => 'production'); (new AppServiceProvider(app()))->boot();`.
