# Brand decisions — D-51, D-52, D-56, D-57, D-63

**Status:** `ANSWERED BY THE BRAND OWNER 2026-09-07 · THREE FOLLOW-UPS OPEN`

**Owner:** Robert — Product, Design, Brand, internal Legal (D-71) ·
**Answered:** pull request #73, 2026-09-07

Six of seven items are settled and recorded below. Item 6 (rights and typeface) is **not** settled:
the answer given appears to describe the product's UI font and its marketing imagery rather than the
logo artwork, and one part of it is a licensing risk that has to be resolved before a trademark is
cut. Two smaller clarifications are also open, marked `FOLLOW-UP`.

Nothing on the live site changes until the follow-ups close.

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

**FOLLOW-UP 2a:** the guide uses the same three values in two other places. Does the amendment reach
them?

| Guide token | V1.0 value | Changes to? |
|---|---|---|
| Deep Blue — gradient base, pressed states | `#0A3FB0` | derived from the old blue; needs a new value if core blue moves |
| Rating bands — Strong / Stable / Weak | `#12A150` `#0A5CFF` `#DD8A00` | these *are* the three role colours |

If the bands move with the roles, the rating scale is restated in the new values. If they do not, the
same visual language means two different things in two places. Either is workable; it has to be
chosen.

---

## 3 — The dark red `#BA0D3B` (D-52) · `DECIDED`

**Dropped.** It was never a design colour — every occurrence was a half-pixel dot left by the Figma
export, present in no logo shape. It is not added to the palette, and a re-export should remove the
artefact circles.

---

## 4 — Role wordmarks (D-63) · `DECIDED, ONE CLARIFICATION OPEN`

**Pair with Rozine by default:** `Rozine investor`, `Rozine business`, `Rozine auditor`. The
standalone title is permitted in generic contexts where Rozine is already established.

**FOLLOW-UP 4a:** "generic contexts" needs a concrete boundary, because an accessible name cannot be
conditional — a screen reader announces one string, every time, and it is the only identity a
non-sighted user gets.

Proposed rule, for confirmation: **the visible mark may drop `Rozine` inside a signed-in
application, where the surrounding context already establishes it; the accessible name is always the
paired form.** Anything public-facing — marketing, the launcher, shared passes, exported documents,
social cards — stays paired in both.

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

## 6 — Ownership, rights and typeface (D-56) · `NOT SETTLED`

The answer given was: imagery sourced partly through Claude Design and partly from Unsplash, which
permits commercial use; the font is San Francisco with Inter as a fallback, freely available.

That reads as an answer about **the product's UI font and its marketing imagery**. The question is
narrower and has different consequences: who owns *the logo vector artwork*, and which typeface the
*wordmark letterforms* were drawn from. Three things need resolving before a trademark is cut.

**6a — San Francisco cannot be used for a wordmark.** Apple licenses SF for user-interface use in
apps on Apple platforms and for UI mock-ups. The licence does not permit using it to create a logo or
trademark, and does not permit redistribution or embedding outside those terms. If the `rozine`
letterforms were set in SF and converted to outlines, the wordmark carries a licensing defect that
converting to outlines does not cure — outlining changes the file format, not the licence.

Inter is a different matter: it is SIL Open Font License, which explicitly permits logos and
derivative letterforms. If the wordmark was set in Inter, this concern falls away.

**What is needed:** confirmation of which typeface the wordmark letterforms actually came from. If SF,
the wordmark should be redrawn or reset in a face whose licence permits trademark use.

**6b — Unsplash images cannot be used in a logo.** The Unsplash licence permits broad commercial use
but specifically excludes using photos to create a trademark or logo. This is only a problem if any
Unsplash material is in the mark itself; for marketing imagery it is fine.

**6c — AI-generated artwork and trademark ownership.** Where artwork was produced by prompting a
generative tool, authorship and therefore registrable ownership is unsettled in several
jurisdictions. This does not block use, but it bears on whether the mark can be *registered*, so the
record should state which elements were generated and which were drawn.

None of this is an objection to the design. It is that a trademark's rights record has to survive
scrutiny later, and the current answer would not.

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

D-51, D-57 and D-63 are decided. D-52 is decided and produces a dated V1.1 amendment once 2a is
answered. D-56 stays open on 6a–6c.

External trademark registration and any legal review beyond this internal record remain separate
gates.
