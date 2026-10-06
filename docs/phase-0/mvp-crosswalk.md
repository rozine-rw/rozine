# MVP source-to-delivery crosswalk

**Status:** `MAPPED — IMPLEMENTATION AND ACCEPTANCE NOT ASSERTED` · **Version:** 1.0 · **Date:** 2026-09-10

**Goal:** Give every MVP criterion, role screen, named state, launcher/demo requirement and source feature an owned implementation and evidence destination without importing conflicting PDF policy.

## Source, authority and interpretation

Source: [Rozine MVP Spec.pdf](../Rozine%20MVP%20Spec.pdf), August 2026, 14 pages, 759,501 bytes. SHA-256: `5019b5f6a53e44d5f42b539c77429113c1eb5c18c6392b0a0916b0e9dc18642a`. All 14 rendered pages were inspected; page references below use the PDF's physical page numbers. The archived copy is byte-identical to the accessible Downloads source.

The [source authority record](source-authority-record.md), [BRS v1.0](../Rozine-BRS.md), [active plan](../Rozine_Phased_Implementation_Plan.md) and [conflict dispositions](conflict-disposition-register.md) govern. This is the MVP portion of the per-ID register, **not** a claim that the separate all-BRS-ID register is complete. The [deferred-scope register](deferred-scope-register.md) preserves every exclusion and post-MVP work package.

PDF waves 1/2/3 map to active plan Phases 1/2/3; Phase 0 is governance, Phase 4 is live acceptance, and Phases 5–8 are post-MVP. D-04 Option B puts the **narrow native secure-capture companion inside MVP**, with ordinary Auditor screens on Inertia web/PWA. Its estimate, named implementation allocation, device matrix and seven-day probe still gate the schedule; full native role clients remain Phase 5.

### Reading a row

- `M` = mapped and planned; no implementation or passing evidence asserted. `G` = mapped but named policy/provider/assurance gates remain open. `DP` = proposed deferral, **not signed and not yet removable from RC acceptance**. `R` = source behavior rejected by a governing invariant; the stated BRS-safe replacement remains planned.
- `A` = Aminu, core/actions/Resources and server tests. `E` = Erastus, UI/client tests and browser/device evidence. `K` = Kimani, Finance/Risk, Compliance and Audit Operations validation. `Rbt` = Robert, Product, internal Legal and Brand/content validation. These are delivery responsibilities under the existing plan, not signatures or newly granted approval powers. A non-author independent reviewer must witness acceptance; an AI run cannot sign it.
- A row's slice keys resolve its exact plan checklist destination, BRS authority, automated evidence `AT-<slice>` and witnessed evidence `WT-<slice>` below. Multiple keys require **all** those evidence packs. Its `Owners` cell allocates the implementation/test owners and domain witness owner. Each explicit state inherits that entire row, plus the generic state contract; a state is not accepted merely because the screen's happy path passes.
- All listed evidence is **required future evidence**, not a reference to an existing passing test. At implementation, record actual test path/name, fixture/policy versions, command/report, exact server/client SHA, browser/device artifact, witness/date and result against each ID. No blank, `TBD`, or assumed pass can close a row.
- Every slice has the plan Section 11 D-65/D-66/D-67 quality gates in addition to its behavior evidence. Phase 3 proves the RC with deterministic provider surrogates; Phase 4 separately proves authorized live rails, operational controls and the required real three-month Note lifecycle. No surrogate closes live acceptance.

## Implementation slices and evidence contracts

| Slice | Phase and exact active-plan checklist destination | Governing requirements / gates | Automated evidence required | Witnessed evidence required |
|---|---|---|---|---|
| ID | 1 identity/Party/role policies; 2 identity exceptions | BR-1–BR-5, FR-100/200/300/413, NFR-3; D-05/08/64 | AT-ID: cross-role/ownership denials, same-person conflicts, role context, KYB/KYC expiry, redacted Resources, re-authentication and session isolation | WT-ID: authorized multi-role journey and forbidden-account attempts; K/Rbt approve membership/mandate policy |
| CORE | 1 shared actions/Resources, ledger, immutable events and outbox; 2 reversal/failure paths | BR-60–BR-67, FR-600–FR-607, DR-7; D-09/17/21/40 | AT-CORE: balanced entries, daily invariant alarm, immutable postings/events, contra links, duplicate/concurrent delivery, outbox propagation, segregated reconciliation | WT-CORE: trace an amount to postings and a non-money fact to retained inputs/policy; reconcile client and operating funds; demonstrate no privileged overwrite |
| UW | 1 statement ingestion, deterministic underwriting, offer/rating display; 2 recalculation | BR-10–BR-37, FR-201–FR-205/213, FR-700–FR-702; C-21, D-11 family/12/14/18, Appendix A activation | AT-UW: approved golden vectors, statement provenance/reuse, parsing corrections, history/staleness gates, manual/decline/auto boundaries, exact-money/schedule parity and rating immutability | WT-UW: K/Rbt review explained ceiling, actionable decline and cost before consent; no requested-amount underwriting, hidden score or APR |
| ORIG | 1 application→co-sign→listing→funding→signing→disbursement; 2 expiry/failure completion | BR-26–BR-28/40/64/66, FR-104/204/205/404; D-09/10/15/22/64 | AT-ORIG: eligible verified issue, capacity/limits, oversubscription/concurrency, consent version, conditions precedent, 30-day expiry/refund and disbursement authorization | WT-ORIG: one Business/Auditor/Investor/Admin chain with costs, contract copy, funding progress and receipt |
| AUD | 1 dispatch/checklist/reconcile/seal; 2 offline/monthly/earnings; narrow native exception in MVP | BR-40–BR-56, FR-301–FR-312; C-23/24, D-04/29–37, G-AUD-01 | AT-AUD: radius/capacity/rotation/conflict/expiry dispatch, camera-only signed evidence, geo/time/device tamper rejection, ordered idempotent encrypted sync, amendment-only publication, variance explanation and SLA yield freeze | WT-AUD: independent supported-device no-signal visit, storage/process/clock/location attacks, seven-day dwell/remote-wipe evidence; review 24-hour Flash clock, earnings and re-dispatch |
| SERV | 1 first repayment; 2 monthly reporting, arrears, dispute, cure and recovery | BR-24/25/36/41/46/55/63/67, FR-103/107/206–FR-210/309/406/409/417; C-22, D-23–25/28, D-11C activation | AT-SERV: 1st–7th submission and co-sign, monthly allocation, early payoff, responsible-partner yield freeze, current/arrears/default/dispute transitions, amendments, notices and cross-role refresh | WT-SERV: phone report in under five minutes on 3G; explain overdue/frozen causes and remedy; K witnesses recovery and statement-to-payout reconciliation |
| MONEY | 1 wallet/deposit/payout adapter; 2 withdrawal/reconciliation exceptions; 4 certified rails | BR-60–BR-67, FR-109/211/408, IR-1/2; C-33, D-21/38 | AT-MONEY: signed callback verification, duplicate/out-of-order settlement, pending/failed/reversed transactions, cutoff/limit handling and no double cash/fee posting | WT-MONEY: sandbox failures and receipts for RC; separately certified bank/MoMo deposits, payouts, independent reconciliation and incident recovery in Phase 4 |
| PORT | 1 browse/buy/portfolio; 2 history/flags/concentration; 3 presentation finish | FR-101–FR-113, BR-21/26/27/64; C-25/26/29, D-10/15/18 | AT-PORT: exact shared Resource facts, itemized fees, projected/realised separation, snapshot-to-live updates, authorized evidence visibility and concentration totals | WT-PORT: retail/fund reviewers inspect disclosure, audit and schedule before confirmation, then see immediate arrears/freeze notice |
| SEC | 1 Holding/Order/fee/halt contracts; 2 eligibility/read models; 3 complete peer-to-peer secondary | BR-70–BR-75, FR-108/407; D-60 approved scope, D-26/27 pending | AT-SEC: the complete SEC-01–SEC-09 matrix below, including settlement races and no-principal negative controls | WT-SEC: seller→buyer→Admin witnessed order, settlement, reconciliation and halt; no promise of liquidity or Rozine quote |
| OPS | 1 applications/disbursements/parties/log; 2 breaks/risk/partners; 3 supervisor pack; 4 live operations | FR-400–FR-418, CR-12, BR-17/74; G-OPS-01, D-39–43/54 and D-71 external approval boundaries | AT-OPS: least privilege, actor/reason, separate approvers, no self-approval, lawful freeze/release, immutable admin trail, break ageing, supervisor read-only denials and export access | WT-OPS: K witnesses two-person control, unresolved reconciliation break, lawful freeze/release, self-describing regulatory trail and read-only supervisor seat |
| REPORT | 1 schedule/statement basics; 2 period exports; 3 reconciled regulator/board/demo packs | FR-109/112/208/414/418, CR-12, C-01/30 | AT-REPORT: period boundaries, snapshot/as-of labels, pending figures, exact wallet/ledger reconciliation, redaction, export permissions/expiry and injection-safe output | WT-REPORT: reconcile exported period to wallet and ledger, explain provenance without staff narration; financial year selection never requests a tax identifier |
| UX | 1 complete foundation/launcher; 2 state completeness; 3 3G/accessibility/demo finish | NFR-1–NFR-14, plan Section 7, C-20/31/32; D-04/07 and approved brand matrix | AT-UX: per-state browser/component tests, recoverable drafts/position, authorization-safe refresh, layout/keyboard/accessibility checks, offline safety and demo-reset isolation | WT-UX: cold reload, narrow phone/desktop and assistive-technology walkthrough; throttled 3G first interactivity under five seconds and no post-paint layout shift |
| PLUS | 3 honest Automation explainer only; 7 executable tranche if signed deferral | C-29, D-61 and full fee/suitability/concentration/legal policy | AT-PLUS: MVP has no executable mandate/band fee side effects; future tranche tests threshold/hold/loss/preview/pause/cancel/cap concurrency from approved policy | WT-PLUS: Rbt/K witness explainer truthfulness; signed D-61 required to exclude AC-07/08 from RC, otherwise rebaseline Phase 3 after full policy approval |

