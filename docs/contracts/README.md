# Server contract handoff: #383 and #396

Reconciled to governing **BRS v3.0 (6 November 2026)** on 9 November; delivery evidence refreshed 16 November. This PR changes documentation only, not legal/CMA approval or runtime certification.

- [#383 — atomic whole-cart checkout](383-atomic-cart-checkout.md): v3 wallet commitment/withdrawal semantics, Pay acknowledgement, prospectus versions, material-change handling, immutable fee snapshots and retained engineering mechanisms.
- [#396 — initial statement verification](396-initial-statement-verification.md): routine statement-review lifecycle from merged #434, separate baseline sealing, one-CPA sealing/disputes, guided visits and remaining v3 coverage.

## Precedence and ownership

Aminu directed that v3 supersede earlier requirements. Each document starts with a governing delta; older source/payload examples are historical engineering detail subject to it, not competing product policy. V3 section 25 allows explicitly proposed defaults unless told otherwise; undefined methods and contradictions stay explicit.

**Confirmed decisions, refreshed 9 November:** Aminu confirmed a single **1.50 coverage threshold**. A requested instalment must have exact coverage ≥1.50; below 1.50 reject the request. The former 1.25 rejection floor and 1.25–1.50 automatic-counteroffer band are superseded, not retained as exceptions. Capacity for a smaller new request may be displayed, but is not an automatic counteroffer on a rejected request. Other eligibility, revenue/absolute caps, RWF 5,000 rounding and RWF 3M minimum still apply.

[Robert confirms repeat opening only after the previous note is fully repaid](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6740428915), including early repayment. This supersedes final-month early fundraising and the former funded-repeat waiting model; there is no overlapping raise requiring an old-final-instalment reserve or old-plus-new instalment test. Do not invent release operations for legacy reserves or use new proceeds to repay old debt. [The growth ceiling is 1.5× the original principal of the immediately preceding, fully repaid note](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6742069454), separate from the 1.50 affordability ratio and always limited by lower caps. [Robert confirms rejection of the RWF 30M application](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6744365155); Aminu's subsequent threshold confirmation removes the remaining two-threshold ambiguity.

[No exceptional-month trimming](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6739131413): retain all verified months, including seasonal highs, weak and negative months. Verify unusual receipts factually; no discretionary exclusion or invented trimming algorithm. [Resale and signature decisions](https://github.com/rozine-rw/rozine/issues/524#issuecomment-6736497481): a seller may price below, but never above, all remaining contractual payouts; the first 11 gates pass, then the official representative signs to complete gate 12, then lock and publish. Conflicting examples and the circular requirement for all 12 gates before enabling signature are superseded. The confirmed overview return range remains 12.5–15%.

V3 section 25 permits its explicitly proposed defaults unless told otherwise. These later decisions govern the conflicts above; unrelated open decisions are not resolved by this document. No runtime change or financial activation follows from this documentation update.

[Aminu's implementation handoff](https://github.com/rozine-rw/rozine/pull/433#issuecomment-6507027925) assigns server work to Erastus; Aminu retains design authority and explicit PR review. UI ownership remains Erastus. [#434](https://github.com/rozine-rw/rozine/pull/434) merged its amended routine-review and aggregate-expiry design. This reconciliation neither reassigns work nor resumes an obsolete independent implementation lane.

## Delivery and validation limits

#396 is closed following its [3 November reported staging test](https://github.com/rozine-rw/rozine/issues/396#issuecomment-6596133832). #383 closed on 10 November after Erastus's [staging retest on UAT f5f26c2b](https://github.com/rozine-rw/rozine/issues/383#issuecomment-6768909273): the report records the fee-band header, a two-deal hold and release, expiry after the five-minute hold followed by sweep about 2–4 minutes later, and a fresh quote. Its confirmation example buys one Rugali note into Awaiting issue, not both deals together. [#531](https://github.com/rozine-rw/rozine/issues/531) records the resulting expiry-action, stale-total and busy-state usability follow-ups; inspect its current status before taking work.

These are author-reported staging observations, not tests independently rerun here or proof of all-cart confirmation, full BRS compliance, prospectus binding, settlement or every runtime/concurrency acceptance case. Inspect current implementation before treating historical gap lists as work to do. The original publication/review SHAs in this handoff are historical snapshots, not the current PR head; obtain that head from GitHub before any exact-head review.

The October source review remains historical evidence, not current v3 certification. This update reads the actual v3 PDF, current PR/issue discussions and merged #434 design. Acceptance matrices are future implementation checks, not executed tests. Preserve existing exact receipt/ledger constraints, source identity, immutable financial snapshots, actor permissions, atomicity and recovery. Final payload/schema design, repeat-opening repayment evidence and real PostgreSQL concurrency proof belong to implementation and exact-head review.

No runtime code, data, credentials, permissions, financial activation, merge or deployment is changed or authorized. Existing history is not rewritten. Neither issue is closed/reopened by this documentation update.

## Remaining limits

Runtime/schema implementation and exact-head validation remain separate. Other v3 open decisions retain their own status; the single underwriting threshold does not by itself redefine the separate P-G07 downside-disclosure rule. No new business-policy ambiguity remains in the confirmed repeat-opening, growth-cap, trimming, resale-price or signature sequence described above.
