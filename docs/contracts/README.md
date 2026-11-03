# Server contract handoff: #383 and #396

Documentation-only draft for server/UI contract review. Aminu/Codex owns server contracts. Erastus confirmed the [cart UI](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6502081931) and [Business transcription, staff assignment and Audit Partner UI](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6502083103). The confirmation establishes ownership; it does not settle the proposed implementation defaults.

- [#383 — atomic whole-cart checkout](383-atomic-cart-checkout.md): operations/payloads, shared fee basis, acknowledgement and re-quote, line refusals, recovery, transactions and acceptance scenarios.
- [#396 — initial statement verification](396-initial-statement-verification.md): Business review/save, authorized staff request, accepted independent partner verification, source revisions/hashes, readiness, recovery and acceptance scenarios.

## Approved direction

#383 reserve and confirm each succeed for every cart line or have no effect. One server-calculated fee band uses outstanding principal in held notes plus the whole checkout, excluding wallet cash, reservations and confirmed-but-unissued purchases. Existing confirmed/issued fee snapshots, disclosures, hashes and receipts must never be repriced or rewritten.

#396 Business representatives save under existing `application.save`; staff request the initial audit under `audit.assignments.manage`; an eligible accepted Audit Partner independently verifies exact evidence/transcription revisions and hashes. Upload/OCR/unverified transcription does not grant eligibility. No new staff transcription grant, impersonation or underwriting-history waiver is introduced. Factual verification remains separate from credit decisions and final report sealing.

## Remaining proposals and prerequisites

Resolve P383-01–P383-07 explicitly: one-live-hold strategy, edit/adoption behavior, quote TTL, shared hold deadline, expiry settlement/recovery, prospective fee-consumer scope and provenance-only consent behavior. Resolve P396-01–P396-05: initial Flash-only scope, transcription readiness ordering, partial-history factual review, nullable/manual assistance versus structured conversion, and the metadata-only staff view. These are proposals, not approved product defaults.

Implementation prerequisites include an authenticated outstanding-capital source, distinct authenticated internal child receipts with complete parent binding that preserve existing uniqueness/actor/receipt/ledger constraints, and rollback/real PostgreSQL concurrency proof. Preserve both `investor:read` and `investor:command` on Investor mutations. Existing trusted per-child lifecycle writers must honor aggregate membership before batch confirmation; subsequent campaign funding/issuance remains separately governed.

## Source and validation limits

The substantive contracts received independent internal document design review against `52a68e22c028060448efaf7c45e8c3372bc7a9de`. Publication is based on current dev `e8af42fbf824bc44239bd7d4bc86a406b0667586`; relevant source changes were inspected, including #432's staff-grant storage refactor. The existing permission and identity/mandate boundaries remain the contract. Ownership/source notes were refreshed and local workspace references omitted for publication; unresolved proposals remain explicit.

Offline source/design/example checks passed during preparation. Publication checks cover documentation-only scope, correspondence to the reviewed text, links, whitespace and absence of local/private evidence. These are not runtime, financial, security, database, concurrency, browser or CI certification. No runtime test or shared database was used for this documentation change. Acceptance matrices specify future implementation tests; they are not reported as executed passes.

This draft does not implement runtime behavior or authorize access changes, financial activation, merge or deployment. It does not close #383 or #396. Subsequent implementation/review/promotion keeps the repository's existing gates.
