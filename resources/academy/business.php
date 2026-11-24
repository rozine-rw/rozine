<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Business Academy content (BRS v3.0 RDY-R07)
|--------------------------------------------------------------------------
|
| The core syllabus of each readiness stage: one module set per stage, the same for every business
| at that stage. Each module has 8 to 12 flashcards and a bank of at least 12 questions; a test
| draws 10 of them in a new order. Every question has three options and `answer` is the place of
| the right one (0, 1 or 2).
|
| Drafted by Rozine engineering as a first version. OD-24 proposes the Auditor Network as the
| content author: they review and replace this text, and supply the Kinyarwanda version. Until then
| every language reads the English text.
|
*/

return [
    'bookkeeping' => [
        'stage' => 1,
        'en' => [
            'title' => 'Keeping your books',
            'summary' => 'Record every sale and every payment on the day it happens.',
            'cards' => [
                ['Books', 'Your books are the record of every franc that comes in and goes out of the business, written in one place.'],
                ['Same day', 'Write each sale and each payment on the day it happens. Records written later are where gaps begin.'],
                ['Cash sales', 'Cash sales are the easiest to forget. Count the till at the close of each day and record the total.'],
                ['Receipts', 'Keep a receipt or invoice for every cost. A cost without a receipt is hard for your Audit Partner to verify.'],
                ['One record per transaction', 'Each line says the date, the amount, who paid or was paid, and what it was for.'],
                ['Business and personal', 'Money you take for yourself is "paid to owners". Record it as its own line, never as a business cost.'],
                ['Monthly close', 'At the end of each month, add up money in, costs by category and money paid to owners.'],
                ['Your Record Gap', 'Each month your Audit Partner compares your figures with what they verify. The difference is your Record Gap.'],
                ['Templates', 'You do not need software to keep good books. A notebook or a spreadsheet kept every day is enough.'],
            ],
            'questions' => [
                ['When should a cash sale be recorded?', ['At the end of the month', 'On the day it happens', 'When the Audit Partner visits'], 1],
                ['A cost has no receipt. What is the result?', ['It is hard to verify', 'It is always accepted', 'It becomes paid to owners'], 0],
                ['Money an owner takes for personal use is recorded as…', ['A staff cost', 'Paid to owners', 'Stock'], 1],
                ['What should each record show?', ['Only the amount', 'Date, amount, who and what for', 'Only the customer name'], 1],
                ['The best time to count the till is…', ['At the close of each day', 'Once a week', 'Only when cash runs low'], 0],
                ['Your Record Gap compares your figures with…', ['Last year\'s figures', 'What your Audit Partner verifies', 'Your competitor\'s figures'], 1],
                ['Do you need accounting software at this stage?', ['Yes, always', 'No, books kept daily are enough', 'Only for cash sales'], 1],
                ['At the monthly close you add up…', ['Money in, costs by category and paid to owners', 'Only money in', 'Only the bank balance'], 0],
                ['A supplier is paid in cash. You should…', ['Skip it, it is cash', 'Record it with its receipt that day', 'Record it next month'], 1],
                ['Which records are most often forgotten?', ['Bank transfers', 'Cash sales', 'Loan repayments'], 1],
                ['Your books are…', ['A record of every franc in and out', 'Only your bank statement', 'Your tax return'], 0],
                ['Mixing personal spending with business costs…', ['Lowers your Record Gap', 'Makes your costs wrong', 'Has no effect'], 1],
            ],
        ],
    ],
    'business_money' => [
        'stage' => 1,
        'en' => [
            'title' => 'Business money, tax and RSSB',
            'summary' => 'Run money through business accounts and keep tax and RSSB current.',
            'cards' => [
                ['Business-named accounts', 'All business money goes through bank or Mobile Money accounts in the business\'s name.'],
                ['Traceable revenue', 'At least 90% of your verified revenue must be traceable to business-named accounts.'],
                ['Why it matters', 'Money in a business account leaves a trail your Audit Partner can verify. Cash in a pocket does not.'],
                ['Bank cash sales', 'Deposit cash sales into the business account regularly so they become traceable.'],
                ['Tax clearance', 'Keep your RRA tax clearance current. When it expires you have 30 days to renew it.'],
                ['RSSB contributions', 'Declare and pay RSSB contributions for your employees each month.'],
                ['Grace period', 'A requirement that lapses becomes Due with a grace period. Past that day it is Overdue.'],
                ['Overdue', 'An Overdue requirement blocks your next raise and removes Ready status until it is Met again.'],
            ],
            'questions' => [
                ['Business money should go through…', ['The owner\'s personal account', 'Accounts in the business\'s name', 'Any family account'], 1],
                ['How much verified revenue must be traceable to business accounts?', ['At least 50%', 'At least 90%', 'Exactly 100%'], 1],
                ['Why deposit cash sales into the business account?', ['It makes them traceable', 'It lowers tax', 'It is required daily by law'], 0],
                ['After tax clearance expires, the grace period is…', ['7 days', '30 days', '1 year'], 1],
                ['RSSB contributions are paid…', ['Once a year', 'Each month', 'Only when audited'], 1],
                ['A requirement past its grace period is…', ['Met', 'Overdue', 'Lapsed forever'], 1],
                ['An Overdue requirement…', ['Blocks your next raise', 'Has no effect', 'Raises your return'], 0],
                ['Which leaves a trail your Audit Partner can verify?', ['Cash kept at home', 'Money in a business account', 'A verbal promise'], 1],
                ['Mobile Money in the business\'s name counts as…', ['A business-named account', 'Personal money', 'Cash'], 0],
                ['When a requirement lapses it becomes…', ['Due, with a grace period', 'Met', 'Removed'], 0],
                ['Who confirms each requirement is Met?', ['The business alone', 'Your Audit Partner, with evidence', 'Any investor'], 1],
                ['Paying staff in cash from a personal pocket…', ['Is traceable', 'Breaks the trail of business money', 'Is preferred'], 1],
            ],
        ],
    ],
    'management_accounts' => [
        'stage' => 2,
        'en' => [
            'title' => 'Accountant and management accounts',
            'summary' => 'Work with a qualified accountant and use accounting software.',
            'cards' => [
                ['Qualified accountant', 'From RWF 10M a month, the business needs a qualified accountant, employed or contracted.'],
                ['Accounting software', 'Software keeps books consistent and makes monthly figures quicker to produce.'],
                ['Grace period', 'This requirement has a 90-day grace period from the day it becomes Due.'],
                ['Chart of accounts', 'A chart of accounts names every category your money is recorded under, used the same way each month.'],
                ['Bank reconciliation', 'Each month, match your books to your bank and Mobile Money statements and explain each difference.'],
                ['Profit and loss', 'A monthly profit and loss shows money in, costs and what is left.'],
                ['Cost categories', 'Use the same six cost categories each month so months can be compared.'],
                ['Evidence', 'Your Audit Partner sights the accountant\'s contract and the software in use at the visit.'],
            ],
            'questions' => [
                ['From what monthly revenue is a qualified accountant required?', ['RWF 2.5M', 'RWF 10M', 'RWF 80M'], 1],
                ['The accountant may be…', ['Employed or contracted', 'Only employed full time', 'A family member without qualifications'], 0],
                ['The grace period for this requirement is…', ['30 days', '90 days', '180 days'], 1],
                ['A bank reconciliation matches your books to…', ['Your forecast', 'Your bank and Mobile Money statements', 'Last year\'s tax return'], 1],
                ['A monthly profit and loss shows…', ['Money in, costs and what is left', 'Only cash in the till', 'Only staff numbers'], 0],
                ['Why use the same cost categories each month?', ['So months can be compared', 'To lower costs', 'It is optional decoration'], 0],
                ['Accounting software mainly helps…', ['Keep books consistent', 'Avoid the Audit Partner', 'Raise prices'], 0],
                ['A chart of accounts is…', ['A list of categories used every month', 'A sales chart', 'A bank statement'], 0],
                ['How does your Audit Partner confirm this requirement?', ['By sighting the contract and the software', 'By phone only', 'They do not'], 0],
                ['A difference found in reconciliation should be…', ['Ignored', 'Explained', 'Moved to next year'], 1],
                ['Management accounts are produced…', ['Monthly', 'Every five years', 'Only when raising'], 0],
                ['Without software, the books…', ['Cannot exist', 'Are harder to keep consistent at this size', 'Are always wrong'], 1],
            ],
        ],
    ],
    'forecasting' => [
        'stage' => 2,
        'en' => [
            'title' => 'Forecasting money in',
            'summary' => 'Forecast next month from your verified average and signed orders.',
            'cards' => [
                ['Your forecast', 'Each month you forecast the money you expect next month, sent with your monthly figures.'],
                ['Start from the average', 'Your forecast is pre-filled with your verified 3-month average. That is the best starting point.'],
                ['Signed orders', 'Move the forecast more than 25% only with a signed order you can name.'],
                ['Forecast Error', 'Forecast Error compares each forecast with the money your Audit Partner verifies for that month.'],
                ['Bias', 'If your forecasts are always above what comes in, you are over-forecasting. Adjust down.'],
                ['Seasons', 'Some months are always busier. Use the same month last year as a guide, with evidence.'],
                ['Cash planning', 'A good forecast tells you when cash will be short, before it is.'],
                ['Targets', 'Your Forecast Error target gets tighter as your stage grows: 25%, 15%, 10%, then 7%.'],
            ],
            'questions' => [
                ['Your forecast is pre-filled with…', ['Your best month ever', 'Your verified 3-month average', 'Your competitor\'s sales'], 1],
                ['A change of more than 25% needs…', ['A signed order', 'Nothing', 'A new bank account'], 0],
                ['Forecast Error compares your forecast with…', ['The verified money in for that month', 'Your costs', 'Last year\'s forecast'], 0],
                ['Forecasts always above what comes in show…', ['Over-forecasting', 'Under-forecasting', 'No bias'], 0],
                ['The Forecast Error target at stage 2 is…', ['25%', '15%', '7%'], 1],
                ['A good forecast helps you…', ['See a cash shortfall before it happens', 'Avoid tax', 'Skip the monthly figures'], 0],
                ['Seasonal months should be forecast using…', ['A guess', 'The same month last year, with evidence', 'The highest month'], 1],
                ['When is the forecast sent?', ['With the monthly figures', 'Once a year', 'Only when raising'], 0],
                ['The target gets tighter as the business…', ['Grows stage by stage', 'Shrinks', 'Hires fewer staff'], 0],
                ['A verbal promise from a customer is…', ['A signed order', 'Not a signed order', 'Better than a signed order'], 1],
                ['If last month\'s forecast was too low, next time you should…', ['Keep it the same', 'Check why and adjust from evidence', 'Double it'], 1],
                ['The stage 4 Forecast Error target is…', ['7%', '15%', '25%'], 0],
            ],
        ],
    ],
    'finance_controls' => [
        'stage' => 3,
        'en' => [
            'title' => 'Finance team and controls',
            'summary' => 'A full-time finance manager and monthly management accounts.',
            'cards' => [
                ['Finance manager', 'From RWF 30M a month, the business needs a full-time qualified finance manager: CPA, ACCA or equivalent, with at least 3 years\' experience.'],
                ['Grace period', 'This requirement has a 120-day grace period.'],
                ['Monthly management accounts', 'Each month the finance manager produces management accounts: profit and loss, balance sheet and cash flow.'],
                ['Balance sheet', 'A balance sheet shows what the business owns, what it owes and what belongs to the owners.'],
                ['Cash flow', 'A cash flow statement shows where cash came from and where it went.'],
                ['Approvals', 'Set who may approve each kind of payment, and up to what amount.'],
                ['Separate duties', 'The person who prepares a payment should not be the only person who approves it.'],
                ['Month-end timetable', 'Close the books on the same day each month so figures are ready before your audit visit.'],
            ],
            'questions' => [
                ['From what monthly revenue is a full-time finance manager required?', ['RWF 10M', 'RWF 30M', 'RWF 80M'], 1],
                ['Which qualification fits the finance manager?', ['CPA, ACCA or equivalent', 'Any degree', 'None'], 0],
                ['How much experience is required?', ['At least 1 year', 'At least 3 years', 'At least 10 years'], 1],
                ['The grace period is…', ['60 days', '120 days', '365 days'], 1],
                ['Management accounts include…', ['Profit and loss, balance sheet and cash flow', 'Only sales', 'Only the bank balance'], 0],
                ['A balance sheet shows…', ['What the business owns and owes', 'Daily sales only', 'Staff rotas'], 0],
                ['A cash flow statement shows…', ['Where cash came from and went', 'Customer names', 'Tax rates'], 0],
                ['Separating duties means…', ['One person prepares and approves every payment', 'Preparing and approving are split', 'No one approves payments'], 1],
                ['Payment approval limits say…', ['Who may approve and up to what amount', 'How much to charge customers', 'When to pay tax'], 0],
                ['Why close the books on a fixed day?', ['Figures are ready before the audit visit', 'It lowers costs', 'It is decorative'], 0],
                ['Management accounts are produced…', ['Monthly', 'Yearly only', 'Never'], 0],
                ['A finance manager at this stage works…', ['Full time', 'One day a month', 'Only at year-end'], 0],
            ],
        ],
    ],
    'governance' => [
        'stage' => 3,
        'en' => [
            'title' => 'Board and governance',
            'summary' => 'A board that meets quarterly, with minutes and written delegation.',
            'cards' => [
                ['Board', 'From RWF 30M a month, or RWF 50M of Rozine principal, the business needs a board of at least 2 directors.'],
                ['Quarterly meetings', 'The board meets at least every quarter and keeps written minutes.'],
                ['Minutes', 'Minutes record who attended, what was decided and who will act.'],
                ['Delegation', 'A written payment delegation says which decisions management may take without the board.'],
                ['Grace period', 'This requirement has a 120-day grace period.'],
                ['Conflicts of interest', 'A director with a personal interest in a decision says so and stays out of it.'],
                ['Independent director', 'At stage 4 at least one director must be independent of the owners and management.'],
                ['Related parties', 'At stage 4 the business keeps a register of related parties and its dealings with them.'],
            ],
            'questions' => [
                ['How many directors must the board have at least?', ['1', '2', '7'], 1],
                ['The board meets at least…', ['Every quarter', 'Every five years', 'Only in a crisis'], 0],
                ['Minutes record…', ['Who attended, decisions and actions', 'Only the date', 'Sales for the quarter'], 0],
                ['A written payment delegation says…', ['What management may decide without the board', 'Staff salaries', 'Tax due'], 0],
                ['The grace period for the board requirement is…', ['30 days', '120 days', '2 years'], 1],
                ['A director with a personal interest in a decision should…', ['Vote first', 'Declare it and stay out', 'Keep quiet'], 1],
                ['Besides RWF 30M a month, the board is also required from…', ['RWF 50M of Rozine principal', 'Any loan', '10 employees'], 0],
                ['An independent director is required from…', ['Stage 2', 'Stage 4', 'Never'], 1],
                ['A related-party register lists…', ['Dealings with people connected to the business', 'All customers', 'Office furniture'], 0],
                ['Board minutes should be…', ['Written and kept', 'Spoken only', 'Optional'], 0],
                ['Who sights the minutes as evidence?', ['Your Audit Partner', 'Your customers', 'No one'], 0],
                ['Good governance mainly protects…', ['The business and its investors', 'Only the owner\'s pay', 'Competitors'], 0],
            ],
        ],
    ],
    'annual_audit' => [
        'stage' => 4,
        'en' => [
            'title' => 'Annual audit under IFRS for SMEs',
            'summary' => 'An independent annual audit and payments split between people.',
            'cards' => [
                ['Annual audit', 'From RWF 80M a month, the annual statements are audited under IFRS for SMEs.'],
                ['Who audits', 'The auditor is ICPAR-registered and is not the Rozine CPA who audits you monthly.'],
                ['Deadline', 'The audit applies from the next year-end after the requirement becomes Due.'],
                ['IFRS for SMEs', 'IFRS for SMEs is the accounting standard for small and medium businesses, used so statements can be compared.'],
                ['Audited statements', 'Audited statements include the auditor\'s opinion on whether they are fairly presented.'],
                ['Payments split', 'Payments are prepared by one person and approved by a different person.'],
                ['Audit file', 'Keep contracts, statements and board minutes in order through the year so the audit goes quickly.'],
                ['Bond track', 'Audited annual statements are one of the conditions for the bond track.'],
            ],
            'questions' => [
                ['From what monthly revenue is an annual audit required?', ['RWF 30M', 'RWF 80M', 'RWF 10M'], 1],
                ['The annual audit uses…', ['IFRS for SMEs', 'No standard', 'The owner\'s own rules'], 0],
                ['The auditor must be…', ['The Rozine CPA', 'ICPAR-registered and not the Rozine CPA', 'A director'], 1],
                ['The audit applies from…', ['The next year-end', 'Tomorrow', 'Five years later'], 0],
                ['Audited statements include…', ['The auditor\'s opinion', 'Only sales', 'Customer reviews'], 0],
                ['Payments at stage 4 must be…', ['Prepared and approved by different people', 'Approved by whoever prepares them', 'Never approved'], 0],
                ['Why keep an audit file through the year?', ['The audit goes quicker', 'To avoid the audit', 'It is not needed'], 0],
                ['IFRS for SMEs is…', ['An accounting standard', 'A tax form', 'A bank account'], 0],
                ['Audited annual statements are needed for…', ['The bond track', 'Opening a till', 'Paying RSSB'], 0],
                ['Can the Rozine CPA also do the annual audit?', ['Yes', 'No', 'Only in December'], 1],
                ['Statements under one standard can be…', ['Compared with other businesses', 'Hidden', 'Skipped'], 0],
                ['Who registers auditors in Rwanda?', ['ICPAR', 'RSSB', 'The business'], 0],
            ],
        ],
    ],
    'capital_markets' => [
        'stage' => 4,
        'en' => [
            'title' => 'Corporate bonds and the capital market',
            'summary' => 'What the bond track and an RSE referral ask of the business.',
            'cards' => [
                ['Corporate bond', 'A bond is a loan from many investors, repaid on fixed terms, often traded on a stock exchange.'],
                ['Bond track', 'Stage 3 or 4, Ready for 6 months in a row, at least 3 Rozine notes with every instalment collected on time, and audited annual statements.'],
                ['Bond-readiness file', 'A business on the bond track receives a bond-readiness file it can share with advisers.'],
                ['RSE Investment Clinic', 'The Rwanda Stock Exchange clinic helps businesses prepare for the capital market.'],
                ['Referral conditions', 'A limited company, at least 3 years trading, no major proceedings, and stage 3 or 4 Ready for 12 months.'],
                ['Consent', 'A referral to the RSE clinic is sent only with the business\'s consent.'],
                ['Disclosure', 'Listed issuers report to investors regularly. Monthly figures and audits are good practice for that.'],
                ['Regulator', 'The Capital Market Authority supervises public offers of bonds in Rwanda.'],
            ],
            'questions' => [
                ['A corporate bond is…', ['A loan from many investors on fixed terms', 'A share in the business', 'A grant'], 0],
                ['How long must a business be Ready for the bond track?', ['6 months in a row', '1 month', '5 years'], 0],
                ['How many Rozine notes does the bond track need at least?', ['1', '3', '10'], 1],
                ['Instalments on those notes must be…', ['Collected on time', 'Paid late', 'Skipped once'], 0],
                ['The bond track gives the business…', ['A bond-readiness file', 'Cash', 'A new label'], 0],
                ['The RSE clinic referral needs at least…', ['1 year trading', '3 years trading', '10 years trading'], 1],
                ['How long must the business be Ready for an RSE referral?', ['12 months', '3 months', '1 week'], 0],
                ['An RSE referral is sent…', ['Only with the business\'s consent', 'Automatically without asking', 'Never'], 0],
                ['Which business form fits an RSE referral?', ['A limited company', 'A sole trader', 'Any'], 0],
                ['Who supervises public bond offers in Rwanda?', ['The Capital Market Authority', 'RSSB', 'ICPAR'], 0],
                ['Which stages can join the bond track?', ['Stage 3 or 4', 'Stage 1 only', 'Any stage'], 0],
                ['Listed issuers are expected to…', ['Report to investors regularly', 'Stop reporting', 'Report once at listing'], 0],
            ],
        ],
    ],
];