Two source operational details need explicit local gate references; these are **open mapping gates, not new approved decisions or invented D numbers**:

- **G-OPS-01:** K/Rbt, with A/E contract review, must baseline the versioned disbursement/manual-entry/reversal maker-checker and threshold matrix before Phase 1 money/control implementation. Plan L6/L10 and BRS FR-408 supply the obligation; no amount or below-threshold bypass is approved here. D-35 concerns verification seals, not financial approval thresholds.
- **G-AUD-01:** K owns the versioned variance tolerances, quality measure, source one-in-ten re-performance target and warning/suspension/decertification procedure; Rbt and A/E review participant obligations and contracts, with external professional approval where required. Baseline variance/checklist rules before Phase 1 filing and quality/sampling/sanction rules before Phase 2. D-29/30/32/33/34 govern related procedures, licence, dispatch, conflicts and lateness; they do not themselves approve a quality formula or sampling implementation.

## Acceptance criteria — all 46 source rows

Titles retain the PDF's acceptance intent. The treatment column is authoritative where literal PDF wording conflicts. Owners and evidence packs apply to every row individually.

| ID | PDF page / source criterion | Phase / slices | BRS-safe treatment and outstanding gate | Owners | Status |
|---|---|---|---|---|---|
| MVP-SPINE-AC-01 | 3 · Every displayed figure traces to the ledger | 1–3 CORE/UW/REPORT | C-30: money→ledger/schedule; rating/capacity→inputs, derivation, policy and events | A/E/K | M |
| MVP-SPINE-AC-02 | 3 · The ledger balances at all times | 1–2 CORE | Daily zero-sum check and loud failure, including concurrent/reversal paths | A/E/K | M |
| MVP-SPINE-AC-03 | 3 · Client money is never company money | 1–2 CORE/MONEY; 4 live | BR-60; C-26 excludes an invented loss reserve/5% floor; D-21 custody topology | A/E/K | G |
| MVP-SPINE-AC-04 | 3 · One action, one effect | 1–2 CORE/MONEY | Duplicate request and provider event produce exactly one effect | A/E/K | M |
| MVP-SPINE-AC-05 | 3 · Nothing is ever destroyed | 1–2 CORE/AUD | Immutable ledger, reports and events; not a ban on lawful PII deletion/retention under DR-7/D-40 | A/E/K | G |
| MVP-SPINE-AC-06 | 3 · Every state change names its actor | 1–2 CORE/OPS | Actor/action/target/time/before/after; reason on privileged mutations; protect sensitive payloads | A/E/K | M |
| MVP-SPINE-AC-07 | 3 · No rating is ever chosen by a human | 1 UW/OPS | Rating cannot be set; BR-17 reasoned Superadmin capacity override is distinct and not silently removed | A/E/K | G |
| MVP-SPINE-AC-08 | 3 · A change in core shows everywhere | 1–2 CORE/SERV/PORT | Authorized cross-role event/read-model propagation without manual reload | A/E/Rbt | M |
| MVP-SPINE-AC-09 | 3 · No dead ends | 1–3 UX | Every explicit and inherited applicable state has a permitted next action | A/E/Rbt | M |
| MVP-SPINE-AC-10 | 3 · Works on a 3G phone | 3 UX | First screen interactive under 5 seconds; no later layout shift; record device/network/build | A/E/Rbt | M |
| MVP-SPINE-AC-11 | 3 · A user only ever sees their own data | 1–2 ID/OPS | Core role, ownership and legitimate scoped staff/supervisor access, not UI hiding; D-05 | A/E/K | G |
| MVP-SPINE-AC-12 | 3 · A cold reload loses nothing | 1–2 UX/AUD | Resume authorized drafts/position; revalidate stale policy/auth; never replay a money command blindly | A/E/Rbt | M |
| MVP-BUSINESS-AC-01 | 6 · A business never enters the same figure twice | 1 UW/AUD | Reuse authorized filed/verified/derived data with provenance and explicit correction path | A/E/Rbt | M |
| MVP-BUSINESS-AC-02 | 6 · The ceiling is explained, not just stated | 1 UW | C-21/D-12: no unapproved 35% revenue cap; Appendix A activation required | A/E/K | G |
| MVP-BUSINESS-AC-03 | 6 · Every cost is visible before acceptance | 1 UW/ORIG | BR-21/26/62/64: francs, flat total return, instalment and charges beside Accept; no annualised/effective APR; D-09 | A/E/K/Rbt | G |
| MVP-BUSINESS-AC-04 | 6 · A refusal is actionable | 1 UW | Decline/manual/Unrated distinctions, failed test and correction/review route | A/E/K | G |
| MVP-BUSINESS-AC-05 | 6 · The schedule never disagrees with the ledger | 1–2 CORE/SERV | Server schedule, actual postings and pending items remain distinguishable and reconcilable | A/E/K | M |
| MVP-BUSINESS-AC-06 | 6 · Filing a monthly report takes under five minutes | 2 SERV/AUD/UX | Phone on 3G including photos; approved secure-capture handoff must fit the witnessed journey | A/E/K | G |
| MVP-BUSINESS-AC-07 | 6 · Arrears are impossible to miss | 2 SERV/UX | Dashboard overdue notice from core; no day-7 automatic penalty/default; D-23/25 | A/E/K | G |
| MVP-BUSINESS-AC-08 | 6 · A frozen deal explains itself | 2 SERV/OPS | Show lawful trigger, contact and release conditions; reporting miss is not automatically a note freeze | A/E/K | G |
| MVP-AUDITOR-AC-01 | 8 · A visit can be completed with no signal | 2 AUD/UX | D-04 narrow native capture package; encrypted durable capture and ordered lossless sync, not offline approval | A/E/K | G |
| MVP-AUDITOR-AC-02 | 8 · No screen asks for a judgement | 1 AUD/UW | No grade/recommendation/credit approval input; recorded evidence legitimately affects computed rating | A/E/K | M |
| MVP-AUDITOR-AC-03 | 8 · A filed report is immutable | 1–2 AUD/CORE | Submitted/published original preserved; correction is a linked new amendment under approved lifecycle | A/E/K | M |
| MVP-AUDITOR-AC-04 | 8 · Photos cannot be back-dated or borrowed | 1–2 AUD | D-04 camera-only capture, trusted integrity assertions and server tamper rejection; real-device proof required | A/E/K | G |
| MVP-AUDITOR-AC-05 | 8 · A partner knows what they will earn | 2 AUD/SERV | C-23: 25% collected attributable service fees; no invented 10%, minimum floor or unpaid Flash policy | A/E/K | G |
| MVP-AUDITOR-AC-06 | 8 · A variance is never silently accepted | 1 AUD | Out-of-tolerance finding requires reason and review; no automatic credit verdict | A/E/K | G |
| MVP-AUDITOR-AC-07 | 8 · Deadlines are unambiguous | 1–2 AUD/SERV | C-24: 24-hour Flash countdown, monthly 1st–7th submission/co-sign; routine SLA D-32, not blanket 48 hours | A/E/K | G |
| MVP-AUDITOR-AC-08 | 8 · A conflict can always be declared | 1 AUD/ID | Record conflict and immediately re-dispatch; unassignable queue must explain capacity/eligibility, not fabricate a replacement | A/E/K | M |
| MVP-INVESTOR-AC-01 | 11 · Nothing is bought without its full cost on screen | 1 PORT/ORIG; 3 SEC | Exact francs/net proceeds/return and exclusive BR-62 fees before confirm; D-09/10/15/27 | A/E/K/Rbt | G |
| MVP-INVESTOR-AC-02 | 11 · A projection is never dressed as a return | 1–3 PORT/REPORT | Realised vs projected labels, input provenance, flat not annualised return and loss risk | A/E/K/Rbt | M |
| MVP-INVESTOR-AC-03 | 11 · An exit quote is firm or absent | 3 SEC | R: C-27/BR-75 reject Rozine quote/inventory. Show seller ask and order validity under D-26/27; no platform liquidity guarantee | A/E/K/Rbt | R |
| MVP-INVESTOR-AC-04 | 11 · A blocked exit explains itself | 3 SEC/SERV | R: show actual eligibility/halt reason. Arrears/Default/Disputed holdings cannot use the order book either; no 45-day loophole | A/E/K/Rbt | R |
| MVP-INVESTOR-AC-05 | 11 · Concentration is impossible to ignore | 2 PORT | Business and sector exposure on portfolio; D-15 limits, no assumed five-note/50% rule | A/E/K | G |
| MVP-INVESTOR-AC-06 | 11 · A frozen or defaulted note surfaces immediately | 2 SERV/PORT | Authorized push/poll/event refresh from core; preserve objective default gate | A/E/K | G |
| MVP-INVESTOR-AC-07 | 11 · Plus never surprises anyone | 7 PLUS; 3 explainer | DP: D-61 signature absent; future threshold/hold/loss notices require approved band/fee policy | A/E/K/Rbt | DP |
| MVP-INVESTOR-AC-08 | 11 · A mandate cannot exceed its own cap | 7 PLUS; 3 explainer | DP: D-61 signature absent; no unapproved 50% constant or live mandate. Rebaseline Phase 3 if not deferred | A/E/K/Rbt | DP |
| MVP-INVESTOR-AC-09 | 11 · Every earning traces to a repayment | 1–2 CORE/SERV/PORT | Drill down to instalment, note, holder record date, fee and posting | A/E/K | M |
| MVP-INVESTOR-AC-10 | 11 · The whole history is exportable | 2–3 REPORT/MONEY | Any permitted period; itemized charges and exact wallet reconciliation, no tax identifiers | A/E/K | M |
| MVP-ADMIN-AC-01 | 13 · No action is possible without an actor and a reason | 1–2 OPS/CORE | Privileged mutations require actor/reason before commit, including rejected unauthorized attempts | A/E/K | M |
| MVP-ADMIN-AC-02 | 13 · Nothing can be deleted or edited | 1–2 OPS/CORE/AUD | Ledger/audit/event originals immutable; approved ordinary Party maintenance and lawful PII retention remain possible | A/E/K | G |
| MVP-ADMIN-AC-03 | 13 · Money movement above a threshold needs two people | 1 OPS/MONEY | G-OPS-01 approval matrix, distinct people and no self-approval; BR/FR dual-control requirements cannot be weakened below threshold | A/E/K | G |
| MVP-ADMIN-AC-04 | 13 · No staff account can set a rating | 1 OPS/UW | No permission/endpoint for rating override; versioned future policy is not live-note yield editing | A/E/K | M |
| MVP-ADMIN-AC-05 | 13 · Yesterday reconciles before today opens | 2 OPS/MONEY | Block financial close with unexplained breaks; age/assign them. Does not authorize blanket denial of all app access | A/E/K | G |
| MVP-ADMIN-AC-06 | 13 · The event log answers any question about a record | 1–2 OPS/CORE/REPORT | Search/export actor/action/target/before/after/time with authorized sensitive-field handling | A/E/K | M |
| MVP-ADMIN-AC-07 | 13 · A regulator can be given a full trail unaided | 3 OPS/REPORT; 4 live | Self-describing traceable pack plus BRS read-only supervisor seat; no PDF third-party-account exclusion | A/E/K | G |
| MVP-ADMIN-AC-08 | 13 · A freeze is always reversible and always attributed | 2 OPS/SERV | Attributed reasoned freeze/release command, lawful authority and release conditions; no unconditional bypass of legal hold | A/E/K | G |

