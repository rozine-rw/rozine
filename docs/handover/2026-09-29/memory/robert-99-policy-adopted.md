---
name: robert-99-policy-adopted
description: "Erastus adopted all of Robert's #99 answers (2026-09-25), including reversals of the engineering contract that still need Aminu's joint amendment"
metadata:
  node_type: memory
  type: project
  originSessionId: 94be1f2e-5e3d-4621-a5b3-6bff0afd9745
  modified: 2026-09-25T11:12:28.228Z
---

On 2026-09-25 Robert answered #99, and Erastus chose to adopt all of his answers as final. Erastus also chose the **detailed late-fee breakdown** for investors (C7).

**Reversals of the engineering contract (engineering-2026-09-23.4 §11 / BRS). Each needs a joint amendment with Aminu (Hussain):**
- **N6:** superseded by Robert's revision 5832645053. The rule is 24 h from the report landing in the Business app, with 1 signatory; see the 2026-09-26 update. It was already jointly agreed and merged in #126.
- **C6:** TIN everywhere, replacing the RDB company code. This reverses BRS AC-9.
- **N3:** NOT a reversal. Robert's revision 5832645053 keeps the MVP at RWF 0, with RWF 50,000 non-refundable at submission as the post-MVP production standard. CFG-01 stands for the MVP.
- **C3:** a 50% single-investor cap per raise.
- **C4:** the Plus tier is in the MVP (new scope).
- **N2:** 0.5% taker / 0.2% maker, replacing AM-11's 0.35% + 0.35%.
- **N5:** two signatories applies only to Business KYC bank-account onboarding, not to every company or to audit sign-off (5832645053).
- **C7:** penalties passed through to investors, replacing the platform-revenue ledger.
- **C5:** in-person CPA visits are core in the monthly audit copy. The exact text is still pending.

**Unchanged and confirmed:** N1, N4 (+5% at due, day 7, day 30), N7 (RWF 0 tolerance), N8, N9 (+10% net margin metric), N10, N11 (PSP pass-through), S1–S9.

**Still missing from Robert:** the approved T&C and Privacy Note (the release gate); the consequences of missing N6; the N3 timing and refund rule; the C4 thresholds; the C3 cap interplay; the exact C5 text.

**Why:** Robert owns product and legal and answered after a long silence. Erastus wanted the answers final so the build converges.

**Update 2026-09-26:** Aminu approved proceeding with N6. Robert's final version is: 24 hours from when the report lands in the Business app; 1 signatory; automatic publication on an undisputed timeout; the dispute freezes the timer and carries evidence (files and/or up to 1,000 characters of text); the assigned CPA handles it first, then staff; a fresh window after an amendment. Hussain owns the server and contract amendment, and we own the UI (the pending states in #115). This also fixes the current-rule bug where monthly reports are already overdue when sealed if dispatch falls after the 7th.

**Plus settled 2026-09-27 (#99 5852160501):** tier = currently active deployed capital (dynamic); fee rate locked at commitment and shown on the checkout ticket; "fee on earnings" (10/8/6.5/5.5/5.0/4.0% by band: <1M, 1–5M, 5–30M, 30–100M, 100–250M, 250M+) replaces CFG-04's 1% investor repayment fee and applies to the return only, never principal; Auto-Deploy post-MVP. Proposed `earnings_fee {tier, rate_bps, basis, policy_version}` wire delta on #96 (5852990721), pending Hussain.

**T&C/Privacy chase (2026-09-28):** issue #154 created and assigned to robtumaini (Erastus asked) — the release gate for staging/production; also linked from #99.

**Amendment PR (2026-09-28):** draft #180 (docs only, new contract §12 overlay, PA-01..PA-13) is awaiting joint sign-off by Erastus, Aminu/Hussain and Robert. Plus privileges (Account Lead, Priority Allocation) were never confirmed for the MVP. R1–R4, R6 and R9, plus the Plus capital basis, are unanswered, so late fees stay fail-closed.

**How to apply:**
- Hussain keeps these policy changes out of the current C2 delivery, except N6 pending Aminu.
- The other changes land as a contract amendment with C3/C4.
- Don't implement a reversal until the amendment is agreed on #96.

Related: [[checkpoint-2-exit-list]], [[stay-on-mvp-course]], [[gh-issues-comms]].

**Legal text posted 2026-09-28 (#154: 5876450158 investor-terms, 5876455204 business-terms, 5876459168 privacy-note; all `-2026-10-01`, signed by Robert).** It contradicts the adopted policy in several places. Our gap list is 5876485661.
- **Release blocker:** Privacy §4.1 says data is localised in Rwanda, but hosting is a Contabo VPS.
- **Conflicts:**
  - B-1: RWF 50,000 application fee.
  - B-2: the fee is deducted twice.
  - B-3: 2% deducted at disbursement, which reverses §11.4.
  - B-4: Default at day 45.
  - I-1: the Plus bands differ from 5852160501.
  - I-2: sandbox caps of 1M/2M/5M, against C3's 50% cap.
  - I-3: late fee on principal + return, capped at 15%.
- **New features the terms describe:** cart checkout, investor categories, withdrawal limits, auto-debit, CRB, first-loss reserve.
- **Plan:** hold the placeholder swap until the blocker and the conflicts are answered.
