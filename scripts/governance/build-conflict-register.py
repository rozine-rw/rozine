#!/usr/bin/env python3
"""
Builds the C-01–C-34 disposition register.

The conflict text and governing default are quoted from Section 8 of the plan
rather than retyped, so the register cannot drift from the source it disposes
of. Everything this script adds — whether a conflict is settled or blocked, who
owns it, and which gate it must close before — is recorded judgement and lives
in DISPOSITIONS below.
"""
import re
import sys
from pathlib import Path

PLAN = Path('docs/Rozine_Phased_Implementation_Plan.md')
OUT = Path('docs/phase-0/conflict-disposition-register.md')

# status: SETTLED (the stated default disposes of it, nothing further required)
#         BLOCKED (a named decision must be signed before the affected code is finalised)
# owner:  per D-71 role ownership
# gate:   the point by which it must close
DISPOSITIONS = {
 'C-01': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Phase 1 Party model freeze',
          'The BRS prohibition is absolute and needs no further decision. What remains is engineering: prove no registry integration receives or persists a TIN.'),
 'C-02': ('SETTLED', 'Kimani — Finance/Risk', 'Phase 3 secondary contract freeze',
          '3% stands until a versioned fee amendment exists. No amendment is proposed, so nothing is pending.'),
 'C-03': ('BLOCKED', 'Kimani — Finance/Risk', 'Appendix A activation, before Phase 1 underwriting',
          'D-11 selects the 10.0% floor, but Appendix A is a product-approved candidate rather than an activated policy. The floor cannot be encoded until it is signed.'),
 'C-04': ('BLOCKED', 'Kimani — Finance/Risk', 'Appendix A activation, before Phase 1 underwriting',
          'Same activation gate as C-03. The prototype coefficients are rejected either way; what is unsigned is the replacement.'),
 'C-05': ('BLOCKED', 'Kimani — Finance/Risk', 'Appendix A activation, before Phase 1 underwriting',
          'Rounding and DSCR-floor behaviour are money rules. They need the Appendix A signature, not just the D-11/D-11B selection.'),
 'C-06': ('BLOCKED', 'Kimani — Finance/Risk', 'Appendix A activation, before Phase 1 underwriting',
          'D-13 preserves the manual tier as indicative only. Activation is what stops it becoming an offer.'),
 'C-07': ('BLOCKED', 'Kimani — Finance/Risk', 'Before primary listing in Phase 1',
          'Note boundaries are unbaselined in the BRS. They must become versioned policy or stay absent; the brief claim cannot be hard-coded.'),
 'C-08': ('BLOCKED', 'Kimani — Finance/Risk', 'Before Phase 1 capacity calculation',
          'The 35% revenue ceiling has no BRS basis. It is excluded from production capacity until approved as policy.'),
 'C-09': ('BLOCKED', 'Kimani — Finance/Risk', 'Before primary-investment acceptance in Phase 1',
          'The BRS delegates concentration limits to policy and none exist. Accepting investment without them would encode an unapproved limit by omission.'),
 'C-10': ('BLOCKED', 'Robert — Product and internal Legal', 'Before Phase 1 Resource contract freeze',
          'Exactly which aggregate financial facts an Investor may see is a disclosure decision. The Resource schema cannot freeze before it.'),
 'C-11': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Phase 6 Pulse remediation',
          'Server-backed values replace random counters. The waitlist numbering half was closed on 2026-09-06 under D-73 with PostgreSQL-certified allocation.'),
 'C-12': ('SETTLED', 'Robert — Product', 'Phase 6 Pulse remediation',
          'Fabricated activity is prohibited outright. Consented records or unmistakable fixtures only.'),
 'C-13': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Closed 2026-08-29',
          'Remediated: React renders server-returned Resource facts and the Domain/Application/Infrastructure boundary is enforced by the architecture suite.'),
 'C-14': ('BLOCKED', 'Robert — Product', 'Before Phase 1 evidence ingestion',
          'Statement upload is mandatory under the BRS. Whether a typed preview may exist alongside it, and how it is labelled, is unapproved.'),
 'C-15': ('SETTLED', 'Kimani — Finance/Risk', 'Phase 4 provider certification',
          'Bank and mobile money only. Card rails stay out until fees, disputes and chargebacks are approved, which is a separate decision rather than a pending one.'),
 'C-16': ('BLOCKED', 'Robert — internal Legal, with Kimani — Finance/Risk', 'Before any treasury behaviour is built',
          'Float and interest ownership touch segregated funds. This needs legal and accounting sign-off, and external counsel under D-39, before a single line is written.'),
 'C-17': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Phase 3 content finish',
          'Unprovable security claims are replaced with precise ones backed by implemented controls. No decision is pending; this is editorial discipline.'),
 'C-18': ('SETTLED', 'Robert — Product', 'Phase 3 content finish',
          'Safety and capital-protection language is prohibited. The rating describes verified business quality and standing.'),
 'C-19': ('SETTLED', 'Robert — Brand', 'Closed 2026-09-07',
          'Superseded by the D-52 amendment: the logo package colours are now authoritative and the guide is reissued as V1.1.'),
 'C-20': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Closed 2026-09-07',
          'Superseded by the D-04 decision: responsive web/PWA for the MVP with a narrow thin-native Auditor capture companion. Pulse stays post-MVP.'),
 'C-21': ('BLOCKED', 'Kimani — Finance/Risk', 'Appendix A activation, before Phase 1 underwriting',
          'BRS tenors and the D-14 routing survive, but the specific manual and auto routes are unactivated policy.'),
 'C-22': ('BLOCKED', 'Kimani — Finance/Risk', 'Before Phase 2 arrears behaviour',
          'No penalty may be invented. D-11C default and arrears consequences must be signed before any consequence is coded.'),
 'C-23': ('SETTLED', 'Kimani — Audit Operations', 'Phase 2 Auditor earnings',
          'The BRS 25% share, monthly payability and SLA freeze govern. The PDF figures do not override them.'),
 'C-24': ('BLOCKED', 'Kimani — Audit Operations', 'Before Phase 2 dispatch',
          'BRS eligibility and the 24-hour rule govern, but D-32 must settle the dispatch policy before it is implemented.'),
 'C-25': ('BLOCKED', 'Kimani — Finance/Risk', 'Before Phase 1 fee disclosure',
          'The exclusive BRS fee schedule governs and every new fee is quarantined. Which of the PDF fees survive is unapproved, and fee disclosure is on the Phase 1 chain.'),
 'C-26': ('BLOCKED', 'Kimani — Finance/Risk, with Robert — internal Legal', 'Before any protection language ships',
          'A reserve floor is an accounting and disclosure commitment. It stays excluded until funded, accounted for and legally reviewed.'),
 'C-27': ('SETTLED', 'Robert — Product, with Kimani — Compliance', 'Phase 3 secondary contract freeze',
          'Rejected outright. Secondary liquidity is Investor-to-Investor; Rozine holds no principal inventory and makes no market.'),
 'C-28': ('SETTLED', 'Kimani — Finance/Risk', 'Phase 3 secondary contract freeze',
          'Rejected for the current product. The BRS 3% seller fee on a settled trade is the only secondary fee.'),
 'C-29': ('SETTLED', 'Robert — Product', 'Phase 7',
          'The Automation screen is a gated explainer in the MVP. Executable Plus moves to Phase 7 behind product and policy approval.'),
 'C-30': ('SETTLED', 'Aminu and Erastus — Engineering/Security', 'Phase 1 Resource contract freeze',
          'Monetary amounts trace to ledger entries; ratings, capacity and evidence facts trace to their own versioned records. Both are traceable, to different things.'),
 'C-31': ('SETTLED', 'Kimani — Finance/Risk', 'Phase 3 demo seed',
          'All four BRS bands are seeded. Distressed is historical and non-listable, so it can never appear as an eligible deal.'),
 'C-32': ('SETTLED', 'Robert — Brand', 'Closed 2026-09-07',
          'Superseded by D-51: the detached leading star is the primary lockup and the embedded PDF treatment is non-authoritative reference art.'),
 'C-33': ('BLOCKED', 'Kimani — Finance/Risk', 'Phase 4 provider certification',
          'Both-network MoMo and same-day withdrawal are provider targets, not promises. D-21 and D-38 contracts must fix cutoffs, reversals and settlement windows first.'),
 'C-34': ('BLOCKED', 'Robert — Product, with Aminu — Engineering', 'Before the Phase 1 Party model freezes',
          'Two directors must not be hard-coded. D-64 decides who must be verified, and the Party model cannot freeze before it.'),
}


