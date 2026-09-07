# Brand decisions — D-51, D-52, D-56, D-57, D-63

**Status:** `TWO ITEMS BLOCKED ON A CONTRADICTION 2026-09-07 · LOCKUP, BACKGROUNDS AND NAMING CLOSED`

**Owner:** Robert — Product, Design, Brand, internal Legal (D-71) ·
**Answered:** pull request #73, 2026-09-07

Lockup, backgrounds and naming are closed. The rating bands are closed: they move with the role
colours.

**Two items are deliberately not recorded**, because recording them would mean recording a
contradiction:

- **The core blue.** Three separate answers name two different values, and the third names a colour
  that cannot occupy the slot it was given. Neither reading can be written down until one is chosen.
- **The wordmark typeface.** The override was given a reason that does not apply. It may still be a
  decision the brand owner is entitled to make, but it would have to be recorded as an accepted risk
  with an accurate reason, not as a resolution.

Both were put back to the brand owner on 2026-09-07. Nothing derived from either may be produced
until they settle — which is why they are blocking rather than pending.

---

## 1 — Primary Rozine lockup (D-51) · `DECIDED`

**Primary is lockup A — the star set apart, then `rozine`.** Frames 83, 84, 89, 92, 95, 96, 101, 102.
It is used in formal contexts: the platform itself, documents, reports.

**Lockup B — the star on the `i`** (Frames 85, 90, 93, 97, 98, 103, 104) is **secondary**, for
branding, merchandise and campaign work.

This makes A the mark for every application surface. B does not appear in product UI.

---

## 2 — Authoritative colours (D-52) · `DECIDED — AMENDS THE BRAND SYSTEM`

**The logo colours win.** `#0039FF`, `#1D9E75`, `#C2661F` replace the V1.0 guide's `#0A5CFF`,
`#12A150`, `#DD8A00`.

This is an amendment to the approved brand system, not a clarification of it, so it needs a dated
revision — V1.1 — and it changes the interface, not only the logo files: core blue is the colour of
every primary button, link, focus ring and active nav state in the product.

**BLOCKED — the answers contradict each other.** Three statements were given:

1. "Go with the logo colors `#0039FF`, `#1D9E75`, `#C2661F`"
2. "Move them [the bands] to `#1D9E75` / `#0039FF` / `#C2661F` to be consistent with the rest of the
   app's color palette"
3. "For the blue color problem, go with `#0A5CFF`"

(1) and (2) make core blue `#0039FF`. (3) makes it `#0A5CFF`. Both cannot hold.

`#0A5CFF` also cannot occupy the slot it was offered for. **Deep Blue is the darker shade** — gradient
base and pressed states — and `#0A5CFF` is *lighter* than `#0039FF` (lightness 52% against 50%). A
button using it would brighten when pressed, which reads as a fault rather than as a press.

Two coherent palettes were put back for a single choice. Every value inside an option moves together;
no mixture of the two is valid.

| | Option 1 — logo files are right | Option 2 — guide is right |
|---|---|---|
| Core / Investor blue | `#0039FF` | `#0A5CFF` |
| Deep Blue | `#0027B0` | `#0A3FB0` |
| Business | `#1D9E75` | `#12A150` |
| Auditor | `#C2661F` | `#DD8A00` |
| Rating bands | `#1D9E75` / `#0039FF` / `#C2661F` | `#12A150` / `#0A5CFF` / `#DD8A00` |
| Consequence | Guide reissued as V1.1; logo files used as delivered | Guide stays V1.0; all 31 logo files re-exported |

**The rating bands are settled either way:** they move with the role colours rather than staying
fixed, so the same colour means one thing across the product.

---

## 3 — The dark red `#BA0D3B` (D-52) · `DECIDED`

**Dropped.** It was never a design colour — every occurrence was a half-pixel dot left by the Figma
export, present in no logo shape. It is not added to the palette, and a re-export should remove the
artefact circles.

---

## 4 — Role wordmarks (D-63) · `DECIDED, ONE CLARIFICATION OPEN`

**Pair with Rozine by default:** `Rozine investor`, `Rozine business`, `Rozine auditor`. The
standalone title is permitted in generic contexts where Rozine is already established.

**The boundary is confirmed** — the proposed rule was accepted as written:

- **Inside a signed-in application** the visible mark may drop `Rozine` and read just `investor`; the
  surrounding context already establishes it.
