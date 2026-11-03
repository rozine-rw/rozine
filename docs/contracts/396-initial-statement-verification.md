# #396 — Business-reviewed transcription and initial independent verification

Proposed contract version: `initial-statement-audit-1` · 2026-10-28 · server owner: Aminu / Codex.

Documentation only: this PR changes no runtime behavior, permissions, settings, live records or financial activation. The contract was independently reviewed against `rozine-rw/rozine@52a68e22c028060448efaf7c45e8c3372bc7a9de`, tree `1351a5033335fa5b90b38d26704b67353685bc64`. Unless stated otherwise, source paths and line references below refer to that pinned revision.

Publication refresh, 2026-10-29: base `dev` is `e8af42fbf824bc44239bd7d4bc86a406b0667586`. The inspected delta includes #359 refusal copy/tests, local app-entry/assets and the #432 staff-permission refactor. The latter resolves role-backed current staff grants through the existing authorization boundary; `audit.assignments.manage`, current identity/mandate checks and the relevant assignment/statement contracts remain applicable. These source reads do not certify the refactor or runtime behavior. No new product policy was adopted. [Erastus confirmed UI ownership](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6502083103); the proposal register below remains unresolved.

## 0. Decision status: approved direction versus proposals

Approved: Business representatives save using existing `application.save`; authorized staff request initial audit under `audit.assignments.manage`; an eligible accepted Audit Partner independently verifies exact source/transcription evidence; no new grants, impersonation, eligibility shortcut or removal of identity/mandate checks. Existing financial, professional and source-integrity rules remain mandatory. New actions, routes, payloads and hash bindings below are proposed engineering contracts for review.

| Proposal | Explicit unresolved choice |
|---|---|
| P396-01 | Fixing the new initial wrapper to `flash` is proposed workflow scope. The existing general request accepts a caller-supplied kind; this document does not establish Flash-only product approval. |
| P396-02 | Requiring a current source-linked transcription before the new initial wrapper is proposed readiness ordering. The existing general assignment action does not impose this prerequisite; do not silently alter it or block necessary preparatory audit work. |
| P396-03 | Allowing factual review of partial contiguous history before the full underwriting window exists is proposed audit scope. It never reduces the existing 36/12-month underwriting requirement or produces eligibility. Decide the supported review scope before enabling the new wrapper. |
| P396-04 | Nullable assistance with manual original-linked review is a proposed first delivery when structured conversion is unavailable. Structured-parser capability/scope remains unresolved; do not describe manual-only input as delivered automated transcription. |
| P396-05 | Staff readiness returns metadata only under existing manage authority; that proposed view is not authorization for staff originals, OCR text or transcription-content access. |

Examples, flow diagrams and acceptance scenarios using these choices are conditional on resolution. Do not turn missing requirements/capabilities into defaults. No further authority or underwriting-policy change is proposed; these workflow/delivery choices remain open; UI ownership is confirmed separately.

## 1. Approved actor split and ownership

[Owner direction 6466803167](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6466803167) supersedes the earlier recommendation-only hold. An authorized Business representative reviews and saves source-linked assisted transcription using existing **`application.save`** authority. Existing authorized staff request the initial audit using **`audit.assignments.manage`**. An eligible, accepted Audit Partner independently verifies exact source/transcription revisions and hashes. Upload, extraction/OCR and transcription alone never unlock underwriting eligibility.

[Peer proposal 6466718307](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6466718307) claims the operator/Audit Partner UI lane but proposes new staff `statements.transcribe` and `businesses.verify` assignment authority. **Those grants are rejected by the owner direction and are not part of this contract.** Staff must not save in a Business actor's name or impersonate a representative. The retained [#375 seam](https://github.com/rozine-rw/rozine/issues/375#issuecomment-6414343478) keeps organization verification/mandates with their existing authority. Membership/access gaps stay explicit; this contract grants no role or permission.

Server contract work belongs to Aminu/Codex. [Erastus confirmed Business transcription, staff assignment and Audit Partner UI ownership](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6502083103). This confirms the UI lane, not acceptance of every proposal below. No UI work or second QA lane starts in this documentation PR. Upload/name/dedup fixes described in #396 are already delivered according to issue discussion and present source; this work defines the remaining connection to initial audit.

## 2. Boundary already present in source