## Screen and named-state register

Each state ID below is permanent and independently testable. Source labels are retained even when the safe treatment differs. `Phase` is delivery/finish, not current completion. Profiles resolve the additional generic states in the following section. All 38 role screens remain represented; the Automation screen is not removed by the proposed executable-Plus deferral.

| Screen ID | Page · screen / carries | Named state IDs and source labels | Phase / slices | Profile | Owners | Status / treatment |
|---|---|---|---|---|---|---|
| MVP-BUSINESS-SCR-01 | 6 · Dashboard / next payment, rating, live raise | MVP-BUSINESS-SCR-01-ST-01 no deal yet; MVP-BUSINESS-SCR-01-ST-02 frozen; MVP-BUSINESS-SCR-01-ST-03 in arrears | 1–2 PORT/SERV/UX | READ | A/E/K | G: actual cause and remedy, no invented penalty |
| MVP-BUSINESS-SCR-02 | 6 · Apply / statements, term, live ceiling | MVP-BUSINESS-SCR-02-ST-01 draft; MVP-BUSINESS-SCR-02-ST-02 ineligible; MVP-BUSINESS-SCR-02-ST-03 under review | 1–2 UW/ORIG/UX | WRITE | A/E/K | G: BRS history/tenors/capacity, D-14 activation |
| MVP-BUSINESS-SCR-03 | 6 · Rating / score, factors, ceiling | MVP-BUSINESS-SCR-03-ST-01 pending audit; MVP-BUSINESS-SCR-03-ST-02 refused, with reasons | 1–2 UW/UX | READ | A/E/K | G: published rating only, actionable manual/decline |
| MVP-BUSINESS-SCR-04 | 6 · Raise / fill progress, charge, sign | MVP-BUSINESS-SCR-04-ST-01 not yet listed; MVP-BUSINESS-SCR-04-ST-02 fully funded; MVP-BUSINESS-SCR-04-ST-03 expired | 1–2 ORIG/CORE/UX | WRITE | A/E/K | G: expired listing returns all committed funds without fee |
| MVP-BUSINESS-SCR-05 | 6 · Repayments / schedule, paid, upcoming | MVP-BUSINESS-SCR-05-ST-01 paid ahead; MVP-BUSINESS-SCR-05-ST-02 overdue; MVP-BUSINESS-SCR-05-ST-03 defaulted | 1–2 SERV/MONEY/UX | RAIL | A/E/K | G: early payoff preserves total return; D-23–25 |
| MVP-BUSINESS-SCR-06 | 6 · Reports / monthly filings and status | MVP-BUSINESS-SCR-06-ST-01 due now; MVP-BUSINESS-SCR-06-ST-02 late; MVP-BUSINESS-SCR-06-ST-03 rejected by CPA | 2 SERV/AUD/UX | WRITE | A/E/K | G: submission and co-sign by 7th, amendment/review next action |
| MVP-BUSINESS-SCR-07 | 6 · Audits / every report CPA filed | MVP-BUSINESS-SCR-07-ST-01 awaiting first audit; MVP-BUSINESS-SCR-07-ST-02 CPA reassigned | 1–2 AUD/SERV/UX | READ | A/E/K | M: retain history and authorized new-partner contact |
| MVP-BUSINESS-SCR-08 | 6 · Wallet / disbursements and payments out | MVP-BUSINESS-SCR-08-ST-01 empty; MVP-BUSINESS-SCR-08-ST-02 pending settlement; MVP-BUSINESS-SCR-08-ST-03 failed | 1–2 MONEY/UX; 4 live | RAIL | A/E/K | G: inspect receipt/status before retry, D-21/38 |
| MVP-BUSINESS-SCR-09 | 6 · Profile / company, signatories, documents | MVP-BUSINESS-SCR-09-ST-01 incomplete; MVP-BUSINESS-SCR-09-ST-02 document expired | 1–2 ID/UX | RAIL | A/E/K/Rbt | G: D-64 mandate, no tax-clearance/TIN field |
| MVP-AUDITOR-SCR-01 | 8 · Jobs / assigned work, deadlines, distance | MVP-AUDITOR-SCR-01-ST-01 none assigned; MVP-AUDITOR-SCR-01-ST-02 overdue; MVP-AUDITOR-SCR-01-ST-03 declined | 1–2 AUD/UX | WRITE | A/E/K | G: 30 km office radius/rotation/capacity, Flash 24 hours |
| MVP-AUDITOR-SCR-02 | 8 · Business file / history, last audit, flags | MVP-AUDITOR-SCR-02-ST-01 first visit; MVP-AUDITOR-SCR-02-ST-02 reassigned to you | 1–2 AUD/ID/UX | READ | A/E/K | M: assignment-scoped historical access |
| MVP-AUDITOR-SCR-03 | 8 · Checklist / tests in fixed order | MVP-AUDITOR-SCR-03-ST-01 part complete; MVP-AUDITOR-SCR-03-ST-02 offline, unsynced | 1–2 AUD/UX | FIELD | A/E/K | G: D-04 durable local progress with visible sync status |
| MVP-AUDITOR-SCR-04 | 8 · Capture / photos, counts, documents | MVP-AUDITOR-SCR-04-ST-01 no signal; MVP-AUDITOR-SCR-04-ST-02 storage full; MVP-AUDITOR-SCR-04-ST-03 upload failed | 1–2 AUD/UX | FIELD | A/E/K | G: narrow native secure-capture handoff; preserve encrypted evidence |
| MVP-AUDITOR-SCR-05 | 8 · Reconcile / feeds against counted stock | MVP-AUDITOR-SCR-05-ST-01 variance found; MVP-AUDITOR-SCR-05-ST-02 feed unavailable | 1–2 AUD/UX | FIELD | A/E/K | G: approved statement/manual evidence; no unauthorized automated bank-feed pull |
| MVP-AUDITOR-SCR-06 | 8 · File / review, signature, submission | MVP-AUDITOR-SCR-06-ST-01 incomplete; MVP-AUDITOR-SCR-06-ST-02 submitted; MVP-AUDITOR-SCR-06-ST-03 rejected | 1–2 AUD/CORE/UX | FIELD | A/E/K | G: offline staged package is not server publication; server validates seal/licence |
| MVP-AUDITOR-SCR-07 | 8 · Reports / everything filed | MVP-AUDITOR-SCR-07-ST-01 none yet; MVP-AUDITOR-SCR-07-ST-02 one late; MVP-AUDITOR-SCR-07-ST-03 one rejected | 1–2 AUD/SERV/UX | READ | A/E/K | G: original immutable; late/report rejection has review/amendment route |
| MVP-AUDITOR-SCR-08 | 8 · Earnings / per note/month, upcoming | MVP-AUDITOR-SCR-08-ST-01 nothing earned yet; MVP-AUDITOR-SCR-08-ST-02 payment delayed | 2 AUD/SERV/MONEY/UX | RAIL | A/E/K | G: accrued/paid/frozen 25% attributable service-fee share |
| MVP-INVESTOR-SCR-01 | 10 · Deals / rated deals, filters, live fill | MVP-INVESTOR-SCR-01-ST-01 none listed; MVP-INVESTOR-SCR-01-ST-02 all sold out; MVP-INVESTOR-SCR-01-ST-03 gated | 1–3 PORT/ID/UX | READ | A/E/K/Rbt | G: eligible deals only; explain KYC/policy gate |
| MVP-INVESTOR-SCR-02 | 10 · Deal detail / audit, terms, costs | MVP-INVESTOR-SCR-02-ST-01 sold out mid-read; MVP-INVESTOR-SCR-02-ST-02 frozen; MVP-INVESTOR-SCR-02-ST-03 withdrawn | 1–2 PORT/ORIG/UX | READ | A/E/K | G: revalidate before purchase, no stale offer acceptance |
| MVP-INVESTOR-SCR-03 | 10 · Buy sheet / itemized cost, net ROI | MVP-INVESTOR-SCR-03-ST-01 below minimum; MVP-INVESTOR-SCR-03-ST-02 wallet short; MVP-INVESTOR-SCR-03-ST-03 cap hit | 1–3 PORT/ORIG/SEC/UX | WRITE | A/E/K | G: 5,000 minimum, D-10/15 units/limits; primary and eligible secondary variants |
| MVP-INVESTOR-SCR-04 | 10 · Portfolio / positions, returns, concentration | MVP-INVESTOR-SCR-04-ST-01 nothing held yet; MVP-INVESTOR-SCR-04-ST-02 a note in arrears | 1–2 PORT/SERV/UX | READ | A/E/K | G: distinguish realised/projected, show arrears immediately |
| MVP-INVESTOR-SCR-05 | 10 · Note detail / schedule, audits, exit options | MVP-INVESTOR-SCR-05-ST-01 matured; MVP-INVESTOR-SCR-05-ST-02 frozen; MVP-INVESTOR-SCR-05-ST-03 defaulted | 1–3 PORT/SERV/SEC/UX | READ | A/E/K | G: no trading of ineligible holding, retain audit and payout history |
| MVP-INVESTOR-SCR-06 | 10 · Market / inventory, own orders, book | MVP-INVESTOR-SCR-06-ST-01 empty book; MVP-INVESTOR-SCR-06-ST-02 your order unfilled | 3 SEC/UX | WRITE | A/E/K | R: peer-owned listings only, no Rozine inventory; D-26/27 |
| MVP-INVESTOR-SCR-07 | 10 · Sell sheet / quote, discount, net proceeds | MVP-INVESTOR-SCR-07-ST-01 exit blocked; MVP-INVESTOR-SCR-07-ST-02 quote expired | 3 SEC/UX | WRITE | A/E/K | R: seller ask/disclosed proceeds; source quote-expired becomes expired order/confirmation, never a Rozine quote |
| MVP-INVESTOR-SCR-08 | 10 · Wallet / balance, in/out, pending | MVP-INVESTOR-SCR-08-ST-01 empty; MVP-INVESTOR-SCR-08-ST-02 withdrawal pending; MVP-INVESTOR-SCR-08-ST-03 failed | 1–2 MONEY/UX; 4 live | RAIL | A/E/K | G: no 0.5% withdrawal fee or unconditional same-day promise |
| MVP-INVESTOR-SCR-09 | 10 · Automation / mandate, outcomes, charge band | MVP-INVESTOR-SCR-09-ST-01 not eligible; MVP-INVESTOR-SCR-09-ST-02 paused; MVP-INVESTOR-SCR-09-ST-03 cap reached | 3 PLUS/UX; 7 execution | EXPLAIN | A/E/K/Rbt | DP: MVP explainer retained; paused/cap execution fixtures belong to unsigned D-61 deferral, not fabricated live outcomes |
| MVP-INVESTOR-SCR-10 | 10 · Reports / statements, returns, tax year | MVP-INVESTOR-SCR-10-ST-01 no history yet; MVP-INVESTOR-SCR-10-ST-02 period incomplete | 1–3 REPORT/UX | WRITE | A/E/K | M: financial-period exports only, no tax-authority integration/data |
| MVP-INVESTOR-SCR-11 | 10 · Profile / identity, bank, tier | MVP-INVESTOR-SCR-11-ST-01 unverified; MVP-INVESTOR-SCR-11-ST-02 document expired | 1–2 ID/MONEY/UX | RAIL | A/E/K | G: approved KYC category/tier, not unapproved Plus ladder |
| MVP-ADMIN-SCR-01 | 13 · Today / queues, breaks, human attention | MVP-ADMIN-SCR-01-ST-01 all clear; MVP-ADMIN-SCR-01-ST-02 a break unassigned | 1–2 OPS/UX | WRITE | A/E/K | M: assign/escalate rather than hide breaks |
| MVP-ADMIN-SCR-02 | 13 · Applications / engine decision and evidence | MVP-ADMIN-SCR-02-ST-01 none waiting; MVP-ADMIN-SCR-02-ST-02 missing audit | 1–2 OPS/UW/ORIG/UX | WRITE | A/E/K | G: missing evidence blocks listing, no human score field |
| MVP-ADMIN-SCR-03 | 13 · Disbursements / approved raises awaiting release | MVP-ADMIN-SCR-03-ST-01 awaiting second approver; MVP-ADMIN-SCR-03-ST-02 failed payout | 1–2 OPS/MONEY/UX; 4 live | RAIL | A/E/K | G: no self-approval, inspect failure before retry |
| MVP-ADMIN-SCR-04 | 13 · Reconciliation / three balances side by side | MVP-ADMIN-SCR-04-ST-01 out of balance; MVP-ADMIN-SCR-04-ST-02 feed unavailable | 2 OPS/CORE/MONEY/UX; 4 live | RAIL | A/E/K | R: D-21 account topology; no invented reserve bucket/floor |
| MVP-ADMIN-SCR-05 | 13 · Book / live notes and health | MVP-ADMIN-SCR-05-ST-01 none live; MVP-ADMIN-SCR-05-ST-02 concentration cap breached | 1–2 OPS/SERV/PORT/UX | READ | A/E/K | G: approved exposure policy and actual triggers |
| MVP-ADMIN-SCR-06 | 13 · Exceptions / arrears, halts, variances | MVP-ADMIN-SCR-06-ST-01 none open; MVP-ADMIN-SCR-06-ST-02 one past escalation window | 2–3 OPS/SERV/SEC/UX | WRITE | A/E/K | G: D-25/28/54 escalation, market halt and reasoned remedy |
| MVP-ADMIN-SCR-07 | 13 · Partners / coverage, quality, licences, payouts | MVP-ADMIN-SCR-07-ST-01 district uncovered; MVP-ADMIN-SCR-07-ST-02 licence expired | 1–2 OPS/AUD/UX | RAIL | A/E/K | G: radius/capacity eligibility; expiry auto-suspends dispatch |
| MVP-ADMIN-SCR-08 | 13 · Parties / four party types and history | MVP-ADMIN-SCR-08-ST-01 frozen; MVP-ADMIN-SCR-08-ST-02 KYC overdue | 1–2 ID/OPS/UX | WRITE | A/E/K | G: authority/reason to release, KYC/mandate policy |
| MVP-ADMIN-SCR-09 | 13 · Event log / actor, action, time, target | MVP-ADMIN-SCR-09-ST-01 filtered to nothing; MVP-ADMIN-SCR-09-ST-02 export running | 1–3 OPS/CORE/REPORT/UX | WRITE | A/E/K | M: clear filters/track authorized export, never edit log |
| MVP-ADMIN-SCR-10 | 13 · Reports / regulator, board and exports | MVP-ADMIN-SCR-10-ST-01 period incomplete; MVP-ADMIN-SCR-10-ST-02 figures still moving | 2–3 OPS/REPORT/UX; 4 live | WRITE | A/E/K | G: explicit as-of/pending snapshot and traceable totals |
| MVP-SUITE-SCR-01 | 14 · Launcher / login, authorized apps, active role, switch, return, demo entry | Defined by MVP-SUITE-REQ-01–04 and MVP-DEMO-FLOW-01–05 below; no source screen-state table | 1–3 ID/UX | LAUNCH | A/E/Rbt | G: D-05 permitted memberships, safe role context and guided visitor route |

