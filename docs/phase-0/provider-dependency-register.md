# Phase 0 provider and external-dependency register

## Status

`PREPARED 2026-09-10 — TRACKS NOT YET STARTED OR CERTIFIED`

This is the owned preparation pack for the Phase 0 external tracks. It is not outreach, a vendor selection, contract, credential receipt, regulatory/legal opinion or authorization to contact anyone. Null evidence means **not recorded/verified in this register**, not proof that the organization has no existing account or relationship. Reconcile existing arrangements with each owner before selecting or contacting a replacement.

Authority: [BRS](../Rozine-BRS.md) IR-1–IR-8, CR-1–CR-13 and cited supporting requirements; [plan](../Rozine_Phased_Implementation_Plan.md) D-04/D-20–D-43/D-46/D-48/D-49/D-71; [MVP crosswalk](mvp-crosswalk.md); [source authority](source-authority-record.md). The [machine-readable register](delivery-readiness.json) holds each track's requested inputs, fake cases, required exit evidence, dates and null external evidence.

## Goal

Make every external dependency actionable without inventing a selected provider, approved data contract, financial limit or service promise. Preserve deterministic local surrogates for pre-production engineering and separate actual provider/regulatory certification for Phase 4.

## Ownership and proposed review dates

Named roles come from D-71/D-74. **Track allocations and dates below are proposed** until the named person acknowledges them. Kimani owns Finance/Risk, Audit Operations and Compliance; Robert owns Product/Business and internal Legal; Aminu/Erastus own Engineering/Security. An internal owner does not replace external counsel, CMA, ICPAR, provider certification or an eligible non-author independent reviewer.

All tracks have a proposed first review of **14 September 2026** to confirm owner, existing relationships, required decision IDs and the next action. This is a requested review date, not a promised vendor response or a scheduled reminder. No contact has been sent. Once acknowledged, capture the actual next due date and escalation owner; do not silently turn `PREPARED_NOT_STARTED` into `STARTED`.

| ID | Track / governing requirement | Accountable lead / engineering | First decision or evidence needed | First live gate |
|---|---|---|---|---|
| DEP-01 | Identity/KYC · IR-3, CR-1 | Kimani / Aminu | Permitted identity/match/expiry fields and KYC-tier/mandate mapping; D-05/15/38/40/64 | Selected contracted route, privacy clearance and certified outcome mapping |
| DEP-02 | Registry/KYB · IR-4, FR-200, DR-8 | Kimani / Aminu | Registration/ownership/mandate authority and source refresh; D-38/40/64 | Verified source contract and tests proving prohibited TIN/tax data is not retained |
| DEP-03 | Sanctions/PEP/AML · CR-2/3 | Kimani / Aminu | Source, match/escalation rules and case/release authority; D-15/38/39/40 | Approved screening/monitoring policy, certified cases and attributed restrictions |
| DEP-04 | ICPAR · IR-5, FR-300, CR-11 | Kimani / Aminu | Authoritative API/file/manual route and refresh SLA; D-29/30/33/38 | ICPAR-authorized evidence, active standing and dispatch-denial proof |
| DEP-05 | Parsing/OCR · FR-201/306 | Kimani / Aminu | Authorized corpus, processing location, formats, confidence and correction lineage; D-14/38/40 | Licensed/approved source corpus and proven parser lineage; no automatic policy activation |
| DEP-06 | Bank/custody · IR-2, CR-4 | Kimani / Aminu | Account topology, operation ownership, cutoffs/reversals/reconciliation; D-17/20/21/22/23/38/39 | Signed custody/rail contracts, separated accounts and certified settlement/reversal evidence |
| DEP-07 | MoMo, both networks · IR-1, FR-211 | Kimani / Aminu | Separate network/operation coverage, callbacks/limits/cutoffs; D-20/21/22/23/38 | Evidence for each network and enabled operation; no uncontracted same-day promise |
| DEP-08 | SMS/email/push · IR-6 | Robert / Aminu; Erastus client delivery | Required/optional notices, language, consent, sender custody and fallback; D-38/40/46/48/49 | Approved templates/rules, delivery/fallback proof and redacted logs |
| DEP-09 | Storage/backup/key custody · NFR-3/12/14, DR-7 | Aminu / Aminu; Erastus cross-review | Residency, retention/holds, recovery objectives, separate resources and key ownership; D-35/36/38/40/42 | Negative cross-environment access, backup restore, rotation/revocation and retention proof |
| DEP-10 | Maps/geocoding · IR-7, BR-51, FR-304 | Kimani / Aminu | Registered-office/premises coordinate source, accuracy and boundary rules; D-31/32/37/38/40 | Approved distance contract; live location is not the dispatch-coordinate authority |
| DEP-11 | External legal clearance · CR-5/6/8/9/10/11 | Robert / Aminu | Named counsel and exact Note/custody/secondary/disclosure/privacy/AUP scope; D-20/21/26/27/28/35/39/40 | External written clearance for exact document versions, plus separate internal approvals |
| DEP-12 | CMA/sandbox/supervisory returns · IR-8, CR-12/13 | Kimani / Aminu; Robert internal Legal | Supervisory route, pilot conditions, return schema/channel/acknowledgement; D-20/39/41/43 | Written scope/conditions and reporting acceptance before a real-money pilot |
| DEP-13 | Native integrity/signing/distribution · FR-304/311, NFR-4 | Erastus / Aminu | Organization-owned app identities/accounts, device/verdict policy and distribution; D-04/35/36/37/38 | Actual signed builds, server-verifiable assertions and device/key-loss evidence |