- `RecordStatementTranscription` → `StatementStore::recordTranscription` → `EloquentStatementStore` uses `WithBusinessAuthority(..., 'application.save', ...)`. It reconciles declared rails/months/transactions, retains original source hashes and an immutable amendment, and returns `STATEMENT_RECONCILED_UNVERIFIED` with `verified:false`.
- `RequestAuditAssignment` → `EloquentAuditAssignmentStore::request` already checks `audit.assignments.manage` and dispatches its caller-supplied `kind` through existing state/selection machinery; it can request `flash`. There is no underwriting-eligible prerequisite in that action. Flash-only applies to proposed P396-01, not to the existing general method.
- `BusinessAuthorityStore::withAudit(..., requireVerified:true)` means verified organization/person identity and a current mandate. `EloquentIdentityAccessStore::withAuditAccess` checks verified Parties and eligible Auditor membership. It does **not** mean already-verified statements, an application quote, capacity, or an existing raise. Do not weaken that `true` flag to bypass an imagined circular financial gate.
- `RecordStatementVerification` already requires `WithAcceptedAuditAssignment`, expected assignment/evidence/verification revisions, current transcription ID/hash, source-integrity checks and `StatementAuditReview` factual procedure. It appends encrypted verification evidence and returns `report_approval:not_cosigned`.
- `EloquentBusinessApplicationStore` reads only current verified evidence, combines obligations/current credit facts and the selected observation window, then evaluates the existing underwriting rules. It never treats a transcription as a credit approval.

The first-audit gap is a supported readiness/request/transport seam and review workflow, with explicit revision binding. It is not a reason to create staff transcription rights or remove identity/mandate checks.

## 3. Authority matrix

| Actor / operation | Required current authority | What it may do |
|---|---|---|
| Business viewer | Active `business` context, verified applicable entity/person identities, effective mandate, `business.view` | View existing manifest and readiness summary within existing data visibility |
| Business representative saving/reviewing transcription | Same identity/mandate checks plus mandate **`application.save`**, current context and expected mandate version | Read own originals/extraction/transcription; explicitly review, correct and save an unverified source-linked amendment |
| Staff requester | Dedicated enabled staff identity, verified email/MFA and existing **`audit.assignments.manage`** held through effect | See scoped initial-audit readiness metadata, request/resume dispatch, use existing operations resolution within that permission |
| Audit Partner offered a job | Current `auditor` context, own offered assignment and existing candidate eligibility | Minimal case summary; accept, decline or declare conflict. Offer alone grants no original-statement access |
| Audit Partner verifying evidence | Current `auditor` context, **own accepted** assignment, current accreditation/engagement acceptance/independence and existing dispatch/identity gates | Read scoped evidence/transcription, independently review, append factual verification |
| Scheduler | Existing trusted internal assignment/expiry workflow | Advance due offers under existing rules; no request-selected actor or access grant |

The staff reader introduced here returns revision/hash/status/count/assignment metadata only. It does not grant raw statement or transcription-content access to staff. Existing permissions elsewhere remain unchanged. No new token ability: mirror existing Business `business:read`/`business:command`, Auditor `auditor:read`/`auditor:command`, and staff audit `staff:audit:read`/`staff:audit:manage` transport conventions, then apply the domain permission. GET/lookup requires the role's read ability. Business commands require both `business:read` and `business:command`; Auditor commands use existing `auditor:command`; staff requests require both `staff:audit:read` and `staff:audit:manage`, as existing staff resolution adapters do. A bearer ability alone never authorizes a Business, Party or assignment. Web uses existing authentication/CSRF; all private data and operation routes are no-store and throttled.

## 4. Canonical types and provenance

IDs: lowercase ULID; request IDs: UUID; revisions: integers ≥0; hash: 64 lowercase hex; times: UTC ISO timestamps; months: `YYYY-MM`; transaction dates: real `YYYY-MM-DD` within the stated month. Exact money uses existing whole-RWF decimal strings (up to the existing 64-digit domain limit); only transaction amounts may be signed. No floats or UI-calculated totals. Keep `StatementReconciliation::VERSION = statement-classification-2`, `StatementAuditReview::PROCEDURE = MVP-AUP-1`, policy `engineering-2026-09-23.4` until a separately versioned policy changes them.

