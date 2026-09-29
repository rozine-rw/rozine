
## 12. Proposed amendment A-2026-09-28 (pending joint sign-off)

**Status:** PROPOSED_PENDING_JOINT_SIGN_OFF - NOT_APPROVED - NOT_IMPLEMENTED. **Nothing in this section is approved until every box in section 12.5 is ticked by its owner.** Until then, sections 1–11 remain the executable baseline, including every line quoted below as "current text".

**Convention:** the contract amends by overlay, not by silent edit (section 2), and the reviewed section hashes in section 9.1 must stay intact. So this amendment leaves sections 1–11 unchanged. Once signed, each item below supersedes only the lines it names. Sign-off then triggers a new contract version, proposed as engineering-2026-09-28.1, together with a separate follow-up for the companion fixture and its Pest guards. For example, `known_parameters.buyer_fee_bps` and `seller_fee_bps` are still asserted at 35. Line numbers refer to this file at base commit `f1428e12` and never move, because the body is untouched.

**Sources:**
- **Robert's #99 answers:** [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265), the revised answers in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053), [5832727753](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832727753), [5832878804](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832878804) and [5852160501](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852160501). Where they differ, a later answer supersedes an earlier one.
- **Erastus's adoption on 2026-09-25:** [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513) ("Your #99 answers are adopted as final"), plus the conflict list in [5831425160](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831425160) and the recorded readings in [5832668950](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832668950) and [5852989890](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852989890).
- **Hussain's conditions:** [5831399889](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5831399889) (keep the amendments out of current delivery) and [5849714844](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5849714844) (reconcile before live behaviour).
- **Hussain's accepted Plus shape and display rules:** [5853007475](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5853007475) and [5865775804](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5865775804).

**Open questions keep their #99 names:**
- R1–R9 are the nine checkpoint 4 questions in [5849676518](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5849676518). R5 (Plus) is answered; R1–R4 and R6–R9 are open.
- The Plus capital basis is asked in [5853012548](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5853012548) and is unanswered.
- The Terms & Conditions and Privacy Note in #154 remain the release gate.
- An open item stays provisional or fails closed. This section never supplies Robert's answer for him. Where it proposes an engineering mapping, it labels it as one.

**Slices:**
- **C3:** wallet, primary purchase, settlement and disbursement (S3-A to S3-D).
- **C4:** repayment, payout, propagation and the secondary-ready contracts (S4-A to S4-F).
- **C5:** the witnessed Alpha chain.

### 12.1 Amendment items

#### PA-01 - N2 secondary fee: taker 0.5%, maker 0.2%

- **Decision:** N2 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): "0.5% Taker fee / 0.2% Maker fee per completed secondary trade", which drops the blended 35 bps. **Adopted** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513), and named as a contract conflict in [5831425160](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831425160).
- **Changes:**
  - Section 1, line 28: "The fee is settled: 35 basis points each side, not 70% and not the old maker/taker rates".
  - Section 2, AM-11, line 46: "buyer 0.35% plus seller 0.35% per fill".
  - Section 4, line 106: "Fee rates are integer basis points: buyer 35, seller 35".
  - Section 6, line 156: "Each side pays `half_up(G * 35 / 10,000)`".
  - Section 6, A5-2, line 177: the bid reserve `ceil(p * 35 / 10,000)`.
  - Consequential lines elsewhere: [plan](../Rozine_Phased_Implementation_Plan.md) line 470 ("approved 0.35% buyer and 0.35% seller fee posting") and line 210 (C-28), and [BRS](../Rozine-BRS.md) BR-62.
- **Proposed text:**
  - "The secondary fee is **50 basis points for the taker and 20 basis points for the maker** on each completed fill. Each fee is `half_up(G * rate_bps / 10,000)` in whole RWF on settled gross, with no minimum fee."
  - "Buyer debit = G + buyer fee; seller credit = G - seller fee; platform fee = both fees."
  - Worked example for a fixed-ask checkout of G = RWF 10,000: buyer (taker) fee 50, seller (maker) fee 20, buyer debit **10,050** (was 10,035), seller credit **9,980** (was 9,965), platform **70**.
  - "The A5-2 bid reserve uses the taker rate, `ceil(p * 50 / 10,000)`, as its buffer. Per-fill rounding, no stacking and the other A5-2 release rules are unchanged."
