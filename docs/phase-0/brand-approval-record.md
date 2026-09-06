# Brand approval record

**Status:** `BRAND SYSTEM V1.0 APPROVED 2026-09-06 · CANONICAL MASTERS NOT YET SELECTED`

**Approver:** Robert — Product, Design, Brand, Business, internal Legal (D-71)

**Recorded:** 2026-09-06, on the attestation of the project owner

## What this record approves

Robert has approved the brand system in [`../Rozine-Branding-Styles.md`](../Rozine-Branding-Styles.md)
(**V1.0, July 2026**) as the authoritative brand contract. That closes the colour question and the
usage rules.

### Authoritative colours — closes D-52

| Token | Value | Use |
|---|---|---|
| Rozine Blue | `#0A5CFF` | Primary. Investor app. All product chrome. |
| Deep Blue | `#0A3FB0` | Gradient base, pressed states. |
| Ink | `#0C1830` | Headings and key figures. |
| Business | `#12A150` | Business app accent. |
| Auditor | `#DD8A00` | Auditor app accent. |
| Pulse | `#08090D` | Pulse surface. |
| Slate / Body / Muted / Hairline / Surface | `#3A465E` `#69748A` `#8894A8` `#E7ECF4` `#F8FAFD` | Neutrals. |
| Rating bands | `#12A150` `#0A5CFF` `#DD8A00` `#E5484D` | Strong / Stable / Weak / Distressed. |

The guide's values stand. **The colours embedded in the supplied SVG package do not amend them**, and
no application token may be derived from a raster sample. This matches the plan's standing
instruction to retain the guide values until a dated brand amendment exists.

### Approved rules — informs D-57

Blue leads; other brand colours identify a surface or a state and never take over the interface. One
accent per audience, appearing on the splash, the logo lockup and the primary action — nowhere else.
The mark is never stretched, recoloured outside the palette, or given effects. Gradient backgrounds
behind content and decorative colour without meaning are prohibited.

## What this record does not approve

Approving the brand system does not select a canonical master, because the supplied package and the
approved guide do not currently agree. Four things remain open, and each is evidenced below rather
than asserted.

### 1. The package's colours are not the approved palette — D-52 boundary

Every fill in the 31 SVGs was extracted and compared against the guide:

| Colour in package | Occurrences | Nearest approved token | Status |
|---|---|---|---|
| `#BA0D3B` | 48 | none | **Not in the approved system at all** |
| `#0039FF` | 31 | Rozine Blue `#0A5CFF` | Differs |
| `#1D9E75` | 28 | Business `#12A150` | Differs |
| `#C2661F` | 27 | Auditor `#DD8A00` | Differs |
| `#8594FF` | 1 | none | Not in the approved system |

`#BA0D3B` is the most frequent fill in the package and appears in 18 of the 31 frames, including the
widest lockup (`Frame 83`, which pairs it with `#0039FF`). It is not the Distressed band's `#E5484D`.
Because the guide is now authoritative, either the masters must be re-exported in approved colours or
a dated brand amendment must add these values. No production asset may be derived until that is
settled.

### 2. No frame has been named canonical — D-51 open

All 31 files are non-semantic Figma exports (`Frame 83`–`Frame 113`). Nothing maps a decision to a
file, so "the primary lockup" currently names no artefact. D-51 also asks whether the star is
integrated at the `i` or leads detached, and the approved guide describes the mark as **the wing** —
matching the current runtime asset `rozine-wing-white.png` — while the supplied package is described
in the plan as star-based. That is a substantive conflict, not a naming one, and Robert has to
resolve it.

Sizes present: `2796×2796` squares (icon/mark treatments) and wider lockups up to `7545×2796`.

### 3. Role naming — D-63 open

Whether the standalone `investor`, `business` and `auditor` wordmarks are complete visible identities
or must always pair with `Rozine` (for example `Rozine Investor`) is not answered by the guide, and it
determines every header and accessible name.

### 4. Rights and provenance — D-56 open

One point in the package's favour: **no SVG contains a `<text>` element or a `font-family`
reference** — every glyph is an outlined path, so the shipped files carry no font dependency.
Provenance of the typeface used to produce those outlines, and the licence permitting its use in a
trademark, are still unrecorded, as is the ownership of the vector artwork itself.

## Consequence for the runtime

No runtime asset changes. The application shell, Pulse pass/card export, Pulse wordmark, and
browser/PWA identity continue to use their current legacy references until D-51, D-56, D-57 and D-63
are closed and rights-cleared masters exist. D-52 is closed, so the *interface* may be tokenised
against the approved palette above without waiting for the logo decisions.

## What Robert needs to decide

1. Which frame is the canonical primary lockup, and is the mark the wing or the star?
2. Do the package colours become an amendment to the brand system, or are the masters re-exported in
   the approved palette? What is `#BA0D3B` for?
3. Are the role wordmarks complete identities, or must they pair with `Rozine`?
4. Who owns the vector artwork and the typeface behind the outlines, under what licence?

Answering 1–3 lets the semantic asset manifest be written and the derived monochrome, dark,
favicon/PWA, Apple-touch and responsive-header outputs be defined.