### Generic state inheritance — no unnamed gaps

For **each** screen above, concatenate its full screen ID, `-GEN-`, and the following suffix to obtain an additional stable state ID (for example `MVP-BUSINESS-SCR-02-GEN-VALIDATION`). These are separate from the **92 literal named states** and include the launcher. Every cell is explicit: `Y` requires automated and witnessed proof, `N` requires the stated non-applicability rationale to stay true. A later new command/provider dependency changes the applicability record before implementation; it cannot silently inherit `N`.

| Suffix / required behavior | READ | WRITE | RAIL | FIELD | EXPLAIN | LAUNCH |
|---|---|---|---|---|---|---|
| LOADING / stable skeleton, no blank frame | Y | Y | Y | Y | Y | Y |
| EMPTY / explanation and permitted next step | Y | Y | Y | Y | Y | Y |
| SUCCESS / completed read or action, factual confirmation | Y | Y | Y | Y | Y | Y |
| VALIDATION / recoverable input error | N: no mutation or form in this profile; any filter form uses WRITE contract | Y | Y | Y | N: non-executable explanation | Y: sign-in/role selection |
| AUTH / authorization gate with safe next step | Y | Y | Y | Y | Y | Y |
| POLICY / unmet eligibility or unavailable function explained | Y | Y | Y | Y | Y | Y |
| PROVIDER / external provider failure or unavailability | N: no direct provider command; stale upstream facts use STALE | N: core-only command; any rail/identity dependency uses RAIL | Y | Y: capture/attestation/ingest | N: no external action | Y: identity/sign-in dependency |
| RETRY / network or request failure with idempotent safe retry | Y | Y | Y | Y | Y | Y |
| FROZEN / safe restricted Party/record state and contact path | Y | Y | Y | Y | Y | Y |
| STALE / expired auth, document, offer, policy or cached fact is revalidated | Y | Y | Y | Y | Y | Y |
| OFFLINE / disconnect and reconnect recovery | Y: labelled cached read or reconnect gate | Y: preserve permitted drafts, no offline money effect | Y: no assumed settlement, recheck status | Y: durable encrypted package and ordered sync | Y: cached explainer or reconnect | Y: no offline role escalation, revalidate session |