- **The accessible name is always the paired form**, `Rozine Investor`, because a screen reader
  announces one string every time and it is the only identity a non-sighted user receives.
- **Everything public-facing** — marketing, the launcher, shared passes, exported documents, social
  cards — stays paired in both the visible mark and the accessible name.

---

## 5 — Background matrix (D-57) · `DECIDED`

| Rank | Treatment |
|---|---|
| Primary | Blue logo on white |
| Secondary | White logo on blue |
| Remaining | Follow the audience and the language of the campaign |

Product chrome uses the primary treatment. The role-coloured and monochrome variants stay available
for campaign work under the guide's existing rule that the accent identifies a surface and never
takes over the interface.

---

## 6 — Ownership, rights and typeface (D-56) · `DEFECT CONFIRMED`

**The wordmark is set in San Francisco Pro.** Confirmed by the brand owner, 2026-09-07.

An override was then given — keep SF Pro, on the grounds that the apps will eventually ship through
the App Store. **That has not been recorded as a resolution**, because the reason does not apply:

- The mark is already live on the open web at rozine.rw and staging.rozine.rw. A website is not an
  Apple platform.
- The MVP is web and PWA. Under D-04 only a thin native capture companion was accepted; every other
  screen is web, on Android and desktop as much as iOS.
- Apple's licence covers using the font *in* an interface, not building a logo from it, and it
  prohibits modification. Outlining letterforms is a modification and the outlines carry the
  restriction.
- Trademark registration would be filed in Rwanda and has no relationship to App Store distribution.

Accepting the risk remains the brand and internal Legal owner's call. If it is accepted it will be
recorded as **`RISK ACCEPTED`** naming the owner and the accurate reason, never as resolved — a record
that overstates its own certainty is worth less than no record. Under D-71 the internal pool cannot
self-approve external clearance, so D-39 counsel sees this either way; the override changes only
whether money has already been spent on the mark by the time they do.

### Scope — 19 of 31 files

| | Files | Affected |
|---|---|---|
| Wordmark-bearing | `rozine` lockups A and B, and the three role wordmarks | **19** — Frames 83, 84, 85, 86, 89, 90, 92, 93, 95, 96, 97, 98, 101, 102, 103, 104, 108, 109, 110 |
| Star only | Standalone star mark in every crop and colourway, plus the dropped gradient icon | **12** — Frames 87, 88, 91, 94, 99, 100, 105, 106, 107, 111, 112, 113 |

**The star mark is clean.** It is not letterforms, so it carries no font licence and can proceed to
production masters immediately. The favicon, app icon and avatar assets all derive from it, so that
work is not blocked.

### Remedies

1. **Reset the wordmark in Inter.** Inter is SIL Open Font License, which expressly permits logos,
   trademarks and derivative letterforms. Cheapest path; the result will differ visibly from SF Pro
   but is a close relative of the same genre.
2. **Commission custom letterforms.** What most wordmarks do anyway — a designer draws the six
   letters once, and the licence question disappears permanently.
3. **License a face that permits trademark use.** Some foundries sell an explicit logo licence.

Option 2 is the usual answer for a mark intended to last, and `rozine` is six lowercase letters.

### Two related points, still unrecorded

- **Unsplash** permits broad commercial use but excludes using photos to create a trademark or logo.
  Only a problem if any Unsplash material is inside the mark itself; marketing imagery is unaffected.
- **Generative-tool artwork** has unsettled authorship in several jurisdictions. It does not block
  use, but it bears on registrability, so the record should state which elements were generated and
  which were drawn.

---

## 7 — Defects (D-51) · `DECIDED`

**The gradient app icon is dropped.** Frame 107 is not used. App icons use the standard flat and
monochrome treatments, which keeps the mark inside the approved palette and inside the guide's rule
against effects on the mark.

**Frame 86** is a broken export of lockup B — unflattened, 19 shapes instead of 7, with a stray
rectangle on the `e`. Since B is secondary and does not appear in product UI, this is no longer
blocking; it should still be re-exported before any merchandise or campaign use.

---

## What closes when the follow-ups do

D-51, D-57 and D-63 are closed. D-52 is decided and produces a dated V1.1 amendment once the rating
band question is answered. D-56 is not a decision any more — it is a defect with three named remedies,
and it blocks the wordmark but not the star.

External trademark registration and any legal review beyond this internal record remain separate
gates.