def main() -> int:
    plan = PLAN.read_text()
    rows = re.findall(r'^\| (C-\d+) \| (.*?) \| (.*?) \|$', plan, re.M)

    if len(rows) != 34:
        print(f'Expected 34 conflicts in Section 8, found {len(rows)}.', file=sys.stderr)
        return 1

    missing = {cid for cid, _, _ in rows} - DISPOSITIONS.keys()
    if missing:
        print(f'No disposition recorded for: {", ".join(sorted(missing))}', file=sys.stderr)
        return 1

    blocked = [c for c in rows if DISPOSITIONS[c[0]][0] == 'BLOCKED']

    out = [
        '# C-01–C-34 conflict disposition register',
        '',
        '**Status:** `DISPOSITIONED 2026-09-09` · **Generated** by `scripts/governance/build-conflict-register.py`',
        '',
        'Section 8 of the plan states each conflict and the default this plan uses. That default is a',
        'working assumption, not an approval. This register adds what Phase 0 requires of each one:',
        'whether the default disposes of it or a named decision must still be signed, who owns that,',
        'and the gate it has to close before.',
        '',
        'Conflict text is quoted from Section 8 rather than retyped, so the two cannot drift apart.',
        '',
        f'**{len(rows) - len(blocked)} settled · {len(blocked)} blocked on a signed decision.**',
        '',
        'No PDF page, brief, prototype constant or formula note overrides the BRS anywhere below. Where',
        'a conflict is settled, it is settled because the BRS or an approved decision governs — never',
        'because the louder document won.',
        '',
        '## Blocked — a signed disposition is required',
        '',
        'These cannot be encoded as behaviour until their owner signs. Several sit directly on the Phase 1',
        'chain, which is why Phase 0 cannot exit on the strength of the defaults alone.',
        '',
        '| ID | Conflict | Owner | Must close by | Why it is not settled |',
        '|---|---|---|---|---|',
    ]

    for cid, conflict, _default in rows:
        status, owner, gate, why = DISPOSITIONS[cid]
        if status == 'BLOCKED':
            out.append(f'| **{cid}** | {conflict} | {owner} | {gate} | {why} |')

    out += [
        '',
        '## Settled by the governing default',
        '',
        'Each of these is disposed of by the BRS or by an approved decision. They still carry an owner,',
        'because a settled conflict can be reopened by a later amendment and someone has to notice.',
        '',
        '| ID | Conflict | Governing disposition | Owner | Gate |',
        '|---|---|---|---|---|',
    ]

    for cid, conflict, default in rows:
        status, owner, gate, why = DISPOSITIONS[cid]
        if status == 'SETTLED':
            out.append(f'| {cid} | {conflict} | {why} | {owner} | {gate} |')

    out += [
        '',
        '## What this register does not do',
        '',
        'It does not activate Appendix A. Six of the blocked conflicts — C-03, C-04, C-05, C-06, C-21 and',
        'the underwriting half of C-25 — wait on the same signature, so activating that worksheet closes',
        'more of this register than any other single act.',
        '',
        'It does not substitute for external authority. C-16 and C-26 need legal and accounting review',
        'beyond the internal pool, and D-71 forbids the pool from self-approving that.',
        '',
        'Governing defaults quoted here remain what the plan says they are. Changing one requires a dated,',
        'versioned policy decision with an owner, rationale, effective date, migration impact and tests —',
        'never an undocumented constant.',
        '',
    ]

    OUT.write_text('\n'.join(out))
    print(f'Wrote {OUT} — {len(rows)} conflicts, {len(blocked)} blocked.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