READ filter exceptions explicitly set `VALIDATION=Y` for `MVP-BUSINESS-SCR-07` (Audits), `MVP-AUDITOR-SCR-02` (Business file), `MVP-AUDITOR-SCR-07` (Reports), `MVP-INVESTOR-SCR-01` (Deals), `MVP-INVESTOR-SCR-04` (Portfolio), and `MVP-ADMIN-SCR-05` (Book). Test malformed/empty filter results on each. All other READ screens are display-only under this baseline. Report downloads and export forms use WRITE. A Y does **not** require inventing an illegal control: a frozen or offline screen may offer only inspection, support or a safe return. PDF page 2's general `error` state maps to RETRY plus VALIDATION/AUTH/POLICY/PROVIDER as appropriate. UX proof must cover every applicable suffix alongside its explicit source states, on mobile and desktop where shipped.

### Mandatory secondary chain — one owned path, no principal branch

These stable checkpoint IDs decompose SEC and apply to Investor Market/Sell/Buy/Note detail, Admin Exceptions/Book/Reconciliation/Event log, and AC-01/03/04/09/10. All owners are **A/E/K/Rbt**; all rows are **G: mapped, D-26/D-27 execution policy pending**. Phase 1 owns contracts, Phase 2 owns current-state eligibility/read models, Phase 3 owns completed behavior and RC evidence; Phase 4 repeats approved live settlement/reconciliation.

| Checkpoint | Required contract / automated proof | Witnessed proof |
|---|---|---|
| SEC-01 | Only an owned fully settled Holding in a current Repaying Note may be offered; deny Arrears/Default/Disputed/frozen/related-party cases | Seller opens eligible and ineligible holdings; denial names actual condition, not a 45-day threshold |
| SEC-02 | Seller chooses an ask under approved pricing policy; disclose remaining schedule, health, rating, audit, premium/discount and exactly 3% seller fee | Buyer and seller inspect totals and loss/liquidity disclosure; no Rozine inventory/quote/price-setting |
| SEC-03 | Create order idempotently; reserve units to prevent double listing; freeze approved order policy/version | Duplicate submit and competing listing do not duplicate availability |
| SEC-04 | Reserve buyer cash/units atomically under approved lifecycle, timeout and self/beneficial-owner denials | Competing buyers cannot reserve the same units; exact ask and fees visible before commitment |
| SEC-05 | Recheck eligibility, halt and policy at fill; cash/Holding/fees/outbox settle together or roll back together | Inject failure mid-fill and show no orphan cash, fee or unit transfer |
| SEC-06 | Cancel/expire/release reservation idempotently; race with settlement has exactly one legal winner; no invented five-minute/seven-day constants | Seller cancellation, timeout and buyer retry have clear terminal/pending states |
| SEC-07 | Holder-of-record and payout allocation use approved settled cutoff; sold holding retains reports/provenance | Reconcile seller/buyer schedules and subsequent payout to the correct holders |
| SEC-08 | Reconcile all cash/unit/fee movements and immutable actor/reason/correlation events; surveillance flags cannot mutate evidence | K traces order→fill→holdings→ledger→wallet/export; Operations reviews flagged linked/circular/velocity activity |
| SEC-09 | Immediate per-note/global Admin halt, restricted resume, and in-flight settlement race tests; negative tests for principal/maker/taker/acquisition/reserve routes | Independent staff halts and safely resumes permitted trading; forbidden Rozine-principal branch does not exist |

