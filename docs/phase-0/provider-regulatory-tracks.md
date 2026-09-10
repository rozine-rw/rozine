# Provider and regulatory tracks

**Status:** `OWNED 2026-09-10 · EXTERNAL ENGAGEMENT NOT YET CONFIRMED` · **Governing decisions:**
D-21, D-38, D-39, D-40, D-41, D-43 · **Ownership:** D-71

These tracks start in Phase 0 because they are the longest waits in the programme, and none of them
is engineering time. Provider contracting alone is estimated at four to twelve weeks; CMA and legal
authorisation at eight to twenty-four or more. A track that starts when the code needs it has already
missed its window.

## What "started" means here

Each track below has a **named owner, a governing decision, and a first action**. That is the part
this document can do.

It does not claim any provider has been contacted, any contract drafted or any regulator engaged.
Those are acts only the named owner can perform. The Phase 0 item stays open until each owner
confirms first contact — recording an owner is not the same as the owner having started.

---

## The ten required tracks

| Track | Owner | Decision | First action | Certification gate |
|---|---|---|---|---|
| **KYC / KYB identity** | Kimani — Compliance, with Aminu — Engineering | D-38 | Shortlist Rwandan-capable identity providers against the D-64 signatory model | Phase 4 — replay, reversal and outage evidence |
| **Business registry** | Kimani — Compliance, with Aminu — Engineering | D-38 | Confirm a registry lookup can run **without receiving or persisting a TIN** (C-01) | Phase 4 |
| **ICPAR Audit Partner** | Kimani — Audit Operations | D-38 | Open the ICPAR accreditation conversation and the Audit Partner agreement | Phase 4 — live integration certification |
| **Statement parsing / OCR** | Aminu — Engineering, with Kimani — Finance/Risk | D-38 | Collect representative Rwandan bank statements to evaluate parsers against | Phase 4 — with correction lineage proven |
| **Bank rails** | Kimani — Finance/Risk | D-21 | Define the custody account topology and safeguarded-funds controls with a bank | Phase 4 — live rail certification |
| **Mobile money** | Kimani — Finance/Risk | D-21 | Engage both networks; fix cutoffs, reversals and settlement windows (C-33) | Phase 4 — live rail certification |
| **Evidence storage** | Aminu and Erastus — Engineering/Security, with Robert — internal Legal | D-38, D-40 | Settle the data-residency boundary before choosing a storage region | Phase 4 |
| **Maps / geolocation** | Erastus — Engineering, with Kimani — Audit Operations | D-38 | Confirm the provider supports the 30 km registered-office eligibility rule (C-24) | Phase 2 — Auditor dispatch |
| **Legal counsel** | Robert — internal Legal | D-39 | Brief Rwandan counsel on the Note structure, client-money flow and fixed-return disclosures | External — not self-approvable |
| **CMA sandbox** | Robert — internal Legal, with Kimani — Compliance | D-39 | Request the sandbox path and its admission criteria | External — not self-approvable |

## Adjacent tracks Phase 4 also certifies

| Track | Owner | Decision |
|---|---|---|
| Sanctions / PEP / AML screening | Kimani — Compliance | D-38 |
| Messaging and push | Robert — Product, with Erastus — Engineering | D-38, D-49 |
| Regulatory report submission | Kimani — Compliance | D-41 |
| Independent penetration test | Aminu and Erastus — Security | D-43 — external |

## Indicative calendar allowances

From the plan's external-track table. These overlap rather than add, and none is engineering time.

| Track | Allowance |
|---|---|
| Provider selection, contracting and sandbox credentials | 4–12+ weeks |
| Live bank, mobile-money and ICPAR certification | 6–16+ weeks |
| CMA, sandbox and legal review and authorisation | 8–24+ weeks, potentially longer |
| Independent penetration test, execution and retest | 3–8 weeks |

---

## How engineering proceeds without waiting

No track blocks Phase 1, because every provider sits behind an application-owned port with a
deterministic fake.

- **The port is the contract.** Domain code never sees a vendor SDK type; the architecture suite
  already restricts concrete adapters to the service provider that binds them.
- **Fakes are deterministic and never reach production.** A fake that returns random data cannot be
  replayed, and a replay is how a failed test becomes a fixed one.
- **A certified provider replaces a fake behind the same port.** The contract tests that passed
  against the fake must pass against the provider, or the provider is not certified.
- **A surrogate that passes proves nothing about the provider.** This is exactly why
  `MVP RELEASE CANDIDATE` does not claim rails work, and why `LIVE MVP ACCEPTED` requires them.

## External authority

Legal counsel, the CMA, ICPAR, provider certification and the penetration test are external gates.
Under D-71 the internal pool of Aminu, Erastus, Robert and Kimani cannot approve them on their own
authority. An owner here is accountable for driving a track to its external decision, not for making
that decision.