```text
Rail = {id:string, active_from:month, active_until:month|null}
DrawRef = {rail_id:string, month:month, reference:string}
Transaction = {
  reference:string, date:date, amount:signedDecimalString,
  classification: operating_inflow|operating_outflow|financing|transfer|owner_draw|owner_return|debt_service,
  source_ids:ulid[], return_of:DrawRef|null, transfer_of:DrawRef|null, exception_id:ulid|null
}
Statement = {
  rail_id:string, month:month, opening_balance:decimalString, closing_balance:decimalString,
  source_ids:ulid[], transactions:Transaction[]
}
SourceBinding = {
  evidence_revision:integer, manifest_sha256:sha256,
  originals:[{document_id:ulid, sha256:sha256}],
  extractions:[{document_id:ulid, extraction_id:ulid, revision:integer, parser_version:string}]
}
Transcription = {
  id:ulid, revision:integer, amends_id:ulid|null, sha256:sha256,
  current:boolean, verified:false,
  payload:{business_id,source_revision,classification_version,rails,months,statements,
           source_hashes:{documentId:sha256},observations:unverifiedObservation[]}
}
```

The manifest hash is over versioned canonical **document identity/hash/media type/size and evidence revision**, ordered by document ID, excluding filenames, presentation URLs and extraction status. Extraction suggestions have their own binding above. The server recomputes it; supplied hashes are optimistic preconditions, never proof of authenticity. `StatementOriginal` bytes are rehashed when read/verified. Immutable transcription hash is existing `CanonicalJson` hash of its payload. Do not mix the business evidence revision, transcription evidence revision, assignment revision or independent verification revision.

Existing `StatementReconciliation` rules stay authoritative: full declared rail inventory over the chosen contiguous months (at most 36), every active rail/month, no conflicting duplicates, exact balances/continuity/boundaries, symmetric cross-rail transfers, identified owner returns and separately authorized draw exceptions. A caller cannot use an `exception_id` or boolean to create exception authority. No typed monthly revenue-only substitute; every statement/transaction references originals belonging to this Business. Financial classification is a factual assertion until independent verification.

## 5. Business assistance and save operations

All route names below are **proposed transport additions**, not existing registered endpoints. API versions prepend `/api/v1`; web uses existing role prefixes. Keep controllers thin, Form Requests validated and Resources authoritative. Existing list/upload/download routes remain the entry to originals.

| Application action / proposed route | Input | Response |
|---|---|---|
| `GetStatementTranscriptionDraft` · GET `/business/{business}/statements/transcription-draft` | current identity context | `{contract_version,business_id,business_revision,mandate_version,evidence:SourceBinding,assistance:{status,reason_codes,candidate|null},current_transcription:Transcription|null,allowed_actions}` |
| `GetStatementTranscription` · GET `/business/{business}/statements/transcription` | context, optional own transcription ID | Exact authorized retained transcription; historical record explicitly `current:false` |
| `RecordStatementTranscription` with reviewed-input preconditions · POST `/business/{business}/statements/transcription` | request below | Existing journal code `STATEMENT_RECONCILED_UNVERIFIED`; evidence revision increases; immutable amendment; no verification or financial eligibility change |
| `FindStatementOperation` · GET `/business/{business}/statement-operations/{request_id}` | context, `command=reconcile` (or existing `ingest`) | Own exact target-bound receipt; current authority required even on replay |

Assistance is a **proposed draft-suggestion interface (P396-04)**, derived from existing isolated extraction output and the Business's current transcription. It may be `available`, `needs_review`, `pending` or `unavailable`, with reason codes and field-level provenance (`document_id`, page/row/reference, extraction ID/revision). The candidate is nullable: current extraction retains text/status, not necessarily a structured rail/transaction conversion. Do not invent balances, missing months, rails or confidence scores. Manual transcription against originals is a proposed fallback, not proof that automated assisted conversion exists. No external OCR/AI service, background provider credentials or new extraction capability is authorized by this design. Suggestions are never saved automatically.

```text
SaveReviewedTranscriptionRequest = {
  request_id:uuid, identity_context_revision:integer,
  expected_business_revision:integer, expected_mandate_version:integer,
  expected_evidence_revision:integer,
  expected_transcription_id:ulid|null, expected_transcription_sha256:sha256|null,
  source_binding:SourceBinding,
  rails:Rail[], months:month[], statements:Statement[],
  reviewed_against_originals:true
}
```