## Launcher and demo — every page 14 requirement

The 24 specification-box fields and five visitor steps each have an ID below. Introductory/side prose is covered explicitly: one identity/four authorized apps by SUITE-01–04, multi-role context by SUITE-02/03, guided entry by FLOW-01/02, seeded non-empty entry by FLOW-03, and healthy/arrears/frozen/matured/early-exit coverage by DEMO-02/05–07. Source labels such as `tax year`, `three balances`, `Plus`, and `exited to Rozine` do not activate rejected policy.

Every demo row also requires AT-UX/WT-UX fixture reset and environment-isolation proof. Demo fixtures may simulate approved marketplace behavior only in an unmistakably isolated environment; neither seeded records nor fake provider success may be represented as live activity or live certification.

| ID | Page 14 field / requirement | Phase / slices | Required proof or safe disposition | Owners | Status |
|---|---|---|---|---|---|
| MVP-SUITE-REQ-01 | Launcher · Sign-in | 1 ID/UX | One identity/sign-in across all authorized role surfaces; D-04 companion handoff does not create a second Party | A/E/K/Rbt | G |
| MVP-SUITE-REQ-02 | Launcher · Visible | 1 ID/UX | Only permitted apps shown; direct unauthorized route/API calls still denied; D-05 memberships | A/E/K/Rbt | G |
| MVP-SUITE-REQ-03 | Launcher · Switch | 1–2 ID/UX | No second login or lost position; persistent active-role context and role-specific cache invalidation | A/E/K/Rbt | G |
| MVP-SUITE-REQ-04 | Launcher · Return | 1–2 ID/UX | Per-app return after switch/reload; expired session or lost membership cannot expose saved data | A/E/K/Rbt | M |
| MVP-DEMO-REQ-01 | Seeded book · Businesses across sectors and all three ratings | 3 UW/UX | C-31 replaces three with all four BRS bands; Distressed historical/non-listable | A/E/K/Rbt | R |
| MVP-DEMO-REQ-02 | Seeded book · Notes live, matured, in arrears, frozen | 3 SERV/PORT/UX | Deterministic fixtures and authorized cross-role consistent facts for each lifecycle | A/E/K/Rbt | M |
| MVP-DEMO-REQ-03 | Seeded book · Retail Investor and Plus fund with mandate | 3 PORT/PLUS/UX; 7 execution | Retail/fund account within D-08/15; executable mandate only after D-61/Plus approval; MVP explainer is labelled non-executable | A/E/K/Rbt | DP |
| MVP-DEMO-REQ-04 | Seeded book · Several CPAs with filed histories | 3 AUD/UX | Multiple eligible/expired/conflicted CPA fixtures; immutable reports, assignment and earnings provenance | A/E/K/Rbt | M |
| MVP-DEMO-REQ-05 | States · Healthy note repaying on schedule | 3 SERV/PORT/UX | Trace scheduled and paid instalments through all roles | A/E/K/Rbt | M |
| MVP-DEMO-REQ-06 | States · Arrears, halt, recovery plan | 3 SERV/SEC/OPS/UX | Distinct approved triggers, responsible owner and permitted cure; no day-7 default or automatic penalty waiver | A/E/K/Rbt | G |
| MVP-DEMO-REQ-07 | States · One exited to Rozine, one via book | 3 SEC/UX | C-27/D-60 reject sale to Rozine; show eligible Investor-to-Investor sale before maturity and preserved audit trail | A/E/K/Rbt | R |
| MVP-DEMO-REQ-08 | States · Refused application | 3 UW/UX | Real failed eligibility vector, reasons and correction/manual-review path, not random refusal | A/E/K/Rbt | G |
| MVP-DEMO-REQ-09 | RCMA · Segregation, three reconciled balances | 3 CORE/MONEY/OPS/UX; 4 live | D-21 approved custody topology, client/company separation; no C-26 reserve invented to create a third balance | A/E/K/Rbt | G |
| MVP-DEMO-REQ-10 | RCMA · Trail back to first event | 3 CORE/OPS/REPORT/UX | Open a record and traverse retained provenance/first event; reconcile monetary facts | A/E/K/Rbt | M |
| MVP-DEMO-REQ-11 | RCMA · No override paths | 3 UW/OPS/UX | Rating/live-yield/ledger/report/event overwrite denials; distinguish lawful BR-17 capacity exception with permanent reason | A/E/K/Rbt | M |
| MVP-DEMO-REQ-12 | RCMA · Pack generated live | 3 OPS/REPORT/UX; 4 live | Generate from current isolated fixture snapshot, not a hard-coded export; no live-production claim | A/E/K/Rbt | M |
| MVP-DEMO-REQ-13 | Fund · Mandate fills deals as listed | 7 PLUS; 3 UX explainer | D-61 unsigned; no simulated executable mandate presented as delivered MVP | A/E/K/Rbt | DP |
| MVP-DEMO-REQ-14 | Fund · Charge ladder and band crossing | 7 PLUS; 3 UX explainer | Reject unapproved fee ladder; explain pending policy honestly, no fictitious reduced fees | A/E/K/Rbt | DP |
| MVP-DEMO-REQ-15 | Fund · Both exit routes priced | 3 SEC/UX | Only seller-chosen peer-to-peer ask and 3% seller fee; no principal route or guaranteed liquidity | A/E/K/Rbt | R |
| MVP-DEMO-REQ-16 | Fund · Concentration and realised return | 3 PORT/REPORT/UX | Business/sector exposure and ledger-backed realised results, distinct projections | A/E/K/Rbt | M |
| MVP-DEMO-REQ-17 | Performance · 3G under five seconds | 3 UX | Record first-screen interactivity on throttled 3G phone with cold-cache/build identity | A/E/Rbt | M |
| MVP-DEMO-REQ-18 | Performance · No layout shift after first paint | 3 UX | Trace layout shifts and verify reserved content/image/font/skeleton dimensions | A/E/Rbt | M |
| MVP-DEMO-REQ-19 | Performance · One-action reset | 3 CORE/UX | Authorized reset restores deterministic isolated demo book; concurrent visitors cannot reset each other's real data | A/E/K/Rbt | M |
| MVP-DEMO-REQ-20 | Performance · Visitor cannot touch real records | 3 ID/CORE/UX | Deny production credentials/data/queues/rails from demo; forged IDs and reset paths fail closed | A/E/K/Rbt | M |
| MVP-DEMO-FLOW-01 | Visitor · Arrives at Suite, roles explained | 1–3 ID/UX | Label demo and explain roles before entry; no real-data credentials required | A/E/Rbt | M |
| MVP-DEMO-FLOW-02 | Visitor · Picks Investor, Business, Auditor or staff | 1–3 ID/UX | Authorized isolated persona selection, persistent role context; never grant real staff privilege | A/E/K/Rbt | M |
| MVP-DEMO-FLOW-03 | Visitor · Sees mid-raise deals and mid-term notes | 3 ORIG/SERV/PORT/UX | Seeded non-empty landing; empty/error states still intentionally reachable for review | A/E/K/Rbt | M |
| MVP-DEMO-FLOW-04 | Visitor · Tries buy, apply, file or approve | 3 ORIG/AUD/OPS/UX | Each role's safe command mutates isolated core and produces an attributable event | A/E/K/Rbt | G |
| MVP-DEMO-FLOW-05 | Visitor · Leaves with traceable proof | 3 CORE/REPORT/UX | Complete narrated first-90-seconds route to a traceable outcome; no claim of full real-life audit/funding inside 90 seconds | A/E/K/Rbt | M |

## Other source requirements and access matrix

These rows prevent acceptance-table coverage from hiding requirements in the feature cards, journey captions, spine rules and phasing paragraphs. The field lists retain **all four fields of each of the six feature cards per role**; evidence and ownership inherit the named slices just as for AC rows. Exclusions are individually enumerated in the companion register.

