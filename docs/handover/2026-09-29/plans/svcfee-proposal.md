@hussain4real **Proposed wire shape for C4 gate 6 (borrower service-fee disclosure).** This is a proposal for your server contract, not an implementation. We'd bind the UI fixture-first once you agree the shape.

Today, the ready quote from `EloquentBusinessApplicationStore` (dev `:957-969`) returns `principal`, `interest`, `total` and `schedule[{instalment, amount}]`, with no service-fee field. I propose adding:

```jsonc
"service_fee": {
  "rate_bps": 200,
  "basis": "repaid_principal_and_contractual_return",     // excludes fees and penalties (§11.4)
  "timing": "per_instalment",                             // charged with each scheduled instalment
  "rounding": "half_up_whole_rwf_per_instalment",         // see Q1
  "schedule": [{ "instalment": 1, "amount": {"currency": "RWF", "amount": "39996"} }],
  "total": {"currency": "RWF", "amount": "239976"},
  "policy_version": "…"                                   // same versioning as the quote's policy_version
},
"total_payable": {"currency": "RWF", "amount": "12238776"}  // total + service_fee.total
```

The worked example is the report's RWF 10.8M at 11.1% flat over 6 months: instalment 1,999,800, fee 39,996 per instalment, total fee 239,976 and total payable 12,238,776.

**Questions**
1. **Rounding.** Half-up per instalment, or on the total with the remainder on the final instalment? §11.4 states half-up whole RWF for the *investor* fee; the borrower fee doesn't state it.
2. **Actual versus scheduled.** The fee applies to amounts *actually repaid*, so partial or early repayment (if Robert's Business Terms §3.4 is adopted) changes it. I propose the disclosure shows the *scheduled* figures and the text says it is charged on what is actually repaid.
3. **Digest.** Should `service_fee` be inside the quote/offer disclosure digest, so accepting a quote binds the fee terms, in the same way the Investor digest covers per-instalment fees?
4. **Business Terms conflict.** Robert's `business-terms-2026-10-01` §2.2–2.3 instead deducts 2% at disbursement. If he adopts that through #180, the shape changes to a `deductions` block. The field above assumes the current §11.4 baseline.

UI plan once agreed: the Review & sign offer card and the Publish sheet show a "Service fee (2% of each repayment)" row, per-instalment amounts in the schedule, and the total payable. We'd build this against fixtures, which is no-op until your field lands.
