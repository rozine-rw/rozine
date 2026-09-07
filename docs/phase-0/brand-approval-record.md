# Brand approval record

**Status:** `LOCKUP, BACKGROUNDS AND NAMING CLOSED 2026-09-07 · CORE BLUE AND WORDMARK TYPEFACE BLOCKED`

**Approver:** Robert — Product, Design, Brand, Business, internal Legal (D-71)

**Semantic map:** [`brand-asset-manifest.json`](brand-asset-manifest.json) · **Identity hashes:**
[`brand-source-manifest.json`](brand-source-manifest.json) · **Open decisions:**
[`brand-decisions-for-approval.md`](brand-decisions-for-approval.md)

## What is approved

Robert has approved the brand system in [`../Rozine-Branding-Styles.md`](../Rozine-Branding-Styles.md)
(**V1.0, July 2026**) as the authoritative brand contract, and has approved the supplied logo package
as the direction for the mark.

### Colours — D-52 blocked 2026-09-07

**Not recorded.** Three answers name two different core blues, and the third names a value that
cannot occupy the slot it was given: Deep Blue is the darker shade for gradients and pressed states,
and `#0A5CFF` is lighter than `#0039FF`. Two complete palettes were put back for a single choice in
[`brand-decisions-for-approval.md`](brand-decisions-for-approval.md); no value is authoritative until
one is picked, and no mixture of the two is valid.

**Settled either way:** the Strong/Stable/Weak rating bands move with the role colours rather than
staying fixed, so one colour carries one meaning across the product.

`#BA0D3B` is **not** in the system. It was proposed on the grounds that it appears in the logos; it
does not — every occurrence is a half-pixel export artefact in no logo shape. Confirmed dropped.

No application token may be derived from a raster sample.

### Lockup — D-51 decided 2026-09-07

**Primary is the detached leading star** (lockup A): the star set apart, then `rozine`. It is used in
formal contexts — the platform, documents, reports — and is the mark for every application surface.
**The star integrated at the `i`** (lockup B) is secondary, for branding, merchandise and campaigns,
and does not appear in product UI.

The gradient app icon (Frame 107) is dropped; app icons use the flat and monochrome treatments.

### The mark is the star

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

### Backgrounds — D-57 decided 2026-09-07

Primary is the blue logo on white; secondary is the white logo on blue. Remaining treatments follow
the audience and the language of the campaign. Product chrome uses the primary treatment.

### Role naming — D-63 decided 2026-09-07

Paired by default: `Rozine investor`, `Rozine business`, `Rozine auditor`. The standalone title is
permitted inside a signed-in application, where context already establishes Rozine. The **accessible
name is always the paired form**, because a screen reader announces one string every time and it is
the only identity a non-sighted user receives. Everything public-facing stays paired in both.

### Wordmark typeface — D-56 defect confirmed 2026-09-07

The wordmark is set in **San Francisco Pro**. Apple licenses that family for user-interface use on
Apple platforms and for UI mock-ups, prohibits modification, and does not grant trademark rights;
outlining the letters carries the restriction rather than curing it.

An override was given — keep SF Pro, because the apps will eventually ship through the App Store.
**Not recorded as a resolution:** the mark is already live on the open web, the MVP is web and PWA
under D-04, the licence covers interface use rather than logo creation, and a Rwandan trademark
filing has no relationship to App Store distribution. If the risk is accepted it will be recorded as
`RISK ACCEPTED` with the owner and the accurate reason. D-39 external counsel remains a separate gate
that D-71 forbids the internal pool from self-approving.

**19 of the 31 files carry letterforms** and are affected. **The star mark is clean** — 12 files,
including every crop and colourway of the standalone star — so favicon, app icon and avatar work is
not blocked. Remedies and scope are in
[`brand-decisions-for-approval.md`](brand-decisions-for-approval.md).

## Findings behind those decisions

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
