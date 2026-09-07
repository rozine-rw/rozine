# Brand decisions — D-51, D-52, D-56, D-57, D-63

**Status:** `DESIGN DECISIONS CLOSED 2026-09-07 · WORDMARK FONT LICENCE IS A DEFECT · ONE COLOUR ITEM AMBIGUOUS`

**Owner:** Robert — Product, Design, Brand, internal Legal (D-71) ·
**Answered:** pull request #73, 2026-09-07

Every design decision is closed. One answer produced a defect rather than a resolution: the wordmark
is set in **San Francisco Pro**, whose licence does not cover trademark use, so 19 of the 31 files
need their letterforms replaced before the mark can ship or be registered. The star mark is
unaffected.

One colour item — whether the rating bands move with the role colours — came back ambiguous and is
the only outstanding question.

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

**Deep Blue — decided.** A new deep blue is to be derived from `#0039FF`, replacing `#0A3FB0`.

Reproducing the exact relationship the guide used between core and deep blue — the old pair scaled
green by 0.685 and blue by 0.690, holding red — gives **`#0027B0`**. Proposed for confirmation rather
than adopted, since it is a brand colour.

**STILL OPEN 2a — do the rating bands move?** Strong / Stable / Weak are literally the three old role
colours, `#12A150` / `#0A5CFF` / `#DD8A00`. The answer restated that fact without saying whether they
follow the amendment, so it has not been recorded either way.

- If they **move** to `#1D9E75` / `#0039FF` / `#C2661F`, the rating scale is restated in the new
  values and one visual language means one thing everywhere.
- If they **stay**, the bands keep a stable meaning independent of brand changes — defensible for a
  data scale — but the same green appears in two slightly different shades meaning two different
  things.

Either is workable. It changes the interface palette, so it has to be chosen.

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

Apple licenses the SF family for user-interface use in software on Apple platforms and for UI
mock-ups, prohibits modification of the fonts, and does not grant rights to use them in a logo or
trademark. Converting letterforms to outlines changes the file format, not the licence, so the
supplied files carry the restriction with them.

This is an engineering reading of a licence, not legal advice. It goes to the internal Legal owner
and, under D-39, to external counsel. But it should be treated as a real defect until counsel says
otherwise, because the cost of finding out after registration is far higher than the cost of
resetting the letterforms now.

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
