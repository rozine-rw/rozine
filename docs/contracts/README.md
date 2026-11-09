# Server contract handoff: #383 and #396

Reconciled to governing **BRS v3.0 (6 November 2026)** on 9 November. This remains a documentation-only draft, not legal/CMA approval or runtime certification.

- [#383 — atomic whole-cart checkout](383-atomic-cart-checkout.md): v3 wallet commitment/withdrawal semantics, Pay acknowledgement, prospectus versions, material-change handling, immutable fee snapshots and retained engineering mechanisms.
- [#396 — initial statement verification](396-initial-statement-verification.md): routine statement-review lifecycle from merged #434, separate baseline sealing, one-CPA sealing/disputes, guided visits and remaining v3 coverage.

## Precedence and ownership

Aminu directed that v3 supersede earlier requirements. Each document starts with a governing delta; older source/payload examples are historical engineering detail subject to it, not competing product policy. V3 section 25 allows explicitly proposed defaults unless told otherwise; undefined methods and contradictions stay explicit.

[Robert's #524 response](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6727745328) confirms repeat disbursement waits until prior-note full repayment, repeat coverage sizing at 1.50 and overview returns 12.5–15%. [Pending clarifications](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6730940161) remain unresolved: trimming, resale example and signature-gate sequence. Do not invent first-repeat cap or reserved-instalment handling.

[Aminu's implementation handoff](https://github.com/rozine-rw/rozine/pull/433#issuecomment-6507027925) assigns server work to Erastus; Aminu retains design authority and explicit PR review. UI ownership remains Erastus. [#434](https://github.com/rozine-rw/rozine/pull/434) merged its amended routine-review and aggregate-expiry design. This reconciliation neither reassigns work nor resumes an obsolete independent implementation lane.

## Delivery and validation limits

#396 is closed following its [3 November reported staging test](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6596133832); #383 remains open with its [latest reported UI gaps](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6604285360). No staging tests were rerun here. Inspect current implementation before treating historical gap lists as work to do.

The October source review remains historical evidence, not current v3 certification. This update reads the actual v3 PDF, current PR/issue discussions and merged #434 design. Acceptance matrices are future implementation checks, not executed tests. Preserve existing exact receipt/ledger constraints, source identity, immutable financial snapshots, actor permissions, atomicity and recovery. Final payload/schema design, funded-repeat ledger timing and real PostgreSQL concurrency proof belong to implementation and exact-head review.

No runtime code, data, credentials, permissions, financial activation, merge or deployment is changed or authorized. Existing history is not rewritten. Neither issue is closed/reopened by this documentation update.