- **Proposed engineering mapping, which needs confirmation because Robert's answer does not define it:**
  - In a fixed-ask checkout, the listing seller is the maker and the buyer is the taker.
  - In funded-bid matching, the order admitted earlier by server sequence is the maker and the incoming order is the taker.
- **Affected slices:** C4 (S4-F fee-policy snapshot and constants); C5 and Phase 3 secondary settlement.
- **Open dependency:**
  - The maker/taker role mapping above. This is a new question and is not among R1–R9.
  - Carrying per-fill half-up rounding forward from the PDF's Q10 is an engineering proposal.
  - **Fail-closed:** S4-F publishes the fee-policy snapshot fields without live fee constants, neither 35/35 nor 50/20, until this item is signed.

#### PA-02 - N3 first-raise fee: no MVP change; the production standard is recorded

- **Decision:** N3 as revised in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053):
  - MVP scope: "RWF 0 (Free listing for MVP)".
  - Production standard: "RWF 50,000 non-refundable application fee charged at submission (regardless of approval or raise outcome)".
  - **Adopted** and recorded as "RWF 0 in MVP" in [5832668950](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832668950).
  - **Correction to earlier notes:** Robert's first answer (RWF 50,000) is superseded, so N3 **does not reverse** CFG-01 for the MVP.
- **Changes:** section 11.1, line 509, "Primary listing fee: RWF 0, explicitly waived for the MVP" stays in force for the MVP. Only the following sentence is added.
- **Proposed added text:** "Post-MVP production standard, not active: a RWF 50,000 non-refundable first-raise application fee, charged at application submission whatever the approval or raise outcome. It activates only under a new policy version with its own disclosure, T&C text and tax/accounting evidence. It would move the charging point from the listing transition (AC-03, line 126) to submission."
- **Affected slices:** none in C3–C5.
- **Open dependency:**
  - The activation date and the tax/accounting treatment.
  - How and when the fee is charged, since the MVP has no payment rail for it.
  - Until then, MC-06 (line 455) keeps a missing value failing closed.

#### PA-03 - N4 late-fee ladder: +5% at due date, day 7 and day 30

- **Decision:** N4 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): +5% on the due date, +5% at day 7, and +5% at day 30, which "immediately trigger[s] legal recovery initiation". **Adopted** as part of all answers in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513). It was recorded as consistent with the build in [5831374508](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831374508), but it is **not** consistent with this contract's penalty design.
- **Changes:**
  - Section 8.3, line 248: "penalty-interest accrual" during days 8–21.
  - Section 8.5, lines 284–285: "2% per year (200 annual basis points) ... on overdue principal only", accrued "from its eighth overdue day".
  - MC-03, line 431: the intraday penalty candidate.
  - Section 11.4, line 541: the "closing-balance penalty".
- **Proposed text:**
  - "Late fees follow the N4 ladder: three steps of 5%, at the due date, day 7 and day 30. Day 30 also initiates legal recovery."
  - The base, stacking and fee treatment are **not specified** here, pending R1–R3.
  - The payment allocation order in line 288 (principal, then due return, then penalties) and the legal-review dependency in line 274 (BNR Regulation 55/2022, Article 61) are retained.
- **Affected slices:**
  - C4: S4-B servicing and S4-D DPD/arrears.
  - C5: witnessing.
- **Open dependency:**
  - **R1:** what each 5% is charged on.
  - **R2:** whether the ladder replaces section 8.5's 2%/yr or stacks on it.
  - **R3:** whether fees apply to late fees.
  - **New mapping questions for the same follow-up:**
    - Payment is due "through that local date" (line 541), so how can a charge apply "on the due date"?
    - "Day 30" initiates legal recovery, while MC-03 (line 429) declares Default at the start of DPD 31.
  - **Fail-closed:** no late-fee assessment of either kind (ladder or section 8.5 accrual) goes live until R1–R3 are answered. `LateFeePolicy` is unavailable and live `late_fees` is `null`, as in the C4 plan.

#### PA-04 - N5 two signatories, for KYC bank-account onboarding only

- **Decision:**
  - N5 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): "2 signatories mandatory for corporate account authorization".
  - Narrowed in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053): "applies exclusively to business KYC bank account onboarding".
  - **Adopted** in [5831425160](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831425160) and [5832668950](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832668950).
  - It is **not** a universal two-signatory rule for every company command.
- **Changes:**
  - Section 11.1, Mandates, line 515: "a company uses its verified entity-specific effective mandate ... do not hard-code two directors".
  - MC-06, line 453.
  - Consequential: plan line 216 (C-34) and D-64.
