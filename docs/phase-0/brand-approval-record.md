# Brand approval record

**Status:** `BRAND SYSTEM V1.0 APPROVED 2026-09-06 · MARK CONFIRMED AS THE STAR 2026-09-07 · PRIMARY LOCKUP NOT YET SELECTED`

**Approver:** Robert — Product, Design, Brand, Business, internal Legal (D-71)

**Semantic map:** [`brand-asset-manifest.json`](brand-asset-manifest.json) · **Identity hashes:**
[`brand-source-manifest.json`](brand-source-manifest.json) · **Open decisions:**
[`brand-decisions-for-approval.md`](brand-decisions-for-approval.md)

## What is approved

Robert has approved the brand system in [`../Rozine-Branding-Styles.md`](../Rozine-Branding-Styles.md)
(**V1.0, July 2026**) as the authoritative brand contract, and has approved the supplied logo package
as the direction for the mark.

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

The guide's values stand and no application token may be derived from a raster sample.

### The mark is the star — resolves half of D-51

Confirmed by inspecting all 31 renders. Every frame uses a five-pointed star with a swept lower-left
tail. The approved guide's section 07 calls the mark **the wing**, and the live runtime asset is still
`rozine-wing-white.png`; that language and that asset describe the previous mark, not this package.
The star supersedes the wing.

### The package is 8 artworks, not 31 competing files

Grouping by path geometry, ignoring colour, resolves the package completely. There is no duplicate
problem — the 31 files are 8 artworks in their colourways.

| Artwork | Frames | Notes |
|---|---|---|
| Rozine lockup, detached leading star | 8 | Full colourway set |
| Rozine lockup, star integrated at the `i` | 7 | Full colourway set, minus a clean blue-on-white |
| Star mark, standalone | 11 | Three clear-space crops: 57%, 75%, 82% of canvas |
| Investor / Business / Auditor role wordmarks | 1 each | Star integrated at the `i` of each word |
| App icon, gradient | 1 | Off-palette; see below |
| Rozine integrated-star lockup, defective export | 1 | See below |

## Three findings that need Robert before anything ships

### 1. `#BA0D3B` is an export artefact, not a brand colour

It was proposed for addition to the approved system on the grounds that it is used in the logos. It
is not. All 48 occurrences across 18 frames are:

```svg
<circle cx="1000.5" cy="1170.5" r="0.5" fill="#BA0D3B"/>
```

A half-unit radius on canvases 2796–7545 units wide — roughly one fifteen-thousandth of the width,
invisible at any rendered size. Frame 83 renders as solid blue despite containing three of them. It
is Figma residue: anchor or measurement dots left in the export.

**No amendment has been made.** Adding it would enshrine an export artefact as a brand colour and
propagate it into design tokens. If Robert wants a crimson in the system it should be chosen as a
colour on its own merits, not adopted from this.

The same export hygiene point applies to the package generally: a re-export should drop these
circles.

### 2. Two lockups compete for primary — the open half of D-51

Both exist as complete colourway sets, and only one can be the primary:

| Candidate | Frames | Treatment |
|---|---|---|
| **A — detached leading star** | 83, 84, 89, 92, 95, 96, 101, 102 | Star set apart, then `rozine` |
| **B — star integrated at the `i`** | 85, 90, 93, 97, 98, 103, 104 | Star replaces the dot of the `i` |

One argument for B, which Robert should weigh rather than inherit: the three role wordmarks
(`investor`, `business`, `auditor`) all integrate the star at the `i`, so B makes the core lockup and
the role lockups one system. A is then the horizontal/wide variant rather than a competitor.

Two consequences follow the choice. If B is primary, its only blue-on-white export is **Frame 86,
which is defective** — exported unflattened at 19 paths instead of 7, with a stray rectangle left on
the `e`. It must be re-exported before use. And the gradient app icon (Frame 107) uses `#8594FF` and
`#F5B686`, neither in the approved palette, while the guide prohibits effects on the mark; shipping it
needs an explicit exception.

### 3. The package colours are not the approved palette

| In package | Approved token | |
|---|---|---|
| `#0039FF` | `#0A5CFF` Rozine Blue | differs |
| `#1D9E75` | `#12A150` Business | differs |
| `#C2661F` | `#DD8A00` Auditor | differs |

Approving the package as the direction for the mark does not by itself amend the colour contract.
Either the masters are re-exported in approved colours, or a dated amendment moves the system to the
package's values. This has to be settled before a master is cut, because it changes the files.

## Still open

- **D-51** — primary lockup: candidate A or B.
- **D-52** — closed for the palette; reopens only if the package colours are adopted as an amendment.
- **D-56** — who owns the vector artwork, and the typeface behind the outlines, under what licence. No
  SVG contains a `<text>` element or `font-family`, so the shipped files carry no font dependency, but
  the provenance of the outlines is unrecorded.
- **D-57** — the background matrix follows from the lockup choice.
- **D-63** — are the role wordmarks complete identities, or must they pair with `Rozine`.

## Consequence for the runtime

No runtime asset changes yet. D-52 being closed means the *interface* can be tokenised against the
approved palette now; the logo surfaces wait on D-51, D-56, D-57 and D-63 and on rights-cleared
masters.