The final boolean records the representative's explicit review acknowledgement; the server must also verify all source bindings and reconcile the full structured input. It never grants independent verification. Bind all preconditions and review acknowledgement in the **same `statement.reconcile` journal fingerprint/effect**; do not add an outer wrapper that can re-read/re-authorize then race before the existing store runs. Expected transcription pair is null only when none exists, and must match the current latest record otherwise. Route Business is canonical; a body Business ID may only match it.

Success `data.transcription = {id,sha256,amends_id,verified:false}` and envelope `revision` are the existing minimal receipt. Read the authorized projection for full payload; do not put raw transactions, original filenames, OCR text or review findings into a journal/notification. New response `data.next` may identify readiness GET; it must not assert `eligible:true`.

Saving against a changed evidence manifest, mandate, business or reviewed extraction revision yields 409 conflict, no amendment. Reload and explicitly review again with a new request ID. A later parser run **does not mutate a saved transcription or invalidate a completed verification solely because extraction metadata advanced**; retained source provenance remains as reviewed. Extraction revisions are pinned while saving suggestions; original/transcription integrity and evidence revision are the governing verification preconditions.

## 6. First-business readiness and initial request

New read seam: `GetInitialStatementAuditReadiness(actor, business)` under the matrix above. The Business sees permitted progress/actions; staff with `audit.assignments.manage` sees only the metadata needed to dispatch. No general staff-original download is added.

```text
Readiness = {
  contract_version:"initial-statement-audit-1", business_id:ulid,
  business_revision:integer, mandate_version:integer, evidence_revision:integer,
  manifest_sha256:sha256,
  transcription:{id:ulid,revision:integer,sha256:sha256,current:true}|null,
  ready_to_request:boolean,
  reasons:[{code:string,field:string|null}],
  coverage:{declared_month_count:integer,first_month:month|null,last_month:month|null,
            required_underwriting_months:integer,complete_for_underwriting:boolean},
  assignment:{id:ulid,revision:integer,kind:string,status:string}|null,
  source_verified:boolean,
  underwriting:{status:"not_evaluated"|"pending"|"available",eligible:boolean|null},
  readiness_sha256:sha256, allowed_actions:string[]
}
```

**Proposed readiness P396-02/P396-03:** `ready_to_request` checks a verified Business/entity and applicable people, effective mandate, retained originals and a current valid source-linked transcription; it may report partial coverage and permit factual review of that declared contiguous window. The transcription prerequisite and partial-history review scope are unresolved workflow choices, not new existing-source gates or settled product policy. Approved non-circularity remains mandatory: initial audit must not require prior verified statements, underwriting eligibility, capacity, a quote, prior notes, or a sealed/published report. Partial or unverified history never grants eligibility. Existing 36-month first-issue / 12-month qualified-repeat selection remains `UnderwritingObservationWindow`; repeat status comes from authenticated credit history, never a request flag. Do not create a history/coverage waiver.

No partner availability means dispatch can enter existing `operations` state; it is not a reason to broaden radius/conflict rules or falsely claim assignment acceptance. An existing live engagement is resumable, not duplicated. A closed engagement remains closed under existing rules. Readiness status and `allowed_actions` are advisory; recheck under locks on command.

`readiness_sha256` hashes a canonical versioned object of Business/mandate revisions, evidence manifest binding and current transcription ID/hash, **excluding** dynamic UI text, clock and partner availability. It is an optimistic reviewed-input binding, not a credential. New sources/transcription/mandate require refresh; partner selection is always current under dispatch locks.

| Action / proposed route | Request | Result |
|---|---|---|
| `GetInitialStatementAuditReadiness` · GET `/admin/businesses/{business}/initial-statement-audit` | authenticated staff | Metadata above; no raw originals/transactions |
| `RequestInitialStatementAudit` · POST `/admin/businesses/{business}/initial-statement-audit` | `{request_id,expected_business_revision,expected_mandate_version,expected_evidence_revision,transcription_id,transcription_sha256,readiness_sha256,reason}` | Existing `AUDIT_ASSIGNMENT_REQUESTED` / `AUDIT_ASSIGNMENT_RESUMED`, minimal `assignment_id`/revision plus safe next/status projection |
| `FindInitialStatementAuditOperation` · GET `/admin/businesses/{business}/initial-statement-audit/operations/{request_id}` | staff with current manage/read domain authority | Same staff/request/Business-target-bound receipt |

