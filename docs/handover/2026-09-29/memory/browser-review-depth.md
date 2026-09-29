---
name: browser-review-depth
description: "Erastus caught deck layout defects I missed in a browser check (2026-09-28, #160) — a screenshot glance is not a browser review"
metadata:
  type: feedback
---

A browser check means interacting and reading every figure, not a scaled screenshot glance. On 2026-09-28 Erastus sent screenshots of the #160 all-closed deck ("Ensure this UI is fixed. Have you noticed it??"). I had checked it at 0.5 scale and missed four defects: a clipped card header, a photo arrow over the rating pill, a wide detail panel showing another deal's figures, and a live countdown on a funded raise.

**Why:** Erastus reviews the UI himself, and defects that reach him cost trust and time. Fixture previews hide cross-component mismatches (a static `focus` against a moved deck).

**How to apply:**
- Take full-scale screenshots at 1113 px and 375 px, and read the header, badges and every number.
- Click the deck arrows or tabs, and confirm that every panel matches the item in front.
- Ask whether each state-dependent element (clock, pulse, "left", controls) is true for that lifecycle.
- When a new element takes vertical space inside the fixed 720 px desk canvas, measure the siblings it shrinks.

Related: [[phase-1b-ui-decisions]], [[rozine-frontend]].