- **Proposed text:**
  - "A company's KYC/KYB bank-account onboarding requires the authorization of **at least two** distinct verified signatories."
  - "Every other Business command keeps the verified entity-specific mandate and one owner for a sole trader. This includes application submission and exposure reservation (AC-02) and the monthly audit review (PA-05)."
- **Affected slices:**
  - C3: the S3-D disbursement destination.
  - A C2 identity follow-up for the onboarding surface.
- **Open dependency:**
  - **Engineering reading, to be confirmed:** "two" is a minimum. An entity mandate that requires more still governs.
  - Whether already registered accounts need re-authorization is unanswered and is not assumed.

#### PA-05 - N6 monthly review: 24 hours, one signatory, automatic publication

- **Decision:**
  - N6 as revised in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053): 1 signatory; 24 hours from arrival in the Business app; automatic approval on an undisputed timeout.
  - Proof in [5832727753](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832727753): files and/or text of up to 1,000 characters.
  - Handling in [5832878804](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832878804): the CPA first, then staff; the timer freezes during a dispute; an amendment opens a fresh window.
  - The 2-hour answer is superseded.
  - **Adopted** in [5832898412](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832898412).
  - Already jointly agreed for implementation: Aminu authorized it on 2026-09-26 ([5845407013](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5845407013)), on the server contract in [5845469433](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5845469433) that Erastus confirmed in [5845495647](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5845495647).
  - Merged to `dev` in PR #126 (`7516e2fa`) and recorded in the [ADR](adr-0001-modular-monolith-boundaries.md) N6 section.
- **Changes:**
  - Section 11.5, Reporting/lateness, line 561: "both report and co-signature still due by day 7".
  - MC-06, line 453: "All required joint signatories".
  - AC-12, line 135.
  - Consequential: plan line 1570 and BRS FR-206 (the 1st–7th window).
- **Proposed text:**
  - "A monthly report sealed under `monthly-review-2026-09-26` is reviewed by **one** current verified Business signatory, within 24 hours of durable delivery to the Business app."
  - "An undisputed timeout publishes the report as `auto_approved`, without a fabricated signature."
  - "A dispute carries files and/or text of up to 1,000 characters, and freezes the timer. The assigned CPA handles it first and staff next. An amendment opens a fresh window."
  - "Flash reports and reports sealed earlier keep their original rules."
- **Affected slices:** C2 (delivered). There is no C3 or C4 dependency.
- **Open dependency:** none on N6 itself. This item transcribes an agreed and merged change into the contract. The seal deadline is covered by PA-07.

#### PA-06 - C3 single-investor cap of 50% per raise

- **Decision:**
  - C3 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): "A single investor may fund up to 50% of any business's target raise".
  - Clarified in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053): the cap "replaces and supersedes all former individual limits/caps", and the RWF 5,000 minimum unit "remains active and unchanged".
  - **Adopted** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513) and [5832668950](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832668950).
- **Changes:**
  - Section 11.1, Investment caps, line 512: "Gross transaction RWF 1M; per Note the lower of RWF 1M or 20% ...; per Business RWF 2M; aggregate RWF 5M".
  - Consequential: plan line 211 (C-29) and the conflict register's C-09.
- **Proposed text:**
  - "One investor Party may hold, per raise, confirmed commitments plus live reservations of at most 50% of that raise's target principal."
  - Proposed engineering mapping in whole units: `units * 5,000 <= floor(target * 50 / 100)`.
  - "No per-transaction, per-Note, per-Business or aggregate cap applies."
  - The RWF 5,000 unit and one-unit minimum (CFG-02) are unchanged.
  - Exposure measurement (line 513), the prohibition on self or connected Businesses, and fees requiring available cash are unchanged.
- **Affected slices:** C3 (S3-C capacity and reservation).
- **Open dependency:**
  - **Engineering reading, to be confirmed:** the unverified-KYC capacity of RWF 0 (line 511) is a verification gate, not an "individual limit", so it stays.
  - Whether secondary acquisitions count toward the 50% is not addressed ("per raise"). This must be settled before C5/Phase 3 secondary; it does not block C3.

#### PA-07 - C5 audit operating copy and cycle

- **Decision:**
  - C5 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): in-person CPA visits form "the primary core" of the monthly audit, and uploads are supplementary.
  - The cycle and the seven approved sentences in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053): a prep notice from the 20th to month-end; the CPA visit; the seal and send to the app, which opens the 24-hour window.
  - **Adopted** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513) and [5832668950](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832668950).
