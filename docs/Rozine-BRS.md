rozine 

BRS · v1.0 · JULY 2026 



# Business Requirements Specification 

The complete, authoritative statement of what the Rozine platform must do — across the Investor app, Business app, Auditor app, Admin console, Pulse and the shared transactional core. 

|DOCUMENT CONTROL|VALUE|
|---|---|
|Document|Rozine Platform—Business Requirements Specification|
|Version|1.0|
|Status|Baselined for build|
|Owner|Rozine Technologies Ltd—Product|
|Audience|Engineering, Compliance, Audit Partner Operations, Regulator, Investors|
|Supersedes|All prior specifications referencing RRA/EBM tax-data verification (deprecated)|
|Classification|Confidential|



#### MATERIAL CHANGE FROM PRIOR BASELINE 

The verification model has changed fundamentally. Tax-authority integration (RRA sync, EBM device/TIN linkage, automated tax-receipt scraping, tax compliance badging) is deprecated in full and must not appear in any interface, data model, contract or communication. It is replaced by bank/MoMo statement ingestion with OCR parsing, and on-site co-signature by ICPAR-accredited Audit Partners under ISRS 4400 agreed-upon procedures. 

ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 

## Purpose, scope and approach 

### 1.1 Purpose 

This document specifies, without ambiguity, the business requirements the Rozine platform must satisfy. It is the single reference against which the build is scoped, the compliance programme is evidenced, and delivery is accepted. Where any other artefact conflicts with this document, this document governs. 

### 1.2 In scope 

- All five applications: Investor, Business, Auditor, Admin console, Pulse. 

- The shared transactional core: entity model, lifecycle state machines, ledger, policy engine, event propagation. 

- Underwriting, pricing, rating, verification, monthly reporting, servicing, collections and recovery. 

- Primary issuance and secondary-market trading. 

- Wallet, payments, treasury and fee collection. 

- KYC/KYB, AML, disclosure, governance and supervisory reporting. 

- The Audit Partner lifecycle from accreditation to compensation. 

### 1.3 Out of scope 

- Any tax-authority integration or tax-derived verification (deprecated — see change notice above). 

- Deposit-taking, principal lending from Rozine's balance sheet, or guarantee of investor capital. 

- Foreign-currency instruments and cross-border settlement in v1. 

- Equity instruments, convertible instruments and revenue-share instruments other than the fixedreturn note. 

Insurance products and credit guarantees (may be partnered, not built). 

### 1.4 Requirement notation 

|PREFIX|CLASS|MEANING|
|---|---|---|
|**`BO-n`**|Business objective|The outcome the platform exists to produce.|
|**`BR-n`**|Business rule|An invariant the system must never violate.|
|**`FR-n`**|Functional requirement|A capability the system must provide.|
|**`DR-n`**|Data requirement|Data that must be captured, derived or retained.|
|**`NFR-n`**|Non-functional requirement|A quality attribute or operational constraint.|
|**`IR-n`**|Integration requirement|An external system dependency.|
|**`CR-n`**|Compliance requirement|A regulatory or legal obligation.|
|**`AC-n`**|Acceptance criterion|The condition under which delivery is accepted.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

MUST denotes a mandatory requirement. SHOULD denotes a strong recommendation that may be deferred with documented justification. MAY denotes an option. 

ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 2 

## Business objectives 

|ID|OBJECTIVE|MEASURE OF SUCCESS|
|---|---|---|
|**`BO-1`**|Enable profitable, asset-light<br>Rwandan businesses to raise<br>growth capital without collateral.|Capital deployed; number of distinct businesses funded; median<br>days from application to funded.|
|**`BO-2`**|Give retail investors a<br>comprehensible, verified, fixed-<br>return instrument accessible from<br>RWF 5,000.|Registered investors; median first-ticket size; reinvestment rate at<br>maturity.|
|**`BO-3`**|Make monthly verification of<br>small-business performance<br>cheap, rigorous and routine.|Cost per verified report; report SLA compliance rate; parsing<br>accuracy against auditor ground truth.|
|**`BO-4`**|Never permit a business to<br>borrow beyond its verified debt-<br>service capacity.|Zero notes originated above computed capacity; portfolio at risk<br>by cohort.|
|**`BO-5`**|Provide pre-maturity liquidity to<br>investors through a functioning<br>secondary market.|Secondary volume; median time-to-fill; spread to par.|
|**`BO-6`**|Operate to a supervisory standard<br>from day one, sufficient to enter<br>and exit the RCMA sandbox<br>successfully.|Sandbox admission; zero material findings; licence granted.|
|**`BO-7`**|Earn only from platform services,<br>never from spread, distress or<br>principal risk.|Revenue composition across the five fee lines; zero revenue<br>derived from default.|
|**`BO-8`**|Build a durable, disciplinable Audit<br>Partner profession with aligned<br>economics.|Accredited partners; partner retention; yield-share paid;<br>disciplinary incidence.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 3 

## Actors, roles and permissions 

### 3.1 Primary actors 

|ACTOR|APP|RESPONSIBILITY|
|---|---|---|
|Investor|Investor|Funds notes, holds positions, receives repayments and verified monthly<br>reports, trades on the secondary market.|
|Business owner|Business|Applies, uploads statements, accepts capacity, creates and manages a<br>raise, submits monthly reports, repays.|
|Audit Partner|Auditor|ICPAR-accredited CPA. Performs on-site verification, captures evidence,<br>co-signs reports, earns yield share.|
|Rozine staff|Admin|Underwriting, compliance, treasury, dispatch, partner accreditation,<br>policy, dispute resolution.|
|Prospect|Pulse|Pre-launch investor pledging or business pre-qualifying. Holds no<br>account and transacts no funds.|
|Supervisor|Admin (read-<br>only)|Regulator or auditor granted a read-only seat over the live operating state.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### 3.2 Staff roles and permission matrix 