**Proposed P396-01:** the new operation fixes `kind=flash`; resolve that scope before implementation rather than inferring it from existing source. The proposed request takes no chosen Auditor ID and no `eligible`/`verified` flag, and uses command **`audit.initial.request`** in `staff:<actorId>` scope. Reuse the existing request/dispatch **effect** inside one parent journal, not a separately journaled `RequestAuditAssignment::handle` whose smaller fingerprint omits readiness binding. Existing general request remains compatible. Never repurpose `businesses.verify` or add staff `statements.transcribe`.

For a new command under the selected workflow, lock the Business/current authority, compare every expected binding and recheck the agreed readiness preconditions; select using existing `AuditorDispatch`; create an assignment of the agreed kind or return the existing open engagement. Preserve original dispatch/attempt clocks on resume. Expose an existing engagement's actual `kind`/status rather than relabel it Flash. Verification needs its own accepted current assignment. Do not retarget or reopen a closed engagement. Retain the evidence/transcription reviewed at request time; acceptance/verification re-read current revisions and require review of replacements rather than treating dispatch as an evidence freeze.

Business readiness can display `waiting_for_staff` and the existing assignment status. No new automatic staff notification or Business-triggered privileged dispatch is implied. The UI provides a truthful next step without claiming staff ownership or timing that has not been accepted.

## 7. Partner acceptance and independent verification

Reuse existing `RespondToAuditAssignment` HTTP accept/decline/conflict routes and their payloads (`request_id`, identity context, expected assignment revision, decision-specific reason/conflict fields). Current selection must preserve accreditation, professional engagement acceptance, availability, workload, rotation, verified locations/radius, conflict declarations and historical/current mandate ties. The Business representative who prepared transcription cannot verify it through another role. Current Auditor membership is exclusive in source, and independence must remain enforced. Staff request does not accept on behalf of a partner.

After acceptance:

| Action / proposed route | Input / result |
|---|---|
| Existing `GetAuditStatements` / `GetAuditTranscription` · GET `/auditor/jobs/{assignment}/statement-evidence` | `{assignment:AcceptedAssignment,evidence:Manifest,transcription:Transcription|null}` plus verification metadata; only own accepted assignment |
| Existing `ReadAuditStatement` · GET `/auditor/jobs/{assignment}/statement-evidence/{document}` | Integrity-checked own Business original; generated filename per existing Auditor privacy policy |
| `RecordStatementVerification` · POST `/auditor/jobs/{assignment}/statement-verification` | verification request below; immutable factual snapshot, not a credit decision |
| `FindStatementVerificationOperation` · GET `/auditor/jobs/{assignment}/statement-verification/operations/{request_id}` | current own accepted-assignment authority and exact target binding; retained `statement.verify` receipt |

```text
VerifyStatementRequest = {
  request_id:uuid, identity_context_revision:integer,
  expected_assignment_revision:integer, expected_evidence_revision:integer,
  expected_verification_revision:integer,
  transcription_id:ulid, transcription_sha256:sha256,
  review:{
    procedure_version:"MVP-AUP-1",
    checks:{complete_rail_inventory:true,originals_authentic:true,
            balances_and_transactions:true,duplicates_and_classification:true,
            debt_schedules:true,baseline_and_gaps:true,draws_returns_and_exceptions:true},
    source_checks:{documentId:{sha256:sha256,reference:nonemptyText}},
    inventory_reference:nonemptyText,
    obligations:[{id:nonemptyText,principal:decimalString,
                  service_by_month:{month:decimalString},source_ids:ulid[]}],
    recurring_owner_draw:decimalString, owner_draw_reference:nonemptyText,
    findings:nonemptyText
  }
}
```

Use the existing strict exact-key factual review. All seven check values must be true and every retained manifest original must have a matching `source_checks` hash/reference, including obligation evidence not referenced by a transaction. References/findings follow existing UTF-8/control-character rules and ≤2000-character bound. An empty obligation list is an explicit factual no-obligation assertion supported by inventory/debt review, never an inferred default from missing evidence. All verified observations are complete prior months in Africa/Kigali; current/future partial months refuse. Uploading a filename or OCR success is not source authenticity.