| ID | PDF page / source group and all carried fields | Phase / slices | Governing treatment / extra proof | Owners | Status |
|---|---|---|---|---|---|
| MVP-SPINE-REQ-01 | 1–2 · Four apps plus launcher, one core, no duplicate state | 1 CORE/ID/UX | Shared Laravel actions/Resources; each app owns layout/navigation/view state/drafts only; deleting a client loses no authoritative data | A/E/Rbt | M |
| MVP-SPINE-REQ-02 | 2 · Core owns identities/roles, ledger/wallets, deals/notes, rating/schedules, reports, secondary book, reserve, event log | 1–3 CORE/UW/SEC/AUD | Core is sole authority; C-26 rejects reserve assumption; ordinary client draft persistence is permitted | A/E/K | G |
| MVP-SPINE-REQ-03 | 2 · Money: double entry, separation, idempotency, reversal | 1–2 CORE/MONEY | Balanced two-sided postings, client/company segregation and compensating entries; no principal-risk/reserve promise | A/E/K | M |
| MVP-SPINE-REQ-04 | 2 · Audit: actor/action/before/after/time, every state, append-only, export | 1–3 CORE/OPS/REPORT | Service/system actors attributable too; export to authorized regulator with sensitive-field controls | A/E/K | M |
| MVP-SPINE-REQ-05 | 2 · Every screen: empty, loading, error, gated; 3G and no dead ends | 1–3 UX | Generic state inheritance plus all literal screen states; skeletons, cause, retry and missing condition | A/E/Rbt | M |
| MVP-SPINE-REQ-06 | 2 · Integrations: both MoMo networks, bank rail, national ID/RDB, ICPAR | 1–3 ID/MONEY/AUD; 4 live | IR-1–IR-8/D-21/38 provider contracts and certification; RDB response must not retain prohibited tax data | A/E/K | G |
| MVP-SPINE-REQ-07 | 2 · Applies→Verifies→Rates→Funds→Oversees | 1 ORIG/AUD/UW/CORE/OPS; 2–3 finish | One shared-core chain including issued holdings, disbursement and repayment; no client-owned score or duplicate balance | A/E/K/Rbt | G |
| MVP-BUSINESS-FEAT-01 | 5 · Onboarding: lookup, signatories, documents, eligibility | 1 ID/UW | RDB prefill; D-64 governs signers, not exactly two directors; no tax-clearance field; eligibility from approved evidence, not standalone prototype margin gate | A/E/K/Rbt | G |
| MVP-BUSINESS-FEAT-02 | 5 · Application: statements, calculator, term, draft | 1 UW/UX | D-14 six-month minimum/max 24 retained; 3/6/9/12 tenors; server-derived capacity, continuously resumable permitted draft; typed preview not a substitute | A/E/K | G |
| MVP-BUSINESS-FEAT-03 | 5 · Rating: score, explanation, ceiling, refusal | 1 UW | One published 0.0–5.0 rating/four bands, approved capacity explanation, no 35% ceiling or 5M–50M bounds absent D-12 | A/E/K | G |
| MVP-BUSINESS-FEAT-04 | 5 · Raise: live fill, charge, signing, disbursement | 1 ORIG/MONEY | Pre-consent total cost/instalment/flat return in francs, retained agreement and named verified destination/receipt; no guaranteed 48-hour apply-to-fund promise | A/E/K/Rbt | G |
| MVP-BUSINESS-FEAT-05 | 5 · Monthly duties: report, CPA, late, recovery | 2 SERV/AUD/OPS | 1st–7th both filings, contact/thread, approved arrears/remedy; reporting breach freezes responsible partner yield plus holder flag, not automatic penalty/default | A/E/K | G |
| MVP-BUSINESS-FEAT-06 | 5 · Repayment: schedule, pay, early, history | 1–2 SERV/MONEY/UW | Fixed scheduled splits and receipts; early repayment preserves total return; verified conduct informs next rating under approved engine policy | A/E/K | G |
| MVP-BUSINESS-JOURNEY-01 | 5–6 · Register→apply→audit→fund→report/repay; honesty rule; two delivery waves | 1–2 ID/UW/ORIG/AUD/SERV/UX | Follow the above six cards and nine screens; no second data entry, hidden charge, amount-request underwriting or unapproved effective/annualised rate | A/E/K/Rbt | G |
| MVP-AUDITOR-FEAT-01 | 7 · Assignment: queue, accept/decline, clock, conflict | 1 AUD/ID | Registered-office 30 km + capacity/rotation/conflict checks, no nearest-first rule; 24-hour Flash; routine acceptance/decline policy D-32 | A/E/K | G |
| MVP-AUDITOR-FEAT-02 | 7 · Flash: checklist, records, stock, feeds | 1 AUD/UW | Fixed agreed procedures; approved history not five years; stock counts/value/photo and bank/MoMo/POS statement reconciliation, not automated feed pull | A/E/K | G |
| MVP-AUDITOR-FEAT-03 | 7 · Field: offline, photos, owner signature, no edits | 1–2 AUD/CORE | D-04 secure camera/geo/time package and ordered sync; authorized signature/verification seal; immutable original and linked amendment | A/E/K | G |
| MVP-AUDITOR-FEAT-04 | 7 · Monthly: cadence, comparison, flag, deadline | 2 AUD/SERV | Every funded-note month, compare source figures and flag deterioration; 7th breach applies BR-46, not automatic trading/default consequence | A/E/K | G |
| MVP-AUDITOR-FEAT-05 | 7 · Earnings: rate, visibility, floor, unpaid Flash | 2 AUD/SERV | C-23/BRS 25% of attributable collected service fees with monthly SLA payability; reject unapproved 10%, floor and unpaid Flash claims | A/E/K | R |
| MVP-AUDITOR-FEAT-06 | 7 · Standing: quality, re-performance, licence, sanction | 2 AUD/OPS | Explain quality changes; G-AUD-01 baselines the quality/sanction policy and source one-in-ten sampling target; licence expiry suspends dispatch | A/E/K | G |
| MVP-AUDITOR-JOURNEY-01 | 7–8 · Accept→prepare→capture→reconcile→file; evidence not verdict; online then offline waves | 1–2 AUD/UW/UX | Captured evidence changes engine inputs, not a partner-selected rating; D-04 companion dependency rebaselines MVP instead of deferring integrity | A/E/K | G |
| MVP-INVESTOR-FEAT-01 | 9 · Account: verify, deposit, withdraw, statements | 1–2 ID/MONEY/REPORT | National ID and segregated wallet; bank/MoMo; no extra 0.5% withdrawal fee; D-21/38 timing/limits; monthly/year exports without tax data | A/E/K | G |
| MVP-INVESTOR-FEAT-02 | 9 · Browse: card, audit, filters, sold out | 1 PORT/ORIG | Rating/yield/term/fill/time, approved disclosed evidence, sector/rating/term/size filters, sold-out next action without inventing next listing date | A/E/K/Rbt | G |
| MVP-INVESTOR-FEAT-03 | 9 · Buy: unit, costs, net ROI, limits | 1 PORT/ORIG | 5,000 minimum not necessarily universal unit; D-10/15; exclusive fees itemized; no five-note, unlimited-investment or 50% constants | A/E/K | G |
| MVP-INVESTOR-FEAT-04 | 9 · Hold: portfolio, schedule, audits, warnings | 1–2 PORT/SERV/AUD | Business/sector/maturity holdings, scheduled payouts, published monthly reports and immediate core flags | A/E/K | M |
| MVP-INVESTOR-FEAT-05 | 9 · Paid: cadence, charge, landing, idle cash | 1–2 SERV/MONEY | Monthly principal/return, 1% investor payout fee, provider-approved landing SLA; idle funds no invented fee/interest entitlement; D-17 | A/E/K | G |
| MVP-INVESTOR-FEAT-06 | 9 · Reporting: returns, concentration, losses, export | 2–3 PORT/REPORT | Realised/projected separate; show business/sector exposure and gross loss, no reserve cover/guarantee; export any permitted period | A/E/K/Rbt | M |
| MVP-INVESTOR-FEAT-07 | 10 · Exit to Rozine: own book, present value, discount, 45-day block | 3 SEC | Reject entire principal-buyback model; no order-book alternative for Arrears/Default/Disputed holdings | A/E/K/Rbt | R |
| MVP-INVESTOR-FEAT-08 | 10 · Order book: peer sale, seller control, fee, no guarantee | 3 SEC | Seller-chosen ask; edit/cancel rules D-26/27; 3% seller fee only, so not literally free; no guarantee of fill | A/E/K/Rbt | G |
| MVP-INVESTOR-FEAT-09 | 10 · Secondhand: source, price, fee, carried history | 3 SEC/PORT | Peer-owned settled eligible holding only, seller ask not Rozine PV price, no acquisition fee; history travels with note | A/E/K | R |
| MVP-INVESTOR-FEAT-10 | 10 · Plus opening: trigger, instant, holds, forward-only | 7 PLUS | D-61 unsigned; threshold/rolling window/minimum hold/prospective effect require full approved policy | A/E/K/Rbt | DP |
| MVP-INVESTOR-FEAT-11 | 10 · Plus automation: flat/percentage mandate, filters, preview, cap | 7 PLUS | D-61 unsigned; no live mandate/1–50% cap in MVP; preview and enforcement belong to approved tranche | A/E/K/Rbt | DP |
| MVP-INVESTOR-FEAT-12 | 10 · Plus ladder: 1M, 5M/25M, 75M/150M, Free | 7 PLUS | All 8%/7%/6%/5%/4.5%/10% earnings tiers quarantined; no amendment is implied by this map | A/E/K/Rbt | DP |
| MVP-INVESTOR-JOURNEY-01 | 9–11 · Verify→deposit→choose→fund→collect; no silent maths; three waves | 1–3 ID/PORT/ORIG/SERV/SEC | 10–15% flat not 10–20%/annualised; disclosed inputs, labelled projections; no “any time exit” promise; Plus requires D-61 disposition | A/E/K/Rbt | G |
| MVP-ADMIN-FEAT-01 | 12 · Queues: applications, disbursements, exits, escalations | 1–3 OPS/ORIG/SEC | Evidence-based decisions, approved dual control; exit queue monitors peer orders not Rozine pool capacity; G-OPS-01 | A/E/K | G |
| MVP-ADMIN-FEAT-02 | 12 · Money: reconcile, breaks, reserve, contra | 2 OPS/MONEY/CORE | Daily bank/MoMo/wallet reconciliation; age and assign breaks; no 5% reserve floor; compensating correction and approval | A/E/K | R |
| MVP-ADMIN-FEAT-03 | 12 · Book: arrears, six halts, concentration, recovery | 2–3 OPS/SERV/SEC | Approved trigger/policy registry, not six unapproved hard-coded thresholds; arrears ageing, exposure and reasoned recovery | A/E/K | G |
| MVP-ADMIN-FEAT-04 | 12 · Partners: coverage, quality, licence, payouts | 1–2 OPS/AUD/SERV | Radius/capacity/rotation, quality/variance/sanction basis, ICPAR validity and 25% attributable fee share | A/E/K | G |
| MVP-ADMIN-FEAT-05 | 12 · Parties/access: parties, freeze, roles, KYC | 1–2 ID/OPS | Business/Investor/Partner/staff, attributed reversible lawful restrictions and permission grants/revokes, reverification; supervisor retained | A/E/K | G |
| MVP-ADMIN-FEAT-06 | 12 · Reporting: regulator, board, event log, export | 1–3 OPS/REPORT | On-demand/monthly self-describing same-source packs, actor/record/date search, permitted table/period export | A/E/K | M |
| MVP-ADMIN-JOURNEY-01 | 12–13 · Reconcile→queues→exceptions→partners→report; can/cannot rules; two waves | 1–3 OPS/MONEY/SERV/AUD/REPORT | Logged money/control changes only; recovery-plan waivers require policy, never automatic. No rating/live-yield/ledger/report/event overwrite; client-fund movements named and reasoned | A/E/K | G |
| MVP-BUILD-REQ-01 | 4 · Wave 1: chain once, core/ledger/roles/event log, no secondary yet | 1 CORE/ORIG/AUD/UW/SERV | MVP ALPHA only; secondary-ready Holding/Order contracts still mandatory in Phase 1 | A/E/K/Rbt | G |
| MVP-BUILD-REQ-02 | 4 · Wave 2: all states, monthly reports, freezes, recovery, Admin reconciliation | 2 SERV/AUD/OPS/UX | Every applicable source/generic state with permitted next step; BRS controls replace conflicting penalties/reserve | A/E/K/Rbt | G |
| MVP-BUILD-REQ-03 | 4 · Wave 3: secondary, Plus, charts/reports, 3G/accessibility, demo | 3 SEC/PORT/REPORT/UX; 7 PLUS pending | D-60 peer-to-peer mandatory; D-61 must be signed or Plus rebaselined into Phase 3 after policy approval | A/E/K/Rbt | G |