|CAPABILITY|ANALYST|APPROVER|TREASURY|COMPLIANCE|SUPERADMIN|
|---|---|---|---|---|---|
|View all directories and<br>records|Yes|Yes|Yes|Yes|Yes|
|Approve/decline an<br>application|No|Yes|No|No|Yes|
|Override computed<br>capacity|No|No|No|No|Yes|
|Verify ICPAR licence,<br>accredit partner|No|Yes|No|Yes|Yes|
|Freeze/unfreeze a party|No|No|No|Yes|Yes|
|Freeze/release Audit<br>Partner yield|No|Yes|Yes|Yes|Yes|
|Post manual ledger entry/<br>reversal|No|No|Yes|No|Yes|
|Halt or resume the<br>secondary market|No|No|Yes|Yes|Yes|
|Amend underwriting or fee<br>policy|No|No|No|No|Yes|
|Toggle app feature flags/<br>maintenance|No|No|No|No|Yes|
|Manage staff accounts and<br>roles|No|No|No|No|Yes|
|Act-as (view a user's live<br>app state)|Yes|Yes|No|Yes|Yes|
|Broadcast to users|No|Yes|No|Yes|Yes|
|ID<br>REQUIREMENT||||||
|**`BR-1`**<br>Every administr|ative action MU|ST be gated by th|e actor's role. An|ungated action is|a defect.|
|**`BR-2`**<br>Every administr<br>reason and time|ative action MU<br>stamp. Reason|ST write an immu<br> is mandatory and|table audit entry <br> non-empty.|recording actor, ta|rget, action, stated|
|**`BR-3`**<br>Destructive or fi<br>policy amendme<br>requires it.|nancially mate<br>nt) MUST requ|rial actions (freeze<br>ire explicit confir|,reversal, capac<br>mation, and MUS|ity override, yield <br>T support dual app|release, market halt,<br>roval where policy|
|**`BR-4`**<br>Act-as MUST be|read-only, vis|ibly indicated, tim|e-boxed, and log|ged as a distinct e|vent class.|
|**`BR-5`**<br>No role, includin|g Superadmin,|MAY delete an au|dit entry or a led|ger entry.||



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 4 

## Domain model and data requirements 

### 4.1 Core entities 

|ENTITY|KEY ATTRIBUTES|
|---|---|
|Party|Party ID, kind (business/investor/auditor/staff/platform),legal name, KYC-KYB state,<br>verification tier, frozen flag, contact, district and province, created-at.|
|Business profile|Registration number, sector, premises geolocation, owner identity, bank and MoMo accounts<br>on file, statement history, derived cash-flow series, capacity, rating, health status, standing.|
|Auditor profile|ICPAR member ID, practising certificate number, licence expiry, accreditation state, office<br>geolocation, active engagement count and cap, performance rating, yield-frozen flag.|
|Application|Application ID, business, requested tenor, computed capacity, DSCR, tier, decision, decision<br>reason, underwriter, timestamps.|
|Note|Note ID, issuer, title, state, target amount, raised amount, tenor months, total return rate,<br>rating at issue, unit price, units outstanding, listing date, expiry date, maturity date, assigned<br>Audit Partner.|
|Holding|Investor, note, units, cost basis, accrued return, payouts received, acquisition channel<br>(primary/secondary).|
|Monthly report|Report ID, note, business, Audit Partner, reporting period, state, statement document<br>reference, parsed gross inflow and outflow, liquidity coverage, health status, business note<br>and photographs, auditor note and geo-tagged photographs, verification seal hash,<br>submission and co-signature timestamps.|
|Order|Order ID, note, seller, units, ask price, state, matched buyer, settlement reference.|
|Ledger entry|Entry ID, kind, debit party, credit party, amount, currency, reference, reversal-of, immutable<br>timestamp.|
|Repayment<br>schedule|Note, instalment number, due date, principal component, return component, fee components,<br>state, paid-at.|
|Dispute|Dispute ID, report, raising party, respondent, reason, state, resolving staff, resolution note.|
|Policy|Path, value, version, amended-by, amended-at, prior value.|
|Audit action|Action ID, staff actor, target type and ID, action performed, reason note, timestamp.|
|Notification|Recipient, class, subject, body, related entity, read state, delivery channels.|
|Pulse registration|Side (investor/business),name, contact method and value, province and district, pledge<br>amount or pre-qualified amount, sequence number, created-at.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### 4.2 Data requirements 

|ID|REQUIREMENT|
|---|---|
|**`DR-1`**|All monetary amounts MUST be stored as exact decimal values in Rwandan francs. Floating-point<br>representation of money is prohibited.|
|**`DR-2`**|Every balance MUST be derived from immutable ledger entries. No stored balance field MAY be<br>mutated directly.|
|**`DR-3`**|Every uploaded statement MUST be retained in original form alongside its parsed derivation, with both<br>linked to the report or application that used them.|
|**`DR-4`**|Every evidence photograph MUST retain capture timestamp, device-reported geolocation and capture<br>method (in-app camera only).|
|**`DR-5`**|Every underwriting decision MUST retain its complete input set, so the decision can be recomputed and<br>explained years later.|
|**`DR-6`**|Policy values MUST be versioned. A decision MUST record which policy version governed it.|
|**`DR-7`**|Personal data MUST be classified, access-controlled and retained per the retention schedule; deletion<br>requests MUST be honoured except where records are required by law or regulation.|
|**`DR-8`**|The system MUST NOT store any tax identification number, EBM device identifier, or tax-authority<br>credential. Existing columns MUST be dropped.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 5 

## Lifecycles 

### 5.1 Note lifecycle 

|STATE|ENTERED WHEN|PERMITTED TRANSITIONS|
|---|---|---|
|Draft|Business begins a raise.|→ Submitted,→ Withdrawn|
|Submitted|Business submits for<br>underwriting.|→ Audit,→ Declined|
|Audit|Underwriting cleared; awaiting<br>Audit Partner co-signature.|→ Approved,→ Declined|
|Approved|Co-signed and cleared for<br>listing.|→ Live|
|Live|Published and accepting<br>investment.|→ Funded,→ Expired,→ Withdrawn|
|Funded|Target reached; disbursement<br>executed.|→ Repaying|
|Repaying|Schedule active.|→ Matured,→ Arrears|
|Arrears|A scheduled instalment is unpaid<br>past grace.|→ Repaying,→ Default|
|Matured|All instalments settled.|Terminal|
|Default|Recovery process initiated.|→ Recovered,→ Written off|
|Expired|Listing validity elapsed without<br>full funding.|Terminal; committed funds returned|



### 5.2 Monthly report lifecycle 

|STATE|DESCRIPTION|
|---|---|
|Drafting|Reporting window open; business preparing upload.|
|Submitted|Business has uploaded the statement, note and photographs. Dispatch triggered.|
|Pending audit|Assigned to an Audit Partner; on-site visit outstanding.|
|Co-signed|Partner has completed procedures, attached evidence and applied the verification seal.|
|Published|Visible to all holders of the note. Immutable thereafter.|
|Disputed|A party has raised a dispute; under Admin resolution.|
|SLA breached|Window closed without publication. Escalation and yield freeze triggered.|



### 5.3 Audit Partner lifecycle 

Pending verification → ICPAR licence checked → engagement terms signed in-app → biometrics bound → Active → (Suspended / Yield-frozen) → Reinstated or Terminated. Licence expiry MUST automatically suspend dispatch eligibility. 

ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 6 

## Business rules 

### 6.1 Underwriting and capacity 

|ID|RULE|
|---|---|
|**`BR-10`**|Capacity MUST be computed from verified cash flow. A business MUST NOT be able to request an<br>amount; it accepts an offer at or below computed capacity.|
|**`BR-11`**|A note MUST NOT be listed for more than the issuer's computed capacity at the time of listing.|
|**`BR-12`**|Capacity MUST account for existing Rozine obligations and any disclosed external debt service.|
|**`BR-13`**|Capacity computation MUST use a volatility-trimmed measure of net operating cash flow so that a<br>single exceptional month cannot inflate the offer.|
|**`BR-14`**|DSCR below 1.00× MUST result in decline. DSCR between 1.00× and 1.25× MUST require enhanced<br>procedures and manual approval. DSCR at or above 1.25× MAY auto-approve subject to co-signature<br>and KYB.|
|**`BR-15`**|A minimum verified statement history MUST be present before any capacity is offered.|
|**`BR-16`**|A declined business MUST retain its profile, continue to be re-scored as new statements arrive, and be<br>re-eligible automatically without reapplying from zero.|
|**`BR-17`**|Capacity override MUST be restricted to Superadmin, require a stated reason, and be logged and<br>surfaced on the note record permanently.|



### 6.2 Pricing and terms 

|ID|RULE|
|---|---|
|**`BR-20`**|Permitted tenors are exactly 3, 6, 9 and 12 months. No other tenor MAY be offered.|
|**`BR-21`**|Return MUST be expressed as a flat total return on principal, not as an annualised rate. The term "APR"<br>MUST NOT appear in any user-facing surface.|
|**`BR-22`**|Total return MUST be at least 10% and at most 15% of principal, on every note, at every tenor, without<br>exception.|
|**`BR-23`**|Total return MUST be a function of rating and tenor within those bounds, and MUST be deterministic<br>and explainable.|
|**`BR-24`**|Repayment MUST be in fixed monthly instalments comprising principal and return, known in full at the<br>time of investment.|
|**`BR-25`**|Early repayment MUST NOT reduce the total return owed to investors.|
|**`BR-26`**|The business MUST see the total cost of capital in francs, and the investor MUST see the total amount<br>returned in francs, before committing.|
|**`BR-27`**|Minimum investment is RWF 5,000. Units MUST be priced so that this minimum is always achievable.|
|**`BR-28`**|A listing MUST expire 30 days after going live if not fully funded; committed funds MUST be returned in<br>full with no fee.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### 6.3 Rating 

|ID|RULE|
|---|---|
|**`BR-30`**|The platform MUST publish exactly one measure of business quality. Any second competing score is<br>prohibited.|
|**`BR-31`**|The rating MUST be expressed on a 0.0–5.0 scale with one decimal place, everywhere, in every app<br>including Admin and Auditor.|
|**`BR-32`**|Bands MUST be: Strong ≥4.0; Stable ≥3.0; Weak ≥2.0; Distressed< 2.0. Band boundaries MUST fall on<br>whole numbers.|
|**`BR-33`**|Presentation MUST lead with the band word, support it with the numeric rating, and reinforce with the<br>band colour.|
|**`BR-34`**|"Stable" MUST be presented as the healthy default, never as a warning state.|
|**`BR-35`**|A business rated Distressed MUST NOT be listable. Its existing notes enter active monitoring.|
|**`BR-36`**|The rating MUST be recomputed on every verified monthly report and on any material event.|
|**`BR-37`**|The rating at time of issue MUST be recorded immutably on the note; the live rating MUST also be<br>shown to holders.|



### 6.4 Verification and reporting 

|ID|RULE|
|---|---|
|**`BR-40`**|No note MAY be listed without an Audit Partner co-signature on the originating verification.|
|**`BR-41`**|The monthly reporting window is the 1 st to the 7 th inclusive. Submission and co-signature MUST both<br>occur within it.|
|**`BR-42`**|A report MUST NOT be published without a valid verification seal from an accredited, unexpired Audit<br>Partner.|
|**`BR-43`**|Evidence photographs MUST be captured through the in-app camera with live geolocation. Gallery<br>upload MUST be rejected.|
|**`BR-44`**|Captured geolocation MUST be cross-checked against the registered premises; a material discrepancy<br>MUST flag the report for review.|
|**`BR-45`**|A published report MUST be immutable. Corrections MUST be issued as an amendment linked to the<br>original, never as an edit.|
|**`BR-46`**|An SLA breach MUST freeze the responsible Audit Partner's yield share pending Admin resolution, and<br>MUST flag the note to holders.|
|**`BR-47`**|The business note and the auditor note are each limited to 100 characters; photographs are limited to<br>five per party per report.|
|**`BR-48`**|Flash Audits MUST be randomly triggerable with a 24-hour completion requirement, and MUST be<br>indistinguishable in advance from routine dispatch.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### 6.5 Audit Partner integrity 

|ID|RULE|
|---|---|
|**`BR-50`**|Only a verified ICPAR member with a current practising certificate MAY be accredited or receive<br>dispatch.|
|**`BR-51`**|Dispatch MUST be restricted to partners whose office is within 30 km of the business premises, subject<br>to capacity.|
|**`BR-52`**|A partner MUST NOT exceed the configured concurrent-engagement cap.|
|**`BR-53`**|Assignments MUST rotate; a partner MUST NOT steward the same business indefinitely.|
|**`BR-54`**|A partner MUST NOT be assigned to a business with which a declared conflict of interest exists.|
|**`BR-55`**|Partner compensation is 25% of the service fee collected on notes they steward, payable monthly on<br>satisfactory SLA performance.|
|**`BR-56`**|Licence expiry MUST automatically suspend dispatch eligibility without staff action.|



### 6.6 Money and fees 

|ID|RULE|
|---|---|
|**`BR-60`**|Investor funds MUST be held in segregated accounts and MUST NOT be commingled with Rozine<br>operating funds at any time.|
|**`BR-61`**|Rozine MUST NOT take principal risk, guarantee capital, or represent any note as capital-protected.|
|**`BR-62`**|The fee schedule is: listing fee (business, fixed, on listing); service fee 2% of each repayment<br>(business); repayment fee 1% of each payout (investor); secondary fee 3% of each trade (seller). No<br>other fee MAY be charged.|
|**`BR-63`**|25% of every service fee collected MUST be routed to the stewarding Audit Partner.|
|**`BR-64`**|All fees MUST be disclosed in francs before the user commits to the action that incurs them.|
|**`BR-65`**|Every movement of value MUST produce balanced double-entry ledger records. Reversals MUST be<br>posted as new compensating entries referencing the original.|
|**`BR-66`**|Disbursement MUST occur only after the note is fully funded and all conditions precedent are satisfied.|
|**`BR-67`**|Repayments MUST be allocated to holders pro rata to units held on the record date.|



### 6.7 Secondary market 

|ID|RULE|
|---|---|
|**`BR-70`**|Only fully settled holdings in notes that are Repaying and current MAY be listed for sale.|
|**`BR-71`**|Holdings in notes in Arrears, Default or Disputed status MUST NOT be tradable.|
|**`BR-72`**|The buyer MUST see the note's current rating, health status, latest published report and remaining<br>schedule before purchase.|
|**`BR-73`**|Settlement MUST be atomic: units and funds transfer together or not at all.|
|**`BR-74`**|Admin MUST be able to halt the secondary market globally or per note, immediately.|
|**`BR-75`**|Rozine MUST NOT act as principal, market maker or price-setter on the secondary market.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### 6.8 Pulse 

|ID|RULE|
|---|---|
|**`BR-80`**|Pulse MUST NOT collect funds, issue any instrument, or create any binding obligation on either party.|
|**`BR-81`**|Every Pulse surface MUST carry a clear non-binding demand-simulation disclosure.|
|**`BR-82`**|A pre-qualification figure MUST be computed by the same engine used in production, and MUST be<br>labelled indicative and subject to full verification.|
|**`BR-83`**|Registrations MUST capture name, one contact method (phone or email),province and district, and<br>MUST issue a sequence number.|
|**`BR-84`**|Pulse counters displayed publicly MUST reflect real registration activity, never fabricated figures.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 7 

## Functional requirements — Investor app 

|ID|CAPABILITY|REQUIREMENT|
|---|---|---|
|**`FR-100`**|Onboarding|Register, verify identity to the required KYC tier, accept terms and risk<br>disclosure, and set up a wallet before any investment is permitted.|
|**`FR-101`**|Deal discovery|Browse live notes as a card deck with filters by sector, tenor, rating band<br>and yield, and a curated ordering.|
|**`FR-102`**|Deal detail|View verified financials, rating and its basis, funding progress, tenor, total<br>return, repayment schedule, use of funds, business photographs, and the<br>Audit Partner's identity and licence.|
|**`FR-103`**|Evidence access|Open every published monthly report for the note, including parsed<br>figures, both parties'notes and photographs, geolocation and the<br>verification seal.|
|**`FR-104`**|Investment|Select units, see the exact amount invested and amount returned in francs,<br>review all fees, and confirm. Minimum RWF 5,000.|
|**`FR-105`**|Watchlist&follow|Save notes and follow businesses to be alerted to new raises.|
|**`FR-106`**|Portfolio|View total value, invested principal, projected return, positions, upcoming<br>payouts, diversification by sector, rating mix, and idle wallet balance.|
|**`FR-107`**|Holding detail|Per position: schedule, payouts received and outstanding, current rating<br>and health, arrears warnings, and the report history.|
|**`FR-108`**|Secondary market|List a holding for sale at a chosen price, browse and purchase others'<br>listings, and track order state to settlement.|
|**`FR-109`**|Wallet|Deposit, withdraw, view balance and a complete transaction history with<br>per-transaction detail.|
|**`FR-110`**|Notifications|Receive and review alerts for new reports, payouts, funding milestones,<br>arrears, disputes and platform notices.|
|**`FR-111`**|Education|Access lessons and FAQs explaining the rating, the fixed-return structure,<br>fees, risk and the secondary market.|
|**`FR-112`**|Profile|Manage personal information, KYC documents, bank and MoMo details,<br>security settings, notification preferences, statements and support.|
|**`FR-113`**|Risk disclosure|Present unavoidable, plain-language risk disclosure at first investment and<br>on every deal page. Capital loss MUST be stated explicitly.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 8 

## Functional requirements — Business app 

|ID|CAPABILITY|REQUIREMENT|
|---|---|---|
|**`FR-200`**|Onboarding&KYB|Register the business, capture registration details, owner identity, premises<br>geolocation and contact; complete KYB to the required tier. No tax<br>identifier MAY be requested.|
|**`FR-201`**|Statement ingest|Upload bank and MoMo statements as PDF. Accepted evidence types:<br>MoMo statement, bank statement, audited cash transactions, POS data.|
|**`FR-202`**|Parsing feedback|Show parsed inflow, outflow and derived series for confirmation, with the<br>ability to flag a parsing error for review.|
|**`FR-203`**|Capacity&rating|Display computed capacity, rating with band, indicative total return by<br>tenor, and a plain-language explanation of each.|
|**`FR-204`**|Create a raise|Choose an amount at or below capacity, choose a tenor from 3/6/9/12,<br>state use of funds, attach photographs, and preview the exact total cost in<br>francs and the monthly repayment.|
|**`FR-205`**|Submission&tracking|Submit for underwriting and track state through audit, approval, listing,<br>funding and disbursement, with reasons shown at every step.|
|**`FR-206`**|Monthly report|Within the 1 st–7th window: upload the period statement, add a note (≤100<br>characters) and up to five photographs, and submit for Audit Partner co-<br>signature.|
|**`FR-207`**|Audit coordination|See the assigned Audit Partner, expected visit, co-signature state, and any<br>outstanding request from the partner.|
|**`FR-208`**|Repayments|View the full schedule, pay an instalment, see fees applied, and receive<br>arrears warnings before and after a due date.|
|**`FR-209`**|Standing&health|See current rating, health status, reporting compliance history and the<br>specific factors improving or damaging standing.|
|**`FR-210`**|Dispute|Raise a dispute against a report or an audit finding, with reason and<br>evidence, and track resolution.|
|**`FR-211`**|Wallet|Receive disbursement, hold balance, withdraw to a registered bank or<br>MoMo account, and view full transaction history.|
|**`FR-212`**|Education|Access guidance on improving rating, managing cash flow, preparing for<br>an audit visit, and understanding fees.|
|**`FR-213`**|Decline handling|On decline, present the computed figures, the specific shortfall, and the<br>concrete conditions under which the business would qualify.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 9 

## Functional requirements — Auditor app 

|ID|CAPABILITY|REQUIREMENT|
|---|---|---|
|**`FR-300`**|Accreditation|Register, submit ICPAR member ID and practising certificate, complete<br>biometric binding, and await verification. Dispatch MUST be blocked until<br>Active.|
|**`FR-301`**|Engagement signing|Review and execute the master services agreement and ISRS 4400<br>agreed-upon-procedures terms inside the app, with a retained signed<br>record.|
|**`FR-302`**|Job queue|Receive dispatched jobs within the 30 km radius, with business detail,<br>location, deadline and job type (routine or Flash Audit).|
|**`FR-303`**|Flash Audit timer|Display a live 24-hour countdown for Flash Audits with escalating urgency<br>and breach consequence stated.|
|**`FR-304`**|On-site capture|Capture geo-tagged photographs through the in-app camera only. Gallery<br>selection MUST be unavailable.|
|**`FR-305`**|Procedures checklist|Work through the agreed-upon procedures as a structured checklist,<br>recording findings against each.|
|**`FR-306`**|Statement verification|Review the parsed figures against source documents and on-site<br>observation; confirm, correct or reject with reason.|
|**`FR-307`**|Co-signature|Apply the verification seal, binding auditor identity, licence, report contents<br>and timestamp. Seal MUST be irreversible.|
|**`FR-308`**|Portfolio|View all stewarded businesses, their reporting history, health and<br>upcoming obligations.|
|**`FR-309`**|Earnings|See yield share accrued, paid and frozen, per note and in total, with the<br>basis of each calculation.|
|**`FR-310`**|Academy|Complete continuing-education modules covering the platform's metrics,<br>formulas, procedures and ethics, with completion recorded.|
|**`FR-311`**|Offline tolerance|Complete a full field capture without connectivity and synchronise reliably<br>when connectivity returns, without data loss.|
|**`FR-312`**|Conflict declaration|Declare a conflict of interest and decline a job, with the declaration<br>recorded and the job re-dispatched.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 0 

## Functional requirements — Admin console 

The Admin console MUST provide complete visibility and command over every entity and action in the ecosystem. Any operation possible in the system MUST be observable and, where appropriate, reversible from Admin. 

ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

|ID|AREA|REQUIREMENT|
|---|---|---|
|**`FR-400`**|Command dashboard|Present live platform state: capital deployed, active notes, funding health,<br>attention items requiring action, and quick commands.|
|**`FR-401`**|Business directory|Search, filter and inspect every business; view rating, capacity, notes,<br>reports, standing and full history; freeze, adjust KYB tier, or override<br>capacity.|
|**`FR-402`**|Investor directory|Search, filter and inspect every investor; view KYC state, wallet, holdings,<br>orders and activity; freeze or adjust tier.|
|**`FR-403`**|Auditor network|ICPAR verification desk for pending accreditations with licence detail and<br>manual override; spatial dispatch map showing partner radius, capacity<br>and load; reassign engagements; suspend, terminate or reinstate; freeze or<br>release yield.|
|**`FR-404`**|Application queue|Review submitted applications with full underwriting basis; approve,<br>decline or return with reason; enforce tier rules.|
|**`FR-405`**|Note management|Inspect any note across its entire lifecycle; force state transitions where<br>legitimate, with reason and log.|
|**`FR-406`**|Monthly audit compliance|Board of every report by state (Drafting, Pending audit, Published, SLA<br>breached, Disputed); open any report in a detail panel with full evidence;<br>escalate, reassign, freeze yield or resolve.|
|**`FR-407`**|Secondary market|Monitor orders and settlement; halt or resume trading globally or per note;<br>cancel an order with reason.|
|**`FR-408`**|Treasury&ledger|View the full double-entry ledger; reconcile segregated accounts; post<br>manual entries and reversals under dual approval; monitor fee revenue by<br>line.|
|**`FR-409`**|Risk&distress|Distressed-note board with outstanding amount, days late, risk band and<br>probability of default; open a review panel with the why-flagged basis,<br>recommended action and one-click execution.|
|**`FR-410`**|Compliance|Case management for AML alerts and investigations; document review;<br>freeze powers; suspicious-activity recording.|
|**`FR-411`**|Policy engine|Amend every configurable parameter—DSCR thresholds, tenor set, return<br>floor and ceiling, fee rates, yield share, dispatch radius, concurrency caps,<br>SLA windows, exposure limits—with versioning and attribution.|
|**`FR-412`**|App control|Per-app feature flags, maintenance mode and forced-update controls for<br>Investor, Business, Auditor and Pulse.|
|**`FR-413`**|Staff&roles|Create, modify and disable staff accounts; assign roles; review each role's<br>permission set.|
|**`FR-414`**|Audit trail|Complete, immutable, searchable record of every administrative action<br>with actor, target, action, reason and timestamp.|
|**`FR-415`**|Act-as|View any user's live application state read-only, clearly indicated and<br>logged.|
|**`FR-416`**|Messaging|Broadcast to a targeted audience across apps; manage templates and<br>automated notification rules.|
|**`FR-417`**|Disputes|Intake, investigate and resolve disputes between businesses and Audit<br>Partners, with the outcome recorded on the report.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

|ID|AREA|REQUIREMENT|
|---|---|---|
|**`FR-418`**|Supervisory reporting|Generate and export the reports required by the regulator, and support a<br>read-only supervisor seat.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 1 

## Functional requirements — Pulse and shared core 

### 11.1 Pulse 

|ID|CAPABILITY|REQUIREMENT|
|---|---|---|
|**`FR-500`**|Investor pledge|Set an intended amount, see the projected total return, register name,<br>contact method, province and district, and receive a sequence number.|
|**`FR-501`**|Business pre-<br>qualification|Upload one statement and receive a real computed pre-qualified amount,<br>rating and indicative yield, produced by the production engine.|
|**`FR-502`**|Shareable pass|Issue a downloadable and shareable pass carrying the registrant's name,<br>figure and sequence number, for organic distribution.|
|**`FR-503`**|Live counters|Display real, live aggregates of registration activity on both sides.|
|**`FR-504`**|Sample deals|Show a rotating sample of pre-qualified businesses with term, yield, rating<br>and the amount a pledger would earn.|
|**`FR-505`**|Disclosure|State prominently that this is a non-binding demand simulation and that no<br>funds are collected or issued.|
### 11.2 Shared transactional core

|ID|CAPABILITY|REQUIREMENT|
|**`FR-600`**|Single source of truth|All apps MUST read and write the same core. No app MAY hold private<br>authoritative state.|
|**`FR-601`**|Live propagation|A committed change MUST be reflected across every affected app without<br>a manual refresh.|
|**`FR-602`**|Deterministic<br>computation|Capacity, DSCR, rating, pricing, schedules, dispatch and fee calculations<br>MUST be reproducible from stored inputs.|
|**`FR-603`**|Idempotency|Every financial operation MUST be idempotent; a retried request MUST<br>NOT double-post.|
|**`FR-604`**|Event log|Every state transition MUST emit a durable event usable for notification,<br>analytics and reconstruction.|
|**`FR-605`**|Policy as data|All thresholds and rates MUST be configurable data, versioned and<br>attributed—never hard-coded.|
|**`FR-606`**|Expiry engine|Automatically expire listings at 30 days, return committed funds, and notify<br>all parties.|
|**`FR-607`**|Scheduler|Open and close the monthly reporting window, trigger dispatch, escalate<br>SLA breaches, release or freeze yield, and drive repayment due dates.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 2 

## Computation specification 

#### NORMATIVE FORMULAS 

```
NOCFm   = Inflowm − Outflowm
CFADS   = trimmed_mean(NOCF) − ExistingDebtService − OwnerDraw
DSCR    = CFADS ÷ ProposedMonthlyRepayment
Capacity = (CFADS ÷ TargetDSCR × Tenor) ÷ (1 + TotalReturn)
Instalment = (Principal × (1 + TotalReturn)) ÷ Tenor
Rating  = clamp(EngineScore ÷ 20, 0.0, 5.0) rounded to 1 dp
Coverage = Inflowperiod ÷ (Outflowperiod + DebtServiceperiod)
```

Figure 1 — All monetary results round to the nearest franc; rates round to one decimal place. Capacity is solved from serviceable repayment, never from a requested amount. 

|ID|REQUIREMENT|
|---|---|
|**`FR-700`**|Rounding MUST be applied consistently and specified per computation; the sum of instalments MUST<br>exactly equal principal plus total return, with any residual applied to the final instalment.|
|**`FR-701`**|Every computed figure shown to a user MUST be traceable to its inputs and reproducible on demand by<br>Admin.|
|**`FR-702`**|Where a computation depends on a policy value, the governing policy version MUST be recorded with<br>the result.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 3 

## Non-functional requirements 

|ID|ATTRIBUTE|REQUIREMENT|
|---|---|---|
|**`NFR-1`**|Availability|Core transactional services MUST target high availability, with planned<br>maintenance announced in advance and surfaced through app maintenance<br>mode.|
|**`NFR-2`**|Data integrity|No committed financial transaction MAY be lost or double-applied under any<br>failure mode. Reconciliation MUST detect and report any imbalance.|
|**`NFR-3`**|Security|Encryption in transit and at rest; managed key rotation; least-privilege<br>access; secrets never in source; regular penetration testing.|
|**`NFR-4`**|Authentication|Strong authentication for all parties; multi-factor for staff and Audit Partners;<br>biometric binding for Audit Partners; session expiry and revocation.|
|**`NFR-5`**|Auditability|Every state change MUST be attributable to an actor and reconstructible in<br>sequence.|
|**`NFR-6`**|Performance|Interactive screens MUST remain responsive on mid-range Android devices<br>over intermittent mobile networks; statement parsing MAY be asynchronous<br>with clear progress feedback.|
|**`NFR-7`**|Resilience|The Auditor app MUST function fully offline in the field and synchronise<br>without loss.|
|**`NFR-8`**|Accessibility|Adequate contrast, legible minimum type sizes, and touch targets no smaller<br>than 44 px.|
|**`NFR-9`**|Localisation|Architecture MUST support Kinyarwanda, English and French; all user-facing<br>strings externalised.|
|**`NFR-10`**|Clarity|Financial figures MUST be shown in full francs with thousands separators<br>where precision matters. No user-facing jargon without an inline explanation.|
|**`NFR-11`**|Scalability|The platform MUST scale to thousands of concurrent monthly audits without<br>a proportional increase in Rozine operations headcount.|
|**`NFR-12`**|Recoverability|Backups MUST be automated, encrypted, geographically separated and<br>restore-tested on a defined cycle.|
|**`NFR-13`**|Observability|Structured logging, metrics and alerting across all services, with dashboards<br>for the platform KPIs.|
|**`NFR-14`**|Data residency|Personal and financial data MUST be stored in compliance with Rwandan<br>data-protection requirements.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 4 

## Integrations and compliance 

### 14.1 Integrations 

|ID|SYSTEM|PURPOSE|
|---|---|---|
|**`IR-1`**|Mobile money providers|Disbursement, collection, wallet funding and withdrawal.|
|**`IR-2`**|Commercial banks|Segregated account operation, settlement and reconciliation.|
|**`IR-3`**|Identity verification|Individual identity confirmation for KYC.|
|**`IR-4`**|Business registry|Confirmation of registration and ownership for KYB.|
|**`IR-5`**|ICPAR register|Verification of member ID, practising certificate and standing.|
|**`IR-6`**|Messaging providers|SMS, email and push delivery of notifications.|
|**`IR-7`**|Mapping/geocoding|Premises geolocation, dispatch radius and evidence cross-check.|
|**`IR-8`**|Supervisory reporting|Delivery of periodic returns to the regulator.|



### 14.2 Compliance requirements 

|ID|REQUIREMENT|
|---|---|
|**`CR-1`**|KYC and KYB MUST be completed and verified before any transaction is permitted, with tiered limits by<br>verification level.|
|**`CR-2`**|AML transaction monitoring MUST run continuously, with case management, escalation and regulatory<br>reporting.|
|**`CR-3`**|Sanctions and PEP screening MUST be performed at onboarding and periodically thereafter.|
|**`CR-4`**|Investor funds MUST be segregated and reconciled daily.|
|**`CR-5`**|Every listing MUST carry complete disclosure: verified financials, rating basis, all fees, tenor, schedule<br>and explicit risk warning.|
|**`CR-6`**|The platform MUST NOT represent, imply or permit marketing suggesting capital protection or<br>guaranteed return.|
|**`CR-7`**|Investment limits appropriate to investor category MUST be enforceable by policy.|
|**`CR-8`**|A documented complaints and dispute-resolution process MUST be available to all users and evidenced<br>in the system.|
|**`CR-9`**|Records MUST be retained for the statutory period and be producible on regulatory request.|
|**`CR-10`**|Personal data processing MUST comply with Rwandan data-protection law, with lawful basis, consent<br>capture and subject-rights handling.|
|**`CR-11`**|Audit Partner engagements MUST be performed under ISRS 4400 agreed-upon procedures with<br>retained working papers.|
|**`CR-12`**|The platform MUST support a read-only supervisor seat providing live visibility of the operating state.|
|**`CR-13`**|Sandbox caps and conditions MUST be enforceable as policy values, without code change.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 5 

## Assumptions, constraints, dependencies 

|CLASS|STATEMENT|
|---|---|
|Assumption|Target businesses transact predominantly through bank and mobile-money channels,<br>producing machine-readable statement history.|
|Assumption|ICPAR-accredited accountants are available in sufficient number and geographic spread to<br>serve the dispatch radius.|
|Assumption|The RCMA sandbox remains an available pathway for a platform of this type.|
|Assumption|Retail investors will accept a fixed-return, capital-at-risk instrument when verification is<br>credible and disclosure is plain.|
|Constraint|Rozine MUST NOT take deposits, lend as principal, or guarantee capital.|
|Constraint|All instruments are denominated in Rwandan francs in v1.|
|Constraint|Tenors are limited to 3, 6, 9 and 12 months; total return is bounded at 10–15% flat.|
|Constraint|No tax-authority data may be used or stored anywhere in the system.|
|Dependency|Payment-rail agreements with mobile-money providers and a banking partner for segregated<br>accounts.|
|Dependency|A working relationship with ICPAR for licence verification and disciplinary referral.|
|Dependency|Regulatory admission to the sandbox before live operation.|
|Dependency|Parsing accuracy sufficient across the major statement formats in market.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

S E C T I O N 1 6 

## Acceptance criteria 

|ID|CRITERION|
|---|---|
|**`AC-1`**|A business can be onboarded, verified, statement-parsed, capacity-computed, co-signed, listed,<br>funded, disbursed, serviced across a full schedule, and matured—end to end, on production rails.|
|**`AC-2`**|An investor can register, verify, fund a wallet, invest from RWF 5,000,receive a monthly verified report,<br>receive scheduled payouts, sell on the secondary market, and withdraw.|
|**`AC-3`**|An Audit Partner can be accredited against the ICPAR register, sign engagement terms in-app, receive a<br>dispatched job, complete an on-site capture offline, and apply a verification seal that publishes to<br>holders.|
|**`AC-4`**|Admin can observe and command every entity and action described in Section 10,with every action<br>gated by role and logged with a reason.|
|**`AC-5`**|No note can be created above computed capacity, at a disallowed tenor, or outside the 10–15% return<br>band, by any path including Admin override without explicit Superadmin action and log.|
|**`AC-6`**|A missed reporting window automatically breaches SLA, freezes the partner's yield, flags the note and<br>notifies all affected parties without staff intervention.|
|**`AC-7`**|The ledger reconciles exactly: assets held equal obligations owed, across every account, every day.|
|**`AC-8`**|A regulator with a read-only seat can inspect the live operating state and reconstruct any historical<br>decision from stored inputs.|
|**`AC-9`**|No surface in any app, or any stored record, contains a reference to RRA, EBM, TIN, tax sync or a tax<br>compliance badge.|
|**`AC-10`**|Every app displays the rating identically: one word, one number out of five to one decimal, one colour.|
|**`AC-11`**|Pulse operates with no funds collected and no instrument issued, and its counters reflect real<br>registrations.|
|**`AC-12`**|A penetration test and an independent security review are completed with no unresolved high-severity<br>finding.|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

A P P E N D I X 

## Parameters and glossary 

### A. Configurable parameters (policy-managed) 

|PARAMETER|BASELINE VALUE|
|---|---|
|Permitted tenors|3, 6, 9, 12 months|
|Total return floor/ceiling|10% / 15% flat on principal|
|Minimum investment|RWF 5,000|
|Auto-approve DSCR|≥1.25×|
|Audit-band DSCR|1.00× – 1.25×|
|Rating band thresholds|4.0 / 3.0 / 2.0|
|Reporting window|1st– 7th of month|
|Flash Audit completion|24 hours|
|Dispatch radius|30 km|
|Audit Partner yield share|25% of service fee|
|Service fee|2% of each repayment|
|Repayment fee|1% of each payout|
|Secondary fee|3% of trade value|
|Listing validity|30 days|
|Note/photo limits|100 characters; 5 photographs per party per report|



ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

### B. Glossary 

|TERM|DEFINITION|
|---|---|
|Note|The instrument issued by a business and held by investors, carrying a fixed total return<br>and fixed monthly repayments.|
|Capacity|The maximum a business may raise, computed from verified cash flow. Never<br>negotiable upward.|
|CFADS|Cash flow available for debt service—verified operating cash flow after existing<br>obligations and owner drawings, volatility-adjusted.|
|DSCR|Debt service coverage ratio—CFADS divided by proposed monthly repayment.|
|Audit Partner|ICPAR-accredited CPA contracted to perform on-site verification and co-sign reports<br>under ISRS 4400.|
|Verification seal|Cryptographic hash binding auditor identity, licence, report contents and timestamp to a<br>published report.|
|Flash Audit|Randomly triggered 24-hour re-verification of a business and its Audit Partner.|
|Health status|Period-level condition derived from the verified report: Healthy, Watch or Distressed.|
|ISRS 4400|International Standard on Related Services governing agreed-upon-procedures<br>engagements.|
|ICPAR|Institute of Certified Public Accountants of Rwanda.|
|RCMA|Rwanda Capital Market Authority.|
|Pulse|Pre-launch demand-aggregation portal. Collects no funds and issues no instrument.|



This specification is confidential and is issued for build, compliance and evaluation purposes. It does not constitute an offer of securities or an invitation to invest. Parameter values reflect current product design and are subject to regulatory approval. Rozine Technologies Ltd · Kigali, Rwanda. 

ROZINE TECHNOLOGIES LTD 

BUSINESS REQUIREMENTS SPECIFICATION · V1.0 

CONFIDENTIAL 