Within current accepted-assignment and evidence locks, reload original bytes/hashes, authenticate transcription payload digest, current evidence/transcription revision, reconciled observations, review source hashes, and assignment/mandate/engagement/independence/accreditation bindings. Reconcile again; server calculates observations. Verification request never includes accepted monthly totals, DSCR, capacity, rating, `approved`, `eligible`, `report_approval` or signatory assertions.

Success is the existing `STATEMENT_SOURCE_VERIFIED`, envelope revision = new verification revision, `data = {verification_id,sha256,source_revision,report_approval:"not_cosigned"}`. Append `amends_id` to previous verification; retain exact evidence, transcription ID/hash, all source hashes/extraction provenance, accepted assignment revision, Business revision, mandate hash/version, engagement acceptance, independence/accreditation provenance, reviewer and factual procedure. Never overwrite prior attestations or promote the transcription's own `verified:false` field to true.

## 8. States, currentness and eligibility

```text
original retained -> extraction pending/ready/needs_review (suggestions only)
                  -> representative review/save -> transcription current + UNVERIFIED
                  -> staff request -> offered / operations -> accepted partner
                  -> independent factual review -> current verification snapshot
                  -> existing underwriting evaluation -> eligible OR reasoned refusal
```

These are independent axes, not one `approved` flag. Assignment may be requested/resumed before financial eligibility and only becomes accepted by the selected eligible partner. Factual verification leaves assignment accepted; existing report publication is what completes its report engagement. Do not auto-complete a job or synthesize a report/seal to make readiness green.

New original or transcription increments evidence revision and makes old verification non-current. New Business/mandate, assignment replacement/conflict, professional/engagement/independence invalidity, policy/classification/procedure changes follow existing `storedVerification` and `withVerificationValidity` currentness checks. Historical records remain immutable and readable only under current authorized access. A replay can return its original receipt but cannot restore superseded currentness. A later extraction version alone preserves prior reviewed provenance, as existing tests require.

Only a current authenticated verification feeds existing underwriting. Preserve freshness, full selected 36/12-month evidence, current obligations/history/restrictions and all financial calculation/admission rules. Factual review supplies inputs; the existing engine may still refuse. Final report creation, step-up/seal, Business co-sign/review/publication and credit/underwriter decisions retain their separate actions and gates; `report_approval:not_cosigned` cannot substitute for any of them. Do not introduce a new report-seal prerequisite for the very factual inputs needed to start the first audit.

## 9. Refusals and recovery

Use existing `OperationResource` semantics: status/code determine outcome; 200 with a dispatch-resumed result means dispatch handling completed, not verification or eligibility. `server_time` is current presentation time, `recorded_at` retained receipt time. No raw evidence/review payload in receipt. Proposed new readiness codes below supplement existing ones; do not replace established domain refusals with generic success.

| Condition | Response / effect |
|---|---|
| Wrong actor/Business permission/staff role/token ability | Existing 403/identity code; never impersonate or grant access |
| Stale context, mandate, Business, evidence, prior transcription or assignment/verification counter | Existing context refusal, `MANDATE_STALE` or 409 `VERSION_CONFLICT` as appropriate, with safe revision/field; reload, review and use a fresh request ID |
| Readiness no originals/current transcription | 422 new `INITIAL_AUDIT_NOT_READY` + metadata reasons `STATEMENT_ORIGINAL_REQUIRED`, `STATEMENT_RECONCILIATION_REQUIRED` or `STATEMENT_TRANSCRIPTION_STALE`; no assignment |
| Bad rails/month coverage/balances/source IDs/classification | Existing `STATEMENT_*`, `DRAW_EXCEPTION_REVIEW_REQUIRED` or owner-return/transfer code, 422; field references only, no partial save |
| Wrong/missing transcription hash/currentness during verification | Existing 409 `STATEMENT_TRANSCRIPTION_STALE`; refresh entire evidence/review; no verification |
| Incomplete checks/source review/debt proof/current partial month | Existing 422 `STATEMENT_REVIEW_INCOMPLETE`, `STATEMENT_SOURCE_REVIEW_REQUIRED`, `STATEMENT_DEBT_EVIDENCE_REQUIRED`, `STATEMENT_COMPLETE_MONTH_REQUIRED` |
| Offered but unaccepted job | Existing 403 `ASSIGNMENT_NOT_ACCEPTED`; no original or verification access |
| Foreign/unknown assignment, document, transcription, receipt | Existing target-specific 404 (`ASSIGNMENT_NOT_FOUND`, `STATEMENT_NOT_FOUND`, `STATEMENT_TRANSCRIPTION_NOT_FOUND`, `OPERATION_NOT_FOUND`); no ownership leak |
| No eligible partner / exhausted normal dispatch | Retain existing `operations` state and reason (`AUDIT_NO_ELIGIBLE_PARTNER` / `AUDIT_DISPATCH_EXHAUSTED`); no waiver or false acceptance |
| Closed engagement | Existing `AUDIT_ENGAGEMENT_CLOSED`; normal separately authorized resolution, no reopening shortcut |
| Same idempotency key, changed target/expected versions/input/hash | 409 `IDEMPOTENCY_CONFLICT`; original receipt unchanged |
| Original/transcription/verification digest corruption or persistence failure | Existing integrity failure / safe 5xx; roll back, no new snapshot/assignment/eligibility. Do not relabel as customer factual approval |