- **Changes:**
  - Section 11.5, procedure MVP-AUP-1, line 553.
  - Section 11.5, Reporting/lateness, line 561: "Business input target day 3".
- **Proposed text:**
  - "The monthly audit centres on the CPA's in-person visit, with digital uploads as supplementary preparation."
  - "The Business is notified to prepare records from the 20th to month-end. The sealed report opens the PA-05 window."
  - The surface copy is Robert's seven sentences, verbatim.
- **Affected slices:**
  - A C2 follow-up for notifications and copy.
  - C5 (the witnessed monthly audit).
- **Open dependency:**
  - Robert gave **no replacement deadline** for the CPA visit or seal. The current day-7 seal deadline is retained until he does. None is invented here.

#### PA-08 - C6 TIN as the Business identifier

- **Decision:**
  - C6 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265) and [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053): "Standardizing on TIN ... across all forms, surfaces, and legal documents. RDB code is dropped".
  - **Adopted** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513).
- **Changes:**
  - No line in this contract names the identifier. Section 1, line 26, reads the contract together with the BRS.
  - The conflicting lines are in the [BRS](../Rozine-BRS.md): DR-8 ("MUST NOT store any tax identification number") and AC-9 ("No surface ... contains a reference to ... TIN").
  - They are also in the [plan](../Rozine_Phased_Implementation_Plan.md): line 140 ("No TIN ... unless the governing BRS is formally amended"), C-01 at line 183, lines 1073 and 1778, and GV-029.
- **Proposed text:**
  - "The TIN is the single Business identifier on forms, surfaces, legal documents and system fields, and replaces the RDB company code."
  - "It is restricted data, never a public Resource field."
  - "The prohibition on RRA/EBM integration, tax sync and tax-compliance badges remains. Robert's answer covers only the identifier."
- **Affected slices:** a cross-cutting C2 identity follow-up. It does not block C3 or C4.
- **Open dependency:**
  - The formal BRS amendment of DR-8 and AC-9, owned by Robert.
  - The Privacy Note coverage in #154, which is the release gate.
  - Migrating existing RDB-code identity records, and the identifier for sole traders. Both are unaddressed and not assumed.

#### PA-09 - C7 late fees passed through to investors

- **Decision:**
  - C7: Erastus chose the **detailed breakdown** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513). Accrued late fees are shown line by line, "payable to the investor only on collection and not guaranteed by Rozine".
  - Robert framed it as "+5% ladder penalties passed to the investor upon collection" in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265).
  - **Adopted** and named as a contract conflict in [5831425160](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831425160).
- **Changes:** section 11.4, line 545: "Collected penalties have a separate platform-revenue ledger, not a promised investor return."
- **Proposed text:**
  - "Late fees actually collected pass through to the holders of the affected instalment component."
  - "The investor view shows accrued late fees line by line, as payable only on collection and not guaranteed by Rozine."
  - "The reserve payout (line 291) never covers late fees."
  - Proposed engineering mapping:
    - The recipient is the component's record-date owner.
    - Amounts are distributed with the largest-remainder rule in section 11.4.
    - The investor view shows collected late fees separately from principal and return ([5865775804](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5865775804)).
- **Affected slices:**
  - C4: S4-B, S4-C payout postings, S4-D, and the U4 bindings.
- **Open dependency:**
  - **R3:** whether the earnings fee or the 2% service fee applies to late fees.
  - **R4:** the approved investor sentence.
  - **R9:** note-level visibility versus the investor's own share.
  - R1 and R2 through PA-03.
  - **Fail-closed:**
    - The lines stay preview-only.
    - Only the holding's own share is shown.
    - No live amounts until PA-03 and R3/R4 are answered.

#### PA-10 - C4 Plus in the MVP scope

- **Decision:**
  - C4 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): Plus is "INCLUDED IN MVP" as a tier/fee ladder.
  - Auto-Deploy ships after the MVP, per [5852160501](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852160501), item 4.
  - **Adopted** in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513) and [5852989890](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852989890).
- **Changes:**
  - No line in this contract covers it.
  - The [plan](../Rozine_Phased_Implementation_Plan.md) does: D-61 at line 2119, lines 674 and 730–731 (Plus "feature-flagged off"), and line 211 (C-29).
  - So does the [deferred-scope register](deferred-scope-register.md), in E-01.
