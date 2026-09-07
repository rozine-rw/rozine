# Brand decisions awaiting approval — D-51, D-52, D-56, D-57, D-63

**Status:** `AWAITING BRAND OWNER` · **Owner:** Robert — Product, Design, Brand, internal Legal (D-71)

**How to answer:** leave a review comment on each `**Decision:**` line below. Anything unclear, ask
in the same place. When every line has an answer this file is updated with them, you approve the
result, and it becomes the governing record.

Nothing on the live site changes until these are answered. Once they are, we can generate the
favicon, app icon, dark-mode and header assets and start the switchover.

## What we already established

The 31 supplied files are **8 designs in their colourways**, not 31 competing options — there is no
duplicate problem to sort out. Full map in
[`brand-asset-manifest.json`](brand-asset-manifest.json).

The mark is **the star**. The brand guide's section 07 still describes *the wing*, and the live site
still serves `rozine-wing-white.png`. Both describe the previous mark.

---

## 1 — Which is the primary Rozine lockup? (D-51)

**A — the star sits apart, then `rozine`** · Frames 83, 84, 89, 92, 95, 96, 101, 102

![Lockup A](../New%20Logo/Frame%2083.png)

**B — the star replaces the dot of the `i`** · Frames 85, 90, 93, 97, 98, 103, 104

![Lockup B](../New%20Logo/Frame%2085.png)

Both exist as complete colourway sets. Only one can be primary; the other becomes a secondary
variant rather than a competitor.

*Engineering's read, not a decision:* the three role wordmarks all put the star on the `i` —

![Investor wordmark](../New%20Logo/Frame%20108.png)

— so B would make the core lockup and the three role lockups one system, with A as the wide
horizontal variant. If you pick B, note item 7 below: its only blue-on-white export is broken.

**Decision:** _awaiting Robert — A or B_

---

## 2 — Which blues, greens and oranges are authoritative? (D-52)

| | Logo files use | Approved brand guide says |
|---|---|---|
| Core / Investor blue | `#0039FF` | `#0A5CFF` |
| Business green | `#1D9E75` | `#12A150` |
| Auditor orange | `#C2661F` | `#DD8A00` |

Close, but not the same. Either the logo files are re-exported in the guide's colours, or the guide
is amended to the logo colours. This has to settle **before masters are cut, because it changes the
files** — and it also decides what colour every button, link and header in the apps is.

**Decision:** _awaiting Robert — re-export logos to guide colours, or amend the guide to the logo
colours_

---

## 3 — The dark red `#BA0D3B` (D-52)

It appears in 18 of the 31 files, which is why it was raised as a brand colour. It is not one: every
occurrence is a half-pixel dot Figma left behind, invisible at any size, and it is in no logo shape
anywhere in the package. Lockup A above renders as solid blue while containing three of them.

We have **not** added it to the palette. If you want a red in the system it should be chosen
deliberately rather than inherited from an export artefact.

**Decision:** _awaiting Robert — confirm dropping it, or specify a red to add on its own merits_

---

## 4 — Do the role wordmarks stand alone? (D-63)

Is `investor` on its own a complete visible identity, or must it always read `Rozine Investor`?

This decides every application header and what a screen reader announces to someone who cannot see
the mark, so it is an accessibility decision as much as a brand one.

**Decision:** _awaiting Robert — standalone, or always paired with `Rozine`_

---

## 5 — Approved background matrix (D-57)

Which combinations are approved, and are any prohibited?

- Coloured mark on white
- White mark on the role colour
- Black mark on white
- White mark on black
- Dark-mode surface treatment

The guide already says the accent appears on the splash, the logo lockup and the primary action and
nowhere else, and that the mark is never recoloured outside the palette. This is confirming how that
applies per surface.

**Decision:** _awaiting Robert_

---

## 6 — Ownership and licence of the artwork (D-56)

Nothing depends on a font file at runtime — every letter is converted to outlines, so the shipped
files carry no font dependency. What is unrecorded is who owns the vector artwork, which typeface it
was drawn from, and the licence permitting that typeface's use in a trademark.

**Decision:** _awaiting Robert — owner, typeface, and licence_

---

## 7 — Two defects to confirm handling

**Frame 86 is broken.** It is lockup B exported unflattened — 19 shapes instead of 7, with a stray
rectangle left on the `e`. It is also the *only* blue-on-white export of lockup B, so if B wins item
1, this needs re-exporting before anything ships.

**The gradient app icon** (Frame 107) uses `#8594FF` and `#F5B686`, neither in the approved palette,
and the guide prohibits effects on the mark. It can ship as an approved exception — it just has to be
recorded as one.

![App icon](../New%20Logo/Frame%20107.png)

**Decision:** _awaiting Robert — confirm the Frame 86 re-export, and approve or reject the gradient
icon as an exception_

---

## What happens after

Items 1–6 close D-51, D-52, D-57 and D-63, and record D-56. We then define the canonical monochrome,
dark, favicon, PWA, Apple-touch and responsive-header outputs from the chosen masters, and the
runtime switchover from the old wing can begin.

Legally required external clearance — trademark registration and any rights confirmation beyond the
internal record — remains a separate gate and is not covered by this approval.