The new readiness reason is structured metadata, not a new financial refusal policy. Preserve existing exact source reconciliation codes. UI may show missing evidence and safe next actions; it must not expose private candidate conflict details or another partner's notes.

## 10. Idempotency, locks and transactions

Save uses `party:<businessRepresentativePartyId>` + `statement.reconcile` + UUID and Business target; initial request uses `staff:<staffId>` + `audit.initial.request` + UUID and Business target; verification uses `party:<auditPartnerPartyId>` + `statement.verify` + UUID and assignment target. Include every reviewed precondition/input in canonical fingerprints. Replays reauthorize current actor/target first. A changed expected revision under the same UUID conflicts even when other content matches; a deliberate corrected command gets a new UUID.

For replay, establish current actor/Business/assignment authority first, then resolve the journal key/fingerprint. Check expected Business/mandate/evidence/transcription/assignment/verification revisions and proposed readiness only inside a **new** effect after replay lookup. An authorized retry returns its original receipt after later evidence changes; it cannot restore old currentness. Do not pass an old expected mandate version as a pre-journal authorization condition: authorize the actor under the current mandate, fingerprint the expected version, then compare it only for new work. Revoked permission still denies private access, including replay.

After timeout, use the same command/request/target lookup before sending a new command. If receipt absent, retry the identical key/input; do not switch to another actor or issue a fresh UUID to “try again.” Lookup must verify route Business/assignment against the journal target; existing `FindStatementOperation`/`FindStatementVerificationOperation` lack explicit route IDs and require transport target checks. Current authority revoked = no private receipt, not a reason to undo a valid historical effect. Journal retention (currently eight days) does not allow old source/currentness or assignment uniqueness checks to be skipped afterward.

Retain existing acquisition order: **BusinessProfile → acting User → globally ordered entity/mandate/candidate/source-author Parties → professional profiles and engagement acceptances → location/independence/assignment records → StatementEvidence → journal key/effect records**, as applicable to each action. Reuse `BusinessAuthorityStore`/`WithBusinessAuthority`/`WithAcceptedAuditAssignment`; never acquire a new Business lock after a Party/evidence lock. A Business revision lock serializes initially absent evidence roots and duplicate initial requests. Candidate workload/acceptance selection must remain locked across dispatch, not preselected in a controller.

Save precondition checks, source reconciliation, transcription/amendment append, evidence revision update and receipt commit in one authority-held transaction. Initial readiness comparison, live-engagement uniqueness, current candidate selection, assignment/version/notice persistence and journal commit in one transaction. Verification authority checks, evidence/source/transcription integrity, review, verification/amendment append and receipt commit in one transaction. No provider I/O inside these locks. Unexpected error rolls back outer receipt/effects; domain refusals use existing journal savepoint behavior.

Race requirements: save/upload vs verify has a serial winner; earlier verification becomes non-current if a later authorized save changes evidence. Two staff/request IDs cannot create two simultaneous initial engagements. Accept vs expire/conflict/standing withdrawal has one authorized winner. Concurrent verification amendments compare independent verification revision. Historical parser appends cannot rewrite source bytes, saved transcription or verification. No shared database or scheduler was used to test any of this in this design task.