### Page 4 permissions — core-enforced, not UI-only

Staff/Admin labels in the PDF are coarse. The BRS permission matrix, D-05 identity rules and the action/dual-control policy that must be baselined under G-OPS-01 govern their exact role assignments. Each row requires AT-ID/AT-OPS endpoint denial tests as well as WT-ID/WT-OPS role walkthroughs.

| ID | Source action | Phase / slices | Required disposition | Owners | Status |
|---|---|---|---|---|---|
| MVP-ACCESS-01 | Apply for a raise | 1 ID/ORIG | Authorized Business actor only, mandate/ownership checked in core; D-64 | A/E/K | G |
| MVP-ACCESS-02 | File audit evidence | 1–2 ID/AUD | Assigned active eligible Auditor only; Business supplies its own distinct monthly evidence, never Auditor seal | A/E/K | M |
| MVP-ACCESS-03 | Set a rating or yield | 1 ID/UW/OPS | Nobody directly sets rating or edits live-note yield; future policy changes are versioned governance, not a bypass | A/E/K | M |
| MVP-ACCESS-04 | Buy or sell a note | 1 ORIG/ID; 3 SEC | Eligible Investor with core ownership, related-party, limit and lifecycle denials | A/E/K | G |
| MVP-ACCESS-05 | Disburse a funded raise | 1 OPS/MONEY | Staff request / independently authorized approve, G-OPS-01 matrix and conditions precedent | A/E/K | G |
| MVP-ACCESS-06 | Freeze a note or party | 2 OPS/SERV; 3 SEC | Auditor flags; scoped staff request; authorized Admin action with reason; separate market-halt permission | A/E/K | G |
| MVP-ACCESS-07 | Change a fee or rate | 1 OPS/UW | Versioned approved future policy only; exclusive BRS fee/tenor/return constraints persist unless formally amended | A/E/K/Rbt | G |
| MVP-ACCESS-08 | Read the event log | 1–3 ID/OPS/REPORT | Own/assigned/scoped/all only as authorized; supervisory read-only scope retained; sensitive fields redacted | A/E/K | G |
| MVP-ACCESS-09 | Delete anything | 1–2 CORE/AUD/OPS | No ledger/event/published-report delete or overwrite; ordinary mutable records and lawful PII retention are separately governed, not globally prohibited | A/E/K | G |

## Deliverables, verification and acceptance boundary

- [x] The original source is archived and hash-identified; every page was visually reviewed.
- [x] 46 criteria, 38 role screens plus launcher, 92 literal named states, generic-state ID/applicability rules, 24 launcher/demo fields and five visitor steps are enumerated with delivery/evidence ownership.
- [x] Source feature cards, journeys, build waves and all nine permission rows have owned destinations; the secondary path has nine explicit checkpoints.
- [x] Every exclusion and former-plan post-MVP destination is preserved in the linked deferred register; source conflicts are visible instead of copied into policy.
- [ ] Non-author document review confirms coverage and accepts each BRS-safe wording adaptation; this authoring record is not that approval.
- [ ] Appendix A and all slice-specific red policy/provider/assurance gates close before their governed implementation/release boundary.
- [ ] Actual automated reports and witnessed artifacts replace evidence requirements per ID on exact release commits.
- [ ] D-61 is signed before Plus rows become `DEFERRED — NOT MVP-APPLICABLE`; until then RC scope remains open.

This document completes **mapping**, not Phase 0, implementation, acceptance, independent review, regulatory authorization, provider certification or production readiness. New requirements append new IDs; retired/rejected IDs remain with rationale and supersession links. Never reuse an ID to hide a scope change.