All eight BRS integration IDs are covered. DEP-03/05/09/11/13 retain the additional compliance, parsing, storage, legal and native dependencies rather than dropping them because they are outside IR-1–IR-8.

## Per-track kickoff checklist

1. Confirm the accountable lead and a next review date. Find existing accounts/contracts through authorized owners first. Record organization/account identifiers only where permitted; never paste secrets into this repository.
2. Record the approved purpose, data allowlist/prohibited fields, residency/retention, authorized actors, source authority and exact decision gates. Unresolved fields remain gated; a provider sample cannot define policy by accident.
3. Obtain a proposed contract: operations, request/response/error/event schemas, authentication/signatures, rate/size limits, timeouts, retries, idempotency, callback ordering, reconciliation identifiers and corrections/reversals. Mark “not applicable” with a reason for non-API legal/regulatory tracks.
4. Specify synthetic positive, failure, stale, duplicate/reordered and revoked cases as relevant. The JSON lists the minimum domain-specific cases. Legal/regulatory fakes simulate an **absent/present test artifact**, not an actual clearance or authorization.
5. Establish separate environment accounts, keys, data and endpoint allowlists, plus credential rotation/revocation and emergency shutdown. A copied production key with a `sandbox` label is forbidden.
6. Before actual sandbox access, obtain the applicable authorized account/contract and an approved adapter mode. Current demo/UAT safeguards intentionally disable remote providers; this register does not loosen them. Keep deterministic tests offline.
7. Archive actual sandbox reports and independently reviewed failure/recovery evidence for the exact implementation/version. Before live use, archive the distinct provider certification, internal sign-offs and applicable external clearance/conditions. Keep timestamps, artifact hashes, scope and expiry; secrets stay outside evidence files.

## State model and closure evidence

| State | Minimum evidence | Does not mean |
|---|---|---|
| `PREPARED_NOT_STARTED` — every current row | Owned draft, requested inputs, fake-case specification and proposed review date | Owner acknowledgement, contact, contract or implementation |
| `STARTED` — future | Named owner acknowledgement plus an actual dated kickoff/contact or authorized internal intake record | Vendor selected, sandbox granted or business policy approved |
| `CONTRACT_REVIEW` — future | Candidate contract/data flow and explicit unresolved decisions | Permission to put real participant data in a sandbox |
| `SANDBOX_READY` — future | Approved test scope, segregated credentials/resources and reviewed adapter/fake contracts | Provider certification or live money permission |
| `CERTIFIED_FOR_SCOPE` — future | Provider/external evidence plus internal and independent approvals for exact scope/version | Permission for a different operation, network or product version |
| `LIVE_APPROVED` — future | Applicable Phase 4 go/no-go, legal/regulatory/operational gates and exact-SHA deployment proof | A guarantee of investment performance or blanket regulatory endorsement |

An expired, revoked or scope-mismatched evidence item removes readiness for its dependent action. “Provider unavailable” is a failure/retry state, never fake success. Financial outcomes remain server-authoritative and reconciled. No provider callback may overwrite ledger/history, decide a rating, or approve an otherwise forbidden action.

## Deliverables and checklist

- [x] Prepare all 13 tracks with required inputs, proposed named leads/review dates, fake-case specifications, decision links and required exit artifacts.
- [x] Cover IR-1–IR-8 and retain parsing, sanctions/AML, storage, legal and native integrity tracks.
- [x] Keep contact, selection, sandbox, certification and live-approval evidence null; distinguish this preparation from actual kickoff.
- [ ] Named leads acknowledge allocations/dates and reconcile existing arrangements.
- [ ] The authorized owners perform and record actual kickoff/outreach; this is what closes the plan's “start tracks” requirement.
- [ ] Contract, implementation, sandbox, certification and Phase 4 gates close with their own evidence.

## Acceptance criteria

The preparation artifact passes when coverage/IDs/owners/dates/fake cases reconcile and no approval is invented. The Phase 0 “start tracks” checkbox stays open until actual startup evidence exists. The earlier plan's provider/regulatory calendar allowances remain indicative and unverified; this register is not a current vendor quotation or legal/regulatory assessment.