- **Proposed text:**
  - "The Plus fee-on-earnings tier ladder (PA-11) is MVP scope."
  - "Auto-Deploy, and any other automation or mandate, stays post-MVP. The gated explainer remains."
- **Affected slices:**
  - C3: the S3-C quote and commitment snapshot.
  - C4: payouts.
- **Open dependency:**
  - The other tier privileges listed in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053) ("Dedicated Account Lead", "Priority Note Allocation") were **not** confirmed for the MVP. They stay out of it.
  - D-61 still needs its signed disposition, under the plan.

#### PA-11 - Plus fee on earnings replaces the 1% investor fee

- **Decision:**
  - The tier table in [5832645053](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5832645053).
  - The rules in [5852160501](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852160501):
    - The tier follows "currently active capital deployed".
    - The rate is "locked at the moment of commitment" and shown on the checkout ticket.
    - The fee "replaces the standard 1% repayment fee and applies strictly to the return/interest portion ... never to returned principal".
  - This supersedes the earlier "lifetime" wording.
  - **Adopted** in [5852989890](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5852989890).
  - Hussain accepted the wire shape for UI preparation, not activation, in [5853007475](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5853007475).
- **Changes:**
  - Section 11.4, line 545: "Keep the 1% investor fee on actual contractual payout, half-up in whole RWF."
  - Consequential: plan lines 1562 and 1774, BRS BR-62, and plan line 207 (C-25).
- **Proposed text:**
  - "The investor fee is `rate_bps` × the return portion of each payout, never principal."
  - "The rate is 1000/800/650/550/500/400 bps for the Standard/Bronze/Silver/Gold/Platinum/Diamond tiers, at RWF <1M, 1M–5M, 5M–30M, 30M–100M, 100M–250M and 250M+ of active deployed capital."
  - "The rate is snapshotted at commitment as `EarningsFee {tier, rate_bps, basis: 'return_only', policy_version}` and kept for that note's life."
  - "A change before confirmation returns `DISCLOSURE_STALE` and requires reconfirmation."
  - "No 1% fee is stacked on it."
- **Engineering mappings, not Robert quotes:**
  - Lower-inclusive/upper-exclusive band endpoints ([5853007475](https://github.com/rozine-rw/rozine/issues/96#issuecomment-5853007475)).
  - Half-up whole-RWF rounding per payout, carried forward from line 545.
- **Affected slices:**
  - C3: the S3-C quote and snapshot.
  - C4: S4-C payout postings.
- **Open dependency:**
  - **The Plus capital basis** ([5853012548](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5853012548)). The provisional reading is unpaid principal in issued holdings, excluding wallet cash, reservations and unissued commitments.
  - Live S3-C activation waits for Robert's answer. Fixtures may use the provisional reading.
  - Whether the fee applies to late fees is R3 (PA-09).

#### PA-12 - N9 RWF 30M minimum annual revenue (found in review)

- **Decision:**
  - N9 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): "Minimum RWF 30M annual revenue", plus 36/12 months of statements and a 10% net margin as a profile metric.
  - **Adopted** with all answers in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513).
  - [5831374508](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831374508) recorded it as consistent with the build. The 36/12-month rule is consistent (section 8.2). The RWF 30M gate is not in this contract, and Pulse uses RWF 15M.
- **Changes:** add a row to the section 8.2 table (lines 227–234) and to AM-05 (line 40).
- **Proposed text:** "First-time and repeat applications require verified annual revenue of at least RWF 30,000,000. The 10% net margin is displayed on the credit profile. It is not an eligibility gate."
- **Affected slices:** an underwriting follow-up, plus the Pulse copy. There is no C3–C5 dependency.
- **Open dependency:**
  - The measurement basis. The proposal is verified TTM revenue (section 8.2), but it is not confirmed.
  - Until confirmed, the gate is recorded and not enforced.

#### PA-13 - N11 withdrawal fees passed through from the PSP (found in review)