## 11. Acceptance tests and integration sequence

Required implementation acceptance, **not executed here**:

| ID | Scenario / expected evidence |
|---|---|
| C396-01 | Brand-new verified Business with active mandate and no application/raise/report can upload, review/save, request initial Flash, receive an eligible partner's acceptance, and independently verify. No prior verified statement/eligibility circular prerequisite. |
| C396-02 | Upload/OCR/draft/save/request/accept each leaves underwriting pending/refused until current independent verification; missing 36-month history and obligations remain refused afterward; qualified repeat uses existing 12-month history authority. |
| C396-03 | Business member with only view permission, staff with `businesses.verify` only, and staff with `audit.assignments.manage` cannot transcribe; requester with manage can request but cannot read raw originals through the new metadata endpoint. No role/access rows created. |
| C396-04 | Representative corrects assisted suggestions; input bound to originals/extraction revisions; no candidate when parser cannot structure evidence; no fabricated balances/months/rail. Stale source/mandate/previous transcription/extraction refuses without amendment. |
| C396-05 | Exact rail/month coverage, continuity, duplicates, transfers, draws/returns and unauthorized draw-exception refusal; all source IDs own-Business; raw original/hash and transcription payload corruption fail closed. |
| C396-06 | Offered/foreign/expired/conflicted/replaced partner cannot read originals or verify; expired accreditation, missing engagement acceptance, role ties/conflict, capacity/rotation/location/radius keep existing gates. No candidate = operations, no waiver. |
| C396-07 | Accepted partner checks every original and all seven factual checks, exact assignment/evidence/transcription/verification versions; wrong source hash/partial month/debt proof refuses. Valid save returns `not_cosigned`, no report/credit decision. |
| C396-08 | Re-upload or amendment invalidates old currentness; replacement partner appends own verification; historical receipts cannot restore old snapshot. Parser provenance append alone preserves completed verification. |
| C396-09 | Same request/input retries and lookup replay once; same UUID different input conflicts; route-target/other Party request guesses leak nothing; current authority is rechecked on retry/lookup. |
| C396-10 | Actual PostgreSQL races: two initial requesters, upload vs save/verify, verify vs amendment, accept vs timeout/standing withdrawal/conflict. No duplicate engagement, stale verified-current snapshot or partial receipt. |
| C396-11 | Inject failures at append/revision/journal/assignment/notice boundaries; all effects roll back; verify immutable/encrypted payload and minimal journals/notices. |
| C396-12 | Existing statement evidence/transcription/verification/access, Business authority/application/window, audit assignment/independence/engagement/report/publication suites pass. Final sealing/co-sign and DSCR decisions unchanged. |

Integration order: (1) accept action/payload/actor seam and obtain UI ownership confirmation; (2) extend reviewed save preconditions/fingerprint and add authorized assistance/readiness projections without grants; (3) initial request/recovery action using existing dispatch internals and current permission; (4) thin Business/staff/Auditor transports with exact target binding and field refusals; (5) source-linked UI integration after confirmed handoff; (6) isolated feature and real PostgreSQL race tests, exact-head independent review and normal promotion gates. No implementation/promotion is performed in this turn.

Existing-code gaps: no Business transcription GET/POST or statement operation-recovery transport; no structured candidate converter/readiness Resource; existing save lacks expected Business/mandate/previous-transcription and reviewed-source/extraction pins; no staff initial-request web/API entry or `audit.initial.request` lookup; statement verification domain exists but no dedicated initial-review transport; existing store lookup signatures need target-binding guards. Audit assignment, reconciliation, integrity, encrypted immutable records and underwriting currentness already exist and should be reused.

Unresolved choices: P396-01–P396-05, especially Flash-only initial scope, transcription readiness ordering, partial-history review and manual fallback versus structured conversion, require explicit handoff resolution. They must not silently become defaults. New route names/Resources need server/UI agreement; structured conversion requires bounded implementation/proof. No authority expansion or underwriting-history waiver is approved or proposed. UI ownership is confirmed in comment 6502083103; action/payload and proposal resolution remain part of this handoff. Full BRS v2 original was not present locally; evidence is owner direction, current source contracts and linked requirements, not an independent complete BRS v2 audit.