- **Decision:**
  - N11 in [5831351265](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831351265): "actual network/PSP charges apply directly upon payout/withdrawal".
  - **Adopted** with all answers in [5831415513](https://github.com/rozine-rw/rozine/issues/99#issuecomment-5831415513).
- **Changes:**
  - Section 11.1, line 510: "no undisclosed surcharge".
  - The BRS BR-62 exclusive schedule ("No other fee MAY be charged").
- **Proposed text:** "An investor withdrawal bears the actual PSP charge, disclosed before confirmation. Rozine adds no withdrawal fee of its own."
- **Affected slices:** none in C4. Withdrawals are plan Phase 2.
- **Open dependency:** **R6** (limits, fee display, timing and MVP scope).

### 12.2 Answers that need no amendment

These are consistent with the contract as written:
- **C1:** no sell-back.
- **C2 and N1:** a 10–15% flat return, by rating and term.
- **N7:** RWF 0 tolerance (line 553).
- **N8:** 30 km and at most three active jobs (line 558).
- **N10:** a 1.50 target and a 1.25 cutoff (AM-04).
- **S1–S9:** approved as built.

### 12.3 Summary

| Item | Decision | Section changed | Affected slices | Open dependency |
|---|---|---|---|---|
| PA-01 | N2 taker 0.5% / maker 0.2% | 1 (l.28), 2 AM-11 (l.46), 4 (l.106), 6 (l.156, l.177) | C4 S4-F; C5/Phase 3 | Maker/taker mapping (new); constants fail-closed |
| PA-02 | N3 RWF 0 in MVP; RWF 50,000 post-MVP | 11.1 (l.509), added sentence only | None | Activation, tax, charging rail |
| PA-03 | N4 +5% ladder | 8.3 (l.248), 8.5 (l.284–285), MC-03 (l.431), 11.4 (l.541) | C4 S4-B/S4-D; C5 | R1, R2, R3; due-date and day-30 mapping; fail-closed |
| PA-04 | N5 two signatories, KYC bank onboarding only | 11.1 Mandates (l.515), MC-06 (l.453) | C3 S3-D; C2 follow-up | "At least two" reading; existing accounts |
| PA-05 | N6 24 h, one signatory, auto-publish | 11.5 (l.561), MC-06 (l.453), AC-12 (l.135) | C2 (merged, #126) | None; transcription only |
| PA-06 | C3 50% per raise replaces all caps | 11.1 Investment caps (l.512) | C3 S3-C | Secondary applicability; KYC gate reading |
| PA-07 | C5 in-person CPA core; 20th–month-end prep | 11.5 (l.553, l.561) | C2 follow-up; C5 | Seal deadline not replaced |
| PA-08 | C6 TIN replaces RDB code | None directly; BRS DR-8/AC-9; plan l.140, C-01 | C2 follow-up | BRS amendment; #154; migration |
| PA-09 | C7 late fees to investors | 11.4 (l.545) | C4 S4-B/S4-C/S4-D, U4 | R3, R4, R9 (+R1/R2); fail-closed |
| PA-10 | C4 Plus in MVP; Auto-Deploy later | None directly; plan D-61, l.674, l.730–731 | C3 S3-C; C4 | Other privileges not in MVP; D-61 |
| PA-11 | Plus fee on earnings replaces 1% | 11.4 (l.545) | C3 S3-C; C4 S4-C | Plus capital basis; R3 |
| PA-12 | N9 RWF 30M minimum revenue | 8.2 table (l.227–234), AM-05 (l.40) | Underwriting follow-up | Revenue basis |
| PA-13 | N11 PSP pass-through withdrawal fee | 11.1 (l.510); BRS BR-62 | Phase 2 | R6 |

### 12.4 What this amendment does not do

- It changes no code, fixture, test, plan or BRS text. Every consequential edit follows sign-off, in its own change.
- It records nothing as approved by Aminu except the N6 authorization already in the ADR.
- Its only answer to R1–R4, R6–R9 or the Plus capital basis is to keep them open. Every "engineering mapping" above still needs joint confirmation.
- It does not lift the release gate (#154), the section 8.5 operational-evidence gaps, or the Phase 0 exit conditions.

### 12.5 Sign-off

Each owner ticks their own box and links the evidence. An agent must not tick a box. The plan's conflict table assigns some fee items (C-25, C-29) to Kimani as Finance/Risk owner. Robert should confirm whether a separate Finance/Risk sign-off is also required.

| Approver | Role | Scope | Approved | Date | Evidence |
|---|---|---|---|---|---|
| Erastus (Engineersticity) | Product and engineering lead | All items | [ ] | | |
| Aminu / Hussain (hussain4real) | Engineering, joint amendment owner | All items | [ ] | | |
| Robert (robtumaini) | Product and internal Legal | All items, especially PA-02, PA-08, PA-09 wording and PA-12/PA-13 | [ ] | | |
